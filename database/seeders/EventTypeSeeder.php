<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EventType;

class EventTypeSeeder extends Seeder
{
    public function run(): void
    {
        EventType::insert([
            // === LOGIN ===
            [
                'code' => 'LOGIN',
                'name' => 'Login',
                'description' => 'Accesso iniziale del personaggio',
                'category' => 'login',
                'points' => 0,
                'is_critical' => false,
                'color' => 'info',
            ],

            // === STATO PERSONAGGIO ===
            [
                'code' => 'DOWNED',
                'name' => 'Downed',
                'description' => 'Il personaggio è stato atterrato (downed)',
                'category' => 'death',
                'points' => -99999,
                'is_critical' => true,
                'color' => 'warning',
            ],
            [
                'code' => 'DEAD',
                'name' => 'Morte',
                'description' => 'Il personaggio è morto definitivamente',
                'category' => 'death',
                'points' => -99999,
                'is_critical' => true,
                'color' => 'danger',
            ],
            [
                'code' => 'RESPAWN',
                'name' => 'Respawn',
                'description' => 'Il personaggio è rinato dopo la morte',
                'category' => 'info',
                'points' => 0,
                'is_critical' => false,
                'color' => 'secondary',
            ],

            // === MOVIMENTO / CAMBI MAPPPA / MOUNT ===
            [
                'code' => 'MAP_CHANGED',
                'name' => 'Cambio mappa',
                'description' => 'Il personaggio è passato ad un altra mappa',
                'category' => 'movement',
                'points' => 0,
                'is_critical' => false,
                'color' => 'primary',
            ],
            [
                'code' => 'MOUNT_CHANGED',
                'name' => 'Cambio mount',
                'description' => 'Il personaggio ha usato una mount',
                'category' => 'movement',
                'points' => -99999,
                'is_critical' => true,
                'color' => 'secondary',
            ],
            [
                'code' => 'HEALING_USED',
                'name' => 'Uso Skill di Cura',
                'description' => 'Il personaggio ha usato l abilità di cura (skill 6), vietata dal regolamento',
                'category' => 'violation',
                'points' => -99999,
                'is_critical' => true,
                'color' => 'danger',
            ],

            // === VIOLAZIONI REGOLAMENTO ===
            [
                'code' => 'RULE_FOOD_001',
                'name' => 'Uso di Cibo',
                'description' => 'Ha consumato cibo o booster durante la prova',
                'category' => 'violation',
                'points' => -100,
                'is_critical' => true,
                'color' => 'danger',
            ],
            [
                'code' => 'DISQUALIFIED',
                'name' => 'Squalifica',
                'description' => 'Violazione grave: personaggio squalificato',
                'category' => 'violation',
                'points' => -99999,
                'is_critical' => true,
                'color' => 'danger',
            ],
        ]);
    }
}
