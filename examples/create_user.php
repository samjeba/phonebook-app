<?php
// examples/create_user.php

require_once __DIR__ . '/../bootstrap.php';

$pdo = require __DIR__ . '/../config/database.php';
$userModel = new \App\Model\SecureUser($pdo);

// Example data
$name = 'Alan peter';
$email = 'alan@example.com';
$phone = '+1234565690';

$id = $userModel->create($name, $email, $phone);
echo "User created with ID: $id\n";