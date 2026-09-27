<x-layouts.app title="Meter Readings — Dorm Manager">

    <x-page-header title="Meter Readings" badge="Owner + Employee"
        subtitle="Enter this month's kWh reading per room">
        <x-slot:actions>
            <button class="px-3.5 py-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-700 hover:bg-gray-50">
                Import CSV
            </button>
            <button form="readings-form" type="submit"
                    class="px-3.5 py-2 text-sm font-medium rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                Save all
            </button>
        </x-slot:actions>
    </x-page-header>

    @if(session('success'))
        <div class="mb-4 px-4 py-2 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
    @endif

    {{-- Month picker --}}
    <div class="flex items-center gap-3 mb-4">
        <h2 class="text-sm font-semibold text-gray-900">{{ now()->parse($month)->format('F Y') }} readings</h2>
        <input type="month" value="{{ $month }}"
               onchange="window.location='{{ route('meter-readings.index') }}?month='+this.value"
               class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 bg-white text-gray-700 ml-auto" />
    </div>

    {{-- Progress bar --}}
    @if($total > 0)
    <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4 mb-4">
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm text-gray-700">{{ $recorded }} of {{ $total }} rooms recorded</p>
            @if($recorded < $total)
                <p class="text-xs text-yellow-600">{{ $total - $recorded }} still needed before invoices can be generated</p>
            @endif
        </div>
        <div class="w-full bg-gray-100 rounded-full h-2">
            <div class="bg-green-500 h-2 rounded-full transition-all"
                 style="width: {{ $total > 0 ? round(($recorded/$total)*100) : 0 }}%"></div>
        </div>
    </div>
    @endif

    <form id="readings-form" method="POST" action="{{ route('meter-readings.store') }}">
        @csrf
        <input type="hidden" name="month" value="{{ $month }}" />

        <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-xs text-gray-400 font-medium uppercase tracking-wide">
                        <th class="px-4 py-3 text-left">Room</th>
                        <th class="px-4 py-3 text-left">Tenant</th>
                        <th class="px-4 py-3 text-left">Meter no.</th>
                        <th class="px-4 py-3 text-left">Previous (kWh)</th>
                        <th class="px-4 py-3 text-left">Current (kWh)</th>
                        <th class="px-4 py-3 text-left">Used (kWh)</th>
                        <th class="px-4 py-3 text-left">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($rooms as $i => $room)
                        @php
                            $reading = $readings->get($room->id);
                            $prev    = $prevReadings->get($room->id);
                            $tenantName = $room->activeContract?->tenants->first()?->user->name ?? '—';
                        @endphp
                        <input type="hidden" name="readings[{{ $i }}][room_id]" value="{{ $room->id }}" />
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 font-semibold text-gray-900">{{ $room->room_number }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $tenantName }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $room->meter_number ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $prev?->current_kwh ?? $reading?->previous_kwh ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <input type="number" name="readings[{{ $i }}][current_kwh]"
                                       value="{{ $reading?->current_kwh }}"
                                       step="0.1" min="0" placeholder="Enter reading"
                                       class="w-32 border border-gray-200 rounded-lg px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ $reading ? number_format($reading->kwh_used, 1) : '—' }}
                            </td>
                            <td class="px-4 py-3">
                                @if($reading)
                                    <x-status-badge status="Recorded" />
                                @else
                                    <x-status-badge status="Needed" />
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-gray-400">No occupied rooms.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

</x-layouts.app>
