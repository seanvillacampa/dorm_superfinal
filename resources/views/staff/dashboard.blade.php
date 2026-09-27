<x-layouts.app :title="'Dashboard — Dorm Manager'">

    {{-- ── Page header ─────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
                <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">
                    {{ auth()->user()->hasRole('owner') ? 'Owner + Employee' : 'Employee' }}
                </span>
            </div>
            <p class="text-sm text-gray-500 mt-0.5">
                {{ now()->format('F Y') }}
            </p>
        </div>
    </div>

    {{-- ── Stat cards ───────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-4 gap-4 mb-6">

        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-xs">
            <div class="flex items-start justify-between">
                <p class="text-xs text-gray-500 font-medium">Occupied rooms</p>
                <span class="w-2.5 h-2.5 rounded-full bg-blue-500 mt-0.5"></span>
            </div>
            <p class="text-2xl font-bold text-gray-900 mt-1.5">
                {{ $occupiedCount }} <span class="text-gray-300 font-normal">/</span> {{ $totalRooms }}
            </p>
            <p class="text-xs text-gray-400 mt-1">
                {{ $vacantCount }} vacant
                @if($maintenanceCount > 0) &middot; {{ $maintenanceCount }} maintenance @endif
            </p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-xs">
            <div class="flex items-start justify-between">
                <p class="text-xs text-gray-500 font-medium">Collected this month</p>
                <span class="w-2.5 h-2.5 rounded-full bg-green-500 mt-0.5"></span>
            </div>
            <p class="text-2xl font-bold text-gray-900 mt-1.5">₱{{ number_format($collectedThisMonth, 2) }}</p>
            <p class="text-xs text-gray-400 mt-1">of ₱{{ number_format($billedThisMonth, 2) }} billed</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-xs">
            <div class="flex items-start justify-between">
                <p class="text-xs text-gray-500 font-medium">Late</p>
                <span class="w-2.5 h-2.5 rounded-full bg-yellow-400 mt-0.5"></span>
            </div>
            <p class="text-2xl font-bold {{ $lateCount > 0 ? 'text-yellow-500' : 'text-gray-900' }} mt-1.5">
                {{ $lateCount }}
            </p>
            <p class="text-xs text-gray-400 mt-1">Past due date, within grace period</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-xs">
            <div class="flex items-start justify-between">
                <p class="text-xs text-gray-500 font-medium">Overdue</p>
                <span class="w-2.5 h-2.5 rounded-full bg-red-500 mt-0.5"></span>
            </div>
            <p class="text-2xl font-bold {{ $overdueCount > 0 ? 'text-red-500' : 'text-gray-900' }} mt-1.5">
                {{ $overdueCount }}
            </p>
            <p class="text-xs text-gray-400 mt-1">Grace period ended</p>
        </div>
    </div>

    {{-- ── Room grid + Live activity ─────────────────────────────────────── --}}
    <div class="grid grid-cols-[1fr_260px] gap-4 items-start">

        {{-- Room status grid --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-gray-900">Room status</h2>
                {{-- Legend --}}
                <div class="flex items-center gap-3 text-[11px] text-gray-500">
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-green-100 border border-green-200 inline-block"></span>Paid</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-yellow-100 border border-yellow-200 inline-block"></span>Late</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-red-100 border border-red-200 inline-block"></span>Overdue</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-gray-100 border border-gray-200 inline-block"></span>Vacant</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-blue-50 border border-blue-200 inline-block"></span>Pending</span>
                </div>
            </div>

            @if(session('warning'))
                <div class="mb-3 px-3 py-2 bg-yellow-50 border border-yellow-200 text-yellow-700 rounded-lg text-xs">
                    {{ session('warning') }}
                </div>
            @endif

            @if($rooms->isEmpty())
                <div class="flex items-center justify-center h-32 text-sm text-gray-400">
                    No rooms set up yet.
                </div>
            @else
                @foreach($rooms->groupBy('floor') as $floor => $floorRooms)
                    <div class="grid grid-cols-7 gap-2 mb-2">
                        @foreach($floorRooms as $room)
                            @php
                                $invoice = $room->currentInvoice ?? null;
                                $status  = $invoice?->status
                                    ?? ($room->status === 'maintenance' ? 'maintenance' : 'vacant');

                                $cardCls = match(true) {
                                    $status === 'paid'                                    => 'bg-green-50 border-green-200 hover:bg-green-100',
                                    $status === 'late'                                    => 'bg-yellow-50 border-yellow-200 hover:bg-yellow-100',
                                    $status === 'overdue'                                 => 'bg-red-50 border-red-200 hover:bg-red-100',
                                    $status === 'maintenance'                             => 'bg-blue-50 border-blue-200',
                                    $status === 'vacant'                                  => 'bg-gray-50 border-gray-200',
                                    in_array($status,['partial','partial_late','partial_overdue']) => 'bg-orange-50 border-orange-200 hover:bg-orange-100',
                                    default                                               => 'bg-gray-50 border-gray-200 hover:bg-gray-100', // pending
                                };
                                $numCls = match(true) {
                                    $status === 'paid'    => 'text-green-800',
                                    $status === 'late'    => 'text-yellow-800',
                                    $status === 'overdue' => 'text-red-800',
                                    $status === 'maintenance' => 'text-blue-800',
                                    in_array($status,['partial','partial_late','partial_overdue']) => 'text-orange-700',
                                    default               => 'text-gray-600',
                                };
                                $badgeText = match(true) {
                                    $status === 'paid'    => 'Paid',
                                    $status === 'late'    => 'Late',
                                    $status === 'overdue' => 'Overdue',
                                    $status === 'maintenance' => 'Maintenance',
                                    $status === 'vacant'  => 'Vacant',
                                    in_array($status,['partial','partial_late','partial_overdue']) => 'Partial',
                                    $status === 'pending' => 'Pending',
                                    default               => 'No invoice',
                                };
                                $badgeCls = match(true) {
                                    $status === 'paid'    => 'text-green-600',
                                    $status === 'late'    => 'text-yellow-600',
                                    $status === 'overdue' => 'text-red-600',
                                    $status === 'maintenance' => 'text-blue-600',
                                    in_array($status,['partial','partial_late','partial_overdue']) => 'text-orange-600',
                                    default               => 'text-gray-400',
                                };

                                $isClickable = !in_array($status, ['vacant','maintenance']);
                                $href = $isClickable
                                    ? route('rooms.invoice', $room)
                                    : null;
                            @endphp

                            @if($isClickable)
                                <a href="{{ $href }}"
                                   class="rounded-lg border {{ $cardCls }} p-2.5 min-w-0 block transition-colors cursor-pointer">
                            @else
                                <div class="rounded-lg border {{ $cardCls }} p-2.5 min-w-0">
                            @endif
                                <p class="text-xs font-bold {{ $numCls }}">{{ $room->room_number }}</p>
                                <p class="text-[11px] text-gray-600 truncate mt-0.5 leading-tight">
                                    {{ $room->currentTenantName ?? '—' }}
                                </p>
                                <p class="text-[10px] font-medium {{ $badgeCls }} mt-1">{{ $badgeText }}</p>
                            @if($isClickable)
                                </a>
                            @else
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endforeach
            @endif
        </div>

        {{-- Live activity feed --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
            <h2 class="text-sm font-semibold text-gray-900 mb-3">Recent activity</h2>

            @if($recentActivity->isEmpty())
                <p class="text-xs text-gray-400 text-center py-6">No activity yet.</p>
            @else
                <ul class="space-y-3">
                    @foreach($recentActivity as $activity)
                        @php
                            $dotCls = match($activity['type']) {
                                'payment' => 'bg-green-500',
                                'late'    => 'bg-yellow-400',
                                'overdue' => 'bg-red-500',
                                default   => 'bg-gray-400',
                            };
                        @endphp
                        <li class="flex gap-2.5 items-start">
                            <span class="mt-1 w-2 h-2 rounded-full {{ $dotCls }} shrink-0"></span>
                            <div class="min-w-0">
                                <p class="text-xs text-gray-800 font-medium leading-snug">{{ $activity['title'] }}</p>
                                <p class="text-[11px] text-gray-400 mt-0.5">{{ $activity['detail'] }}</p>
                                <p class="text-[11px] text-gray-400">{{ $activity['time'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

</x-layouts.app>
