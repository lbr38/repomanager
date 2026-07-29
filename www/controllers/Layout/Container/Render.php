<?php

namespace Controllers\Layout\Container;

use Exception;
use Controllers\Exception\InvalidContainerException;
use Controllers\Utils\Validate;

class Render
{
    /**
     *  Render container content
     *  InvalidContainerException is thrown when the container name is invalid or the container file does not
     *  exist, which should not be the case unless someone is trying to do something nasty
     *  Otherwise, a generic Exception is thrown when the container file exists but an error occurs during rendering. The error is then shown in the container content.
     */
    public static function render(string $container): void
    {
        try {
            // Check if the container name is valid
            if (!Validate::alphaNumericHyphen($container, ['/'])) {
                throw new InvalidContainerException('Invalid container name');
            }

            $_view = realpath(ROOT . '/views/includes/containers/' . $container . '.inc.php');
            $_vars = realpath(ROOT . '/controllers/Layout/Container/vars/' . $container . '.vars.inc.php');

            // Check that the container exists and is located inside the containers directory (prevent path traversal)
            if ($_view === false || !str_starts_with($_view, ROOT . '/views/includes/containers/')) {
                throw new InvalidContainerException('Unknown container name');
            }

            // Include vars file if exists
            if ($_vars !== false && file_exists($_vars)) {
                include_once($_vars);
            }

            // Include container content
            include_once($_view);
        } catch (InvalidContainerException $e) {
            // Rethrow the exception to be handled by the caller (when called via ajax)
            throw $e;
        } catch (Exception $e) {
            // Otherwise include error container
            include(ROOT . '/views/includes/containers/error.inc.php');
        }

        unset($_view, $_vars);
    }
}
