<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncAllData extends Command
{
    /**
     * Nome del comando
     */
    protected $signature = 'heroes:sync-all-data';

    /**
     * Descrizione
     */
    protected $description = 'Sincronizza tutte le tabelle statiche e i metadati del sistema (professioni, specializzazioni, banned traits, banned skills)';

    /**
     * Esecuzione comando
     */
    public function handle(): int
    {
        $this->info("🚀 Avvio sincronizzazione completa...");

        $this->newLine();
        $this->info("📘 1/3 — Professioni & Specializzazioni");
        $this->call('gw2:sync-professions');

        $this->newLine();
        $this->info("📙 2/3 — Traits bannati");
        $this->call('gw2:sync-banned-traits');

        $this->newLine();
        $this->info("📕 3/3 — Skill bannate");
        $this->call('gw2:sync-banned-skills');

        $this->newLine(2);
        $this->info("🎉 Sincronizzazione completata con successo!");

        return Command::SUCCESS;
    }
}
