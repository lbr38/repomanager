<?php

namespace Controllers\Layout\Panel;

use Exception;
use Controllers\Exception\InvalidPanelException;
use Controllers\Utils\Validate;

class Render
{
    /**
     *  Render panel content
     *  InvalidPanelException is thrown when the panel name is invalid or the panel file does not
     *  exist, which should not be the case unless someone is trying to do something nasty
     *  Otherwise, a generic Exception is thrown when the panel file exists but an error occurs during rendering. The error is then shown in the panel content.
     */
    public static function render(string $panel, array|null $item = null): void
    {
        try {
            // Check if the panel name is valid
            if (!Validate::alphaNumericHyphen($panel, ['/'])) {
                throw new InvalidPanelException('Invalid panel name');
            }

            $_view = realpath(ROOT . '/views/includes/panels/' . $panel . '.inc.php');
            $_vars = realpath(ROOT . '/controllers/Layout/Panel/vars/' . $panel . '.vars.inc.php');

            // Check that the panel exists and is located inside the panels directory (prevent path traversal)
            if ($_view === false || !str_starts_with($_view, ROOT . '/views/includes/panels/')) {
                throw new InvalidPanelException('Unknown panel name');
            }

            // Include vars file if exists
            if ($_vars !== false && file_exists($_vars)) {
                include_once($_vars);
            }

            // Include container content
            include_once($_view);
        } catch (InvalidPanelException $e) {
            // Rethrow the exception to be handled by the caller (when called via ajax)
            throw $e;
        } catch (Exception $e) {
            // Otherwise include error container
            include(ROOT . '/views/includes/panels/error.inc.php');
        }

        unset($_view, $_vars);
    }
}
