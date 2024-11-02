<?php
/**
 *  4.12.1 update
 *  TODO: move this to a separate "startup" file later
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
