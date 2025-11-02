<?php
header('Content-Type: application/json; charset=utf-8');

// esempio: nessuna violazione
$rules_valid = true;
$violation_code = null;

// esempio: violazione (attiva manualmente per test)
# $rules_valid = false;
# $violation_code = "RULE_FOOD_001"; // used forbidden food items

echo json_encode([
  "status" => "ok",
  "rules_valid" => $rules_valid,
  "violation_code" => $violation_code
]);
