<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Setting;
use App\Models\TenantPayment;
use App\Models\User;
use App\Notifications\InvoiceDueReminder;
use App\Notifications\PaymentMissed;
use App\Notifications\PaymentOverdue;
use App\Notifications\OwnerPaymentAlert;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Runs daily (via scheduler) to:
 *  1. Flip pending → late → overdue based on billing rules
 *  2. Send due-date reminder emails on the due date
 *  3. Send missed-payment (late) emails
 *  4. Send overdue emails to tenants + one-time alert to owner
 */
class ProcessInvoiceStatuses extends Command
{
    protected $signature   = 'invoices:process-statuses';
    protected $description = 'Flip invoice statuses and send email notifications';

    public function handle(): void
    {
        $rules      = Setting::billingRules();
        $graceDays  = (int) ($rules['overdue_grace_days'] ?? 3);
        $today      = now()->toDateString();

        $invoices = Invoice::with([
                'contract.room',
                'contract.tenants.user',
                'tenantPayments.tenant.user',
            ])
            ->whereNotIn('status', ['paid', 'void'])
            ->get();

        $owner = User::role('owner')->first();

        foreach ($invoices as $invoice) {
            $dueDate  = $invoice->due_date;
            $daysLate = now()->diffInDays($dueDate, false); // negative = not yet due

            foreach ($invoice->tenantPayments as $tp) {
                if ($tp->status === 'paid') continue;

                $oldStatus = $tp->status;

                // ── Due date: send reminder ──────────────────────────
                if ($daysLate === 0 && $oldStatus === 'pending') {
                    $this->notify($tp->tenant->user, new InvoiceDueReminder($invoice, $tp), 'Due reminder');
                }

                // ── Past due but within grace: flip to late ──────────
                if ($daysLate > 0 && $daysLate <= $graceDays && $oldStatus === 'pending') {
                    $tp->update(['status' => 'late']);
                    $this->notify($tp->tenant->user, new PaymentMissed($invoice, $tp), 'Payment missed (late)');
                }

                // ── Grace period ended: flip to overdue ──────────────
                if ($daysLate > $graceDays && in_array($oldStatus, ['pending', 'late'])) {
                    $tp->update(['status' => 'overdue']);
                    $this->notify($tp->tenant->user, new PaymentOverdue($invoice, $tp), 'Payment overdue (tenant)');

                    // One-time owner alert only when flipping from late → overdue
                    if ($oldStatus === 'late' && $owner) {
                        $this->notify($owner, new OwnerPaymentAlert($invoice), 'Overdue owner alert');
                    }
                }
            }

            // Recompute invoice-level status after updating tenant payments
            $invoice->load('tenantPayments');
            $invoice->recomputeStatus();
        }

        $this->info('Invoice statuses processed for ' . $today);
    }

    private function notify($notifiable, $notification, string $label): void
    {
        try {
            $notifiable->notify($notification);
            Log::info("Notification sent: {$label}", ['to' => $notifiable->email ?? 'unknown']);
        } catch (\Exception $e) {
            Log::warning("Notification failed: {$label}", ['error' => $e->getMessage()]);
        }
    }
}
