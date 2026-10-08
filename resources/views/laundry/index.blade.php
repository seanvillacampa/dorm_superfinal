<x-layouts.app title="Laundry — Quest Building">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Laundry Transactions</h1>
            <p class="text-sm text-gray-500 mt-0.5">All orders recorded at the laundry counter</p>
        </div>
        @can('create', App\Models\LaundryOrder::class)
            <a href="{{ route('laundry.create') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 4v16m8-8H4"/></svg>
                Record Laundry
            </a>
        @endcan
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="mb-4 px-4 py-2.5 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Filters --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4 mb-4">
        <form method="GET" action="{{ route('laundry.index') }}"
              class="flex flex-wrap items-end gap-3">

            <div class="flex-1 min-w-[160px]">
                <label class="block text-xs font-medium text-gray-600 mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Order no. or customer…"
                       class="w-full text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                <select name="status"
                        class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All</option>
                    <option value="paid"   @selected(request('status') === 'paid')>Paid</option>
                    <option value="unpaid" @selected(request('status') === 'unpaid')>NP</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Method</label>
                <select name="method"
                        class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All</option>
                    <option value="cash"   @selected(request('method') === 'cash')>Cash</option>
                    <option value="online" @selected(request('method') === 'online')>Online</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">From</label>
                <input type="date" name="from" value="{{ request('from') }}"
                       class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">To</label>
                <input type="date" name="to" value="{{ request('to') }}"
                       class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="flex gap-2">
                <button type="submit"
                        class="px-4 py-1.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    Filter
                </button>
                <a href="{{ route('laundry.index') }}"
                   class="px-4 py-1.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                    Clear
                </a>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Order No.</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Customer</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Type</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Room</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Services</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Total</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Method</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($orders as $order)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ $order->order_no }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $order->date_received->format('M d, Y') }}</td>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $order->customer->name }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $customerTypes[$order->customer_type] ?? $order->customer_type }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $order->room_no ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600 text-xs leading-5">
                            @foreach ($order->items as $item)
                                {{ $item->service->name }} ({{ $item->weight_kg }} kg)<br>
                            @endforeach
                        </td>
                        <td class="px-4 py-3 text-right font-medium text-gray-900">₱{{ number_format($order->total_amount, 2) }}</td>
                        <td class="px-4 py-3">
                            <span class="text-xs px-2 py-0.5 rounded-full
                                {{ $order->payment_method === 'cash' ? 'bg-blue-50 text-blue-700' :
                                   ($order->payment_method === 'online' ? 'bg-purple-50 text-purple-700' : 'bg-gray-100 text-gray-500') }}">
                                {{ $order->paymentMethodLabel }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium
                                {{ $order->payment_status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                {{ $order->paymentStatusLabel }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2 justify-end">
                                <a href="{{ route('laundry.show', $order) }}"
                                   class="text-xs text-blue-600 hover:underline font-medium">View</a>
                                @can('update', $order)
                                    <a href="{{ route('laundry.edit', $order) }}"
                                       class="text-xs text-gray-500 hover:text-gray-700 hover:underline">Edit</a>
                                @endcan
                                @can('delete', $order)
                                    <form method="POST" action="{{ route('laundry.destroy', $order) }}"
                                          onsubmit="return confirm('Delete {{ $order->order_no }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="text-xs text-red-500 hover:text-red-700 hover:underline">Delete</button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-4 py-10 text-center text-sm text-gray-400">
                            No laundry transactions found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($orders->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">
                {{ $orders->links() }}
            </div>
        @endif
    </div>

</x-layouts.app>
