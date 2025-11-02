<?php
header('Content-Type: application/json; charset=utf-8');

// esempio: nessuna violazione
$rules_valid = false;
$violation_code = "RULE_FOOD_001";


echo json_encode([
  "status" => "ok",
  "rules_valid" => $rules_valid,
  "violation_code" => $violation_code
]);
