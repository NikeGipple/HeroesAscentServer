<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\Gw2ApiService;

class SyncBannedSkills extends Command
{
    /**
     * Nome del comando
     */
    protected $signature = 'gw2:sync-banned-skills';

    /**
     * Descrizione del comando
     */
    protected $description = 'Importa o aggiorna le Skill bannate usando le API GW2';

    /**
     * Esecuzione del comando
     */
    public function handle(): int
    {
        $this->info("⏳ Importazione Banned Skills...");

        // ────────────────────────────────────────────
        // LISTA SKILL BANNATE (versione leggibile)
        // ────────────────────────────────────────────
        $banned = [
            10541, // Necro - Summon Bone Minions
            10533, // Necro - Summon Bone Fiend
        ];

        // ────────────────────────────────────────────
        // IMPORTAZIONE
        // ────────────────────────────────────────────
        foreach ($banned as $gw2Id) {

            $this->line("➡️ Skill <fg=yellow>{$gw2Id}</>");

            $data = Gw2ApiService::safeRequest(
                "https://api.guildwars2.com/v2/skills/{$gw2Id}",
                null,
                ['lang' => 'en'],
                30
            );

            if (!$data) {
                $this->error("   ❌ Skill {$gw2Id} non caricata (API error)");
                continue;
            }

            DB::table('banned_skills')->updateOrInsert(
                ['gw2_id' => $gw2Id],
                [
                    'name'        => $data['name'] ?? "Unknown Skill",
                    'icon_url'    => $data['icon'] ?? null,
                    'slot'        => $data['slot'] ?? null,
                    'type'        => $data['type'] ?? null,
                    'weapon_type' => $data['weapon_type'] ?? null,
                    'description' => $data['description'] ?? null,
                    'updated_at'  => now(),
                    'created_at'  => now(),
                ]
            );

            $this->info("   ✔ Salvata: {$data['name']}");
        }

        $this->newLine();
        $this->info("🎉 Banned Skills aggiornate!");

        return Command::SUCCESS;
    }
}
