<?php

namespace Controllers\Repo\Source;

use Exception;

/**
 *  Arch source repository: its repositories (e.g. core, extra) and architectures can be edited (see Items)
 *
 *  Its databases are signed with GPG keys: as for the deb and rpm source repositories, they are imported in Repomanager trusted
 *  keyring and their fingerprints are stored in the source repository definition ('gpgkeys')
 */
class Arch extends Items
{
    protected const TYPE = 'arch';

    protected const FIELDS = [
        'repos' => 'Repository',
        'archs' => 'Architecture'
    ];

    /**
     *  Import a GPG key in the trusted keyring (from a URL, a fingerprint or plain text) and add it to the source repository
     */
    public function addGpgKey(string $gpgKeyUrl, string $gpgKeyFingerprint, string $gpgKeyPlainText): void
    {
        // Import the gpg key and get the fingerprints
        $fingerprints = (new \Controllers\Gpg())->import($gpgKeyUrl, $gpgKeyFingerprint, $gpgKeyPlainText);

        /**
         *  Add the new gpg key to the source repository
         *  Instead of adding the provided gpg key, we add all the fingerprints found in the key (primary key and subkeys)
         */
        foreach ($fingerprints as $fingerprint) {
            // Ignore empty or already existing fingerprints
            if (empty($fingerprint) or in_array($fingerprint, array_column($this->currentDefinition['gpgkeys'] ?? [], 'fingerprint'))) {
                continue;
            }

            $this->currentDefinition['gpgkeys'][] = [
                'fingerprint' => $fingerprint
            ];
        }

        // Save the updated source repository definition
        $this->editDefinition($this->id, $this->currentDefinition);
    }

    /**
     *  Remove a gpg key from the source repository (the key stays in the trusted keyring)
     */
    public function removeGpgKey(int $gpgKeyId): void
    {
        if (!isset($this->currentDefinition['gpgkeys'][$gpgKeyId])) {
            throw new Exception('GPG key does not exist');
        }

        unset($this->currentDefinition['gpgkeys'][$gpgKeyId]);
        $this->currentDefinition['gpgkeys'] = array_values($this->currentDefinition['gpgkeys']);

        // Save the updated source repository definition
        $this->editDefinition($this->id, $this->currentDefinition);
    }
}
