<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Mountain;
use Auth;


class ProfilesController extends Controller
{

    public function __construct()
    {
       // $this->middleware('auth');
    }

    public function profile(User $user, Request $request)
    {
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

                // Fetch all mountains from the database
                $mountains = Mountain::all(); 
                // Get mountains already associated with the user
                $userMountains = $user->mountains()->pluck('mountains.id')->toArray();

                return view('profile', [
                    'user' => $user,
                    'mountains' => $mountains,
                    'userMountains' => $userMountains
                ]);
            } else {
                return view('profile-guest-view', ['user' => $user]);
            }
        } else{
            return redirect()->route('home');
        }   
}
}
