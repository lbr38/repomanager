<?php

namespace Controllers\Layout\Table;

use Exception;

class Render
{
    public static function render(string $table, int $offset = 0)
    {
        try {
            /**
             *  Check if table exists
             */
            if (!file_exists(ROOT . '/views/includes/tables/' . $table . '.inc.php')) {
                throw new Exception('Could not retrieve content: unknow table ' . $table);
            }

            /**
             *  Include vars file if exists
             */
            if (file_exists(ROOT . '/controllers/Layout/Table/vars/' . $table . '.vars.inc.php')) {
                include_once(ROOT . '/controllers/Layout/Table/vars/' . $table . '.vars.inc.php');
            }

            /**
             *  Include table content
             */
            include_once(ROOT . '/views/includes/tables/' . $table . '.inc.php');
        } catch (Exception $e) {
            echo '<p>' . $e->getMessage() . '</p>';
        }
    }

    /**
     *  Generate pagination buttons
     */
    public static function paginationBtn($currentPage, $totalPages)
    {
        $output = '';

        /**
         *  Don't print pagination if only one page
         */
        if ($totalPages == 1) {
            return;
        }

        /**
         *  Previous button
         */
        if ($currentPage > 1) {
            $output .= '<button class="reloadable-table-page-btn pagination-btn-first pagination-btn-previous" page="' . ($currentPage - 1) . '" title="Previous"><img src="/assets/icons/previous.svg" class="icon" /></button>';
        }

        /**
         *  Button 1
         */
        if ($currentPage == 1) {
            $output .= '<button class="reloadable-table-page-btn pagination-btn-first pagination-btn-current" page="1">1</button>';
        } else {
            $output .= '<button class="reloadable-table-page-btn pagination-btn" page="1">1</button>';
        }

        /**
         *  Print 2 previous and next pages
         */
        $start = max(2, $currentPage - 2);
        $end = min($totalPages - 1, $currentPage + 2);

        if ($start > 2) {
            $output .= '<span class="pagination-btn">...</span>';
        }

        for ($i = $start; $i <= $end; $i++) {
            if ($currentPage == $i) {
                $output .= '<button class="reloadable-table-page-btn pagination-btn pagination-btn-current" page="' . $i . '">' . $i . '</button>';
            } else {
                $output .= '<button class="reloadable-table-page-btn pagination-btn" page="' . $i . '">' . $i . '</button>';
            }
        }

        if ($end < $totalPages - 1) {
            $output .= '<span class="pagination-btn">...</span>';
        }

        /**
         *  Button last
         */
        if ($totalPages > 1) {
            if ($currentPage == $totalPages) {
                $output .= '<button class="reloadable-table-page-btn pagination-btn-last pagination-btn-current" page="' . $totalPages . '">' . $totalPages . '</button>';
            } else {
                $output .= '<button class="reloadable-table-page-btn pagination-btn" page="' . $totalPages . '">' . $totalPages . '</button>';
            }
        }

        /**
         *  Next button
         */
        if ($currentPage < $totalPages) {
            $output .= '<button class="reloadable-table-page-btn pagination-btn-last pagination-btn-next" page="' . ($currentPage + 1) . '" title="Next"><img src="/assets/icons/next.svg" class="icon" /></button>';
        }

        echo $output;
    }
}
