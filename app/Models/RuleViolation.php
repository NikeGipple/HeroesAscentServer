<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RuleViolation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'character_id',
        'violation_code',
        'details',
        'detected_at',
    ];

    /**
     * Relazione con il personaggio a cui appartiene la violazione.
     */
    public function character()
    {
        return $this->belongsTo(Character::class);
    }

    /**
     * Accessor per ottenere la descrizione leggibile della regola (opzionale)
     * Se in futuro hai un dizionario di violazioni, puoi collegarlo qui.
     */
    public function getReadableCodeAttribute(): string
    {
        $map = [
            'RULE_MAP_001' => 'Accesso a mappa vietata',
            'RULE_DEAD_001' => 'Il personaggio è morto',
            'RULE_FOOD_001' => 'Cibo o buff non consentito',
        ];

        return $map[$this->violation_code] ?? $this->violation_code;
    }
}
