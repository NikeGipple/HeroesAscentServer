<?php

namespace App\Services;

use App\Models\Character;
use App\Models\CharacterEvent;

class CharacterEventRecorder
{
    /**
     * Costruisce il context per il record dell'evento.
     */
    public static function buildContext(array $data, string $eventCode, int $state): array
    {
        return [
        'map_id'      => (int)$data['map_id'],
        'map_type'    => $data['map_type'] ?? null,
        'profession'  => $data['profession'] ?? null,
        'elite_spec'  => $data['elite_spec'] ?? null,
        'race'        => $data['race'] ?? null,
            'state'       => $state,
            'group_type'  => $data['group_type'] ?? null,
            'group_count' => $data['group_count'] ?? null,
            'commander'   => $data['commander'] ?? false,
            'is_login'    => ($eventCode === 'LOGIN'),

            'pos_x'       => $data['position']['x'] ?? null,
            'pos_y'       => $data['position']['y'] ?? null,
            'pos_z'       => $data['position']['z'] ?? null,

            'mount_index' => $data['mount'] ?? null,

        'level'            => $data['level'] ?? null,
        'effective_level'  => $data['effective_level'] ?? null,

        'details'     => $data['details'] ?? ("Client event: {$eventCode}"),

        'buff_id'     => $data['buff_id'] ?? null,
        'buff_name'   => $data['buff_name'] ?? null,
    ];
}

    /**
     * Registra l'evento e aggiorna lo stato del personaggio.
     */
    public static function recordEvent(
        Character $character,
        string $eventCode,
        array $context,
        bool $bypass
    ) {
        $event = CharacterEvent::record($character, $eventCode, $context, $bypass);

        $character->refresh();

        return $event;
    }
}
