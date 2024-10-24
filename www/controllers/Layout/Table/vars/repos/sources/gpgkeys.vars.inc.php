<?php
$mygpg = new \Controllers\Gpg();
$reloadableTableOffset = 0;

/**
 *  Retrieve offset from cookie if exists
 */
if (!empty($_COOKIE['tables/repos/sources/gpgkeys/offset']) and is_numeric($_COOKIE['tables/repos/sources/gpgkeys/offset'])) {
    $reloadableTableOffset = $_COOKIE['tables/repos/sources/gpgkeys/offset'];
}

/**
 *  Get ALL imported GPG signing keys
 */
$knownPublicKeys = $mygpg->getTrustedKeys();

/**
 *  Use array_slice to get only 10 items
 */
$reloadableTableContent = array_slice($knownPublicKeys, $reloadableTableOffset, 10);

/**
 *  Count total items
 */
$reloadableTableTotalItems = count($knownPublicKeys);

/**
 *  Count total pages for the pagination
 */
$reloadableTableTotalPages = ceil($reloadableTableTotalItems / 10);

/**
 *  Calculate current page number
 */
$reloadableTableCurrentPage = ceil($reloadableTableOffset / 10) + 1;

unset($mygpg);
