<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\TenantPayment;
use App\Models\Setting;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class InvoiceController extends Controller
{
    // ── Room → Invoice redirect ───────────────────────────────────────────

    /**
     * Called when a room card is clicked on the dashboard.
     * If an invoice exists for the current month, show it.
     * If not, redirect back with a warning prompt to generate one.
     */
    public function roomInvoice(\App\Models\Room $room)
    {
        $month    = now()->format('Y-m');
        $contract = $room->activeContract;

        if (!$contract) {
            return redirect()->route('owner.dashboard')
                ->with('warning', "Room {$room->room_number} has no active contract.");
        }

        $invoice = Invoice::where('contract_id', $contract->id)
            ->where('billing_month', $month)
            ->first();

        if ($invoice) {
            return redirect()->route('invoices.show', $invoice);
        }

        // No invoice yet — redirect to invoices page with a prompt
        return redirect()->route('invoices.index', ['month' => $month])
            ->with('warning', "No invoice yet for Room {$room->room_number} ({$month}). Generate invoices to create one.");
    }

    // ── Month coverage helper ────────────────────────────────────────────

    private function monthCoverage(): Collection
    {
        $totalRooms = Contract::where('status', 'active')->count();

        $counts = Invoice::select('billing_month', DB::raw('COUNT(*) as cnt'))
            ->where('status', '!=', 'void')
            ->groupBy('billing_month')
            ->pluck('cnt', 'billing_month');

        $months = collect();
        for ($i = 0; $i <= 12; $i++) {
            $m      = now()->subMonths($i)->format('Y-m');
            $cnt    = (int) ($counts[$m] ?? 0);
            $status = match(true) {
                $totalRooms > 0 && $cnt >= $totalRooms => 'all',
                $cnt > 0                               => 'partial',
                default                                => 'none',
            };
            $months->put($m, [
                'label'  => now()->subMonths($i)->format('F Y'),
                'status' => $status,
                'count'  => $cnt,
                'total'  => $totalRooms,
            ]);
        }
        return $months;
    }

    // ── Index ────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $month     = $request->get('month', now()->format('Y-m'));
        $activeTab = $request->get('tab', 'all'); // kept for manage tab only
        $status    = $request->get('status', '');  // filter value
        $search    = $request->get('search', '');

        $query = Invoice::with(['contract.room', 'contract.tenants.user', 'tenantPayments.tenant.user'])
            ->where('billing_month', $month)
            ->where('status', '!=', 'void')
            ->orderBy('invoice_number');

        // Status filter — 'partial' catches all partial variants
        if ($status && $status !== 'all') {
            if ($status === 'partial') {
                $query->whereIn('status', ['partial', 'partial_late', 'partial_overdue']);
            } else {
                $query->where('status', $status);
            }
        }

        // Search by room number or invoice number
        if ($search) {
            $query->where(fn ($q) =>
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('contract.room', fn ($r) =>
                      $r->where('room_number', 'like', "%{$search}%"))
            );
        }

        $invoices = $query->paginate(10)->withQueryString();

        // Summary counts for the status dropdown labels
        $rawCounts = Invoice::where('billing_month', $month)
            ->where('status', '!=', 'void')
            ->selectRaw("
                COUNT(*) as total,
                SUM(status='paid') as paid,
                SUM(status IN ('partial','partial_late','partial_overdue')) as partial,
                SUM(status='late') as late,
                SUM(status='overdue') as overdue,
                SUM(status='pending') as pending
            ")->first();

        // All invoices (incl. void) for manage tab
        $allInvoices = Invoice::with(['contract.room', 'contract.tenants.user', 'tenantPayments.tenant.user'])
            ->where('billing_month', $month)
            ->orderByRaw("FIELD(status,'pending','late','overdue','partial','partial_late','partial_overdue','paid','void')")
            ->orderBy('invoice_number')
            ->paginate(28)
            ->withQueryString();

        $monthCoverage = $this->monthCoverage();

        return view('invoices.index', compact(
            'invoices', 'allInvoices', 'month', 'rawCounts',
            'activeTab', 'monthCoverage', 'status', 'search'
        ));
    }

    // ── Show ─────────────────────────────────────────────────────────────

    public function show(Invoice $invoice)
    {
        $invoice->load([
            'contract.room',
            'contract.tenants.user',
            'tenantPayments.tenant.user',
            'payments.recordedByUser',
            'createdBy',
        ]);
        $canVoid = Auth::user()->hasRole('owner') && $invoice->status !== 'void';
        return view('invoices.show', compact('invoice', 'canVoid'));
    }

    // ── Generate ─────────────────────────────────────────────────────────

    public function generate(Request $request)
    {
        $request->validate(['month' => 'required|date_format:Y-m']);
        $month = $request->month;
        $rate  = Setting::currentElectricityRate();
        $created = 0;

        DB::transaction(function () use ($month, $rate, &$created) {
            $contracts = Contract::where('status', 'active')
                ->with(['room', 'tenants', 'lastMeterReading'])
                ->get();

            foreach ($contracts as $contract) {
                if (Invoice::where('contract_id', $contract->id)
                        ->where('billing_month', $month)->exists()) {
                    continue;
                }

                $tenantCount  = $contract->tenants->count();
                if ($tenantCount === 0) continue;

                $roomRate    = $contract->room->monthly_rate;
                $electricity = 0;

                $reading = $contract->lastMeterReading;
                if ($reading && $reading->kwh_used !== null) {
                    $electricity = round($reading->kwh_used * $rate, 2);
                }

                $total   = $roomRate + $electricity;
                $dueDate = \Carbon\Carbon::createFromFormat('Y-m', $month)
                    ->day($contract->due_day);

                $invoice = Invoice::create([
                    'invoice_number'     => 'INV-' . str_replace('-', '', substr($month, 2)) . '-' . $contract->room->room_number,
                    'contract_id'        => $contract->id,
                    'tenant_count'       => $tenantCount,
                    'billing_month'      => $month,
                    'due_date'           => $dueDate,
                    'rent_amount'        => $roomRate,
                    'electricity_amount' => $electricity,
                    'total_amount'       => $total,
                    'amount_paid'        => 0,
                    'status'             => 'pending',
                    'created_by'         => Auth::id(),
                ]);

                // Create one TenantPayment row per tenant
                $shareRent  = round($roomRate / $tenantCount, 2);
                $shareElec  = round($electricity / $tenantCount, 2);
                $shareTotal = $shareRent + $shareElec;

                foreach ($contract->tenants as $tenant) {
                    TenantPayment::create([
                        'invoice_id'   => $invoice->id,
                        'tenant_id'    => $tenant->id,
                        'share_amount' => $shareTotal,
                        'amount_paid'  => 0,
                        'status'       => 'pending',
                    ]);
                }

                $created++;
            }
        });

        AuditLog::record('Generated', 'Invoice', null,
            "{$created} room invoices generated for {$month}");

        return redirect()->route('invoices.index', ['month' => $month])
            ->with('success', "{$created} invoice(s) generated for {$month}.");
    }

    // ── Cancel (bulk void unpaid) ─────────────────────────────────────────

    public function cancel(Request $request)
    {
        $request->validate(['month' => 'required|date_format:Y-m']);
        $month = $request->month;

        $ids = Invoice::where('billing_month', $month)
            ->whereNotIn('status', ['paid', 'void'])
            ->pluck('id');

        Invoice::whereIn('id', $ids)->update(['status' => 'void']);
        TenantPayment::whereIn('invoice_id', $ids)->update(['status' => 'void']);

        AuditLog::record('Cancelled', 'Invoice', null,
            "{$ids->count()} invoices cancelled for {$month}");

        return redirect()->route('invoices.index', ['month' => $month])
            ->with('success', "{$ids->count()} invoice(s) cancelled for {$month}.");
    }

    // ── Void (single invoice, owner only) ────────────────────────────────

    public function void(Invoice $invoice)
    {
        abort_if($invoice->status === 'void', 422, 'Already voided.');
        $old = $invoice->status;
        $invoice->update(['status' => 'void']);
        TenantPayment::where('invoice_id', $invoice->id)
            ->whereNotIn('status', ['paid'])
            ->update(['status' => 'void']);

        AuditLog::record('Voided', 'Invoice', $invoice->id, "status: {$old} → void");
        return back()->with('success', "Invoice {$invoice->invoice_number} voided.");
    }
}
