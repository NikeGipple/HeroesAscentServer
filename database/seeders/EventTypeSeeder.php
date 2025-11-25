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
                'code' => 'LEVEL_UP',
                'name' => 'Aumento di livello',
                'description' => 'Il personaggio ha ottenuto un nuovo livello',
                'category' => 'progression',
                'points' => 0,
                'is_critical' => false,
                'color' => 'info',
            ],
            [
                'code' => 'DOWNED',
                'name' => 'Downed',
                'description' => 'Il personaggio è stato atterrato (downed)',
                'category' => 'death',
                'points' => 0,
                'is_critical' => true,
                'color' => 'warning',
            ],
            [
                'code' => 'DEAD',
                'name' => 'Morte',
                'description' => 'Il personaggio è morto definitivamente',
                'category' => 'death',
                'points' => 0,
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
                'code' => 'MAP_CHANGED_INVALID',
                'name' => 'Cambio mappa su mappa non consentita',
                'description' => 'Il personaggio è entrato in una mappa non permessa dal regolamento',
                'category' => 'movement',
                'points' => 0,
                'is_critical' => true,
                'color' => 'danger',
            ],
            [
                'code' => 'MOUNT_CHANGED',
                'name' => 'Cambio mount',
                'description' => 'Il personaggio ha usato una mount',
                'category' => 'movement',
                'points' => 0,
                'is_critical' => true,
                'color' => 'secondary',
            ],
            [
                'code' => 'HEALING_USED',
                'name' => 'Uso Skill di Cura',
                'description' => 'Il personaggio ha usato l abilità di cura (skill 6), vietata dal regolamento',
                'category' => 'violation',
                'points' => 0,
                'is_critical' => true,
                'color' => 'danger',
            ],
            [
                'code' => 'GROUP',
                'name' => 'Presenza in gruppo',
                'description' => 'Il personaggio risulta in party/squad/gruppo, vietato dal regolamento',
                'category' => 'violation',
                'points' => 0,
                'is_critical' => true,
                'color' => 'danger',
            ],

            // === VIOLAZIONI REGOLAMENTO ===
            [
                'code' => 'BUFF_FORBIDDEN_FOOD',
                'name' => 'Cibo Non Consentito',
                'description' => 'Uso di Cibo (Nourishment), vietato dal regolamento',
                'category' => 'violation',
                'points' => 0,
                'is_critical' => true,
                'color' => 'danger',
            ],

            [
                'code' => 'BUFF_FORBIDDEN_ENHANCEMENT',
                'name' => 'Enhancement non consentito',
                'description' => 'Uso di Enhancement, vietato dal regolamento',
                'category' => 'violation',
                'points' => 0,
                'is_critical' => true,
                'color' => 'danger',
            ],

            [
                'code' => 'BUFF_FORBIDDEN_REINFORCED',
                'name' => 'Reinforced Armor',
                'description' => 'Uso di Reinforced Armor, vietato dal regolamento',
                'category' => 'violation',
                'points' => 0,
                'is_critical' => true,
                'color' => 'danger',
            ],

            [
                'code' => 'BUILD_FORBIDDEN_TRAIT',
                'name' => 'Trait vietato in build',
                'description' => 'La build attiva contiene uno o più trait vietati dal regolamento',
                'category' => 'violation',
                'points' => 0,
                'is_critical' => true,
                'color' => 'danger',
            ],
            
            [
                'code' => 'BUILD_FORBIDDEN_SKILL',
                'name' => 'Skill vietata in build',
                'description' => 'La build attiva contiene una o più skill vietate dal regolamento',
                'category' => 'violation',
                'points' => 0,
                'is_critical' => true,
                'color' => 'danger',
            ],

            [
                'code' => 'DISQUALIFIED',
                'name' => 'Squalifica',
                'description' => 'Violazione grave: personaggio squalificato',
                'category' => 'violation',
                'points' => 0,
                'is_critical' => true,
                'color' => 'danger',
            ],
        ]);
    }
}
