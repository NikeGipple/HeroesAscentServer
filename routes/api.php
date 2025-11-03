<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\CharacterController;

// 🔧 Test server
Route::get('/ping', function () {
    return response()->json(['status' => 'ok', 'message' => 'pong']);
});

// Registration using GW2 API key
Route::post('/register', [RegistrationController::class, 'register']);

// Auth using contest token
Route::post('/auth', [RegistrationController::class, 'authenticate']);

// Character lifecycle
Route::post('/character/register', [CharacterController::class, 'register']);
Route::post('/character/update', [CharacterController::class, 'update']);
