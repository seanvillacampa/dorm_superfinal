<x-layouts.app title="Edit Room {{ $room->room_number }} — Dorm Manager">

    <x-page-header title="Edit room {{ $room->room_number }}" badge="Owner + Employee" />

    <div class="max-w-lg bg-white rounded-xl border border-gray-200 shadow-xs p-6">
        <form method="POST" action="{{ route('rooms.update', $room) }}" class="space-y-4">
            @csrf @method('PUT')

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Floor</label>
                    <input type="number" name="floor" value="{{ old('floor',$room->floor) }}" min="1"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required />
                    @error('floor') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Capacity (number of beds)</label>
                    <input type="number" name="capacity" value="{{ old('capacity', $room->capacity) }}" min="1" max="20"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Monthly rate (₱)</label>
                    <input type="number" name="monthly_rate" value="{{ old('monthly_rate',$room->monthly_rate) }}" step="0.01"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Meter number</label>
                    <input type="text" name="meter_number" value="{{ old('meter_number',$room->meter_number) }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="vacant"      @selected(old('status',$room->status)==='vacant')>Vacant</option>
                    <option value="occupied"    @selected(old('status',$room->status)==='occupied')>Occupied</option>
                    <option value="maintenance" @selected(old('status',$room->status)==='maintenance')>Maintenance</option>
                </select>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                    Save changes
                </button>
                <a href="{{ route('rooms.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
            </div>
        </form>
    </div>

</x-layouts.app>
