<?php

namespace Controllers;

use Exception;

class Settings
{
    /**
     *  Apply settings
     */
    public function apply(array $settings)
    {
        /**
         *  Getting actual configuration from config file
         */
        $repomanager_conf_array = parse_ini_file(REPOMANAGER_CONF, true);

        if (!empty($settings['reposDir'])) {
            $reposDir = Common::validateData($settings['reposDir']);

            /**
             *  Le chemin ne doit comporter que des lettres, des chiffres, des tirets et des slashs
             */
            if (Common::isAlphanumDash($reposDir, array('/'))) {
                /**
                 *  Suppression du dernier slash si il y en a un
                 */
                $repomanager_conf_array['PATHS']['REPOS_DIR'] = rtrim($reposDir, '/');
            }
        }

        if (!empty($settings['emailDest'])) {
            $emailDest = Common::validateData($settings['emailDest']);

            if (Common::isAlphanumDash($emailDest, array('@', '.'))) {
                $repomanager_conf_array['CONFIGURATION']['EMAIL_DEST'] = trim($emailDest);
            }
        }

        if (!empty($settings['manageHosts']) and $settings['manageHosts'] == "true") {
            $repomanager_conf_array['CONFIGURATION']['MANAGE_HOSTS'] = 'true';
        } else {
            $repomanager_conf_array['CONFIGURATION']['MANAGE_HOSTS'] = 'false';
        }

        if (!empty($settings['manageProfiles']) and $settings['manageProfiles'] == "true") {
            $repomanager_conf_array['CONFIGURATION']['MANAGE_PROFILES'] = 'true';
        } else {
            $repomanager_conf_array['CONFIGURATION']['MANAGE_PROFILES'] = 'false';
        }

        if (isset($settings['repoConfPrefix'])) {
            $repomanager_conf_array['CONFIGURATION']['REPO_CONF_FILES_PREFIX'] = Common::validateData($settings['repoConfPrefix']);
        }

        if (!empty($settings['rpmRepo']) and $settings['rpmRepo'] == 'true') {
            $repomanager_conf_array['RPM']['RPM_REPO'] = 'true';
        } else {
            $repomanager_conf_array['RPM']['RPM_REPO'] = 'false';
        }

        if (!empty($settings['rpmSignPackages']) and $settings['rpmSignPackages'] == "true") {
            $repomanager_conf_array['RPM']['RPM_SIGN_PACKAGES'] = 'true';
        } else {
            $repomanager_conf_array['RPM']['RPM_SIGN_PACKAGES'] = 'false';
        }

        if (!empty($settings['releasever']) and is_numeric($settings['releasever'])) {
            $repomanager_conf_array['RPM']['RELEASEVER'] = $settings['releasever'];
        }

        if (!empty($settings['rpmDefaultArchitecture'])) {
            /**
             *  Convert array to a string with values separated by a comma
             */
            $rpmDefaultArchitecture = Common::validateData(implode(',', $settings['rpmDefaultArchitecture']));
        } else {
            $rpmDefaultArchitecture = '';
        }
        if (Common::isAlphanumDash($rpmDefaultArchitecture, array(','))) {
            $repomanager_conf_array['RPM']['RPM_DEFAULT_ARCH'] = trim($rpmDefaultArchitecture);
        }

        if (!empty($settings['rpmIncludeSource']) and $settings['rpmIncludeSource'] == "true") {
            $repomanager_conf_array['RPM']['RPM_INCLUDE_SOURCE'] = 'true';
        } else {
            $repomanager_conf_array['RPM']['RPM_INCLUDE_SOURCE'] = 'false';
        }

        if (!empty($settings['debRepo']) and $settings['debRepo'] == 'true') {
            $repomanager_conf_array['DEB']['DEB_REPO'] = 'true';
        } else {
            $repomanager_conf_array['DEB']['DEB_REPO'] = 'false';
        }

        if (!empty($settings['debSignRepo']) and $settings['debSignRepo'] == "true") {
            $repomanager_conf_array['DEB']['DEB_SIGN_REPO'] = 'true';
        } else {
            $repomanager_conf_array['DEB']['DEB_SIGN_REPO'] = 'false';
        }

        if (!empty($settings['debDefaultArchitecture'])) {
            /**
             *  Convert array to a string with values separated by a comma
             */
            $debDefaultArchitecture = Common::validateData(implode(',', $settings['debDefaultArchitecture']));
        } else {
            $debDefaultArchitecture = '';
        }
        if (Common::isAlphanumDash($debDefaultArchitecture, array(','))) {
            $repomanager_conf_array['DEB']['DEB_DEFAULT_ARCH'] = trim($debDefaultArchitecture);
        }

        if (!empty($settings['debIncludeSource']) and $settings['debIncludeSource'] == "true") {
            $repomanager_conf_array['DEB']['DEB_INCLUDE_SOURCE'] = 'true';
        } else {
            $repomanager_conf_array['DEB']['DEB_INCLUDE_SOURCE'] = 'false';
        }

        if (!empty($settings['debDefaultTranslation'])) {
            /**
             *  Convert array to a string with values separated by a comma
             */
            $debDefaultTranslation = Common::validateData(implode(',', $settings['debDefaultTranslation']));
        } else {
            $debDefaultTranslation = '';
        }
        if (Common::isAlphanumDash($debDefaultTranslation, array(','))) {
            $repomanager_conf_array['DEB']['DEB_DEFAULT_TRANSLATION'] = trim($debDefaultTranslation);
        }

        if (!empty($settings['updateAuto']) and $settings['updateAuto'] == "true") {
            $repomanager_conf_array['UPDATE']['UPDATE_AUTO'] = 'true';
        } else {
            $repomanager_conf_array['UPDATE']['UPDATE_AUTO'] = 'false';
        }

        if (!empty($settings['updateBackup']) and $settings['updateBackup'] == "true") {
            $repomanager_conf_array['UPDATE']['UPDATE_BACKUP_ENABLED'] = 'true';
        } else {
            $repomanager_conf_array['UPDATE']['UPDATE_BACKUP_ENABLED'] = 'false';
        }

        if (!empty($settings['updateBackupDir'])) {
            $updateBackupDir = Common::validateData($settings['updateBackupDir']);

            if (Common::isAlphanumDash($updateBackupDir, array('/'))) {
                $repomanager_conf_array['UPDATE']['BACKUP_DIR'] = rtrim($updateBackupDir, '/');
            }
        }

        if (!empty($settings['updateBranch'])) {
            $updateBranch = Common::validateData($settings['updateBranch']);

            if (Common::isAlphanum($updateBranch, array('/'))) {
                $repomanager_conf_array['UPDATE']['UPDATE_BRANCH'] = $updateBranch;
            }
        }

        if (!empty($settings['wwwUser'])) {
            $wwwUser = Common::validateData($settings['wwwUser']);

            if (Common::isAlphanumDash($wwwUser)) {
                $repomanager_conf_array['WWW']['WWW_USER'] = trim($wwwUser);
            }
        }

        if (!empty($settings['wwwHostname'])) {
            $hostname = Common::validateData($settings['wwwHostname']);
            $repomanager_conf_array['WWW']['WWW_HOSTNAME'] = "$hostname";
        }

        if (!empty($settings['wwwReposDirUrl'])) {
            $wwwReposDirUrl = Common::validateData($settings['wwwReposDirUrl']);

            if (Common::isAlphanumDash($wwwReposDirUrl, array('.', '/', ':'))) {
                $repomanager_conf_array['WWW']['WWW_REPOS_DIR_URL'] = rtrim($wwwReposDirUrl, '/');
            }
        }

        if (!empty($settings['gpgKeyID'])) {
            $gpgKeyID = Common::validateData($settings['gpgKeyID']);

            if (Common::isAlphanumDash($gpgKeyID, array('@', '.'))) {
                $repomanager_conf_array['GPG']['GPG_SIGNING_KEYID'] = trim($gpgKeyID);
            }
        }

        if (!empty($settings['automatisationEnable']) and $settings['automatisationEnable'] == "true") {
            $repomanager_conf_array['PLANS']['PLANS_ENABLED'] = 'true';
        } else {
            $repomanager_conf_array['PLANS']['PLANS_ENABLED'] = 'false';
        }

        if (!empty($settings['allowAutoUpdateRepos']) and $settings['allowAutoUpdateRepos'] == "true") {
            $repomanager_conf_array['PLANS']['ALLOW_AUTOUPDATE_REPOS'] = 'true';
        } else {
            $repomanager_conf_array['PLANS']['ALLOW_AUTOUPDATE_REPOS'] = 'false';
        }
        /**
         *  Autoriser ou non le changement d'environnement par l'automatisation
         */
        // if (!empty($settings['allowAutoUpdateReposEnv']) and Common::validateData($settings['allowAutoUpdateReposEnv']) == "true") {
        //     $repomanager_conf_array['PLANS']['ALLOW_AUTOUPDATE_REPOS_ENV'] = 'true';
        // } else {
        //     $repomanager_conf_array['PLANS']['ALLOW_AUTOUPDATE_REPOS_ENV'] = 'false';
        // }

        if (!empty($settings['allowAutoDeleteArchivedRepos']) and $settings['allowAutoDeleteArchivedRepos'] == "true") {
            $repomanager_conf_array['PLANS']['ALLOW_AUTODELETE_ARCHIVED_REPOS'] = 'true';
        } else {
            $repomanager_conf_array['PLANS']['ALLOW_AUTODELETE_ARCHIVED_REPOS'] = 'false';
        }

        if (isset($settings['retention'])) {
            $retention = Common::validateData($settings['retention']);

            if (is_numeric($retention)) {
                $repomanager_conf_array['PLANS']['RETENTION'] = $retention;
            }
        }

        if (!empty($settings['cronSendReminders']) and $settings['cronSendReminders'] == "true") {
            $repomanager_conf_array['PLANS']['PLAN_REMINDERS_ENABLED'] = 'true';
        } else {
            $repomanager_conf_array['PLANS']['PLAN_REMINDERS_ENABLED'] = 'false';
        }

        if (!empty($settings['cronStatsEnable']) and $settings['cronStatsEnable'] == "true") {
            $repomanager_conf_array['STATS']['STATS_ENABLED'] = 'true';
        } else {
            $repomanager_conf_array['STATS']['STATS_ENABLED'] = 'false';
        }

        if (!empty($settings['statsLogPath'])) {
            $statsLogPath = Common::validateData($settings['statsLogPath']);
            if (Common::isAlphanumDash($statsLogPath, array('.', '/'))) {
                $repomanager_conf_array['STATS']['STATS_LOG_PATH'] = $statsLogPath;
            }
            /**
             *  On stoppe le process stats-log-parser.sh actuel, il sera relancé au rechargement de la page
             */
            Common::killStatsLogParser();
        }
        /**
         *  Write configuration to file
         */
        Common::writeToIni(REPOMANAGER_CONF, $repomanager_conf_array);

        /**
         *  Clean repos list cache
         */
        Common::clearCache();
    }
}
