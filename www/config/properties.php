<?php
$config = [
    'project_name' => 'Repomanager',
    'project_dir_name' => 'repomanager',
    'project_logo' => 'https://raw.githubusercontent.com/lbr38/repomanager/main/www/public/assets/official-logo/repomanager-gray-256.png',
    'project_git_repo' => 'https://github.com/lbr38/repomanager',
    'project_git_repo_raw' => 'https://raw.githubusercontent.com/lbr38/repomanager/main',
    'project_update_doc_url' => 'https://github.com/lbr38/repomanager/wiki/01.-Installation-and-update#update-repomanager',
    'releases_url' => 'https://api.github.com/repos/lbr38/repomanager/releases',
    'version_file_url' => 'https://raw.githubusercontent.com/lbr38/repomanager/main/www/version',

    // RPM release versions default values
    'rpm_releasevers' => array('9' => 'RHEL 9 and derivatives', '8' => 'RHEL 8 and derivatives', '7' => 'RHEL 7 and derivatives'),

    // DEB distributions default values
    'deb_distributions' => array('bookworm' => 'Debian 12', 'bullseye' => 'Debian 11', 'buster' => 'Debian 10', 'stretch' => 'Debian 9', 'jessie' => 'Debian 8', 'wheezy' => 'Debian 7', 'noble' => 'Ubuntu 24.04', 'jammy' => 'Ubuntu 22.04', 'hirsute' => 'Ubuntu 21.04', 'groovy' => 'Ubuntu 20.10', 'focal' => 'Ubuntu 20.04', 'eoan' => 'Ubuntu 19.10', 'disco' => 'Ubuntu 19.04', 'cosmic' => 'Ubuntu 18.10', 'bionic' => 'Ubuntu 18.04', 'xenial' => 'Ubuntu 16.04', 'trusty' => 'Ubuntu 14.04'),

    // DEB components default values
    'deb_components' => array('main', 'contrib', 'non-free', 'restricted', 'universe', 'multiverse'),

    // DEB default values
    'deb_archs' => array('amd64', 'arm64', 'armel', 'armhf', 'i386', 'mips', 'mips64el', 'mipsel', 'ppc64el', 's390x', 'src'),

    // RPM default values
    'rpm_archs' => array('noarch', 'i386', 'i586', 'i686', 'x86_64', 'armv6hl', 'armv7hl', 'aarch64', 'ppc64', 'ppc64le', 's390x', 'src'),
];
