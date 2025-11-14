<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ForbiddenMap;

class ForbiddenMapSeeder extends Seeder
{
    public function run(): void
    {
        ForbiddenMap::insert([

            // === CITY MAPS ===
            [
                'map_id' => 91,
                'name'   => 'The Grove',
                'type'   => 'city',
            ],
            [
                'map_id' => 18,
                'name'   => 'Divinity\'s Reach',
                'type'   => 'city',
            ],
            [
                'map_id' => 218,
                'name'   => 'Black Citadel',
                'type'   => 'city',
            ],
            [
                'map_id' => 326,
                'name'   => 'Hoelbrak',
                'type'   => 'city',
            ],
            [
                'map_id' => 139,
                'name'   => 'Rata Sum',
                'type'   => 'city',
            ],
            [
                'map_id' => 50,
                'name'   => 'Lion\'s Arch',
                'type'   => 'city',
            ],

            // === WvW MAPS ===
            [
                'map_id' => 899,
                'name'   => 'Obsidian Sanctum',
                'type'   => 'wvw',
            ],
            [
                'map_id' => 38,
                'name'   => 'Eternal Battlegrounds',
                'type'   => 'wvw',
            ],
            [
                'map_id' => 1099,
                'name'   => 'Red Desert Borderlands',
                'type'   => 'wvw',
            ],
            [
                'map_id' => 96,
                'name'   => 'Blue Alpine Borderlands',
                'type'   => 'wvw',
            ],
            [
                'map_id' => 95,
                'name'   => 'Green Alpine Borderlands',
                'type'   => 'wvw',
            ],

        ]);
    }
}
