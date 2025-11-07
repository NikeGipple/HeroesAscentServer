<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\Account;

class RegistrationController extends Controller
{
    public function register(Request $request)
{
    $apiKey = $request->input('api_key');

    // 🧩 Logghiamo subito la chiave ricevuta dal client
    \Log::info('📥 Registration attempt received', [
        'api_key' => $apiKey,
        'ip' => $request->ip(),
        'user_agent' => $request->userAgent(),
    ]);

    if (empty($apiKey)) {
        \Log::warning('⚠️ Missing API key in registration request');
        return response()->json([
            'status' => 'error',
            'message' => 'Missing API key'
        ], 400);
    }

    // ✅ Chiamata all’API ufficiale GW2
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $apiKey
    ])->get('https://api.guildwars2.com/v2/tokeninfo');

    // 🧾 Logghiamo il risultato grezzo della chiamata
    \Log::info('🌐 GW2 TokenInfo response', [
        'status' => $response->status(),
        'body' => $response->body(),
    ]);

    if ($response->failed()) {
        \Log::error('❌ Invalid API key', ['api_key' => $apiKey]);
        return response()->json([
            'status' => 'error',
            'message' => 'Invalid API key'
        ], 401);
    }

    $tokenInfo = $response->json();

    // 🔍 Verifica account già registrato
    $account = Account::where('api_key', $apiKey)->first();
    if ($account) {
        \Log::info('ℹ️ Account already registered', ['account_token' => $account->account_token]);
        return response()->json([
            'status' => 'ok',
            'message' => 'already_registered',
            'account_token' => $account->account_token,
            'api_permissions' => $tokenInfo['permissions'] ?? [],
            'api_name' => $tokenInfo['name'] ?? null,
        ]);
    }

    // 🆕 Crea nuovo record
    $accountToken = \Str::uuid()->toString();

    $account = Account::create([
        'api_key' => $apiKey,
        'account_token' => $accountToken,
        'active' => true,
    ]);

    \Log::info('✅ New account registered', [
        'account_token' => $accountToken,
        'api_name' => $tokenInfo['name'] ?? null
    ]);

    return response()->json([
        'status' => 'ok',
        'message' => 'registered',
        'account_token' => $accountToken,
        'api_permissions' => $tokenInfo['permissions'] ?? [],
        'api_name' => $tokenInfo['name'] ?? null,
    ]);
}

}
