<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Models\Account;
use App\Models\Character;
use App\Models\CharacterEvent;
use App\Models\EventType;
use App\Models\ForbiddenMap;
use App\Http\Requests\CharacterUpdateRequest;

class CharacterEventWorkflow
{
    /**
     * Recupera l'account dal token; logga e ritorna null se non trovato.
     */
    public function findAccount(string $token, string $ip): ?Account
    {
        $account = Account::where('account_token', $token)->first();

        if (!$account) {
            Log::warning("❌ Account not found for provided token", [
                'ip'    => $ip,
                'token' => substr($token, 0, 12) . '...',
            ]);
        }

        return $account;
    }

    /**
     * Trova il personaggio associato all'account, oppure lo crea al primo LOGIN livello 1.
     */
    public function findOrCreateCharacter(Account $account, array $data, string $eventCode): ?Character
    {
        $isNewCharacter = ($eventCode === 'LOGIN' && isset($data['level']) && (int) $data['level'] === 1);

        if ($isNewCharacter) {
            // firstOrCreate (not create): a level-1 LOGIN should only make a NEW
            // row the very first time this character is seen. Without the find
            // half, logging out and back in while still level 1 silently created
            // a second Character row instead of reusing the first, fragmenting
            // its event history/score. Also closes a race two near-simultaneous
            // LOGINs would otherwise have on plain create().
            return Character::firstOrCreate(
                ['name' => $data['name'], 'account_id' => $account->id],
                ['profession' => $data['profession'] ?? null, 'level' => 1, 'score' => 0]
            );
        }

        return Character::where('name', $data['name'])
            ->where('account_id', $account->id)
            ->latest('id')
            ->first();
    }

    /**
     * Uniforma il codice evento a maiuscolo.
     */
    public function normalizeEventCode(array $data): string
    {
        return strtoupper($data['event']);
    }

    /**
     * Valida gli eventi di cambio mappa, marcando quelli vietati come MAP_CHANGED_INVALID.
     */
    public function validateMapEvent(string $eventCode, array $data): string
    {
        if ($eventCode !== 'MAP_CHANGED') {
            return $eventCode;
        }

        $mapId   = (int) $data['map_id'];
        $mapType = (int) ($data['map_type'] ?? -1);

        $forbidden = ForbiddenMap::where('map_id', $mapId)->exists();

        if ($mapType === 2 || $forbidden) {
            Log::warning("🚫 Mappa non permessa rilevata", [
                'character' => $data['name'],
                'map_id'    => $mapId,
                'map_type'  => $mapType,
            ]);

            return 'MAP_CHANGED_INVALID';
        }

        return $eventCode;
    }

    /**
     * Traduce BUFF_APPLIED nei codici di violazione noti o restituisce errore se non riconosciuto.
     */
    public function translateBuffEvent(string $eventCode, array $data): array
    {
        if ($eventCode !== 'BUFF_APPLIED') {
            return ['code' => $eventCode, 'error' => false];
        }

        $buffId   = (int) ($data['buff_id'] ?? 0);
        $buffName = strtolower($data['buff_name'] ?? '');

        // Match per NOME sulle categorie generiche cibo/utility: un cibo qualsiasi
        // applica il buff di categoria "Nourishment"/"Enhancement".
        if ($buffName === 'nourishment') {
            return ['code' => 'BUFF_FORBIDDEN_FOOD', 'error' => false];
        }

        if ($buffName === 'enhancement') {
            return ['code' => 'BUFF_FORBIDDEN_ENHANCEMENT', 'error' => false];
        }

        // Match per ID (data-driven: config/heroesascent.php → forbidden_buff_ids).
        // Copre Reinforced Armor + i boost di gilda, e fa da fallback ID a cibo/utility.
        $forbiddenById = config('heroesascent.forbidden_buff_ids', []);
        if (isset($forbiddenById[$buffId])) {
            return ['code' => $forbiddenById[$buffId], 'error' => false];
        }

        Log::warning("⚠️ BUFF_APPLIED ricevuto ma non riconosciuto", [
            'buff_id'   => $buffId,
            'buff_name' => $buffName,
        ]);

        return ['code' => $eventCode, 'error' => true];
    }

    /**
     * Esegue controlli di coerenza sul payload in base al tipo di evento e allo state bitmask.
     */
    public function validateIntegrity(string $eventCode, int $state, array $data, CharacterUpdateRequest $request): array
    {
        $CS_IS_ALIVE   = 1 << 0;
        $CS_IS_DOWNED  = 1 << 1;
        $CS_IS_GLIDING = 1 << 5;
        $errors = [];

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
            case 'MAP_CHANGED_INVALID':
                if (!array_key_exists('map_type', $data)) {
                    $errors[] = 'Missing map_type for MAP_CHANGED';
                }
                break;
            case 'LEVEL_UP':
                if (!array_key_exists('level', $data)) {
                    $errors[] = 'Missing level for LEVEL_UP';
                }
                break;
            case 'GROUP':
                $gt = (int) ($data['group_type'] ?? 0);
                $gc = (int) ($data['group_count'] ?? 0);

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
                'event'  => $eventCode,
                'errors' => $errors,
            ]);
        }

        return $errors;
    }

    /**
     * Log strutturato dell'esito evento (info o warning a seconda del tipo).
     */
    public function logOutcome(string $eventCode, Character $character, Account $account, array $data, CharacterEvent $event): void
    {
        Log::info("✅ Event recorded for {$character->name}", [
            'event'       => $event->event_code,
            'points'      => $event->points,
            'is_critical' => $event->eventType->is_critical ?? false,
            'account_id'  => $account->id,
        ]);

        switch ($eventCode) {
            case 'LOGIN':
                Log::info("🔐 Character {$character->name} logged in successfully", [
                    'account_name' => $account->account_name,
                    'map_id'       => $data['map_id'],
                ]);
                break;
            case 'LOGOUT':
                Log::info("🔐 Character {$character->name} logged out", [
                    'map_id' => $data['map_id'] ?? null,
                ]);
                break;
            case 'LEVEL_UP':
                Log::info("🎉 Level Up! {$character->name} è salito al livello {$data['level']}", [
                    'level'  => $data['level'] ?? null,
                    'map_id' => $data['map_id'],
                ]);
                break;
            case 'DOWNED':
                Log::warning("🩹 Character {$character->name} is DOWNED", [
                    'map_id' => $data['map_id'],
                ]);
                break;
            case 'DEAD':
                Log::warning("💀 Character {$character->name} has died", [
                    'map_id' => $data['map_id'],
                ]);
                break;
            case 'MAP_CHANGED':
                Log::info("ℹ️ Character {$character->name} changed map", [
                    'new_map_id' => $data['map_id'],
                ]);
                break;
            case 'MAP_CHANGED_INVALID':
                Log::warning("🚫 Character {$character->name} entered a FORBIDDEN MAP!", [
                    'map_id' => $data['map_id'],
                ]);
                break;
            case 'MOUNT_CHANGED':
                Log::warning("🐎❌ MOUNT usage detected for {$character->name}", [
                    'mount_index' => $data['mount'] ?? null,
                ]);
                break;
            case 'GLIDING':
                Log::warning("🌬️❌ GLIDING rilevato per {$character->name}");
                break;
            case 'HEALING_USED':
                Log::warning("❤️‍🩹❌ HEALING SKILL used by {$character->name}");
                break;
            case 'BUFF_FORBIDDEN_FOOD':
                Log::warning("🍛❌ CIBO vietato per {$character->name}");
                break;
            case 'BUFF_FORBIDDEN_ENHANCEMENT':
                Log::warning("⚡❌ ENHANCEMENT vietato per {$character->name}");
                break;
            case 'BUFF_FORBIDDEN_REINFORCED':
                Log::warning("🛡️❌ REINFORCED ARMOR rilevato per {$character->name}");
                break;
            case 'BUFF_FORBIDDEN_GUILD':
                Log::warning("🏰❌ BOOST DI GILDA rilevato per {$character->name}", [
                    'buff_id'   => $data['buff_id'] ?? null,
                    'buff_name' => $data['buff_name'] ?? null,
                ]);
                break;
            case 'GROUP':
                Log::warning("🚫 Player {$character->name} is in a GROUP!", [
                    'group_type'  => $data['group_type'] ?? null,
                    'group_count' => $data['group_count'] ?? null,
                ]);
                break;
        }
    }
}
