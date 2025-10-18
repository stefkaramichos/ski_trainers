<?php

namespace App\Http\Controllers;

use App\Models\Mountain;
use Illuminate\Http\Request;
use Auth;

class AdminMountainsController extends Controller
{
    public function admin_mountains(Request $request)
    {
        if (!(Auth::check() && Auth::user()->super_admin === 'Y')) {
            return redirect()->route('home');
        }

        // Create (POST on same route)
        if ($request->isMethod('post')) {
            $request->validate([
                'mountain_name' => 'required|string|max:255|unique:mountains,mountain_name',
            ]);

            Mountain::create([
                'mountain_name' => $request->input('mountain_name'),
            ]);

            return redirect()->back()->with('success', 'Το βουνό προστέθηκε με επιτυχία!');
        }

        // List
        $mountains = Mountain::orderBy('mountain_name')->get();

        return view('admin.admin_mountains', [
            'mountains' => $mountains
        ]);
    }

    public function updateMountain(Request $request)
    {
        $request->validate([
            'mountain_id'   => 'required|exists:mountains,id',
            'mountain_name' => 'required|string|max:255|unique:mountains,mountain_name,' . $request->mountain_id,
        ]);

        $mountain = Mountain::find($request->mountain_id);
        $mountain->mountain_name = $request->mountain_name;
        $mountain->save();

        return response()->json(['success' => true, 'name' => $mountain->mountain_name]);
    }

    public function deleteMountain(Request $request)
    {
        $request->validate([
            'mountain_id' => 'required|exists:mountains,id',
        ]);

        $mountain = Mountain::find($request->mountain_id);

        if ($mountain) {
            // If you have a users() relation like $mountain->users() on the Mountain model, detach to clean pivot:
            if (method_exists($mountain, 'users')) {
                $mountain->users()->detach();
            }

            $mountain->delete();
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 400);
    }
}
