<?php
/**
 *  3.5.3 database update
 */
$mysource = new \Controllers\Source();

/**
 *  Sources URLs
 */
$sources = array(
    // debian
    array(
        'type' => 'deb',
        'name' => 'debian',
        'url' => 'https://deb.debian.org/debian'
    ),
    array(
        'type' => 'deb',
        'name' => 'debian-security',
        'url' => 'https://deb.debian.org/debian-security'
    ),
    // ubuntu
    array(
        'type' => 'deb',
        'name' => 'ubuntu',
        'url' => 'http://archive.ubuntu.com/ubuntu/'
    ),
    array(
        'type' => 'deb',
        'name' => 'ubuntu-security',
        'url' => 'http://security.ubuntu.com/ubuntu/'
    ),
    // centos 7
    array(
        'type' => 'rpm',
        'name' => 'base',
        'url' => 'https://vault.centos.org/7.9.2009/os/x86_64/'
    ),
    array(
        'type' => 'rpm',
        'name' => 'updates',
        'url' => 'https://vault.centos.org/7.9.2009/updates/x86_64/'
    ),
    array(
        'type' => 'rpm',
        'name' => 'extras',
        'url' => 'https://vault.centos.org/7.9.2009/extras/x86_64/'
    ),
    array(
        'type' => 'rpm',
        'name' => 'epel',
        'url' => 'https://dl.fedoraproject.org/pub/epel/7/$basearch/'
    ),
    // centos 8 stream
    array(
        'type' => 'rpm',
        'name' => 'centos8-baseos',
        'url' => 'http://vault.centos.org/8-stream/BaseOS/$basearch/os/'
    ),
    array(
        'type' => 'rpm',
        'name' => 'centos8-appstream',
        'url' => 'http://vault.centos.org/8-stream/AppStream/$basearch/os/'
    ),
    array(
        'type' => 'rpm',
        'name' => 'centos8-extras',
        'url' => 'http://mirror.centos.org/centos/8-stream/extras/$basearch/os/'
    ),
    array(
        'type' => 'rpm',
        'name' => 'epel8',
        'url' => 'https://dl.fedoraproject.org/pub/epel/8/Everything/$basearch/'
    ),
    // centos 9 stream
    array(
        'type' => 'rpm',
        'name' => 'centos9-baseos',
        'url' => 'https://mirror.stream.centos.org/9-stream/BaseOS/$basearch/os/'
    ),
    array(
        'type' => 'rpm',
        'name' => 'centos9-appstream',
        'url' => 'https://mirror.stream.centos.org/9-stream/AppStream/$basearch/os/'
    ),
    array(
        'type' => 'rpm',
        'name' => 'centos9-extras-common',
        'url' => 'https://mirror.stream.centos.org/SIGs/9-stream/extras/$basearch/extras-common/'
    ),
    array(
        'type' => 'rpm',
        'name' => 'epel9',
        'url' => 'https://dl.fedoraproject.org/pub/epel/9/Everything/$basearch/'
    )
);

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
    // debian 10
    // 6D33866EDD8FFA41C0143AEDDCC9EFBF77E11517 Debian Stable Release Key (10/buster) <debian-release@lists.debian.org>
    '-----BEGIN PGP PUBLIC KEY BLOCK-----

    mQINBFxZ9FABEADPEDVwAUd10zcdnQJF7klaxK1mcTEUd+xfNAKBjhvP69XdAf54
    7PS8Xid9zMAK/JARzLsZj5STy1VQQrPGOkSqPKA+gSpW8CmEWwfL/VTfQFFrJ9kb
    1eArtb3FFk3qdtLih38t5JUhm0PidKcThemoi3kfVfoK3iWnfnb36RuNG75H73gf
    /5i3C4Wq+dGusGXWxz15E9qACja3i/r239unHKvfEFWXQU6IyNYkz8o/hG/knRCX
    DTBKbzKt4AH7LQFoLsd+qN8DNUUjxIUZyDTxJac5TXTWKiiOXsxzUmcgZBO+FT8b
    Nx19fq9leIqxcBGdXU1TT2STwcgku9QtIKdm8wq0IrlbLjEasmmpeEx6WAIvaZfx
    U2hFIKhYJXue2LTu2eUgxFBPUwQYoClCBUDuJgA9n+Z4HGKlibiUhf3HF+KIxqzr
    woQn+rac6eVJowsPPN8maeMwltjAdkfSHGWQkgGPPCaGwJj7shq2qJBYmbEbC5j6
    02ZJS1srmvJbQrKhG+jdPDADDhwLq5vEQysqcJJ72+vAKjMHOTWc026zwQz3evvO
    p6LsrJ+l0kyH1CjMhmumr4A/d+GSFGxzUR6BRAGigSYKQdPWb7Fb9fEuTsa1kp9k
    cqRMMGxPYNQsBPu+h0PIMMHEYY5WOMaKni7bE7lfxSdcnDG6TbtAy4zcQwARAQAB
    tEdEZWJpYW4gU3RhYmxlIFJlbGVhc2UgS2V5ICgxMC9idXN0ZXIpIDxkZWJpYW4t
    cmVsZWFzZUBsaXN0cy5kZWJpYW4ub3JnPokCVAQTAQoAPhYhBG0zhm7dj/pBwBQ6
    7dzJ77934RUXBQJcWfRQAhsDBQkPCZwABQsJCAcDBRUKCQgLBRYCAwEAAh4BAheA
    AAoJENzJ77934RUX/woQAICqnZKgvhZrYU/ogF1Kbx1oPYWg1Dz8ErQtXbFqcSeU
    JBsG2eJFHkR//sqeKGFYcE8xHN9oX8i9UMUvmb6FtMMTK9wJ99sSA/PFWJT6FbZo
    Eflx27q3fJfzcGGAgtslXBEqYVcyBv6KUQk/d+OC73rdFAH+53BuWFLQKxPFEa3l
    U7QLo0oyWH4gKXVGs2D+Bo4sRSa0NzcJoUQXTi04f2RU/4Zs4ar/tYopMoA3H0hC
    axZLfrSFtXpb7n3IsivP4mwdaPDSRavLZuNoc/Vze4RGmd0rtC/HyUBHVVMJ17Q2
    2WD7eCEhq8XBbh2u1xZWW3WjRgZxlIdvBu78+A0Kiz0noobA/pwPqYAtMmY3hB+8
    AuaYYWiM53HhySp0m/XkIMOCHZiAaOe4mTf1rrj2qsEH9ZqHljqLD1Bas5NIy2AD
    Q2t5MJiNLKKI54cNCsYB2gkCNNoBN+wYRzbrFPMGFxFk/dnb7gRIsqq60t+cwfdt
    Y8QlyI0ss1uWhaB7ORXNC7hOziTM1nJ3rCQy5LY1pUyb7WecYIRG2niLIb8bXlml
    XA+jyVQ/Ft8FL33drvXdIrNobNz5Q9PZUSC0Ll2OYkbTzioxTMv8o0SPkz7xawvq
    cOhWyNdf7E0/SUf4T75jCZ3zqaZOucNBRekumcUme+6ua8+W0iC4Jtmot5yh4oaZ
    =a/CW
    -----END PGP PUBLIC KEY BLOCK-----',

    // 80D15823B7FD1561F9F7BCDDDC30D7C23CBBABEE Debian Archive Automatic Signing Key (10/buster) <ftpmaster@debian.org>

    '-----BEGIN PGP PUBLIC KEY BLOCK-----

    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
    qn8+ouVQUr65+xcIST13Ffg80zc=
    =5Cty
    -----END PGP PUBLIC KEY BLOCK-----',

    // 5E61B217265DA9807A23C5FF4DFAB270CAA96DFA Debian Security Archive Automatic Signing Key (10/buster) <ftpmaster@debian.org>
    '-----BEGIN PGP PUBLIC KEY BLOCK-----

    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
    8V9DmVt99tlJJvTXFUXZ7K2JMX0f8pDW7KtR0zI=
    =xBv7
    -----END PGP PUBLIC KEY BLOCK-----',

    // ED541312A33F1128F10B1C6C54404762BBB6E853 Debian Security Archive Automatic Signing Key (11/bullseye) <ftpmaster@debian.org>
    '-----BEGIN PGP PUBLIC KEY BLOCK-----
    Comment: Hostname: 
    Version: Hockeypuck 2.1.0-222-g25248d4
    
    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
    TT9yj35WJXkuIZ7GV189g3gogTpu
    =Ky9e
    -----END PGP PUBLIC KEY BLOCK-----',

    // A7236886F3CCCAAD148A27F80E98404D386FA1D9 Debian Archive Automatic Signing Key (11/bullseye) <ftpmaster@debian.org> (also needed for Debian 10 repos)
    '-----BEGIN PGP PUBLIC KEY BLOCK-----
    Comment: Hostname: 
    Version: Hockeypuck 2.1.0-222-g25248d4
    
    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
    JZs=
    =6oF2
    -----END PGP PUBLIC KEY BLOCK-----',

    // A4285295FC7B1A81600062A9605C66F00D6C9793 Debian Stable Release Key (11/bullseye) <debian-release@lists.debian.org>
    '-----BEGIN PGP PUBLIC KEY BLOCK-----
    Comment: Hostname: 
    Version: Hockeypuck 2.1.0-222-g25248d4
    
    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
    e18slw8=
    =ZeNo
    -----END PGP PUBLIC KEY BLOCK-----',

    // 3B4FE6ACC0B21F32 Ubuntu Archive Automatic Signing Key (2012) <ftpmaster@ubuntu.com>
    '-----BEGIN PGP PUBLIC KEY BLOCK-----
    Version: Hockeypuck 2.1.0-222-g25248d4
    Comment: Hostname: 
    
    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
    =UpHi
    -----END PGP PUBLIC KEY BLOCK-----',

    // 871920D1991BC93C Ubuntu Archive Automatic Signing Key (2018) <ftpmaster@ubuntu.com>
    '-----BEGIN PGP PUBLIC KEY BLOCK-----
    Comment: Hostname: 
    Version: Hockeypuck 2.1.0-222-g25248d4
    
    xsFNBFufwdoBEADv/Gxytx/LcSXYuM0MwKojbBye81s0G1nEx+lz6VAUpIUZnbkq
    dXBHC+dwrGS/CeeLuAjPRLU8AoxE/jjvZVp8xFGEWHYdklqXGZ/gJfP5d3fIUBtZ
    HZEJl8B8m9pMHf/AQQdsC+YzizSG5t5Mhnotw044LXtdEEkx2t6Jz0OGrh+5Ioxq
    X7pZiq6Cv19BohaUioKMdp7ES6RYfN7ol6HSLFlrMXtVfh/ijpN9j3ZhVGVeRC8k
    KHQsJ5PkIbmvxBiUh7SJmfZUx0IQhNMaDHXfdZAGNtnhzzNReb1FqNLSVkrS/Pns
    AQzMhG1BDm2VOSF64jebKXffFqM5LXRQTeqTLsjUbbrqR6s/GCO8UF7jfUj6I7ta
    LygmsHO/JD4jpKRC0gbpUBfaiJyLvuepx3kWoqL3sN0LhlMI80+fA7GTvoOx4tpq
    VlzlE6TajYu+jfW3QpOFS5ewEMdL26hzxsZg/geZvTbArcP+OsJKRmhv4kNo6Ayd
    yHQ/3ZV/f3X9mT3/SPLbJaumkgp3Yzd6t5PeBu+ZQk/mN5WNNuaihNEV7llb1Zhv
    Y0Fxu9BVd/BNl0rzuxp3rIinB2TX2SCg7wE5xXkwXuQ/2eTDE0v0HlGntkuZjGow
    DZkxHZQSxZVOzdZCRVaX/WEFLpKa2AQpw5RJrQ4oZ/OfifXyJzP27o03wQARAQAB
    zUJVYnVudHUgQXJjaGl2ZSBBdXRvbWF0aWMgU2lnbmluZyBLZXkgKDIwMTgpIDxm
    dHBtYXN0ZXJAdWJ1bnR1LmNvbT7CwXgEEwEKACIFAlufwdoCGwMGCwkIBwMCBhUI
    AgkKCwQWAgMBAh4BAheAAAoJEIcZINGZG8k8LHMQAKS2cnxz/5WaoCOWArf5g6UH
    beOCgc5DBm0hCuFDZWWv427aGei3CPuLw0DGLCXZdyc5dqE8mvjMlOmmAKKlj1uG
    g3TYCbQWjWPeMnBPZbkFgkZoXJ7/6CB7bWRht1sHzpt1LTZ+SYDwOwJ68QRp7DRa
    Zl9Y6QiUbeuhq2DUcTofVbBxbhrckN4ZteLvm+/nG9m/ciopc66LwRdkxqfJ32Cy
    q+1TS5VaIJDG7DWziG+Kbu6qCDM4QNlg3LH7p14CrRxAbc4lvohRgsV4eQqsIcdF
    kuVY5HPPj2K8TqpY6STe8Gh0aprG1RV8ZKay3KSMpnyV1fAKn4fM9byiLzQAovC0
    LZ9MMMsrAS/45AvC3IEKSShjLFn1X1dRCiO6/7jmZEoZtAp53hkf8SMBsi78hVNr
    BumZwfIdBA1v22+LY4xQK8q4XCoRcA9G+pvzU9YVW7cRnDZZGl0uwOw7z9PkQBF5
    KFKjWDz4fCk+K6+YtGpovGKekGBb8I7EA6UpvPgqA/QdI0t1IBP0N06RQcs1fUaA
    QEtz6DGy5zkRhR4pGSZn+dFET7PdAjEK84y7BdY4t+U1jcSIvBj0F2B7LwRL7xGp
    SpIKi/ekAXLs117bvFHaCvmUYN7JVp1GMmVFxhIdx6CFm3fxG8QjNb5tere/YqK+
    uOgcXny1UlwtCUzlrSaPwsFzBBABCgAdFiEEFT8cnvE5X78ANS6NC/uEfz8nL1sF
    AlufxEMACgkQC/uEfz8nL1tuFw/9GgaeggvCn15QplABa86OReJARxnAxpaL223p
    LkgAbBYAOT7PmTjwwHCqGeJZGLzAQsGLc6WkQDegewQCMWLp+1zOHmUBHbZPsz3E
    76Ac381FAXhZBj8MLbcyOROsKYKZ9M/yGerMpVx4B8WNb5P+t9ttAwwAR/lNs5OS
    3lpV4nkwIzvxA6Wnq0gWKBL/9rc7sL+qWeJDnQEkq1Z/dNBbgIWktDtqeIXFldgj
    YOX+x1RN81beLVDtRLoOU0IkQsFGaOOb0o2x8/dmYM2cXuchNGYmdY2Z5jeLI1F0
    dzCR+CRUEDFdr0cF94USgVGWyCoaHdABTRD5e/uIEySL0T9ym93RNBtoc9gPENFB
    2ASMJgkMNINiV82alPjYYrbs+ZVHuLQIgd+qw/N6zwLtVDgo2Pc6FXZpqmSjRRmt
    BRJuv+VnDBeAOstl0QloRm5gRBp/wgt93E1Ah+QJRVuMQFqz0nPZWTwfcGagmSEu
    rWiKX8n2FFYkiLfyUW0335TN88Z99+gvQ+AySAFu8ReT/lQzAPRPNRLjpAk5e1Fu
    MzQYoBJcYwP0sjAIO1AWmguPI1KLfnVnXnsT5JYMbG2DCLHI/OIvnpRq8v955glZ
    5L9aq8bNnOwC2BK6MVUspbJRpGLQ29hbeH8jnRPOPQ+Sbwa2C8/ZSoBa/L6JGl5R
    DaOLQ1zCwHMEEAEKAB0WIQRLluASHQi2/EbrKw195QqCeN3B8AUCYa6RGwAKCRB9
    5QqCeN3B8JPqCACo9qgOsQ6FxnsxwWJcSWbO6Gi7yALj2X9T6PJhprbG9c69fy49
    HbBkll2QVBH/iJEcz6sa9TEfYoY+m9zXDMu4ne3XrD3t41IQmz88f7ucTImaZZbv
    eplR2cXVR+DN8DH35qobHg0m1ZXDPhGSUP40Q2db1ilnni0yHnYAFOrgtNNo+tVa
    i9v+iZXJuoAvWYmyKZLcmCE+yWOz0Xv61/OTsGE6vrHD3AVapZoJ6sVQjI4WW9YZ
    aETNe+Yf3QsLhTQVIBi6bV1yC0RyBi/IjpS8ORwtsitx2BGjuoSRsT+0BLfVJETm
    a73bT+9eWAFw4FRaM43EUP+pOzDcyiH4bP2P
    =XG66
    -----END PGP PUBLIC KEY BLOCK-----',

    // RPM-GPG-KEY-CentOS-7 (CentOS 7)
    '-----BEGIN PGP PUBLIC KEY BLOCK-----
    Version: GnuPG v1.4.5 (GNU/Linux)
    
    mQINBFOn/0sBEADLDyZ+DQHkcTHDQSE0a0B2iYAEXwpPvs67cJ4tmhe/iMOyVMh9
    Yw/vBIF8scm6T/vPN5fopsKiW9UsAhGKg0epC6y5ed+NAUHTEa6pSOdo7CyFDwtn
    4HF61Esyb4gzPT6QiSr0zvdTtgYBRZjAEPFVu3Dio0oZ5UQZ7fzdZfeixMQ8VMTQ
    4y4x5vik9B+cqmGiq9AW71ixlDYVWasgR093fXiD9NLT4DTtK+KLGYNjJ8eMRqfZ
    Ws7g7C+9aEGHfsGZ/SxLOumx/GfiTloal0dnq8TC7XQ/JuNdB9qjoXzRF+faDUsj
    WuvNSQEqUXW1dzJjBvroEvgTdfCJfRpIgOrc256qvDMp1SxchMFltPlo5mbSMKu1
    x1p4UkAzx543meMlRXOgx2/hnBm6H6L0FsSyDS6P224yF+30eeODD4Ju4BCyQ0jO
    IpUxmUnApo/m0eRelI6TRl7jK6aGqSYUNhFBuFxSPKgKYBpFhVzRM63Jsvib82rY
    438q3sIOUdxZY6pvMOWRkdUVoz7WBExTdx5NtGX4kdW5QtcQHM+2kht6sBnJsvcB
    JYcYIwAUeA5vdRfwLKuZn6SgAUKdgeOtuf+cPR3/E68LZr784SlokiHLtQkfk98j
    NXm6fJjXwJvwiM2IiFyg8aUwEEDX5U+QOCA0wYrgUQ/h8iathvBJKSc9jQARAQAB
    tEJDZW50T1MtNyBLZXkgKENlbnRPUyA3IE9mZmljaWFsIFNpZ25pbmcgS2V5KSA8
    c2VjdXJpdHlAY2VudG9zLm9yZz6JAjUEEwECAB8FAlOn/0sCGwMGCwkIBwMCBBUC
    CAMDFgIBAh4BAheAAAoJECTGqKf0qA61TN0P/2730Th8cM+d1pEON7n0F1YiyxqG
    QzwpC2Fhr2UIsXpi/lWTXIG6AlRvrajjFhw9HktYjlF4oMG032SnI0XPdmrN29lL
    F+ee1ANdyvtkw4mMu2yQweVxU7Ku4oATPBvWRv+6pCQPTOMe5xPG0ZPjPGNiJ0xw
    4Ns+f5Q6Gqm927oHXpylUQEmuHKsCp3dK/kZaxJOXsmq6syY1gbrLj2Anq0iWWP4
    Tq8WMktUrTcc+zQ2pFR7ovEihK0Rvhmk6/N4+4JwAGijfhejxwNX8T6PCuYs5Jiv
    hQvsI9FdIIlTP4XhFZ4N9ndnEwA4AH7tNBsmB3HEbLqUSmu2Rr8hGiT2Plc4Y9AO
    aliW1kOMsZFYrX39krfRk2n2NXvieQJ/lw318gSGR67uckkz2ZekbCEpj/0mnHWD
    3R6V7m95R6UYqjcw++Q5CtZ2tzmxomZTf42IGIKBbSVmIS75WY+cBULUx3PcZYHD
    ZqAbB0Dl4MbdEH61kOI8EbN/TLl1i077r+9LXR1mOnlC3GLD03+XfY8eEBQf7137
    YSMiW5r/5xwQk7xEcKlbZdmUJp3ZDTQBXT06vavvp3jlkqqH9QOE8ViZZ6aKQLqv
    pL+4bs52jzuGwTMT7gOR5MzD+vT0fVS7Xm8MjOxvZgbHsAgzyFGlI1ggUQmU7lu3
    uPNL0eRx4S1G4Jn5
    =OGYX
    -----END PGP PUBLIC KEY BLOCK-----',

    // RPM-GPG-KEY-EPEL-7 (EPEL 7)
    '-----BEGIN PGP PUBLIC KEY BLOCK-----
    Version: GnuPG v1.4.11 (GNU/Linux)
    
    mQINBFKuaIQBEAC1UphXwMqCAarPUH/ZsOFslabeTVO2pDk5YnO96f+rgZB7xArB
    OSeQk7B90iqSJ85/c72OAn4OXYvT63gfCeXpJs5M7emXkPsNQWWSju99lW+AqSNm
    jYWhmRlLRGl0OO7gIwj776dIXvcMNFlzSPj00N2xAqjMbjlnV2n2abAE5gq6VpqP
    vFXVyfrVa/ualogDVmf6h2t4Rdpifq8qTHsHFU3xpCz+T6/dGWKGQ42ZQfTaLnDM
    jToAsmY0AyevkIbX6iZVtzGvanYpPcWW4X0RDPcpqfFNZk643xI4lsZ+Y2Er9Yu5
    S/8x0ly+tmmIokaE0wwbdUu740YTZjCesroYWiRg5zuQ2xfKxJoV5E+Eh+tYwGDJ
    n6HfWhRgnudRRwvuJ45ztYVtKulKw8QQpd2STWrcQQDJaRWmnMooX/PATTjCBExB
    9dkz38Druvk7IkHMtsIqlkAOQMdsX1d3Tov6BE2XDjIG0zFxLduJGbVwc/6rIc95
    T055j36Ez0HrjxdpTGOOHxRqMK5m9flFbaxxtDnS7w77WqzW7HjFrD0VeTx2vnjj
    GqchHEQpfDpFOzb8LTFhgYidyRNUflQY35WLOzLNV+pV3eQ3Jg11UFwelSNLqfQf
    uFRGc+zcwkNjHh5yPvm9odR1BIfqJ6sKGPGbtPNXo7ERMRypWyRz0zi0twARAQAB
    tChGZWRvcmEgRVBFTCAoNykgPGVwZWxAZmVkb3JhcHJvamVjdC5vcmc+iQI4BBMB
    AgAiBQJSrmiEAhsPBgsJCAcDAgYVCAIJCgsEFgIDAQIeAQIXgAAKCRBqL66iNSxk
    5cfGD/4spqpsTjtDM7qpytKLHKruZtvuWiqt5RfvT9ww9GUUFMZ4ZZGX4nUXg49q
    ixDLayWR8ddG/s5kyOi3C0uX/6inzaYyRg+Bh70brqKUK14F1BrrPi29eaKfG+Gu
    MFtXdBG2a7OtPmw3yuKmq9Epv6B0mP6E5KSdvSRSqJWtGcA6wRS/wDzXJENHp5re
    9Ism3CYydpy0GLRA5wo4fPB5uLdUhLEUDvh2KK//fMjja3o0L+SNz8N0aDZyn5Ax
    CU9RB3EHcTecFgoy5umRj99BZrebR1NO+4gBrivIfdvD4fJNfNBHXwhSH9ACGCNv
    HnXVjHQF9iHWApKkRIeh8Fr2n5dtfJEF7SEX8GbX7FbsWo29kXMrVgNqHNyDnfAB
    VoPubgQdtJZJkVZAkaHrMu8AytwT62Q4eNqmJI1aWbZQNI5jWYqc6RKuCK6/F99q
    thFT9gJO17+yRuL6Uv2/vgzVR1RGdwVLKwlUjGPAjYflpCQwWMAASxiv9uPyYPHc
    ErSrbRG0wjIfAR3vus1OSOx3xZHZpXFfmQTsDP7zVROLzV98R3JwFAxJ4/xqeON4
    vCPFU6OsT3lWQ8w7il5ohY95wmujfr6lk89kEzJdOTzcn7DBbUru33CQMGKZ3Evt
    RjsC7FDbL017qxS+ZVA/HGkyfiu4cpgV8VUnbql5eAZ+1Ll6Dw==
    =hdPa
    -----END PGP PUBLIC KEY BLOCK-----',

    // RPM-GPG-KEY-CentOS-Official (CentOS 8)
    '-----BEGIN PGP PUBLIC KEY BLOCK-----
    Version: GnuPG v2.0.22 (GNU/Linux)
    
    mQINBFzMWxkBEADHrskpBgN9OphmhRkc7P/YrsAGSvvl7kfu+e9KAaU6f5MeAVyn
    rIoM43syyGkgFyWgjZM8/rur7EMPY2yt+2q/1ZfLVCRn9856JqTIq0XRpDUe4nKQ
    8BlA7wDVZoSDxUZkSuTIyExbDf0cpw89Tcf62Mxmi8jh74vRlPy1PgjWL5494b3X
    5fxDidH4bqPZyxTBqPrUFuo+EfUVEqiGF94Ppq6ZUvrBGOVo1V1+Ifm9CGEK597c
    aevcGc1RFlgxIgN84UpuDjPR9/zSndwJ7XsXYvZ6HXcKGagRKsfYDWGPkA5cOL/e
    f+yObOnC43yPUvpggQ4KaNJ6+SMTZOKikM8yciyBwLqwrjo8FlJgkv8Vfag/2UR7
    JINbyqHHoLUhQ2m6HXSwK4YjtwidF9EUkaBZWrrskYR3IRZLXlWqeOi/+ezYOW0m
    vufrkcvsh+TKlVVnuwmEPjJ8mwUSpsLdfPJo1DHsd8FS03SCKPaXFdD7ePfEjiYk
    nHpQaKE01aWVSLUiygn7F7rYemGqV9Vt7tBw5pz0vqSC72a5E3zFzIIuHx6aANry
    Gat3aqU3qtBXOrA/dPkX9cWE+UR5wo/A2UdKJZLlGhM2WRJ3ltmGT48V9CeS6N9Y
    m4CKdzvg7EWjlTlFrd/8WJ2KoqOE9leDPeXRPncubJfJ6LLIHyG09h9kKQARAQAB
    tDpDZW50T1MgKENlbnRPUyBPZmZpY2lhbCBTaWduaW5nIEtleSkgPHNlY3VyaXR5
    QGNlbnRvcy5vcmc+iQI3BBMBAgAhBQJczFsZAhsDBgsJCAcDAgYVCAIJCgsDFgIB
    Ah4BAheAAAoJEAW1VbOEg8ZdjOsP/2ygSxH9jqffOU9SKyJDlraL2gIutqZ3B8pl
    Gy/Qnb9QD1EJVb4ZxOEhcY2W9VJfIpnf3yBuAto7zvKe/G1nxH4Bt6WTJQCkUjcs
    N3qPWsx1VslsAEz7bXGiHym6Ay4xF28bQ9XYIokIQXd0T2rD3/lNGxNtORZ2bKjD
    vOzYzvh2idUIY1DgGWJ11gtHFIA9CvHcW+SMPEhkcKZJAO51ayFBqTSSpiorVwTq
    a0cB+cgmCQOI4/MY+kIvzoexfG7xhkUqe0wxmph9RQQxlTbNQDCdaxSgwbF2T+gw
    byaDvkS4xtR6Soj7BKjKAmcnf5fn4C5Or0KLUqMzBtDMbfQQihn62iZJN6ZZ/4dg
    q4HTqyVpyuzMXsFpJ9L/FqH2DJ4exGGpBv00ba/Zauy7GsqOc5PnNBsYaHCply0X
    407DRx51t9YwYI/ttValuehq9+gRJpOTTKp6AjZn/a5Yt3h6jDgpNfM/EyLFIY9z
    V6CXqQQ/8JRvaik/JsGCf+eeLZOw4koIjZGEAg04iuyNTjhx0e/QHEVcYAqNLhXG
    rCTTbCn3NSUO9qxEXC+K/1m1kaXoCGA0UWlVGZ1JSifbbMx0yxq/brpEZPUYm+32
    o8XfbocBWljFUJ+6aljTvZ3LQLKTSPW7TFO+GXycAOmCGhlXh2tlc6iTc41PACqy
    yy+mHmSv
    =kkH7
    -----END PGP PUBLIC KEY BLOCK-----',

    // RPM-GPG-KEY-EPEL-8 (EPEL 8)
    '-----BEGIN PGP PUBLIC KEY BLOCK-----

    mQINBFz3zvsBEADJOIIWllGudxnpvJnkxQz2CtoWI7godVnoclrdl83kVjqSQp+2
    dgxuG5mUiADUfYHaRQzxKw8efuQnwxzU9kZ70ngCxtmbQWGmUmfSThiapOz00018
    +eo5MFabd2vdiGo1y+51m2sRDpN8qdCaqXko65cyMuLXrojJHIuvRA/x7iqOrRfy
    a8x3OxC4PEgl5pgDnP8pVK0lLYncDEQCN76D9ubhZQWhISF/zJI+e806V71hzfyL
    /Mt3mQm/li+lRKU25Usk9dWaf4NH/wZHMIPAkVJ4uD4H/uS49wqWnyiTYGT7hUbi
    ecF7crhLCmlRzvJR8mkRP6/4T/F3tNDPWZeDNEDVFUkTFHNU6/h2+O398MNY/fOh
    yKaNK3nnE0g6QJ1dOH31lXHARlpFOtWt3VmZU0JnWLeYdvap4Eff9qTWZJhI7Cq0
    Wm8DgLUpXgNlkmquvE7P2W5EAr2E5AqKQoDbfw/GiWdRvHWKeNGMRLnGI3QuoX3U
    pAlXD7v13VdZxNydvpeypbf/AfRyrHRKhkUj3cU1pYkM3DNZE77C5JUe6/0nxbt4
    ETUZBTgLgYJGP8c7PbkVnO6I/KgL1jw+7MW6Az8Ox+RXZLyGMVmbW/TMc8haJfKL
    MoUo3TVk8nPiUhoOC0/kI7j9ilFrBxBU5dUtF4ITAWc8xnG6jJs/IsvRpQARAQAB
    tChGZWRvcmEgRVBFTCAoOCkgPGVwZWxAZmVkb3JhcHJvamVjdC5vcmc+iQI4BBMB
    AgAiBQJc9877AhsPBgsJCAcDAgYVCAIJCgsEFgIDAQIeAQIXgAAKCRAh6kWrL4bW
    oWagD/4xnLWws34GByVDQkjprk0fX7Iyhpm/U7BsIHKspHLL+Y46vAAGY/9vMvdE
    0fcr9Ek2Zp7zE1RWmSCzzzUgTG6BFoTG1H4Fho/7Z8BXK/jybowXSZfqXnTOfhSF
    alwDdwlSJvfYNV9MbyvbxN8qZRU1z7PEWZrIzFDDToFRk0R71zHpnPTNIJ5/YXTw
    NqU9OxII8hMQj4ufF11040AJQZ7br3rzerlyBOB+Jd1zSPVrAPpeMyJppWFHSDAI
    WK6x+am13VIInXtqB/Cz4GBHLFK5d2/IYspVw47Solj8jiFEtnAq6+1Aq5WH3iB4
    bE2e6z00DSF93frwOyWN7WmPIoc2QsNRJhgfJC+isGQAwwq8xAbHEBeuyMG8GZjz
    xohg0H4bOSEujVLTjH1xbAG4DnhWO/1VXLX+LXELycO8ZQTcjj/4AQKuo4wvMPrv
    9A169oETG+VwQlNd74VBPGCvhnzwGXNbTK/KH1+WRH0YSb+41flB3NKhMSU6dGI0
    SGtIxDSHhVVNmx2/6XiT9U/znrZsG5Kw8nIbbFz+9MGUUWgJMsd1Zl9R8gz7V9fp
    n7L7y5LhJ8HOCMsY/Z7/7HUs+t/A1MI4g7Q5g5UuSZdgi0zxukiWuCkLeAiAP4y7
    zKK4OjJ644NDcWCHa36znwVmkz3ixL8Q0auR15Oqq2BjR/fyog==
    =84m8
    -----END PGP PUBLIC KEY BLOCK-----',

    // RPM-GPG-KEY-centosofficial (CentOS 9)
    '-----BEGIN PGP PUBLIC KEY BLOCK-----
    Version: GnuPG v2.0.22 (GNU/Linux)
    
    mQINBFzMWxkBEADHrskpBgN9OphmhRkc7P/YrsAGSvvl7kfu+e9KAaU6f5MeAVyn
    rIoM43syyGkgFyWgjZM8/rur7EMPY2yt+2q/1ZfLVCRn9856JqTIq0XRpDUe4nKQ
    8BlA7wDVZoSDxUZkSuTIyExbDf0cpw89Tcf62Mxmi8jh74vRlPy1PgjWL5494b3X
    5fxDidH4bqPZyxTBqPrUFuo+EfUVEqiGF94Ppq6ZUvrBGOVo1V1+Ifm9CGEK597c
    aevcGc1RFlgxIgN84UpuDjPR9/zSndwJ7XsXYvZ6HXcKGagRKsfYDWGPkA5cOL/e
    f+yObOnC43yPUvpggQ4KaNJ6+SMTZOKikM8yciyBwLqwrjo8FlJgkv8Vfag/2UR7
    JINbyqHHoLUhQ2m6HXSwK4YjtwidF9EUkaBZWrrskYR3IRZLXlWqeOi/+ezYOW0m
    vufrkcvsh+TKlVVnuwmEPjJ8mwUSpsLdfPJo1DHsd8FS03SCKPaXFdD7ePfEjiYk
    nHpQaKE01aWVSLUiygn7F7rYemGqV9Vt7tBw5pz0vqSC72a5E3zFzIIuHx6aANry
    Gat3aqU3qtBXOrA/dPkX9cWE+UR5wo/A2UdKJZLlGhM2WRJ3ltmGT48V9CeS6N9Y
    m4CKdzvg7EWjlTlFrd/8WJ2KoqOE9leDPeXRPncubJfJ6LLIHyG09h9kKQARAQAB
    tDpDZW50T1MgKENlbnRPUyBPZmZpY2lhbCBTaWduaW5nIEtleSkgPHNlY3VyaXR5
    QGNlbnRvcy5vcmc+iQI3BBMBAgAhBQJczFsZAhsDBgsJCAcDAgYVCAIJCgsDFgIB
    Ah4BAheAAAoJEAW1VbOEg8ZdjOsP/2ygSxH9jqffOU9SKyJDlraL2gIutqZ3B8pl
    Gy/Qnb9QD1EJVb4ZxOEhcY2W9VJfIpnf3yBuAto7zvKe/G1nxH4Bt6WTJQCkUjcs
    N3qPWsx1VslsAEz7bXGiHym6Ay4xF28bQ9XYIokIQXd0T2rD3/lNGxNtORZ2bKjD
    vOzYzvh2idUIY1DgGWJ11gtHFIA9CvHcW+SMPEhkcKZJAO51ayFBqTSSpiorVwTq
    a0cB+cgmCQOI4/MY+kIvzoexfG7xhkUqe0wxmph9RQQxlTbNQDCdaxSgwbF2T+gw
    byaDvkS4xtR6Soj7BKjKAmcnf5fn4C5Or0KLUqMzBtDMbfQQihn62iZJN6ZZ/4dg
    q4HTqyVpyuzMXsFpJ9L/FqH2DJ4exGGpBv00ba/Zauy7GsqOc5PnNBsYaHCply0X
    407DRx51t9YwYI/ttValuehq9+gRJpOTTKp6AjZn/a5Yt3h6jDgpNfM/EyLFIY9z
    V6CXqQQ/8JRvaik/JsGCf+eeLZOw4koIjZGEAg04iuyNTjhx0e/QHEVcYAqNLhXG
    rCTTbCn3NSUO9qxEXC+K/1m1kaXoCGA0UWlVGZ1JSifbbMx0yxq/brpEZPUYm+32
    o8XfbocBWljFUJ+6aljTvZ3LQLKTSPW7TFO+GXycAOmCGhlXh2tlc6iTc41PACqy
    yy+mHmSv
    =kkH7
    -----END PGP PUBLIC KEY BLOCK-----',

    // RPM-GPG-KEY-CentOS-SIG-Extras-SHA512 (CentOS 9 extras-common)
    '-----BEGIN PGP PUBLIC KEY BLOCK-----
    Version: GnuPG v2.0.22 (GNU/Linux)
    
    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
    =jx2B
    -----END PGP PUBLIC KEY BLOCK-----',

    // RPM-GPG-KEY-EPEL-9 (EPEL 9)
    '-----BEGIN PGP PUBLIC KEY BLOCK-----

    mQINBGE3mOsBEACsU+XwJWDJVkItBaugXhXIIkb9oe+7aadELuVo0kBmc3HXt/Yp
    CJW9hHEiGZ6z2jwgPqyJjZhCvcAWvgzKcvqE+9i0NItV1rzfxrBe2BtUtZmVcuE6
    2b+SPfxQ2Hr8llaawRjt8BCFX/ZzM4/1Qk+EzlfTcEcpkMf6wdO7kD6ulBk/tbsW
    DHX2lNcxszTf+XP9HXHWJlA2xBfP+Dk4gl4DnO2Y1xR0OSywE/QtvEbN5cY94ieu
    n7CBy29AleMhmbnx9pw3NyxcFIAsEZHJoU4ZW9ulAJ/ogttSyAWeacW7eJGW31/Z
    39cS+I4KXJgeGRI20RmpqfH0tuT+X5Da59YpjYxkbhSK3HYBVnNPhoJFUc2j5iKy
    XLgkapu1xRnEJhw05kr4LCbud0NTvfecqSqa+59kuVc+zWmfTnGTYc0PXZ6Oa3rK
    44UOmE6eAT5zd/ToleDO0VesN+EO7CXfRsm7HWGpABF5wNK3vIEF2uRr2VJMvgqS
    9eNwhJyOzoca4xFSwCkc6dACGGkV+CqhufdFBhmcAsUotSxe3zmrBjqA0B/nxIvH
    DVgOAMnVCe+Lmv8T0mFgqZSJdIUdKjnOLu/GRFhjDKIak4jeMBMTYpVnU+HhMHLq
    uDiZkNEvEEGhBQmZuI8J55F/a6UURnxUwT3piyi3Pmr2IFD7ahBxPzOBCQARAQAB
    tCdGZWRvcmEgKGVwZWw5KSA8ZXBlbEBmZWRvcmFwcm9qZWN0Lm9yZz6JAk4EEwEI
    ADgWIQT/itE0RZcQbs6BO5GKOHK/MihGfAUCYTeY6wIbDwULCQgHAgYVCgkICwIE
    FgIDAQIeAQIXgAAKCRCKOHK/MihGfFX/EACBPWv20+ttYu1A5WvtHJPzwbj0U4yF
    3zTQpBglQ2UfkRpYdipTlT3Ih6j5h2VmgRPtINCc/ZE28adrWpBoeFIS2YAKOCLC
    nZYtHl2nCoLq1U7FSttUGsZ/t8uGCBgnugTfnIYcmlP1jKKA6RJAclK89evDQX5n
    R9ZD+Cq3CBMlttvSTCht0qQVlwycedH8iWyYgP/mF0W35BIn7NuuZwWhgR00n/VG
    4nbKPOzTWbsP45awcmivdrS74P6mL84WfkghipdmcoyVb1B8ZP4Y/Ke0RXOnLhNe
    CfrXXvuW+Pvg2RTfwRDtehGQPAgXbmLmz2ZkV69RGIr54HJv84NDbqZovRTMr7gL
    9k3ciCzXCiYQgM8yAyGHV0KEhFSQ1HV7gMnt9UmxbxBE2pGU7vu3CwjYga5DpwU7
    w5wu1TmM5KgZtZvuWOTDnqDLf0cKoIbW8FeeCOn24elcj32bnQDuF9DPey1mqcvT
    /yEo/Ushyz6CVYxN8DGgcy2M9JOsnmjDx02h6qgWGWDuKgb9jZrvRedpAQCeemEd
    fhEs6ihqVxRFl16HxC4EVijybhAL76SsM2nbtIqW1apBQJQpXWtQwwdvgTVpdEtE
    r4ArVJYX5LrswnWEQMOelugUG6S3ZjMfcyOa/O0364iY73vyVgaYK+2XtT2usMux
    VL469Kj5m13T6w==
    =Mjs/
    -----END PGP PUBLIC KEY BLOCK-----'
);

foreach ($sources as $source) {
    /**
     *  Check if source repo already exists
     */
    if ($mysource->exists($source['type'], $source['name']) === true) {
        continue;
    }

    /**
     *  Insert source repo in database
     */
    $mysource->new($source['type'], $source['name'], $source['url']);
}

foreach ($gpgkeys as $gpgkey) {
    $mysource->importGpgKey($gpgkey);
}
