<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RegistrationController extends Controller
{
    public function register(Request $request)
    {
        return response()->json(['status' => 'ok', 'message' => 'registered']);
    }

    public function authenticate(Request $request)
    {
        return response()->json(['status' => 'ok', 'message' => 'authenticated']);
    }
}
