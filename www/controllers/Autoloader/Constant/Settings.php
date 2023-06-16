<?php

namespace Controllers\Autoloader\Constant;

use Exception;

class Settings
{
    public static function get()
    {
        $__LOAD_SETTINGS_ERROR = 0;
        $__LOAD_SETTINGS_MESSAGES = array();

        $mysettings = new \Controllers\Settings();
        $myconn = new \Controllers\Connection();

        /**
         *  Check that database exists or generate it with default settings
         */
        $myconn->checkDatabase('main');

        /**
         *  Get all settings
         */
        $settings = $mysettings->get();

        /**
         *  Si certains paramètres sont vides alors on incrémente $EMPTY_CONFIGURATION_VARIABLES qui fera afficher un bandeau d'alertes.
         *  Certains paramètres font exceptions et peuvent rester vides
         */
        foreach ($settings as $key => $value) {
            /**
             *  Les paramètres suivants peuvent rester vides, on n'incrémente pas le compteur d'erreurs dans leur cas
             */
            $ignoreEmptyParam = array('STATS_LOG_PATH', 'RPM_DEFAULT_ARCH', 'DEB_DEFAULT_ARCH', 'DEB_DEFAULT_TRANSLATION');

            if (in_array($key, $ignoreEmptyParam)) {
                continue;
            }

            if (empty($value)) {
                ++$__LOAD_SETTINGS_ERROR;
            }
        }

        if (!defined('REPOS_DIR')) {
            if (!empty($settings['REPOS_DIR'])) {
                define('REPOS_DIR', $settings['REPOS_DIR']);

                if (!is_writable(REPOS_DIR)) {
                    ++$__LOAD_SETTINGS_ERROR; // On force l'affichage d'un message d'erreur même si le paramètre n'est pas vide
                    $__LOAD_SETTINGS_MESSAGES[] = "Repos directory '" . REPOS_DIR . "' is not writeable.";
                }
            } else {
                define('REPOS_DIR', '');
                $__LOAD_SETTINGS_MESSAGES[] = 'Repos directory is not defined. ';
            }
        }

        if (!defined('TIMEZONE')) {
            if (!empty($settings['TIMEZONE'])) {
                define('TIMEZONE', $settings['TIMEZONE']);
            } else {
                define('TIMEZONE', 'Europe/Paris');
            }
        }

        /**
         *  Set default timezone
         */
        date_default_timezone_set(TIMEZONE);

        if (!defined('EMAIL_RECIPIENT')) {
            if (!empty($settings['EMAIL_RECIPIENT'])) {
                define('EMAIL_RECIPIENT', explode(',', $settings['EMAIL_RECIPIENT']));
            } else {
                define('EMAIL_RECIPIENT', array());
                $__LOAD_SETTINGS_MESSAGES[] = 'No recipient email adress is defined. ';
            }
        }

        if (!defined('DEBUG_MODE')) {
            if (!empty($settings['DEBUG_MODE'])) {
                define('DEBUG_MODE', $settings['DEBUG_MODE']);
            } else {
                define('DEBUG_MODE', 'false');
            }
        }

        /**
         *  Paramètres web
         */
        if (!defined('WWW_HOSTNAME')) {
            if (!empty($settings['WWW_HOSTNAME'])) {
                define('WWW_HOSTNAME', $settings['WWW_HOSTNAME']);
            } else {
                define('WWW_HOSTNAME', 'localhost');
            }
        }
        if (!defined('WWW_REPOS_DIR_URL')) {
            define('WWW_REPOS_DIR_URL', __SERVER_PROTOCOL__ . '://' . WWW_HOSTNAME . '/repo');
        }

        if (!defined('WWW_USER')) {
            if (!empty($settings['WWW_USER'])) {
                define('WWW_USER', $settings['WWW_USER']);
            } else {
                define('WWW_USER', '');
                $__LOAD_SETTINGS_MESSAGES[] = "Linux web dedied user is not defined.";
            }
        }

        /**
         *  Paramètres de repos
         */

        // RPM
        if (!defined('RPM_REPO')) {
            if (!empty($settings['RPM_REPO'])) {
                define('RPM_REPO', $settings['RPM_REPO']);
            } else {
                define('RPM_REPO', 'false');
            }
        }

        if (!defined('RPM_SIGN_PACKAGES')) {
            if (!empty($settings['RPM_SIGN_PACKAGES'])) {
                define('RPM_SIGN_PACKAGES', $settings['RPM_SIGN_PACKAGES']);
            } else {
                define('RPM_SIGN_PACKAGES', 'false');
            }
        }

        if (!defined('RPM_SIGN_METHOD')) {
            define('RPM_SIGN_METHOD', 'rpmsign');
        }

        if (!defined('RELEASEVER')) {
            if (!empty($settings['RELEASEVER'])) {
                define('RELEASEVER', $settings['RELEASEVER']);
            } else {
                define('RELEASEVER', '');

                /**
                 *  On affiche un message uniquement si les repos RPM sont activés.
                 */
                if (RPM_REPO == 'true') {
                    $__LOAD_SETTINGS_MESSAGES[] = "Release version for RPM repositories is not defined.";
                }
            }
        }

        if (!defined('RPM_DEFAULT_ARCH')) {
            if (!empty($settings['RPM_DEFAULT_ARCH'])) {
                define('RPM_DEFAULT_ARCH', explode(',', $settings['RPM_DEFAULT_ARCH']));
            } else {
                define('RPM_DEFAULT_ARCH', array());
            }
        }

        if (!defined('RPM_INCLUDE_SOURCE')) {
            if (!empty($settings['RPM_INCLUDE_SOURCE'])) {
                define('RPM_INCLUDE_SOURCE', $settings['RPM_INCLUDE_SOURCE']);
            } else {
                define('RPM_INCLUDE_SOURCE', 'false');
            }
        }

        // DEB
        if (!defined('DEB_REPO')) {
            if (!empty($settings['DEB_REPO'])) {
                define('DEB_REPO', $settings['DEB_REPO']);
            } else {
                define('DEB_REPO', 'false');
            }
        }

        if (!defined('DEB_SIGN_REPO')) {
            if (!empty($settings['DEB_SIGN_REPO'])) {
                define('DEB_SIGN_REPO', $settings['DEB_SIGN_REPO']);
            } else {
                define('DEB_SIGN_REPO', 'false');
            }
        }

        if (!defined('DEB_DEFAULT_ARCH')) {
            if (!empty($settings['DEB_DEFAULT_ARCH'])) {
                define('DEB_DEFAULT_ARCH', explode(',', $settings['DEB_DEFAULT_ARCH']));
            } else {
                define('DEB_DEFAULT_ARCH', array());
            }
        }

        if (!defined('DEB_INCLUDE_SOURCE')) {
            if (!empty($settings['DEB_INCLUDE_SOURCE'])) {
                define('DEB_INCLUDE_SOURCE', $settings['DEB_INCLUDE_SOURCE']);
            } else {
                define('DEB_INCLUDE_SOURCE', 'false');
            }
        }

        if (!defined('DEB_DEFAULT_TRANSLATION')) {
            if (!empty($settings['DEB_DEFAULT_TRANSLATION'])) {
                define('DEB_DEFAULT_TRANSLATION', explode(',', $settings['DEB_DEFAULT_TRANSLATION']));
            } else {
                define('DEB_DEFAULT_TRANSLATION', array());
            }
        }

        if (!defined('GPG_SIGNING_KEYID')) {
            if (!empty($settings['GPG_SIGNING_KEYID'])) {
                define('GPG_SIGNING_KEYID', $settings['GPG_SIGNING_KEYID']);
            } else {
                /**
                 *  Define a default key ID
                 */
                define('GPG_SIGNING_KEYID', '');
                $__LOAD_SETTINGS_MESSAGES[] = "GPG key Id is not defined.";
            }
        }

        /**
         *  Paramètres d'automatisation
         */
        if (!defined('PLANS_ENABLED')) {
            if (!empty($settings['PLANS_ENABLED'])) {
                define('PLANS_ENABLED', $settings['PLANS_ENABLED']);
            } else {
                define('PLANS_ENABLED', '');
                $__LOAD_SETTINGS_MESSAGES[] = "Enabling plans is not defined.";
            }
        }

        if (!defined('PLANS_REMINDERS_ENABLED')) {
            if (!empty($settings['PLANS_REMINDERS_ENABLED'])) {
                define('PLANS_REMINDERS_ENABLED', $settings['PLANS_REMINDERS_ENABLED']);
            } else {
                define('PLANS_REMINDERS_ENABLED', 'false');
            }
        }

        if (!defined('PLANS_UPDATE_REPO')) {
            if (!empty($settings['PLANS_UPDATE_REPO'])) {
                define('PLANS_UPDATE_REPO', $settings['PLANS_UPDATE_REPO']);
            } else {
                define('PLANS_UPDATE_REPO', '');
                if (defined('PLANS_ENABLED') and PLANS_ENABLED == "true") {
                    $__LOAD_SETTINGS_MESSAGES[] = "Allowing plans to update repositories is not defined.";
                }
            }
        }

        if (!defined('PLANS_CLEAN_REPOS')) {
            if (!empty($settings['PLANS_CLEAN_REPOS'])) {
                define('PLANS_CLEAN_REPOS', $settings['PLANS_CLEAN_REPOS']);
            } else {
                define('PLANS_CLEAN_REPOS', '');
                if (defined('PLANS_ENABLED') and PLANS_ENABLED == "true") {
                    $__LOAD_SETTINGS_MESSAGES[] = "Allowing plans to delete old repos snapshots is not defined.";
                }
            }
        }

        if (!defined('RETENTION')) {
            if (isset($settings['RETENTION']) and $settings['RETENTION'] >= 0) {
                define('RETENTION', intval($settings['RETENTION'], 8));
            } else {
                define('RETENTION', '');
                if (defined('PLANS_ENABLED') and PLANS_ENABLED == "true") {
                    $__LOAD_SETTINGS_MESSAGES[] = "Old repos snapshots retention is not defined.";
                }
            }
        }

        /**
         *  Paramètres des hôtes
         */
        if (!defined('MANAGE_HOSTS')) {
            if (!empty($settings['MANAGE_HOSTS'])) {
                define('MANAGE_HOSTS', $settings['MANAGE_HOSTS']);
            } else {
                define('MANAGE_HOSTS', '');
                $__LOAD_SETTINGS_MESSAGES[] = "Enabling hosts management is not defined.";
            }
        }

        /**
         *  Paramètres des profils
         */
        if (!defined('MANAGE_PROFILES')) {
            if (!empty($settings['MANAGE_PROFILES'])) {
                define('MANAGE_PROFILES', $settings['MANAGE_PROFILES']);
            } else {
                define('MANAGE_PROFILES', '');
                $__LOAD_SETTINGS_MESSAGES[] = "Enabling profiles management is not defined.";
            }
        }

        if (!defined('REPO_CONF_FILES_PREFIX')) {
            if (!empty($settings['REPO_CONF_FILES_PREFIX'])) {
                define('REPO_CONF_FILES_PREFIX', $settings['REPO_CONF_FILES_PREFIX']);
            } else {
                define('REPO_CONF_FILES_PREFIX', '');
            }
        }

        /**
         *  Paramètres statistiques
         */
        if (!defined('STATS_ENABLED')) {
            if (!empty($settings['STATS_ENABLED'])) {
                define('STATS_ENABLED', $settings['STATS_ENABLED']);
            } else {
                define('STATS_ENABLED', '');
                $__LOAD_SETTINGS_MESSAGES[] = "Enabling repos statistics is not defined.";
            }
        }

        if (STATS_ENABLED == "true") {
            if (!defined('STATS_LOG_PATH')) {
                if (!empty($settings['STATS_LOG_PATH'])) {
                    define('STATS_LOG_PATH', $settings['STATS_LOG_PATH']);

                    /**
                     *  On teste l'accès au chemin renseigné
                     */
                    if (!is_readable(STATS_LOG_PATH)) {
                        ++$__LOAD_SETTINGS_ERROR; // On force l'affichage d'un message d'erreur même si le paramètre n'est pas vide
                        $__LOAD_SETTINGS_MESSAGES[] = "Access log file to scan for statistics is not readable: '" . STATS_LOG_PATH . "'";
                    }
                } else {
                    define('STATS_LOG_PATH', '');
                    $__LOAD_SETTINGS_MESSAGES[] = "Access log file to scan for statistics is not defined.";
                }
            }
        }

        if (!defined('__LOAD_SETTINGS_ERROR')) {
            define('__LOAD_SETTINGS_ERROR', $__LOAD_SETTINGS_ERROR);
        }
        if (!defined('__LOAD_SETTINGS_MESSAGES')) {
            define('__LOAD_SETTINGS_MESSAGES', $__LOAD_SETTINGS_MESSAGES);
        }

        unset($myconn);
    }
}
