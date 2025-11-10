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

        // ✅ Verifica validità API key tramite Gw2ApiService
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
            Log::warning("Errore recuperando tokeninfo: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Servizi Guild Wars 2 temporaneamente non disponibili.'
            ], 503);
        }

        // ✅ Recupera i punti Achievement stimati
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

        // ✅ Se l'account esiste già, restituisci il token esistente
        $account = Account::where('api_key', $apiKey)->first();
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
