<?php

namespace Controllers\Utils;

use Exception;
use Generator;

/**
 *  Alpine Linux packages (.apk) and repository indexes (APKINDEX.tar.gz), apk v2 format
 *
 *  Both are concatenated gzip streams, each one containing a tar segment (without end-of-archive blocks):
 *   - a package: a signature stream (unless the package is unsigned), a control stream (.PKGINFO) and a data stream
 *   - an index: a signature stream (unless the index is unsigned) and an index stream (DESCRIPTION and APKINDEX files)
 *
 *  The signature stream contains a .SIGN.<type>.<key name> file: the RSA signature (PKCS#1 v1.5) of the next stream by the
 *  key installed as /etc/apk/keys/<key name> on clients. The type gives the hash algorithm: RSA (SHA1), RSA256 or RSA512.
 *
 *  The index identifies each package by the SHA1 of its control stream (C: field, 'Q1' + base64), not of the whole file.
 */
class Apk
{
    // Checksum algorithm name of the packages identity, as used by the downloader (see Controllers\Download\Parallel)
    public const IDENTITY_ALGO = 'apk-identity';

    // Hash algorithm of each signature type
    private const SIGNATURE_ALGOS = [
        'RSA'    => OPENSSL_ALGO_SHA1,
        'RSA256' => OPENSSL_ALGO_SHA256,
        'RSA512' => OPENSSL_ALGO_SHA512
    ];

    // Size of the chunks read to find the end of a gzip stream
    private const CHUNK_SIZE = 8192;

    // Maximum uncompressed size of a signature or control stream (a few KB), and of an index stream (a few MB)
    private const MAX_CONTROL_SIZE = 16 * 1024 * 1024;
    private const MAX_INDEX_SIZE = 256 * 1024 * 1024;

    /**
     *  Return the identity of a package, as in the C: field of the index: 'Q1' + base64 of the SHA1 of its control stream
     *  Only the signature and control streams are read
     */
    public static function identity(string $path): string
    {
        $file = self::open($path);

        try {
            // The control stream is the first one of an unsigned package, the second one otherwise
            for ($i = 0; $i < 2; $i++) {
                $offset = ftell($file);
                [$length, $content] = self::readStream($file, self::MAX_CONTROL_SIZE);

                if (isset(self::listFiles($content)['.PKGINFO'])) {
                    fseek($file, $offset);

                    return 'Q1' . base64_encode(sha1(fread($file, $length), true));
                }
            }
        } finally {
            fclose($file);
        }

        throw new Exception('No control stream found in ' . basename($path));
    }

    /**
     *  Read a repository index, return:
     *   - signatures: the signatures of the index (empty if unsigned), see verifySignatures()
     *   - signed: the signed data (the compressed index stream)
     *   - description: the description of the index (usually its version)
     *   - apkindex: the content of the APKINDEX file
     */
    public static function readIndex(string $path): array
    {
        $file = self::open($path);

        try {
            $offset = ftell($file);
            [$length, $content] = self::readStream($file, self::MAX_INDEX_SIZE);
            $files = self::listFiles($content);
            $signatures = self::getSignatures($files);

            // The first stream is the signature stream of a signed index, the index stream follows
            if (!empty($signatures)) {
                $offset = ftell($file);
                [$length, $content] = self::readStream($file, self::MAX_INDEX_SIZE);
                $files = self::listFiles($content);
            }

            // The uncompressed stream is not needed anymore, the index can be large
            unset($content);

            if (!isset($files['APKINDEX'])) {
                throw new Exception('no APKINDEX file found');
            }

            fseek($file, $offset);
            $signed = fread($file, $length);
        } finally {
            fclose($file);
        }

        return [
            'signatures'  => $signatures,
            'signed'      => $signed,
            'description' => trim($files['DESCRIPTION'] ?? ''),
            'apkindex'    => $files['APKINDEX']
        ];
    }

    /**
     *  Verify signatures with the specified public keys (key name => PEM content), return the name of the key that verified a signature
     *  As apk does, a single valid signature by a known key is enough
     */
    public static function verifySignatures(array $signatures, string $data, array $keys): string
    {
        if (empty($signatures)) {
            throw new Exception('The index is not signed');
        }

        $signers = [];

        foreach ($signatures as $signature) {
            $signers[] = '<code>' . htmlspecialchars($signature['key']) . '</code>';

            if (!isset($keys[$signature['key']])) {
                continue;
            }

            $result = openssl_verify($data, $signature['signature'], $keys[$signature['key']], self::SIGNATURE_ALGOS[$signature['type']]);

            if ($result === 1) {
                return $signature['key'];
            }

            if ($result === 0) {
                throw new Exception('Invalid signature by key <code>' . htmlspecialchars($signature['key']) . '</code>');
            }

            throw new Exception('Could not verify signature by key <code>' . htmlspecialchars($signature['key']) . '</code>: ' . htmlspecialchars((string) openssl_error_string()));
        }

        throw new Exception('No key of the source repository could verify the signature (signed by ' . implode(', ', $signers) . '). Please add the signing key to the source repository.');
    }

    /**
     *  Parse the content of an APKINDEX file, yield one array per package: field letter => value (e.g. 'P' => name, 'V' => version)
     *  Field letters are case sensitive (e.g. 'C' is the checksum, 'c' the commit)
     *  Packages are yielded one by one, an index can list more than 100 000 packages (e.g. Wolfi)
     */
    public static function parseIndex(string $apkindex): Generator
    {
        $package = [];
        $offset = 0;
        $length = strlen($apkindex);

        while ($offset < $length) {
            $end = strpos($apkindex, "\n", $offset);

            if ($end === false) {
                $end = $length;
            }

            $line = rtrim(substr($apkindex, $offset, $end - $offset), "\r");
            $offset = $end + 1;

            // A blank line ends a package
            if ($line === '') {
                if (!empty($package)) {
                    yield $package;
                }

                $package = [];
                continue;
            }

            if (strlen($line) < 2 or $line[1] !== ':') {
                continue;
            }

            $package[$line[0]] = substr($line, 2);
        }

        if (!empty($package)) {
            yield $package;
        }
    }

    /**
     *  Check that a public key is a RSA public key in PEM format, as used to sign indexes
     */
    public static function checkPublicKey(string $content): void
    {
        $key = openssl_pkey_get_public($content);

        if ($key === false) {
            throw new Exception('Invalid public key, a RSA public key in PEM format is expected');
        }

        if ((openssl_pkey_get_details($key)['type'] ?? null) !== OPENSSL_KEYTYPE_RSA) {
            throw new Exception('Invalid public key, a RSA public key is expected');
        }
    }

    /**
     *  Return the details of a RSA public key: its size (bits) and its SHA256 fingerprint, the SHA256 of the key in DER format
     *  (same as: openssl pkey -pubin -in <key> -outform DER | sha256sum)
     */
    public static function getPublicKeyDetails(string $content): array
    {
        self::checkPublicKey($content);

        $details = openssl_pkey_get_details(openssl_pkey_get_public($content));

        // The exported key is the PEM of the DER encoded key (SubjectPublicKeyInfo), whatever the format of the key file
        $der = base64_decode(preg_replace('/-----[A-Z ]+-----|\s/', '', $details['key']));

        return [
            'bits'        => $details['bits'],
            'fingerprint' => hash('sha256', $der)
        ];
    }

    /**
     *  Check a key name: the name of the key file on clients (/etc/apk/keys/<name>), referenced by the signatures
     */
    public static function checkKeyName(string $name): void
    {
        if ($name === '' or str_starts_with($name, '.') or !Validate::alphaNumericHyphen($name, ['.', '@', '+'])) {
            throw new Exception('Invalid key name ' . htmlspecialchars($name) . ', it must be the name of the key file (e.g. alpine-devel@lists.alpinelinux.org-6165ee59.rsa.pub)');
        }
    }

    /**
     *  Return the signatures found in the files of a signature stream: type, key name and signature
     */
    private static function getSignatures(array $files): array
    {
        $signatures = [];

        foreach ($files as $name => $content) {
            if (!preg_match('/^\.SIGN\.([A-Z0-9]+)\.(.+)$/', $name, $matches)) {
                continue;
            }

            // Obsolete or unknown signature types (e.g. DSA) are ignored, as long as another signature can be verified
            if (!isset(self::SIGNATURE_ALGOS[$matches[1]])) {
                continue;
            }

            $signatures[] = ['type' => $matches[1], 'key' => $matches[2], 'signature' => $content];
        }

        return $signatures;
    }

    /**
     *  Open a file for reading
     */
    private static function open(string $path)
    {
        $file = fopen($path, 'rb');

        if ($file === false) {
            throw new Exception('Could not open ' . $path);
        }

        return $file;
    }

    /**
     *  Read the gzip stream starting at the current position of the file, return its compressed length and its uncompressed content
     *  The file position is moved to the end of the stream
     */
    private static function readStream($file, int $maxSize): array
    {
        $start = ftell($file);
        $context = inflate_init(ZLIB_ENCODING_GZIP);
        $content = '';

        while (($chunk = fread($file, self::CHUNK_SIZE)) !== false and $chunk !== '') {
            $inflated = @inflate_add($context, $chunk, ZLIB_SYNC_FLUSH);

            if ($inflated === false) {
                throw new Exception('invalid gzip stream');
            }

            $content .= $inflated;

            if (strlen($content) > $maxSize) {
                throw new Exception('gzip stream is too large');
            }

            // Inflating stops at the end of the stream, the next stream may already have been read
            if (inflate_get_status($context) == ZLIB_STREAM_END) {
                $length = inflate_get_read_len($context);
                fseek($file, $start + $length);

                return [$length, $content];
            }
        }

        throw new Exception('truncated gzip stream');
    }

    /**
     *  Return the regular files of a tar segment: name => content
     *  Long names from pax extended headers and GNU long name entries are supported
     */
    private static function listFiles(string $tar): array
    {
        $files = [];
        $offset = 0;
        $longName = null;

        while ($offset + 512 <= strlen($tar)) {
            $header = substr($tar, $offset, 512);

            // End of archive (the segments of an apk file usually have none)
            if (trim($header, "\0") === '') {
                break;
            }

            $name = rtrim(substr($header, 0, 100), "\0");
            $type = $header[156];
            $size = (int) octdec(trim(substr($header, 124, 12), "\0 "));

            // POSIX ustar prefix of long names
            if (substr($header, 257, 6) === "ustar\0" and trim(substr($header, 345, 155), "\0") !== '') {
                $name = rtrim(substr($header, 345, 155), "\0") . '/' . $name;
            }

            $content = substr($tar, $offset + 512, $size);
            $offset += 512 + (int) ceil($size / 512) * 512;

            // Pax extended header: the name of the next entry is in its path record
            if ($type === 'x') {
                if (preg_match('/^[0-9]+ path=(.*)$/m', $content, $matches)) {
                    $longName = $matches[1];
                }
                continue;
            }

            // GNU long name: the name of the next entry
            if ($type === 'L') {
                $longName = rtrim($content, "\0");
                continue;
            }

            if ($longName !== null) {
                $name = $longName;
                $longName = null;
            }

            if ($type === '0' or $type === "\0") {
                $files[preg_replace('#^\./#', '', $name)] = $content;
            }
        }

        return $files;
    }
}
