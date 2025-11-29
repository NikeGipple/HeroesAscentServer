<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Specialization extends Model
{
    protected $fillable = [
        'gw2_id',
        'name',
        'profession',
        'icon',
        'background',
    ];

    public function profession()
    {
        return $this->belongsTo(Profession::class, 'profession', 'name');
    }
}
