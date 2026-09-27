<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        $query = Contract::with(['room', 'tenants.user'])
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $s = '%'.$request->search.'%';
            $query->where(fn ($q) =>
                $q->whereRaw("LPAD(id, 4, '0') LIKE ?", [$s])
                  ->orWhereHas('room', fn ($r) => $r->where('room_number', 'like', $s))
            );
        }

        $contracts = $query->paginate(10)->withQueryString();
        return view('contracts.index', compact('contracts'));
    }

    public function create()
    {
        $rooms   = Room::where('status', 'vacant')->orderBy('room_number')->get();
        $tenants = Tenant::with('user')
            ->whereDoesntHave('contracts', fn ($q) => $q->where('status', 'active'))
            ->get()
            ->sortBy('user.name');
        return view('contracts.create', compact('rooms', 'tenants'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'room_id'          => 'required|exists:rooms,id',
            'tenant_ids'       => 'required|array|min:1',
            'tenant_ids.*'     => 'exists:tenants,id',
            'start_date'       => 'required|date',
            'end_date'         => 'required|date|after:start_date',
            'due_day'          => 'required|integer|min:1|max:28',
            'deposit_required' => 'required|numeric|min:0',
        ]);

        $room = Room::findOrFail($data['room_id']);

        // Enforce capacity: tenant count must not exceed room capacity
        if (count($data['tenant_ids']) > (int) $room->capacity) {
            return back()->withErrors([
                'tenant_ids' => "Room {$room->room_number} has a capacity of {$room->capacity}. You selected " . count($data['tenant_ids']) . " tenant(s).",
            ])->withInput();
        }

        $contract = Contract::create([
            'room_id'           => $data['room_id'],
            'start_date'        => $data['start_date'],
            'end_date'          => $data['end_date'],
            // monthly_rent is no longer stored on contract — rent comes from Room.monthly_rate
            'due_day'           => $data['due_day'],
            'deposit_required'  => $data['deposit_required'],
            'deposit_collected' => 0,
            'status'            => 'active',
            'created_by'        => Auth::id(),
        ]);

        $contract->tenants()->attach(
            collect($data['tenant_ids'])->mapWithKeys(fn ($tid) => [
                $tid => ['joined_on' => $data['start_date'], 'left_on' => null],
            ])->toArray()
        );

        Room::where('id', $data['room_id'])->update(['status' => 'occupied']);

        AuditLog::record('Created', 'Contract', $contract->id,
            "Contract for Room {$room->room_number} — " . count($data['tenant_ids']) . " tenant(s) @ ₱" . number_format($room->monthly_rate, 2) . "/month room rate");

        return redirect()->route('contracts.index')
            ->with('success', 'Contract created. Rent is ₱' . number_format($room->monthly_rate / count($data['tenant_ids']), 2) . '/tenant.');
    }

    public function show(Contract $contract)
    {
        $contract->load(['room', 'tenants.user', 'invoices.tenantPayments.tenant.user', 'createdBy']);

        // Compute rent per tenant for display
        $tenantCount  = $contract->tenants->count();
        $rentPerTenant = $tenantCount > 0
            ? round($contract->room->monthly_rate / $tenantCount, 2)
            : $contract->room->monthly_rate;

        return view('contracts.show', compact('contract', 'rentPerTenant'));
    }
}
