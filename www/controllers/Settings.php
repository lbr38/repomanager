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
    public function get()
    {
        /**
         *  Get all settings
         */
        return $this->model->get();
    }

    /**
     *  Apply settings
     */
    public function apply(array $sendSettings)
    {
        $settingsToApply = array();

        /**
         *  General settings
         */
        if (!empty($sendSettings['reposDir'])) {
            $reposDir = rtrim(Common::validateData($sendSettings['reposDir']), '/');

            /**
             *  Le chemin ne doit comporter que des lettres, des chiffres, des tirets et des slashs
             */
            if (!Common::isAlphanumDash($reposDir, array('/'))) {
                throw new Exception('Invalid directory value for ' . $reposDir);
            }

            $settingsToApply['REPOS_DIR'] = $reposDir;
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
        }

        if (isset($sendSettings['repoConfFilesPrefix'])) {
            $repoConfFilesPrefix = Common::validateData($sendSettings['repoConfFilesPrefix']);

            if (!Common::isAlphanumDash($repoConfFilesPrefix, array('-'))) {
                throw new Exception('Invalid prefix value for ' . $repoConfFilesPrefix);
            }

            $settingsToApply['REPO_CONF_FILES_PREFIX'] = $repoConfFilesPrefix;
        }

        if (!empty($sendSettings['timezone'])) {
            $timezone = Common::validateData($sendSettings['timezone']);

            if (!Common::isAlphanumDash($timezone, array('/'))) {
                throw new Exception('Invalid timezone value for ' . $timezone);
            }

            $settingsToApply['TIMEZONE'] = $timezone;
        }

        /**
         *  Web settings
         */
        if (!empty($sendSettings['wwwDir'])) {
            $wwwDir = rtrim(Common::validateData($sendSettings['wwwDir']), '/');

            /**
             *  Le chemin ne doit comporter que des lettres, des chiffres, des tirets et des slashs
             */
            if (!Common::isAlphanumDash($wwwDir, array('/'))) {
                throw new Exception('Invalid directory value for ' . $wwwDir);
            }

            $settingsToApply['WWW_DIR'] = $wwwDir;
        }

        /**
         *  RPM repo settings
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

        // hardcoded for now
        $settingsToApply['RPM_SIGN_METHOD'] = 'rpmsign';

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

        if (!empty($sendSettings['rpmIncludeSource'])) {
            if ($sendSettings['rpmIncludeSource'] == "true") {
                $settingsToApply['RPM_INCLUDE_SOURCE'] = 'true';
            } else {
                $settingsToApply['RPM_INCLUDE_SOURCE'] = 'false';
            }
        }

        /**
         *  DEB repo settings
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

        if (!empty($sendSettings['debIncludeSource'])) {
            if ($sendSettings['debIncludeSource'] == "true") {
                $settingsToApply['DEB_INCLUDE_SOURCE'] = 'true';
            } else {
                $settingsToApply['DEB_INCLUDE_SOURCE'] = 'false';
            }
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

        /**
         *  GPG settings
         */
        if (!empty($sendSettings['gpgKeyID'])) {
            $gpgKeyID = Common::validateData($sendSettings['gpgKeyID']);

            if (!Common::isAlphanumDash($gpgKeyID, array('@', '.'))) {
                throw new Exception('Invalid GPG key ID value for ' . $gpgKeyID);
            }

            $settingsToApply['GPG_SIGNING_KEYID'] = $gpgKeyID;
        }

        /**
         *  Plans settings
         */
        if (!empty($sendSettings['plansEnable'])) {
            if ($sendSettings['plansEnable'] == "true") {
                $settingsToApply['PLANS_ENABLED'] = 'true';
            } else {
                $settingsToApply['PLANS_ENABLED'] = 'false';
            }
        }

        if (!empty($sendSettings['plansRemindersEnable'])) {
            if ($sendSettings['plansRemindersEnable'] == "true") {
                $settingsToApply['PLANS_REMINDERS_ENABLED'] = 'true';
            } else {
                $settingsToApply['PLANS_REMINDERS_ENABLED'] = 'false';
            }
        }

        if (!empty($sendSettings['plansUpdateRepo'])) {
            if ($sendSettings['plansUpdateRepo'] == "true") {
                $settingsToApply['PLANS_UPDATE_REPO'] = 'true';
            } else {
                $settingsToApply['PLANS_UPDATE_REPO'] = 'false';
            }
        }

        if (!empty($sendSettings['plansCleanRepo'])) {
            if ($sendSettings['plansCleanRepo'] == "true") {
                $settingsToApply['PLANS_CLEAN_REPOS'] = 'true';
            } else {
                $settingsToApply['PLANS_CLEAN_REPOS'] = 'false';
            }
        }

        if (isset($sendSettings['retention'])) {
            $retention = Common::validateData($sendSettings['retention']);

            if (!is_numeric($retention) or $retention < 0) {
                throw new Exception('Invalid retention value for ' . $retention);
            }

            $settingsToApply['RETENTION'] = $retention;
        }

        /**
         *  Stats settings
         */
        if (!empty($sendSettings['statsEnable'])) {
            if ($sendSettings['statsEnable'] == "true") {
                $settingsToApply['STATS_ENABLED'] = 'true';
            } else {
                $settingsToApply['STATS_ENABLED'] = 'false';
            }
        }

        // hardcoded for now
        $settingsToApply['STATS_LOG_PATH'] = '/var/log/nginx/repomanager_access.log';

        /**
         *  Hosts settings
         */
        if (!empty($sendSettings['manageHosts'])) {
            if ($sendSettings['manageHosts'] == "true") {
                $settingsToApply['MANAGE_HOSTS'] = 'true';
            } else {
                $settingsToApply['MANAGE_HOSTS'] = 'false';
            }
        }

        if (!empty($sendSettings['manageProfiles'])) {
            if ($sendSettings['manageProfiles'] == "true") {
                $settingsToApply['MANAGE_PROFILES'] = 'true';
            } else {
                $settingsToApply['MANAGE_PROFILES'] = 'false';
            }
        }

        /**
         *  Write settings to database
         */
        $this->model->apply($settingsToApply);

        /**
         *  Clean repos list cache
         */
        Common::clearCache();
    }
}
