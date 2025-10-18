<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\User;
use App\Models\Mountain;
use App\Models\UserSelectedDatetime; // <-- add
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;   // <-- add
use Illuminate\Validation\Rule;


class AdminBookingsController extends Controller
{
    public function index(Request $request)
    {
        $status       = $request->get('status');           // pending | claimed
        $mountainId   = $request->get('mountain_id');
        $instructorId = $request->get('instructor_id');
        $dateFrom     = $request->get('date_from');
        $dateTo       = $request->get('date_to');
        $q            = trim((string)$request->get('q', ''));

        $bookings = Booking::query()
            ->with(['instructor:id,name', 'mountain:id,mountain_name'])
            ->when($status, fn($q2) => $q2->where('status', $status))
            ->when($mountainId, fn($q2) => $q2->where('mountain_id', $mountainId))
            ->when($instructorId, fn($q2) => $q2->where('instructor_id', $instructorId))
            ->when($dateFrom, fn($q2) => $q2->whereDate('selected_date', '>=', $dateFrom))
            ->when($dateTo, fn($q2) => $q2->whereDate('selected_date', '<=', $dateTo))
            ->when($q, function($q2) use ($q) {
                $q2->where(function($qq) use ($q) {
                    $qq->where('customer_name', 'like', "%{$q}%")
                       ->orWhere('customer_email', 'like', "%{$q}%")
                       ->orWhere('customer_phone', 'like', "%{$q}%")
                       ->orWhere('notes', 'like', "%{$q}%");
                });
            })
            ->orderBy('selected_date')
            ->orderBy('selected_time')
            ->paginate(20)
            ->withQueryString();

        $instructors = User::where('status', 'A')->orderBy('name')->get(['id','name']);
        $mountains   = Mountain::orderBy('mountain_name')->get(['id','mountain_name']);

        return view('admin.admin_bookings', compact(
            'bookings','instructors','mountains',
            'status','mountainId','instructorId','dateFrom','dateTo','q'
        ));
    }

    public function updateStatus(Request $request)
    {
        $data = $request->validate([
            'booking_id' => ['required','exists:bookings,id'],
            'status'     => ['required', Rule::in(['pending','claimed'])],
        ]);

        $booking = Booking::findOrFail($data['booking_id']);
        $booking->status = $data['status'];
        $booking->save();

        return response()->json(['success' => true, 'status' => $booking->status]);
    }

    public function assignInstructor(Request $request)
    {
        $data = $request->validate([
            'booking_id'    => ['required','exists:bookings,id'],
            'instructor_id' => ['nullable','exists:users,id'],
        ]);

        return DB::transaction(function () use ($data) {
            $booking = Booking::lockForUpdate()->findOrFail($data['booking_id']);

            $oldInstructorId = $booking->instructor_id;
            $newInstructorId = $data['instructor_id'] ?? null;

            // Αν δεν αλλάζει κάτι, βγες
            if ((int)$oldInstructorId === (int)$newInstructorId) {
                return response()->json([
                    'success'         => true,
                    'instructor_name' => optional($booking->instructor)->name,
                    'status'          => $booking->status,
                ]);
            }

            // Έλεγχος conflict στο unique slot (όπως πριν)
            if (!empty($newInstructorId)) {
                $exists = Booking::where('id', '!=', $booking->id)
                    ->where('instructor_id', $newInstructorId)
                    ->whereDate('selected_date', $booking->selected_date)
                    ->whereTime('selected_time', $booking->selected_time)
                    ->exists();

                if ($exists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Ο εκπαιδευτής έχει ήδη κράτηση στο ίδιο slot.',
                    ], 422);
                }
            }

            // 1) Unreserve από τον παλιό instructor (αν υπήρχε)
            if (!empty($oldInstructorId)) {
                UserSelectedDatetime::where('user_id', $oldInstructorId)
                    ->whereDate('selected_date', $booking->selected_date)
                    ->whereTime('selected_time', $booking->selected_time)
                    ->update(['is_reserved' => 0]);
                // Αν δεν υπάρχει καν εγγραφή, δεν κάνουμε τίποτα (προαιρετικά θα μπορούσες να δημιουργήσεις με is_reserved=0)
            }

            // 2) Reserve στον νέο instructor (αν δίνεται)
            if (!empty($newInstructorId)) {
                UserSelectedDatetime::updateOrCreate(
                    [
                        'user_id'       => $newInstructorId,
                        'selected_date' => $booking->selected_date,
                        'selected_time' => $booking->selected_time,
                    ],
                    [
                        'is_reserved'   => 1,
                    ]
                );
            }

            // 3) Ενημέρωση ραντεβού
            $booking->instructor_id = $newInstructorId;

            // Auto-sync status (όπως πριν)
            if ($newInstructorId && $booking->status === 'pending') {
                $booking->status = 'claimed';
            } elseif (!$newInstructorId && $booking->status === 'claimed') {
                $booking->status = 'pending';
            }
            $booking->save();

            return response()->json([
                'success'         => true,
                'instructor_name' => optional($booking->instructor)->name,
                'status'          => $booking->status,
            ]);
        });
    }

    public function delete(Request $request)
    {
        $request->validate([
            'booking_id' => ['required','exists:bookings,id'],
        ]);

        return DB::transaction(function () use ($request) {
            $booking = \App\Models\Booking::lockForUpdate()->findOrFail($request->booking_id);

            // Αν το ραντεβού έχει instructor, κάνε unreserve το slot
            if ($booking->instructor_id) {
                UserSelectedDatetime::where('user_id', $booking->instructor_id)
                    ->whereDate('selected_date', $booking->selected_date)
                    ->whereTime('selected_time', $booking->selected_time)
                    ->update(['is_reserved' => 0]);
            }

            // Διαγραφή ραντεβού
            $booking->delete();

            return response()->json([
                'success' => true,
                'message' => 'Η κράτηση διαγράφηκε επιτυχώς και το slot ελευθερώθηκε.',
            ]);
        });
    }

}
