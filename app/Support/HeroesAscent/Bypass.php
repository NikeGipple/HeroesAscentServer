<?php

namespace App\Support\HeroesAscent;

use Illuminate\Support\Str;

final class Bypass
{
    private static ?array $normalized = null;

    private static function normalizedAccounts(): array
    {
        if (self::$normalized !== null) {
            return self::$normalized;
        }

        $list = config('heroesascent.bypass_accounts', []);

        self::$normalized = array_values(array_filter(array_map(
            fn ($v) => Str::lower(trim($v)),
            $list
        )));

        return self::$normalized;
    }

    public static function isBypassAccount(string $accountName): bool
    {
        $needle = Str::lower(trim($accountName));
        return in_array($needle, self::normalizedAccounts(), true);
    }
}