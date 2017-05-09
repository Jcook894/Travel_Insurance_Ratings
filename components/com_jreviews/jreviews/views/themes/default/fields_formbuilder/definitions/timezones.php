<?php
$timezonesDef = file_get_contents('timezones.json');

$timezonesDef = json_decode($timezonesDef, true);

// Commented because it causes errors on some servers

// $userTimezone = (new DateTime('NOW'))->getTimezone();

// $timezonesDef['default'] = $userTimezone->getName();

header('Content-type: application/json');

echo json_encode($timezonesDef);