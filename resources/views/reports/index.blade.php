<x-layouts.app title="Reports — Dorm Manager">

    <x-page-header title="Reports" badge="Owner only" subtitle="Income summary and arrears">
        <x-slot:actions>
            <input type="month" value="{{ $month }}"
                   onchange="window.location='{{ route('reports.index') }}?month='+this.value"
                   class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 bg-white text-gray-700" />
            <a href="{{ route('reports.csv', ['month' => $month]) }}"
               class="px-3.5 py-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-700 hover:bg-gray-50">
                Export CSV
            </a>
            <a href="{{ route('reports.pdf', ['month' => $month]) }}"
               class="px-3.5 py-2 text-sm font-medium rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                Export PDF
            </a>
        </x-slot:actions>
    </x-page-header>

    {{-- Summary cards --}}
    <div class="grid grid-cols-2 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
            <p class="text-xs text-gray-500 font-medium">Total billed</p>
            <p class="text-2xl font-bold text-gray-900 mt-1.5">₱{{ number_format($totalBilled,2) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
            <p class="text-xs text-gray-500 font-medium">Collected</p>
            <p class="text-2xl font-bold text-green-600 mt-1.5">₱{{ number_format($totalCollected,2) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-[1fr_280px] gap-4 items-start">
        {{-- Bar chart --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-5">
            <h2 class="text-sm font-semibold text-gray-900 mb-4">Monthly income (₱ thousands)</h2>
            @php $max = max(array_merge($trendValues,[1])); @endphp
            <div class="flex items-end gap-2 h-32">
                @foreach($trendValues as $i => $val)
                    @php $pct = $max > 0 ? ($val/$max)*100 : 0; @endphp
                    <div class="flex-1 flex flex-col items-center gap-1">
                        <p class="text-[10px] text-gray-500">{{ $val > 0 ? round($val/1000) : '' }}</p>
                        <div class="w-full rounded-t-sm"
                             style="height:{{ max(4,$pct) }}%; background-color:{{ $i===count($trendValues)-1?'#bfdbfe':'#3b82f6' }}">
                        </div>
                        <p class="text-[10px] text-gray-400">{{ $trendLabels[$i] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Right --}}
        <div class="space-y-4">
            {{-- Payment status --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
                <h2 class="text-sm font-semibold text-gray-900 mb-3">Payment status</h2>
                @php
                    $statusConfig = [
                        'paid'    => ['label'=>'Paid',            'color'=>'bg-green-500'],
                        'partial' => ['label'=>'Partially paid',  'color'=>'bg-orange-400'],
                        'pending' => ['label'=>'Pending',         'color'=>'bg-gray-300'],
                        'late'    => ['label'=>'Late',            'color'=>'bg-yellow-400'],
                        'overdue' => ['label'=>'Overdue',         'color'=>'bg-red-500'],
                    ];
                    $totalRooms = $statusCounts->sum();
                @endphp
                @foreach($statusConfig as $s => $cfg)
                    @php $cnt = $statusCounts[$s] ?? 0; @endphp
                    <div class="flex items-center justify-between py-1 text-sm">
                        <span class="text-gray-600">{{ $cfg['label'] }}</span>
                        <span class="font-medium text-gray-900">{{ $cnt }} rooms</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-1.5 mb-2">
                        <div class="h-1.5 rounded-full {{ $cfg['color'] }}"
                             style="width:{{ $totalRooms > 0 ? min(100,round(($cnt/$totalRooms)*100)) : 0 }}%">
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Top arrears --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
                <h2 class="text-sm font-semibold text-gray-900 mb-3">Top arrears</h2>
                @forelse($topArrears as $a)
                    <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full bg-red-100 text-red-700 flex items-center justify-center text-xs font-semibold">
                                {{ strtoupper(substr($a['name'],0,1)) }}
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-900">{{ $a['name'] }}</p>
                                <p class="text-[11px] text-gray-400">Room {{ $a['room'] }}</p>
                            </div>
                        </div>
                        <p class="text-sm font-semibold text-red-600">₱{{ number_format($a['balance'],2) }}</p>
                    </div>
                @empty
                    <p class="text-xs text-gray-400 text-center py-3">No arrears this month.</p>
                @endforelse
            </div>
        </div>
    </div>

</x-layouts.app>
