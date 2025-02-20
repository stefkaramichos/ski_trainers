<?php
namespace App\Http\Controllers;
use App\Models\Mountain;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Http\Middleware\CheckSuperAdmin;
use Auth;

class AdminTrainersController extends Controller
{
    public function admin_trainers(Request $request)
    {
        if (Auth::check() && Auth::user()->super_admin == 'Y') {
            $mountains = Mountain::all(); 
            $users = User::all();

            if ($request->isMethod('post')) {
                // Validate input
                $request->validate([
                    'name' => 'required|string|max:255',
                    'email' => 'required|email|unique:users,email',
                    'password' => 'required|min:6',
                    'description' => 'nullable|string',
                    'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                    'mountains' => 'required|array',
                    'mountains.*' => 'exists:mountains,id',
                ]);

                // Handle image upload
                $imagePath = null;
                if ($request->hasFile('image')) {
                    $imagePath = $request->file('image')->store('profiles', 'public');
                }

                // Create the user
                $user = User::create([
                    'name' => $request->input('name'),
                    'email' => $request->input('email'),
                    'password' => Hash::make($request->input('password')),
                    'description' => $request->input('description'),
                    'image' => $imagePath, // Assuming the users table has an 'image' column
                ]);

                // Attach user to selected mountains
                if ($request->has('mountains')) {
                    $user->mountains()->attach($request->input('mountains'));
                }

                return redirect()->back()->with('success', 'Trainer added successfully!');
            }
        }else{
            return redirect()->route('home');
        }
        return view('admin.admin_trainers', ['users' => $users, 'mountains' => $mountains]);
    }

    public function updateStatus(Request $request)
    {
        // Get the user and status from the request
        $user = User::find($request->user_id);
        if ($user) {
            // Update the user's status
            $user->status = $request->status;
            $user->save();

            // Return a success response
            return response()->json(['success' => true, 'status' => $request->status]);
        }

        return response()->json(['success' => false], 400);
    }
    public function deleteUser(Request $request)
    {
        // Find the user by ID
        $user = User::find($request->user_id);

        if ($user) {
            // Delete the user
            $user->delete();

            // Return success response
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 400);
    }

}
