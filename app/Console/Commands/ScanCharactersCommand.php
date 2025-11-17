<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Gw2RulesService;

class ScanCharactersCommand extends Command
{
    /**
     * Nome del comando artisan
     */
    protected $signature = 'characters:scan';

    /**
     * Descrizione mostrata in `php artisan list`
     */
    protected $description = 'Esegue immediatamente la verifica delle regole per tutti i personaggi attivi';

    /**
     * Esecuzione del comando
     */
    public function handle()
    {
        $this->info("Avvio verifica personaggi attivi...");

        Gw2RulesService::scanAllActiveCharacters();

        $this->info("Verifica completata!");

        return Command::SUCCESS;
    }
}
