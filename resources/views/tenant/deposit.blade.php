<x-layouts.tenant title="Deposit — Dorm Manager">

    <h1 class="text-2xl font-bold text-gray-900 mb-1">Deposit</h1>
    <p class="text-sm text-gray-500 mb-6">What you paid, and what is held for you</p>

    @if($contract)
        @php
            $collected  = $entries->where('type','collected')->sum('amount');
            $deductions = $entries->where('type','deduction')->sum('amount');
            $balance    = $collected - $deductions;
        @endphp

        {{-- Summary cards --}}
        <div class="grid grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
                <p class="text-xs text-gray-400">Required</p>
                <p class="text-xl font-bold text-gray-900 mt-1">₱{{ number_format($contract->deposit_required,2) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
                <p class="text-xs text-gray-400">Collected</p>
                <p class="text-xl font-bold text-green-600 mt-1">₱{{ number_format($collected,2) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
                <p class="text-xs text-gray-400">Deductions</p>
                <p class="text-xl font-bold text-red-500 mt-1">− ₱{{ number_format($deductions,2) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
                <p class="text-xs text-gray-400">Refundable balance</p>
                <p class="text-xl font-bold text-blue-600 mt-1">₱{{ number_format($balance,2) }}</p>
            </div>
        </div>

        {{-- History --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden mb-4">
            <div class="px-4 py-3 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-900">Deposit history</h2>
            </div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-xs text-gray-400 font-medium uppercase tracking-wide">
                        <th class="px-4 py-3 text-left">Date</th>
                        <th class="px-4 py-3 text-left">Type</th>
                        <th class="px-4 py-3 text-left">Reason</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($entries as $entry)
                        <tr>
                            <td class="px-4 py-3 text-gray-600">{{ $entry->date->format('M d, Y') }}</td>
                            <td class="px-4 py-3"><x-status-badge :status="$entry->type" /></td>
                            <td class="px-4 py-3 text-gray-700">{{ $entry->reason ?? '—' }}</td>
                            <td class="px-4 py-3 text-right font-medium {{ $entry->type === 'deduction' ? 'text-red-600' : 'text-gray-900' }}">
                                {{ $entry->type === 'deduction' ? '−' : '' }} ₱{{ number_format($entry->amount,2) }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-6 text-center text-sm text-gray-400">No deposit entries yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="bg-gray-50 rounded-xl border border-gray-200 p-4 text-sm text-gray-600">
            <p class="font-medium text-gray-900 mb-1">How your deposit works</p>
            <p>The remaining balance is refunded after move-out inspection. Deductions are listed above with a reason.</p>
        </div>
    @else
        <p class="text-sm text-gray-400">No active contract found.</p>
    @endif

</x-layouts.tenant>
