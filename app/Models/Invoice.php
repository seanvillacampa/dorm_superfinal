<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One invoice per room per billing month.
 * rent_amount  = Room.monthly_rate (full room rent)
 * tenant_count = number of active tenants sharing the room
 * rentPerTenant() = rent_amount / tenant_count
 *
 * status is derived from tenantPayments:
 *   paid          — all tenant payments paid
 *   partial       — some paid, none late/overdue
 *   partial_late  — some paid, at least one late
 *   partial_overdue — some paid, at least one overdue
 *   late          — all unpaid and late
 *   overdue       — all unpaid and overdue
 *   pending       — no payments yet, not past due
 *   void          — cancelled
 */
class Invoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'contract_id',
        'tenant_count',
        'billing_month',
        'due_date',
        'rent_amount',
        'electricity_amount',
        'total_amount',
        'amount_paid',
        'status',
        'paid_at',
        'paymongo_link_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'paid_at'  => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function tenantPayments()
    {
        return $this->hasMany(TenantPayment::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    public function balanceDue(): float
    {
        return max(0, $this->total_amount - $this->amount_paid);
    }

    /** Rent share per tenant = room rent ÷ number of tenants. */
    public function rentPerTenant(): float
    {
        $count = max(1, $this->tenant_count);
        return round($this->rent_amount / $count, 2);
    }

    /** Electricity share per tenant = electricity ÷ number of tenants. */
    public function electricityPerTenant(): float
    {
        $count = max(1, $this->tenant_count);
        return round($this->electricity_amount / $count, 2);
    }

    /** Total amount each tenant owes (rent share + electricity share). */
    public function amountPerTenant(): float
    {
        return $this->rentPerTenant() + $this->electricityPerTenant();
    }

    /**
     * Recompute the invoice-level status from its tenant payment rows.
     *
     * Status values:
     *   paid     — all tenants paid
     *   partial  — some paid, rest pending/late/overdue
     *   late     — no one paid, all unpaid are late
     *   overdue  — no one paid, all unpaid are overdue
     *   pending  — no payments yet, not past due
     *   void     — cancelled
     */
    public function recomputeStatus(): void
    {
        $tp = $this->tenantPayments;
        if ($tp->isEmpty()) return;

        $total   = $tp->count();
        $paid    = $tp->where('status', 'paid')->count();
        $late    = $tp->whereIn('status', ['late'])->count();
        $overdue = $tp->whereIn('status', ['overdue'])->count();
        $unpaid  = $total - $paid;

        $status = match(true) {
            $paid === $total                                  => 'paid',
            $paid > 0                                        => 'partial',
            $overdue === $unpaid                             => 'overdue',
            $late === $unpaid                                => 'late',
            ($late + $overdue) > 0 && $paid === 0           => 'late',   // mixed pending+late+overdue, none paid
            default                                          => 'pending', // all genuinely pending
        };

        $amountPaid = $tp->sum('amount_paid');
        $paidAt     = $paid === $total ? ($tp->max('paid_at') ?? now()) : null;

        $this->update([
            'status'      => $status,
            'amount_paid' => $amountPaid,
            'paid_at'     => $paidAt,
        ]);
    }
}
