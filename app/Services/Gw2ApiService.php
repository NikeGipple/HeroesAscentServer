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
     * Effettua una chiamata sicura alle API GW2 
     */
    private static function safeRequest(string $url, string $apiKey = null, array $params = [])
    {
        static $lastRequestTime = 0;

        // === Rate limiting locale ===
        // Max 5 richieste/sec ovvero una ogni 0.2s
        $elapsed = microtime(true) - $lastRequestTime;
        if ($elapsed < 0.2) {
            usleep((0.2 - $elapsed) * 1_000_000);
        }
        $lastRequestTime = microtime(true);

        try {
            $response = Http::withOptions(['timeout' => 30])
                ->retry(2, 2000)
                ->when($apiKey, fn($req) => $req->withToken($apiKey))
                ->get($url, $params);

            // === Gestione Rate Limit ===
            if ($response->status() === 429) {
                $retryAfter = (int) $response->header('Retry-After', 5);
                Log::warning("GW2 API rate limit: 429 Too Many Requests. Attesa {$retryAfter}s...");
                sleep($retryAfter);
                return self::safeRequest($url, $apiKey, $params);
            }

            // Se header X-Rate-Limit-Remaining è a 0, rispetta il reset
            $remaining = (int) $response->header('X-Rate-Limit-Remaining', 1);
            if ($remaining <= 0) {
                $reset = (int) $response->header('X-Rate-Limit-Reset', 5);
                Log::info("GW2 API: esaurite le richieste disponibili. Pausa {$reset}s...");
                sleep($reset);
            }

            if ($response->failed()) {
                Log::warning("Chiamata fallita a {$url} ({$response->status()})", [
                    'body' => $response->body(),
                ]);
                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::warning("Errore chiamando {$url}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Recupera le informazioni di una API key e i permessi associati.
     */
    public static function getTokenInfo(string $apiKey): ?array
    {
        $data = self::safeRequest('https://api.guildwars2.com/v2/tokeninfo', $apiKey);
        if (!$data) {
            Log::warning("Impossibile recuperare tokeninfo per API key {$apiKey}");
        }
        return $data;
    }

    /**
     * Ottiene le informazioni di base dell’account.
     */
    public static function getAccount(string $apiKey): ?array
    {
        $data = self::safeRequest('https://api.guildwars2.com/v2/account', $apiKey);
        if (!$data) {
            Log::warning("Impossibile recuperare account info per API key {$apiKey}");
        }
        return $data;
    }

    /**
     * Calcola (in modo stimato) il totale degli Achievement Points.
     * Include paginazione, batching e cache (10 minuti).
     */
    public static function getAchievementPoints(string $apiKey): int
    {
        return Cache::remember("gw2_ap_total_{$apiKey}", now()->addMinutes(10), function () use ($apiKey) {
            $doneIds = collect();
            $page = 0;
            $pageSize = 200;

            Log::info("Inizio calcolo Achievement Points per API key {$apiKey}");

            while (true) {
                $data = self::safeRequest(
                    'https://api.guildwars2.com/v2/account/achievements',
                    $apiKey,
                    ['page' => $page, 'page_size' => $pageSize]
                );

                if (!$data) {
                    Log::warning("Nessun dato da /account/achievements (page {$page})");
                    break;
                }

                $chunk = collect($data)->where('done', true)->pluck('id');
                if ($chunk->isEmpty()) break;

                $doneIds = $doneIds->merge($chunk);
                if (count($data) < $pageSize) break;
                $page++;
            }

            if ($doneIds->isEmpty()) {
                Log::info("Nessun achievement completato trovato per questa API key.");
                return 0;
            }

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

            Log::info("Achievement points stimati per API key {$apiKey}: {$total}");
            return (int) $total;
        });
    }

    /**
     * Restituisce la lista dei personaggi dell’account.
     */
    public static function getCharacters(string $apiKey): ?array
    {
        $data = self::safeRequest('https://api.guildwars2.com/v2/characters', $apiKey);
        if (is_array($data)) {
            Log::info("Trovati " . count($data) . " personaggi per l’account.");
        }
        return $data;
    }
}
