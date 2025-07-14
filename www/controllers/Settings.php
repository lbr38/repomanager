<?php

namespace Controllers;

use Exception;

class Settings
{
    private $model;

    public function __construct()
    {
        $this->model = new \Models\Settings();
    }

    /**
     *  Get settings
     */
    public function get() : array
    {
        return $this->model->get();
    }

    /**
     *  Apply settings
     */
    public function apply(array $sendSettings) : void
    {
        if (!IS_ADMIN) {
            throw new Exception('You are not allowed to perform this action');
        }

        $settingsToApply = array();

        /**
         *  Main configuration / Global settings
         */
        if (!empty($sendSettings['timezone'])) {
            $timezone = Common::validateData($sendSettings['timezone']);

            if (!Common::isAlphanumDash($timezone, array('/'))) {
                throw new Exception('Invalid timezone value for ' . $timezone);
            }

            $settingsToApply['TIMEZONE'] = $timezone;
        }

        if (!empty($sendSettings['emailRecipient'])) {
            foreach ($sendSettings['emailRecipient'] as $email) {
                $emailRecipient = Common::validateData($email);

                if (!Common::isAlphanumDash($emailRecipient, array('@', '.'))) {
                    throw new Exception('Invalid email address format for ' . $emailRecipient);
                }
                if (!Common::validateMail($emailRecipient)) {
                    throw new Exception('Invalid email address format for ' . $emailRecipient);
                }
            }

            $settingsToApply['EMAIL_RECIPIENT'] = implode(',', $sendSettings['emailRecipient']);
        } else {
            $settingsToApply['EMAIL_RECIPIENT'] = '';
        }

        if (!empty($sendSettings['session-timeout'])) {
            if (is_numeric($sendSettings['session-timeout']) and $sendSettings['session-timeout'] >= 15) {
                $settingsToApply['SESSION_TIMEOUT'] = $sendSettings['session-timeout'];
            }
        }

        if (!empty($sendSettings['proxy'])) {
            /**
             *  Check that URL is valid
             */
            if (!preg_match('#^https?://#', $sendSettings['proxy'])) {
                throw new Exception('Proxy URL must start with http(s)://');
            }

            $settingsToApply['PROXY'] = \Controllers\Common::validateData($sendSettings['proxy']);
        } else {
            $settingsToApply['PROXY'] = '';
        }

        if (!empty($sendSettings['task-queuing'])) {
            if ($sendSettings['task-queuing'] == 'true') {
                $settingsToApply['TASK_QUEUING'] = 'true';
            } else {
                $settingsToApply['TASK_QUEUING'] = 'false';
            }
        }

        if (!empty($sendSettings['task-queuing-max-simultaneous']) and is_numeric($sendSettings['task-queuing-max-simultaneous']) and $sendSettings['task-queuing-max-simultaneous'] > 0) {
            $settingsToApply['TASK_QUEUING_MAX_SIMULTANEOUS'] = \Controllers\Common::validateData($sendSettings['task-queuing-max-simultaneous']);
        }

        if (!empty($sendSettings['task-execution-memory-limit']) and is_numeric($sendSettings['task-execution-memory-limit']) and $sendSettings['task-execution-memory-limit'] > 2) {
            $settingsToApply['TASK_EXECUTION_MEMORY_LIMIT'] = \Controllers\Common::validateData($sendSettings['task-execution-memory-limit']);
        }

        if (!empty($sendSettings['task-clean-older-than']) and is_numeric($sendSettings['task-clean-older-than']) and $sendSettings['task-clean-older-than'] >= 1) {
            $settingsToApply['TASK_CLEAN_OLDER_THAN'] = \Controllers\Common::validateData($sendSettings['task-clean-older-than']);
        }

        /**
         *  Repositories / Mirroring settings
         */
        if (isset($sendSettings['retention'])) {
            $retention = Common::validateData($sendSettings['retention']);

            if (!is_numeric($retention) or $retention < 0) {
                throw new Exception('Invalid retention value');
            }

            $settingsToApply['RETENTION'] = $retention;
        }

        if (isset($sendSettings['repoConfFilesPrefix'])) {
            $repoConfFilesPrefix = Common::validateData($sendSettings['repoConfFilesPrefix']);

            if (!Common::isAlphanumDash($repoConfFilesPrefix, array('-'))) {
                throw new Exception('Invalid prefix value for ' . $repoConfFilesPrefix);
            }

            $settingsToApply['REPO_CONF_FILES_PREFIX'] = $repoConfFilesPrefix;
        }

        if (!empty($sendSettings['mirrorPackageDownloadTimeout'])) {
            if (is_numeric($sendSettings['mirrorPackageDownloadTimeout']) and $sendSettings['mirrorPackageDownloadTimeout'] > 0) {
                $settingsToApply['MIRRORING_PACKAGE_DOWNLOAD_TIMEOUT'] = $sendSettings['mirrorPackageDownloadTimeout'];
            } else {
                $settingsToApply['MIRRORING_PACKAGE_DOWNLOAD_TIMEOUT'] = 300;
            }
        }

        /**
         *  Repositories / RPM
         */
        if (!empty($sendSettings['rpmRepo'])) {
            if ($sendSettings['rpmRepo'] == 'true') {
                $settingsToApply['RPM_REPO'] = 'true';
            } else {
                $settingsToApply['RPM_REPO'] = 'false';
            }
        }

        if (!empty($sendSettings['rpmSignPackages'])) {
            if ($sendSettings['rpmSignPackages'] == "true") {
                $settingsToApply['RPM_SIGN_PACKAGES'] = 'true';
            } else {
                $settingsToApply['RPM_SIGN_PACKAGES'] = 'false';
            }
        }

        if (!empty($sendSettings['releasever']) and is_numeric($sendSettings['releasever'])) {
            $settingsToApply['RELEASEVER'] = $sendSettings['releasever'];
        }

        if (!empty($sendSettings['rpmDefaultArch'])) {
            /**
             *  Convert array to a string with values separated by a comma
             */
            $rpmDefaultArch = Common::validateData(implode(',', $sendSettings['rpmDefaultArch']));

            if (!Common::isAlphanumDash($rpmDefaultArch, array(','))) {
                throw new Exception('Invalid architecture value for ' . $rpmDefaultArch);
            }

            $settingsToApply['RPM_DEFAULT_ARCH'] = $rpmDefaultArch;
        }

        if (!empty($sendSettings['rpm-missing-signature'])) {
            if (in_array($sendSettings['rpm-missing-signature'], ['download', 'ignore', 'error'])) {
                $settingsToApply['RPM_MISSING_SIGNATURE'] = $sendSettings['rpm-missing-signature'];
            } else {
                $settingsToApply['RPM_MISSING_SIGNATURE'] = 'error';
            }
        }

        if (!empty($sendSettings['rpm-invalid-signature'])) {
            if (in_array($sendSettings['rpm-invalid-signature'], ['download', 'ignore', 'error'])) {
                $settingsToApply['RPM_INVALID_SIGNATURE'] = $sendSettings['rpm-invalid-signature'];
            } else {
                $settingsToApply['RPM_INVALID_SIGNATURE'] = 'error';
            }
        }

        if (!empty($sendSettings['rpm-signature-fail'])) {
            if (in_array($sendSettings['rpm-signature-fail'], ['keep', 'ignore', 'error'])) {
                $settingsToApply['RPM_SIGNATURE_FAIL'] = $sendSettings['rpm-signature-fail'];
            } else {
                $settingsToApply['RPM_SIGNATURE_FAIL'] = 'error';
            }
        }

        /**
         *  Repositories / DEB
         */
        if (!empty($sendSettings['debRepo'])) {
            if ($sendSettings['debRepo'] == 'true') {
                $settingsToApply['DEB_REPO'] = 'true';
            } else {
                $settingsToApply['DEB_REPO'] = 'false';
            }
        }

        if (!empty($sendSettings['debSignRepo'])) {
            if ($sendSettings['debSignRepo'] == "true") {
                $settingsToApply['DEB_SIGN_REPO'] = 'true';
            } else {
                $settingsToApply['DEB_SIGN_REPO'] = 'false';
            }
        }

        if (!empty($sendSettings['debDefaultArch'])) {
            /**
             *  Convert array to a string with values separated by a comma
             */
            $debDefaultArch = Common::validateData(implode(',', $sendSettings['debDefaultArch']));

            if (!Common::isAlphanumDash($debDefaultArch, array(','))) {
                throw new Exception('Invalid architecture value for ' . $debDefaultArch);
            }

            $settingsToApply['DEB_DEFAULT_ARCH'] = $debDefaultArch;
        }

        if (!empty($sendSettings['debDefaultTranslation'])) {
            /**
             *  Convert array to a string with values separated by a comma
             */
            $debDefaultTranslation = Common::validateData(implode(',', $sendSettings['debDefaultTranslation']));

            if (!Common::isAlphanum($debDefaultTranslation, array(','))) {
                throw new Exception('Invalid translation value for ' . $debDefaultTranslation);
            }

            $settingsToApply['DEB_DEFAULT_TRANSLATION'] = $debDefaultTranslation;
        }

        if (!empty($sendSettings['deb-allow-empty-repo'])) {
            if ($sendSettings['deb-allow-empty-repo'] == 'true') {
                $settingsToApply['DEB_ALLOW_EMPTY_REPO'] = 'true';
            } else {
                $settingsToApply['DEB_ALLOW_EMPTY_REPO'] = 'false';
            }
        }

        if (!empty($sendSettings['deb-invalid-signature'])) {
            if (in_array($sendSettings['deb-invalid-signature'], ['ignore', 'error'])) {
                $settingsToApply['DEB_INVALID_SIGNATURE'] = $sendSettings['deb-invalid-signature'];
            } else {
                $settingsToApply['DEB_INVALID_SIGNATURE'] = 'error';
            }
        }

        /**
         *  Repositories / GPG signing key
         */
        if (!empty($sendSettings['gpgKeyID'])) {
            $gpgKeyID = Common::validateData($sendSettings['gpgKeyID']);

            if (!Common::isAlphanumDash($gpgKeyID, array('@', '.'))) {
                throw new Exception('Invalid GPG key ID value for ' . $gpgKeyID);
            }

            $settingsToApply['GPG_SIGNING_KEYID'] = $gpgKeyID;
        }

        /**
         *  Repositories / Statistics
         */
        if (!empty($sendSettings['statsEnable'])) {
            if ($sendSettings['statsEnable'] == "true") {
                $settingsToApply['STATS_ENABLED'] = 'true';
            } else {
                $settingsToApply['STATS_ENABLED'] = 'false';
            }
        }

        /**
         *  Scheduled tasks
         */
        if (!empty($sendSettings['scheduled-tasks-reminders'])) {
            if ($sendSettings['scheduled-tasks-reminders'] == "true") {
                $settingsToApply['SCHEDULED_TASKS_REMINDERS'] = 'true';
            } else {
                $settingsToApply['SCHEDULED_TASKS_REMINDERS'] = 'false';
            }
        }

        /**
         *  Hosts & profiles
         */
        if (!empty($sendSettings['manageHosts'])) {
            if ($sendSettings['manageHosts'] == "true") {
                $settingsToApply['MANAGE_HOSTS'] = 'true';
            } else {
                $settingsToApply['MANAGE_HOSTS'] = 'false';
            }
        }

        /**
         *  CVE
         */
        if (!empty($sendSettings['cveImport'])) {
            if ($sendSettings['cveImport'] == 'true') {
                $settingsToApply['CVE_IMPORT'] = 'true';
            } else {
                $settingsToApply['CVE_IMPORT'] = 'false';
            }
        }

        if (!empty($sendSettings['cveImportTime'])) {
            $cveImportTime = Common::validateData($sendSettings['cveImportTime']);

            if (preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $cveImportTime)) {
                $settingsToApply['CVE_IMPORT_TIME'] = $cveImportTime;
            }
        }

        if (!empty($sendSettings['cveScanHosts'])) {
            if ($sendSettings['cveScanHosts'] == 'true') {
                $settingsToApply['CVE_SCAN_HOSTS'] = 'true';
            } else {
                $settingsToApply['CVE_SCAN_HOSTS'] = 'false';
            }
        }

        /**
         * OIDC
         */
        if (!empty($sendSettings['oidcEnable'])) {
            if ($sendSettings['oidcEnable'] == 'true') {
                $settingsToApply['OIDC_ENABLED'] = 'true';
            } else {
                $settingsToApply['OIDC_ENABLED'] = 'false';
            }
        }

        if (!empty($sendSettings['ssoOidcOnly'])) {
            if ($sendSettings['ssoOidcOnly'] == 'true') {
                $settingsToApply['SSO_OIDC_ONLY'] = 'true';
            } else {
                $settingsToApply['SSO_OIDC_ONLY'] = 'false';
            }
        }

        if (isset($sendSettings['oidcProviderUrl'])) {
            $ssoOidcOnly = Common::validateData($sendSettings['oidcProviderUrl']);
            $settingsToApply['OIDC_PROVIDER_URL'] = $ssoOidcOnly;
        }

        if (isset($sendSettings['oidcAuthorizationEndpoint'])) {
            $oidcAuthorizationEndpoint = Common::validateData($sendSettings['oidcAuthorizationEndpoint']);
            $settingsToApply['OIDC_AUTHORIZATION_ENDPOINT'] = $oidcAuthorizationEndpoint;
        }

        if (isset($sendSettings['oidcTokenEndpoint'])) {
            $oidcTokenEndpoint = Common::validateData($sendSettings['oidcTokenEndpoint']);
            $settingsToApply['OIDC_TOKEN_ENDPOINT'] = $oidcTokenEndpoint;
        }

        if (isset($sendSettings['oidcUserinfoEndpoint'])) {
            $oidcUserinfoEndpoint = Common::validateData($sendSettings['oidcUserinfoEndpoint']);
            $settingsToApply['OIDC_USERINFO_ENDPOINT'] = $oidcUserinfoEndpoint;
        }

        if (isset($sendSettings['oidcScopes'])) {
            $oidcScopes = Common::validateData($sendSettings['oidcScopes']);
            $settingsToApply['OIDC_SCOPES'] = $oidcScopes;
        }

        if (isset($sendSettings['oidcClientId'])) {
            $oidcClientId = Common::validateData($sendSettings['oidcClientId']);
            $settingsToApply['OIDC_CLIENT_ID'] = $oidcClientId;
        }

        if (isset($sendSettings['oidcClientSecret'])) {
            $oidcClientSecret = Common::validateData($sendSettings['oidcClientSecret']);
            $settingsToApply['OIDC_CLIENT_SECRET'] = $oidcClientSecret;
        }

        if (!empty($sendSettings['oidcUsername'])) {
            $oidcUsername = Common::validateData($sendSettings['oidcUsername']);
            $settingsToApply['OIDC_USERNAME'] = $oidcUsername;
        }

        if (!empty($sendSettings['oidcFirstName'])) {
            $oidcFirstName = Common::validateData($sendSettings['oidcFirstName']);
            $settingsToApply['OIDC_FIRST_NAME'] = $oidcFirstName;
        }

        if (!empty($sendSettings['oidcLastName'])) {
            $oidcLastName = Common::validateData($sendSettings['oidcLastName']);
            $settingsToApply['OIDC_LAST_NAME'] = $oidcLastName;
        }

        if (!empty($sendSettings['oidcEmail'])) {
            $oidcEmail = Common::validateData($sendSettings['oidcEmail']);
            $settingsToApply['OIDC_EMAIL'] = $oidcEmail;
        }

        if (!empty($sendSettings['oidcGroups'])) {
            $oidcGroups = Common::validateData($sendSettings['oidcGroups']);
            $settingsToApply['OIDC_GROUPS'] = $oidcGroups;
        }

        if (!empty($sendSettings['oidcGroupAdministrator'])) {
            $oidcGroupAdministrator = Common::validateData($sendSettings['oidcGroupAdministrator']);
            $settingsToApply['OIDC_GROUP_ADMINISTRATOR'] = $oidcGroupAdministrator;
        }

        if (!empty($sendSettings['oidcGroupSuperAdministrator'])) {
            $oidcGroupSuperAdministrator = Common::validateData($sendSettings['oidcGroupSuperAdministrator']);
            $settingsToApply['OIDC_GROUP_SUPER_ADMINISTRATOR'] = $oidcGroupSuperAdministrator;
        }

        if (!empty($sendSettings['oidcHttpProxy'])) {
            $oidcHttpProxy = Common::validateData($sendSettings['oidcHttpProxy']);

            if (!preg_match('#^https?://#', $oidcHttpProxy)) {
                throw new Exception('OIDC HTTP proxy URL must start with http(s)://');
            }

            $settingsToApply['OIDC_HTTP_PROXY'] = $oidcHttpProxy;
        } else {
            $settingsToApply['OIDC_HTTP_PROXY'] = '';
        }

        if (!empty($sendSettings['oidcCertPath'])) {
            $oidcCertPath = realpath(Common::validateData($sendSettings['oidcCertPath']));

            if (!file_exists($oidcCertPath)) {
                throw new Exception('OIDC certificate file does not exist');
            }

            // Certificate path must be inside the data directory to be valid
            if (!preg_match('#^' . DATA_DIR . '/#', $oidcCertPath)) {
                throw new Exception('OIDC certificate path is not valid');
            }

            $settingsToApply['OIDC_CERT_PATH'] = $oidcCertPath;
        } else {
            $settingsToApply['OIDC_CERT_PATH'] = '';
        }

        /**
         *  Write settings to database
         */
        $this->model->apply($settingsToApply);
    }

    /**
     *  Enable or disable debug mode
     */
    public function enableDebugMode(string $enable) : void
    {
        if (!IS_ADMIN) {
            throw new Exception('You are not allowed to perform this action');
        }

        if ($enable != 'true' and $enable != 'false') {
            throw new Exception('Invalid value for debug mode');
        }

        $this->model->enableDebugMode($enable);
    }
}
