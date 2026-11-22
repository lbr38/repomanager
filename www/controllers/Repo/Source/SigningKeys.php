<?php

namespace Controllers\Repo\Source;

use Exception;
use JsonException;
use Controllers\Gpg;
use Controllers\Utils\Apk;
use Controllers\Utils\Signify;

/**
 *  Overview of the public keys used to verify the source repositories signatures (signing keys panel):
 *   - GPG keys of the trusted keyring, shared by all the source repositories, with the source repositories referencing them
 *   - RSA keys (alpine) and signify keys (openbsd) stored in the definition of each source repository
 */
class SigningKeys
{
    /**
     *  Return the keys of the trusted keyring with their details (see Gpg::getTrustedKeysDetails()) and the source repositories
     *  referencing them ('usage': labels such as '<source> / <distribution>'), by one of their fingerprints (primary key or subkey)
     */
    public static function getGpgKeys(): array
    {
        $references = self::getGpgReferences();
        $keys = Gpg::getTrustedKeysDetails();

        foreach ($keys as &$key) {
            $fingerprints = array_filter(array_merge([$key['fingerprint']], array_column($key['subkeys'], 'fingerprint')));
            $usage = [];

            foreach ($references as $reference) {
                foreach ($fingerprints as $fingerprint) {
                    // A reference can be a fingerprint or a long key Id (last 16 characters of the fingerprint)
                    if ($reference['fingerprint'] === $fingerprint or (strlen($reference['fingerprint']) == 16 and str_ends_with($fingerprint, $reference['fingerprint']))) {
                        $usage[] = $reference['label'];
                        break;
                    }
                }
            }

            $key['usage'] = array_values(array_unique($usage));
        }

        return $keys;
    }

    /**
     *  Return the source repositories of a package type (alpine or openbsd) with the details of the public keys stored in
     *  their definition: id, name and keys (name, link, content and details, or the error if the key is invalid)
     */
    public static function getSourcesKeys(string $type): array
    {
        $sources = [];

        foreach ((new Source())->listAll($type) as $source) {
            try {
                $definition = json_decode($source['Definition'], true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                continue;
            }

            $keys = [];

            foreach ($definition['keys'] ?? [] as $key) {
                $key['details'] = [];
                $key['error'] = '';

                try {
                    if ($type == 'alpine') {
                        $key['details'] = Apk::getPublicKeyDetails($key['content'] ?? '');
                    }

                    if ($type == 'openbsd') {
                        $publicKey = Signify::readPublicKey($key['content'] ?? '');
                        $key['details'] = [
                            'comment' => $publicKey['comment'],
                            'keynum'  => bin2hex($publicKey['keynum'])
                        ];
                    }
                } catch (Exception $e) {
                    $key['error'] = $e->getMessage();
                }

                $keys[] = $key;
            }

            $sources[] = [
                'id'   => $source['Id'],
                'name' => $definition['name'] ?? '',
                'keys' => $keys
            ];
        }

        return $sources;
    }

    /**
     *  Return the GPG keys referenced by the source repositories definitions: fingerprint (uppercase) and label of the referencing
     *  source repository (deb distributions, rpm release versions and arch source repositories)
     */
    private static function getGpgReferences(): array
    {
        $references = [];

        foreach ((new Source())->listAll() as $source) {
            try {
                $definition = json_decode($source['Definition'], true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                continue;
            }

            $name = $definition['name'] ?? '';
            $lists = [];

            if (($definition['type'] ?? '') == 'deb') {
                foreach ($definition['distributions'] ?? [] as $distribution) {
                    $lists[$name . ' / ' . ($distribution['name'] ?? '')] = $distribution['gpgkeys'] ?? [];
                }
            }

            if (($definition['type'] ?? '') == 'rpm') {
                foreach ($definition['releasever'] ?? [] as $releasever) {
                    $lists[$name . ' / release version ' . ($releasever['name'] ?? '')] = $releasever['gpgkeys'] ?? [];
                }
            }

            if (($definition['type'] ?? '') == 'arch') {
                $lists[$name] = $definition['gpgkeys'] ?? [];
            }

            foreach ($lists as $label => $gpgKeys) {
                foreach ((array) $gpgKeys as $gpgKey) {
                    if (empty($gpgKey['fingerprint']) or !is_string($gpgKey['fingerprint'])) {
                        continue;
                    }

                    $references[] = [
                        'fingerprint' => strtoupper(preg_replace('/^0x|\s/i', '', $gpgKey['fingerprint'])),
                        'label'       => $label
                    ];
                }
            }
        }

        return $references;
    }
}
