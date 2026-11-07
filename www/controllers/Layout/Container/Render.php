<?php

namespace Controllers\Layout\Container;

use Exception;
use Controllers\Exception\InvalidContainerException;
use Controllers\Utils\Validate;

class Render
{
    /**
     *  Containers that are only available to administrators (their tabs are only available to administrators)
     */
    private const ADMIN_ONLY = ['cves/', 'history/', 'settings/', 'status/'];

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

            // Check that the user is allowed to access the container
            foreach (self::ADMIN_ONLY as $prefix) {
                if (str_starts_with($container, $prefix) and !IS_ADMIN) {
                    throw new InvalidContainerException('You are not allowed to access this container');
                }
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
