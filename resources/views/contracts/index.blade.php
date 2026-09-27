<x-layouts.app title="Contracts — Dorm Manager">

    <x-page-header title="Contracts" badge="Owner + Employee"
        subtitle="Contract letters, rent and deposit terms">
        <x-slot:actions>
            <a href="{{ route('contracts.create') }}"
               class="px-3.5 py-2 text-sm font-medium rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                + New contract
            </a>
        </x-slot:actions>
    </x-page-header>

    @if(session('success'))
        <div class="mb-4 px-4 py-2 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
    @endif

    <form method="GET" class="flex items-center gap-3 mb-4">
        <div class="flex items-center gap-2 bg-gray-100 rounded-md px-3 py-1.5">
            <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search contract ID or room"
                   class="bg-transparent text-xs text-gray-600 placeholder-gray-400 outline-none w-40" />
        </div>
        <button type="submit" class="text-sm text-blue-600 hover:underline">Filter</button>
    </form>

    <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-xs text-gray-400 font-medium uppercase tracking-wide">
                    <th class="px-4 py-3 text-left">Contract</th>
                    <th class="px-4 py-3 text-left">Room</th>
                    <th class="px-4 py-3 text-left">Start</th>
                    <th class="px-4 py-3 text-left">End</th>
                    <th class="px-4 py-3 text-left">Rent / tenant</th>
                    <th class="px-4 py-3 text-left">Deposit</th>
                    <th class="px-4 py-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($contracts as $contract)
                    @php
                        $tenantCount   = $contract->tenants->count();
                        $rentPerTenant = $tenantCount > 0
                            ? round($contract->room->monthly_rate / $tenantCount, 2)
                            : $contract->room->monthly_rate;
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 font-medium text-gray-900">CT-{{ str_pad($contract->id, 4, '0', STR_PAD_LEFT) }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $contract->room->room_number }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $contract->start_date->format('M d, Y') }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $contract->end_date->format('M d, Y') }}</td>
                        <td class="px-4 py-3 text-gray-900">
                            ₱{{ number_format($rentPerTenant, 2) }}
                            @if($tenantCount > 1)
                                <span class="text-xs text-gray-400 ml-1">÷{{ $tenantCount }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-900">₱{{ number_format($contract->deposit_required, 2) }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('contracts.show', $contract) }}" class="text-xs font-medium text-blue-600 hover:underline">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-gray-400">No contracts found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-gray-100 flex items-center justify-between">
            <p class="text-xs text-gray-400">Showing {{ $contracts->firstItem() }}–{{ $contracts->lastItem() }} of {{ $contracts->total() }} contracts</p>
            {{ $contracts->links('vendor.pagination.simple-tailwind') }}
        </div>
    </div>

</x-layouts.app>
