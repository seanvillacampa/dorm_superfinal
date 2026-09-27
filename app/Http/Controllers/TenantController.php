<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use App\Models\AuditLog;
use App\Notifications\TenantWelcome;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

class TenantController extends Controller
{
    public function index(Request $request)
    {
        $query = Tenant::with(['user', 'contracts.room'])
            ->join('users', 'users.id', '=', 'tenants.user_id')
            ->select('tenants.*');

        if ($request->filled('search')) {
            $s = '%'.$request->search.'%';
            $query->where(fn ($q) =>
                $q->where('users.name', 'like', $s)
                  ->orWhere('users.email', 'like', $s)
                  ->orWhere('users.phone', 'like', $s)
            );
        }

        $tenants = $query->orderBy('users.name')->paginate(10)->withQueryString();

        return view('tenants.index', compact('tenants'));
    }

    public function create()
    {
        return view('tenants.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:255',
            'email'           => 'required|email|unique:users,email',
            'phone'           => 'nullable|string|max:30',
            'birthdate'       => 'nullable|date',
            'id_type'         => 'nullable|in:National ID,Passport,Student ID,Driver\'s License,PRC ID,UMID',
            'id_number'       => 'nullable|string|max:50',
            'emergency_name'  => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:30',
        ]);

        // Create user + tenant inside transaction
        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name'      => $data['name'],
                'email'     => $data['email'],
                'phone'     => $data['phone'] ?? null,
                'password'  => Hash::make(\Illuminate\Support\Str::random(16)),
                'is_active' => true,
            ]);
            $user->assignRole('tenant');

            $tenant = Tenant::create([
                'user_id'         => $user->id,
                'birthdate'       => $data['birthdate'] ?? null,
                'id_type'         => $data['id_type'] ?? null,
                'id_number'       => $data['id_number'] ?? null,
                'emergency_name'  => $data['emergency_name'] ?? null,
                'emergency_phone' => $data['emergency_phone'] ?? null,
            ]);

            AuditLog::record('Created', 'Tenant', $tenant->id,
                "New tenant {$user->name} ({$user->email})");

            return $user;
        });

        // Send activation email OUTSIDE the transaction so a mail failure
        // doesn't roll back the user record. Log any failure clearly.
        $emailSent = false;
        try {
            $token = Password::broker()->createToken($user);
            $user->notify(new TenantWelcome($token));
            $emailSent = true;
        } catch (\Exception $e) {
            Log::error('TenantWelcome notification failed', [
                'tenant_email' => $user->email,
                'error'        => $e->getMessage(),
            ]);
        }

        $message = $emailSent
            ? "Tenant {$user->name} registered. Activation email sent to {$user->email}."
            : "Tenant {$user->name} registered. ⚠️ Activation email could not be sent — check mail config in .env.";

        return redirect()->route('tenants.index')->with('success', $message);
    }

    public function show(Tenant $tenant)
    {
        $tenant->load(['user', 'contracts.room', 'contracts.invoices']);
        return view('tenants.show', compact('tenant'));
    }
}
