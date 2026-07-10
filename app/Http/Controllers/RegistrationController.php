<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Account;
use App\Services\Gw2ApiService;
use App\Support\HeroesAscent\Bypass;

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
            Log::warning("❌ Registration failed: missing API key", [
                'ip' => $request->ip(),
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'missing_key',
            ], 400);
        }

        if (empty($accountName)) {
            Log::warning("❌ Registration failed: missing account name", [
                'ip' => $request->ip(),
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'missing_account_name',
            ], 400);
        }

        // ✅ 1. Verifica validità API key
        try {
            $tokenInfo = Gw2ApiService::getTokenInfo($apiKey);

            if (!$tokenInfo) {
                Log::warning("❌ Registration failed: invalid GW2 API key", [
                    'ip' => $request->ip(),
                    'account_name' => $accountName,
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'gw2_invalid_api_key',
                ], 503);
            }

            // Permessi MINIMI richiesti sull'API key (possono evolvere nel tempo — vedi CLAUDE.md).
            // Ogni scope è richiesto da un controllo lato server specifico:
            //   - account      → /v2/account (nome account, achievement points)
            //   - progression  → /v2/account/achievements (soglia AP)
            //   - characters   → /v2/characters/:id/core (gilda rappresentata, equipmenttabs)
            // NB: la scansione build (trait/skill vietati) usa anche lo scope `builds`;
            //     valutare se aggiungerlo qui — vedi docs/TODO.md.
            $requiredScopes = ['account', 'progression', 'characters'];
            $missingScopes  = array_diff($requiredScopes, $tokenInfo['permissions'] ?? []);

            if (!empty($missingScopes)) {
                Log::warning("❌ Registration failed: invalid API key permissions", [
                    'account_name' => $accountName,
                    'permissions'  => $tokenInfo['permissions'] ?? [],
                    'missing'      => array_values($missingScopes),
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'invalid_permissions',
                ], 401);
            }
        } catch (\Throwable $e) {
            Log::error("⚠️ GW2 API error (tokeninfo): " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'gw2_api_down',
            ], 503);
        }

        // ✅ 2. Verifica nome account
        $bypass = false;
        try {
            $accountData = Gw2ApiService::getAccount($apiKey);

            if (!$accountData || empty($accountData['name'])) {
                Log::error("⚠️ GW2 API unavailable during account name verification");
                return response()->json([
                    'status'  => 'error',
                    'message' => 'gw2_api_unavailable',
                ], 503);
            }

            if (strcasecmp($accountData['name'], $accountName) !== 0) {
                Log::warning("❌ Account mismatch", [
                    'expected' => $accountData['name'],
                    'provided' => $accountName,
                ]);
                return response()->json([
                    'status'  => 'error',
                    'message' => 'account_mismatch',
                ], 403);
            }

            Log::info("✅ Account name verified successfully: {$accountData['name']}");

            $bypass = Bypass::isBypassAccount($accountData['name']);

            Log::info("🧪 BYPASS CHECK", [
                'account_name' => $accountData['name'],
                'bypass' => $bypass,
                'config_bypass_accounts' => config('heroesascent.bypass_accounts'),
                'env_raw' => env('HA_BYPASS_ACCOUNTS'), // solo per debug
            ]);

            // NB: nessun controllo gilda qui. Il vantaggio vietato deriva dalla gilda
            // RAPPRESENTATA sul personaggio in gara (per-personaggio, verificato sulla
            // wiki GW2), non dall'appartenenza dell'account — e in fase di registrazione
            // (solo account, nessun personaggio ancora noto) non c'è un personaggio da
            // controllare. L'enforcement avviene per-personaggio nella scansione periodica
            // (Gw2RulesService::scanAllActiveCharacters → getRepresentedGuild), con
            // un'unica policy condivisa (config heroesascent.allowed_guilds).

        } catch (\Throwable $e) {
            Log::error("⚠️ GW2 API error (account verification): " . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'gw2_api_error'
            ], 503);
        }



        // ✅ 3. Controlla Achievement Points
        if (!$bypass) {
            try {
                $achievementPoints = Gw2ApiService::getAchievementPoints($apiKey);
                Log::info("🏅 Account '{$accountName}' has {$achievementPoints} achievement points.");
            } catch (\RuntimeException $e) {
                Log::warning("❌ Registration stopped — too many AP ({$e->getMessage()})", [
                    'account_name' => $accountName,
                ]);
                return response()->json([
                    'status'  => 'error',
                    'message' => 'too_many_ap',
                ], 403);
            }
        }

        // ✅ 4. Controlla se esiste già
        $account = Account::where('api_key', $apiKey)
            ->orWhere('account_name', $accountName)
            ->first();

        if ($account) {
            Log::info("ℹ️ Account '{$accountName}' already registered. Returning existing token.");
            return response()->json([
                'status' => 'ok',
                'message' => 'already_registered',
                'account_token' => $account->account_token,
            ], 200);
        }

        // ✅ 5. Crea nuovo account
        $accountToken = Str::uuid()->toString();
        $account = Account::create([
            'api_key'       => $apiKey,
            'account_token' => $accountToken,
            'account_name'  => $accountName,
            'active'        => true,
        ]);

        Log::info("✅ Registration successful for '{$accountName}'", [
            'account_id' => $account->id,
            'token' => $accountToken,
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

        \Log::info("=== Incoming /api/check request ===", [
            'ip' => $request->ip(),
            'account_name' => $accountName,
            'token' => $token,
        ]);

        if (empty($token) || empty($accountName)) {
            \Log::warning("❌ Check failed: missing fields", [
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
            \Log::warning("❌ Check failed: token not found in database", [
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
            // ✅ Successo: account loggato correttamente
            \Log::info("🔑 Account {$accountName} has connected to the server", [
                'ip' => $request->ip(),
                'account_id' => $account->id,
                'message' => 'Connection established — waiting for character selection.'
            ]);
        } else {
            // ⚠️ Fallimento: tentativo di accesso non autorizzato
            \Log::warning("⚠️ Token mismatch — potential unauthorized access attempt", [
                'ip' => $request->ip(),
                'account_request' => $accountName,
                'account_real' => $account->account_name,
                'message' => 'The account from this request tried to access another account’s API.'
            ]);
        }

        // ℹ️ Log finale di riepilogo
        \Log::info($match ? "✅ Check completed: token valid" : "❌ Check completed: token invalid", [
            'account_name' => $accountName,
            'result' => $match,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'status' => 'ok',
            'result' => $match,
        ], 200);
    }



}
