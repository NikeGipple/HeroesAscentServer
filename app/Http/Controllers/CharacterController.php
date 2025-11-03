<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CharacterController extends Controller
{
    public function update(Request $request)
    {
        return response()->json([
            "status" => "ok",
            "rules_valid" => false,
            "violation_code" => "RULE_FOOD_001"
        ]);
    }
}
