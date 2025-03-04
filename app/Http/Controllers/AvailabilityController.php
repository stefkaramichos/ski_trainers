<?php

namespace App\Http\Controllers;
use App\Models\UserSelectedDatetime;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function getAvailability(Request $request) {
        $availabilities = UserSelectedDatetime::where('user_id', $request->user_id)
            ->whereDate('selected_date', $request->date)
            ->whereBetween('selected_time', ['09:00:00', '15:00:00'])
            ->orderBy('selected_time', 'asc')
            ->pluck('selected_time');
        
        return response()->json($availabilities);
    }
}
