<?php
// lib/CipherSweetManager.php

namespace App\Lib;

use ParagonIE\CipherSweet\CipherSweet;
use ParagonIE\CipherSweet\KeyProvider\StringProvider;
use ParagonIE\CipherSweet\EncryptedRow;
use ParagonIE\CipherSweet\BlindIndex;

class CipherSweetManager
{
    private static ?CipherSweet $engine = null;
    private static ?EncryptedRow $userRow = null;

    public static function isEncryptionEnabled(): bool
    {
        return !empty($_ENV['CIPHERSWEET_KEY']);
    }

    private static function getBinaryKey(): ?string
    {
        $hexKey = $_ENV['CIPHERSWEET_KEY'] ?? '';
        if (!$hexKey) {
            return null;
        }

        if (!ctype_xdigit($hexKey) || strlen($hexKey) !== 64) {
            throw new \InvalidArgumentException(
                'CIPHERSWEET_KEY must be a 64-character hex string or empty'
            );
        }

        $binary = @hex2bin($hexKey);
        return $binary ?: null;
    }

    public static function getEngine(): ?CipherSweet
    {
        if (!self::isEncryptionEnabled()) {
            return null;
        }

        if (self::$engine === null) {
            $binaryKey = self::getBinaryKey();
            if ($binaryKey === null) {
                return null;
            }
            $keyProvider = new StringProvider($binaryKey);
            self::$engine = new CipherSweet($keyProvider);
        }
        return self::$engine;
    }

    public static function getUserEncryptedRow(): ?EncryptedRow
    {
        if (!self::isEncryptionEnabled()) {
            return null;
        }

        if (self::$userRow === null) {
            $engine = self::getEngine();
            if ($engine === null) {
                return null;
            }

            self::$userRow = new EncryptedRow($engine, 'users');
            self::$userRow->addField('email');
            self::$userRow->addField('phone');
            self::$userRow->addBlindIndex('email', new BlindIndex(
                'email_idx',
                [fn($v) => strtolower((string)$v)],
                32
            ));
            self::$userRow->addBlindIndex('phone', new BlindIndex(
                'phone_idx',
                [fn($v) => (string)$v],
                32
            ));
        }
        return self::$userRow;
    }
}