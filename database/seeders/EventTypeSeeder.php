<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EventType;

class EventTypeSeeder extends Seeder
{
    public function run(): void
    {
        EventType::insert([
            // === EVENTI DI LOGIN ===
            [
                'code' => 'LOGIN',
                'name' => 'Login',
                'description' => 'Accesso iniziale del personaggio',
                'category' => 'login',
                'default_points' => 0,
                'is_critical' => false,
                'color' => 'info',
            ],

            // === EVENTI DI STATO ===
            [
                'code' => 'STATUS_UPDATE',
                'name' => 'Aggiornamento stato',
                'description' => 'Aggiornamento periodico dello stato del personaggio',
                'category' => 'info',
                'default_points' => 0,
                'is_critical' => false,
                'color' => 'secondary',
            ],

            [
                'code' => 'DOWNED',
                'name' => 'Downed',
                'description' => 'Il personaggio è stato atterrato (downed)',
                'category' => 'death',
                'default_points' => -50,
                'is_critical' => true,
                'color' => 'warning',
            ],

            [
                'code' => 'DEATH',
                'name' => 'Morte',
                'description' => 'Il personaggio è morto durante la prova',
                'category' => 'death',
                'default_points' => -200,
                'is_critical' => true,
                'color' => 'danger',
            ],

            // === EVENTI DI VIOLAZIONE REGOLAMENTO ===
            [
                'code' => 'RULE_FOOD_001',
                'name' => 'Uso di Cibo',
                'description' => 'Ha consumato cibo o booster durante la prova',
                'category' => 'violation',
                'default_points' => -100,
                'is_critical' => true,
                'color' => 'danger',
            ],

            [
                'code' => 'DISQUALIFIED',
                'name' => 'Squalifica',
                'description' => 'Violazione grave: personaggio squalificato',
                'category' => 'violation',
                'default_points' => -999,
                'is_critical' => true,
                'color' => 'danger',
            ],
        ]);
    }
}
