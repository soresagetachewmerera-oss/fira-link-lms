<?php
$pdo = new PDO('sqlite:' . __DIR__ . '/../Eduex Laravel/Eduex Laravel/database/database.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 1. Disable all gateways
$pdo->exec("UPDATE payment_gateway_settings SET is_enabled = 0");

// 2. Ensure Chapa exists and is enabled
$stmt = $pdo->prepare("SELECT id FROM payment_gateway_settings WHERE identifier = 'chapa'");
$stmt->execute();
$chapa = $stmt->fetch(PDO::FETCH_ASSOC);

if ($chapa) {
    $pdo->exec("UPDATE payment_gateway_settings SET is_enabled = 1, type = 'online', name = 'Chapa' WHERE identifier = 'chapa'");
} else {
    $stmtInsert = $pdo->prepare("INSERT INTO payment_gateway_settings (identifier, name, type, credentials, is_enabled, created_at, updated_at) VALUES ('chapa', 'Chapa', 'online', :creds, 1, datetime('now'), datetime('now'))");
    $stmtInsert->execute([':creds' => json_encode(['mode' => 'sandbox'])]);
}

// 3. Ensure platform default_currency is ETB
$stmt = $pdo->query("SELECT payload FROM platform_settings WHERE key = 'platform.general'");
$setting = $stmt->fetch(PDO::FETCH_ASSOC);
if ($setting) {
    $data = json_decode($setting['payload'], true);
    if (!isset($data['data'])) {
        $data['data'] = [];
    }
    $data['data']['default_currency'] = 'ETB';
    $updateStmt = $pdo->prepare("UPDATE platform_settings SET payload = :payload, updated_at = datetime('now') WHERE key = 'platform.general'");
    $updateStmt->execute([':payload' => json_encode($data)]);
}

echo "Database updated successfully: Only Chapa is enabled, default_currency set to ETB.\n";

$verify = $pdo->query("SELECT identifier, name, is_enabled FROM payment_gateway_settings")->fetchAll(PDO::FETCH_ASSOC);
print_r($verify);
