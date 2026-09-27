<?php

namespace App\Http\Controllers;

use App\Models\MeterReading;
use App\Models\Room;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MeterReadingController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));

        $rooms = Room::where('status', 'occupied')
            ->with(['activeContract'])
            ->orderBy('floor')->orderBy('room_number')
            ->get();

        // Load this month's readings keyed by room_id
        $readings = MeterReading::where('reading_month', $month)
            ->get()->keyBy('room_id');

        // Load previous month readings for "previous kWh" column
        $prevMonth = \Carbon\Carbon::createFromFormat('Y-m', $month)->subMonth()->format('Y-m');
        $prevReadings = MeterReading::where('reading_month', $prevMonth)
            ->get()->keyBy('room_id');

        $recorded = $readings->count();
        $total    = $rooms->count();

        return view('meter-readings.index', compact(
            'rooms', 'readings', 'prevReadings', 'month', 'recorded', 'total'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'month'    => 'required|date_format:Y-m',
            'readings' => 'required|array',
            'readings.*.room_id'      => 'required|exists:rooms,id',
            'readings.*.current_kwh'  => 'nullable|numeric|min:0',
        ]);

        $month = $request->month;
        $saved = 0;

        foreach ($request->readings as $row) {
            if (empty($row['current_kwh'])) continue;

            // Get previous reading for this room
            $prev = MeterReading::where('room_id', $row['room_id'])
                ->where('reading_month', '<', $month)
                ->orderByDesc('reading_month')
                ->value('current_kwh') ?? 0;

            MeterReading::updateOrCreate(
                ['room_id' => $row['room_id'], 'reading_month' => $month],
                [
                    'previous_kwh' => $prev,
                    'current_kwh'  => $row['current_kwh'],
                    'kwh_used'     => max(0, $row['current_kwh'] - $prev),
                    'recorded_by'  => Auth::id(),
                ]
            );

            AuditLog::record('Reading', 'MeterReading', $row['room_id'],
                "reading: {$row['current_kwh']} kWh for {$month}");
            $saved++;
        }

        return redirect()->route('meter-readings.index', ['month' => $month])
            ->with('success', "{$saved} reading(s) saved.");
    }
}
