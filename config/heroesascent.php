<?php

return [
    'bypass_accounts' => array_values(array_filter(array_map('trim', explode(',', env('HA_BYPASS_ACCOUNTS', ''))))),

    /*
     * Gilde di cui è consentita la RAPPRESENTAZIONE sul personaggio in gara.
     *
     * La rappresentazione di gilda è per-personaggio (non per-account) e i buff di
     * gilda si applicano SOLO alla gilda rappresentata: è la rappresentazione sul
     * personaggio in gara a dare un eventuale vantaggio, non l'appartenenza
     * dell'account. Il controllo periodico (Gw2RulesService::scanAllActiveCharacters)
     * segnala il personaggio che rappresenta una gilda NON presente in questa lista.
     *
     * Lista vuota (default) ⇒ rappresentare QUALSIASI gilda è una violazione.
     * Popolabile via env HA_ALLOWED_GUILDS="GUID1,GUID2,..." (es. la gilda ufficiale HA).
     */
    'allowed_guilds' => array_values(array_filter(array_map('trim', explode(',', env('HA_ALLOWED_GUILDS', ''))))),
];