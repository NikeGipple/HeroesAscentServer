<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class CharacterEvent extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'character_events';

    protected $fillable = [
        'character_id',
        'event_code',
        'title',
        'details',
        'map_id',
        'map_type',
        'profession',
        'elite_spec',
        'race',
        'state',
        'group_type',
        'group_count',
        'commander',
        'is_login',
        'pos_x',
        'pos_y',
        'pos_z',
        'mount_index',      
        'level',            
        'effective_level',
        'detected_at', 
    ];

    protected $casts = [
        'detected_at' => 'datetime',
        'commander'   => 'boolean',
        'is_login'    => 'boolean',
        'points'      => 'integer',
    ];

    /** Relazioni */
    public function character()
    {
        return $this->belongsTo(Character::class);
    }

    public function eventType()
    {
        return $this->belongsTo(EventType::class, 'event_code', 'code');
    }

    /**
     * Registra un evento:
     * - imposta automaticamente 'points' dal tipo evento
     * - aggiorna il punteggio del personaggio
     * - se critico, marca il personaggio come squalificato
     */
    public static function record(Character $character, string $code, array $context = [])
    {
        $eventType = EventType::where('code', $code)->firstOrFail();

        return DB::transaction(function () use ($character, $eventType, $code, $context) {
            // Crea l'evento senza 'points' nel mass assignment
            $event = self::create(array_merge($context, [
                'character_id' => $character->id,
                'event_code'   => $code,
                'detected_at'  => $context['detected_at'] ?? now(),
            ]));

            // Imposta il punteggio dal tipo e salva
            $event->points = (int) $eventType->points;
            $event->save();

            // Aggiorna punteggio personaggio
            if ($event->points !== 0) {
                $character->increment('score', $event->points);
            }

            // Se evento critico → squalifica personaggio
            if ($eventType->is_critical && is_null($character->disqualified_at)) {
                $character->update(['disqualified_at' => now()]);
            }

            return $event;
        });
    }

    /** Scopes utili */
    public function scopeLogin($query)
    {
        return $query->where('is_login', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
