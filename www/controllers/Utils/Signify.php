<?php

namespace Controllers\Utils;

use Exception;

/**
 *  OpenBSD signify signatures (Ed25519), as used to sign the OpenBSD releases, packages and patches
 *
 *  A public key file has two lines: an untrusted comment and the base64 of the algorithm ('Ed'), the key number (8 bytes) and
 *  the Ed25519 public key (32 bytes). A signature file has the same format, with the signature (64 bytes) instead of the key.
 *  An embedded signature file (signify -S -e, e.g. SHA256.sig) is a signature file directly followed by the signed message.
 *
 *  The comments are untrusted: the public key verifying a signature is the one with the same key number.
 */
class Signify
{
    private const ALGO = 'Ed';
    private const COMMENT_PREFIX = 'untrusted comment: ';
    private const KEYNUM_SIZE = 8;

    /**
     *  Read an embedded signature file, return its untrusted comment, key number, signature and the signed message
     */
    public static function readEmbedded(string $content): array
    {
        $lines = explode("\n", $content, 3);

        if (count($lines) < 3) {
            throw new Exception('Invalid signature file, a signify embedded signature is expected');
        }

        $signature = self::decode($lines[0], $lines[1], SODIUM_CRYPTO_SIGN_BYTES, 'signature');
        $signature['message'] = $lines[2];

        return $signature;
    }

    /**
     *  Verify an embedded signature file with the specified public keys (key name => content)
     *  Return the signed message and the name of the key that verified it
     */
    public static function verifyEmbedded(string $content, array $keys): array
    {
        if (!function_exists('sodium_crypto_sign_verify_detached')) {
            throw new Exception('PHP sodium extension is required to verify signify signatures');
        }

        $signature = self::readEmbedded($content);

        foreach ($keys as $name => $key) {
            try {
                $publicKey = self::readPublicKey($key);
            } catch (Exception $e) {
                continue;
            }

            if (!hash_equals($publicKey['keynum'], $signature['keynum'])) {
                continue;
            }

            if (!sodium_crypto_sign_verify_detached($signature['data'], $signature['message'], $publicKey['data'])) {
                throw new Exception('Invalid signature, it does not match the signed content (key ' . htmlspecialchars($name) . ')');
            }

            return ['message' => $signature['message'], 'key' => $name];
        }

        throw new Exception('No public key of the source repository matches the signature (' . htmlspecialchars($signature['comment']) . '). Please add the missing public key to the source repository.');
    }

    /**
     *  Read a public key file, return its untrusted comment, key number and Ed25519 public key
     */
    public static function readPublicKey(string $content): array
    {
        $lines = explode("\n", trim($content));

        if (count($lines) != 2) {
            throw new Exception('Invalid public key, a signify public key file is expected');
        }

        return self::decode($lines[0], $lines[1], SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES, 'public key');
    }

    /**
     *  Check that a public key is a signify public key, as used to sign the repositories
     */
    public static function checkPublicKey(string $content): void
    {
        self::readPublicKey($content);
    }

    /**
     *  Check a key name: the name of the key file (e.g. openbsd-80-pkg.pub)
     */
    public static function checkKeyName(string $name): void
    {
        if ($name === '' or str_starts_with($name, '.') or !Validate::alphaNumericHyphen($name, ['.'])) {
            throw new Exception('Invalid key name ' . htmlspecialchars($name) . ', it must be the name of the key file (e.g. openbsd-80-pkg.pub)');
        }
    }

    /**
     *  Decode the comment and base64 lines of a public key or signature file
     *  Return the untrusted comment, the key number and the key or signature data ($size bytes)
     */
    private static function decode(string $commentLine, string $base64Line, int $size, string $type): array
    {
        if (!str_starts_with($commentLine, self::COMMENT_PREFIX)) {
            throw new Exception('Invalid ' . $type . ', the first line must be an untrusted comment');
        }

        $decoded = base64_decode(trim($base64Line), true);

        if ($decoded === false or strlen($decoded) != strlen(self::ALGO) + self::KEYNUM_SIZE + $size) {
            throw new Exception('Invalid ' . $type . ', could not decode it');
        }

        if (!str_starts_with($decoded, self::ALGO)) {
            throw new Exception('Invalid ' . $type . ', only Ed25519 (' . self::ALGO . ') is supported');
        }

        return [
            'comment' => substr($commentLine, strlen(self::COMMENT_PREFIX)),
            'keynum'  => substr($decoded, strlen(self::ALGO), self::KEYNUM_SIZE),
            'data'    => substr($decoded, strlen(self::ALGO) + self::KEYNUM_SIZE)
        ];
    }
}
