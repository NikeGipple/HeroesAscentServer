<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CharacterEvent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'character_id',
        'event_code',
        'title',
        'details',
        'points',
        'detected_at',
    ];

    protected $casts = [
        'detected_at' => 'datetime',
    ];

    /**
     * Riferimento al personaggio a cui appartiene l'evento.
     */
    public function character()
    {
        return $this->belongsTo(Character::class);
    }

    /**
     * Applica automaticamente l’effetto dell’evento sul personaggio.
     */
    public function applyToCharacter(): void
    {
        if ($this->points !== 0) {
            $this->character->increment('score', $this->points);
        }

        if (in_array($this->event_code, ['RULE_DOWNED_001', 'DISQUALIFIED'])) {
            $this->character->disqualify($this->title ?? 'Squalificato');
        }
    }
}
