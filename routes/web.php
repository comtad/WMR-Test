<?php

use App\Http\Controllers\{
    DashboardController,
    GetMediaController,
    ProfileController,
    UploadController
};
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', DashboardController::class)
        ->middleware('verified')
        ->name('dashboard');

    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });

    Route::post('/upload', UploadController::class)->name('upload');
    Route::get('/media/accessible', GetMediaController::class)->name('media.accessible');
});

require __DIR__.'/auth.php';
