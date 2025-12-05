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
    /**
     * Costruisce la risposta standard quando il personaggio è squalificato.
     */
    private function buildDisqualifiedResponse(Character $character)
    {
        $lastViolation = $character
            ->events()
            ->whereHas('eventType', fn($q) => $q->where('is_critical', true))
            ->latest('detected_at')
            ->first();

        return response()->json([
            'status'  => 'error',
            'message' => 'Character is disqualified',
            'last_violation' => $lastViolation ? [
                'code'        => $lastViolation->event_code,
            ] : null,
        ], 403);
    }
    
    /**
     * Riceve e valida un aggiornamento dal client, identifica il personaggio,
     * applica i controlli sulle regole, registra l’evento e gestisce
     * automaticamente la squalifica in caso di violazioni critiche.
     *
     * Restituisce:
     * - status:ok per eventi validi
     * - status:error con ultima violazione se il personaggio è squalificato
     */
    public function update(Request $request)
    {
        Log::info("=== Incoming Character Update ===", [
            'ip'      => $request->ip(),
            'payload' => $request->all(),
        ]);

        // 1. Validazione base
        $data = $request->validate([
            'token'             => 'required|string',
            'name'              => 'required|string',
            'event'             => 'required|string',
            'map_id'            => 'required|integer',
            'state'             => 'required|integer',
            'map_type'          => 'sometimes|integer',
            'profession'        => 'sometimes|integer',
            'elite_spec'        => 'sometimes|integer',
            'race'              => 'sometimes|integer',
            'group_type'        => 'sometimes|integer',
            'group_count'       => 'sometimes|integer',
            'commander'         => 'sometimes|boolean',
            'mount'             => 'sometimes|integer',
            'is_login'          => 'sometimes|boolean',
            'position.x'        => 'sometimes|numeric',
            'position.y'        => 'sometimes|numeric',
            'position.z'        => 'sometimes|numeric',
            'level'             => 'sometimes|integer',
            'effective_level'   => 'sometimes|integer',
            'buff_id'           => 'sometimes|integer',
            'buff_name'         => 'sometimes|string',
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

        // // === EVENTO FORCED_LOGOUT ===
        // if ($eventCode === 'FORCED_LOGOUT') {

        //     Log::info("🔻 FORCED_LOGOUT ricevuto per account {$account->id}");

        //     // Trova l’ultimo personaggio che ha fatto LOGIN
        //     $character = Character::where('account_id', $account->id)
        //         ->whereHas('events', function ($q) {
        //             $q->where('event_code', 'LOGIN');
        //         })
        //         ->with(['events' => function ($q) {
        //             $q->where('event_code', 'LOGIN')->latest('detected_at');
        //         }])
        //         ->get()
        //         ->sortByDesc(fn($c) => optional($c->events->first())->detected_at)
        //         ->first();

        //     if (!$character) {
        //         Log::warning("⚠️ Nessun personaggio trovato con un login precedente", [
        //             'account_id' => $account->id
        //         ]);

        //         return response()->json([
        //             'status' => 'ok',
        //             'message' => 'No character with recorded LOGIN found — nothing to logout.',
        //         ]);
        //     }

        //     // Registra l'evento di logout forzato
        //     CharacterEvent::record($character, 'FORCED_LOGOUT', [
        //         'details' => 'Client event: FORCED_LOGOUT',
        //     ]);

        //     Log::info("🔻 FORCED_LOGOUT registrato per {$character->name}");

        //     return response()->json([
        //         'status' => 'ok',
        //         'event' => [
        //             'code' => 'FORCED_LOGOUT',
        //             'points' => 0,
        //             'is_critical' => false,
        //             'disqualified' => $character->isDisqualified(),
        //         ],
        //     ]);
        // }


        // 4. Recupera o crea il personaggio
        $isNewCharacter = ($eventCode === 'LOGIN' && isset($data['level']) && (int)$data['level'] === 1);

        if ($isNewCharacter) {

            // Creiamo un nuovo personaggio
            $character = Character::create([
                'name'        => $data['name'],
                'account_id'  => $account->id,
                'profession'  => $data['profession'] ?? null,
                'level'       => 1,
                'score'       => 0,
            ]);

        } else {

            // Recuperiamo il personaggio esistente
            $character = Character::where('name', $data['name'])
                ->latest('id')
                ->first();

                if (!$character) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Character not found',
                    ], 404);
                }
        }
        
        // === EVENTO LOGOUT ===
        if ($eventCode === 'LOGOUT') {

            Log::info("🔻 LOGOUT ricevuto per {$data['name']}", [
                'account_id' => $account->id,
                'character_id' => $character->id ?? null,
            ]);

            CharacterEvent::record($character, 'LOGOUT', [
                'details' => 'Client event: LOGOUT',
                'map_id'  => $data['map_id'] ?? null,
                'state'   => $data['state'] ?? null,
            ]);

            return response()->json([
                'status' => 'ok',
                'event' => [
                    'code' => 'LOGOUT',
                    'points' => 0,
                    'is_critical' => false,
                    'disqualified' => $character->isDisqualified(),
                ],
            ]);
        }


        // === CONTROLLO MAPPE VIETATE ===
        if ($eventCode === 'MAP_CHANGED') {

            $mapId   = (int)$data['map_id'];
            $mapType = (int)($data['map_type'] ?? -1);

            $forbidden = ForbiddenMap::where('map_id', $mapId)->first();

            // 🔥 1) MapType == 2 ovvero sPvP → automaticamente vietata
            if ($mapType === 2) {

                Log::warning("⛔ Mappa sPvP rilevata!", [
                    'character' => $data['name'],
                    'map_id'    => $mapId,
                    'map_type'  => $mapType,
                ]);

                $eventCode = 'MAP_CHANGED_INVALID';
            }
            // 🔥 2) Mappa presente nella lista ForbiddenMap
            elseif ($forbidden) {

                Log::warning("⛔ Mappa Vietata Rilevata!", [
                    'character' => $data['name'],
                    'map_id'    => $mapId,
                    'map_name'  => $forbidden->name,
                    'type'      => $forbidden->type,
                ]);

                $eventCode = 'MAP_CHANGED_INVALID';
            }
        }


        // === BUFF PROIBITI (Cibo, Enhancement, Reinforced Armor) ===
        if ($eventCode === 'BUFF_APPLIED') {

            $buffId   = (int)($data['buff_id'] ?? 0);
            $buffName = strtolower($data['buff_name'] ?? '');

            // Nourishment (Cibo)
            if ($buffName === 'nourishment') {
                $eventCode = 'BUFF_FORBIDDEN_FOOD';
            }

            // Enhancement (Utility)
            elseif ($buffName === 'enhancement') {
                $eventCode = 'BUFF_FORBIDDEN_ENHANCEMENT';
            }

            // Reinforced Armor (ID 9283)
            elseif ($buffId === 9283) {
                $eventCode = 'BUFF_FORBIDDEN_REINFORCED';
            }

            // Se buff non riconosciuto: rifiutiamo
            else {
                Log::warning("⚠️ BUFF_APPLIED ricevuto ma non riconosciuto", [
                    'buff_id' => $buffId,
                    'buff_name' => $buffName,
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Unknown BUFF_APPLIED payload',
                ], 400);
            }
        }


        // 3. Verifica tipo evento
        $eventType = EventType::where('code', $eventCode)->first();
        if (!$eventType) {
            Log::warning("⚠️ Unknown event type received: {$eventCode}", ['payload' => $data]);
            return response()->json(['status' => 'error', 'message' => "Unknown event type: {$eventCode}"], 400);
        }

        // 5. Controllo squalifica PRIMA di registrare nuovi eventi
        if ($character->isDisqualified()) {
            Log::warning("❌ Event rejected — character is disqualified", [
                'character' => $character->name,
                'event'     => $eventCode,
            ]);

            return $this->buildDisqualifiedResponse($character);
        }



        // Bit di stato
        $CS_IS_ALIVE  = 1 << 0;
        $CS_IS_DOWNED = 1 << 1;
        $CS_IS_GLIDING = 1 << 5;
        $state = (int) $data['state'];
        $errors = [];

        // 6. Controlli specifici di coerenza per tipo evento
        switch ($eventCode) {
            case 'LOGIN':
                if (array_key_exists('is_login', $data) && !$request->boolean('is_login')) {
                    $errors[] = 'Payload says event=LOGIN but is_login flag is false';
                }
                break;
            
            case 'LOGOUT':
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

            case 'MOUNT_CHANGED':
                if (!array_key_exists('mount', $data)) {
                    $errors[] = 'Missing mount index for MOUNT_CHANGED';
                }
                break;
            case 'GLIDING':
                if (($state & $CS_IS_GLIDING) === 0) {
                    $errors[] = 'State bit does not indicate gliding while event is GLIDING';
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
            case 'LEVEL_UP':
                if (!array_key_exists('level', $data)) {
                    $errors[] = 'Missing level for LEVEL_UP';
                }
                break;
            case 'HEALING_USED':
                break;
            case 'GROUP':
                $gt = (int)($data['group_type'] ?? 0);
                $gc = (int)($data['group_count'] ?? 0);

                if ($gt === 0 && $gc === 0) {
                    $errors[] = 'Invalid GROUP event: both group_type and group_count are 0';
                }
                break;


            case 'BUFF_APPLIED':
                if (!array_key_exists('buff_id', $data)) {
                    $errors[] = 'Missing buff_id for BUFF_APPLIED';
                }
                if (!array_key_exists('buff_name', $data)) {
                    $errors[] = 'Missing buff_name for BUFF_APPLIED';
                }
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
            'level'            => $data['level'] ?? null,
            'effective_level'  => $data['effective_level'] ?? null,
            'details'     => $data['details'] ?? ("Client event: {$eventCode}"),
            'buff_id'   => $data['buff_id'] ?? null,
            'buff_name' => $data['buff_name'] ?? null,
        ];

        // 8. Registra l'evento
        $event = CharacterEvent::record($character, $eventCode, $context);
        $character->refresh();

        // === Aggiorna il livello del personaggio ===
        if ($eventCode === 'LEVEL_UP') {

            $newLevel = (int)($data['level'] ?? 0);
            $currentLevel = (int)$character->level;

            $allowed = false;

            // Caso speciale: da 1 → 3 permesso
            if ($currentLevel === 1 && $newLevel === 3) {
                $allowed = true;
            }

            // Caso normale: +1 livello
            if ($newLevel === $currentLevel + 1) {
                $allowed = true;
            }

            if (!$allowed) {

                Log::warning("🚫 Level jump detected!", [
                    'character'      => $character->name,
                    'current_level'  => $currentLevel,
                    'requested'      => $newLevel,
                ]);

                return response()->json([
                    'status'  => 'error',
                    'message' => 'Invalid level progression',
                ], 400);
            }

            // Ok → aggiorna livello nel DB
            $character->level = $newLevel;
            $character->save();
        }



        // Dopo un evento critico il personaggio potrebbe essere appena stato squalificato
        if ($character->isDisqualified()) {
            return $this->buildDisqualifiedResponse($character);
        }

        Log::info("✅ Event recorded for {$character->name}", [
            'event'       => $event->event_code,
            'points'      => $event->points,
            'is_critical' => $event->eventType->is_critical ?? false,
            'account_id'  => $account->id,
        ]);

        // 9. Log per eventi 
        if ($eventCode === 'LOGIN') {
            Log::info("🔑 Character {$character->name} logged in successfully", [
                'account_name' => $account->account_name,
                'map_id' => $data['map_id'],
            ]);
        
        } elseif ($eventCode === 'LOGOUT') {
            Log::info("🔑 Character {$character->name} logged out", [
                'map_id' => $data['map_id'] ?? null,
            ]);
        } elseif ($eventCode === 'LEVEL_UP') {
            Log::info("🎉 Level Up! {$character->name} è salito al livello {$data['level']}", [
                'level'            => $data['level'] ?? null,
                'map_id'           => $data['map_id'],
            ]);
        } elseif ($eventCode === 'DOWNED') {
            Log::warning("🩸 Character {$character->name} is DOWNED", [
                'map_id' => $data['map_id'],
            ]);
        } elseif ($eventCode === 'DEAD') {
            Log::warning("💀 Character {$character->name} has died", [
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
        } elseif ($eventCode === 'MOUNT_CHANGED') {
            Log::warning("🐎❌ MOUNT usage detected for {$character->name}", [
                'mount_index' => $data['mount'] ?? null,
            ]);
        } elseif ($eventCode === 'GLIDING') {
            Log::warning("🌪️❌ GLIDING rilevato per {$character->name}");

        } elseif ($eventCode === 'HEALING_USED') {
            Log::warning("❤️‍🩹❌ HEALING SKILL used by {$character->name}");

        } elseif ($eventCode === 'MAP_CHANGED_INVALID') {
            Log::warning("🚫 Character {$character->name} entered a FORBIDDEN MAP!", [
                'map_id' => $data['map_id'],
            ]);
            
        } elseif ($eventCode === 'BUFF_FORBIDDEN_FOOD') {
            Log::warning("🍗❌ CIBO vietato per {$character->name}");
        }
        elseif ($eventCode === 'BUFF_FORBIDDEN_ENHANCEMENT') {
            Log::warning("⚡❌ ENHANCEMENT vietato per {$character->name}");
        }
        elseif ($eventCode === 'BUFF_FORBIDDEN_REINFORCED') {
            Log::warning("🛡️❌ REINFORCED ARMOR rilevato per {$character->name}");
        } 
        elseif ($eventCode === 'GROUP') {
            Log::warning("🚫 Player {$character->name} is in a GROUP!", [
                'group_type'  => $data['group_type'] ?? null,
                'group_count' => $data['group_count'] ?? null,
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
