<?php
header('Content-Type: application/json; charset=utf-8');

$rules_valid = false;
$violation_code = "RULE_FOOD_001";

$output = json_encode([
    "status" => "ok",
    "rules_valid" => $rules_valid,
    "violation_code" => $violation_code
]);

// Rimuove tutti gli spazi dopo i due punti
$output = str_replace(': ', ':', $output);

echo $output;
