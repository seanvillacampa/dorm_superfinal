<x-layouts.tenant title="Contract — Dorm Manager">

    <h1 class="text-2xl font-bold text-gray-900 mb-1">Contract</h1>
    <p class="text-sm text-gray-500 mb-6">Your signed contract letter</p>

    @if($contract)
        <div class="grid grid-cols-[280px_1fr] gap-6 items-start">
            {{-- Details --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-5">
                <h2 class="text-sm font-semibold text-gray-900 mb-3">Contract details</h2>
                <dl class="space-y-2 text-sm">
                    <dt class="text-xs text-gray-400">Contract no.</dt>
                    <dd class="text-gray-900 font-medium">CT-{{ str_pad($contract->id,4,'0',STR_PAD_LEFT) }}</dd>
                    <dt class="text-xs text-gray-400 mt-2">Status</dt>
                    <dd><x-status-badge :status="$contract->status" /></dd>
                    <dt class="text-xs text-gray-400 mt-2">Room</dt>
                    <dd class="text-gray-900">{{ $contract->room->room_number }} · Floor {{ $contract->room->floor }}</dd>
                    <dt class="text-xs text-gray-400 mt-2">Start date</dt>
                    <dd class="text-gray-900">{{ $contract->start_date->format('M d, Y') }}</dd>
                    <dt class="text-xs text-gray-400 mt-2">End date</dt>
                    <dd class="text-gray-900">{{ $contract->end_date->format('M d, Y') }}</dd>
                    <dt class="text-xs text-gray-400 mt-2">Monthly rent</dt>
                    <dd class="text-gray-900">₱{{ number_format($contract->monthly_rent,2) }}</dd>
                    <dt class="text-xs text-gray-400 mt-2">Due day</dt>
                    <dd class="text-gray-900">{{ $contract->due_day }}{{ match($contract->due_day%10){1=>'st',2=>'nd',3=>'rd',default=>'th'} }} of each month</dd>
                    <dt class="text-xs text-gray-400 mt-2">Deposit</dt>
                    <dd class="text-gray-900">₱{{ number_format($contract->deposit_required,2) }}</dd>
                </dl>
                <button class="mt-4 w-full py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    Download PDF
                </button>
            </div>

            {{-- Contract preview --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-8 min-h-64">
                <div class="max-w-sm mx-auto">
                    <h3 class="text-center font-bold text-gray-900 text-sm mb-1">CONTRACT OF LEASE</h3>
                    <p class="text-center text-xs text-gray-400 mb-6">Dorm Manager · Room {{ $contract->room->room_number }}</p>
                    <div class="space-y-3">
                        @for($i=0;$i<10;$i++)
                            <div class="h-2.5 bg-gray-100 rounded-full w-full"></div>
                        @endfor
                        <div class="h-2.5 bg-gray-100 rounded-full w-3/4"></div>
                    </div>
                    <div class="flex justify-between mt-12 pt-4 border-t border-gray-200 text-xs text-gray-400">
                        <span>Tenant signature</span>
                        <span>Owner signature</span>
                    </div>
                </div>
            </div>
        </div>
    @else
        <p class="text-sm text-gray-400">No active contract found.</p>
    @endif

</x-layouts.tenant>
