<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Servizio per interfacciarsi con le API pubbliche di Guild Wars 2.
 * Gestisce retry, timeout e rate limit di base.
 */
class Gw2ApiService
{
    /**
     * Effettua una chiamata generica alle API GW2 gestendo automaticamente
     * timeout, retry e rate limit.
     */
    private static function safeRequest(string $url, string $apiKey = null, array $params = [])
    {
        try {
            $response = Http::withOptions(['timeout' => 30])
                ->retry(2, 2000)
                ->when($apiKey, fn($req) => $req->withToken($apiKey))
                ->get($url, $params);

            // Rate limit: se 429 o header Retry-After
            if ($response->status() === 429) {
                $retryAfter = (int) $response->header('Retry-After', 5);
                Log::warning("Rate limit ArenaNet raggiunto — attesa {$retryAfter}s");
                sleep($retryAfter);
                return self::safeRequest($url, $apiKey, $params);
            }

            return $response->successful() ? $response->json() : null;
        } catch (\Exception $e) {
            Log::warning("Errore chiamando {$url}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Verifica la validità di una API key e restituisce le info base.
     */
    public static function getTokenInfo(string $apiKey): ?array
    {
        return self::safeRequest('https://api.guildwars2.com/v2/tokeninfo', $apiKey);
    }

    /**
     * Ottiene le informazioni di base dell’account.
     */
    public static function getAccount(string $apiKey): ?array
    {
        return self::safeRequest('https://api.guildwars2.com/v2/account', $apiKey);
    }

    /**
     * Calcola (in modo stimato) i punti Achievement totali.
     */
    public static function getAchievementPoints(string $apiKey): int
    {
        return Cache::remember("gw2_ap_total_{$apiKey}", now()->addMinutes(10), function () use ($apiKey) {
            $doneIds = collect();

            // Paginazione semplice
            $page = 0;
            $pageSize = 200;
            while (true) {
                $data = self::safeRequest(
                    'https://api.guildwars2.com/v2/account/achievements',
                    $apiKey,
                    ['page' => $page, 'page_size' => $pageSize]
                );

                if (!$data) break;

                $chunk = collect($data)->where('done', true)->pluck('id');
                if ($chunk->isEmpty()) break;

                $doneIds = $doneIds->merge($chunk);

                if (count($data) < $pageSize) break;
                $page++;
            }

            if ($doneIds->isEmpty()) return 0;

            // Dettagli achievement per calcolo punti
            $total = 0;
            foreach ($doneIds->chunk(200) as $chunk) {
                $details = self::safeRequest(
                    'https://api.guildwars2.com/v2/achievements',
                    null,
                    ['ids' => $chunk->implode(',')]
                );

                if (!$details) continue;

                $total += collect($details)->sum(function ($a) {
                    $tiers = $a['tiers'] ?? [];
                    return collect($tiers)->sum('points');
                });
            }

            Log::info("Achievement points stimati per API key: {$total}");
            return (int) $total;
        });
    }
}
