<?php
$pdo = new PDO('sqlite:' . __DIR__ . '/../Eduex Laravel/Eduex Laravel/database/database.sqlite');
$stmt = $pdo->query("SELECT * FROM platform_settings WHERE key = 'platform.general'");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "platform.general: " . print_r($row, true) . "\n";

$stmt2 = $pdo->query("SELECT identifier, name, is_enabled FROM payment_gateway_settings");
$rows2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
echo "payment_gateways: " . print_r($rows2, true) . "\n";
