<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Session; 
use App\Models\Mountain;
use Auth;
use App\Models\UserSelectedDatetime;

class ProfilesController extends Controller
{

    public function __construct()
    {
       // $this->middleware('auth');
    }

    public function profile_view(User $user){

        $accessLevel = $this->checkUserAccess($user);

        return view('profile-guest-view', [
            'user' => $user,
            'accessLevel' => $accessLevel
        ]);
    }

    public function profile_date(User $user, Request $request)
    {
        // Check user access level
        $accessLevel = $this->checkUserAccess($user);

        $mountains = Mountain::all(); 
        $userMountains = $user->mountains()->pluck('mountains.id')->toArray();

        if ($accessLevel == 'A') {
            // Handle form submission for date and time selection
            if ($request->isMethod('post')) {
                $selectedDate = $request->input('selected_date');
                $selectedTime = $request->input('selected_time');

                if ($selectedDate) {
                    Session::put('last_selected_date', $selectedDate);
                }

                if ($selectedDate && $selectedTime) {
                    // Initialize the session array if it doesn't exist
                    if (!Session::has('selected_datetimes')) {
                        Session::put('selected_datetimes', [
                            'dates' => [], // Array for dates
                            'times' => []  // Array for times
                        ]);
                    }

                    // Combine date and time into a single string
                    $datetime = $selectedDate . ' ' . $selectedTime;

                    // Check if the date-time combination is already selected
                    $selectedDatetimes = Session::get('selected_datetimes');
                    if (!in_array($datetime, array_map(function ($date, $time) {
                        return $date . ' ' . $time;
                    }, $selectedDatetimes['dates'], $selectedDatetimes['times']))) {
                        // Add the selected date and time to their respective arrays
                        Session::push('selected_datetimes.dates', $selectedDate);
                        Session::push('selected_datetimes.times', $selectedTime);
                    }
                }
            }

            // Get the current month and year from the query string or use the current date
            $month = $request->query('month', date('n'));
            $year = $request->query('year', date('Y'));

            // Handle month navigation
            if ($request->query('action')) {
                if ($request->query('action') == 'prev') {
                    $month--;
                    if ($month < 1) {
                        $month = 12;
                        $year--;
                    }
                } elseif ($request->query('action') == 'next') {
                    $month++;
                    if ($month > 12) {
                        $month = 1;
                        $year++;
                    }
                }
            }

            // Build the calendar
            $calendar = $this->build_calendar($month, $year);

            // Build the time selection
            $timeSelection = $this->build_time_selection($user);

            // Get selected dates and times from the session
            $selectedDatetimes = Session::get('selected_datetimes', []);

            return view('profile-date', [
                'user' => $user,
                'calendar' => $calendar,
                'timeSelection' => $timeSelection,
                'selectedDatetimes' => $selectedDatetimes
            ]);
        } elseif ($accessLevel == 'U') {
            $accessLevel = $this->checkUserAccess($user);

            return view('profile-guest-view', [
                'user' => $user,
                'mountains' => $mountains,
                'userMountains' => $userMountains,
                'accessLevel' => $accessLevel
            ]);
        } elseif ($accessLevel == 'N') {
            return redirect()->route('home');
        } else {
            abort(403, 'Unauthorized access');
        }
    }

    // Function to build the calendar
    private function build_calendar($month, $year)
    {
        $daysOfWeek = ['Δευ', 'Τρι', 'Τετ', 'Πεμ', 'Παρ', 'Σαβ', 'Κυρ']; // Greek days of the week starting from Monday
        $firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);
        $numberDays = date('t', $firstDayOfMonth);
        $dateComponents = getdate($firstDayOfMonth);
        $monthName = [
            'Ιανουάριος', 'Φεβρουάριος', 'Μάρτιος', 'Απρίλιος', 'Μάιος', 'Ιούνιος',
            'Ιούλιος', 'Αύγουστος', 'Σεπτέμβριος', 'Οκτώβριος', 'Νοέμβριος', 'Δεκέμβριος'
        ][$month - 1]; // Greek month names
        $dayOfWeek = $dateComponents['wday'];
        // Adjust day of the week to start from Monday (1 = Monday, 7 = Sunday)
        $dayOfWeek = $dayOfWeek == 0 ? 6 : $dayOfWeek - 1;

        $calendar = "<div class='card mt-4'>";
        $calendar .= "<div class='card-header bg-light text-dark text-center'>";
        $calendar .= "<h2>$monthName $year</h2>";
        $calendar .= "</div>";
        $calendar .= "<div class='card-body'>";
        $calendar .= "<div class='d-flex justify-content-between mb-3'>";
        $calendar .= "<a href='?month=" . ($month - 1) . "&year=$year' class='btn btn-outline-primary'>Προηγούμενος Μήνας</a>";
        $calendar .= "<a href='?month=" . ($month + 1) . "&year=$year' class='btn btn-outline-primary'>Επόμενος Μήνας</a>";
        $calendar .= "</div>";
        $calendar .= "<table class='table table-bordered'>";
        $calendar .= "<thead class='thead-light'>";
        $calendar .= "<tr>";
        foreach ($daysOfWeek as $day) {
            $calendar .= "<th scope='col' class='text-center'>$day</th>";
        }
        $calendar .= "</tr>";
        $calendar .= "</thead>";
        $calendar .= "<tbody>";
        $calendar .= "<tr>";
        if ($dayOfWeek > 0) {
            $calendar .= str_repeat("<td></td>", $dayOfWeek);
        }
        
        $currentDay = 1;
        while ($currentDay <= $numberDays) {
            if ($dayOfWeek == 7) {
                $dayOfWeek = 0;
                $calendar .= "</tr><tr>";
            }
            $todayClass = ($currentDay == date('j') && $month == date('n') && $year == date('Y')) ? 'currenet-day' : '';
            $selectedDate = "$currentDay-$month-$year";
            $isSelected = in_array($selectedDate, Session::get('selected_datetimes.dates', []));
            $lastSelectedDate = Session::get('last_selected_date');
            $selectedDates = Session::get('selected_datetimes.dates', []);
            $selectedTimes = Session::get('selected_datetimes.times', []);
            $selectedClass = $isSelected ? 'bg-info selected-date text-white' : '';

            $calendar .= "<td class='text-center $todayClass '>";

            $lastSelectedClass = ($selectedDate === $lastSelectedDate && !in_array($selectedDate, $selectedTimes)) ? 'last-clicked' : '';

            $calendar .= "<form method='POST' class='d-inline'>";
            $calendar .= csrf_field(); // Add CSRF token
            $calendar .= "<button type='submit' name='selected_date' value='$selectedDate' class='btn btn-link p-0 $lastSelectedClass'>$currentDay</button>";
            $calendar .= "</form>";

            $calendar .= "</td>";
            $currentDay++;
            $dayOfWeek++;
        }
        if ($dayOfWeek != 7) {
            $remainingDays = 7 - $dayOfWeek;
            $calendar .= str_repeat("<td></td>", $remainingDays);
        }
        $calendar .= "</tr>";
        $calendar .= "</tbody>";
        $calendar .= "</table>";
        $calendar .= "</div>";
        //$calendar .= "</div>";
       
        return $calendar;
    }

    public function submitSelectedDatetimes(User $user, Request $request)
    {
        // Validate the request
        $request->validate([
            'selected_datetimes' => 'required|array',
            'selected_datetimes.*.date' => 'required|string', // Accepts string since we'll convert it
            'selected_datetimes.*.time' => 'required|date_format:H:i',
        ]);

        foreach ($request->selected_datetimes as $datetime) {
            try {
                // Check if the date is in 'Y-m-d' format (e.g. 2025-02-28)
                if (\Carbon\Carbon::hasFormat($datetime['date'], 'Y-m-d')) {
                    // If it's already in 'Y-m-d' format, just use it directly
                    $formattedDate = $datetime['date'];
                } else {
                    // Otherwise, try to convert it from 'd-m-Y'
                    $formattedDate = \Carbon\Carbon::createFromFormat('d-m-Y', $datetime['date'])->format('Y-m-d');
                }

                UserSelectedDatetime::create([
                    'user_id' => $user->id,
                    'selected_date' => $formattedDate, // Store correct format
                    'selected_time' => $datetime['time'],
                ]);
            } catch (\Exception $e) {
                Log::error("Invalid date format: " . $datetime['date']);
                return redirect()->back()->withErrors(['selected_datetimes' => 'Invalid date format provided.']);
            }
        }

        // Clear the session data
        Session::forget('selected_datetimes');
        Session::forget('last_selected_date');

        return redirect()->back()->with('success', 'Selected date-times saved successfully!');
    }


    private function build_time_selection(User $user)
    {
        $times = [];
        for ($i = 9; $i <= 15; $i++) {
            $times[] = sprintf("%02d:00", $i);
        }
    
      
        $timeSelection  = "<form method='POST'>";
        $timeSelection .= csrf_field(); // Add CSRF token
    
        // Get and clean selected date
        $selectedDate = request('selected_date') ?? '';
        $selectedDate = trim($selectedDate); // Remove extra spaces
    
        // Convert format only if needed
        if (!empty($selectedDate) && preg_match('/^\d{1,2}-\d{1,2}-\d{4}$/', $selectedDate)) {
            try {
                $selectedDate = \Carbon\Carbon::createFromFormat('d-m-Y', $selectedDate)->format('Y-m-d');
            } catch (\Exception $e) {
                $selectedDate = ''; // Reset if conversion fails
            }
        }
    
        $timeSelection .= "<input type='hidden' name='selected_date' value='$selectedDate'>";
        $timeSelection .= "<div class='d-flex flex-wrap justify-content-center'>";
    
        // Fetch selected times from database
        $existingTimes = $selectedDate 
                        ? \App\Models\UserSelectedDatetime::where('selected_date', $selectedDate)
                            ->where('user_id', $user->id)
                            ->pluck('selected_time')
                            ->toArray() 
                        : [];
    
        foreach ($times as $time) {
            // Check if the time is already selected in session
            $isDisabledInSession = false;
            $sessionDates = Session::get('selected_datetimes.dates', []);
            $sessionTimes = Session::get('selected_datetimes.times', []);
    
            if (!empty($sessionDates) && !empty($sessionTimes)) {
                $formattedSessionDates = array_map(function ($date) {
                    return preg_match('/^\d{1,2}-\d{1,2}-\d{4}$/', $date) 
                        ? \Carbon\Carbon::createFromFormat('d-m-Y', $date)->format('Y-m-d') 
                        : $date;
                }, $sessionDates);
    
                $isDisabledInSession = in_array("$selectedDate $time", array_map(null, $formattedSessionDates, $sessionTimes));
            }
    
            // Check if the time exists in the database
            $isDisabledInDB = in_array("$time:00", $existingTimes);
            $disabledClass = ($isDisabledInSession || $isDisabledInDB) ? 'disabled' : '';
    
            $timeSelection .= "<button type='submit' name='selected_time' value='$time' class='btn btn-outline-primary m-2 $disabledClass' $disabledClass>$time</button>";
        }
    
        $timeSelection .= "</div>";
        $timeSelection .= "</form>";
       
    
        return $timeSelection;
    }
    


    public function profile_programm(User $user){

        $accessLevel = $this->checkUserAccess($user);

        // Fetch all mountains from the database
        $mountains = Mountain::all(); 
        $userMountains = $user->mountains()->pluck('mountains.id')->toArray();

        if ( $accessLevel == 'A'){
            return view('profile-date', ['user' => $user]);
        }elseif ( $accessLevel == 'U'){
            return view('profile-guest-view', [
                'user' => $user,
                'mountains' => $mountains,
                'userMountains' => $userMountains
            ]);
        }elseif ( $accessLevel == 'N'){
            return redirect()->route('home');
        }else{
            abort(403, 'Unauthorized access');
        }
    }

    function checkUserAccess($user)
    {
        if ($user->status === 'A' || (Auth::check() && Auth::user()->super_admin === "Y")) {
            if (Auth::check() && (Auth::user()->id === $user->id || Auth::user()->super_admin === "Y")) {
                return "A";
            } else {
                return "U";
            }
        } else {
            return "N";
        }
    }

    public function profile(User $user, Request $request)
    {
        // Fetch all mountains from the database
        $mountains = Mountain::all(); 
        $userMountains = $user->mountains()->pluck('mountains.id')->toArray();

        if($user->status === 'A' || (Auth::check() && Auth::user()->super_admin === "Y")){
            if ( Auth::check() && (Auth::user()->id === $user->id  || Auth::user()->super_admin === "Y")  ) {
                if ($request->isMethod('post')) { 
                    // Validate input
                    $request->validate([
                        'name' => 'required|string|max:255',
                        'email' => 'required|email|unique:users,email,' . $user->id, 
                        'description' => 'required|string',
                        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                        'mountains' => 'nullable|array', // Allow multiple values
                        'mountains.*' => 'exists:mountains,id', // Ensure each selected value exists in the database
                    ]);

                    // Update user details
                    $user->name = $request->get('name');
                    $user->email = $request->get('email');
                    $user->description = $request->get('description');

                    // Handle image upload
                    if ($request->hasFile('image')) {
                        if ($user->image) {
                            Storage::delete('public/' . $user->image);
                        }
                        $imagePath = $request->file('image')->store('profiles', 'public');
                        $user->image = $imagePath;
                    }

                    $user->save();

                    // Sync selected mountains (insert into mountain_user pivot table)
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
        } else{
            return redirect()->route('home');
        }   
    }

    public function deleteDateTime(Request $request)
    {
        $index = $request->input('delete_index');

        // Get the current session values
        $selectedDatetimes = Session::get('selected_datetimes', [
            'dates' => [],
            'times' => []
        ]);

        // Ensure the index exists
        if (isset($selectedDatetimes['dates'][$index]) && isset($selectedDatetimes['times'][$index])) {
            // Remove the selected index from both arrays
            array_splice($selectedDatetimes['dates'], $index, 1);
            array_splice($selectedDatetimes['times'], $index, 1);

            // Update the session
            Session::put('selected_datetimes', $selectedDatetimes);
        }

        // Redirect back
        return redirect()->back();
    }

}
