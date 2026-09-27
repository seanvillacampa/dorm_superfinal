<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $query = Room::with('activeContract.tenants.user')
            ->orderBy('floor')->orderBy('room_number');

        if ($request->filled('search')) {
            $query->where('room_number', 'like', '%'.$request->search.'%');
        }
        if ($request->filled('floor') && $request->floor !== 'all') {
            $query->where('floor', $request->floor);
        }
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $rooms  = $query->paginate(10)->withQueryString();
        $floors = Room::distinct()->orderBy('floor')->pluck('floor');

        return view('rooms.index', compact('rooms', 'floors'));
    }

    public function create()
    {
        return view('rooms.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'room_number'  => 'required|string|max:10|unique:rooms,room_number',
            'floor'        => 'required|integer|min:1|max:20',
            'capacity'     => 'required|integer|min:1|max:20',
            'monthly_rate' => 'required|numeric|min:0',
            'meter_number' => 'nullable|string|max:30',
            'status'       => 'required|in:vacant,occupied,maintenance',
        ]);

        $room = Room::create($data);

        AuditLog::record('Created', 'Room', $room->id, "Room {$room->room_number} added");

        return redirect()->route('rooms.index')
            ->with('success', "Room {$room->room_number} created.");
    }

    public function edit(Room $room)
    {
        return view('rooms.edit', compact('room'));
    }

    public function update(Request $request, Room $room)
    {
        $data = $request->validate([
            'floor'        => 'required|integer|min:1|max:20',
            'capacity'     => 'required|integer|min:1|max:20',
            'monthly_rate' => 'required|numeric|min:0',
            'meter_number' => 'nullable|string|max:30',
            'status'       => 'required|in:vacant,occupied,maintenance',
        ]);

        $old = $room->only(array_keys($data));
        $room->update($data);

        AuditLog::record('Updated', 'Room', $room->id,
            "Room {$room->room_number}: " . AuditLog::diff($old, $data));

        return redirect()->route('rooms.index')
            ->with('success', "Room {$room->room_number} updated.");
    }
}
