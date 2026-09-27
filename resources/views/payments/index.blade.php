<x-layouts.app title="Payments — Dorm Manager">

    <x-page-header title="Payments" badge="Owner + Employee"
        subtitle="Online (PayMongo) and cash payments" />

    @if(session('success'))
        <div class="mb-4 px-4 py-2 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 px-4 py-2 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
            @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
        </div>
    @endif

    <div class="grid grid-cols-[1fr_320px] gap-4 items-start">

        {{-- ── Payments history table ───────────────────────────────── --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-xs text-gray-400 font-medium uppercase tracking-wide">
                        <th class="px-4 py-3 text-left">Date</th>
                        <th class="px-4 py-3 text-left">Invoice</th>
                        <th class="px-4 py-3 text-left">Tenant</th>
                        <th class="px-4 py-3 text-left">Method</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                        <th class="px-4 py-3 text-left">Recorded by</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($payments as $p)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 text-gray-600">{{ $p->received_at->format('M d, Y') }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('invoices.show', $p->invoice) }}" class="text-blue-600 font-medium hover:underline">
                                    {{ $p->invoice->invoice_number }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-gray-700 text-xs">
                                {{ $p->tenantPayment?->tenant->user->name ?? $p->invoice->contract->tenants->first()?->user->name ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded font-medium">
                                    {{ ucfirst(str_replace('_',' ',$p->method)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-medium text-gray-900 text-right">₱{{ number_format($p->amount,2) }}</td>
                            <td class="px-4 py-3 text-gray-500 text-xs">
                                {{ $p->recorded_by_type === 'paymongo' ? 'PayMongo' : ($p->recordedByUser?->name ?? 'Staff') }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400">No payments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="px-4 py-3 border-t border-gray-100 flex items-center justify-between">
                <p class="text-xs text-gray-400">Showing {{ $payments->firstItem() }}–{{ $payments->lastItem() }} of {{ $payments->total() }}</p>
                {{ $payments->links('vendor.pagination.simple-tailwind') }}
            </div>
        </div>

        {{-- ── Record cash payment form ─────────────────────────────── --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4"
             x-data="paymentForm()" x-init="init()">

            <h2 class="text-sm font-semibold text-gray-900 mb-1">Record cash payment</h2>
            <p class="text-xs text-gray-400 mb-4">Select an invoice, then choose which tenant paid.</p>

            <form method="POST" action="{{ route('payments.store') }}" class="space-y-3">
                @csrf

                {{-- Step 1: Invoice select --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Invoice</label>
                    <select name="invoice_id" x-model="invoiceId" @change="loadTenants()"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="">Select invoice…</option>
                        @foreach($openInvoices as $inv)
                            <option value="{{ $inv->id }}"
                                    data-tenants="{{ $inv->tenantPayments->map(fn($tp) => [
                                        'tp_id'  => $tp->id,
                                        'name'   => $tp->tenant->user->name,
                                        'share'  => $tp->share_amount,
                                        'paid'   => $tp->amount_paid,
                                        'status' => $tp->status,
                                    ])->values()->toJson() }}"
                                    @selected(old('invoice_id') == $inv->id)>
                                {{ $inv->invoice_number }} · Room {{ $inv->contract->room->room_number }}
                                ({{ $inv->tenantPayments->where('status','!=','paid')->count() }} unpaid)
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Step 2: Tenant selector (shown after invoice picked) --}}
                <div x-show="tenants.length > 0" style="display:none">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Which tenant is paying?</label>
                    <div class="space-y-1.5 max-h-40 overflow-y-auto border border-gray-200 rounded-lg p-2">
                        <template x-for="t in tenants" :key="t.tp_id">
                            <label class="flex items-center justify-between p-2 rounded-lg cursor-pointer hover:bg-gray-50"
                                   :class="t.status === 'paid' ? 'opacity-40 cursor-not-allowed' : ''">
                                <div class="flex items-center gap-2">
                                    <input type="radio" name="tenant_payment_id" :value="t.tp_id"
                                           x-model="selectedTpId"
                                           @change="amount = t.share - t.paid"
                                           :disabled="t.status === 'paid'"
                                           class="text-blue-600" />
                                    <span class="text-xs font-medium text-gray-900" x-text="t.name"></span>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs text-gray-500" x-text="'₱' + Number(t.share - t.paid).toLocaleString('en-PH',{minimumFractionDigits:2})"></span>
                                    <span class="ml-1.5 text-[10px] px-1.5 py-0.5 rounded font-medium"
                                          :class="{
                                              'bg-green-100 text-green-700': t.status === 'paid',
                                              'bg-yellow-100 text-yellow-700': t.status === 'late',
                                              'bg-red-100 text-red-700': t.status === 'overdue',
                                              'bg-gray-100 text-gray-500': t.status === 'pending',
                                          }"
                                          x-text="t.status"></span>
                                </div>
                            </label>
                        </template>
                    </div>
                    <input type="hidden" name="tenant_payment_id" :value="selectedTpId" />
                </div>

                {{-- Amount --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Amount (₱)</label>
                    <input type="number" name="amount" :value="amount" x-model="amount" step="0.01" min="0.01"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required />
                </div>

                {{-- Method --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Payment method</label>
                    <select name="method" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="cash"          @selected(old('method','cash')==='cash')>Cash</option>
                        <option value="bank_transfer" @selected(old('method')==='bank_transfer')>Bank transfer</option>
                        <option value="gcash"         @selected(old('method')==='gcash')>GCash</option>
                        <option value="maya"          @selected(old('method')==='maya')>Maya</option>
                        <option value="card"          @selected(old('method')==='card')>Card</option>
                        <option value="qr_ph"         @selected(old('method')==='qr_ph')>QR Ph</option>
                    </select>
                </div>

                {{-- Date --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Date received</label>
                    <input type="date" name="received_at" value="{{ old('received_at', now()->toDateString()) }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required />
                </div>

                {{-- Reference --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Reference / note</label>
                    <input type="text" name="reference" value="{{ old('reference') }}" placeholder="Optional"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                </div>

                <button type="submit"
                        class="w-full px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                    Save payment
                </button>
            </form>
        </div>
    </div>

    <script>
    function paymentForm() {
        return {
            invoiceId: '{{ old('invoice_id', '') }}',
            tenants: [],
            selectedTpId: '',
            amount: '',
            init() {
                if (this.invoiceId) this.loadTenants();
            },
            loadTenants() {
                const sel = document.querySelector('select[name="invoice_id"]');
                const opt = sel?.options[sel?.selectedIndex];
                const raw = opt?.dataset?.tenants;
                this.tenants = raw ? JSON.parse(raw) : [];
                this.selectedTpId = '';
                this.amount = '';
                // Auto-select first unpaid tenant
                const first = this.tenants.find(t => t.status !== 'paid');
                if (first) {
                    this.selectedTpId = first.tp_id;
                    this.amount = (first.share - first.paid).toFixed(2);
                }
            }
        }
    }
    </script>

</x-layouts.app>
