<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BannedSkill extends Model
{
    protected $table = 'banned_skills';

    protected $fillable = [
        'gw2_id',
        'name',
        'icon_url',
        'slot',
        'type',
        'weapon_type',
        'description',
    ];

    protected $casts = [
        'gw2_id' => 'integer',
    ];

    /**
     * Verifica se una skill GW2 è bannata.
     */
    public static function isBanned(int $gw2Id): bool
    {
        return self::where('gw2_id', $gw2Id)->exists();
    }
}
