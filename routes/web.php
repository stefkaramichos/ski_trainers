<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MountainsController;
use App\Http\Controllers\ProfilesController;
use App\Http\Controllers\AdminTrainersController;

Route::get('/', function () {
    return view('home');
});

Auth::routes();
Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('/mountain/{mountain}', [App\Http\Controllers\MountainsController::class, 'mountain'])->name('mountain');
Route::any('/profile/{user}', [App\Http\Controllers\ProfilesController::class, 'profile'])->name('profile');
Route::any('/admin_trainers', [App\Http\Controllers\AdminTrainersController::class, 'admin_trainers'])->name('admin.trainers');
Route::post('/update-user-status', [AdminTrainersController::class, 'updateStatus'])->name('updateUserStatus');
Route::post('/delete-user', [AdminTrainersController::class, 'deleteUser'])->name('deleteUser');
Route::get('/profile/{user}/date', [ProfilesController::class, 'profile_date'])->name('profile.date');
Route::post('/profile/{user}/date', [ProfilesController::class, 'profile_date']);
Route::any('/profile_programm/{user}', [App\Http\Controllers\ProfilesController::class, 'profile_programm'])->name('profile.programm');
Route::any('/profile_guest_view/{user}', [App\Http\Controllers\ProfilesController::class, 'profile_view'])->name('profile.view');
Route::any('/profile/{user}/submit-datetimes', [ProfilesController::class, 'submitSelectedDatetimes'])->name('submit.selected.datetimes');
