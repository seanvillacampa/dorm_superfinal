<x-layouts.app title="Add Room — Dorm Manager">

    <x-page-header title="Add room" badge="Owner + Employee" subtitle="Create a new room in the dormitory" />

    <div class="max-w-lg bg-white rounded-xl border border-gray-200 shadow-xs p-6">
        <form method="POST" action="{{ route('rooms.store') }}" class="space-y-4">
            @csrf

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Room number</label>
                    <input type="text" name="room_number" value="{{ old('room_number') }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="e.g. 101" required />
                    @error('room_number') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Floor</label>
                    <input type="number" name="floor" value="{{ old('floor',1) }}" min="1"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required />
                    @error('floor') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Capacity (number of beds)</label>
                <input type="number" name="capacity" value="{{ old('capacity', 1) }}" min="1" max="20"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="e.g. 4" required />
                @error('capacity') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Monthly rate (₱)</label>
                    <input type="number" name="monthly_rate" value="{{ old('monthly_rate') }}" step="0.01" min="0"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required />
                    @error('monthly_rate') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Meter number</label>
                    <input type="text" name="meter_number" value="{{ old('meter_number') }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="e.g. MTR-1001" />
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="vacant"      @selected(old('status','vacant')==='vacant')>Vacant</option>
                    <option value="occupied"    @selected(old('status')==='occupied')>Occupied</option>
                    <option value="maintenance" @selected(old('status')==='maintenance')>Maintenance</option>
                </select>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                    Create room
                </button>
                <a href="{{ route('rooms.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
            </div>
        </form>
    </div>

</x-layouts.app>
