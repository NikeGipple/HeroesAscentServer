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
        Log::info("=== Incoming Character Update ===", [
            'ip'      => $request->ip(),
            'payload' => $request->all(),
        ]);

        // validazione base
        $data = $request->validate([
            'token'       => 'required|string',
            'name'        => 'required|string',
            'event'       => 'required|string',
            'map_id'      => 'required|integer',
            'state'       => 'required|integer',
            'map_type'    => 'sometimes|integer',
            'profession'  => 'sometimes|integer',
            'elite_spec'  => 'sometimes|integer',
            'race'        => 'sometimes|integer',
            'group_type'  => 'sometimes|integer',
            'group_count' => 'sometimes|integer',
            'commander'   => 'sometimes|boolean',
            'mount'       => 'sometimes|integer',
            'is_login'    => 'sometimes|boolean',
            'position.x'  => 'sometimes|numeric',
            'position.y'  => 'sometimes|numeric',
            'position.z'  => 'sometimes|numeric',
        ]);

        // Account
        $account = Account::where('account_token', $data['token'])->first();
        if (!$account) {
            Log::warning("Account not found for token: {$data['token']}");
            return response()->json(['status' => 'error', 'message' => 'Account not registered'], 404);
        }

        // Normalizza event code
        $eventCode = strtoupper($data['event']);

        // Verifica esistenza EventType in DB
        $eventType = EventType::where('code', $eventCode)->first();
        if (!$eventType) {
            Log::warning("Unknown event type received: {$eventCode}", ['payload' => $data]);
            return response()->json(['status' => 'error', 'message' => "Unknown event type: {$eventCode}"], 400);
        }

        // Recupera o crea character
        $character = Character::firstOrCreate(
            ['name' => $data['name']],
            [
                'account_id' => $account->id,
                'profession' => $data['profession'] ?? null,
                'level'      => 0,
                'score'      => 0,
            ]
        );

        // Se il character è già squalificato, blocchiamo ulteriori eventi (opzionale)
        if ($character->isDisqualified()) {
            Log::warning("Event rejected for disqualified character", ['name' => $character->name, 'event' => $eventCode]);
            return response()->json(['status' => 'error', 'message' => 'Character is disqualified'], 403);
        }

        // Bit di stato (RTAPI.h)
        $CS_IS_ALIVE  = 1 << 0; // 1
        $CS_IS_DOWNED = 1 << 1; // 2

        $state = (int) $data['state'];

        // CONTROLLI DI COERENZA PER TIPO EVENTO
        $errors = [];

        // controlli specifici
        switch ($eventCode) {
            case 'LOGIN':
                if (array_key_exists('is_login', $data) && !$request->boolean('is_login')) {
                    $errors[] = 'Payload says event=LOGIN but is_login flag is false';
                }
                break;

            case 'DOWNED':
                if (($state & $CS_IS_DOWNED) === 0) {
                    $errors[] = 'State bit does not indicate DOWNED';
                }
                break;

            case 'DEAD':
                if (($state & $CS_IS_ALIVE) !== 0) {
                    $errors[] = 'State bit indicates alive while event is DEAD';
                }
                break;

            case 'RESPAWN':
                if (($state & $CS_IS_ALIVE) === 0) {
                    $errors[] = 'State bit does not indicate alive while event is RESPAWN';
                }
                break;

            case 'MOUNT_CHANGED':
                if (!array_key_exists('mount', $data)) {
                    $errors[] = 'Missing mount index for MOUNT_CHANGED';
                }
                break;

            case 'MAP_CHANGED':
                // richiediamo map_type e map_id (map_id è già required nella validazione)
                if (!array_key_exists('map_type', $data)) {
                    $errors[] = 'Missing map_type for MAP_CHANGED';
                }
                break;

            default:
                // per altri tipi, nessun controllo specifico di default
                break;
        }

        if (!empty($errors)) {
            Log::warning("Payload failed integrity checks", ['name' => $character->name, 'event' => $eventCode, 'errors' => $errors, 'payload' => $data]);
            return response()->json(['status' => 'error', 'message' => 'Payload failed integrity checks', 'errors' => $errors], 400);
        }

        // Costruisci contesto da salvare (rinforzato)
        $context = [
            'map_id'      => (int)$data['map_id'],
            'map_type'    => $data['map_type'] ?? null,
            'profession'  => $data['profession'] ?? null,
            'elite_spec'  => $data['elite_spec'] ?? null,
            'race'        => $data['race'] ?? null,
            'state'       => $state,
            'group_type'  => $data['group_type'] ?? null,
            'group_count' => $data['group_count'] ?? null,
            'commander'   => $data['commander'] ?? false,
            'is_login'    => ($eventCode === 'LOGIN'),
            'pos_x'       => $data['position']['x'] ?? null,
            'pos_y'       => $data['position']['y'] ?? null,
            'pos_z'       => $data['position']['z'] ?? null,
            'mount_index' => $data['mount'] ?? null,
            'details'     => $data['details'] ?? ("Client event: {$eventCode}"),
        ];

        // registra l'evento (modello gestisce points e squalifica)
        $event = CharacterEvent::record($character, $eventCode, $context);

        Log::info("Event recorded for {$character->name}", [
            'event'       => $event->event_code,
            'points'      => $event->points,
            'is_critical' => $event->eventType->is_critical ?? false,
        ]);

        return response()->json([
            'status'  => 'ok',
            'event'   => [
                'code'         => $event->event_code,
                'points'       => $event->points,
                'is_critical'  => $event->eventType->is_critical ?? false,
                'disqualified' => $character->isDisqualified(),
            ],
        ]);
    }
}
