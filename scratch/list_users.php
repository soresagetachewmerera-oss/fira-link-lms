<?php
$pdo = new PDO('sqlite:' . __DIR__ . '/../Eduex Laravel/Eduex Laravel/database/database.sqlite');
$stmt = $pdo->query('SELECT id, name, email, role, password FROM users');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$candidates = ['password', '12345678', '123456', 'admin', 'admin123', 'secret', 'password123', 'firalink', 'firalink123', 'eduex', 'eduex123'];

foreach ($users as $user) {
    $found = 'UNKNOWN';
    foreach ($candidates as $cand) {
        if (password_verify($cand, $user['password'])) {
            $found = $cand;
            break;
        }
    }
    echo "ID: {$user['id']} | Email: {$user['email']} | Role: {$user['role']} | Pass: {$found} | Hash: " . substr($user['password'], 0, 15) . "...\n";
}
