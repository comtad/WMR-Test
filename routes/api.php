<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/users/generate', \App\Http\Controllers\GenerateUsersController::class)->name('users.generate');
Route::get('/users', \App\Http\Controllers\GetAllUsersController::class)->name('users.index');
Route::post('/upload', [\App\Http\Controllers\UploadController::class, 'store'])->name('upload');
