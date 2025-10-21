<?php
// models/SecureUser.php

namespace App\Model;

use App\Lib\CipherSweetManager;
use PDO;

class SecureUser
{
    private PDO $pdo;
    private $encryptedRow;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->encryptedRow = CipherSweetManager::getUserEncryptedRow();
    }

    public function create(string $name, string $email, string $phone): int
    {
        $plainData = compact('email', 'phone');
        [$encrypted, $indexes] = $this->encryptedRow->prepareRowForStorage($plainData);

        $stmt = $this->pdo->prepare("
            INSERT INTO users (name, email, phone, email_idx, phone_idx)
            VALUES (:name, :email, :phone, :email_idx, :phone_idx)
        ");

        $stmt->execute([
            ':name'       => $name,
            ':email'      => $encrypted['email'],
            ':phone'      => $encrypted['phone'],
            ':email_idx'  => $indexes['email_idx'],
            ':phone_idx'  => $indexes['phone_idx']
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function findByEmail(string $email): ?array
    {
        $blindIndex = $this->encryptedRow->getBlindIndex('email_idx', ['email' => $email]);
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email_idx = ?");
        $stmt->execute([$blindIndex]);
        $row = $stmt->fetch();

        return $row ? $this->decryptRow($row) : null;
    }

    public function findByPhone(string $phone): ?array
    {
        $blindIndex = $this->encryptedRow->getBlindIndex('phone_idx', ['phone' => $phone]);
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE phone_idx = ?");
        $stmt->execute([$blindIndex]);
        $row = $stmt->fetch();

        return $row ? $this->decryptRow($row) : null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? $this->decryptRow($row) : null;
    }

    private function decryptRow(array $row): array
    {
        $decrypted = $this->encryptedRow->decryptRow($row);
        return array_merge($row, $decrypted); // keeps id, name + adds decrypted email/phone
    }
}