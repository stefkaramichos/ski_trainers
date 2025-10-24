<?php

namespace App\Http\Controllers;

use App\Models\Mountain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMountainsController extends Controller
{
    public function admin_mountains(Request $request)
    {
        // only super admin can access
        if (!(Auth::check() && Auth::user()->super_admin === 'Y')) {
            return redirect()->route('home');
        }

        // HANDLE CREATE (same route, POST)
        if ($request->isMethod('post')) {

            $validated = $request->validate([
                'mountain_name' => 'required|string|max:255|unique:mountains,mountain_name',
                'latitude'      => 'required|numeric|between:-90,90',
                'longitude'     => 'required|numeric|between:-180,180',
                'description'   => 'nullable|string',
                'image_1'       => 'nullable|image|max:4096', // ~4MB
                'image_2'       => 'nullable|image|max:4096',
            ]);

            // handle file uploads
            $image1Path = null;
            $image2Path = null;

            if ($request->hasFile('image_1')) {
                // will end up like storage/mountains/abc123.jpg after storage:link
                $image1Path = $request->file('image_1')->store('mountains', 'public');
            }

            if ($request->hasFile('image_2')) {
                $image2Path = $request->file('image_2')->store('mountains', 'public');
            }

            Mountain::create([
                'mountain_name' => $validated['mountain_name'],
                'latitude'      => $validated['latitude'],
                'longitude'     => $validated['longitude'],
                'description'   => $validated['description'] ?? null,
                'image_1'       => $image1Path,
                'image_2'       => $image2Path,
            ]);

            return redirect()->back()->with('success', 'Το βουνό προστέθηκε με επιτυχία!');
        }

        // HANDLE LIST
        $mountains = Mountain::orderBy('mountain_name')->get();

        return view('admin.admin_mountains', [
            'mountains' => $mountains
        ]);
    }

    public function updateMountain(Request $request)
    {
        // IMPORTANT: AJAX edit now supports text + optional new images
        $validated = $request->validate([
            'mountain_id'   => 'required|exists:mountains,id',
            'mountain_name' => 'required|string|max:255|unique:mountains,mountain_name,' . $request->mountain_id,
            'latitude'      => 'required|numeric|between:-90,90',
            'longitude'     => 'required|numeric|between:-180,180',
            'description'   => 'nullable|string',
            'image_1'       => 'nullable|image|max:4096',
            'image_2'       => 'nullable|image|max:4096',
        ]);

        $mountain = Mountain::findOrFail($validated['mountain_id']);

        $mountain->mountain_name = $validated['mountain_name'];
        $mountain->latitude      = $validated['latitude'];
        $mountain->longitude     = $validated['longitude'];
        $mountain->description   = $validated['description'] ?? $mountain->description;

        // If user uploaded a new image_1, replace the stored path
        if ($request->hasFile('image_1')) {
            $path1 = $request->file('image_1')->store('mountains', 'public');
            $mountain->image_1 = $path1;
            // (Optional) you could delete old file here if you want
        }

        // If user uploaded a new image_2, replace the stored path
        if ($request->hasFile('image_2')) {
            $path2 = $request->file('image_2')->store('mountains', 'public');
            $mountain->image_2 = $path2;
        }

        $mountain->save();

        return response()->json([
            'success'       => true,
            'name'          => $mountain->mountain_name,
            'lat'           => $mountain->latitude,
            'lng'           => $mountain->longitude,
            'description'   => $mountain->description,
            'image_1_url'   => $mountain->image_1 ? asset('storage/' . $mountain->image_1) : null,
            'image_2_url'   => $mountain->image_2 ? asset('storage/' . $mountain->image_2) : null,
        ]);
    }

    public function deleteMountain(Request $request)
    {
        $request->validate([
            'mountain_id' => 'required|exists:mountains,id',
        ]);

        $mountain = Mountain::find($request->mountain_id);

        if ($mountain) {
            // if there is pivot relation users()->detach() keep it
            if (method_exists($mountain, 'users')) {
                $mountain->users()->detach();
            }

            // (Optional) also unlink old images from storage if you want to clean up
            // if ($mountain->image_1) Storage::disk('public')->delete($mountain->image_1);
            // if ($mountain->image_2) Storage::disk('public')->delete($mountain->image_2);

            $mountain->delete();

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 400);
    }
}
