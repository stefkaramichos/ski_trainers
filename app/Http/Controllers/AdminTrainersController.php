<?php

namespace App\Http\Controllers;

use App\Models\Mountain;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Auth;

class AdminTrainersController extends Controller
{
    public function admin_trainers(Request $request)
    {
        if (Auth::check() && Auth::user()->super_admin == 'Y') {
            $mountains = Mountain::all(); 

            // Φορτώνουμε όλα όσα θα δείξουμε στο view
            $users = User::with([
                'mountains:id,mountain_name',
                'bookings' => function ($q) {
                    $q->orderBy('selected_date')->orderBy('selected_time');
                },
                'tickets:id,instructor_id,status',
                'bookingClaims' => function ($q) {
                    // χρειάζομαι και το booking για να πάρω ημερομηνία/ώρα
                    $q->with(['booking' => function ($b) {
                        $b->select(
                            'id',
                            'selected_date',
                            'selected_time',
                            'mountain_id'
                        )->with('mountain:id,mountain_name');
                    }]);
                }
            ])->get();

            if ($request->isMethod('post')) {
                $request->validate([
                    'name' => 'required|string|max:255',
                    'email' => 'required|email|unique:users,email',
                    'password' => 'required|min:6',
                    'description' => 'nullable|string',
                    'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                    'mountains' => 'required|array',
                    'mountains.*' => 'exists:mountains,id',
                ]);

                $imagePath = null;
                if ($request->hasFile('image')) {
                    $imagePath = $request->file('image')->store('profiles', 'public');
                }

                $user = User::create([
                    'name' => $request->input('name'),
                    'email' => $request->input('email'),
                    'password' => Hash::make($request->input('password')),
                    'description' => $request->input('description'),
                    'image' => $imagePath,
                ]);

                if ($request->has('mountains')) {
                    $user->mountains()->attach($request->input('mountains'));
                }

                return redirect()->back()->with('success', 'Trainer added successfully!');
            }

            return view('admin.admin_trainers', [
                'users' => $users,
                'mountains' => $mountains
            ]);
        }

        return redirect()->route('home');
    }

    public function updateStatus(Request $request)
    {
        $user = User::find($request->user_id);
        if ($user) {
            $user->status = $request->status;
            $user->save();

            return response()->json(['success' => true, 'status' => $request->status]);
        }

        return response()->json(['success' => false], 400);
    }

    public function deleteUser(Request $request)
    {
        $user = User::find($request->user_id);

        if ($user) {
            $user->delete();
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 400);
    }
}
