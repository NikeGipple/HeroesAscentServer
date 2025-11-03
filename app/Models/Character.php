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
    ];

    /**
     * Relazione con l’account proprietario.
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Relazione con le violazioni registrate.
     */
    public function violations()
    {
        return $this->hasMany(RuleViolation::class);
    }

    /**
     * Helper per verificare se il personaggio è “vivo”.
     */
    public function isAlive(): bool
    {
        return !($this->last_state & 0x02); // esempio flag per stato "Downed/Dead"
    }

    /**
     * Helper per aggiornare lo snapshot ricevuto dall’addon.
     */
    public function updateSnapshot(int $mapId, int $state): void
    {
        $this->update([
            'last_map_id' => $mapId,
            'last_state' => $state,
            'last_check_at' => now(),
        ]);
    }
}
