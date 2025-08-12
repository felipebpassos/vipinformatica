<?php
require_once __DIR__ . '/config.php';

echo "Timezone atual: " . date_default_timezone_get() . "\n";
echo "Data/hora atual: " . date('Y-m-d H:i:s') . "\n";

// Teste com uma data UTC
$utc_date = '2024-01-15 10:30:00';
echo "Data UTC: " . $utc_date . "\n";
echo "Data local: " . formatLocalDateTime($utc_date) . "\n";
echo "Data apenas: " . formatLocalDate($utc_date) . "\n";
?>
