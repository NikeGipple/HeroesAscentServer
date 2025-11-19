<?php

namespace App\Console;

use App\Services\Gw2RulesService;
use Illuminate\Console\Scheduling\Schedule;
use App\Console\Commands\ScanCharactersCommand;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Comandi personalizzati dell'applicazione.
     */
    protected $commands = [
        ScanCharactersCommand::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('characters:scan')
            ->everyFiveMinutes()
            ->withoutOverlapping();
    }


    /**
     * Register the commands for the application.
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
