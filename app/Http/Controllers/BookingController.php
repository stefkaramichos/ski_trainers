<?php
// app/Http/Controllers/BookingController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Booking;
use App\Models\UserSelectedDatetime;

class BookingController extends Controller
{
    public function store(Request $request)
    {
        // 1) Validation: no instructor_id required
        $validated = $request->validate([
            'selected_date' => ['required','date'],        // Y-m-d
            'selected_time' => ['required','date_format:H:i'],
            'mountain_id'   => ['required','integer','exists:mountains,id'],
            'customer_name' => ['required','string','max:255'],
            'customer_email'=> ['required','email'],
            'customer_phone'=> ['nullable','string','max:50'],
            'people_count'  => ['nullable','integer','min:1'],
            'level'         => ['nullable','string','max:50'],
            'notes'         => ['nullable','string','max:2000'],
        ]);

        // 2) Re-check availability ACROSS instructors for that mountain/date/time
        $isFree = $this->anyInstructorFreeAt(
            (int) $validated['mountain_id'],
            $validated['selected_date'],
            $validated['selected_time']
        );

        if (!$isFree) {
            return back()
                ->withErrors(['selected_time' => 'Η επιλεγμένη ώρα δεν είναι πλέον διαθέσιμη.'])
                ->withInput();
        }

        // 3) Save booking WITHOUT instructor for now
        Booking::create([
            'instructor_id' => null, // <- leave empty for now
            'mountain_id'   => $validated['mountain_id'],
            'selected_date' => $validated['selected_date'],
            'selected_time' => $validated['selected_time'] . ':00', // if your column is TIME
            'customer_name' => $validated['customer_name'],
            'customer_email'=> $validated['customer_email'],
            'customer_phone'=> $validated['customer_phone'] ?? null,
            'people_count'  => $validated['people_count'] ?? 1,
            'level'         => $validated['level'] ?? null,
            'notes'         => $validated['notes'] ?? null,
            'status'        => 'pending',
        ]);

        return back()->with('success', 'Η κράτησή σας αποθηκεύτηκε με επιτυχία!');
    }

    /**
     * Return TRUE if there exists at least one ACTIVE instructor
     * who serves this mountain, has published availability for
     * {date,time}, and is not already booked.
     */
    private function anyInstructorFreeAt(int $mountainId, string $date, string $time): bool
    {
        // 1) All active instructors at mountain
        $instructorIds = User::where('status', 'A')
            ->whereHas('mountains', fn($q) => $q->where('mountains.id', $mountainId))
            ->pluck('id')
            ->all();

        if (empty($instructorIds)) {
            return false;
        }

        // 2) Which instructors advertise this exact slot (date,time)
        $publishers = UserSelectedDatetime::whereIn('user_id', $instructorIds)
            ->where('selected_date', $date)
            ->where('selected_time', $time . ':00') // stored as TIME
            ->pluck('user_id')
            ->all();

        if (empty($publishers)) {
            return false;
        }

        // 3) Remove those already booked (pending/confirmed)
        $booked = Booking::whereIn('instructor_id', $publishers)
            ->where('selected_date', $date)
            ->where('selected_time', $time . ':00')
            ->whereIn('status', ['pending','confirmed'])
            ->pluck('instructor_id')
            ->all();

        // If there is at least one publisher not in booked, it’s free
        $bookedSet = array_flip($booked);
        foreach ($publishers as $insId) {
            if (!isset($bookedSet[$insId])) {
                return true;
            }
        }
        return false;
    }
}
