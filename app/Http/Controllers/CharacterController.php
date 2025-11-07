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
        // // === Log for debug ===
        // Log::info("=== Incoming Character Update ===", [
        //     'ip'      => $request->ip(),
        //     'payload' => $request->all(),
        // ]);

        $data = $request->validate([
            'token'    => 'required|string',
            'name'     => 'required|string',
            'map_id'   => 'required|integer',
            'state'    => 'required|integer',
        ]);

        $account = Account::where('account_token', $data['token'])->first();
        if (!$account) {
            Log::warning("Account not found for token: {$data['token']}");
            return response()->json([
                "status"  => "error",
                "message" => "Account not registered",
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

        $character->updateSnapshot($request->integer('map_id'), $request->integer('state'));

        $state = $request->integer('state');
        $context = [
            'map_id'      => $request->integer('map_id'),
            'map_type'    => $request->integer('map_type'),
            'profession'  => $request->integer('profession'),
            'elite_spec'  => $request->integer('elite_spec'),
            'race'        => $request->integer('race'),
            'state'       => $state,
            'group_type'  => $request->integer('group_type'),
            'group_count' => $request->integer('group_count'),
            'commander'   => $request->boolean('commander'),
            'is_login'    => $request->boolean('is_login'),
            'pos_x'       => $request->input('position.x'),
            'pos_y'       => $request->input('position.y'),
            'pos_z'       => $request->input('position.z'),
        ];

        // === Determine event type ===
        if ($state & 2) { // CS_IsDowned
            $eventCode = 'DOWNED';
            $type = 'death';
            $context['details'] = 'The character has been downed.';
        } elseif ($state === 0) {
            $eventCode = 'DEATH';
            $type = 'death';
            $context['details'] = 'The character has died.';
        } elseif ($request->boolean('is_login')) {
            $eventCode = 'LOGIN';
            $type = 'login';
            $context['details'] = 'Initial login event detected.';
        } else {
            $eventCode = 'STATUS_UPDATE';
            $type = 'info';
            $context['details'] = 'Periodic state update received.';
        }

        // === Retrieve event definition ===
        $eventType = EventType::where('code', $eventCode)->first();

        // === Save event ===
        CharacterEvent::record($character, $eventCode, array_merge($context, [
            'type' => $type,
        ]));

        Log::info("Event recorded: {$eventCode} for {$character->name}");

        // === Determine rule status ===
        $rulesValid = !($eventType && $eventType->is_critical);

        return response()->json([
            "status"      => "ok",
            "event"       => $eventCode,
            "rules_valid" => $rulesValid,
        ]);
    }
}
