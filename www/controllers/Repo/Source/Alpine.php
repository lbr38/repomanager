<?php

namespace Controllers\Repo\Source;

use Controllers\Utils\Apk;

/**
 *  Alpine source repository: its branches (e.g. v3.24, edge), repositories (e.g. main, community) and architectures can be edited (see Items)
 *
 *  Indexes are signed with RSA keys (not GPG keys), clients have them in /etc/apk/keys/<key name>. The public keys of the source
 *  repository are stored in its definition (see Keys), and only them are used to verify its indexes. A signature names its key,
 *  so the key name must be the name of the key file (e.g. alpine-devel@lists.alpinelinux.org-6165ee59.rsa.pub)
 */
class Alpine extends Items
{
    use Keys;

    protected const TYPE = 'alpine';

    protected const FIELDS = [
        'branches' => 'Branch',
        'repos'    => 'Repository',
        'archs'    => 'Architecture'
    ];

    // Branches have an end of life date
    protected const EOL_FIELDS = ['branches'];

    /**
     *  Check a key name: the name of the key file on clients
     */
    protected static function checkKeyName(string $name): void
    {
        Apk::checkKeyName($name);
    }

    /**
     *  Check a key content: a RSA public key in PEM format
     */
    protected static function checkKeyContent(string $content): void
    {
        Apk::checkPublicKey($content);
    }
}
