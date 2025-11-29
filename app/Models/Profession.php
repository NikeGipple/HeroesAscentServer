<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profession extends Model
{
    protected $fillable = [
        'name',
        'icon',
        'icon_big',
    ];

    public function specializations()
    {
        return $this->hasMany(Specialization::class, 'profession', 'name');
    }
}
