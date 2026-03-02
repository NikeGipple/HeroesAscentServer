<?php

return [
    'bypass_accounts' => array_values(array_filter(array_map('trim', explode(',', env('HA_BYPASS_ACCOUNTS', ''))))),
];