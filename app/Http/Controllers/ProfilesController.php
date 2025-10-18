<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session; 
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Mountain;
use App\Models\UserSelectedDatetime;
use Carbon\Carbon;
use App\Models\Booking;


class ProfilesController extends Controller
{
    public function __construct()
    {
        // $this->middleware('auth');
        Carbon::setLocale('el');
    }

    public function profile_view(User $user)
    {
        $accessLevel = $this->checkUserAccess($user);

        return view('profile-guest-view', [
            'user' => $user,
            'accessLevel' => $accessLevel
        ]);
    }

    public function deleteSession(Request $request)
    {
        $index = (int) $request->input('delete_index');
        $selected = Session::get('selected_datetimes', ['dates' => [], 'times' => []]);

        $removed = false;
        if (isset($selected['dates'][$index])) {
            array_splice($selected['dates'], $index, 1);
            array_splice($selected['times'], $index, 1);
            Session::put('selected_datetimes', $selected);
            $removed = true;
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => $removed,
                'message' => $removed ? 'Removed.' : 'Not found.',
                'index'   => $index,
                // echo back for client convenience
                'date'    => $request->input('date'),
                'time'    => $request->input('time'),
            ]);
        }

        return back();
    }


    // ProfilesController.php
    public function deleteSaved(User $user, Request $request)
    {
        $id = (int) $request->input('id');

        // ✅ check against the URL user, not Auth::id()
        $row = UserSelectedDatetime::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        $deleted = false;
        $date = null;
        $time = null;

        if ($row) {
            $date = $row->selected_date;                  // Y-m-d
            $time = substr($row->selected_time, 0, 5);    // HH:MM
            $row->delete();
            $deleted = true;
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => $deleted,
                'id'      => $id,
                'date'    => $date,
                'time'    => $time,
                'message' => $deleted ? 'Deleted' : 'Not found',
            ]);
        }

        return back();
    }


   public function profile_date(User $user, Request $request)
{
    $accessLevel = $this->checkUserAccess($user);
    $mountains = Mountain::all(); 
    $userMountains = $user->mountains()->pluck('mountains.id')->toArray();

    if ($accessLevel == 'A') {

        // Handle date/time clicks (unchanged) ...
        if ($request->isMethod('post')) {
            $selectedDate = $request->input('selected_date'); // expect Y-m-d
            $selectedTime = $request->input('selected_time'); // expect H:i
            if ($selectedDate) {
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
                    $ts = strtotime(str_replace('/', '-', $selectedDate));
                    if ($ts) $selectedDate = date('Y-m-d', $ts);
                }
                Session::put('last_selected_date', $selectedDate);
            }
            if ($selectedDate && $selectedTime) {
                if (!Session::has('selected_datetimes')) {
                    Session::put('selected_datetimes', ['dates' => [], 'times' => []]);
                }
                $selectedDatetimes = Session::get('selected_datetimes');
                $existingPairs = [];
                foreach ($selectedDatetimes['dates'] as $i => $d) {
                    $dNorm = preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)
                        ? $d
                        : date('Y-m-d', strtotime(str_replace('/', '-', $d)));
                    $existingPairs[] = $dNorm . ' ' . ($selectedDatetimes['times'][$i] ?? '');
                }
                $pair = $selectedDate . ' ' . $selectedTime;
                if (!in_array($pair, $existingPairs, true)) {
                    Session::push('selected_datetimes.dates', $selectedDate);
                    Session::push('selected_datetimes.times', $selectedTime);
                }
            }
        }

        // Already saved (DB)
        $savedDatetimes = UserSelectedDatetime::where('user_id', $user->id)
            ->orderBy('selected_date')
            ->orderBy('selected_time')
            ->get();

        // Session picks
        $selectedDatetimes = Session::get('selected_datetimes', ['dates' => [], 'times' => []]);

        // Merge Y-m-d dates for calendar highlighting
        $selectedDatesForCalendar = collect($savedDatetimes)->pluck('selected_date')
            ->merge($selectedDatetimes['dates'])
            ->map(function ($d) {
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) return $d;
                $ts = strtotime(str_replace('/', '-', $d));
                return $ts ? date('Y-m-d', $ts) : $d;
            })
            ->unique()
            ->values()
            ->toArray();

        // ------------------------------
        // ✅ Determine the "current" selected date FIRST (from booking link or session)
        // ------------------------------
        $currentSelectedDate = $request->query('selected_date', request('selected_date')) ?: Session::get('last_selected_date', '');

        if (empty($currentSelectedDate)) {
            $currentSelectedDate = date('Y-m-d');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $currentSelectedDate)) {
            $ts = strtotime(str_replace('/', '-', $currentSelectedDate));
            $currentSelectedDate = $ts ? date('Y-m-d', $ts) : date('Y-m-d');
        }

        // Keep last clicked/visited date in session (helps highlighting)
        Session::put('last_selected_date', $currentSelectedDate);

        // Make sure helpers that read request('selected_date') see this value
        $request->merge(['selected_date' => $currentSelectedDate]); // ← important

        // ------------------------------
        // ✅ Month & year default to the selected date’s month/year
        // ------------------------------
        $baseTs = strtotime($currentSelectedDate);
        $defaultMonth = (int) date('n', $baseTs);
        $defaultYear  = (int) date('Y', $baseTs);

        $month = (int) $request->query('month', $defaultMonth);
        $year  = (int) $request->query('year',  $defaultYear);

        if ($request->query('action') === 'prev') {
            $month--; if ($month < 1) { $month = 12; $year--; }
        } elseif ($request->query('action') === 'next') {
            $month++; if ($month > 12) { $month = 1; $year++; }
        }

        // Calendar + time grid (now built for the correct month/year)
        $calendar = $this->build_calendar($month, $year, $selectedDatesForCalendar);
        $timeSelection = $this->build_time_selection($user);

        // Unified display list (unchanged) ...
        $displayDatetimes = [];
        foreach ($savedDatetimes as $item) {
            $displayDatetimes[] = [
                'persisted' => true,
                'id'   => $item->id,
                'date' => $item->selected_date,
                'time' => substr($item->selected_time, 0, 5),
            ];
        }
        foreach ($selectedDatetimes['dates'] as $index => $date) {
            $displayDatetimes[] = [
                'persisted'    => false,
                'sessionIndex' => $index,
                'date'         => $date,
                'time'         => $selectedDatetimes['times'][$index] ?? '',
            ];
        }

        // DB datetimes for this user and selected date
        $dbDatetimesForSelectedDate = UserSelectedDatetime::where('user_id', $user->id)
            ->where('selected_date', $currentSelectedDate)
            ->orderBy('selected_date')
            ->orderBy('selected_time')
            ->get(['id', 'selected_date', 'selected_time','is_reserved']);

        // Session (unsaved) for selected date
        $sessionDatetimesForSelectedDate = [];
        foreach ($selectedDatetimes['dates'] as $i => $date) {
            $normalized = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
                ? $date
                : date('Y-m-d', strtotime(str_replace('/', '-', $date)));
            if ($normalized === $currentSelectedDate) {
                $sessionDatetimesForSelectedDate[] = [
                    'index' => $i,
                    'date'  => $normalized,
                    'time'  => $selectedDatetimes['times'][$i] ?? '',
                ];
            }
        }

        // Bookings for this instructor on that date
        $bookingsByTime = Booking::where('instructor_id', $user->id)
            ->where('selected_date', $currentSelectedDate)
            ->whereIn('status', ['pending','claimed','confirmed'])
            ->get()
            ->keyBy(fn($b) => substr($b->selected_time, 0, 5));

        $session = Session::get('selected_datetimes', ['dates' => [], 'times' => []]);
        $sessionDatetimesAll = [];
        foreach ($session['dates'] as $i => $date) {
            $normalizedDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
                ? $date
                : date('Y-m-d', strtotime(str_replace('/', '-', $date)));
            $sessionDatetimesAll[] = [
                'index' => $i,
                'date'  => $normalizedDate,
                'time'  => $session['times'][$i] ?? '',
            ];
        }

        return view('profile-date', [
            'user'                            => $user,
            'calendar'                        => $calendar,
            'timeSelection'                   => $timeSelection,
            'selectedDatetimes'               => $selectedDatetimes,
            'currentSelectedDate'             => $currentSelectedDate,
            'dbDatetimesForSelectedDate'      => $dbDatetimesForSelectedDate,
            'sessionDatetimesForSelectedDate' => $sessionDatetimesForSelectedDate,
            'sessionDatetimesAll'             => $sessionDatetimesAll,
            'bookingsByTime'                  => $bookingsByTime, 
        ]);
    } elseif ($accessLevel == 'U') {

            return view('profile-guest-view', [
                'user'         => $user,
                'mountains'    => $mountains,
                'userMountains'=> $userMountains,
                'accessLevel'  => $accessLevel
            ]);

        } elseif ($accessLevel == 'N') {
            return redirect()->route('home');
        } else {
            abort(403, 'Unauthorized access');
        }
    }

    // ---- Calendar builder (supports selected dates in Y-m-d) ----
    private function build_calendar(int $month, int $year, array $selectedDates = []): string
    {
        $sessionDates = collect(Session::get('selected_datetimes.dates', []))->map(function ($d) {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) return $d;
            $ts = strtotime(str_replace('/', '-', $d));
            return $ts ? date('Y-m-d', $ts) : $d;
        });

        $allSelected = collect($selectedDates)->merge($sessionDates)->unique()->values()->all();
        $lastSelectedDate = Session::get('last_selected_date'); // Y-m-d

        $daysOfWeek = ['Δευ', 'Τρι', 'Τετ', 'Πεμ', 'Παρ', 'Σαβ', 'Κυρ'];
        $monthsGreek = [
            1=>'Ιανουάριος',2=>'Φεβρουάριος',3=>'Μάρτιος',4=>'Απρίλιος',
            5=>'Μάιος',6=>'Ιούνιος',7=>'Ιούλιος',8=>'Αύγουστος',
            9=>'Σεπτέμβριος',10=>'Οκτώβριος',11=>'Νοέμβριος',12=>'Δεκέμβριος',
        ];

        $firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);
        $numberDays = (int) date('t', $firstDayOfMonth);
        $monthName = $monthsGreek[$month] ?? date('F', $firstDayOfMonth);

        $dayOfWeek = (int) date('N', $firstDayOfMonth); // 1..7 (Mon..Sun)
        $leadingEmptyCells = $dayOfWeek - 1;

         $prevMonth = $month - 1; $prevYear = $year; if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
        $nextMonth = $month + 1; $nextYear = $year; if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

            $baseUrl = request()->url();

            $html  = "<div class='card mt-4'>";
        $html .= "  <div class='card-header bg-light text-dark text-center'><h2>{$monthName} {$year}</h2></div>";
        $html .= "  <div class='card-body'>";
        $html .= "    <div class='d-flex justify-content-between mb-3'>";

        // ✅ AJAX nav buttons instead of GET links
        $html .= "      <button type='button' class='btn btn-outline-primary js-cal-nav' data-year='{$prevYear}' data-month='{$prevMonth}'>Προηγούμενος Μήνας</button>";
        $html .= "      <button type='button' class='btn btn-outline-primary js-cal-nav' data-year='{$nextYear}' data-month='{$nextMonth}'>Επόμενος Μήνας</button>";

        $html .= "    </div>";


        $html .= "    <table class='table table-bordered mb-0'>";
        $html .= "      <thead class='thead-light'><tr>";
        foreach ($daysOfWeek as $d) $html .= "<th class='text-center'>{$d}</th>";
        $html .= "      </tr></thead><tbody><tr>";

        if ($leadingEmptyCells > 0) $html .= str_repeat("<td></td>", $leadingEmptyCells);

        $currentDay = 1;
        $cellIndex = $leadingEmptyCells;

        while ($currentDay <= $numberDays) {
            if ($cellIndex === 7) { $html .= "</tr><tr>"; $cellIndex = 0; }

            $dateYmd = sprintf('%04d-%02d-%02d', $year, $month, $currentDay);
            $isToday = ($dateYmd === date('Y-m-d'));
            $isSelected = in_array($dateYmd, $allSelected, true);
            $todayClass = $isToday ? 'current-day' : '';
            //$selectedClass = $isSelected ? 'bg-info text-white' : '';
            $lastClickedClass = ($lastSelectedDate === $dateYmd) ? 'last-clicked' : '';

            // inside build_calendar loop (replace the <form>...<button type='submit' ...>...</form> part)
            $html .= "<td class='text-center align-middle {$todayClass} {$lastClickedClass}'>";
            $html .= "  <button type='button' class='btn button-shandow-st btn-link p-0 js-select-date' data-date='{$dateYmd}'>{$currentDay}</button>";
            $html .= "</td>";


            $currentDay++;
            $cellIndex++;
        }

        if ($cellIndex !== 7) $html .= str_repeat("<td></td>", 7 - $cellIndex);

        $html .= "      </tr></tbody></table>";
        $html .= "  </div>";
        $html .= "</div>";

        return $html;
    }


    public function selectDate(User $user, Request $request)
    {
        $date = $request->input('selected_date');
        // normalize to Y-m-d
        if ($date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $ts = strtotime(str_replace('/', '-', $date));
            $date = $ts ? date('Y-m-d', $ts) : date('Y-m-d');
        }
        if (!$date) $date = date('Y-m-d');

        // remember last clicked date (for highlight)
        Session::put('last_selected_date', $date);

        // month/year from selected date (so calendar centers on that month)
        $month = (int) date('n', strtotime($date));
        $year  = (int) date('Y', strtotime($date));

        $dbDatetimesForSelectedDate = UserSelectedDatetime::where('user_id', $user->id)
            ->where('selected_date', $date)
            ->orderBy('selected_time')
            ->get(['id','selected_date','selected_time','is_reserved']);

        // session datetimes (ALL + filtered for selected date if you need)
        $session = Session::get('selected_datetimes', ['dates' => [], 'times' => []]);

        // build "ALL session" items (used by your yellow list)
        $sessionDatetimesAll = [];
        foreach ($session['dates'] as $i => $d) {
            $dNorm = preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) ? $d : date('Y-m-d', strtotime(str_replace('/', '-', $d)));
            $sessionDatetimesAll[] = [
                'index' => $i,
                'date'  => $dNorm,
                'time'  => $session['times'][$i] ?? '',
            ];
        }

        // selected dates for calendar highlight (DB + session)
        $selectedDatesForCalendar = collect(
            \App\Models\UserSelectedDatetime::where('user_id', $user->id)->pluck('selected_date')->toArray()
        )->merge($session['dates'])->map(function ($d) {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) return $d;
            $ts = strtotime(str_replace('/', '-', $d));
            return $ts ? date('Y-m-d', $ts) : $d;
        })->unique()->values()->toArray();

        // rebuild calendar & time grid for the new date
        $calendar      = $this->build_calendar($month, $year, $selectedDatesForCalendar);
        // build_time_selection reads request('selected_date'), so set it for this call:
        $request->merge(['selected_date' => $date]);
        $timeSelection = $this->build_time_selection($user);

        $bookingsByTime = Booking::where('instructor_id', $user->id)
            ->where('selected_date', $date)
            ->whereIn('status', ['pending','claimed','confirmed'])
            ->get()
            ->keyBy(fn($b) => substr($b->selected_time, 0, 5));


        $dbListHtml = view('partials.db-datetimes-list', [
                'dbDatetimesForSelectedDate' => $dbDatetimesForSelectedDate,
                'currentSelectedDate'        => $date,
                'user'                       => $user,
                'bookingsByTime'             => $bookingsByTime, // ← add this
            ])->render();


        // And the session pending list:
        $sessionListHtml = view('partials.session-datetimes-list', [
            'sessionDatetimesAll' => $sessionDatetimesAll,
            'user' => $user, // for the Save All form route
        ])->render();

        return response()->json([
            'success'          => true,
            'selected_date'    => $date,
            'calendar'         => $calendar,
            'timeSelection'    => $timeSelection,
            'dbListHtml'       => $dbListHtml,
            'sessionListHtml'  => $sessionListHtml,
        ]);
    }


    // Time buttons (disable if already chosen in session or saved in DB)
    private function build_time_selection(User $user)
    {
        $times = [];
        for ($i = 9; $i <= 15; $i++) { // 09:00 to 15:00
            $times[] = sprintf('%02d:00', $i);
        }

        $html  = "<form method='POST'>";
        $html .= csrf_field();

        // Selected date from request (Y-m-d preferred)
        $selectedDate = trim((string) request('selected_date', ''));
        if ($selectedDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
            $ts = strtotime(str_replace('/', '-', $selectedDate));
            $selectedDate = $ts ? date('Y-m-d', $ts) : '';
        }

        $html .= "<input type='hidden' name='selected_date' value='{$selectedDate}'>";
        $html .= "<div class='d-flex flex-wrap justify-content-center'>";

        // DB: fetch existing times for that date/user (stored as HH:MM:SS)
        $existingTimes = $selectedDate
            ? UserSelectedDatetime::where('selected_date', $selectedDate)
                ->where('user_id', $user->id)
                ->pluck('selected_time')
                ->toArray()
            : [];

        // SESSION: build pairs "Y-m-d H:i"
        $sessionDates = Session::get('selected_datetimes.dates', []);
        $sessionTimes = Session::get('selected_datetimes.times', []);
        $sessionPairs = [];
        foreach ($sessionDates as $i => $d) {
            $dNorm = preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) ? $d : date('Y-m-d', strtotime(str_replace('/', '-', $d)));
            $sessionPairs[] = trim($dNorm . ' ' . ($sessionTimes[$i] ?? ''));
        }

        foreach ($times as $time) {
            $disabledInSession = in_array(trim($selectedDate . ' ' . $time), $sessionPairs, true);
            $disabledInDB = in_array($time . ':00', $existingTimes, true);

            $disabled = ($disabledInSession || $disabledInDB);
            $disabledAttr = $disabled ? 'disabled' : '';
            $disabledClass = $disabled ? 'disabled' : '';

            // NEW: reason flags + date/time
            $html .= "<button
                type='submit'
                name='selected_time'
                title= 'Καταχωρείστε νέα ώρα'
                value='{$time}'
                class='btn button-shandow-st btn-outline-primary m-2 {$disabledClass}'
                {$disabledAttr}
                data-date='{$selectedDate}'
                data-time='{$time}'
                data-disabled-session='".($disabledInSession ? '1' : '0')."'
                data-disabled-db='".($disabledInDB ? '1' : '0')."'
            >{$time}</button>";
        }


        $html .= "</div>";
        $html .= "</form>";

        return $html;
    }

    public function submitSelectedDatetimes(User $user, Request $request)
    {
        $request->validate([
            'selected_datetimes' => 'required|array',
            'selected_datetimes.*.date' => 'required|string',
            'selected_datetimes.*.time' => 'required|date_format:H:i',
        ]);

        foreach ($request->selected_datetimes as $dt) {
            try {
                $dateIn = trim($dt['date']);
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateIn)) {
                    $dateIn = \Carbon\Carbon::createFromFormat('d-m-Y', $dateIn)->format('Y-m-d');
                }

                UserSelectedDatetime::create([
                    'user_id'       => $user->id,
                    'selected_date' => $dateIn,
                    'selected_time' => $dt['time'],
                ]);
            } catch (\Exception $e) {
                Log::error("Invalid date format: {$dt['date']} ({$e->getMessage()})");
                return back()->withErrors(['selected_datetimes' => 'Invalid date format provided.']);
            }
        }

        // Clear session picks
        Session::forget('selected_datetimes');
        Session::forget('last_selected_date');

        return back()->with('success', 'Οι διαθεσιμότητες καταχωρήθηκαν επιτυχώς');
    }

    public function profile_programm(User $user)
    {
        $accessLevel = $this->checkUserAccess($user);

        $mountains = Mountain::all(); 
        $userMountains = $user->mountains()->pluck('mountains.id')->toArray();

        if ($accessLevel == 'A') {
            return view('profile-date', ['user' => $user]);
        } elseif ($accessLevel == 'U') {
            return view('profile-guest-view', [
                'user'          => $user,
                'mountains'     => $mountains,
                'userMountains' => $userMountains
            ]);
        } elseif ($accessLevel == 'N') {
            return redirect()->route('home');
        } else {
            abort(403, 'Unauthorized access');
        }
    }

    // Access check
    function checkUserAccess($user)
    {
        if ($user->status === 'A' || (Auth::check() && Auth::user()->super_admin === "Y")) {
            if (Auth::check() && (Auth::user()->id === $user->id || Auth::user()->super_admin === "Y")) {
                return "A"; // owner/admin
            } else {
                return "U"; // guest view
            }
        } else {
            return "N"; // no access
        }
    }

    public function profile(User $user, Request $request)
    {
        $mountains = Mountain::all(); 
        $userMountains = $user->mountains()->pluck('mountains.id')->toArray();

        if ($user->status === 'A' || (Auth::check() && Auth::user()->super_admin === "Y")) {
            if (Auth::check() && (Auth::user()->id === $user->id || Auth::user()->super_admin === "Y")) {

                if ($request->isMethod('post')) { 
                    $request->validate([
                        'name' => 'required|string|max:255',
                        'email' => 'required|email|unique:users,email,' . $user->id, 
                        'description' => 'required|string',
                        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                        'mountains' => 'nullable|array',
                        'mountains.*' => 'exists:mountains,id',
                    ]);

                    $user->name = $request->get('name');
                    $user->email = $request->get('email');
                    $user->description = $request->get('description');

                    if ($request->hasFile('image')) {
                        if ($user->image) Storage::delete('public/' . $user->image);
                        $imagePath = $request->file('image')->store('profiles', 'public');
                        $user->image = $imagePath;
                    }

                    $user->save();
                    $user->mountains()->sync($request->get('mountains', []));

                    return redirect()->route('profile', ['user' => $user->id])
                        ->with('success', 'Profile updated successfully!');
                }

                return view('profile', [
                    'user' => $user,
                    'mountains' => $mountains,
                    'userMountains' => $userMountains
                ]);
            } else {
                $accessLevel = $this->checkUserAccess($user);

                return view('profile-guest-view', [
                    'user' => $user,
                    'mountains' => $mountains,
                    'userMountains' => $userMountains,
                    'accessLevel' => $accessLevel
                ]);
            }
        } else {
            return redirect()->route('home');
        }   
    }

    // Legacy session delete (kept for safety if referenced elsewhere)
    public function deleteDateTime(Request $request)
    {
        $index = (int) $request->input('delete_index');

        $selected = Session::get('selected_datetimes', ['dates' => [], 'times' => []]);

        if (isset($selected['dates'][$index], $selected['times'][$index])) {
            array_splice($selected['dates'], $index, 1);
            array_splice($selected['times'], $index, 1);
            Session::put('selected_datetimes', $selected);
        }

        return back();
    }
}
