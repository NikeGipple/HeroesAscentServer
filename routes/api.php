<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\CharacterController;

// 🔧 Test server
Route::get('/status', function () {
    return response()->json(['status' => 'ok']); 
});

// === Account routes ===
Route::group([
    'prefix' => 'account'
], function () {

    Route::post('/register', [RegistrationController::class, 'register'])
        ->name('account-register');

    Route::post('/check', [RegistrationController::class, 'check'])
        ->name('account-check');
});


// === Character lifecycle routes ===
Route::group([
    'prefix' => 'character'
], function () {

    Route::post('/register', [CharacterController::class, 'register'])
        ->name('character-register');

    Route::post('/update', [CharacterController::class, 'update'])
        ->name('character-update');
});