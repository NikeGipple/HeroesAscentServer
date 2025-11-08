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
        'last_check_at',
        'score',
        'disqualified_at',
    ];

    protected $casts = [
        'last_check_at'    => 'datetime',
        'disqualified_at'  => 'datetime',
    ];

    /**
     * Account proprietario del personaggio.
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Eventi associati al personaggio.
     */
    public function events()
    {
        return $this->hasMany(CharacterEvent::class);
    }

    /**
     * Applica punti al punteggio del personaggio.
     */
    public function applyScore(int $points): void
    {
        $this->increment('score', $points);
    }

    /**
     * Restituisce true se il personaggio è squalificato.
     */
    public function isDisqualified(): bool
    {
        return !is_null($this->disqualified_at);
    }
}
