<?php
// app/Http/Controllers/AvailabilityController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Mountain;
use App\Models\UserSelectedDatetime;
use App\Models\Booking;

class AvailabilityController extends Controller
{
    /**
     * Return available times (H:i) for a given mountain and date.
     * A time is "available" if there exists at least one ACTIVE instructor
     * who serves that mountain, has that date/time in user_selected_datetimes,
     * and is NOT already booked at that date/time (pending/confirmed).
     */
    public function timesByMountain(Request $request)
    {
        $request->validate([
            'mountain_id'   => ['required','integer','exists:mountains,id'],
            'selected_date' => ['required','date_format:Y-m-d'],
        ]);

        $mountainId = (int) $request->mountain_id;
        $date       = $request->selected_date;

        // 1) Get all ACTIVE instructors who serve this mountain
        $instructorIds = User::where('status', 'A')
            ->whereHas('mountains', fn($q) => $q->where('mountains.id', $mountainId))
            ->pluck('id')
            ->toArray();

        if (empty($instructorIds)) {
            return response()->json(['success' => true, 'available' => []]);
        }

        // 2) Base times (09:00–15:00)
        $baseTimes = [];
        for ($h = 9; $h <= 15; $h++) {
            $baseTimes[] = sprintf('%02d:00', $h);
        }

        // 3) All "advertised" availabilities for those instructors on that date (H:i => [instructor_ids...])
        $availRows = UserSelectedDatetime::whereIn('user_id', $instructorIds)
            ->where('selected_date', $date)
            ->get(['user_id', 'selected_time']);

        $timeToInstructors = [];
        foreach ($availRows as $row) {
            $t = substr($row->selected_time, 0, 5); // H:i
            if (!in_array($t, $baseTimes, true)) continue;
            $timeToInstructors[$t] = $timeToInstructors[$t] ?? [];
            $timeToInstructors[$t][] = $row->user_id;
        }

        if (empty($timeToInstructors)) {
            return response()->json(['success' => true, 'available' => []]);
        }

        // 4) Booked slots for those instructors at that date
$booked = Booking::whereIn('instructor_id', $instructorIds)
    ->where('selected_date', $date)
    ->whereIn('status', ['pending', 'confirmed', 'claimed']) // include claimed
    ->get(['instructor_id', 'selected_time']);

// Also treat is_reserved = true as "booked"
$reserved = UserSelectedDatetime::whereIn('user_id', $instructorIds)
    ->where('selected_date', $date)
    ->where('is_reserved', true)
    ->get(['user_id', 'selected_time']);

// Build a quick lookup: time => set of booked instructor_ids
$bookedAtTime = [];
foreach ($booked as $b) {
    $t = substr($b->selected_time, 0, 5);
    $bookedAtTime[$t] = $bookedAtTime[$t] ?? [];
    $bookedAtTime[$t][$b->instructor_id] = true;
}

// Merge in reserved flags (treat as booked too)
foreach ($reserved as $r) {
    $t = substr($r->selected_time, 0, 5);
    $bookedAtTime[$t] = $bookedAtTime[$t] ?? [];
    $bookedAtTime[$t][$r->user_id] = true;
}

// 5) A time is available if there exists at least one instructor in timeToInstructors[time]
// who is NOT in bookedAtTime[time]
$available = [];
foreach ($timeToInstructors as $time => $instructors) {
    $isFreeForSomeone = false;
    foreach ($instructors as $insId) {
        if (empty($bookedAtTime[$time][$insId])) {
            $isFreeForSomeone = true;
            break;
        }
    }
    if ($isFreeForSomeone) $available[] = $time;
}

sort($available);
return response()->json([
    'success'   => true,
    'available' => $available,
]);

    }
}
