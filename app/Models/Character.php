<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Character extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'account_id',
        'name',
        'level',
        'profession',
        'last_map_id',
        'last_state',
        'last_check_at',
        'score',
        'disqualified_at',
    ];

    protected $casts = [
        'last_check_at' => 'datetime',
        'disqualified_at' => 'datetime',
    ];

    /**
     * Account proprietario del personaggio.
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Eventi (positivi o negativi) associati al personaggio.
     */
    public function events()
    {
        return $this->hasMany(CharacterEvent::class);
    }

    /**
     * Helper per verificare se il personaggio è vivo.
     */
    public function isAlive(): bool
    {
        // Stato 0x02 = Downed/Dead secondo RTAPI
        return !($this->last_state & 0x02);
    }

    /**
     * Aggiorna lo snapshot runtime ricevuto dall’addon.
     */
    public function updateSnapshot(int $mapId, int $state): void
    {
        $this->update([
            'last_map_id' => $mapId,
            'last_state' => $state,
            'last_check_at' => now(),
        ]);
    }

    /**
     * Aggiunge o rimuove punti dal punteggio.
     */
    public function addScore(int $points): void
    {
        $this->increment('score', $points);
    }

    public function subtractScore(int $points): void
    {
        $this->decrement('score', $points);
    }

    /**
     * Squalifica il personaggio.
     */
    public function disqualify(string $reason): void
    {
        $this->update(['disqualified_at' => now()]);

        $this->events()->create([
            'event_code' => 'DISQUALIFIED',
            'title' => 'Personaggio squalificato',
            'details' => $reason,
            'points' => -999,
            'detected_at' => now(),
        ]);
    }

    public function isDisqualified(): bool
    {
        return !is_null($this->disqualified_at);
    }
}
