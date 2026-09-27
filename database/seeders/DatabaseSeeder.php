<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\MeterReading;
use App\Models\Room;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\TenantPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // ── 1. Full wipe ──────────────────────────────────────────────────
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach ([
            'tenant_payments','payments','invoices','meter_readings',
            'deposit_entries','contract_tenants','contracts','tenants',
            'rooms','settings','audit_logs','users',
            'model_has_roles','model_has_permissions',
        ] as $t) { DB::table($t)->truncate(); }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // ── 2. Roles ──────────────────────────────────────────────────────
        Role::firstOrCreate(['name' => 'owner',    'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'tenant',   'guard_name' => 'web']);

        // ── 3. Staff accounts ─────────────────────────────────────────────
        $owner = User::create([
            'name' => 'Rosa Dela Vega', 'email' => 'owner@dorm.test',
            'phone' => '0917 000 0001',  'password' => Hash::make('password'), 'is_active' => true,
        ]);
        $owner->assignRole('owner');

        $staff = User::create([
            'name' => 'Ben Lopez', 'email' => 'staff@dorm.test',
            'phone' => '0917 000 0002', 'password' => Hash::make('password'), 'is_active' => true,
        ]);
        $staff->assignRole('employee');

        // ── 4. Electricity rate ───────────────────────────────────────────
        Setting::create([
            'key' => 'electricity_rate', 'value' => 12.50,
            'effective_from' => '2026-01-01', 'set_by' => $owner->id,
        ]);

        // ── 5. Room definitions ───────────────────────────────────────────
        // 28 rooms across 4 floors (7 per floor)
        // Capacity 1–4, rent varies by floor, some rooms partially/fully occupied
        $roomDefs = [
            // [room_number, floor, capacity, monthly_rate]
            ['101',1,2,8000], ['102',1,3,8000], ['103',1,4,8000], ['104',1,1,8000],
            ['105',1,2,8000], ['106',1,4,8000], ['107',1,3,8000],
            ['201',2,4,9000], ['202',2,2,9000], ['203',2,3,9000], ['204',2,4,9000],
            ['205',2,1,9000], ['206',2,2,9000], ['207',2,3,9000],
            ['301',3,3,10000],['302',3,4,10000],['303',3,2,10000],['304',3,4,10000],
            ['305',3,1,10000],['306',3,3,10000],['307',3,4,10000],
            ['401',4,4,11000],['402',4,2,11000],['403',4,3,11000],['404',4,4,11000],
            ['405',4,1,11000],['406',4,2,11000],['407',4,4,11000],
        ];

        // How many tenants actually occupy each room (≤ capacity, some partial)
        $occupancy = [
            2,3,4,1,1,3,2,   // floor 1 — mix of full and partial
            4,2,3,2,1,2,3,   // floor 2
            3,4,1,3,1,2,4,   // floor 3
            4,1,3,4,1,2,3,   // floor 4
        ];

        // kWh used this month per room (unique per room)
        $kwhThisMonth = [
            70.5,43.0,88.0,25.0,35.5,92.0,60.0,
            55.0,78.5,48.0,95.0,28.0,66.0,51.0,
            38.0,82.5,33.0,77.5,22.0,74.0,90.0,
            63.0,31.5,68.0,85.0,18.0,53.5,91.0,
        ];

        // Previous month baseline kWh reading
        $prevBaseKwh = [
            1200.0,985.5, 1340.0,760.0, 1100.5,1450.0,890.0,
            1025.0,1380.5,670.0, 1250.0,940.0, 1500.0,1075.0,
            820.0, 1175.5,995.0, 1290.5,715.0, 1135.0,1485.0,
            1060.0,880.0, 1320.0,1415.0,750.0, 960.0, 1030.0,
        ];

        // Tenant payment statuses for seeded invoices
        // One entry per tenant slot; we cycle through these
        $payStatuses = ['paid','paid','paid','pending','late','overdue','paid','pending'];

        $firstNames = [
            'Juan','Maria','Carlo','Liza','Miguel','Rica','Paolo','Jenny',
            'Mark','Sofia','Leo','Bea','Nico','Ana','Rex','Kim',
            'Jose','Carla','Renz','Tina','Dante','Mel','Felix','Grace',
            'Alex','Donna','Ryan','Cris','Edwin','Luz','Ariel','Pia',
            'Mario','Nora','Ruben','Dina','Eric','Mia','Vince','Claire',
            'Jason','Rose','Kevin','Lyn','Patrick','Elle','Ricky','Ivy',
            'Dennis','Joanna','Francis','Stella','Neil','Aileen','Jake','Lena',
            'Roy','Tess','Adrian','Gem','Oscar','Hazel','Noel','Vera',
            'Bernard','Wena','Alvin','Charina','Bobby','Flor','Jerome','Nina',
            'Percy','Diana','Romeo','Susie','Anton','Rhea','Arnold','Faye',
        ];
        $lastNames = [
            'Santos','Reyes','Cruz','Bautista','Ocampo','Garcia','Mendoza',
            'Torres','Flores','Villanueva','Ramos','Castillo','Soriano','Aquino',
            'Navarro','Pineda','Dela Cruz','De Leon','Manalo','Mercado',
            'Robles','Domingo','Salazar','Cabrera','Lim','Tan','Go','Chua',
        ];

        $billingMonth  = now()->format('Y-m');
        $prevMonth     = now()->subMonth()->format('Y-m');
        $contractStart = Carbon::create(2026, 1, 5);
        $nameIdx       = 0;
        $emailIdx      = 1;
        $payIdx        = 0;

        foreach ($roomDefs as $ri => [$roomNo, $floor, $capacity, $rate]) {
            // ── Room ──────────────────────────────────────────────────────
            $room = Room::create([
                'room_number'  => $roomNo,
                'floor'        => $floor,
                'capacity'     => $capacity,
                'monthly_rate' => (float) $rate,
                'meter_number' => 'MTR-' . str_pad($ri + 1001, 4, '0', STR_PAD_LEFT),
                'status'       => 'occupied',
            ]);

            $tenantsInRoom = $occupancy[$ri];

            // ── Tenants ───────────────────────────────────────────────────
            $tenantIds = [];
            for ($t = 0; $t < $tenantsInRoom; $t++) {
                $fn    = $firstNames[$nameIdx % count($firstNames)];
                $ln    = $lastNames[($nameIdx + $t * 3) % count($lastNames)];
                $email = strtolower($fn) . $emailIdx . '@dorm.test';
                $user  = User::create([
                    'name'      => "$fn $ln",
                    'email'     => $email,
                    'phone'     => '09' . rand(10,99) . ' ' . rand(100,999) . ' ' . rand(1000,9999),
                    'password'  => Hash::make('password'),
                    'is_active' => true,
                ]);
                $user->assignRole('tenant');
                $tenantIds[] = Tenant::create(['user_id' => $user->id])->id;
                $nameIdx++;
                $emailIdx++;
            }

            // ── Contract ──────────────────────────────────────────────────
            $contract = Contract::create([
                'room_id'           => $room->id,
                'start_date'        => $contractStart,
                'end_date'          => $contractStart->copy()->addYear(),
                'due_day'           => 5,
                'deposit_required'  => $rate,
                'deposit_collected' => $rate,
                'status'            => 'active',
                'created_by'        => $owner->id,
            ]);

            $pivotData = [];
            foreach ($tenantIds as $tid) {
                $pivotData[$tid] = ['joined_on' => $contractStart->toDateString(), 'left_on' => null];
            }
            $contract->tenants()->attach($pivotData);

            // ── Meter readings ─────────────────────────────────────────────
            $prevBase = $prevBaseKwh[$ri];
            $prevUsed = round($kwhThisMonth[$ri] * 0.85, 1); // prev month slightly less
            MeterReading::create([
                'room_id' => $room->id, 'reading_month' => $prevMonth,
                'previous_kwh' => round($prevBase - $prevUsed, 1),
                'current_kwh' => $prevBase, 'kwh_used' => $prevUsed,
                'recorded_by' => $staff->id,
            ]);

            $currUsed = $kwhThisMonth[$ri];
            $currEnd  = round($prevBase + $currUsed, 1);
            MeterReading::create([
                'room_id' => $room->id, 'reading_month' => $billingMonth,
                'previous_kwh' => $prevBase, 'current_kwh' => $currEnd,
                'kwh_used' => $currUsed, 'recorded_by' => $staff->id,
            ]);

            // ── Invoice ────────────────────────────────────────────────────
            $elec     = round($currUsed * 12.50, 2);
            $total    = (float)$rate + $elec;
            $shareAmt = round($total / $tenantsInRoom, 2);

            $invoice = Invoice::create([
                'invoice_number'     => 'INV-' . str_replace('-','',substr($billingMonth,2)) . '-' . $roomNo,
                'contract_id'        => $contract->id,
                'tenant_count'       => $tenantsInRoom,
                'billing_month'      => $billingMonth,
                'due_date'           => Carbon::createFromFormat('Y-m', $billingMonth)->day(5),
                'rent_amount'        => (float)$rate,
                'electricity_amount' => $elec,
                'total_amount'       => $total,
                'amount_paid'        => 0,
                'status'             => 'pending',
                'created_by'         => $owner->id,
            ]);

            // ── TenantPayments with mixed statuses ─────────────────────────
            $totalPaid    = 0;
            $tpStatuses   = [];

            foreach ($tenantIds as $tid) {
                $tpStatus = $payStatuses[$payIdx % count($payStatuses)];
                $paid     = $tpStatus === 'paid' ? $shareAmt : 0;
                $totalPaid += $paid;

                TenantPayment::create([
                    'invoice_id'   => $invoice->id,
                    'tenant_id'    => $tid,
                    'share_amount' => $shareAmt,
                    'amount_paid'  => $paid,
                    'status'       => $tpStatus,
                    'paid_at'      => $tpStatus === 'paid' ? now()->subDays(rand(1,10)) : null,
                ]);

                $tpStatuses[] = $tpStatus;
                $payIdx++;
            }

            // Derive invoice-level status from tenant payment statuses
            $paidCount    = collect($tpStatuses)->filter(fn($s)=>$s==='paid')->count();
            $lateCount    = collect($tpStatuses)->filter(fn($s)=>$s==='late')->count();
            $overdueCount = collect($tpStatuses)->filter(fn($s)=>$s==='overdue')->count();
            $total_t      = count($tpStatuses);
            $unpaid       = $total_t - $paidCount;

            $invStatus = match(true) {
                $paidCount === $total_t                          => 'paid',
                $paidCount > 0                                   => 'partial',
                $overdueCount === $unpaid                        => 'overdue',
                $lateCount === $unpaid                           => 'late',
                ($lateCount + $overdueCount) > 0 && $paidCount === 0 => 'late', // mixed late/overdue/pending → late
                default                                          => 'pending',  // all genuinely pending
            };

            $invoice->update([
                'status'      => $invStatus,
                'amount_paid' => $totalPaid,
                'paid_at'     => $invStatus === 'paid' ? now()->subDays(rand(1,5)) : null,
            ]);
        }
    }
}
