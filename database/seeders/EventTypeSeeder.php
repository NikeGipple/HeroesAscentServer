<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EventType;

class EventTypeSeeder extends Seeder
{
    public function run(): void
    {
        EventType::truncate();

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
            [
                'code' => 'LOGOUT',
                'name' => 'Logout',
                'description' => 'Il personaggio è uscito dal gioco ed è tornato alla schermata di selezione',
                'category' => 'login',
                'points' => 0,
                'is_critical' => false,
                'color' => 'info',
            ],
            [
                'code' => 'LOGOUT_LOW_HP',
                'name' => 'Logout in situazione critica',
                'description' => 'Il personaggio ha effettuato il logout mentre era sotto il 50% di vita, indicando una possibile fuga da combattimento o da pericolo.',
                'category' => 'login',
                'points' => 0,
                'is_critical' => false,
                'color' => 'warning',
            ],
            [
                'code' => 'ABUSE_LOGOUT_LOW_HP',
                'name' => 'Abuso Logout in Situazione Critica',
                'description' => 'Il personaggio ha effettuato troppi logout mentre era sotto il 50% di vita, indicando un comportamento abusivo volto a evitare la morte.',
                'category' => 'violation',
                'points' => 0,
                'is_critical' => true,
                'color' => 'danger',
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
                'color' => 'danger',
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

            // === MOVIMENTO / CAMBI MAPPPA / MOUNT ===
            [
                'code' => 'MAP_CHANGED',
                'name' => 'Cambio mappa',
                'description' => 'Il personaggio è passato ad un altra mappa',
                'category' => 'info',
                'points' => 0,
                'is_critical' => false,
                'color' => 'primary',
            ],
            [
                'code' => 'MAP_CHANGED_INVALID',
                'name' => 'Cambio mappa su mappa non consentita',
                'description' => 'Il personaggio è entrato in una mappa non permessa dal regolamento',
                'category' => 'violation',
                'points' => 0,
                'is_critical' => true,
                'color' => 'danger',
            ],
            [
                'code' => 'MOUNT_CHANGED',
                'name' => 'Cambio mount',
                'description' => 'Il personaggio ha usato una mount',
                'category' => 'violation',
                'points' => 0,
                'is_critical' => true,
                'color' => 'secondary',
            ],
            [
                'code' => 'GLIDING',
                'name' => 'Uso del Glider',
                'description' => 'Il personaggio ha utilizzato il glider, azione vietata dal regolamento.',
                'category' => 'violation',
                'points' => 0,
                'is_critical' => true,
                'color' => 'danger',
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
            [
                'code' => 'ACCOUNT_IN_GUILD',
                'name' => 'Account in una gilda',
                'description' => 'L’account appartiene a una o più gilde — vietato dal regolamento del concorso',
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
                'code' => 'EQUIPMENT_INVALID',
                'name' => 'Equipaggiamento non consentito',
                'description' => 'Il personaggio ha equipaggiato uno o più oggetti non permessi dal regolamento (solo l’arma è consentita).',
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
