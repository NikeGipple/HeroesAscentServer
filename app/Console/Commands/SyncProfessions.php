<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\Gw2ApiService;

class SyncProfessions extends Command
{
    /**
     * Nome del comando Artisan
     */
    protected $signature = 'gw2:sync-professions';

    /**
     * Descrizione comando
     */
    protected $description = 'Importa e sincronizza professioni e specializzazioni da Guild Wars 2 API';

    /**
     * Esecuzione comando
     */
    public function handle(): int
    {
        $this->info("⏳ Importazione professioni & specializzazioni...");

        // STEP 1 — Lista professioni
        $profList = Gw2ApiService::safeRequest("https://api.guildwars2.com/v2/professions");

        if (!$profList || !is_array($profList)) {
            $this->error("❌ Errore caricando lista professioni");
            return Command::FAILURE;
        }

        foreach ($profList as $professionName) {

            $this->line("\n➡️ Professione: <fg=yellow>{$professionName}</>");

            // STEP 2 — Dettagli professione
            $profData = Gw2ApiService::safeRequest(
                "https://api.guildwars2.com/v2/professions/{$professionName}"
            );

            if (!$profData) {
                $this->error("❌ Errore caricando {$professionName}");
                continue;
            }

            // Salva professione
            DB::table('professions')->updateOrInsert(
                ['name' => $professionName],
                [
                    'icon'       => $profData['icon']     ?? null,
                    'icon_big'   => $profData['icon_big'] ?? null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $this->info("   ✔ Professione salvata");

            // STEP 3 — Specializzazioni della professione
            $specIds = $profData['specializations'] ?? [];

            foreach ($specIds as $specId) {

                $this->line("   ➜ Specialization ID <fg=cyan>{$specId}</>...");

                $specData = Gw2ApiService::safeRequest(
                    "https://api.guildwars2.com/v2/specializations/{$specId}"
                );

                if (!$specData) {
                    $this->error("     ❌ Errore caricando specialization {$specId}");
                    continue;
                }

                DB::table('specializations')->updateOrInsert(
                    ['gw2_id' => $specId],
                    [
                        'name'        => $specData['name'],
                        'profession'  => $specData['profession'], // es. “Engineer”
                        'icon'        => $specData['icon'] ?? null,
                        'background'  => $specData['background'] ?? null,
                        'updated_at'  => now(),
                        'created_at'  => now(),
                    ]
                );

                $this->info("     ✔ Specialization salvata: {$specData['name']}");
            }
        }

        $this->newLine();
        $this->info("🎉 Importazione completata con successo!");

        return Command::SUCCESS;
    }
}
