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
     * Esegue una richiesta sicura con gestione di rate-limit, retry e timeout.
     */
    private static function safeRequest(string $url, string $apiKey = null, array $params = [], int $timeout = 30)
    {
        static $lastRequestTime = 0;

        // Throttle a 5 richieste/sec
        $elapsed = microtime(true) - $lastRequestTime;
        if ($elapsed < 0.2) {
            usleep((0.2 - $elapsed) * 1_000_000);
        }
        $lastRequestTime = microtime(true);

        try {
            $response = Http::withOptions(['timeout' => $timeout])
                ->retry(3, 2000, function ($exception, $request) {
                    // Ritenta su errori di rete e 5xx
                    if ($exception instanceof \Illuminate\Http\Client\ConnectionException) {
                        return true;
                    }
                    $res = method_exists($exception, 'response') ? $exception->response() : null;
                    $status = $res ? $res->status() : null;
                    return in_array($status, [500, 502, 503, 504]);
                })
                ->when($apiKey, fn($req) => $req->withToken($apiKey))
                ->get($url, $params);

            if ($response->status() === 429) {
                $retryAfter = (int) $response->header('Retry-After', 5);
                Log::warning("GW2 API 429 Rate Limit – attendo {$retryAfter}s…");
                sleep($retryAfter);
                return self::safeRequest($url, $apiKey, $params, $timeout);
            }

            if ($response->failed()) {
                Log::warning("Chiamata fallita a {$url} ({$response->status()})");
                return null;
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::warning("Errore chiamando {$url}: ".$e->getMessage());
            return null;
        }
    }

    /**
     * Ottiene le informazioni base dell’API key.
     */
    public static function getTokenInfo(string $apiKey): ?array
    {
        $data = self::safeRequest('https://api.guildwars2.com/v2/tokeninfo', $apiKey, [], 60);
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
        $data = self::safeRequest('https://api.guildwars2.com/v2/account', $apiKey, [], 60);
        if (!$data) {
            Log::warning("Impossibile recuperare account info per API key {$apiKey}");
        }
        return $data;
    }

    /**
     * Calcola il totale (stimato) degli Achievement Points.
     */
    public static function getAchievementPoints(string $apiKey): int
    {
        $cacheKey   = "gw2_ap_total_{$apiKey}";
        $partialKey = "gw2_ap_partial_{$apiKey}";

        // Usa cache esistente se presente
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return (int) $cached;
        }

        $doneIds   = Cache::get($partialKey, collect());
        $page      = 0;
        $pageSize  = 200;
        $start     = microtime(true);
        $hadError  = false;

        Log::info("Inizio calcolo AP per API key {$apiKey} (parziale: ".$doneIds->count().")");

        $previousIds = collect();

        while (true) {
            if (microtime(true) - $start > 300) {
                Log::warning("Stop automatico: > 300 s");
                break;
            }
            if ($page >= 25) {
                Log::warning("Stop automatico: > 25 pagine");
                break;
            }

            Log::info("Richiesta pagina {$page} di /account/achievements…");
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

            if (!$data) {
                $hadError = true;
                Log::warning("Pagina {$page} non valida (null) — stop dataset.");
                break;
            }
            if ($count === 0) {
                Log::warning("Pagina {$page} vuota — fine dataset.");
                break;
            }

            $chunk = collect($data)->where('done', true)->pluck('id');

            if ($chunk->diff($previousIds)->isEmpty()) {
                Log::warning("Pagina {$page} duplicata — stop loop API.");
                break;
            }

            $doneIds   = $doneIds->merge($chunk);
            $previousIds = $chunk;

            Cache::put($partialKey, $doneIds, 600);

            if ($count < $pageSize) {
                Log::info("Pagina {$page} incompleta — fine dataset.");
                break;
            }
            $page++;
        }

        if ($doneIds->isEmpty()) {
            if ($hadError) {
                throw new \RuntimeException("GW2 API temporaneamente non disponibile. Riprova più tardi.");
            }
            Log::info("Nessun achievement completato trovato.");
            Cache::put($cacheKey, 0, 600);
            return 0;
        }

        Log::info("Raccolti ".$doneIds->count()." achievements completati (inizio calcolo punti).");

        $total = 0;
        foreach ($doneIds->chunk(50) as $chunk) {
            Log::info("Richiesta dettagli achievements per ".count($chunk)." ID…");
            $details = self::safeRequest(
                'https://api.guildwars2.com/v2/achievements',
                null,
                ['ids' => $chunk->implode(',')],
                60
            );

            if (!$details) {
                $hadError = true;
                Log::warning("Errore/timeout batch da ".count($chunk)." ID.");
                continue;
            }

            $total += collect($details)->sum(function ($a) {
                return collect($a['tiers'] ?? [])->sum('points');
            });

            // Blocco > 2500 AP
            if ($total > 2500) {
                Log::warning("Interruzione: API key {$apiKey} ha superato il limite di 2500 Achievement Points.");
                throw new \RuntimeException(
                    "L'account associato alla chiave specificata supera il limite di 2500 Achievement Points."
                );
            }
        }

        Log::info("Achievement points stimati per API key {$apiKey}: {$total}");

        // Cache finale solo se nessun errore
        if (!$hadError) {
            Cache::forget($partialKey);
            Cache::put($cacheKey, (int)$total, 600);
        }

        return (int) $total;
    }

    /**
     * Restituisce la lista dei personaggi dell’account.
     */
    public static function getCharacters(string $apiKey): ?array
    {
        $data = self::safeRequest('https://api.guildwars2.com/v2/characters', $apiKey, [], 60);
        if (is_array($data)) {
            Log::info("Trovati ".count($data)." personaggi per l’account.");
        }
        return $data;
    }
}
