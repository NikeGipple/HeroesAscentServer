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

        // Throttle 5 req/sec
        $elapsed = microtime(true) - $lastRequestTime;
        if ($elapsed < 0.2) usleep((0.2 - $elapsed) * 1_000_000);
        $lastRequestTime = microtime(true);

        try {
            $response = Http::withOptions(['timeout' => $timeout])
                // retry(count, sleepMs, when)
                ->retry(3, 1000, function ($exception, $request) {
                    // Ritenta su errori di rete/timeout e 5xx
                    if (method_exists($exception, 'getCode') && $exception->getCode() === 0) {
                        return true; // es. cURL 28
                    }
                    $res = method_exists($exception, 'response') ? $exception->response() : null;
                    $status = $res ? $res->status() : null;
                    return in_array($status, [500, 502, 503, 504]);
                })
                ->when($apiKey, fn($req) => $req->withToken($apiKey))
                ->get($url, $params);

            // 429: rispetta Retry-After e riprova
            if ($response->status() === 429) {
                $retryAfter = (int) $response->header('Retry-After', 5);
                sleep($retryAfter);
                // jitter minimo
                usleep(random_int(50_000, 150_000));
                return self::safeRequest($url, $apiKey, $params, $timeout);
            }

            if ($response->failed()) {
                // Non restituire JSON su failed, segnala null
                \Log::warning("Chiamata fallita a {$url}: HTTP ".$response->status());
                return null;
            }

            return $response->json();
        } catch (\Throwable $e) {
            \Log::warning("Errore chiamando {$url}: ".$e->getMessage());
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
        $cacheKey   = "gw2_ap_total_{$apiKey}";
        $partialKey = "gw2_ap_partial_{$apiKey}";

        // se esiste cache valida, usa quella
        $cached = Cache::get($cacheKey);
        if ($cached !== null) return (int) $cached;

        $doneIds   = Cache::get($partialKey, collect());
        $page      = 0;
        $pageSize  = 200;
        $start     = microtime(true);
        $hadError  = false; // <— traccia errori per evitare cache 0

        Log::info("Inizio calcolo Achievement Points per API key {$apiKey} (parziale: ".$doneIds->count().")");
        $previousIds = collect();

        while (true) {
            if (microtime(true) - $start > 300) { Log::warning("Stop: >300s"); break; }
            if ($page >= 25) { Log::warning("Stop: >25 pagine"); break; }

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

            if (!$data) {
                $hadError = true; // <— segna errore remoto (es. 502)
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

            $doneIds = $doneIds->merge($chunk);
            $previousIds = $chunk;

            Cache::put($partialKey, $doneIds, 600);

            if ($count < $pageSize) { Log::info("Pagina {$page} incompleta — fine dataset."); break; }
            $page++;
        }

        if ($doneIds->isEmpty()) {
            // Se è vuoto e c’è stato un errore (502), **non cachare 0** e segnala indisponibilità
            if ($hadError) {
                throw new \RuntimeException("GW2 API temporaneamente non disponibile. Riprova più tardi.");
            }
            Log::info("Nessun achievement completato trovato.");
            Cache::put($cacheKey, 0, 600); // opzionale: puoi anche evitare
            return 0;
        }

        Log::info("Raccolti ".$doneIds->count()." achievements completati (inizio calcolo punti).");

        $total = 0;
        foreach ($doneIds->chunk(50) as $chunk) {
            Log::info("Richiesta dettagli achievements per ".count($chunk)." ID...");
            $details = self::safeRequest(
                'https://api.guildwars2.com/v2/achievements',
                null,
                ['ids' => $chunk->implode(',')],
                60
            );
            if (!$details) {
                $hadError = true; // segna errore su batch dettagli
                Log::warning("Errore/timeout batch da ".count($chunk)." ID.");
                continue;
            }

            $total += collect($details)->sum(function ($a) {
                return collect($a['tiers'] ?? [])->sum('points');
            });

            // blocco >2500 AP (chiave non anonimizzata)
            if ($total > 2500) {
                Log::warning("Interruzione: API key {$apiKey} ha superato il limite di 2500 AP.");
                throw new \RuntimeException("L'account associato alla chiave specificata supera il limite di 2500 AP.");
            }
        }

        Log::info("Achievement points stimati per API key {$apiKey}: {$total}");

        // Cache finale SOLO se non abbiamo avuto errori critici
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
        $data = self::safeRequest('https://api.guildwars2.com/v2/characters', $apiKey);
        if (is_array($data)) {
            Log::info("Trovati " . count($data) . " personaggi per l’account.");
        }
        return $data;
    }
}
