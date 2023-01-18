<?php

namespace Controllers\Layout\Tab;

class Cves
{
    public static function render()
    {
        $mycve = new \Controllers\Cve\Cve();
        $filter = '';

        /**
         *  Only admin have access to this page
         */
        if (!IS_ADMIN) {
            header('Location: /');
            exit;
        }

        /**
         *  Retrieve CVEs id list for the current page, using filter if any, and count them
         */
        if (!empty($_GET['page']) and is_numeric($_GET['page']) and $_GET['page'] > 1) {
            $currentPage = $_GET['page'];
            $previousPage = $currentPage - 1;
            $nextPage = $currentPage + 1;
            $previousPageLink = '?page=' . $currentPage - 1;
            $nextPageLink = '?page=' . $currentPage + 1;
            $startIndex = $currentPage * 50;
        } else {
            $currentPage = 1;
            $previousPage = 1;
            $nextPage = 2;
            $previousPageLink = '?page=1';
            $nextPageLink = '?page=2';
            $startIndex = 0;
        }

        /**
         *  Retrieve filters if any
         */
        if (!empty($_GET['vendor'])) {
            $filter = 'WHERE vendor = "' . $_GET['vendor'] . '"';
            $previousPageLink .= '&vendor=' . $_GET['vendor'];
            $nextPageLink .= '&vendor=' . $_GET['vendor'];
        }
        if (!empty($_GET['product'])) {
            $filter = 'WHERE product = "' . $_GET['product'] . '"';
            $previousPageLink .= '&product=' . $_GET['product'];
            $nextPageLink .= '&product=' . $_GET['product'];
        }

        /**
         *  Retrieve CVEs id list for the current page, using filter if any
         */
        $cveIdList = $mycve->getAllIdByIndex($startIndex, $filter);

        /**
         *  Get all CVEs Id and count them
         */
        $totalImportedCve = count($mycve->getAllId());
        $pagesCount = ceil($totalImportedCve / 50);

        if (!empty($filter)) {
            /**
             *  Get all CVEs Id mathching the filter and count them
             */
            $totalCveFound = count($mycve->getAllIdByIndex(-1, $filter));
            $pagesCount = ceil($totalCveFound / 50);
        }

        include_once(ROOT . '/views/cves.template.php');
    }
}
