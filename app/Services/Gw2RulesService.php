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


    public static function scanAllActiveCharacters(bool $verbose = false): void
    {
        // Helper interno per output console
        $out = function($msg) use ($verbose) {
            if ($verbose) echo $msg . PHP_EOL;
        };

        $out("🟦 [SCAN] Avvio scansione personaggi attivi...");

        $characters = Character::whereNull('disqualified_at')->get();
        $count = $characters->count();

        $out("🟦 [SCAN] Trovati {$count} personaggi da controllare.");

        if ($count === 0) {
            $out("🟩 [SCAN] Nessun personaggio da controllare. Fine.");
            return;
        }

        $processed = 0;
        $disqualified = 0;

        foreach ($characters as $char) {

            $out("➡️ [SCAN] Controllo {$char->name}");

            $violations = self::scanCharacter($char);

            if (empty($violations['traits']) && empty($violations['skills'])) {
                $out("🟩 [SCAN] OK — Nessuna violazione per {$char->name}");
                $char->update(['last_check_at' => now()]);
                $processed++;
                continue;
            }

            // Solo questo va nei log reali
            Log::warning("Personaggio {$char->name} SQUALIFICATO. Violazioni:", $violations);

            $out("🚨 [SCAN] SQUALIFICATO {$char->name}");
            $out("🚨 Violazioni: " . json_encode($violations));

            $char->update([
                'disqualified_at' => now(),
                'last_check_at'   => now(),
            ]);

            $processed++;
            $disqualified++;
        }

        $out("🟦 [SCAN] Scansione completata.");
        $out("🟦 Controllati: {$processed}");
        $out("🟦 Squalificati: {$disqualified}");
        $out("🟩 Fine.");
    }


}
