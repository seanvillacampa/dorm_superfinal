<x-layouts.tenant title="My Bill — Dorm Manager">

    <h1 class="text-2xl font-bold text-gray-900 mb-0.5">My Bill</h1>
    <p class="text-sm text-gray-500 mb-6">
        {{ now()->format('F Y') }}
        @if($contract) · Room {{ $contract->room->room_number }} @endif
    </p>

    @if(!$contract)
        <div class="bg-blue-50 border border-blue-100 rounded-xl p-5 text-sm text-blue-700">
            No active contract found. Contact the dorm office.
        </div>
    @elseif(!$invoice)
        <div class="bg-blue-50 border border-blue-100 rounded-xl p-5 text-sm text-blue-700">
            No invoice has been generated for this month yet. Check back later.
        </div>
    @else
        @php
            $myPaid    = $myPayment?->amount_paid ?? 0;
            $myShare   = $myPayment?->share_amount ?? $invoice->amountPerTenant();
            $myBalance = max(0, $myShare - $myPaid);
            $myStatus  = $myPayment?->status ?? 'pending';
        @endphp

        {{-- Late / overdue banner --}}
        @if(in_array($myStatus, ['late','overdue']))
            <div class="mb-4 px-4 py-3 bg-yellow-50 border border-yellow-200 rounded-xl flex items-center justify-between text-sm text-yellow-700">
                <span>Your payment is <strong>{{ $myStatus }}</strong>. Please settle immediately to avoid further charges.</span>
                <x-status-badge :status="$myStatus" />
            </div>
        @endif

        <div class="grid grid-cols-[1fr_280px] gap-6 items-start">

            {{-- ── Left: MY bill ───────────────────────────────────────── --}}
            <div class="space-y-4">

                {{-- My amount due --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-5">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <p class="text-xs text-gray-400 mb-1">Your amount due</p>
                            <p class="text-4xl font-bold {{ $myBalance > 0 ? 'text-gray-900' : 'text-green-600' }}">
                                ₱{{ number_format($myBalance, 2) }}
                            </p>
                            <p class="text-xs text-gray-400 mt-1">Due {{ $invoice->due_date->format('M d, Y') }}</p>
                        </div>
                        <x-status-badge :status="$myStatus" />
                    </div>

                    {{-- Bill breakdown: my share --}}
                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-2">Your share breakdown</p>
                    <div class="space-y-1.5 text-sm">
                        <div class="flex justify-between text-gray-700">
                            <span>
                                Rent share
                                <span class="text-xs text-gray-400">
                                    (₱{{ number_format($invoice->rent_amount, 2) }} ÷ {{ $invoice->tenant_count }})
                                </span>
                            </span>
                            <span>₱{{ number_format($invoice->rentPerTenant(), 2) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-700">
                            <span>
                                Electricity share
                                @if($reading)
                                    <span class="text-xs text-gray-400">
                                        ({{ number_format($reading->kwh_used, 1) }} kWh ÷ {{ $invoice->tenant_count }})
                                    </span>
                                @endif
                            </span>
                            <span>₱{{ number_format($invoice->electricityPerTenant(), 2) }}</span>
                        </div>
                        <div class="flex justify-between font-semibold text-gray-900 border-t border-gray-100 pt-2">
                            <span>Your total</span>
                            <span>₱{{ number_format($myShare, 2) }}</span>
                        </div>
                        @if($myPaid > 0)
                            <div class="flex justify-between text-green-600">
                                <span>Paid</span>
                                <span>− ₱{{ number_format($myPaid, 2) }}</span>
                            </div>
                            <div class="flex justify-between font-bold text-gray-900 border-t border-gray-100 pt-2">
                                <span>Balance</span>
                                <span class="{{ $myBalance > 0 ? 'text-red-600' : 'text-green-600' }}">
                                    ₱{{ number_format($myBalance, 2) }}
                                </span>
                            </div>
                        @endif
                    </div>

                    {{-- Pay button --}}
                    @if($myBalance > 0)
                        @error('pay')
                            <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg text-xs text-red-600">{{ $message }}</div>
                        @enderror
                        <form method="POST" action="{{ route('tenant.pay', $invoice) }}" class="mt-4">
                            @csrf
                            <button type="submit"
                                    class="w-full py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-colors text-sm">
                                Pay ₱{{ number_format($myBalance, 2) }} with PayMongo
                            </button>
                        </form>
                        <p class="text-center text-xs text-gray-400 mt-2">GCash &middot; Maya &middot; Card &middot; QR Ph</p>
                    @else
                        <div class="mt-4 flex items-center justify-center gap-2 py-3 bg-green-50 rounded-xl text-sm font-medium text-green-700">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path d="M5 13l4 4L19 7"/>
                            </svg>
                            Your share is fully paid
                        </div>
                    @endif
                </div>

                {{-- Full room bill --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
                    <h2 class="text-sm font-semibold text-gray-900 mb-3">Full room bill</h2>
                    <div class="space-y-1.5 text-sm">
                        <div class="flex justify-between text-gray-600">
                            <span>Total rent</span>
                            <span>₱{{ number_format($invoice->rent_amount, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Total electricity</span>
                            <span>₱{{ number_format($invoice->electricity_amount, 2) }}</span>
                        </div>
                        <div class="flex justify-between font-semibold text-gray-900 border-t border-gray-100 pt-2">
                            <span>Room total</span>
                            <span>₱{{ number_format($invoice->total_amount, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-green-600 text-xs">
                            <span>Collected from all tenants</span>
                            <span>₱{{ number_format($invoice->amount_paid, 2) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Co-tenant payment status --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
                    <h2 class="text-sm font-semibold text-gray-900 mb-3">
                        Roommates
                        <span class="text-xs font-normal text-gray-400 ml-1">{{ $invoice->tenantPayments->count() }} tenant(s)</span>
                    </h2>
                    <div class="space-y-2">
                        @foreach($invoice->tenantPayments as $tp)
                            @php $isMe = $tp->tenant_id === Auth::user()->tenant?->id; @endphp
                            <div class="flex items-center justify-between py-1.5 {{ !$loop->last ? 'border-b border-gray-50' : '' }}">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-full {{ $isMe ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600' }} flex items-center justify-center text-xs font-semibold shrink-0">
                                        {{ strtoupper(substr($tp->tenant->user->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ $tp->tenant->user->name }}
                                            @if($isMe) <span class="text-xs text-blue-500 font-normal">(you)</span> @endif
                                        </p>
                                        <p class="text-xs text-gray-400">₱{{ number_format($tp->share_amount, 2) }} share</p>
                                    </div>
                                </div>
                                <x-status-badge :status="$tp->status" />
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- ── Right: room + meter info ──────────────────────────────── --}}
            <div class="space-y-4">

                <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
                    <h2 class="text-sm font-semibold text-gray-900 mb-3">My room</h2>
                    <dl class="space-y-2 text-sm">
                        <dt class="text-xs text-gray-400">Room</dt>
                        <dd class="text-gray-900">{{ $contract->room->room_number }} · Floor {{ $contract->room->floor }}</dd>
                        <dt class="text-xs text-gray-400 mt-1">Contract period</dt>
                        <dd class="text-gray-900 text-xs">{{ $contract->start_date->format('M d, Y') }} – {{ $contract->end_date->format('M d, Y') }}</dd>
                        <dt class="text-xs text-gray-400 mt-1">Room rate</dt>
                        <dd class="text-gray-900">₱{{ number_format($contract->room->monthly_rate, 2) }}/month</dd>
                        <dt class="text-xs text-gray-400 mt-1">Your rent share</dt>
                        <dd class="font-semibold text-gray-900">₱{{ number_format($invoice->rentPerTenant(), 2) }}</dd>
                        <dt class="text-xs text-gray-400 mt-1">Deposit held</dt>
                        <dd class="text-gray-900">₱{{ number_format($contract->deposit_collected, 2) }}</dd>
                    </dl>
                </div>

                @if($reading)
                    <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
                        <h2 class="text-sm font-semibold text-gray-900 mb-3">Electricity meter</h2>
                        <dl class="space-y-2 text-sm">
                            <dt class="text-xs text-gray-400">Previous reading</dt>
                            <dd class="text-gray-900">{{ number_format($reading->previous_kwh, 1) }} kWh</dd>
                            <dt class="text-xs text-gray-400 mt-1">Current reading</dt>
                            <dd class="text-gray-900">{{ number_format($reading->current_kwh, 1) }} kWh</dd>
                            <dt class="text-xs text-gray-400 mt-1">Room used</dt>
                            <dd class="font-medium text-gray-900">{{ number_format($reading->kwh_used, 1) }} kWh</dd>
                            <dt class="text-xs text-gray-400 mt-1">Your share</dt>
                            <dd class="font-semibold text-gray-900">
                                {{ number_format($reading->kwh_used / max(1,$invoice->tenant_count), 1) }} kWh
                                = ₱{{ number_format($invoice->electricityPerTenant(), 2) }}
                            </dd>
                        </dl>
                    </div>
                @endif

                <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
                    <h2 class="text-sm font-semibold text-gray-900 mb-1">Need help?</h2>
                    <p class="text-xs text-gray-400 mb-3">Questions about your bill? Contact the dorm office.</p>
                    <button class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                        Message the office
                    </button>
                </div>
            </div>
        </div>
    @endif

</x-layouts.tenant>
