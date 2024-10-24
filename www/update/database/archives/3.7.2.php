<?php
/**
 *  3.7.2 database update
 */
$mysource = new \Controllers\Repo\Source\Source();

/**
 *  Sources GPG keys
 *
 *  How to find Debian missing keys:
 *  1. Start a debian repository mirroring and wait for the 'Error while checking GPG signature' error message
 *  2. Go inside the container and the download directory (e.g. /home/repo/download-mirror-debian-bullseye-contrib-1686917775)
 *  3. Use gpgv to find which signing keys are missing:
 *      gpgv --homedir /var/lib/repomanager/.gnupg/ Release.gpg Release
 *  4. Copy the missing keys ID, and search for them on https://keyserver.ubuntu.com/
 *  5. Import the missing keys
 */
$gpgkeys = array(
    // debian 12
    // 4D64FEC119C2029067D6E791F8D2585B8783D481 Debian Stable Release Key (12/bookworm) <debian-release@lists.debian.org>

    '-----BEGIN PGP PUBLIC KEY BLOCK-----
    Comment: Hostname: 
    Version: Hockeypuck 2.1.0-223-gdc2762b
    
    xjMEY865UxYJKwYBBAHaRw8BAQdAd7Z0srwuhlB6JKFkcf4HU4SSS/xcRfwEQWzr
    crf6AErNSURlYmlhbiBTdGFibGUgUmVsZWFzZSBLZXkgKDEyL2Jvb2t3b3JtKSA8
    ZGViaWFuLXJlbGVhc2VAbGlzdHMuZGViaWFuLm9yZz7ClgQTFggAPhYhBE1k/sEZ
    wgKQZ9bnkfjSWFuHg9SBBQJjzrlTAhsDBQkPCZwABQsJCAcCBhUKCQgLAgQWAgMB
    Ah4BAheAAAoJEPjSWFuHg9SBSgwBAP9qpeO5z1s5m4D4z3TcqDo1wez6DNya27QW
    WoG/4oBsAQCEN8Z00DXagPHbwrvsY2t9BCsT+PgnSn9biobwX7bDDg==
    =lzWw
    -----END PGP PUBLIC KEY BLOCK-----',

    // 4CB50190207B4758A3F73A796ED0E7B82643E131 Debian Archive Automatic Signing Key (12/bookworm) <ftpmaster@debian.org>

    '-----BEGIN PGP PUBLIC KEY BLOCK-----
    Comment: Hostname: 
    Version: Hockeypuck 2.1.0-223-gdc2762b
    
    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
    NlB2T6bKGcYGqL7L
    =rAMi
    -----END PGP PUBLIC KEY BLOCK-----'
);

foreach ($gpgkeys as $gpgkey) {
    $mysource->importGpgKey($gpgkey);
}
