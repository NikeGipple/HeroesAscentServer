<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Support\HeroesAscent\Bypass;

class Account extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'api_key',
        'account_token',
        'account_name',
        'active',
    ];

    // Relazione con i personaggi
    public function characters()
    {
        return $this->hasMany(Character::class);
    }

    // Controlla se l'account è nella lista di bypass
    public function isBypass(): bool
    {
        return Bypass::isBypassAccount($this->account_name ?? '');
    }
}
