<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BannedTrait extends Model
{
    protected $table = 'banned_traits';

    protected $fillable = [
        'gw2_id',
        'name',
        'icon_url',
        'slot',
        'specialization',
        'description',
    ];

    protected $casts = [
        'gw2_id'        => 'integer',
        'specialization' => 'integer',
    ];

    /**
     * Verifica se un trait GW2 è bannato.
     */
    public static function isBanned(int $gw2Id): bool
    {
        return self::where('gw2_id', $gw2Id)->exists();
    }
}
