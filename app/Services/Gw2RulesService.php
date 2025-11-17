<?php

namespace App\Services;

use App\Models\Character;
use App\Models\BannedSkill;
use App\Models\BannedTrait;
use App\Services\Gw2ApiService;
use Illuminate\Support\Facades\Log;

class Gw2RulesService
{
    /**
     * Controlla UN personaggio:
     * - ritorna array con violazioni o array vuoto
     */
    public static function scanCharacter(Character $character): array
    {
        $apiKey = $character->account->api_key;
        $name   = $character->name;

        Log::info("Controllo regole per il personaggio {$name}");

        // 1) Traits attivi
        $traits = Gw2ApiService::getActiveTraits($apiKey, $name);

        // 2) Skills attive
        $skills = Gw2ApiService::getActiveSkills($apiKey, $name);

        $violations = [
            'traits' => [],
            'skills' => [],
        ];

        // 🔍 Controllo trait
        foreach ($traits as $traitId) {
            if (BannedTrait::isBanned($traitId)) {
                $violations['traits'][] = $traitId;
            }
        }

        // 🔍 Controllo skill
        foreach ($skills as $skillId) {
            if (BannedSkill::isBanned($skillId)) {
                $violations['skills'][] = $skillId;
            }
        }

        return $violations;
    }


    /**
     * Controlla TUTTI i personaggi attivi (non squalificati)
     */
    public static function scanAllActiveCharacters(): void
    {
        $characters = Character::whereNull('disqualified_at')->get();

        foreach ($characters as $char) {

            $violations = self::scanCharacter($char);

            // Nessuna violazione → aggiorno solo last_check_at
            if (empty($violations['traits']) && empty($violations['skills'])) {
                $char->update(['last_check_at' => now()]);
                continue;
            }

            // 🚨 Violazioni: squalifica
            $char->update([
                'disqualified_at' => now(),
                'last_check_at'   => now(),
            ]);

            Log::warning("Personaggio {$char->name} SQUALIFICATO. Violazioni:", $violations);

            // 🔥 Se vuoi registrare gli eventi in CharacterEvent:
            // $char->events()->create([
            //     'type' => 'DISQUALIFIED',
            //     'payload' => json_encode($violations),
            // ]);
        }
    }
}
