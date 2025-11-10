<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * Servizio per interfacciarsi con le API pubbliche di Guild Wars 2.
 * Conforme alle best practices ArenaNet:
 *  - Rate limit: 300 burst, 5/sec
 *  - Cache incrementale
 *  - Retry e timeout robusti
 */
class Gw2ApiService
{
    /**
     * Effettua una chiamata sicura alle API GW2 con gestione automatica di:
     * rate limit, retry, timeout e header Retry-After.
     */
    private static function safeRequest(string $url, string $apiKey = null, array $params = [], int $timeout = 30)
    {
        static $lastRequestTime = 0;
        static $requestCount = 0;

        // Limita a 5 richieste/sec (0.2s di intervallo)
        $elapsed = microtime(true) - $lastRequestTime;
        if ($elapsed < 0.2) {
            usleep((0.2 - $elapsed) * 1_000_000);
        }
        $lastRequestTime = microtime(true);
        $requestCount++;

        try {
            $response = Http::withOptions(['timeout' => $timeout])
                ->retry(2, 2000)
                ->when($apiKey, fn($req) => $req->withToken($apiKey))
                ->get($url, $params);

            if ($response->status() === 429) {
                $retryAfter = (int) $response->header('Retry-After', 5);
                Log::warning("GW2 API rate limit 429: attendo {$retryAfter}s...");
                sleep($retryAfter);
                return self::safeRequest($url, $apiKey, $params, $timeout);
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
     * Include paginazione, batching, rate limit e cache incrementale.
     */
    public static function getAchievementPoints(string $apiKey): int
    {
        $cacheKey = "gw2_ap_total_{$apiKey}";
        $partialKey = "gw2_ap_partial_{$apiKey}";

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($apiKey, $partialKey) {
            $doneIds = Cache::get($partialKey, collect());
            $page = 0;
            $pageSize = 200;
            $start = microtime(true);

            Log::info("Inizio calcolo Achievement Points per API key {$apiKey} (parziale: " . $doneIds->count() . ")");

            $previousIds = collect();

            while (true) {
                // Interruzione dopo 300s totali
                if (microtime(true) - $start > 300) {
                    Log::warning("Stop automatico: superato limite 300s totali per /account/achievements");
                    break;
                }

                // Stop di sicurezza: massimo 25 pagine (~5000 achievements)
                if ($page >= 25) {
                    Log::warning("Stop automatico: raggiunto limite massimo di 25 pagine");
                    break;
                }

                Log::info("Richiesta pagina {$page} di /account/achievements...");
                $startPage = microtime(true);
                $data = self::safeRequest(
                    'https://api.guildwars2.com/v2/account/achievements',
                    $apiKey,
                    ['page' => $page, 'page_size' => $pageSize],
                    60
                );

                $elapsedPage = round(microtime(true) - $startPage, 2);
                $count = $data ? count($data) : 0;
                Log::info("Pagina {$page} completata in {$elapsedPage}s ({$count} record).");

                if (!$data || $count === 0) {
                    Log::warning("Pagina {$page} vuota o non valida — fine dataset.");
                    break;
                }

                // Estrai achievements completati
                $chunk = collect($data)->where('done', true)->pluck('id');

                // Stop se la pagina è identica alla precedente (loop)
                if ($chunk->diff($previousIds)->isEmpty()) {
                    Log::warning("Pagina {$page} duplicata — stop per loop API.");
                    break;
                }

                $doneIds = $doneIds->merge($chunk);
                $previousIds = $chunk;

                // Salva cache incrementale ogni pagina
                Cache::put($partialKey, $doneIds, 600);

                if ($count < $pageSize) {
                    Log::info("Pagina {$page} incompleta ({$count}/{$pageSize}) — fine dataset.");
                    break;
                }

                $page++;
            }

            if ($doneIds->isEmpty()) {
                Log::info("Nessun achievement completato trovato per questa API key.");
                return 0;
            }

            Log::info("Raccolti " . $doneIds->count() . " achievements completati (inizio calcolo punti).");

            $total = 0;
            foreach ($doneIds->chunk(50) as $chunk) {
                Log::info("Richiesta dettagli achievements per " . count($chunk) . " ID...");
                $details = self::safeRequest(
                    'https://api.guildwars2.com/v2/achievements',
                    null,
                    ['ids' => $chunk->implode(',')],
                    60
                );

                if (!$details) {
                    Log::warning("Errore o timeout nel batch da " . count($chunk) . " ID.");
                    continue;
                }

                $total += collect($details)->sum(function ($a) {
                    $tiers = $a['tiers'] ?? [];
                    return collect($tiers)->sum('points');
                });
            }

            Log::info("Achievement points stimati per API key {$apiKey}: {$total}");

            // Cancella cache parziale (completato)
            Cache::forget($partialKey);

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
