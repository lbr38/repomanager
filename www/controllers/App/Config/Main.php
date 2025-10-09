<?php

namespace Controllers\App\Config;

use Exception;

class Main
{
    public static function get()
    {
        // Root dir
        if (!defined('ROOT')) {
            define('ROOT', '/var/www/repomanager');
        }
        // Data dir
        if (!defined('DATA_DIR')) {
            define('DATA_DIR', '/var/lib/repomanager');
        }
        // Databases dir
        if (!defined('DB_DIR')) {
            define('DB_DIR', DATA_DIR . "/db");
        }
        // Main database
        if (!defined('DB')) {
            define('DB', DB_DIR . "/repomanager.db");
        }
        // Stats database
        if (!defined('STATS_DB')) {
            define('STATS_DB', DB_DIR . "/repomanager-stats.db");
        }
        // Hosts database
        if (!defined('HOSTS_DB')) {
            define('HOSTS_DB', DB_DIR . "/repomanager-hosts.db");
        }
        // Websocket server database
        if (!defined('WS_DB')) {
            define('WS_DB', DB_DIR . "/repomanager-ws.db");
        }
        // GnuPG home
        if (!defined('GPGHOME')) {
            define('GPGHOME', DATA_DIR . "/.gnupg");
        }
        // Passphrase file
        if (!defined('PASSPHRASE_FILE')) {
            define('PASSPHRASE_FILE', GPGHOME . '/passphrase');
        }
        // RPM macros file
        if (!defined('MACROS_FILE')) {
            define('MACROS_FILE', DATA_DIR . '/.rpm/.mcs');
        }
        // Logs dir
        if (!defined('LOGS_DIR')) {
            define('LOGS_DIR', DATA_DIR . "/logs");
        }
        // Main logs dir
        if (!defined('MAIN_LOGS_DIR')) {
            define('MAIN_LOGS_DIR', LOGS_DIR . '/main');
        }
        // Service logs dir
        if (!defined('SERVICE_LOGS_DIR')) {
            define('SERVICE_LOGS_DIR', LOGS_DIR . '/service');
        }
        // Websocket requests logs dir
        if (!defined('WS_REQUESTS_LOGS_DIR')) {
            define('WS_REQUESTS_LOGS_DIR', LOGS_DIR . '/websocket-requests');
        }
        if (!defined('EXCEPTIONS_LOG')) {
            define('EXCEPTIONS_LOG', LOGS_DIR . '/exceptions');
        }
        if (!defined('FATAL_ERRORS_LOG')) {
            define('FATAL_ERRORS_LOG', LOGS_DIR . '/fatal_errors');
        }
        if (!defined('DB_UPDATE_DONE_DIR')) {
            define('DB_UPDATE_DONE_DIR', DATA_DIR . '/update');
        }
        if (!defined('UPDATE_SUCCESS_LOG')) {
            define('UPDATE_SUCCESS_LOG', LOGS_DIR . '/update/update.success');
        }
        if (!defined('UPDATE_ERROR_LOG')) {
            define('UPDATE_ERROR_LOG', LOGS_DIR . '/update/update.error');
        }
        if (!defined('UPDATE_INFO_LOG')) {
            define('UPDATE_INFO_LOG', LOGS_DIR . '/update/update.info');
        }
        // PIDs
        if (!defined('PID_DIR')) {
            define('PID_DIR', DATA_DIR . "/tasks/pid");
        }
        // Temp dir
        if (!defined('TEMP_DIR')) {
            define('TEMP_DIR', DATA_DIR . "/.temp");
        }
        // Hosts databases dir
        if (!defined('HOSTS_DIR')) {
            define('HOSTS_DIR', DATA_DIR . '/hosts');
        }
        // Cve affected hosts import dir
        if (!defined('CVE_IMPORT_HOSTS_DIR')) {
            define('CVE_IMPORT_HOSTS_DIR', TEMP_DIR . '/cve');
        }
        // Actual release version and available version on github
        if (!defined('VERSION')) {
            define('VERSION', trim(file_get_contents(ROOT . '/version')));
        }
        if (!file_exists(DATA_DIR . '/version.available')) {
            file_put_contents(DATA_DIR . '/version.available', VERSION);
        }
        if (!defined('GIT_VERSION')) {
            define('GIT_VERSION', trim(file_get_contents(DATA_DIR . '/version.available')));
        }
        if (!defined('UPDATE_AVAILABLE')) {
            if (defined('VERSION') and defined('GIT_VERSION') and version_compare(GIT_VERSION, VERSION, '>')) {
                define('UPDATE_AVAILABLE', true);
            } else {
                define('UPDATE_AVAILABLE', false);
            }
        }

        /**
         *  Check if a repomanager update is running
         */
        if (!defined('UPDATE_RUNNING')) {
            if (file_exists(DATA_DIR . '/update-running')) {
                define('UPDATE_RUNNING', true);
            } else {
                define('UPDATE_RUNNING', false);
            }
        }

        /**
         *  Check if app is under maintenance
         */
        if (!defined('MAINTENANCE')) {
            if (file_exists(DATA_DIR . '/maintenance')) {
                define('MAINTENANCE', true);
            } else {
                define('MAINTENANCE', false);
            }
        }

        /**
         *  Date and time
         */
        if (!defined('DATE_DMY')) {
            define('DATE_DMY', date('d-m-Y'));
        }
        if (!defined('DATE_YMD')) {
            define('DATE_YMD', date('Y-m-d'));
        }
        if (!defined('TIME')) {
            define('TIME', date('H-i'));
        }

        /**
         *  Repomanager service status
         */
        if (!defined('SERVICE_RUNNING')) {
            define('SERVICE_RUNNING', \Controllers\Service\Service::isRunning());
        }

        /**
         *  Source repositories lists dir
         */
        // Default source repositories lists dir (from github)
        if (!defined('DEFAULT_SOURCES_REPOS_LISTS_DIR')) {
            define('DEFAULT_SOURCES_REPOS_LISTS_DIR', ROOT . '/templates/source-repositories');
        }
        // Custom source repositories lists dir (made by the user)
        if (!defined('CUSTOM_SOURCES_REPOS_LISTS_DIR')) {
            define('CUSTOM_SOURCES_REPOS_LISTS_DIR', DATA_DIR . '/templates/source-repositories');
        }

        if (!defined('APP_YAML')) {
            define('APP_YAML', DATA_DIR . '/app.yaml');
        }

        if (!defined('DEVEL')) {
            if (file_exists(ROOT . '/.devel')) {
                define('DEVEL', true);
            } else {
                define('DEVEL', false);
            }
        }

        /**
         *  Load system constants
         */
        System::get();
    }
}
