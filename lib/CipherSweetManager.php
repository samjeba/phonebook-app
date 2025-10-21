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

    private static function getBinaryKey(): string
    {
        if (!isset($_ENV['CIPHERSWEET_KEY'])) {
            throw new \RuntimeException('Missing CIPHERSWEET_KEY in environment');
        }

        $hexKey = $_ENV['CIPHERSWEET_KEY'];

        if (!ctype_xdigit($hexKey) || strlen($hexKey) !== 64) {
            throw new \InvalidArgumentException(
                'CIPHERSWEET_KEY must be a 64-character hexadecimal string (256-bit key)'
            );
        }

        $binary = hex2bin($hexKey);
        if ($binary === false) {
            throw new \InvalidArgumentException('Invalid hex key');
        }

        return $binary;
    }

    public static function getEngine(): CipherSweet
    {
        if (self::$engine === null) {
            $binaryKey = self::getBinaryKey();
            $keyProvider = new StringProvider($binaryKey);
            self::$engine = new CipherSweet($keyProvider);
        }
        return self::$engine;
    }

    public static function getUserEncryptedRow(): EncryptedRow
    {
        if (self::$userRow === null) {
            $engine = self::getEngine();
            self::$userRow = new EncryptedRow($engine, 'users');
            self::$userRow->addField('email');
            self::$userRow->addField('phone');

            self::$userRow->addBlindIndex('email', new BlindIndex(
                'email_idx',
                [function ($value) {
                    return strtolower((string)$value);
                }],
                32
            ));

            self::$userRow->addBlindIndex('phone', new BlindIndex(
                'phone_idx',
                [function ($value) {
                    return (string)$value;
                }],
                32
            ));
        }
        return self::$userRow;
    }
}