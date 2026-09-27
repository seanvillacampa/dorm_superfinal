<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent once to the owner when an invoice becomes OVERDUE
 * (grace period ended) — the signal to personally follow up.
 */
class OwnerPaymentAlert extends Notification
{
    public function __construct(public Invoice $invoice) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $room    = $this->invoice->contract->room->room_number;
        $month   = now()->parse($this->invoice->billing_month)->format('F Y');
        $balance = number_format($this->invoice->balanceDue(), 2);

        $tenants = $this->invoice->contract->tenants
            ->map(fn($t) => $t->user->name)
            ->implode(', ');

        return (new MailMessage)
            ->subject("🚨 Room {$room} — Invoice Overdue · {$month}")
            ->greeting("Hello, {(\$notifiable->name ?? 'there')}!")
            ->line("**Room {$room}** ({$month}) has become overdue.")
            ->line("**Balance remaining: ₱{$balance}**")
            ->line("**Tenants:** {$tenants}")
            ->line('This is a one-time alert. Please follow up with the tenant(s) directly.')
            ->action('View Invoice', url(route('invoices.index')))
            ->salutation('— Dorm Manager (automated alert)');
    }
}
