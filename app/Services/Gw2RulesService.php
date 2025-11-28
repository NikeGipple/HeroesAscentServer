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
     * Controlla l’equip del personaggio e ritorna gli oggetti non consentiti.
     */
    private static function getInvalidEquipment(Character $character): array
    {
        if ($character->level <= 3) {
            return [];
        }

        $apiKey = $character->account->api_key;
        $name   = $character->name;

        try {
            $tabs = Gw2ApiService::getEquipmentTabs($apiKey, $name);
        } catch (\Exception $e) {
            Log::error("Errore /equipmenttabs per {$name}: " . $e->getMessage());
            return [];
        }

        if (!isset($tabs['tabs'])) {
            return [];
        }

        $active = collect($tabs['tabs'])->firstWhere('is_active', true);

        if (!$active || empty($active['equipment'])) {
            return [];
        }

        $invalid = [];

        foreach ($active['equipment'] as $item) {

            $slot = $item['slot'];

            // CONSENTITO: solo weapon*
            if (str_starts_with($slot, 'Weapon')) {
                continue;
            }

            // VIETATO: tutto il resto
            $invalid[] = [
                'slot' => $slot,
                'id'   => $item['id'] ?? null,
            ];
        }

        return $invalid;
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
            'equipment'  => [],
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

        $violations['equipment'] = self::getInvalidEquipment($character);

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
            $apiKey = $char->account->api_key;
            $guilds = Gw2ApiService::scanAccountGuilds($apiKey);

            $allowedGuild = '76A00BE9-8DEB-EE11-8465-0228F2FB5E53';

            if (!empty($guilds) && !isset($guilds['error'])) {

                // Se una delle gilde è quella ammessa → NON registriamo nulla e proseguiamo
                if (in_array($allowedGuild, $guilds, true)) {

                } else {

                    // Altrimenti → squalifica 
                    $firstGuild = $guilds[0];

                    CharacterEvent::record($char, 'ACCOUNT_IN_GUILD', [
                        'details'   => "Account appartenente alla gilda (ID: {$firstGuild})",
                    ]);

                    $out("🚨 [SCAN] {$char->name} appartiene a una gilda NON permessa. Rimosso dalla competizione.");
                    Log::warning("Personaggio {$char->name} escluso → appartiene a una gilda non ammessa", [
                        'guilds' => $guilds
                    ]);

                    $char->update(['last_check_at' => now()]);

                    if ($char->fresh()->isDisqualified()) {
                        $disqualified++;
                        continue;
                    }
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
            if (!empty($violations['traits'])) {

                $firstTraitId = $violations['traits'][0];

                $firstTraitName = BannedTrait::where('gw2_id', $firstTraitId)
                    ->value('name') ?? 'Trait Sconosciuto';

                CharacterEvent::record($char, 'BUILD_FORBIDDEN_TRAIT', [
                    'details'    => "Trait vietato rilevato",
                    'buff_id'    => $firstTraitId,          
                    'buff_name'  => $firstTraitName,
                ]);

                $out("⚠️ [SCAN] Trait vietati per {$char->name} → Primo trovato: {$firstTraitName} (ID {$firstTraitId})");
                Log::warning("Trait vietati per {$char->name}. Primo: {$firstTraitName} (ID {$firstTraitId})");
            }

            // Registra eventi per ogni SKILL vietata
            if (!empty($violations['skills'])) {

                $firstSkillId = $violations['skills'][0];

                $firstSkillName = BannedSkill::where('gw2_id', $firstSkillId)
                    ->value('name') ?? 'Skill Sconosciuta';

                CharacterEvent::record($char, 'BUILD_FORBIDDEN_SKILL', [
                    'details'    => "Skill vietata rilevata",
                    'buff_id'    => $firstSkillId,          
                    'buff_name'  => $firstSkillName,
                ]);

                $out("⚠️ [SCAN] Skill vietate per {$char->name} → Prima trovata: {$firstSkillName} (ID {$firstSkillId})");
                Log::warning("Skill vietate per {$char->name}. Prima: {$firstSkillName} (ID {$firstSkillId})");
            }


            // === EQUIPAGGIAMENTO VIETATO ===
            if (!empty($violations['equipment'])) {

                // Prendiamo SOLO il primo pezzo non consentito
                $first = $violations['equipment'][0];
                $firstId = $first['id'] ?? null;

                CharacterEvent::record($char, 'EQUIPMENT_INVALID', [
                    'details'    => "Equipaggiamento non consentito",
                    'buff_id'    => $firstId,     
                    'buff_name'  => 'Forbidden Equipment',
                ]);

                Log::warning("Equip non valido per {$char->name}. Primo slot non valido: {$first['slot']} (ID {$firstId})");
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
