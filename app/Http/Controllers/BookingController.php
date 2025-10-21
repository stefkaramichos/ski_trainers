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
        // 1) Validation (δεν ζητάμε instructor_id)
        $validated = $request->validate([
            'selected_date' => ['required','date'],            // Y-m-d
            'selected_time' => ['required','date_format:H:i'], // H:i
            'mountain_id'   => ['required','integer','exists:mountains,id'],
            'discipline'    => ['required','in:sk,sn'],        // <— Ski/Snowboard επιλογή
            'customer_name' => ['required','string','max:255'],
            'customer_email'=> ['required','email'],
            'customer_phone'=> ['nullable','string','max:50'],
            'people_count'  => ['nullable','integer','min:1'],
            'level'         => ['nullable','string','max:50'],
            'notes'         => ['nullable','string','max:2000'],
        ]);

        // 2) Re-check availability across instructors (με βάση το άθλημα)
        $isFree = $this->anyInstructorFreeAt(
            (int) $validated['mountain_id'],
            $validated['selected_date'],
            $validated['selected_time'],
            $validated['discipline']
        );

        if (!$isFree) {
            return back()
                ->withErrors(['selected_time' => 'Η επιλεγμένη ώρα δεν είναι πλέον διαθέσιμη.'])
                ->withInput();
        }

        // 3) Δημιουργία κράτησης (χωρίς instructor ακόμη)
        $booking = Booking::create([
            'instructor_id' => null,
            'mountain_id'   => $validated['mountain_id'],
            'selected_date' => $validated['selected_date'],
            'selected_time' => $validated['selected_time'] . ':00', // TIME column
            'discipline'    => $validated['discipline'],            // <— αποθήκευση επιλογής
            'customer_name' => $validated['customer_name'],
            'customer_email'=> $validated['customer_email'],
            'customer_phone'=> $validated['customer_phone'] ?? null,
            'people_count'  => $validated['people_count'] ?? 1,
            'level'         => $validated['level'] ?? null,
            'notes'         => $validated['notes'] ?? null,
            'status'        => 'pending',
        ]);

        $booking->load('mountain');

        // 3.1) Email επιβεβαίωσης στον πελάτη (native mail())
        try {
            $subject = 'Επιβεβαίωση αίτησης κράτησης';
            $body  = "Γεια σας {$booking->customer_name},\n\n";
            $body .= "Το αίτημά σας καταχωρήθηκε. Σύντομα ένας εκπαιδευτής θα επικοινωνήσει μαζί σας.\n\n";
            $body .= "Στοιχεία κράτησης:\n";
            $body .= "- Ημερομηνία: {$booking->selected_date}\n";
            $body .= "- Ώρα: {$validated['selected_time']}\n";
            $mountainName = $booking->mountain?->mountain_name ?? '—';
            $body .= "- Χιονοδρομικό: {$mountainName}\n";
            $body .= "- Άθλημα: " . ($validated['discipline'] === 'sk' ? 'Ski' : 'Snowboard') . "\n";
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
            \Log::error('Customer confirmation mail failed', [
                'booking_id' => $booking->id,
                'error'      => $e->getMessage(),
            ]);
        }

        // 4) Email σε όλους τους διαθέσιμους instructors του slot (με φίλτρο discipline)
        $this->emailClaimLinksToInstructors(
            booking: $booking,
            mountainId: (int) $validated['mountain_id'],
            date: $validated['selected_date'],
            time: $validated['selected_time'],
            discipline: $validated['discipline']
        );

        return back()->with('success', 'Το αίτημά σας καταχωρήθηκε. Σύντομα ένας εκπαιδευτής θα επικοινωνήσει μαζί σας.');
    }

    /**
     * Claim endpoint: ο πρώτος instructor που πατάει αναλαμβάνει.
     * GET /booking/{booking}/claim?token=TOKEN
     */
    public function claim(Request $request, Booking $booking)
    {
        // Πρέπει να είναι logged-in (middleware)
        $instructorId = auth()->id();
        $token        = (string) $request->query('token');

        if (!$token) {
            return view('booking-claim-result', [
                'ok'      => false,
                'message' => 'Λάθος σύνδεσμος.',
            ]);
        }

        // Έλεγχος claim record
        $claim = BookingClaim::where('booking_id', $booking->id)
            ->where('instructor_id', $instructorId)
            ->where('token', $token)
            ->whereNull('claimed_at')
            ->whereNull('invalidated_at')
            ->first();

        if (!$claim) {
            return view('booking-claim-result', [
                'ok'      => false,
                'message' => 'Δεν βρέθηκε έγκυρη πρόσκληση για εσάς ή έχει λήξει.',
            ]);
        }

        try {
            DB::transaction(function () use ($booking, $claim, $instructorId) {
                // Κλείδωμα γραμμής κράτησης
                $b = Booking::where('id', $booking->id)->lockForUpdate()->first();

                if ($b->instructor_id) {
                    throw new \RuntimeException('Η κράτηση έχει ήδη ανατεθεί.');
                }

                $b->instructor_id = $instructorId;
                $b->status = 'claimed';
                $b->save();

                // Μαρκάρουμε το slot του instructor ως reserved
                $timeHi = substr($b->selected_time, 0, 5); // "H:i"
                UserSelectedDatetime::where('user_id', $instructorId)
                    ->where('selected_date', $b->selected_date)
                    ->where('selected_time', $timeHi . ':00')
                    ->update(['is_reserved' => true]);

                // Ακυρώνουμε τα υπόλοιπα claims
                BookingClaim::where('booking_id', $b->id)
                    ->where('id', '!=', $claim->id)
                    ->update(['invalidated_at' => now()]);
            });
        } catch (\RuntimeException $e) {
            return view('booking-claim-result', [
                'ok'      => false,
                'message' => $e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            return view('booking-claim-result', [
                'ok'      => false,
                'message' => 'Σφάλμα κατά την ανάθεση.',
            ]);
        }

        return view('booking-claim-result', [
            'ok'      => true,
            'message' => 'Η κράτηση ανατέθηκε σε εσάς με επιτυχία!',
            'booking' => $booking->fresh(),
        ]);
    }

    /**
     * Στέλνει claim links σε όλους τους κατάλληλους instructors
     *  - ενεργούς
     *  - που εξυπηρετούν το βουνό
     *  - με σωστό discipline (sk/sn)
     *  - που έχουν δημοσιεύσει το συγκεκριμένο slot
     *  - και δεν είναι ήδη κλεισμένοι στο slot
     */
    private function emailClaimLinksToInstructors(
        Booking $booking,
        int $mountainId,
        string $date,
        string $time,
        string $discipline
    ): void
    {
        // Instructors που ταιριάζουν
        $instructorIds = User::where('status', 'A')
            ->where('description', $discipline) // <— φίλτρο Ski/Snowboard
            ->whereHas('mountains', fn($q) => $q->where('mountains.id', $mountainId))
            ->pluck('id')
            ->all();

        if (!$instructorIds) return;

        // Όσοι έχουν δημοσιεύσει αυτό το slot
        $publishers = UserSelectedDatetime::whereIn('user_id', $instructorIds)
            ->where('selected_date', $date)
            ->where('selected_time', $time . ':00')
            ->pluck('user_id')
            ->all();

        if (!$publishers) return;

        // Όσοι είναι ήδη κλεισμένοι
        $booked = Booking::whereIn('instructor_id', $publishers)
            ->where('selected_date', $date)
            ->where('selected_time', $time . ':00')
            ->whereIn('status', ['pending','confirmed','claimed'])
            ->pluck('instructor_id')
            ->all();

        $eligible = array_values(array_diff($publishers, $booked));
        if (!$eligible) return;

        // Δημιουργία claims + αποστολή emails
        foreach ($eligible as $insId) {
            $token = Str::random(40);

            BookingClaim::create([
                'booking_id'    => $booking->id,
                'instructor_id' => $insId,
                'token'         => $token,
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
            $body .= "Άθλημα: " . ($discipline === 'sk' ? 'Ski' : 'Snowboard') . "\n";
            $body .= "Χιονοδρομικό ID: {$mountainId}\n\n";
            $body .= "Αν μπορείτε να αναλάβετε, πατήστε τον παρακάτω σύνδεσμο (ο πρώτος που θα πατήσει κερδίζει την κράτηση):\n";
            $body .= "{$link}\n\n";
            $body .= "— Σύστημα κρατήσεων\n";

            // Basic headers
            $headers  = "From: Κρατήσεις <no-reply@yourdomain.tld>\r\n";
            $headers .= "Reply-To: no-reply@yourdomain.tld\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();

            @mail($ins->email, $subject, $body, $headers);
        }
    }

    /**
     * TRUE αν υπάρχει έστω ένας κατάλληλος (με σωστό discipline) instructor ελεύθερος στο slot
     */
    private function anyInstructorFreeAt(int $mountainId, string $date, string $time, string $discipline): bool
    {
        $instructorIds = User::where('status', 'A')
            ->where('description', $discipline) // <— Ski/Snowboard filter
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
            if (!isset($bookedSet[$insId])) {
                return true; // βρέθηκε κάποιος ελεύθερος
            }
        }

        return false;
    }
}
