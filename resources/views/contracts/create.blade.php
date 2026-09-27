<x-layouts.app title="New Contract — Dorm Manager">

    <x-page-header title="New contract" badge="Owner + Employee"
        subtitle="Rent is calculated automatically from the room rate ÷ number of tenants." />

    <div class="max-w-xl bg-white rounded-xl border border-gray-200 shadow-xs p-6">
        <form method="POST" action="{{ route('contracts.store') }}" class="space-y-4"
              x-data="contractForm()" x-init="init()">
            @csrf

            @if($errors->any())
                <div class="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600">
                    @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
                </div>
            @endif

            {{-- Room select --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Room</label>
                <select name="room_id" x-model="roomId" @change="updateRent()"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <option value="">Select a vacant room…</option>
                    @foreach($rooms as $room)
                        <option value="{{ $room->id }}"
                                data-rate="{{ $room->monthly_rate }}"
                                data-capacity="{{ $room->capacity }}"
                                @selected(old('room_id')==$room->id)>
                            Room {{ $room->room_number }} · Floor {{ $room->floor }} · ₱{{ number_format($room->monthly_rate,2) }}/mo · {{ $room->capacity }} bed(s)
                        </option>
                    @endforeach
                </select>
                @error('room_id') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Tenants --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Tenants</label>
                <select name="tenant_ids[]" multiple x-model="selectedTenants" @change="updateRent()"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 h-28" required>
                    @foreach($tenants as $tenant)
                        <option value="{{ $tenant->id }}" @selected(in_array($tenant->id, old('tenant_ids',[])))>
                            {{ $tenant->user->name }} &lt;{{ $tenant->user->email }}&gt;
                        </option>
                    @endforeach
                </select>
                <p class="text-[11px] text-gray-400 mt-1">Hold Ctrl / Cmd to select multiple. Max = room capacity.</p>
                @error('tenant_ids') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Live rent preview --}}
            <div class="bg-blue-50 border border-blue-100 rounded-lg px-4 py-3 text-sm" x-show="roomRate > 0">
                <div class="flex items-center justify-between">
                    <span class="text-blue-700 font-medium">Rent per tenant</span>
                    <span class="text-blue-800 font-bold text-base" x-text="'₱' + rentPerTenant"></span>
                </div>
                <p class="text-xs text-blue-500 mt-0.5"
                   x-text="'Room rate ₱' + roomRate.toLocaleString('en-PH', {minimumFractionDigits:2}) + ' ÷ ' + tenantCount + ' tenant(s)'">
                </p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Start date</label>
                    <input type="date" name="start_date" value="{{ old('start_date') }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required />
                    @error('start_date') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">End date</label>
                    <input type="date" name="end_date" value="{{ old('end_date') }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required />
                    @error('end_date') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Due day (1–28)</label>
                    <input type="number" name="due_day" value="{{ old('due_day', 5) }}" min="1" max="28"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Deposit required (₱)</label>
                    <input type="number" name="deposit_required" value="{{ old('deposit_required', 0) }}" step="0.01" min="0"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required />
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                    Create contract
                </button>
                <a href="{{ route('contracts.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
            </div>
        </form>
    </div>

    <script>
    function contractForm() {
        return {
            roomId: '{{ old('room_id', '') }}',
            roomRate: 0,
            roomCapacity: 0,
            selectedTenants: @json(old('tenant_ids', [])),
            tenantCount: 0,
            rentPerTenant: '0.00',
            init() { this.updateRent(); },
            updateRent() {
                const sel = document.querySelector('select[name="room_id"]');
                const opt = sel?.options[sel?.selectedIndex];
                this.roomRate     = parseFloat(opt?.dataset?.rate ?? 0);
                this.roomCapacity = parseInt(opt?.dataset?.capacity ?? 0);
                this.tenantCount  = Math.max(1, this.selectedTenants.length);
                const per = this.roomRate / this.tenantCount;
                this.rentPerTenant = per.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
            }
        }
    }
    </script>

</x-layouts.app>
