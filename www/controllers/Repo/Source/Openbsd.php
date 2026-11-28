<?php

namespace Controllers\Repo\Source;

use Controllers\Utils\Signify;

/**
 *  OpenBSD source repository: the root of an OpenBSD mirror (e.g. https://cdn.openbsd.org/pub/OpenBSD)
 *  Its versions (e.g. 8.0) and architectures (platforms, e.g. amd64) can be edited (see Items)
 *
 *  Releases, packages and patches are signed with signify keys, one per version and usage (e.g. openbsd-80-base.pub, openbsd-80-pkg.pub,
 *  openbsd-80-syspatch.pub), installed in /etc/signify on clients. The public keys of the source repository are stored in its definition
 *  (see Keys), and only them are used to verify its signatures.
 */
class Openbsd extends Items
{
    use Keys;

    protected const TYPE = 'openbsd';

    protected const FIELDS = [
        'versions' => 'Version',
        'archs'    => 'Architecture'
    ];

    // Versions have an end of life date
    protected const EOL_FIELDS = ['versions'];

    /**
     *  Check a key name: the name of the key file (e.g. openbsd-80-pkg.pub)
     */
    protected static function checkKeyName(string $name): void
    {
        Signify::checkKeyName($name);
    }

    /**
     *  Check a key content: a signify public key
     */
    protected static function checkKeyContent(string $content): void
    {
        Signify::checkPublicKey($content);
    }
}
