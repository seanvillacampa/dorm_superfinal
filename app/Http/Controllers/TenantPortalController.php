<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\MeterReading;
use App\Models\Payment;
use App\Models\TenantPayment;
use App\Models\DepositEntry;
use Illuminate\Support\Facades\Auth;

class TenantPortalController extends Controller
{
    private function activeContract(): ?Contract
    {
        return Auth::user()->tenant?->contracts()
            ->where('status', 'active')
            ->with(['room', 'tenants.user'])
            ->first();
    }

    public function dashboard()
    {
        return redirect()->route('tenant.bill');
    }

    public function bill()
    {
        $contract = $this->activeContract();
        $invoice  = null;
        $myPayment = null; // TenantPayment row for this tenant
        $reading  = null;

        if ($contract) {
            $invoice = Invoice::where('contract_id', $contract->id)
                ->where('billing_month', now()->format('Y-m'))
                ->with([
                    'tenantPayments.tenant.user',
                    'payments',
                ])
                ->first();

            if ($invoice) {
                $myPayment = $invoice->tenantPayments
                    ->firstWhere('tenant_id', Auth::user()->tenant?->id);
            }

            $reading = MeterReading::where('room_id', $contract->room_id)
                ->where('reading_month', now()->format('Y-m'))
                ->first();
        }

        return view('tenant.bill', compact('contract', 'invoice', 'myPayment', 'reading'));
    }

    public function payments()
    {
        $contract = $this->activeContract();
        $payments = collect();
        $myTenantId = Auth::user()->tenant?->id;

        if ($contract && $myTenantId) {
            $payments = TenantPayment::where('tenant_id', $myTenantId)
                ->whereHas('invoice', fn ($q) => $q->where('contract_id', $contract->id))
                ->with('invoice')
                ->orderByDesc('updated_at')
                ->paginate(10);
        }

        return view('tenant.payments', compact('contract', 'payments'));
    }

    public function deposit()
    {
        $contract = $this->activeContract();
        $entries  = collect();

        if ($contract) {
            $entries = DepositEntry::where('contract_id', $contract->id)
                ->orderBy('date')
                ->get();
        }

        return view('tenant.deposit', compact('contract', 'entries'));
    }

    public function contract()
    {
        $contract = $this->activeContract();
        return view('tenant.contract', compact('contract'));
    }
}
