<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'api_key',
        'account_token',
        'active',
    ];

    // Relazione con i personaggi
    public function characters()
    {
        return $this->hasMany(Character::class);
    }
}
