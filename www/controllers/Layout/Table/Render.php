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
                throw new Exception('Could not retrieve content: unknown table ' . $table);
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

        // Don't print pagination if only one page
        if ($totalPages == 1) {
            return;
        }

        // Previous button
        if ($currentPage > 1) {
            $output .= '<button class="reloadable-table-page-btn pagination-btn-first pagination-btn-previous" page="' . ($currentPage - 1) . '" title="Previous"><img src="/assets/icons/previous.svg" class="icon" /></button>';
        }

        // First page button (n°1)
        if ($currentPage == 1) {
            $output .= '<button class="reloadable-table-page-btn pagination-btn-first pagination-btn-current" page="1">1</button>';
        } else {
            $output .= '<button class="reloadable-table-page-btn pagination-btn" page="1">1</button>';
        }

        // Dots if needed before current-1
        if ($currentPage > 3) {
            $output .= '<span class="pagination-btn">...</span>';
        }

        // Page before current
        if ($currentPage - 1 > 1) {
            $output .= '<button class="reloadable-table-page-btn pagination-btn" page="' . ($currentPage - 1) . '">' . ($currentPage - 1) . '</button>';
        }

        // Current page (if not first or last)
        if ($currentPage != 1 && $currentPage != $totalPages) {
            $output .= '<button class="reloadable-table-page-btn pagination-btn pagination-btn-current" page="' . $currentPage . '">' . $currentPage . '</button>';
        }

        // Page after current
        if ($currentPage + 1 < $totalPages) {
            $output .= '<button class="reloadable-table-page-btn pagination-btn" page="' . ($currentPage + 1) . '">' . ($currentPage + 1) . '</button>';
        }

        // Dots if needed after current+1
        if ($currentPage < $totalPages - 2) {
            $output .= '<span class="pagination-btn">...</span>';
        }

        // Last page button
        if ($totalPages > 1) {
            if ($currentPage == $totalPages) {
                $output .= '<button class="reloadable-table-page-btn pagination-btn-last pagination-btn-current" page="' . $totalPages . '">' . $totalPages . '</button>';
            } else {
                $output .= '<button class="reloadable-table-page-btn pagination-btn" page="' . $totalPages . '">' . $totalPages . '</button>';
            }
        }

        // Next button
        if ($currentPage < $totalPages) {
            $output .= '<button class="reloadable-table-page-btn pagination-btn-last pagination-btn-next" page="' . ($currentPage + 1) . '" title="Next"><img src="/assets/icons/next.svg" class="icon" /></button>';
        }

        echo $output;
    }
}
