<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MountainsController;
use App\Http\Controllers\ProfilesController;
use App\Http\Controllers\AdminTrainersController;
use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\AdminMountainsController;
use App\Http\Controllers\AdminBookingsController;
//testgit 

Route::get('/', function () {
    return view('home');
});

Auth::routes();
Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('/mountain-forecast/{id}', [HomeController::class, 'getMountainForecast'])->name('mountain.forecast'); 
Route::get('/home', [HomeController::class, 'index'])->name('dashboard'); // or 'home.index'
Route::get('/mountain/{mountain}', [App\Http\Controllers\MountainsController::class, 'mountain'])->name('mountain');
Route::any('/profile/{user}', [App\Http\Controllers\ProfilesController::class, 'profile'])->name('profile');
Route::any('/admin/admin_trainers', [App\Http\Controllers\AdminTrainersController::class, 'admin_trainers'])->name('admin.trainers');
Route::post('/update-user-status', [AdminTrainersController::class, 'updateStatus'])->name('updateUserStatus');
Route::post('/delete-user', [AdminTrainersController::class, 'deleteUser'])->name('deleteUser');
Route::get('/profile/{user}/date', [ProfilesController::class, 'profile_date'])->name('profile.date');
Route::post('/profile/{user}/date', [ProfilesController::class, 'profile_date']);
Route::any('/profile_programm/{user}', [App\Http\Controllers\ProfilesController::class, 'profile_programm'])->name('profile.programm');
Route::any('/profile_guest_view/{user}', [App\Http\Controllers\ProfilesController::class, 'profile_view'])->name('profile.view');
Route::any('/profile/{user}/submit-datetimes', [ProfilesController::class, 'submitSelectedDatetimes'])
    ->name('submit.selected.datetimes');

// Route::post('/profile-date/delete', [ProfilesController::class, 'deleteDateTime'])->name('profile-date.delete');
Route::post('/profile-date/delete-session', [ProfilesController::class, 'deleteSession'])
    ->name('profile-date.delete');

// routes/web.php
Route::post('/profile/{user}/delete-saved', [ProfilesController::class, 'deleteSaved'])
    ->name('profile-date.deleteSaved');


Route::get('/get-availability', [AvailabilityController::class, 'getAvailability']);
// routes/web.php
Route::post('/profile/{user}/select-date', [\App\Http\Controllers\ProfilesController::class, 'selectDate'])
    ->name('profile-date.selectDate');


Route::post('/profile/{user}/book', [ProfilesController::class, 'book'])->name('profile.book');

// Booking submit (from Home)
Route::post('/book', [BookingController::class, 'store'])->name('home.book');

// AJAX – get available times for instructor+date
Route::post('/availability/times', [AvailabilityController::class, 'times'])->name('availability.times');


Route::post('/availability/times-by-mountain', [AvailabilityController::class, 'timesByMountain'])
    ->name('availability.timesByMountain');

Route::middleware('auth')->group(function () {
    Route::get('/booking/{booking}/claim', [BookingController::class, 'claim'])->name('booking.claim');
});



// routes/web.php
Route::get('/dev/send-claims/{booking}', function (\App\Models\Booking $booking) {
    $mountainId = (int) $booking->mountain_id;
    $date = $booking->selected_date;
    $time = substr($booking->selected_time, 0, 5); // "H:i"

    $instructorIds = \App\Models\User::where('status', 'A')
        ->whereHas('mountains', fn($q) => $q->where('mountains.id', $mountainId))
        ->pluck('id')->all();

    $publishers = \App\Models\UserSelectedDatetime::whereIn('user_id', $instructorIds)
        ->where('selected_date', $date)
        ->where('selected_time', $time . ':00')
        ->pluck('user_id')->all();

    $booked = \App\Models\Booking::whereIn('instructor_id', $publishers)
        ->where('selected_date', $date)
        ->where('selected_time', $time . ':00')
        ->whereIn('status', ['pending','confirmed','claimed'])
        ->pluck('instructor_id')->all();

    $bookedSet = array_flip($booked);
    $eligible = array_values(array_filter($publishers, fn($id) => !isset($bookedSet[$id])));

    if (!$eligible) {
        return 'No eligible instructors for this slot.';
    }

    $links = [];
    foreach ($eligible as $insId) {
        $token = \Illuminate\Support\Str::random(40);
        \App\Models\BookingClaim::create([
            'booking_id'    => $booking->id,
            'instructor_id' => $insId,
            'token'         => $token,
        ]);

        // ⬇️ NO instructor param here (claim() uses Auth user)
        $links[] = route('booking.claim', [
            'booking' => $booking->id,
            'token'   => $token,
        ]);
    }

    return '<pre>'.implode("\n\n", $links).'</pre>';
});


Route::middleware(['auth', \App\Http\Middleware\CheckSuperAdmin::class])->group(function () {
    Route::match(['get','post'], '/admin/mountains', [AdminMountainsController::class, 'admin_mountains'])
        ->name('admin.mountains');

    Route::post('/admin/mountains/update', [AdminMountainsController::class, 'updateMountain'])
        ->name('updateMountain');

    Route::post('/admin/mountains/delete', [AdminMountainsController::class, 'deleteMountain'])
        ->name('deleteMountain');
});


Route::middleware(['auth', \App\Http\Middleware\CheckSuperAdmin::class])->group(function () {
    Route::get('/admin/bookings',  [AdminBookingsController::class, 'index'])->name('admin.bookings');
    Route::post('/admin/bookings/status', [AdminBookingsController::class, 'updateStatus'])->name('admin.bookings.status');
    Route::post('/admin/bookings/assign', [AdminBookingsController::class, 'assignInstructor'])->name('admin.bookings.assign');
    Route::post('/admin/bookings/delete', [AdminBookingsController::class, 'delete'])->name('admin.bookings.delete');
});