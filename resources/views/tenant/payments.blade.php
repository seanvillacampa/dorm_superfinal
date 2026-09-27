<x-layouts.tenant title="Payment History — Dorm Manager">

    <h1 class="text-2xl font-bold text-gray-900 mb-1">Payment History</h1>
    <p class="text-sm text-gray-500 mb-6">Your personal payment records</p>

    <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-xs text-gray-400 font-medium uppercase tracking-wide">
                    <th class="px-4 py-3 text-left">Billing month</th>
                    <th class="px-4 py-3 text-left">Invoice</th>
                    <th class="px-4 py-3 text-right">Your share</th>
                    <th class="px-4 py-3 text-right">Paid</th>
                    <th class="px-4 py-3 text-left">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($payments as $tp)
                    <tr>
                        <td class="px-4 py-3 text-gray-600">
                            {{ now()->parse($tp->invoice->billing_month)->format('F Y') }}
                        </td>
                        <td class="px-4 py-3 text-blue-600 font-medium">
                            {{ $tp->invoice->invoice_number }}
                        </td>
                        <td class="px-4 py-3 text-right text-gray-900 font-medium">
                            ₱{{ number_format($tp->share_amount, 2) }}
                        </td>
                        <td class="px-4 py-3 text-right {{ $tp->amount_paid > 0 ? 'text-green-600' : 'text-gray-400' }}">
                            {{ $tp->amount_paid > 0 ? '₱'.number_format($tp->amount_paid,2) : '—' }}
                        </td>
                        <td class="px-4 py-3"><x-status-badge :status="$tp->status" /></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400">No payment history yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($payments instanceof \Illuminate\Pagination\LengthAwarePaginator && $payments->hasPages())
            <div class="px-4 py-3 border-t border-gray-100 flex items-center justify-between">
                <p class="text-xs text-gray-400">Showing {{ $payments->firstItem() }}–{{ $payments->lastItem() }} of {{ $payments->total() }}</p>
                {{ $payments->links('vendor.pagination.simple-tailwind') }}
            </div>
        @endif
    </div>

</x-layouts.tenant>
