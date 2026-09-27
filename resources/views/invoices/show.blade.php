<x-layouts.app title="{{ $invoice->invoice_number }} — Dorm Manager">

    <div class="flex items-start justify-between mb-6">
        <div>
            <div class="flex items-center gap-2 flex-wrap">
                <h1 class="text-2xl font-bold text-gray-900">Invoice {{ $invoice->invoice_number }}</h1>
                <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">Owner + Employee</span>
                <x-status-badge :status="str_replace('_',' ',$invoice->status)" />
            </div>
            <p class="text-sm text-gray-500 mt-0.5">
                Room {{ $invoice->contract->room->room_number }} ·
                Floor {{ $invoice->contract->room->floor }} ·
                {{ now()->parse($invoice->billing_month)->format('F Y') }} ·
                {{ $invoice->tenant_count }} tenant(s)
            </p>
        </div>
        <button class="px-3.5 py-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-700 hover:bg-gray-50">
            Download PDF
        </button>
    </div>

    <div class="grid grid-cols-[1fr_300px] gap-4 items-start">

        {{-- ── Left: invoice breakdown ──────────────────────────────── --}}
        <div class="space-y-4">

            {{-- Meta + Room totals --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-5">
                <div class="grid grid-cols-4 gap-4 mb-5 text-sm">
                    <div><p class="text-xs text-gray-400">Billing month</p><p class="font-medium text-gray-900 mt-0.5">{{ now()->parse($invoice->billing_month)->format('F Y') }}</p></div>
                    <div><p class="text-xs text-gray-400">Due date</p><p class="font-medium text-gray-900 mt-0.5">{{ $invoice->due_date->format('M d, Y') }}</p></div>
                    <div><p class="text-xs text-gray-400">Invoice no.</p><p class="font-medium text-gray-900 mt-0.5">{{ $invoice->invoice_number }}</p></div>
                    <div><p class="text-xs text-gray-400">Tenants</p><p class="font-medium text-gray-900 mt-0.5">{{ $invoice->tenant_count }}</p></div>
                </div>

                <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-3">Room charges</p>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <div>
                            <p class="font-medium text-gray-900">Monthly rent</p>
                            <p class="text-xs text-gray-400">Room {{ $invoice->contract->room->room_number }} · full room rate</p>
                        </div>
                        <p class="font-medium text-gray-900">₱{{ number_format($invoice->rent_amount, 2) }}</p>
                    </div>
                    <div class="flex justify-between">
                        <div>
                            <p class="font-medium text-gray-900">Electricity</p>
                            <p class="text-xs text-gray-400">Shared equally among {{ $invoice->tenant_count }} tenant(s)</p>
                        </div>
                        <p class="font-medium text-gray-900">₱{{ number_format($invoice->electricity_amount, 2) }}</p>
                    </div>
                </div>

                <div class="border-t border-gray-100 mt-4 pt-4 space-y-1 text-sm">
                    <div class="flex justify-between text-gray-600">
                        <span>Total room bill</span>
                        <span>₱{{ number_format($invoice->total_amount, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-gray-500 text-xs">
                        <span>Per tenant share (rent + electricity)</span>
                        <span>₱{{ number_format($invoice->amountPerTenant(), 2) }}</span>
                    </div>
                    <div class="flex justify-between text-green-600">
                        <span>Collected so far</span>
                        <span>₱{{ number_format($invoice->amount_paid, 2) }}</span>
                    </div>
                    <div class="flex justify-between font-bold text-gray-900 text-base border-t border-gray-100 pt-2">
                        <span>Balance remaining</span>
                        <span class="{{ $invoice->balanceDue() > 0 ? 'text-red-600' : 'text-green-600' }}">
                            ₱{{ number_format($invoice->balanceDue(), 2) }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- ── Tenant payment breakdown ──────────────────────────── --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
                <h2 class="text-sm font-semibold text-gray-900 mb-3">Tenant payment breakdown</h2>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-xs text-gray-400 font-medium uppercase tracking-wide">
                            <th class="pb-2 text-left">Tenant</th>
                            <th class="pb-2 text-right">Share (rent)</th>
                            <th class="pb-2 text-right">Share (elec.)</th>
                            <th class="pb-2 text-right">Total share</th>
                            <th class="pb-2 text-right">Paid</th>
                            <th class="pb-2 text-left">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($invoice->tenantPayments as $tp)
                            <tr class="hover:bg-gray-50">
                                <td class="py-2.5">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-semibold shrink-0">
                                            {{ strtoupper(substr($tp->tenant->user->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900 text-xs">{{ $tp->tenant->user->name }}</p>
                                            <p class="text-gray-400 text-[11px]">{{ $tp->tenant->user->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-2.5 text-right text-gray-700">₱{{ number_format($invoice->rentPerTenant(), 2) }}</td>
                                <td class="py-2.5 text-right text-gray-700">₱{{ number_format($invoice->electricityPerTenant(), 2) }}</td>
                                <td class="py-2.5 text-right font-medium text-gray-900">₱{{ number_format($tp->share_amount, 2) }}</td>
                                <td class="py-2.5 text-right {{ $tp->amount_paid > 0 ? 'text-green-600' : 'text-gray-400' }}">
                                    {{ $tp->amount_paid > 0 ? '₱'.number_format($tp->amount_paid, 2) : '—' }}
                                </td>
                                <td class="py-2.5">
                                    <x-status-badge :status="$tp->status" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Cash payments log --}}
            @if($invoice->payments->isNotEmpty())
                <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
                    <h2 class="text-sm font-semibold text-gray-900 mb-3">Payments received</h2>
                    @foreach($invoice->payments as $p)
                        <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0 text-sm">
                            <div>
                                <p class="text-gray-700">{{ $p->received_at->format('M d, Y') }}</p>
                                <span class="text-xs bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded">
                                    {{ ucfirst(str_replace('_',' ',$p->method)) }}
                                </span>
                                @if($p->recordedByUser)
                                    <span class="text-xs text-gray-400 ml-1">by {{ $p->recordedByUser->name }}</span>
                                @endif
                            </div>
                            <p class="font-semibold text-gray-900">₱{{ number_format($p->amount, 2) }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ── Right: actions ──────────────────────────────────────────── --}}
        <div class="space-y-3">
            <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
                <h2 class="text-sm font-semibold text-gray-900 mb-3">Actions</h2>
                <a href="{{ route('payments.index') }}"
                   class="block w-full text-center px-3.5 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 mb-2">
                    Record cash payment
                </a>
                @if($canVoid)
                    <form method="POST" action="{{ route('invoices.void', $invoice) }}">
                        @csrf
                        <button type="submit"
                                onclick="return confirm('Void this invoice? This cannot be undone.')"
                                class="w-full px-3.5 py-2 border border-red-200 text-red-600 text-sm font-medium rounded-lg hover:bg-red-50 transition-colors">
                            Void invoice (owner only)
                        </button>
                    </form>
                @endif
            </div>

            {{-- Room info summary --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
                <h2 class="text-sm font-semibold text-gray-900 mb-2">Room summary</h2>
                <dl class="space-y-1.5 text-sm">
                    <dt class="text-xs text-gray-400">Room</dt>
                    <dd class="text-gray-900">{{ $invoice->contract->room->room_number }} · Floor {{ $invoice->contract->room->floor }}</dd>
                    <dt class="text-xs text-gray-400 mt-2">Room rate</dt>
                    <dd class="text-gray-900">₱{{ number_format($invoice->contract->room->monthly_rate, 2) }}/month</dd>
                    <dt class="text-xs text-gray-400 mt-2">Per tenant</dt>
                    <dd class="font-semibold text-gray-900">₱{{ number_format($invoice->amountPerTenant(), 2) }}</dd>
                    <dt class="text-xs text-gray-400 mt-2">Capacity</dt>
                    <dd class="text-gray-900">{{ $invoice->contract->room->capacity }} bed(s)</dd>
                </dl>
            </div>
        </div>
    </div>

</x-layouts.app>
