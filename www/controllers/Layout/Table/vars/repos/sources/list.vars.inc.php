<?php
$mysource = new \Controllers\Repo\Source\Source();
$reloadableTableOffset = 0;
$sourceSearch = '';

/**
 *  Source repositories types, one tab per type (in this order)
 */
$sourceTypes = [
    'rpm'     => 'RPM',
    'deb'     => 'DEB',
    'arch'    => 'Arch',
    'alpine'  => 'Alpine',
    'openbsd' => 'OpenBSD'
];

/**
 *  Retrieve the searched text and the offset from cookies if exist
 */
if (!empty($_COOKIE['tables/repos/sources/list/search']) and is_string($_COOKIE['tables/repos/sources/list/search'])) {
    $sourceSearch = mb_substr(trim($_COOKIE['tables/repos/sources/list/search']), 0, 100);
}

if (!empty($_COOKIE['tables/repos/sources/list/offset']) and is_numeric($_COOKIE['tables/repos/sources/list/offset'])) {
    $reloadableTableOffset = (int) $_COOKIE['tables/repos/sources/list/offset'];
}

/**
 *  Count source repositories by type, all of them and the ones matching the search
 */
$sourceTotalByType = $mysource->countByType();
$sourceMatchesByType = $sourceSearch !== '' ? $mysource->countByType($sourceSearch) : $sourceTotalByType;

/**
 *  Active tab: the one selected by the user (cookie), else the first one with source repositories
 *  While searching, if the selected tab has no result, the first tab with results is displayed instead (without changing the selected tab)
 */
$sourceActiveType = array_key_first(array_intersect_key($sourceTypes, $sourceTotalByType)) ?? array_key_first($sourceTypes);

if (!empty($_COOKIE['tables/repos/sources/list/type']) and array_key_exists($_COOKIE['tables/repos/sources/list/type'], $sourceTypes)) {
    $sourceActiveType = $_COOKIE['tables/repos/sources/list/type'];
}

if ($sourceSearch !== '' and empty($sourceMatchesByType[$sourceActiveType])) {
    foreach (array_keys($sourceTypes) as $sourceType) {
        if (!empty($sourceMatchesByType[$sourceType])) {
            $sourceActiveType = $sourceType;
            break;
        }
    }
}

/**
 *  Total count of the active tab, for the pagination
 */
$reloadableTableTotalItems = $sourceMatchesByType[$sourceActiveType] ?? 0;

/**
 *  Go back to the first page if the offset is out of range (e.g. tab or search changed)
 */
if ($reloadableTableOffset >= $reloadableTableTotalItems) {
    $reloadableTableOffset = 0;
}

/**
 *  Get list of source repos of the active tab, with offset
 */
$reloadableTableContent = [];

if ($reloadableTableTotalItems > 0) {
    $reloadableTableContent = $mysource->listAll($sourceActiveType, true, $reloadableTableOffset, $sourceSearch);
}

/**
 *  Count total pages for the pagination
 */
$reloadableTableTotalPages = ceil($reloadableTableTotalItems / 10);

/**
 *  Calculate current page number
 */
$reloadableTableCurrentPage = floor($reloadableTableOffset / 10) + 1;

/**
 *  Keys of the trusted keyring, to print the name of the GPG keys of the arch source repositories (only if the page has one)
 */
$trustedGpgKeys = [];

if ($sourceActiveType == 'arch' and !empty($reloadableTableContent)) {
    $trustedGpgKeys = \Controllers\Gpg::getTrustedKeys();
}

unset($mysource, $sourceType);
