<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventType extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'category',
        'points',
        'is_critical',
        'color',
    ];

    public function events()
    {
        return $this->hasMany(CharacterEvent::class, 'event_code', 'code');
    }
}
