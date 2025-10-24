<?php
/**
 * CipherSweet Phone Book Encryption/Decryption Utility
 *
 * Usage:
 *   php utils/encrypt_decrypt_util.php --encrypt
 *   php utils/encrypt_decrypt_util.php --decrypt
 *   php utils/encrypt_decrypt_util.php --help
 */

var_dump(
    defined('SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES') // nonce size
);

require_once __DIR__ . '/../bootstrap.php';
$pdo = require __DIR__ . '/../config/database.php';

use App\Lib\CipherSweetManager;
use App\Model\SecureUser;

function showHelp(): void
{
    echo "Phone Book CipherSweet Utility\n";
    echo "----------------------------\n";
    echo "Usage:\n";
    echo "  --encrypt    Encrypt all plaintext records (idempotent)\n";
    echo "  --decrypt    Decrypt all records (for verification only!)\n";
    echo "  --help       Show this help\n\n";
    echo "⚠️  WARNING: --decrypt exposes plaintext data. Use with caution!\n";
}

function encryptAllUsers(PDO $pdo): void
{
    echo "🔒 Encrypting all user records...\n";

    $encryptedRow = CipherSweetManager::getUserEncryptedRow();
    
    // Get all users (assume current table has plaintext email/phone)
    $stmt = $pdo->query("SELECT id, name, email, phone FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($users as $user) {
        // Skip if already encrypted (check if email looks like base64/CipherSweet format)
        if (str_starts_with($user['email'], 'CSv1:') || strlen($user['email']) > 100) {
            echo "  Skipping ID {$user['id']} (already encrypted)\n";
            continue;
        }

        $plainData = ['email' => $user['email'], 'phone' => $user['phone']];
        [$encrypted, $indexes] = $encryptedRow->prepareRowForStorage($plainData);

        $update = $pdo->prepare("
            UPDATE users 
            SET email = :email, phone = :phone, email_idx = :email_idx, phone_idx = :phone_idx
            WHERE id = :id
        ");
        $update->execute([
            ':email' => $encrypted['email'],
            ':phone' => $encrypted['phone'],
            ':email_idx' => $indexes['email_idx'],
            ':phone_idx' => $indexes['phone_idx'],
            ':id' => $user['id']
        ]);

        echo "  Encrypted user ID {$user['id']}\n";
    }

    echo "✅ Encryption complete.\n";
}

function decryptAllUsers(PDO $pdo): void
{
    echo "🔓 Decrypting all user records (FOR VERIFICATION ONLY)...\n";

    $encryptedRow = CipherSweetManager::getUserEncryptedRow();
    
    $stmt = $pdo->query("SELECT * FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($users as $user) {
        try {
            $decrypted = $encryptedRow->decryptRow($user);
            echo sprintf(
                "ID: %d | Name: %s | Email: %s | Phone: %s\n",
                $user['id'],
                $user['name'],
                $decrypted['email'],
                $decrypted['phone']
            );
        } catch (Exception $e) {
            echo "  ❌ Failed to decrypt ID {$user['id']}: " . $e->getMessage() . "\n";
        }
    }

    echo "✅ Decryption complete.\n";
}

// === Main ===
$argv = $_SERVER['argv'] ?? [];
$command = $argv[1] ?? '--help';

switch ($command) {
    case '--encrypt':
        encryptAllUsers($pdo);
        break;
    case '--decrypt':
        decryptAllUsers($pdo);
        break;
    case '--help':
    default:
        showHelp();
        break;
}