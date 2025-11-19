<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // NECESSARIO in Laravel 11/12 per caricare lo scheduler del Kernel
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            // Carica le definizioni dello scheduler presenti nel Kernel
            require base_path('routes/console.php');
        });
    }
}
