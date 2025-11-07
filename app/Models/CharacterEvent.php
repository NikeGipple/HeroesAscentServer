<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CharacterEvent extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'character_events';

    protected $fillable = [
        'character_id',
        'type',
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
        'detected_at',
    ];

    protected $casts = [
        'detected_at' => 'datetime',
        'commander'   => 'boolean',
        'is_login'    => 'boolean',
    ];

    public function character()
    {
        return $this->belongsTo(Character::class);
    }

    /**
     * Registra un evento già preparato dal controller.
     * Riceve il codice dell’evento e tutti i dati di contesto completi.
     */
    public static function record(Character $character, string $code, array $context = [])
    {
        return self::create(array_merge([
            'character_id' => $character->id,
            'event_code'   => $code,
            'detected_at'  => now(),
        ], $context));
    }

    // === Scopes utili ===
    public function scopeLogin($query)
    {
        return $query->where('is_login', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
