<?php

use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // User routes
    Route::resource('users', UserController::class);
    
    // Course routes
    Route::resource('courses', CourseController::class);
});