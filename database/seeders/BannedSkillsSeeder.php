<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class BannedSkillsSeeder extends Seeder
{
    public function run(): void
    {
        // Lista ID delle skill da bannare
        $banned = [
            10541, // Necro - Summon Bone Minions
            10533, // Necro - Summon Bone Fiend
        ];

        foreach ($banned as $gw2Id) {

            $data = Http::get("https://api.guildwars2.com/v2/skills/{$gw2Id}?lang=en")->json();

            DB::table('banned_skills')->updateOrInsert(
                ['gw2_id' => $gw2Id],
                [
                    'name'         => $data['name'] ?? "Unknown Skill",
                    'icon_url'     => $data['icon'] ?? null,
                    'slot'         => $data['slot'] ?? null,
                    'type'         => $data['type'] ?? null,
                    'weapon_type'  => $data['weapon_type'] ?? null,
                    'description'  => $data['description'] ?? null,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]
            );

            echo "Skill $gw2Id seeded\n";
        }
    }
}
