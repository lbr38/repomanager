<?php

namespace Controllers\App\Config;

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
         * Get app.yaml
         */
        $app_yaml = yaml_parse_file(APP_YAML);

        /**
         *  If some settings are empty then we increment $EMPTY_CONFIGURATION_VARIABLES which will display a warning banner.
         *  Some settings are exceptions and can be empty
         */
        foreach ($settings as $key => $value) {
            /**
             *  Following parameters can be empty (or equal to 0), we don't increment the error counter in their case
             */
            $ignoreEmptyParam = array('EMAIL_RECIPIENT', 'PROXY', 'RPM_DEFAULT_ARCH', 'DEB_DEFAULT_ARCH', 'DEB_DEFAULT_TRANSLATION', 'REPO_CONF_FILES_PREFIX', 'RETENTION', 'OIDC_PROVIDER_URL', 'OIDC_AUTHORIZATION_ENDPOINT', 'OIDC_TOKEN_ENDPOINT', 'OIDC_USERINFO_ENDPOINT', 'OIDC_SCOPES', 'OIDC_CLIENT_ID', 'OIDC_CLIENT_SECRET');

            if (in_array($key, $ignoreEmptyParam)) {
                continue;
            }

            if (empty($value)) {
                ++$__LOAD_SETTINGS_ERROR;
            }
        }

        if (!defined('REPOS_DIR')) {
            define('REPOS_DIR', '/home/repo');
        }

        if (!is_writable(REPOS_DIR)) {
            ++$__LOAD_SETTINGS_ERROR;
            $__LOAD_SETTINGS_MESSAGES[] = "Repositories main directory <code>" . REPOS_DIR . "</code> is not writeable.";
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
            }
        }

        if (!defined('DEBUG_MODE')) {
            if (!empty($settings['DEBUG_MODE'])) {
                define('DEBUG_MODE', $settings['DEBUG_MODE']);
            } else {
                define('DEBUG_MODE', false);
            }
        }

        /**
         *  Global settings
         */
        if (!defined('WWW_HOSTNAME')) {
            /**
             *  FQDN file is created by the dockerfile
             */
            if (file_exists(ROOT . '/.fqdn')) {
                define('WWW_HOSTNAME', trim(file_get_contents(ROOT . '/.fqdn')));
            } else {
                define('WWW_HOSTNAME', 'localhost');
            }
        }

        if (!defined('PROXY')) {
            if (!empty($settings['PROXY'])) {
                define('PROXY', $settings['PROXY']);
            } else {
                define('PROXY', '');
            }
        }

        if (!defined('TASK_QUEUING')) {
            if (!empty($settings['TASK_QUEUING'])) {
                define('TASK_QUEUING', $settings['TASK_QUEUING']);
            } else {
                define('TASK_QUEUING', 'false');
            }
        }

        if (!defined('TASK_QUEUING_MAX_SIMULTANEOUS')) {
            if (!empty($settings['TASK_QUEUING_MAX_SIMULTANEOUS'])) {
                define('TASK_QUEUING_MAX_SIMULTANEOUS', $settings['TASK_QUEUING_MAX_SIMULTANEOUS']);
            } else {
                define('TASK_QUEUING_MAX_SIMULTANEOUS', 2);
            }
        }

        if (!defined('TASK_EXECUTION_MEMORY_LIMIT')) {
            if (!empty($settings['TASK_EXECUTION_MEMORY_LIMIT'])) {
                define('TASK_EXECUTION_MEMORY_LIMIT', $settings['TASK_EXECUTION_MEMORY_LIMIT']);
            } else {
                define('TASK_EXECUTION_MEMORY_LIMIT', 512);
            }
        }

        if (!defined('WWW_REPOS_DIR_URL')) {
            define('WWW_REPOS_DIR_URL', __SERVER_PROTOCOL__ . '://' . WWW_HOSTNAME . '/repo');
        }

        if (!defined('WWW_USER')) {
            define('WWW_USER', 'www-data');
        }

        /**
         *  Repositories
         */

        // Global settings
        if (!defined('REPO_CONF_FILES_PREFIX')) {
            if (!empty($settings['REPO_CONF_FILES_PREFIX'])) {
                define('REPO_CONF_FILES_PREFIX', $settings['REPO_CONF_FILES_PREFIX']);
            } else {
                define('REPO_CONF_FILES_PREFIX', '');
            }
        }

        if (!defined('RETENTION')) {
            if (isset($settings['RETENTION']) and $settings['RETENTION'] >= 0) {
                define('RETENTION', intval($settings['RETENTION'], 8));
            } else {
                define('RETENTION', 3);
            }
        }

        // Mirroring settings
        if (!defined('MIRRORING_PACKAGE_DOWNLOAD_TIMEOUT')) {
            if (!empty($settings['MIRRORING_PACKAGE_DOWNLOAD_TIMEOUT'])) {
                define('MIRRORING_PACKAGE_DOWNLOAD_TIMEOUT', $settings['MIRRORING_PACKAGE_DOWNLOAD_TIMEOUT']);
            } else {
                define('MIRRORING_PACKAGE_DOWNLOAD_TIMEOUT', '300');
            }
        }

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

        if (!defined('RELEASEVER')) {
            if (!empty($settings['RELEASEVER'])) {
                define('RELEASEVER', $settings['RELEASEVER']);
            } else {
                define('RELEASEVER', '');

                /**
                 *  Print a message only if RPM repositories are enabled.
                 */
                if (RPM_REPO == 'true') {
                    $__LOAD_SETTINGS_MESSAGES[] = "<code>DEFAULT RELEASE VERSION</code> setting is not defined.";
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

        if (!defined('RPM_MISSING_SIGNATURE')) {
            if (!empty($settings['RPM_MISSING_SIGNATURE'])) {
                define('RPM_MISSING_SIGNATURE', $settings['RPM_MISSING_SIGNATURE']);
            } else {
                define('RPM_MISSING_SIGNATURE', 'error');
            }
        }

        if (!defined('RPM_INVALID_SIGNATURE')) {
            if (!empty($settings['RPM_INVALID_SIGNATURE'])) {
                define('RPM_INVALID_SIGNATURE', $settings['RPM_INVALID_SIGNATURE']);
            } else {
                define('RPM_INVALID_SIGNATURE', 'error');
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

        if (!defined('DEB_DEFAULT_TRANSLATION')) {
            if (!empty($settings['DEB_DEFAULT_TRANSLATION'])) {
                define('DEB_DEFAULT_TRANSLATION', explode(',', $settings['DEB_DEFAULT_TRANSLATION']));
            } else {
                define('DEB_DEFAULT_TRANSLATION', array());
            }
        }

        if (!defined('DEB_INVALID_SIGNATURE')) {
            if (!empty($settings['DEB_INVALID_SIGNATURE'])) {
                define('DEB_INVALID_SIGNATURE', $settings['DEB_INVALID_SIGNATURE']);
            } else {
                define('DEB_INVALID_SIGNATURE', 'error');
            }
        }

        //  GPG signing key
        if (!defined('GPG_SIGNING_KEYID')) {
            if (!empty($settings['GPG_SIGNING_KEYID'])) {
                define('GPG_SIGNING_KEYID', $settings['GPG_SIGNING_KEYID']);
            } else {
                /**
                 *  Define a default key ID
                 */
                define('GPG_SIGNING_KEYID', '');
                $__LOAD_SETTINGS_MESSAGES[] = "<code>GPG key Id</code> setting (GPG signing key) is not defined.";
            }
        }

        // Statistics and metrics
        if (!defined('STATS_ENABLED')) {
            if (!empty($settings['STATS_ENABLED'])) {
                define('STATS_ENABLED', $settings['STATS_ENABLED']);
            } else {
                define('STATS_ENABLED', '');
                $__LOAD_SETTINGS_MESSAGES[] = "<code>ENABLE REPOSITORIES STATISTICS</code> setting is not defined.";
            }
        }

        if (STATS_ENABLED == 'true') {
            if (!defined('STATS_LOG_PATH')) {
                define('STATS_LOG_PATH', '/var/log/nginx/repomanager_access.log');
            }

            /**
             *  Test if log file is readable
             */
            if (!is_readable(STATS_LOG_PATH)) {
                ++$__LOAD_SETTINGS_ERROR;
                $__LOAD_SETTINGS_MESSAGES[] = "Access log file used for statistics is not readable: <code>" . STATS_LOG_PATH . "</code>";
            }
        }

        /**
         *  Scheduled tasks settings
         */
        if (!defined('SCHEDULED_TASKS_REMINDERS')) {
            if (!empty($settings['SCHEDULED_TASKS_REMINDERS'])) {
                define('SCHEDULED_TASKS_REMINDERS', $settings['SCHEDULED_TASKS_REMINDERS']);
            } else {
                define('SCHEDULED_TASKS_REMINDERS', 'false');
            }
        }

        /**
         *  Hosts and profiles settings
         */
        if (!defined('MANAGE_HOSTS')) {
            if (!empty($settings['MANAGE_HOSTS'])) {
                define('MANAGE_HOSTS', $settings['MANAGE_HOSTS']);
            } else {
                define('MANAGE_HOSTS', '');
                $__LOAD_SETTINGS_MESSAGES[] = "<code>MANAGE HOSTS</code> setting is not defined.";
            }
        }

        /**
         *  CVE settings
         */
        if (!defined('CVE_IMPORT')) {
            if (!empty($settings['CVE_IMPORT'])) {
                define('CVE_IMPORT', $settings['CVE_IMPORT']);
            } else {
                define('CVE_IMPORT', 'false');
            }
        }

        if (!defined('CVE_IMPORT_TIME')) {
            if (!empty($settings['CVE_IMPORT_TIME'])) {
                define('CVE_IMPORT_TIME', $settings['CVE_IMPORT_TIME']);
            } else {
                define('CVE_IMPORT_TIME', '00:00');
            }
        }

        if (!defined('CVE_SCAN_HOSTS')) {
            if (!empty($settings['CVE_SCAN_HOSTS'])) {
                define('CVE_SCAN_HOSTS', $settings['CVE_SCAN_HOSTS']);
            } else {
                define('CVE_SCAN_HOSTS', 'false');
            }
        }

        /**
         * OIDC settings
         */
        if (!defined('OIDC_ENABLED')) {
            if (isset($app_yaml['oidc']['enabled'])) {
                define('OIDC_ENABLED', $app_yaml['oidc']['enabled']);
            } elseif (isset($settings['OIDC_ENABLED'])) {
                define('OIDC_ENABLED', $settings['OIDC_ENABLED']);
            } else {
                define('OIDC_ENABLED', 'false');
            }
        }

        if (!defined('SSO_OIDC_ONLY')) {
            if (isset($app_yaml['oidc']['oidc_only'])) {
                define('SSO_OIDC_ONLY', $app_yaml['oidc']['oidc_only']);
            } elseif (isset($settings['SSO_OIDC_ONLY'])) {
                define('SSO_OIDC_ONLY', $settings['SSO_OIDC_ONLY']);
            } else {
                define('SSO_OIDC_ONLY', 'false');
            }
        }

        if (OIDC_ENABLED == 'true') {
            if (!defined('OIDC_PROVIDER_URL')) {
                if (isset($app_yaml['oidc']['provider_url'])) {
                    define('OIDC_PROVIDER_URL', $app_yaml['oidc']['provider_url']);
                } elseif (isset($settings['OIDC_PROVIDER_URL'])) {
                    define('OIDC_PROVIDER_URL', $settings['OIDC_PROVIDER_URL']);
                } else {
                    define('OIDC_PROVIDER_URL', '');
                }
            }

            if (!defined('OIDC_AUTHORIZATION_ENDPOINT')) {
                if (isset($app_yaml['oidc']['authorization_endpoint'])) {
                    define('OIDC_AUTHORIZATION_ENDPOINT', $app_yaml['oidc']['authorization_endpoint']);
                } elseif (isset($settings['OIDC_AUTHORIZATION_ENDPOINT'])) {
                    define('OIDC_AUTHORIZATION_ENDPOINT', $settings['OIDC_AUTHORIZATION_ENDPOINT']);
                } else {
                    define('OIDC_AUTHORIZATION_ENDPOINT', '');
                }
            }

            if (!defined('OIDC_TOKEN_ENDPOINT')) {
                if (isset($app_yaml['oidc']['token_endpoint'])) {
                    define('OIDC_TOKEN_ENDPOINT', $app_yaml['oidc']['token_endpoint']);
                } elseif (isset($settings['OIDC_TOKEN_ENDPOINT'])) {
                    define('OIDC_TOKEN_ENDPOINT', $settings['OIDC_TOKEN_ENDPOINT']);
                } else {
                    define('OIDC_TOKEN_ENDPOINT', '');
                }
            }

            if (!defined('OIDC_USERINFO_ENDPOINT')) {
                if (isset($app_yaml['oidc']['userinfo_endpoint'])) {
                    define('OIDC_USERINFO_ENDPOINT', $app_yaml['oidc']['userinfo_endpoint']);
                } elseif (isset($settings['OIDC_USERINFO_ENDPOINT'])) {
                    define('OIDC_USERINFO_ENDPOINT', $settings['OIDC_USERINFO_ENDPOINT']);
                } else {
                    define('OIDC_USERINFO_ENDPOINT', '');
                }
            }

            if (!defined('OIDC_SCOPES')) {
                if (isset($app_yaml['oidc']['scopes'])) {
                    define('OIDC_SCOPES', $app_yaml['oidc']['scopes']);
                } elseif (isset($settings['OIDC_SCOPES'])) {
                    define('OIDC_SCOPES', $settings['OIDC_SCOPES']);
                } else {
                    define('OIDC_SCOPES', 'groups,email,profile');
                }
            }

            if (!defined('OIDC_CLIENT_ID')) {
                if (isset($app_yaml['oidc']['client_id'])) {
                    define('OIDC_CLIENT_ID', $app_yaml['oidc']['client_id']);
                } elseif (isset($settings['OIDC_CLIENT_ID'])) {
                    define('OIDC_CLIENT_ID', $settings['OIDC_CLIENT_ID']);
                } else {
                    define('OIDC_CLIENT_ID', '');
                }
            }

            if (!defined('OIDC_CLIENT_SECRET')) {
                if (isset($app_yaml['oidc']['client_secret'])) {
                    define('OIDC_CLIENT_SECRET', $app_yaml['oidc']['client_secret']);
                } elseif (isset($settings['OIDC_CLIENT_SECRET'])) {
                    define('OIDC_CLIENT_SECRET', $settings['OIDC_CLIENT_SECRET']);
                } else {
                    define('OIDC_CLIENT_SECRET', '');
                }
            }

            if (!defined('OIDC_USERNAME')) {
                if (isset($app_yaml['oidc']['username'])) {
                    define('OIDC_USERNAME', $app_yaml['oidc']['username']);
                } elseif (isset($settings['OIDC_USERNAME'])) {
                    define('OIDC_USERNAME', $settings['OIDC_USERNAME']);
                } else {
                    define('OIDC_USERNAME', 'preferred_username');
                }
            }

            if (!defined('OIDC_FIRST_NAME')) {
                if (isset($app_yaml['oidc']['first_name'])) {
                    define('OIDC_FIRST_NAME', $app_yaml['oidc']['first_name']);
                } elseif (isset($settings['OIDC_FIRST_NAME'])) {
                    define('OIDC_FIRST_NAME', $settings['OIDC_FIRST_NAME']);
                } else {
                    define('OIDC_FIRST_NAME', 'given_name');
                }
            }

            if (!defined('OIDC_LAST_NAME')) {
                if (isset($app_yaml['oidc']['last_name'])) {
                    define('OIDC_LAST_NAME', $app_yaml['oidc']['last_name']);
                } elseif (isset($settings['OIDC_LAST_NAME'])) {
                    define('OIDC_LAST_NAME', $settings['OIDC_LAST_NAME']);
                } else {
                    define('OIDC_LAST_NAME', 'family_name');
                }
            }

            if (!defined('OIDC_EMAIL')) {
                if (isset($app_yaml['oidc']['email'])) {
                    define('OIDC_EMAIL', $app_yaml['oidc']['email']);
                } elseif (isset($settings['OIDC_EMAIL'])) {
                    define('OIDC_EMAIL', $settings['OIDC_EMAIL']);
                } else {
                    define('OIDC_EMAIL', 'email');
                }
            }

            if (!defined('OIDC_GROUPS')) {
                if (isset($app_yaml['oidc']['groups'])) {
                    define('OIDC_GROUPS', $app_yaml['oidc']['groups']);
                } elseif (isset($settings['OIDC_GROUPS'])) {
                    define('OIDC_GROUPS', $settings['OIDC_GROUPS']);
                } else {
                    define('OIDC_GROUPS', 'groups');
                }
            }

            if (!defined('OIDC_GROUP_ADMINISTRATOR')) {
                if (isset($app_yaml['oidc']['group_administrator'])) {
                    define('OIDC_GROUP_ADMINISTRATOR', $app_yaml['oidc']['group_administrator']);
                } elseif (isset($settings['OIDC_GROUP_ADMINISTRATOR'])) {
                    define('OIDC_GROUP_ADMINISTRATOR', $settings['OIDC_GROUP_ADMINISTRATOR']);
                } else {
                    define('OIDC_GROUP_ADMINISTRATOR', 'administrator');
                }
            }

            if (!defined('OIDC_GROUP_SUPER_ADMINISTRATOR')) {
                if (isset($app_yaml['oidc']['group_super_administrator'])) {
                    define('OIDC_GROUP_SUPER_ADMINISTRATOR', $app_yaml['oidc']['group_super_administrator']);
                } elseif (isset($settings['OIDC_GROUP_SUPER_ADMINISTRATOR'])) {
                    define('OIDC_GROUP_SUPER_ADMINISTRATOR', $settings['OIDC_GROUP_SUPER_ADMINISTRATOR']);
                } else {
                    define('OIDC_GROUP_SUPER_ADMINISTRATOR', 'super-administrator');
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
