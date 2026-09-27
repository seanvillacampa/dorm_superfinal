<x-layouts.app title="Contract CT-{{ str_pad($contract->id,4,'0',STR_PAD_LEFT) }} — Dorm Manager">

    <x-page-header title="Contract CT-{{ str_pad($contract->id,4,'0',STR_PAD_LEFT) }}"
        badge="Owner + Employee" subtitle="Room {{ $contract->room->room_number }}" />

    <div class="grid grid-cols-[1fr_280px] gap-4">
        <div class="space-y-4">
            <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-5">
                <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <dt class="text-gray-400">Status</dt>       <dd><x-status-badge :status="$contract->status" /></dd>
                    <dt class="text-gray-400">Room</dt>         <dd class="text-gray-900">{{ $contract->room->room_number }} · Floor {{ $contract->room->floor }}</dd>
                    <dt class="text-gray-400">Start date</dt>   <dd class="text-gray-900">{{ $contract->start_date->format('M d, Y') }}</dd>
                    <dt class="text-gray-400">End date</dt>     <dd class="text-gray-900">{{ $contract->end_date->format('M d, Y') }}</dd>
                    <dt class="text-gray-400">Room rate</dt>    <dd class="text-gray-900">₱{{ number_format($contract->room->monthly_rate,2) }}/month</dd>
                    <dt class="text-gray-400">Tenants</dt>      <dd class="text-gray-900">{{ $contract->tenants->count() }}</dd>
                    <dt class="text-gray-400">Per tenant</dt>   <dd class="font-semibold text-gray-900">₱{{ number_format($rentPerTenant,2) }}/month</dd>
                    <dt class="text-gray-400">Due day</dt>      <dd class="text-gray-900">{{ $contract->due_day }}{{ match($contract->due_day % 10) { 1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th' } }} of each month</dd>
                    <dt class="text-gray-400">Deposit req.</dt> <dd class="text-gray-900">₱{{ number_format($contract->deposit_required,2) }}</dd>
                    <dt class="text-gray-400">Collected</dt>    <dd class="text-gray-900">₱{{ number_format($contract->deposit_collected,2) }}</dd>
                    <dt class="text-gray-400">Created by</dt>   <dd class="text-gray-900">{{ $contract->createdBy?->name ?? '—' }}</dd>
                </dl>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
                <h2 class="text-sm font-semibold text-gray-900 mb-3">Tenants</h2>
                @foreach($contract->tenants as $t)
                    <div class="flex items-center gap-2 py-1.5 border-b border-gray-50 last:border-0">
                        <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-semibold">
                            {{ strtoupper(substr($t->user->name,0,1)) }}
                        </div>
                        <div>
                            <p class="text-sm text-gray-900">{{ $t->user->name }}</p>
                            <p class="text-xs text-gray-400">{{ $t->user->email }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="space-y-4">
            <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
                <h2 class="text-sm font-semibold text-gray-900 mb-3">Invoices</h2>
                @forelse($contract->invoices->take(6) as $inv)
                    <div class="flex items-center justify-between py-1.5 border-b border-gray-50 last:border-0 text-sm">
                        <a href="{{ route('invoices.show', $inv) }}" class="text-blue-600 hover:underline font-medium">
                            {{ $inv->invoice_number }}
                        </a>
                        <x-status-badge :status="$inv->status" />
                    </div>
                @empty
                    <p class="text-xs text-gray-400">No invoices yet.</p>
                @endforelse
            </div>
        </div>
    </div>

</x-layouts.app>
