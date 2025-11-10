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
    private static function safeRequest(string $url, string $apiKey = null, array $params = [], int $timeout = 30)
    {
        static $lastRequestTime = 0;

        // Limita a 5 richieste/sec (0.2s di intervallo)
        $elapsed = microtime(true) - $lastRequestTime;
        if ($elapsed < 0.2) {
            usleep((0.2 - $elapsed) * 1_000_000);
        }
        $lastRequestTime = microtime(true);

        try {
            $response = Http::withOptions(['timeout' => $timeout])
                ->retry(2, 2000)
                ->when($apiKey, fn($req) => $req->withToken($apiKey))
                ->get($url, $params);

            if ($response->status() === 429) {
                $retryAfter = (int) $response->header('Retry-After', 5);
                Log::warning("GW2 API rate limit 429: attendo {$retryAfter}s...");
                sleep($retryAfter);
                return self::safeRequest($url, $apiKey, $params);
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

            $start = microtime(true);
            Log::info("Inizio calcolo Achievement Points per API key {$apiKey}");

            while (true) {
                // interruzione dopo 20s totali
                if (microtime(true) - $start > 20) {
                    Log::warning("Interruzione automatica: superato limite 20s per /account/achievements");
                    break;
                }

                Log::info("Richiesta pagina {$page} di /account/achievements...");
                $data = self::safeRequest(
                    'https://api.guildwars2.com/v2/account/achievements',
                    $apiKey,
                    ['page' => $page, 'page_size' => $pageSize]
                );

                $count = $data ? count($data) : 0;
                Log::info("Pagina {$page} completata ({$count} record).");

                if (!$data || $count === 0) break;

                $chunk = collect($data)->where('done', true)->pluck('id');
                if ($chunk->isEmpty()) break;

                $doneIds = $doneIds->merge($chunk);

                if ($count < $pageSize) break;
                $page++;
            }

            if ($doneIds->isEmpty()) {
                Log::info("Nessun achievement completato trovato per questa API key.");
                return 0;
            }

            $total = 0;
            foreach ($doneIds->chunk(100) as $chunk) {
                Log::info("Richiesta dettagli achievements per " . count($chunk) . " ID...");
                $details = self::safeRequest(
                    'https://api.guildwars2.com/v2/achievements',
                    null,
                    ['ids' => $chunk->implode(',')],
                    60
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
