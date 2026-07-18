<?php

namespace App\Services;

class CcaVenueCryptoService
{
    private const CIPHER = 'AES-128-CBC';

    private const FIXED_IV = "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0a\x0b\x0c\x0d\x0e\x0f";

    public function encrypt(string $plainText, string $workingKey): string
    {
        $secretKey = pack('H*', md5($workingKey));
        $encrypted = openssl_encrypt(
            $plainText,
            self::CIPHER,
            $secretKey,
            OPENSSL_RAW_DATA,
            self::FIXED_IV,
        );

        if ($encrypted === false) {
            throw new \RuntimeException('Unable to encrypt CCAvenue request.');
        }

        return bin2hex($encrypted);
    }

    public function decrypt(string $encryptedHex, string $workingKey): string
    {
        $secretKey = pack('H*', md5($workingKey));
        $encrypted = hex2bin(trim($encryptedHex));
        if ($encrypted === false) {
            throw new \RuntimeException('Invalid encrypted CCAvenue response.');
        }

        $decrypted = openssl_decrypt(
            $encrypted,
            self::CIPHER,
            $secretKey,
            OPENSSL_RAW_DATA,
            self::FIXED_IV,
        );

        if ($decrypted === false) {
            throw new \RuntimeException('Unable to decrypt CCAvenue response.');
        }

        return $decrypted;
    }

    public function parseResponseString(string $payload): array
    {
        parse_str($payload, $parsed);

        return is_array($parsed) ? $parsed : [];
    }
}
