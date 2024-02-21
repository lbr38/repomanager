<?php
$mycve = new \Controllers\Cve\Cve();
$reloadableTableOffset = 0;
$filter = '';

/**
 *  Retrieve filters if any
 *  Filters can either be in the URL (GET request) or in the POST request (when table is reloaded through ajax)
 */

/**
 *  Vendor filter
 */
if (!empty($_GET['vendor']) or !empty(__GET_PARAMETERS__['vendor'])) {
    /**
     *  Case the filter is in the POST request
     */
    if (!empty(__GET_PARAMETERS__['vendor'])) {
        $vendor = __GET_PARAMETERS__['vendor'];
    }

    /**
     *  Case the filter is in the URL (GET request)
     */
    if (!empty($_GET['vendor'])) {
        $vendor = $_GET['vendor'];
    }

    $filter = 'WHERE Vendor = "' . \Controllers\Common::validateData($vendor) . '"';
}

/**
 *  Product filter
 */
if (!empty($_GET['product']) or !empty(__GET_PARAMETERS__['product'])) {
    /**
     *  Case the filter is in the POST request
     */
    if (!empty(__GET_PARAMETERS__['product'])) {
        $product = __GET_PARAMETERS__['product'];
    }

    /**
     *  Case the filter is in the URL (GET request)
     */
    if (!empty($_GET['product'])) {
        $product = $_GET['product'];
    }

    $filter = 'WHERE Product = "' . \Controllers\Common::validateData($product) . '"';
}

/**
 *  Search filter (search input)
 */
if (!empty($_GET['search']) or !empty(__GET_PARAMETERS__['search'])) {
    /**
     *  Case the filter is in the POST request
     */
    if (!empty(__GET_PARAMETERS__['search'])) {
        $search = __GET_PARAMETERS__['search'];
    }

    /**
     *  Case the filter is in the URL (GET request)
     */
    if (!empty($_GET['search'])) {
        $search = $_GET['search'];
    }

    $search = \Controllers\Common::validateData($search);
    $filter = 'WHERE Name = "' . $search . '" OR Vendor = "' . $search . '" OR Product = "' . $search . '" OR Description = "' . $search . '"';
}

/**
 *  Retrieve offset from cookie if exists
 */
if (!empty($_COOKIE['tables/cves/list/offset']) and is_numeric($_COOKIE['tables/cves/list/offset'])) {
    $reloadableTableOffset = $_COOKIE['tables/cves/list/offset'];
}

/**
 *  Get list of ALL cves, without offset, for the total count
 */
$reloadableTableTotalItems = count($mycve->getAll(false, 0, $filter));

/**
 *  If offset is out of range, reset it to 0
 *  This can happen when a new filter is applied and the total count of items is less than the offset
 */
if ($reloadableTableOffset > $reloadableTableTotalItems) {
    $reloadableTableOffset = 0;
}

/**
 *  Get list of cves, with offset
 */
$reloadableTableContent = $mycve->getAll(true, $reloadableTableOffset, $filter);

/**
 *  Count total pages for the pagination
 */
$reloadableTableTotalPages = ceil($reloadableTableTotalItems / 10);

/**
 *  Calculate current page number
 */
$reloadableTableCurrentPage = ceil($reloadableTableOffset / 10) + 1;
