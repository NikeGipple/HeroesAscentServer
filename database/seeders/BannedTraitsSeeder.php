<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Services\Gw2ApiService;

class BannedTraitsSeeder extends Seeder
{
    public function run(): void
    {
        // Lista dei trait GW2 da bannare
        $banned = [
            780, // Necro - Ritual of life
            783, // Necro - Vampiric
        ];

        foreach ($banned as $gw2Id) {

            $data = Gw2ApiService::safeRequest(
                "https://api.guildwars2.com/v2/traits/{$gw2Id}",
                null,
                ['lang' => 'en'],
                30
            );

            if (!$data) {
                echo "❌ Trait $gw2Id non caricato (API error)\n";
                continue;
            }

            DB::table('banned_traits')->updateOrInsert(
                ['gw2_id' => $gw2Id],
                [
                    'name'            => $data['name'] ?? "Unknown Trait",
                    'icon_url'        => $data['icon'] ?? null,
                    'slot'            => $data['slot'] ?? null,
                    'specialization'  => $data['specialization'] ?? null,
                    'description'     => $data['description'] ?? null,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]
            );

            echo "Trait $gw2Id seeded\n";
        }
    }
}
