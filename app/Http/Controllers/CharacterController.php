<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Account;
use App\Models\Character;
use App\Models\CharacterEvent;
use App\Models\EventType;

class CharacterController extends Controller
{
    public function update(Request $request)
    {
        // === Log ricezione richiesta in formato leggibile ===
        Log::info("=== Incoming Character Update ===\n" . json_encode([
            'ip'      => $request->ip(),
            'time'    => now()->toDateTimeString(),
            'payload' => $request->all(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // === 2Validazione minima ===
        $data = $request->validate([
            'token'         => 'required|string',
            'name'          => 'required|string',
            'map_id'        => 'required|integer',
            'state'         => 'required|integer',
            'is_login'      => 'boolean',
        ]);

        // === Trova o crea account e personaggio ===
        $account = Account::where('account_token', $data['token'])->first();

        if (!$account) {
            Log::warning("Account non trovato per token: {$data['token']}");
            return response()->json([
                "status"  => "error",
                "message" => "Account non registrato",
            ], 404);
        }

        $character = Character::firstOrCreate(
            ['name' => $data['name']],
            [
                'account_id' => $account->id,
                'profession' => $request->input('profession'),
                'level'      => 0,
                'score'      => 0,
            ]
        );

        // Aggiorna lo snapshot corrente
        $character->updateSnapshot($request->integer('map_id'), $request->integer('state'));

        // === Se è un login, registra evento LOGIN_START ===
        if ($request->boolean('is_login')) {
            CharacterEvent::record($character, 'LOGIN_START', [
                'is_login'   => true,
                'map_id'     => $request->integer('map_id'),
                'map_type'   => $request->integer('map_type'),
                'profession' => $request->integer('profession'),
                'elite_spec' => $request->integer('elite_spec'),
                'race'       => $request->integer('race'),
                'state'      => $request->integer('state'),
                'group_type' => $request->integer('group_type'),
                'group_count'=> $request->integer('group_count'),
                'commander'  => $request->boolean('commander'),
                'pos_x'      => $request->input('position.x'),
                'pos_y'      => $request->input('position.y'),
                'pos_z'      => $request->input('position.z'),
            ]);

            Log::info("Evento di login registrato per {$character->name}");
        }

        // === Regole o violazioni simulate (per ora) ===
        // Qui puoi aggiungere controlli runtime e creare eventi violazione:
        // Esempio: se elite_spec vietata
        // if (in_array($request->integer('elite_spec'), [67, 62, 64])) {
        //     CharacterEvent::record($character, 'RULE_ELITE_SPEC', [
        //         'details' => 'Uso di specializzazione Elite non consentita',
        //         'map_id'  => $request->integer('map_id'),
        //     ]);

        //     return response()->json([
        //         "status"         => "ok",
        //         "rules_valid"    => false,
        //         "violation_code" => "RULE_ELITE_SPEC",
        //     ]);
        // }

        // === 6️⃣ Tutto regolare ===
        return response()->json([
            "status"         => "ok",
            "rules_valid"    => true,
            "violation_code" => null
        ]);
    }
}



// return response()->json([
//     "status"         => "ok",
//     "rules_valid"    => false,
//     "violation_code" => "RULE_FOOD_001"
// ]);