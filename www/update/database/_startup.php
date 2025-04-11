<?php
/**
 *  Database requests to execute before all other updates
 *  Unless the _always-*.php files, this file is executed only once like all other x.x.x.php files
 */
$mysource = new \Controllers\Repo\Source\Source();

/**
 *  If no sources repositories are defined, import some default ones
 */
try {
    $sources = $mysource->listAll();

    if (!empty($sources)) {
        return;
    }

    /**
     *  Define default sources files, prefix with 'github/' to indicate that the source is a github repository
     */
    $lists = [
        // deb
        'github/deb/debian',
        'github/deb/debian', // TODO temporary fix: import the same source twice to avoid a bug with gpg initialization, to fix
        'github/deb/debian-archive',
        'github/deb/ubuntu',
        'github/deb/ubuntu-archive',
        // rpm
        'github/rpm/centos',
        'github/rpm/centos-vault',
        'github/rpm/redhat',
        'github/rpm/epel',
    ];

    /**
     *  Import
     */
    $mysource->import($lists);
} catch (Exception $e) {
    throw new Exception('could not import default source repositories: ' . $e->getMessage());
}
