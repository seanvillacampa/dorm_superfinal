<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantPayment extends Model
{
    protected $fillable = [
        'invoice_id',
        'tenant_id',
        'share_amount',
        'amount_paid',
        'status',
        'method',
        'paid_at',
        'paymongo_link_id',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
        ];
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function balanceDue(): float
    {
        return max(0, $this->share_amount - $this->amount_paid);
    }
}
