<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Services\Gw2ApiService;

class ProfessionsSeeder extends Seeder
{
    public function run(): void
    {
        echo "⏳ Importing professions...\n";

        $list = Gw2ApiService::safeRequest(
            "https://api.guildwars2.com/v2/professions"
        );

        if (!$list || !is_array($list)) {
            echo "❌ Errore caricando lista professioni\n";
            return;
        }

        foreach ($list as $professionName) {

            echo "➡️ Importing: {$professionName}\n";

            $data = Gw2ApiService::safeRequest(
                "https://api.guildwars2.com/v2/professions/{$professionName}"
            );

            if (!$data) {
                echo "❌ Errore importando {$professionName}\n";
                continue;
            }

            DB::table('professions')->updateOrInsert(
                ['name' => $data['id']],  // es: "Mesmer", "Guardian"
                [
                    'icon'       => $data['icon']      ?? null,
                    'icon_big'   => $data['icon_big']  ?? null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            echo "✔️ Professione importata: {$data['id']}\n";
        }

        echo "🎉 Importazione professioni completata!\n";
    }
}
