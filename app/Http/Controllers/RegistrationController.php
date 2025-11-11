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
                'message' => 'missing_key',
            ], 400);
        }

        if (empty($accountName)) {
            return response()->json([
                'status' => 'error',
                'message' => 'missing_account_name',
            ], 400);
        }

        // ✅ 1. Verifica validità API key tramite Gw2ApiService
        try {
            $tokenInfo = Gw2ApiService::getTokenInfo($apiKey);

            if (!$tokenInfo) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'gw2_invalid_api_key',
                ], 503);
            }

            // Controllo permessi (la chiave deve avere almeno "account" e "progression")
            if (!in_array('account', $tokenInfo['permissions']) || !in_array('progression', $tokenInfo['permissions'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'invalid_permissions',
                ], 401);
            }
        } catch (\Throwable $e) {
            Log::warning("GW2 API error (tokeninfo): " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'gw2_api_down',
            ], 503);
        }

        // ✅ 2. Verifica che l’account dichiarato corrisponda a quello ufficiale di ArenaNet
        try {
            $accountData = Gw2ApiService::getAccount($apiKey);

            if (!$accountData || empty($accountData['name'])) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'gw2_api_unavailable',
                ], 503);
            }

            if (strcasecmp($accountData['name'], $accountName) !== 0) {
                Log::warning("Account name mismatch: API={$accountData['name']} vs Provided={$accountName}");
                return response()->json([
                    'status'  => 'error',
                    'message' => 'account_mismatch',
                ], 403);
            }

            Log::info("Verifica nome account riuscita per {$accountData['name']}");
        } catch (\Throwable $e) {
            Log::error("GW2 API error (account verification): " . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'gw2_api_error'
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
                'message'    => 'too_many_ap',
            ], 403);
        }

        // ✅ 4. Se l'account esiste già, restituisci il token esistente
        $account = Account::where('api_key', $apiKey)
            ->orWhere('account_name', $accountName)
            ->first();

        if ($account) {
            return response()->json([
                'status' => 'ok',
                'message'   => 'already_registered',
                'account_token' => $account->account_token,
            ], 200);
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
        ], 200);
    }


    public function check(Request $request)
    {
        $token = $request->input('account_token');
        $accountName = $request->input('account_name');

        if (empty($token) || empty($accountName)) {
            \Log::warning('Check failed: missing fields', [
                'ip' => $request->ip(),
                'token_present' => !empty($token),
                'account_name_present' => !empty($accountName),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'missing_fields',
                'result' => false,
            ], 400);
        }

        // ✅ Cerca l’account corrispondente
        $account = Account::where('account_token', $token)->first();

        if (!$account) {
            \Log::warning('Check failed: token not found in database', [
                'ip' => $request->ip(),
                'account_name' => $accountName,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'account_not_found',
                'result' => false,
            ], 404);
        }

        // ✅ Confronta il nome account
        $match = strcasecmp($account->account_name, $accountName) === 0;

        if ($match) {
            // Successo: account loggato correttamente
            \Log::info('Account validated successfully', [
                'ip' => $request->ip(),
                'account_name' => $accountName,
                'message' => 'Account has just logged in — waiting for character selection event.'
            ]);
        } else {
            // Fallimento: tentativo di accesso all’API di un altro account
            \Log::warning('Token mismatch — potential unauthorized access attempt', [
                'ip' => $request->ip(),
                'account_request' => $accountName,
                'account_real' => $account->account_name,
                'message' => 'The account from this request tried to access another account’s API.'
            ]);
        }

        return response()->json([
            'status' => 'ok',
            'result' => $match,
        ], 200);
    }


}
