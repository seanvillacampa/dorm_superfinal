<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'effective_from',
        'set_by',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'value'          => 'float',
        ];
    }

    public function setBy()
    {
        return $this->belongsTo(User::class, 'set_by');
    }

    // ── Static helpers ───────────────────────────────────────────────────

    /** Current peso-per-kWh electricity rate (most recent effective_from <= today). */
    public static function currentElectricityRate(): float
    {
        return (float) (static::where('key', 'electricity_rate')
            ->where('effective_from', '<=', now()->toDateString())
            ->orderByDesc('effective_from')
            ->value('value') ?? 0);
    }

    /** Return billing rules as a keyed array with defaults. */
    public static function billingRules(): array
    {
        $keys = ['invoice_generated_on','default_due_day','late_after_days','overdue_grace_days'];
        $rows = static::whereIn('key', $keys)->pluck('value', 'key');

        return [
            'invoice_generated_on' => (int) ($rows['invoice_generated_on'] ?? 1),
            'default_due_day'      => (int) ($rows['default_due_day'] ?? 5),
            'late_after_days'      => (int) ($rows['late_after_days'] ?? 0),
            'overdue_grace_days'   => (int) ($rows['overdue_grace_days'] ?? 3),
        ];
    }

    /** Upsert a simple key→value setting (no effective_from). */
    public static function updateOrCreateSimple(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key, 'effective_from' => null], ['value' => $value]);
    }
}
