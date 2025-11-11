<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\CharacterController;

// 🔧 Test server
Route::get('/status', function () {
    return response()->json(['status' => 'ok']); 
});

// Registration using GW2 API key
Route::post('/register', [RegistrationController::class, 'register']);

Route::post('/check', [RegistrationController::class, 'check']);

// Auth using contest token
Route::post('/auth', [RegistrationController::class, 'authenticate']);

// Character lifecycle
Route::post('/character/register', [CharacterController::class, 'register']);
Route::post('/character/update', [CharacterController::class, 'update']);
