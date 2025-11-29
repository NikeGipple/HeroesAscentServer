<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\Gw2ApiService;

class SyncBannedTraits extends Command
{
    protected $signature = 'gw2:sync-banned-traits';
    protected $description = 'Importa o aggiorna i Traits bannati usando i dati delle API GW2';

    public function handle(): int
    {
        $this->info("⏳ Importazione Banned Traits...");

        // ────────────────────────────────────────────
        // LISTA TRATTI BANNATI (versione leggibile)
        // ────────────────────────────────────────────
        $banned = [
            // —— GUARDIAN ——
            585,  // Altruistic Healing
            584,  // Redemption
            586,  // Monk's Focus
            1899, // Invigorated Bulwark
            549,  // Pure of Heart
            558,  // Writ of Persistence
            1682, // Force of Will
            610,  // Absolute Resolve
            554,  // Battle Presence
            587,  // Glacial Heart

            // —— REVENANT ——
            1720, // Fiendish Tenacity
            1790, // Steadfast Rejuvenation
            1823, // Invoking Harmony
            1815, // Generous Abundance
            1784, // Glaring Resolve
            1774, // Spirit Boon
            1760, // Rapid Flow
            1749, // Song of the Mists
            1755, // Battle Scarred
            1802, // Thrill of Combat
            1754, // Dance of Death

            // —— WARRIOR ——
            1454, // Might Makes Right
            1488, // Dogged March
            1375, // Last Stand
            1474, // Soldier's Comfort
            1479, // Shrug It Off
            1482, // Empower Allies
            1667, // Martial Cadence
            1470, // Vigorous Shouts
            1484, // Doubled Standards

            // —— ENGINEER ——
            1834, // Soothing Detonation
            1680, // Bunker Down
            1916, // Medical Dispersion Field
            521,  // Health Insurance
            520,  // Comeback Cure
            470,  // Backpack Regenerator

            // —— RANGER ——
            1086, // Oakheart Salve
            978,  // Wellspring
            964,  // Windborne Notes
            1697, // Invigorating Bond
            1606, // Resounding Timbre
            970,  // Natural Healing

            // —— THIEF ——
            1702, // Invigorating Precision
            1130, // Leeching Venoms
            1300, // Cloaked in Shadow
            1238, // Assassin's Reward
            1295, // Upper Hand

            // —— ELEMENTALIST ——
            348,  // Soothing Ice
            364,  // Soothing Disruption
            2028, // Soothing Power
            1487, // Arcane Restoration
            265,  // Arcane Resurrection
            238,  // Evasive Arcana

            // —— MESMER ——
            668,  // Chaotic Transference
            1687, // Bountiful Disillusionment
            738,  // Restorative Mantras
            1866, // Restorative Illusions

            // —— NECROMANCER ——
            812,  // Parasitic Contagion
            1694, // Unholy Sanctuary
            780,  // Ritual of Life
            1876, // Blood Bond
            789,  // Life from Death
            1844, // Vampiric Presence
            788,  // Transfusion
        ];

        // ────────────────────────────────────────────
        // IMPORTAZIONE
        // ────────────────────────────────────────────
        foreach ($banned as $gw2Id) {

            $this->line("➡️ Trait <fg=yellow>{$gw2Id}</>");

            $data = Gw2ApiService::safeRequest(
                "https://api.guildwars2.com/v2/traits/{$gw2Id}",
                null,
                ['lang' => 'en']
            );

            if (!$data) {
                $this->error("   ❌ Trait {$gw2Id} non caricato (API error)");
                continue;
            }

            DB::table('banned_traits')->updateOrInsert(
                ['gw2_id' => $gw2Id],
                [
                    'name'           => $data['name'] ?? "Unknown Trait",
                    'icon_url'       => $data['icon'] ?? null,
                    'slot'           => $data['slot'] ?? null,
                    'specialization' => $data['specialization'] ?? null,
                    'description'    => $data['description'] ?? null,
                    'updated_at'     => now(),
                    'created_at'     => now(),
                ]
            );

            $this->info("   ✔ Salvato: {$data['name']}");
        }

        $this->newLine();
        $this->info("🎉 Banned Traits aggiornati!");

        return Command::SUCCESS;
    }
}
