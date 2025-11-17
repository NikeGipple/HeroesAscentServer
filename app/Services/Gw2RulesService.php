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
        Log::info("🟦 [SCAN] Avvio scansione personaggi attivi...");

        // Recupero tutti i personaggi non squalificati
        $characters = Character::whereNull('disqualified_at')->get();
        $count = $characters->count();

        Log::info("🟦 [SCAN] Trovati {$count} personaggi da controllare.");

        if ($count === 0) {
            Log::info("🟩 [SCAN] Nessun personaggio da controllare. Fine scansione.");
            return;
        }

        $processed = 0;
        $disqualified = 0;

        foreach ($characters as $char) {

            Log::info("➡️ [SCAN] Controllo personaggio: {$char->name} (ID {$char->id})");

            try {
                $violations = self::scanCharacter($char);
            } catch (\Throwable $e) {
                Log::error("❌ [SCAN] Errore durante il controllo del personaggio {$char->name}: " . $e->getMessage());
                continue;
            }

            $hasTraitViolations = !empty($violations['traits']);
            $hasSkillViolations = !empty($violations['skills']);

            if (!$hasTraitViolations && !$hasSkillViolations) {

                Log::info("🟩 [SCAN] OK — Nessuna violazione per {$char->name}");

                $char->update(['last_check_at' => now()]);
                $processed++;
                continue;
            }

            // Violazioni trovate → squalifica
            $disqualified++;
            $processed++;

            Log::warning("🚨 [SCAN] VIOLAZIONI — Personaggio {$char->name} SQUALIFICATO");
            Log::warning("🚨 [SCAN] Violazioni dettagliate:", $violations);

            $char->update([
                'disqualified_at' => now(),
                'last_check_at'   => now(),
            ]);

            // Se vuoi loggare eventi nel DB, puoi riattivare questa parte:
            /*
            $char->events()->create([
                'type' => 'DISQUALIFIED',
                'payload' => json_encode($violations),
            ]);
            */
        }

        Log::info("🟦 [SCAN] Scansione completata.");
        Log::info("🟦 [SCAN] Personaggi controllati: {$processed}");
        Log::info("🟦 [SCAN] Personaggi squalificati: {$disqualified}");
        Log::info("🟩 [SCAN] Fine.");
    }

}
