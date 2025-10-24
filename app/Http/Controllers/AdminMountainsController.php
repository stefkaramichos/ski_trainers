<?php

namespace App\Http\Controllers;

use App\Models\Mountain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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
                'slug'          => 'nullable|string|max:255|unique:mountains,slug',
                'latitude'      => 'required|numeric|between:-90,90',
                'longitude'     => 'required|numeric|between:-180,180',
                'description'   => 'nullable|string',
                'image_1'       => 'nullable|image|max:4096', // ~4MB
                'image_2'       => 'nullable|image|max:4096',
            ]);

            // Generate slug if not provided
            $slug = $validated['slug'] ?? null;
            if (!$slug || trim($slug) === '') {
                $slug = Str::slug($validated['mountain_name'], '-');
                // make sure slug is unique; if exists, append number
                $originalSlug = $slug;
                $i = 2;
                while (Mountain::where('slug', $slug)->exists()) {
                    $slug = $originalSlug . '-' . $i;
                    $i++;
                }
            }

            // handle file uploads
            $image1Path = null;
            $image2Path = null;

            if ($request->hasFile('image_1')) {
                // stored in storage/app/public/mountains -> public/storage/mountains
                $image1Path = $request->file('image_1')->store('mountains', 'public');
            }

            if ($request->hasFile('image_2')) {
                $image2Path = $request->file('image_2')->store('mountains', 'public');
            }

            Mountain::create([
                'mountain_name' => $validated['mountain_name'],
                'slug'          => $slug,
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
        // validate request
        $validated = $request->validate([
            'mountain_id'   => 'required|exists:mountains,id',
            'mountain_name' => 'required|string|max:255|unique:mountains,mountain_name,' . $request->mountain_id,
            'slug'          => 'nullable|string|max:255|unique:mountains,slug,' . $request->mountain_id,
            'latitude'      => 'required|numeric|between:-90,90',
            'longitude'     => 'required|numeric|between:-180,180',
            'description'   => 'nullable|string',
            'image_1'       => 'nullable|image|max:4096',
            'image_2'       => 'nullable|image|max:4096',
        ]);

        $mountain = Mountain::findOrFail($validated['mountain_id']);

        // Handle slug logic:
        // - if provided, use it
        // - if not provided or empty, regenerate from new name
        $incomingSlug = $validated['slug'] ?? null;
        if (!$incomingSlug || trim($incomingSlug) === '') {
            $incomingSlug = Str::slug($validated['mountain_name'], '-');

            // ensure uniqueness if name changed to conflict
            $originalSlug = $incomingSlug;
            $i = 2;
            while (
                Mountain::where('slug', $incomingSlug)
                        ->where('id', '!=', $mountain->id)
                        ->exists()
            ) {
                $incomingSlug = $originalSlug . '-' . $i;
                $i++;
            }
        }

        // update basic fields
        $mountain->mountain_name = $validated['mountain_name'];
        $mountain->slug          = $incomingSlug;
        $mountain->latitude      = $validated['latitude'];
        $mountain->longitude     = $validated['longitude'];
        $mountain->description   = $validated['description'] ?? $mountain->description;

        // If user uploaded a new image_1, replace the stored path
        if ($request->hasFile('image_1')) {
            $path1 = $request->file('image_1')->store('mountains', 'public');
            $mountain->image_1 = $path1;
            // You can optionally delete the old file if you want, using Storage::disk('public')->delete(...)
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
            'slug'          => $mountain->slug,
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
            // detach relations if you have pivot like $mountain->users()
            if (method_exists($mountain, 'users')) {
                $mountain->users()->detach();
            }

            // Optional: delete images from storage here if you want cleanup
            // if ($mountain->image_1) Storage::disk('public')->delete($mountain->image_1);
            // if ($mountain->image_2) Storage::disk('public')->delete($mountain->image_2);

            $mountain->delete();

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 400);
    }
}
