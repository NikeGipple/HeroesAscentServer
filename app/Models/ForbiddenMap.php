<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ForbiddenMap extends Model
{
    protected $table = 'forbidden_maps';

    protected $fillable = [
        'map_id',
        'name',
        'type',
    ];
}
