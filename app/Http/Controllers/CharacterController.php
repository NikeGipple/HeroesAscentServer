<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CharacterController extends Controller
{
    public function update(Request $request)
    {
        Log::info("=== Incoming Character Update ===\n" . json_encode([
            'ip'      => $request->ip(),
            'time'    => now()->toDateTimeString(),
            'headers' => $request->headers->all(),
            'payload' => $request->all(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return response()->json([
            "status"         => "ok",
            "rules_valid"    => false,
            "violation_code" => "RULE_FOOD_001"
        ]);
    }
}
