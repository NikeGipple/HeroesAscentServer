<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Account;
use App\Services\Gw2ApiService;

class RegistrationController extends Controller
{
    public function register(Request $request)
    {   
        Log::info("=== Incoming Character Registration ===", [
            'ip'      => $request->ip(),
            'payload' => $request->all(),
        ]);

        $apiKey = $request->input('api_key');
        $accountName = $request->input('account_name');

        if (empty($apiKey)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Missing API key'
            ], 400);
        }

        // ✅ 1. Verifica validità API key tramite Gw2ApiService
        try {
            $tokenInfo = Gw2ApiService::getTokenInfo($apiKey);

            if (!$tokenInfo) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Servizi Guild Wars 2 non disponibili. Riprova più tardi.'
                ], 503);
            }

            // Controllo permessi (la chiave deve avere almeno "account" e "progression")
            if (!in_array('account', $tokenInfo['permissions']) || !in_array('progression', $tokenInfo['permissions'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'API key non valida: mancano i permessi necessari (account, progression).'
                ], 401);
            }
        } catch (\Throwable $e) {
            Log::warning("GW2 API error (tokeninfo): " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Servizi Guild Wars 2 temporaneamente non disponibili.'
            ], 503);
        }

        // ✅ 2. Verifica che l’account dichiarato corrisponda a quello ufficiale di ArenaNet
        try {
            $accountData = Gw2ApiService::getAccount($apiKey);

            if (!$accountData || empty($accountData['name'])) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Impossibile verificare il nome account tramite API ufficiale.'
                ], 503);
            }

            if (strcasecmp($accountData['name'], $accountName) !== 0) {
                Log::warning("Account name mismatch: API={$accountData['name']} vs Provided={$accountName}");
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Il nome account fornito non corrisponde al proprietario della API key.'
                ], 403);
            }

            Log::info("Verifica nome account riuscita per {$accountData['name']}");
        } catch (\Throwable $e) {
            Log::error("GW2 API error (account verification): " . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Errore durante la verifica del nome account. Riprova più tardi.'
            ], 503);
        }


        // ✅ 3. Recupera e controlla i punti Achievement
        Log::info("DEBUG — chiamata a Gw2ApiService avviata");

        try {
            $achievementPoints = Gw2ApiService::getAchievementPoints($apiKey);
            Log::info("Account '{$accountName}' ha stimato {$achievementPoints} achievement points.");
        } catch (\RuntimeException $e) {
            Log::warning("Registrazione interrotta per API key {$apiKey}: {$e->getMessage()}");

            return response()->json([
                'status'  => 'error',
                'message' => 'Account non idoneo: supera i 2500 Achievement Points consentiti.'
            ], 403);
        }

        // ✅ 4. Se l'account esiste già, restituisci il token esistente
        $account = Account::where('api_key', $apiKey)
            ->orWhere('account_name', $accountName)
            ->first();

        if ($account) {
            return response()->json([
                'status' => 'ok',
                'message' => 'already_registered',
                'account_token' => $account->account_token,
            ]);
        }

        // ✅ Crea nuovo token univoco
        $accountToken = Str::uuid()->toString();

        // ✅ Salva nuovo account
        $account = Account::create([
            'api_key'       => $apiKey,
            'account_token' => $accountToken,
            'account_name'  => $accountName,
            'active'        => true,
        ]);

        return response()->json([
            'status'        => 'ok',
            'message'       => 'registered',
            'account_token' => $accountToken,
        ]);
    }
}
