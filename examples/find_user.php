<?php
// examples/find_user.php

require_once __DIR__ . '/../bootstrap.php';

$pdo = require __DIR__ . '/../config/database.php';
$userModel = new \App\Model\SecureUser($pdo);

// Search by email
$user = $userModel->findByEmail('alan@example.com');

if ($user) {
    echo "Found user:\n";
    echo "ID: {$user['id']}\n";
    echo "Name: {$user['name']}\n";
    echo "Email: {$user['email']}\n";   // decrypted!
    echo "Phone: {$user['phone']}\n";   // decrypted!
} else {
    echo "User not found.\n";
}