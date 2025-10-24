<?php
// models/SecureUser.php

namespace App\Model;

use App\Lib\CipherSweetManager;
use PDO;

class SecureUser
{
    private PDO $pdo;
    private ?bool $useEncryption = null;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->useEncryption = CipherSweetManager::isEncryptionEnabled();
    }

    public function create(string $name, string $email, string $phone): int
    {
        if ($this->useEncryption) {
            $encryptedRow = CipherSweetManager::getUserEncryptedRow();
            [$encrypted, $indexes] = $encryptedRow->prepareRowForStorage(compact('email', 'phone'));

            $stmt = $this->pdo->prepare("
                INSERT INTO users (name, email, phone, email_idx, phone_idx)
                VALUES (:name, :email, :phone, :email_idx, :phone_idx)
            ");
            $stmt->execute([
                ':name' => $name,
                ':email' => $encrypted['email'],
                ':phone' => $encrypted['phone'],
                ':email_idx' => $indexes['email_idx'],
                ':phone_idx' => $indexes['phone_idx']
            ]);
        } else {
            // Plaintext mode: store as-is, no indexes
            $stmt = $this->pdo->prepare("
                INSERT INTO users (name, email, phone)
                VALUES (:name, :email, :phone)
            ");
            $stmt->execute(compact('name', 'email', 'phone'));
        }

        return (int)$this->pdo->lastInsertId();
    }

    public function findByEmail(string $email): ?array
    {
        if ($this->useEncryption) {
            $encryptedRow = CipherSweetManager::getUserEncryptedRow();
            $blindIndex = $encryptedRow->getBlindIndex('email_idx', ['email' => $email]);
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email_idx = ?");
            $stmt->execute([$blindIndex]);
        } else {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
        }

        $row = $stmt->fetch();
        if (!$row) return null;

        if ($this->useEncryption) {
            $decrypted = CipherSweetManager::getUserEncryptedRow()->decryptRow($row);
            return array_merge($row, $decrypted);
        }

        return $row; // already plaintext
    }

    public function findByPhone(string $phone): ?array
    {
        if ($this->useEncryption) {
            $encryptedRow = CipherSweetManager::getUserEncryptedRow();
            $blindIndex = $encryptedRow->getBlindIndex('phone_idx', ['phone' => $phone]);
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE phone_idx = ?");
            $stmt->execute([$blindIndex]);
        } else {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE phone = ?");
            $stmt->execute([$phone]);
        }

        $row = $stmt->fetch();
        if (!$row) return null;

        if ($this->useEncryption) {
            $decrypted = CipherSweetManager::getUserEncryptedRow()->decryptRow($row);
            return array_merge($row, $decrypted);
        }

        return $row;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if (!$row || !$this->useEncryption) {
            return $row;
        }

        $decrypted = CipherSweetManager::getUserEncryptedRow()->decryptRow($row);
        return array_merge($row, $decrypted);
    }
}