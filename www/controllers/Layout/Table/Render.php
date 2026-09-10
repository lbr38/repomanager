<?php

namespace Controllers\Layout\Table;

use Exception;
use Controllers\Exception\InvalidTableException;
use Controllers\Utils\Validate;

class Render
{
    /**
     *  Render table content
     *  InvalidTableException is thrown when the table name is invalid or the table file does not
     *  exist, which should not be the case unless someone is trying to do something nasty
     *  Otherwise, a generic Exception is thrown when the table file exists but an error occurs during rendering. The error is then shown in the table content.
     */
    public static function render(string $table, int $offset = 0): void
    {
        try {
            // Check if the table name is valid
            if (!Validate::alphaNumericHyphen($table, ['/'])) {
                throw new InvalidTableException('Invalid table name');
            }

            $_view = realpath(ROOT . '/views/includes/tables/' . $table . '.inc.php');
            $_vars = realpath(ROOT . '/controllers/Layout/Table/vars/' . $table . '.vars.inc.php');

            // Check that the table exists and is located inside the tables directory (prevent path traversal)
            if ($_view === false || !str_starts_with($_view, ROOT . '/views/includes/tables/')) {
                throw new InvalidTableException('Unknown table name');
            }

            // Include vars file if exists
            if ($_vars !== false && file_exists($_vars)) {
                include_once($_vars);
            }

            // Include table content
            include_once($_view);
        } catch (InvalidTableException $e) {
            // Rethrow the exception to be handled by the caller (when called via ajax)
            throw $e;
        } catch (Exception $e) {
            // Otherwise include error container
            include(ROOT . '/views/includes/tables/error.inc.php');
        }

        unset($_view, $_vars);
    }

    /**
     *  Generate pagination buttons
     */
    public static function paginationBtn($currentPage, $totalPages): void
    {
        $output = '';

        // Don't print pagination if only one page
        if ($totalPages == 1) {
            return;
        }

        // Previous button
        if ($currentPage > 1) {
            $output .= '<button class="reloadable-table-page-btn pagination-btn-first pagination-btn-previous" page="' . ($currentPage - 1) . '" title="Previous">❮</button>';
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
            $output .= '<button class="reloadable-table-page-btn pagination-btn-last pagination-btn-next" page="' . ($currentPage + 1) . '" title="Next">❯</button>';
        }

        echo $output;
    }
}
