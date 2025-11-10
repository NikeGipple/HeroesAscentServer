<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Servizio per interfacciarsi con le API pubbliche di Guild Wars 2.
 */

class Gw2ApiService
{
    /**
     * Calcola (in modo stimato) il totale degli Achievement Points di un account.
     * @param  string  $apiKey
     * @return int
     */
    public static function getAccountAchievementPoints(string $apiKey): int
    {
        return Cache::remember("ap_total_{$apiKey}", now()->addMinutes(10), function () use ($apiKey) {

            // 1️⃣ Ottieni la lista degli achievement dell'account
            $acc = Http::withToken($apiKey)
                ->timeout(10)
                ->get('https://api.guildwars2.com/v2/account/achievements');

            if ($acc->failed()) {
                Log::warning('GW2 API error while fetching /account/achievements', [
                    'status' => $acc->status(),
                    'body' => $acc->body(),
                ]);
                return 0;
            }

            $data = collect($acc->json());

            // 2️⃣ Filtra solo quelli completati
            $doneIds = $data->where('done', true)->pluck('id')->values();

            if ($doneIds->isEmpty()) {
                Log::info('Nessun achievement completato trovato per questa API key.');
                return 0;
            }

            $total = 0;

            // 3️⃣ Recupera i dettagli in blocchi (max 200 ID per richiesta)
            foreach ($doneIds->chunk(200) as $chunk) {
                $ids = $chunk->implode(',');
                $details = Http::timeout(10)
                    ->get("https://api.guildwars2.com/v2/achievements?ids={$ids}");

                if ($details->failed()) {
                    Log::warning('GW2 API failed while fetching /achievements details', [
                        'ids' => $ids,
                        'status' => $details->status(),
                    ]);
                    continue;
                }

                // 4️⃣ Somma i punti dei tiers per ogni achievement completato
                $total += collect($details->json())->sum(function ($achievement) {
                    $tiers = $achievement['tiers'] ?? [];
                    return collect($tiers)->sum('points');
                });
            }

            Log::info("Achievement points stimati per account: {$total}");

            return (int) $total;
        });
    }
}
