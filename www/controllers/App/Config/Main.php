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
        // Cache dir
        if (!defined('WWW_CACHE')) {
            define('WWW_CACHE', DATA_DIR . "/cache");
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
        if (!defined('EXCEPTIONS_LOG')) {
            define('EXCEPTIONS_LOG', LOGS_DIR . '/exceptions');
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
        if (!defined('CVE_LOG_DIR')) {
            define('CVE_LOG_DIR', LOGS_DIR . '/cve');
        }
        // Async tasks pool dir
        if (!defined('POOL')) {
            define('POOL', DATA_DIR . "/tasks/pool");
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
        // Logbuilder
        if (!defined('LOGBUILDER')) {
            define('LOGBUILDER', ROOT . '/tools/logbuilder.php');
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

        if (defined('VERSION') and defined('GIT_VERSION')) {
            if (!empty(GIT_VERSION) && VERSION !== GIT_VERSION) {
                if (!defined('UPDATE_AVAILABLE')) {
                    define('UPDATE_AVAILABLE', 'true');
                }
            } else {
                if (!defined('UPDATE_AVAILABLE')) {
                    define('UPDATE_AVAILABLE', 'false');
                }
            }
        } else {
            define('UPDATE_AVAILABLE', 'false');
        }

        /**
         *  Check if a repomanager update is running
         */
        if (file_exists(DATA_DIR . "/update-running")) {
            if (!defined('UPDATE_RUNNING')) {
                define('UPDATE_RUNNING', 'true');
            }
        } else {
            if (!defined('UPDATE_RUNNING')) {
                define('UPDATE_RUNNING', 'false');
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
         *  Debug mode
         */
        if (!defined('DEBUG_MODE')) {
            define('DEBUG_MODE', false);
        }

        /**
         *  Repomanager service status
         */
        if (!defined('SERVICE_RUNNING')) {
            define('SERVICE_RUNNING', \Controllers\Service\Service::isRunning());
        }

        /**
         *  Load system constants
         */
        System::get();
    }
}
