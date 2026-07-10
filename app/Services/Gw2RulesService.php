<?php

namespace App\Services;

use App\Models\Character;
use App\Models\BannedSkill;
use App\Models\BannedTrait;
use App\Services\Gw2ApiService;
use App\Models\CharacterEvent;
use App\Support\HeroesAscent\Bypass;

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
            $activeTab = Gw2ApiService::getActiveEquipmentTab($apiKey, $name);
        } catch (\Exception $e) {
            Log::error("Errore /equipmenttabs per {$name}: " . $e->getMessage());
            return [];
        }

        if (!$activeTab || empty($activeTab['equipment'])) {
            return [];
        }

        $invalid = [];

        foreach ($activeTab['equipment'] as $item) {
            $slot = $item['slot'] ?? null;

            if (!$slot) continue;

            // CONSENTITO: solo weapon*
            if (str_starts_with($slot, 'Weapon')) {
                continue;
            }

            // TUTTO IL RESTO è vietato
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
        if ($character->level <= 3) {
            return [
                'traits' => [],
                'skills' => [],
                'equipment' => [],
            ];
        }

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

        $characters = Character::whereNull('disqualified_at')
            ->with('account')
            ->get();
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

            $bypass = $char->account->isBypass();
            if ($bypass) {
                $char->update(['last_check_at' => now()]);
                continue;
            }

            // CONTROLLO GILDA RAPPRESENTATA (per-personaggio, non per-account).
            // I buff di gilda si applicano solo alla gilda rappresentata dal personaggio
            // in gara: è quella a dare un eventuale vantaggio, non l'appartenenza account.
            $apiKey = $char->account->api_key;
            $repr = Gw2ApiService::getRepresentedGuild($apiKey, $char->name);

            // Su errore API (perm mancante, rete, personaggio non trovato) NON squalifichiamo:
            // meglio rimandare al prossimo giro che escludere per un falso positivo.
            if (!$repr['error'] && $repr['guild'] !== null) {

                $allowedGuilds = config('heroesascent.allowed_guilds', []);

                // Lista vuota (default) ⇒ rappresentare qualsiasi gilda è una violazione.
                if (!in_array($repr['guild'], $allowedGuilds, true)) {

                    CharacterEvent::record($char, 'ACCOUNT_IN_GUILD', [
                        'details'   => "Personaggio rappresenta una gilda non permessa (ID: {$repr['guild']})",
                    ]);

                    $out("🚨 [SCAN] {$char->name} sta rappresentando una gilda non permessa. Rimosso dalla competizione.");
                    Log::warning("Personaggio {$char->name} escluso → rappresenta una gilda non ammessa", [
                        'guild' => $repr['guild'],
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
            $hasEquipViolations = !empty($violations['equipment']);

            // === TRAIT VIETATI ===
            if ($hasTraitViolations) {

                $firstTraitId = $violations['traits'][0];

                $firstTraitName = BannedTrait::where('gw2_id', $firstTraitId)
                    ->value('name') ?? 'Trait Sconosciuto';

                CharacterEvent::record($char, 'BUILD_FORBIDDEN_TRAIT', [
                    'details'    => "Trait vietato rilevato",
                    'buff_id'    => $firstTraitId,
                    'buff_name'  => $firstTraitName,
                ]);

                $out("⚠️ [SCAN] Trait vietati per {$char->name} → Primo: {$firstTraitName} (ID {$firstTraitId})");
                Log::warning("Trait vietati per {$char->name}. Primo: {$firstTraitName} (ID {$firstTraitId})");
            }

            // === SKILL VIETATE ===
            if ($hasSkillViolations) {

                $firstSkillId = $violations['skills'][0];

                $firstSkillName = BannedSkill::where('gw2_id', $firstSkillId)
                    ->value('name') ?? 'Skill Sconosciuta';

                CharacterEvent::record($char, 'BUILD_FORBIDDEN_SKILL', [
                    'details'    => "Skill vietata rilevata",
                    'buff_id'    => $firstSkillId,
                    'buff_name'  => $firstSkillName,
                ]);

                $out("⚠️ [SCAN] Skill vietate per {$char->name} → Prima: {$firstSkillName} (ID {$firstSkillId})");
                Log::warning("Skill vietate per {$char->name}. Prima: {$firstSkillName} (ID {$firstSkillId})");
            }

            // === EQUIPAGGIAMENTO VIETATO ===
            if ($hasEquipViolations) {

                $first = $violations['equipment'][0];
                $firstId = $first['id'] ?? null;

                CharacterEvent::record($char, 'EQUIPMENT_INVALID', [
                    'details'   => "Server event: EQUIPMENT_INVALID",
                    'buff_id'   => $firstId,
                    'buff_name' => "Forbidden Slot: {$first['slot']}",
                ]);

                $out("⚠️ [SCAN] Equip vietato per {$char->name} → Slot {$first['slot']} (ID {$firstId})");
                Log::warning("Equip vietato per {$char->name}. Primo slot non valido: {$first['slot']} (ID {$firstId})");
            }

            // === Se non c’è NESSUNA violazione di nessun tipo ===
            if (!$hasTraitViolations && !$hasSkillViolations && !$hasEquipViolations) {
                $out("🟩 [SCAN] OK — Nessuna violazione per {$char->name}");
                $char->update(['last_check_at' => now()]);
                $processed++;
                continue;
            }

            // ===== SQUALIFICA AUTOMATICA SE EVENTO CRITICO =====
            $char->update(['last_check_at' => now()]);

            if ($char->fresh()->isDisqualified()) {
                $disqualified++;
                $out("🚨 [SCAN] SQUALIFICATO {$char->name}");
                Log::warning("Squalifica attivata per {$char->name}", $violations);
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
