<?php
// app/Http/Controllers/BookingController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Booking;
use App\Models\UserSelectedDatetime;
use App\Models\BookingClaim;

class BookingController extends Controller
{
    public function store(Request $request)
    {
        // 1) Validation: no instructor_id required
        $validated = $request->validate([
            'selected_date' => ['required','date'],            // Y-m-d
            'selected_time' => ['required','date_format:H:i'], // H:i
            'mountain_id'   => ['required','integer','exists:mountains,id'],
            'customer_name' => ['required','string','max:255'],
            'customer_email'=> ['required','email'],
            'customer_phone'=> ['nullable','string','max:50'],
            'people_count'  => ['nullable','integer','min:1'],
            'level'         => ['nullable','string','max:50'],
            'notes'         => ['nullable','string','max:2000'],
        ]);

        // 2) Re-check availability across instructors
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

        // 3) Create the booking (no instructor yet)
        $booking = Booking::create([
            'instructor_id' => null, // still empty
            'mountain_id'   => $validated['mountain_id'],
            'selected_date' => $validated['selected_date'],
            'selected_time' => $validated['selected_time'] . ':00', // TIME column
            'customer_name' => $validated['customer_name'],
            'customer_email'=> $validated['customer_email'],
            'customer_phone'=> $validated['customer_phone'] ?? null,
            'people_count'  => $validated['people_count'] ?? 1,
            'level'         => $validated['level'] ?? null,
            'notes'         => $validated['notes'] ?? null,
            'status'        => 'pending',
        ]);

        $booking->load('mountain');

        // 3.1) Email confirmation to the customer (native mail())
        //     Κρατάμε το ίδιο στυλ με τα emails προς instructors.
        try {
            $subject = 'Επιβεβαίωση αίτησης κράτησης';
            $body  = "Γεια σας {$booking->customer_name},\n\n";
            $body .= "Το αίτημά σας καταχωρήθηκε. Σύντομα ένας εκπαιδευτής θα επικοινωνήσει μαζί σας.\n\n";
            $body .= "Στοιχεία κράτησης:\n";
            $body .= "- Ημερομηνία: {$booking->selected_date}\n";
            $body .= "- Ώρα: {$validated['selected_time']}\n";
            $mountainName = $booking->mountain?->mountain_name ?? '—';
            $body .= "- Χιονοδρομικό: {$mountainName}\n";
            $body .= "- Άτομα: {$booking->people_count}\n";
            if (!empty($booking->notes)) {
                $body .= "- Σημειώσεις: {$booking->notes}\n";
            }
            $body .= "\n— Σύστημα κρατήσεων\n";

            // Basic headers (προσαρμόστε σε δικό σας domain)
            $headers  = "From: Κρατήσεις <no-reply@yourdomain.tld>\r\n";
            $headers .= "Reply-To: no-reply@yourdomain.tld\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();

            @mail($booking->customer_email, $subject, $body, $headers);
        } catch (\Throwable $e) {
            // Προαιρετικό logging — δεν μπλοκάρουμε την ροή
            \Log::error('Customer confirmation mail failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }

        // 4) Email all available instructors for this slot with claim links
        $this->emailClaimLinksToInstructors(
            booking: $booking,
            mountainId: (int) $validated['mountain_id'],
            date: $validated['selected_date'],
            time: $validated['selected_time']
        );

        // Μήνυμα οθόνης όπως το ζήτησες
        return back()->with('success', 'Το αίτημά σας καταχωρήθηκε. Σύντομα ένας εκπαιδευτής θα επικοινωνήσει μαζί σας.');
    }

    /**
     * Claim endpoint: first instructor who clicks gets assigned.
     * GET /booking/{booking}/claim?instructor=ID&token=TOKEN
     */
    public function claim(Request $request, Booking $booking)
    {
        // User must be logged in (middleware ensures it)
        $instructorId = auth()->id();
        $token        = (string) $request->query('token');

        if (!$token) {
            return view('booking-claim-result', [
                'ok' => false,
                'message' => 'Λάθος σύνδεσμος.',
            ]);
        }

        // Check claim record
        $claim = \App\Models\BookingClaim::where('booking_id', $booking->id)
            ->where('instructor_id', $instructorId)
            ->where('token', $token)
            ->whereNull('claimed_at')
            ->whereNull('invalidated_at')
            ->first();

        if (!$claim) {
            return view('booking-claim-result', [
                'ok' => false,
                'message' => 'Δεν βρέθηκε έγκυρη πρόσκληση για εσάς ή έχει λήξει.',
            ]);
        }

        try {
            \DB::transaction(function () use ($booking, $claim, $instructorId) {
                // app/Http/Controllers/BookingController.php (μέσα στο transaction του claim)
                $b = \App\Models\Booking::where('id', $booking->id)->lockForUpdate()->first();

                if ($b->instructor_id) {
                    throw new \RuntimeException('Η κράτηση έχει ήδη ανατεθεί.');
                }

                $b->instructor_id = $instructorId;
                $b->status = 'claimed';
                $b->save();

                // ✅ μαρκάρουμε το slot του συγκεκριμένου instructor ως reserved
                $timeHi = substr($b->selected_time, 0, 5); // "H:i"
                \App\Models\UserSelectedDatetime::where('user_id', $instructorId)
                    ->where('selected_date', $b->selected_date)
                    ->where('selected_time', $timeHi . ':00')
                    ->update(['is_reserved' => true]);

                // ακυρώνουμε τα υπόλοιπα claims
                \App\Models\BookingClaim::where('booking_id', $b->id)
                    ->where('id', '!=', $claim->id)
                    ->update(['invalidated_at' => now()]);

            });
        } catch (\RuntimeException $e) {
            return view('booking-claim-result', [
                'ok' => false,
                'message' => $e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            return view('booking-claim-result', [
                'ok' => false,
                'message' => 'Σφάλμα κατά την ανάθεση.',
            ]);
        }

        return view('booking-claim-result', [
            'ok' => true,
            'message' => 'Η κράτηση ανατέθηκε σε εσάς με επιτυχία!',
            'booking' => $booking->fresh(),
        ]);
    }

    /**
     * Email claim links to all instructors who:
     *  - are active
     *  - serve this mountain
     *  - have published this exact slot
     *  - are not already booked at that slot
     */
    private function emailClaimLinksToInstructors(Booking $booking, int $mountainId, string $date, string $time): void
    {
        // Find eligible instructors
        $instructorIds = User::where('status', 'A')
            ->whereHas('mountains', fn($q) => $q->where('mountains.id', $mountainId))
            ->pluck('id')
            ->all();

        if (!$instructorIds) return;

        $publishers = UserSelectedDatetime::whereIn('user_id', $instructorIds)
            ->where('selected_date', $date)
            ->where('selected_time', $time . ':00')
            ->pluck('user_id')
            ->all();

        if (!$publishers) return;

        $booked = \App\Models\Booking::whereIn('instructor_id', $publishers)
            ->where('selected_date', $date)
            ->where('selected_time', $time . ':00')
            ->whereIn('status', ['pending','confirmed','claimed'])
            ->pluck('instructor_id')
            ->all();

        $bookedSet = array_flip($booked);
        $eligible = array_values(array_filter($publishers, fn($id) => !isset($bookedSet[$id])));

        if (!$eligible) return;

        // Create claims + send emails (native mail())
        foreach ($eligible as $insId) {
            $token = Str::random(40);

            BookingClaim::create([
                'booking_id'   => $booking->id,
                'instructor_id'=> $insId,
                'token'        => $token,
            ]);

            $link = route('booking.claim', [
                'booking' => $booking->id,
                'token'   => $token,
            ]);

            $ins = User::find($insId);
            if (!$ins || !$ins->email) continue;

            $subject = "Νέο αίτημα μαθήματος – {$date} {$time}";
            $body  = "Γεια σας {$ins->name},\n\n";
            $body .= "Υπάρχει νέο αίτημα μαθήματος:\n";
            $body .= "Ημερομηνία: {$date}\n";
            $body .= "Ώρα: {$time}\n";
            $body .= "Χιονοδρομικό ID: {$mountainId}\n\n";
            $body .= "Αν μπορείτε να αναλάβετε, πατήστε τον παρακάτω σύνδεσμο (ο πρώτος που θα πατήσει κερδίζει την κράτηση):\n";
            $body .= "{$link}\n\n";
            $body .= "— Σύστημα κρατήσεων\n";

            // Basic headers (adjust the From to your domain)
            $headers  = "From: Κρατήσεις <no-reply@yourdomain.tld>\r\n";
            $headers .= "Reply-To: no-reply@yourdomain.tld\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();

            // Send with native mail()
            @mail($ins->email, $subject, $body, $headers);
        }
    }

    /**
     * TRUE if at least one eligible instructor is free at that slot
     */
    private function anyInstructorFreeAt(int $mountainId, string $date, string $time): bool
    {
        $instructorIds = User::where('status', 'A')
            ->whereHas('mountains', fn($q) => $q->where('mountains.id', $mountainId))
            ->pluck('id')
            ->all();

        if (!$instructorIds) return false;

        $publishers = UserSelectedDatetime::whereIn('user_id', $instructorIds)
            ->where('selected_date', $date)
            ->where('selected_time', $time . ':00')
            ->pluck('user_id')
            ->all();

        if (!$publishers) return false;

        $booked = Booking::whereIn('instructor_id', $publishers)
            ->where('selected_date', $date)
            ->where('selected_time', $time . ':00')
            ->whereIn('status', ['pending','confirmed','claimed'])
            ->pluck('instructor_id')
            ->all();

        $bookedSet = array_flip($booked);
        foreach ($publishers as $insId) {
            if (!isset($bookedSet[$insId])) return true;
        }
        return false;
    }
}
