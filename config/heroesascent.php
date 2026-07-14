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

    /*
     * Buff vietati identificati per ID GW2, mappati sul codice di violazione
     * (EventType.code). Il buff arriva dall'addon come BUFF_APPLIED (buff_id +
     * buff_name); qui l'ID viene tradotto nel codice corretto (data-driven, così
     * l'organizzatore aggiunge/rimuove ID senza deploy — stesso spirito di
     * bypass_accounts/allowed_guilds).
     *
     * Le categorie generiche cibo/utility sono abbinate ANCHE per nome
     * ("Nourishment"/"Enhancement") in CharacterEventWorkflow::translateBuffEvent —
     * gli ID qui sotto (9994/9958) sono un fallback robusto al match per nome.
     * ID di gilda verificati in-game 2026-07-14 (vedi docs/buff-probe-findings.md).
     */
    'forbidden_buff_ids' => [
        9994  => 'BUFF_FORBIDDEN_FOOD',         // Nourishment (categoria cibo)    — regola 9
        9958  => 'BUFF_FORBIDDEN_ENHANCEMENT',  // Enhancement (categoria utility) — regola 9
        9283  => 'BUFF_FORBIDDEN_REINFORCED',   // Reinforced Armor                — regola 8
        // --- Boost di gilda (regola 7) ---
        33106 => 'BUFF_FORBIDDEN_GUILD',        // Guild Experience Boost
        32103 => 'BUFF_FORBIDDEN_GUILD',        // Guild Karma Boost
        32665 => 'BUFF_FORBIDDEN_GUILD',        // Guild WvW Experience Bonus
        33362 => 'BUFF_FORBIDDEN_GUILD',        // Guild PvP Reward Track Boost
        33772 => 'BUFF_FORBIDDEN_GUILD',        // Guild Map Bonus Boost
        33833 => 'BUFF_FORBIDDEN_GUILD',        // Guild Item Research
        33969 => 'BUFF_FORBIDDEN_GUILD',        // Guild Crafting Boost
        33984 => 'BUFF_FORBIDDEN_GUILD',        // Guild Gathering Boost
        35126 => 'BUFF_FORBIDDEN_GUILD',        // Guild WvW Reward Track Boost
    ],
];