<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class CalendarController extends Controller
{
    public function build_calendar($month, $year)
    {
        // Your calendar building logic here
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
        $calendar .= "<a href='#' class='btn btn-outline-primary prev-month' data-month='" . ($month - 1) . "' data-year='$year'>Προηγούμενος Μήνας</a>";
        $calendar .= "<a href='#' class='btn btn-outline-primary next-month' data-month='" . ($month + 1) . "' data-year='$year'>Επόμενος Μήνας</a>";
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
            $todayClass = ($currentDay == date('j') && $month == date('n') && $year == date('Y')) ? 'table-warning' : '';
            $selectedDate = "$year-$month-$currentDay";
            $isSelected = in_array($selectedDate, Session::get('selected_datetimes.dates', []));
            $selectedClass = $isSelected ? 'bg-info selected-date text-white' : '';
            $calendar .= "<td class='text-center $todayClass $selectedClass'>";
            $calendar .= "<a href='#' class='select-date' data-date='$selectedDate'>$currentDay</a>";
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
        $calendar .= "</div>";
        return $calendar;
    }

    public function build_time_selection()
    {
        $times = [];
        for ($i = 9; $i <= 15; $i++) {
            $times[] = sprintf("%02d:00", $i);
        }
        $timeSelection = "<div class='card mt-4'>";
        $timeSelection .= "<div class='card-header bg-light text-dark text-center'>";
        $timeSelection .= "<h3>Επιλέξτε Ώρα</h3>";
        $timeSelection .= "</div>";
        $timeSelection .= "<div class='card-body'>";
        $timeSelection .= "<input type='hidden' id='selected-date' value='" . (request('selected_date') ?? '') . "'>";
        $timeSelection .= "<div class='d-flex flex-wrap justify-content-center'>";
        foreach ($times as $time) {
            $isDisabled = in_array((request('selected_date') ?? '') . ' ' . $time, array_map(function ($date, $time) {
                return $date . ' ' . $time;
            }, Session::get('selected_datetimes.dates', []), Session::get('selected_datetimes.times', [])));
            $disabledClass = $isDisabled ? 'disabled' : '';
            $timeSelection .= "<button type='button' class='btn btn-outline-primary m-2 select-time $disabledClass' data-time='$time' $disabledClass>$time</button>";
        }
        $timeSelection .= "</div>";
        $timeSelection .= "</div>";
        $timeSelection .= "</div>";
        return $timeSelection;
    }
}