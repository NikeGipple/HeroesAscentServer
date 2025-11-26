<?php

namespace App\Services;

use App\Models\Character;
use App\Models\BannedSkill;
use App\Models\BannedTrait;
use App\Services\Gw2ApiService;
use App\Models\CharacterEvent;

use Illuminate\Support\Facades\Log;

class Gw2RulesService
{

    /**
     * Controlla SE l'account appartiene a una o piu gilde:
     */
    public static function scanAccountGuilds(string $apiKey): array
    {
        try {
            $account = Gw2ApiService::getAccount($apiKey); // GET /v2/account

            if (!isset($account['guilds']) || empty($account['guilds'])) {
                return []; // OK → nessuna gilda
            }

            return $account['guilds']; // Lista ID gilde
        } catch (\Exception $e) {
            Log::error("Errore chiamando /v2/account per controllo gilde: " . $e->getMessage());
            return ['error' => true];
        }
    }



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

        // Log::info("{$name} → Traits attivi: " . implode(', ', $traits));
        // Log::info("{$name} → Skills attive: " . implode(', ', $skills));

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
        Log::warning("🟦 [SCAN] Inizio scansione automatica alle " . now());

        // Helper interno per output console (solo per comando artisan)
        $out = function ($msg) use ($verbose) {
            if ($verbose) {
                echo $msg . PHP_EOL;
            }
        };

        $out("🟦 [SCAN] Avvio scansione personaggi attivi...");

        $characters = Character::whereNull('disqualified_at')->get();
        $count = $characters->count();

        $out("🟦 [SCAN] Trovati {$count} personaggi da controllare.");

        if ($count === 0) {
            $out("🟩 [SCAN] Nessun personaggio da controllare. Fine.");
            Log::warning("🟩 [SCAN] Fine scansione automatica alle " . now());
            return;
        }

        $processed     = 0;
        $disqualified  = 0;

        foreach ($characters as $char) {
            $out("➡️ [SCAN] Controllo {$char->name}");

            // CONTROLLO GILDE ACCOUNT
            $guilds = self::scanAccountGuilds($apiKey);

            if (!empty($guilds) && !isset($guilds['error'])) {

                $firstGuild = $guilds[0];

                CharacterEvent::record($char, 'ACCOUNT_IN_GUILD', [
                    'details'   => "Account appartenente a una gilda (ID: {$firstGuild})",
                    'buff_id'   => $firstGuild,
                    'buff_name' => 'Guild Membership',
                ]);

                $out("🚨 [SCAN] {$char->name} appartiene a una gilda. SQUALIFICATO.");
                Log::warning("Personaggio {$char->name} squalificato → appartiene a una gilda", [
                    'guilds' => $guilds
                ]);

                $char->update(['last_check_at' => now()]);

                if ($char->fresh()->isDisqualified()) {
                    $disqualified++;
                    continue;
                }
            }

            // CONTROLLO controllo trait + skill
            $violations = self::scanCharacter($char);

            $hasTraitViolations = !empty($violations['traits']);
            $hasSkillViolations = !empty($violations['skills']);

            // Nessuna violazione
            if (!$hasTraitViolations && !$hasSkillViolations) {
                $out("🟩 [SCAN] OK — Nessuna violazione per {$char->name}");
                $char->update(['last_check_at' => now()]);
                $processed++;
                continue;
            }

            // Registra eventi per ogni TRAIT vietato
            foreach ($violations['traits'] as $traitId) {

                $traitName = BannedTrait::where('gw2_id', $traitId)->value('name') ?? 'Trait Sconosciuto';

                CharacterEvent::record($char, 'BUILD_FORBIDDEN_TRAIT', [
                    'details'    => "Trait vietato: {$traitName} (ID: {$traitId})",
                    'buff_id'    => $traitId,
                    'buff_name'  => $traitName,
                ]);

                $out("⚠️ [SCAN] Trait vietato per {$char->name} → {$traitName} (ID {$traitId})");
            }

            // Registra eventi per ogni SKILL vietata
            foreach ($violations['skills'] as $skillId) {

                $skillName = BannedSkill::where('gw2_id', $skillId)->value('name') ?? 'Skill Sconosciuta';

                CharacterEvent::record($char, 'BUILD_FORBIDDEN_SKILL', [
                    'details'    => "Skill vietata: {$skillName} (ID: {$skillId})",
                    'buff_id'    => $skillId,
                    'buff_name'  => $skillName,
                ]);

                $out("⚠️ [SCAN] Skill vietata per {$char->name} → {$skillName} (ID {$skillId})");
            }


            // Aggiorna sempre last_check_at
            $char->update([
                'last_check_at' => now(),
            ]);

            // Se almeno un evento critico è stato creato, il personaggio ora è squalificato
            if ($char->fresh()->isDisqualified()) {
                $disqualified++;
                Log::warning("Personaggio {$char->name} SQUALIFICATO per build non valida.", $violations);
                $out("🚨 [SCAN] SQUALIFICATO {$char->name} per build non valida");
            }

            $processed++;
        }

        Log::warning("🟩 [SCAN] Fine scansione automatica alle " . now());

        $out("🟦 [SCAN] Scansione completata.");
        $out("🟦 Controllati: {$processed}");
        $out("🟦 Squalificati: {$disqualified}");
        $out("🟩 Fine.");
    }



}
