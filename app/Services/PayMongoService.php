<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Wrapper around the PayMongo v1 REST API.
 * Docs: https://developers.paymongo.com/reference
 */
class PayMongoService
{
    private string $baseUrl;
    private string $secretKey;
    private string $webhookSecret;

    public function __construct()
    {
        $this->baseUrl       = config('paymongo.api_url', 'https://api.paymongo.com/v1');
        $this->secretKey     = config('paymongo.secret_key', '');
        $this->webhookSecret = config('paymongo.webhook_secret', '');
    }

    // ── Create Payment Link ──────────────────────────────────────────────

    public function createPaymentLink(Invoice $invoice, string $description = ''): array
    {
        $amountCentavos = (int) round($invoice->balanceDue() * 100);

        if ($amountCentavos < 2000) {
            throw new \RuntimeException('Amount too small for PayMongo (minimum ₱20).');
        }

        $payload = [
            'data' => [
                'attributes' => [
                    'amount'      => $amountCentavos,
                    'currency'    => 'PHP',
                    'description' => $description ?: "Invoice {$invoice->invoice_number}",
                    'remarks'     => $invoice->invoice_number,
                ],
            ],
        ];

        $response = Http::withBasicAuth($this->secretKey, '')
            ->post("{$this->baseUrl}/links", $payload);

        if ($response->failed()) {
            Log::error('PayMongo createPaymentLink failed', [
                'status'  => $response->status(),
                'body'    => $response->json(),
                'invoice' => $invoice->invoice_number,
            ]);
            throw new \RuntimeException(
                'PayMongo error: ' . ($response->json('errors.0.detail') ?? $response->body())
            );
        }

        $data = $response->json('data');

        return [
            'id'           => $data['id'],
            'checkout_url' => $data['attributes']['checkout_url'],
            'reference'    => $data['attributes']['reference_number'] ?? $data['id'],
        ];
    }

    // ── Verify Webhook Signature ─────────────────────────────────────────

    /**
     * Verify the PayMongo webhook signature.
     *
     * Header format: t=<timestamp>,te=<test_hmac>,li=<live_hmac>
     * Signed string: "<timestamp>.<raw_body>"
     * HMAC key:      webhook secret key (whsk_...)
     */
    public function verifyWebhookSignature(string $rawBody, string $sigHeader): bool
    {
        if (empty($sigHeader) || empty($this->webhookSecret)) {
            Log::warning('PayMongo webhook: missing signature header or webhook secret');
            return false;
        }

        // Parse header — split by comma, then split each part on first '='
        $parts = [];
        foreach (explode(',', $sigHeader) as $part) {
            $eq = strpos($part, '=');
            if ($eq !== false) {
                $k = substr($part, 0, $eq);
                $v = substr($part, $eq + 1);
                $parts[trim($k)] = trim($v);
            }
        }

        $timestamp = $parts['t'] ?? null;
        // Use 'te' for test keys, 'li' for live keys
        $signature = str_starts_with($this->secretKey, 'sk_test')
            ? ($parts['te'] ?? null)
            : ($parts['li'] ?? null);

        if (empty($timestamp) || empty($signature)) {
            Log::warning('PayMongo webhook: could not extract timestamp or signature', [
                'parsed_parts' => array_keys($parts),
            ]);
            return false;
        }

        $signedPayload = $timestamp . '.' . $rawBody;
        $expectedHmac  = hash_hmac('sha256', $signedPayload, $this->webhookSecret);

        $valid = hash_equals($expectedHmac, $signature);

        if (!$valid) {
            Log::warning('PayMongo webhook: signature mismatch', [
                'expected_prefix' => substr($expectedHmac, 0, 8) . '...',
                'received_prefix' => substr($signature, 0, 8) . '...',
            ]);
        }

        return $valid;
    }

    // ── Parse Webhook Event ──────────────────────────────────────────────

    /**
     * Extract a structured event from a decoded webhook body array.
     * Call this AFTER getting the raw body string for signature verification.
     */
    public function parseWebhookEvent(array $body): array
    {
        // PayMongo webhook body structure:
        // data.attributes.type  = event type (e.g. "payment.paid")
        // data.attributes.data  = the resource that triggered the event
        $attrs    = $body['data']['attributes'] ?? [];
        $resource = $attrs['data'] ?? [];

        return [
            'event_type'     => $attrs['type'] ?? null,
            'resource_type'  => $resource['type'] ?? null,
            'resource_id'    => $resource['id'] ?? null,
            'resource_attrs' => $resource['attributes'] ?? [],
        ];
    }
}
