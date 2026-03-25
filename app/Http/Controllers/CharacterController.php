<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use App\Models\Character;
use App\Models\EventType;
use App\Models\CharacterEvent;
use App\Services\CharacterEventRecorder;
use App\Http\Requests\CharacterUpdateRequest;
use App\Services\CharacterEventWorkflow;

class CharacterController extends Controller
{
    public function __construct(private CharacterEventWorkflow $workflow)
    {
    }

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
                'code' => $lastViolation->event_code,
            ] : null,
        ], 403);
    }

    /**
     * Riceve gli eventi dal client del giocatore, applica le regole di gara
     * (validazione, normalizzazione mappe/buff, controlli di coerenza) e
     * registra l’evento aggiornando eventuale squalifica o progressione.
     */
    public function update(CharacterUpdateRequest $request)
    {
        Log::info("=== Incoming Character Update ===", [
            'ip'      => $request->ip(),
            'payload' => $request->all(),
        ]);

        // 1) Validazione payload
        $data = $request->validated();

        // 2) Account lookup
        $account = $this->workflow->findAccount($data['token'], $request->ip());
        if (!$account) {
            return response()->json(['status' => 'error', 'message' => 'Account not registered'], 404);
        }

        $bypass = $account->isBypass();
        $eventCode = $this->workflow->normalizeEventCode($data);

        // 3) Personaggio: trova o crea al primo LOGIN livello 1
        $character = $this->workflow->findOrCreateCharacter($account, $data, $eventCode);
        if (!$character) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Character not found',
            ], 404);
        }

        // 4) Gestione logout rapida
        if ($eventCode === 'LOGOUT') {
            Log::info("?? LOGOUT ricevuto per {$data['name']}", [
                'account_id'   => $account->id,
                'character_id' => $character->id ?? null,
            ]);

            CharacterEvent::record($character, 'LOGOUT', [
                'details' => 'Client event: LOGOUT',
                'map_id'  => $data['map_id'] ?? null,
                'state'   => $data['state'] ?? null,
            ], $bypass);

            return response()->json([
                'status' => 'ok',
                'event'  => [
                    'code'         => 'LOGOUT',
                    'points'       => 0,
                    'is_critical'  => false,
                    'disqualified' => (!$bypass && $character->isDisqualified()),
                ],
            ]);
        }

        if ($eventCode === 'LOGOUT_LOW_HP') {
            Log::warning("?? LOGOUT_LOW_HP rilevato per {$data['name']}", [
                'account_id'   => $account->id,
                'character_id' => $character->id ?? null,
                'state'        => $data['state'] ?? null,
                'map_id'       => $data['map_id'] ?? null,
            ]);

            CharacterEvent::record($character, 'LOGOUT_LOW_HP', [
                'details' => 'Client event: LOGOUT_LOW_HP',
                'map_id'  => $data['map_id'] ?? null,
                'state'   => $data['state'] ?? null,
            ], $bypass);

            $startOfDay = now()->startOfDay();
            $limit = 1;

            $recent = $character->events()
                ->where('event_code', 'LOGOUT_LOW_HP')
                ->where('detected_at', '>=', $startOfDay)
                ->count();

            if ($recent > $limit) {
                Log::error("? ABUSO LOGOUT_LOW_HP per {$character->name}", [
                    'character_id' => $character->id,
                    'count'        => $recent,
                ]);

                CharacterEvent::record($character, 'ABUSE_LOGOUT_LOW_HP', [
                    'details' => "System event: ABUSE_LOGOUT_LOW_HP events",
                    'map_id'  => $data['map_id'] ?? null,
                    'state'   => $data['state'] ?? null,
                ], $bypass);

                if (!$bypass) {
                    return $this->buildDisqualifiedResponse($character);
                }

                return response()->json([
                    'status' => 'ok',
                    'event'  => [
                        'code'         => 'ABUSE_LOGOUT_LOW_HP',
                        'points'       => 0,
                        'is_critical'  => false,
                        'disqualified' => (!$bypass && $character->isDisqualified()),
                    ],
                ]);
            }

            return response()->json([
                'status' => 'ok',
                'event'  => [
                    'code'         => 'LOGOUT_LOW_HP',
                    'points'       => 0,
                    'is_critical'  => false,
                    'disqualified' => (!$bypass && $character->isDisqualified()),
                ],
            ]);
        }

        // 5) Mappa e buff: normalizza o blocca l'evento
        $eventCode = $this->workflow->validateMapEvent($eventCode, $data);

        $buffCheck = $this->workflow->translateBuffEvent($eventCode, $data);
        if ($buffCheck['error']) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unknown BUFF_APPLIED payload',
            ], 400);
        }
        $eventCode = $buffCheck['code'];

        // 6) Tipo evento e stato squalifica pre-esistente
        $eventType = EventType::where('code', $eventCode)->first();
        if (!$eventType) {
            Log::warning("?? Unknown event type received: {$eventCode}", ['payload' => $data]);
            return response()->json(['status' => 'error', 'message' => "Unknown event type: {$eventCode}"], 400);
        }

        if (!$bypass && $character->isDisqualified()) {
            Log::warning("? Event rejected — character is disqualified", [
                'character' => $character->name,
                'event'     => $eventCode,
            ]);

            return $this->buildDisqualifiedResponse($character);
        }

        // 7) Controlli di coerenza sul payload
        $state = (int) $data['state'];
        $errors = $this->workflow->validateIntegrity($eventCode, $state, $data, $request);
        if (!empty($errors)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Payload failed integrity checks',
                'errors'  => $errors,
            ], 400);
        }

        // 8) Registrazione evento e contesto
        $context = CharacterEventRecorder::buildContext($data, $eventCode, $state);
        $event   = CharacterEventRecorder::recordEvent($character, $eventCode, $context, $bypass);

        // 9) Progressione livello
        if ($eventCode === 'LEVEL_UP') {
            $newLevel     = (int) ($data['level'] ?? 0);
            $currentLevel = (int) $character->level;

            $allowed = false;
            if ($currentLevel === 1 && $newLevel === 3) {
                $allowed = true;
            }
            if ($newLevel === $currentLevel + 1) {
                $allowed = true;
            }

            if (!$allowed) {
                Log::warning("?? Level jump detected!", [
                    'character'     => $character->name,
                    'current_level' => $currentLevel,
                    'requested'     => $newLevel,
                ]);

                return response()->json([
                    'status'  => 'error',
                    'message' => 'Invalid level progression',
                ], 400);
            }

            $character->level = $newLevel;
            $character->save();
        }

        // 10) Check squalifica dopo evento critico
        if (!$bypass && $character->isDisqualified()) {
            return $this->buildDisqualifiedResponse($character);
        }

        // 11) Log strutturato dell'esito
        $this->workflow->logOutcome($eventCode, $character, $account, $data, $event);

        return response()->json([
            'status' => 'ok',
            'event'  => [
                'code'         => $event->event_code,
                'points'       => $event->points,
                'is_critical'  => $event->eventType->is_critical ?? false,
                'disqualified' => (!$bypass && $character->isDisqualified()),
            ],
        ]);
    }

    
}
