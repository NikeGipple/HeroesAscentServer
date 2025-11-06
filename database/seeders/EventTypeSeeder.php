<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EventType;

class EventTypeSeeder extends Seeder
{
    public function run(): void
    {
        EventType::insert([
            [
                'code' => 'LOGIN_START',
                'name' => 'Login',
                'description' => 'Accesso iniziale del personaggio',
                'category' => 'login',
                'default_points' => 0,
                'color' => 'info',
            ],
            [
                'code' => 'RULE_FOOD_001',
                'name' => 'Uso di Cibo',
                'description' => 'Ha consumato cibo durante la prova',
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
