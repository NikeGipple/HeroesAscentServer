<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Account;

class RegistrationController extends Controller
{
    public function register(Request $request)
    {
        $apiKey = $request->input('api_key');

        if (empty($apiKey)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Missing API key'
            ], 400);
        }

        // Se l'account esiste già, restituisce il token esistente
        $account = Account::where('api_key', $apiKey)->first();
        if ($account) {
            return response()->json([
                'status' => 'ok',
                'message' => 'already_registered',
                'account_token' => $account->account_token,
            ]);
        }

        // Crea un nuovo token univoco
        $accountToken = Str::uuid()->toString();

        // Crea un nuovo record
        $account = Account::create([
            'api_key' => $apiKey,
            'account_token' => $accountToken,
            'active' => true,
        ]);

        return response()->json([
            'status' => 'ok',
            'message' => 'registered',
            'account_token' => $accountToken
        ]);
    }
}
