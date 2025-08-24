<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('books', [BookController::class, 'index']);
    Route::post('books', [BookController::class, 'store']);
    Route::post('books/{id}/favourite', [BookController::class, 'toggleFavourite']);
    Route::get('books/{id}', [BookController::class, 'show']);
    Route::delete('books/{id}', [BookController::class, 'destroy']);


    Route::get('settings', [ProfileController::class, 'getSettings']);
    Route::post('settings', [ProfileController::class, 'settings']);
    Route::get('profile', [ProfileController::class, 'show']);
});
