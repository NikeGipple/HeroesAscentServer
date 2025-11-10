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

        // ✅ Verifica validità API key tramite endpoint ufficiale
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey
        ])->get('https://api.guildwars2.com/v2/tokeninfo');

        if ($response->failed()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid API key'
            ], 401);
        }

        // Se la chiave è valida, ottieni info base
        $tokenInfo = $response->json();

        // Se l'account esiste già, restituisci il token esistente
        $account = Account::where('api_key', $apiKey)->first();
        if ($account) {
            return response()->json([
                'status' => 'ok',
                'message' => 'already_registered',
                'account_token' => $account->account_token,
            ]);
        }

        // Crea nuovo token
        $accountToken = Str::uuid()->toString();

        // Salva nuovo account
        $account = Account::create([
            'api_key' => $apiKey,
            'account_token' => $accountToken,
            'account_name' => $accountName,
            'active' => true,
        ]);

        return response()->json([
            'status' => 'ok',
            'message' => 'registered',
            'account_token' => $accountToken,
        ]);
    }
}
