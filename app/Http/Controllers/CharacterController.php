<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CharacterController extends Controller
{
    public function update(Request $request)
    {
        // === Log dettagliato di debug ===
        Log::info('=== Incoming Character Update ===', [
            'ip'       => $request->ip(),
            'time'     => now()->toDateTimeString(),
            'headers'  => $request->headers->all(),
            'payload'  => $request->all(),   // tutti i dati JSON ricevuti
            'raw'      => $request->getContent(), // corpo grezzo per sicurezza
        ]);

        // === Risposta temporanea di test ===
        return response()->json([
            "status"         => "ok",
            "rules_valid"    => false,
            "violation_code" => "RULE_FOOD_001"
        ]);
    }
}
