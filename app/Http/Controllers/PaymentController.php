<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\TenantPayment;
use App\Models\AuditLog;
use App\Notifications\PaymentReceived;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $payments = Payment::with([
                'invoice.contract.room',
                'tenantPayment.tenant.user',
                'recordedByUser',
            ])
            ->orderByDesc('received_at')
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        // Open invoices with their unpaid tenant payment rows
        $openInvoices = Invoice::whereIn('status', ['pending','late','overdue','partial'])
            ->with([
                'contract.room',
                'tenantPayments.tenant.user',
            ])
            ->orderBy('invoice_number')
            ->get();

        return view('payments.index', compact('payments', 'openInvoices'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'invoice_id'        => 'required|exists:invoices,id',
            'tenant_payment_id' => 'nullable|exists:tenant_payments,id',
            'amount'            => 'required|numeric|min:0.01',
            'method'            => 'required|in:cash,bank_transfer,gcash,maya,card,qr_ph',
            'received_at'       => 'required|date',
            'reference'         => 'nullable|string|max:100',
            'notes'             => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($data) {
            $invoice   = Invoice::with('tenantPayments.tenant.user')->findOrFail($data['invoice_id']);
            $tpId      = $data['tenant_payment_id'] ?? null;
            $tenantPay = $tpId ? TenantPayment::find($tpId) : null;

            // Record the payment transaction
            Payment::create([
                'invoice_id'        => $invoice->id,
                'tenant_payment_id' => $tenantPay?->id,
                'amount'            => $data['amount'],
                'method'            => $data['method'],
                'received_at'       => $data['received_at'],
                'reference'         => $data['reference'] ?? null,
                'notes'             => $data['notes'] ?? null,
                'recorded_by_type'  => 'staff',
                'recorded_by_user'  => Auth::id(),
            ]);

            // Update the specific TenantPayment row
            if ($tenantPay) {
                $newTpPaid = $tenantPay->amount_paid + $data['amount'];
                $tpStatus  = $newTpPaid >= $tenantPay->share_amount ? 'paid' : $tenantPay->status;

                $tenantPay->update([
                    'amount_paid' => $newTpPaid,
                    'status'      => $tpStatus,
                    'method'      => $data['method'],
                    'paid_at'     => $tpStatus === 'paid' ? now() : $tenantPay->paid_at,
                ]);

                // Send payment received notification
                try {
                    $tenant = $tenantPay->tenant;
                    $tenant->user->notify(new PaymentReceived(
                        $invoice, $tenantPay, $data['amount'], $data['method']
                    ));
                } catch (\Exception $e) {
                    Log::warning('PaymentReceived notification failed', ['error' => $e->getMessage()]);
                }
            }

            // Recompute invoice-level status from all tenant payments
            $invoice->load('tenantPayments');
            $invoice->recomputeStatus();

            $tenantName = $tenantPay?->tenant->user->name ?? 'unspecified tenant';
            AuditLog::record('Payment', 'Invoice', $invoice->id,
                "₱{$data['amount']} ({$data['method']}) for {$tenantName} recorded by " . Auth::user()->name);
        });

        return redirect()->route('payments.index')
            ->with('success', 'Payment recorded successfully.');
    }
}
