<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Account;
use App\Models\Character;
use App\Models\CharacterEvent;
use App\Models\EventType;
use App\Models\ForbiddenMap;

class CharacterController extends Controller
{
    public function update(Request $request)
    {
        Log::info("=== Incoming Character Update ===", [
            'ip'      => $request->ip(),
            'payload' => $request->all(),
        ]);

        // 1. Validazione base
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

        // 2. Account lookup
        $account = Account::where('account_token', $data['token'])->first();
        if (!$account) {
            Log::warning("❌ Account not found for provided token", [
                'ip' => $request->ip(),
                'token' => substr($data['token'], 0, 12) . '...',
            ]);
            return response()->json(['status' => 'error', 'message' => 'Account not registered'], 404);
        }

        // Normalizza codice evento
        $eventCode = strtoupper($data['event']);

        
        // === CONTROLLO MAPPE VIETATE ===
        if ($eventCode === 'MAP_CHANGED') {

            $forbidden = ForbiddenMap::where('map_id', (int)$data['map_id'])->first();

            if ($forbidden) {

                Log::warning("⛔ Mappa Vietata Rilevata!", [
                    'character' => $data['name'],
                    'map_id'    => $data['map_id'],
                    'map_name'  => $forbidden->name,
                    'type'      => $forbidden->type,
                ]);

                // Sovrascrivi l'evento
                $eventCode = 'MAP_CHANGED_INVALID';
            }
        }

        // 3. Verifica tipo evento
        $eventType = EventType::where('code', $eventCode)->first();
        if (!$eventType) {
            Log::warning("⚠️ Unknown event type received: {$eventCode}", ['payload' => $data]);
            return response()->json(['status' => 'error', 'message' => "Unknown event type: {$eventCode}"], 400);
        }

        // 4. Recupera o crea il personaggio
        $character = Character::firstOrCreate(
            ['name' => $data['name']],
            [
                'account_id' => $account->id,
                'profession' => $data['profession'] ?? null,
                'level'      => 0,
                'score'      => 0,
            ]
        );

        // 5. Controllo squalifica
        if ($character->isDisqualified()) {
            Log::warning("❌ Event rejected — character is disqualified", [
                'character' => $character->name,
                'event'     => $eventCode,
            ]);
            return response()->json(['status' => 'error', 'message' => 'Character is disqualified'], 403);
        }

        // Bit di stato
        $CS_IS_ALIVE  = 1 << 0;
        $CS_IS_DOWNED = 1 << 1;
        $state = (int) $data['state'];
        $errors = [];

        // 6. Controlli specifici di coerenza per tipo evento
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
                if (!array_key_exists('map_type', $data)) {
                    $errors[] = 'Missing map_type for MAP_CHANGED';
                }
                break;
            case 'MAP_CHANGED_INVALID':
                if (!array_key_exists('map_type', $data)) {
                    $errors[] = 'Missing map_type for MAP_CHANGED_INVALID';
                }
                break;
            case 'HEALING_USED':
                break;
        }

        if (!empty($errors)) {
            Log::warning("⚠️ Payload failed integrity checks", [
                'character' => $character->name,
                'event'     => $eventCode,
                'errors'    => $errors,
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Payload failed integrity checks',
                'errors' => $errors,
            ], 400);
        }

        // 🗺️ 7. Costruisci contesto da salvare
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

        // 8. Registra l'evento
        $event = CharacterEvent::record($character, $eventCode, $context);
        $character->refresh();

        if ($character->isDisqualified()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Character is disqualified'
            ], 403);
        }

        Log::info("✅ Event recorded for {$character->name}", [
            'event'       => $event->event_code,
            'points'      => $event->points,
            'is_critical' => $event->eventType->is_critical ?? false,
            'account_id'  => $account->id,
        ]);

        // 🔔 9. Log extra per eventi importanti
        if ($eventCode === 'LOGIN') {
            Log::info("🔑 Character {$character->name} logged in successfully", [
                'account_name' => $account->account_name,
                'map_id' => $data['map_id'],
            ]);
        } elseif ($eventCode === 'DEAD') {
            Log::warning("💀 Character {$character->name} has died", [
                'map_id' => $data['map_id'],
            ]);
        } elseif ($eventCode === 'RESPAWN') {
            Log::info("ℹ️ Character {$character->name} has respawned", [
                'map_id' => $data['map_id'],
            ]);
        } elseif ($eventCode === 'MAP_CHANGED') {
            Log::info("ℹ️ Character {$character->name} changed map", [
                'new_map_id' => $data['map_id'],
            ]);
        } elseif ($eventCode === 'MAP_CHANGED_INVALID') {
            Log::warning("🚫 Character {$character->name} entered a FORBIDDEN MAP!", [
                'map_id' => $data['map_id'],
            ]);
        }

        // ✅ 10. Risposta finale
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
