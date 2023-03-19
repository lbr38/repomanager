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
        $__LOAD_SETTINGS_ERROR = 0;
        $__LOAD_SETTINGS_MESSAGES = array();

        /**
         *  Import settings from old config file if exists
         */
        if (file_exists(REPOMANAGER_CONF)) {
            try {
                $this->import(REPOMANAGER_CONF);
            } catch (Exception $e) {
                $__LOAD_SETTINGS_ERROR++;
                $__LOAD_SETTINGS_MESSAGES[] = $e->getMessage();
            }
        }

        /**
         *  Get all settings
         */
        $settings = $this->model->get();

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

        /**
         *  Paramètres généraux
         */
        if (!defined('REPOS_DIR')) {
            if (!empty($settings['REPOS_DIR'])) {
                define('REPOS_DIR', $settings['REPOS_DIR']);
                /**
                 *  On teste l'accès au répertoire renseigné
                 */
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

        if (!defined('UPDATE_AUTO')) {
            if (!empty($settings['UPDATE_AUTO'])) {
                define('UPDATE_AUTO', $settings['UPDATE_AUTO']);
            } else {
                define('UPDATE_AUTO', 'false');
            }
        }

        if (!defined('UPDATE_BRANCH')) {
            if (!empty($settings['UPDATE_BRANCH'])) {
                define('UPDATE_BRANCH', $settings['UPDATE_BRANCH']);
            } else {
                define('UPDATE_BRANCH', 'stable');
            }
        }

        if (!defined('UPDATE_BACKUP')) {
            if (!empty($settings['UPDATE_BACKUP'])) {
                define('UPDATE_BACKUP', $settings['UPDATE_BACKUP']);
            } else {
                define('UPDATE_BACKUP', 'true');
            }
        }

        if (!defined('UPDATE_BACKUP_DIR')) {
            if (UPDATE_BACKUP == "true") {
                if (!empty($settings['UPDATE_BACKUP_DIR'])) {
                    define('UPDATE_BACKUP_DIR', $settings['UPDATE_BACKUP_DIR']);
                    /**
                     *  On teste l'accès au répertoire renseigné
                     */
                    if (!is_writable(UPDATE_BACKUP_DIR)) {
                        ++$__LOAD_SETTINGS_ERROR; // On force l'affichage d'un message d'erreur même si le paramètre n'est pas vide
                        $__LOAD_SETTINGS_MESSAGES[] = "Backup before update directory '" . UPDATE_BACKUP_DIR . "' is not writeable.";
                    }
                } else {
                    define('UPDATE_BACKUP_DIR', '/var/lib/repomanager/backups');
                }
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
            define('WWW_REPOS_DIR_URL', 'https://' . WWW_HOSTNAME . '/repo');
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
            if (!empty($settings['RPM_SIGN_METHOD'])) {
                define('RPM_SIGN_METHOD', $settings['RPM_SIGN_METHOD']);
            } else {
                /**
                 *  On défini la méthode par défaut en cas de valeur vide
                 */
                define('RPM_SIGN_METHOD', 'rpmsign');

                /**
                 *  On affiche un message uniquement si la signature est activée
                 */
                if (RPM_SIGN_PACKAGES == 'true') {
                    $__LOAD_SETTINGS_MESSAGES[] = "GPG signing method for signing RPM packages is not defined.";
                }
            }
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
                define('GPG_SIGNING_KEYID', 'repomanager@localhost.local');
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

        if (!empty($sendSettings['wwwUser'])) {
            $wwwUser = Common::validateData($sendSettings['wwwUser']);

            if (!Common::isAlphanumDash($wwwUser)) {
                throw new Exception('Invalid user value for ' . $wwwUser);
            }

            $settingsToApply['WWW_USER'] = $wwwUser;
        }

        if (!empty($sendSettings['wwwHostname'])) {
            $wwwHostname = Common::validateData($sendSettings['wwwHostname']);

            if (!Common::isAlphanumDash($wwwHostname, array('.'))) {
                throw new Exception('Invalid hostname value for ' . $wwwHostname);
            }

            $settingsToApply['WWW_HOSTNAME'] = $wwwHostname;
        }

        /**
         *  Update settings
         */
        if (!empty($sendSettings['updateAuto'])) {
            if ($sendSettings['updateAuto'] == "true") {
                $settingsToApply['UPDATE_AUTO'] = 'true';
            } else {
                $settingsToApply['UPDATE_AUTO'] = 'false';
            }
        }

        // Hardcoded for now
        // if (!empty($sendSettings['updateBranch'])) {
        //     $updateBranch = 'stable';
        // }

        if (!empty($sendSettings['updateBackup'])) {
            if ($sendSettings['updateBackup'] == "true") {
                $settingsToApply['UPDATE_BACKUP'] = 'true';
            } else {
                $settingsToApply['UPDATE_BACKUP'] = 'false';
            }
        }

        if (!empty($sendSettings['updateBackupDir'])) {
            $updateBackupDir = Common::validateData($sendSettings['updateBackupDir']);

            if (!Common::isAlphanumDash($updateBackupDir, array('/'))) {
                throw new Exception('Invalid directory value for ' . $updateBackupDir);
            }

            $settingsToApply['UPDATE_BACKUP_DIR'] = $updateBackupDir;
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

        if (!empty($sendSettings['rpmSignMethod'])) {
            if ($sendSettings['rpmSignMethod'] != 'rpmsign' and $sendSettings['rpmSignMethod'] != 'rpmresign') {
                throw new Exception('Invalid RPM signing method');
            }
            $settingsToApply['RPM_SIGN_METHOD'] = $sendSettings['rpmSignMethod'];
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

        if (!empty($sendSettings['statsLogPath'])) {
            $statsLogPath = Common::validateData($sendSettings['statsLogPath']);
            if (!Common::isAlphanumDash($statsLogPath, array('.', '/'))) {
                throw new Exception('Invalid stats log path value for ' . $statsLogPath);
            }

            $settingsToApply['STATS_LOG_PATH'] = $statsLogPath;
        }

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

    /**
     *  Import settings from configuration file
     */
    public function import(string $configFile)
    {
        $settings = array();
        $repomanager_conf_array = parse_ini_file($configFile);

        if (!empty($repomanager_conf_array['WWW_DIR'])) {
            $settings['wwwDir'] = $repomanager_conf_array['WWW_DIR'];
        }

        if (!empty($repomanager_conf_array['REPOS_DIR'])) {
            $settings['reposDir'] = $repomanager_conf_array['REPOS_DIR'];
        }

        $settings['emailRecipient'] = array();

        $settings['debugMode'] = 'false';

        if (!empty($repomanager_conf_array['REPO_CONF_FILES_PREFIX'])) {
            $settings['repoConfFilesPrefix'] = $repomanager_conf_array['REPO_CONF_FILES_PREFIX'];
        }

        if (!empty($repomanager_conf_array['TIMEZONE'])) {
            $settings['timezone'] = $repomanager_conf_array['TIMEZONE'];
        }

        if (!empty($repomanager_conf_array['WWW_USER'])) {
            $settings['wwwUser'] = $repomanager_conf_array['WWW_USER'];
        }

        if (!empty($repomanager_conf_array['WWW_HOSTNAME'])) {
            $settings['wwwHostname'] = $repomanager_conf_array['WWW_HOSTNAME'];
        }

        if (!empty($repomanager_conf_array['UPDATE_AUTO'])) {
            $settings['updateAuto'] = $repomanager_conf_array['UPDATE_AUTO'];
        }

        if (!empty($repomanager_conf_array['UPDATE_BRANCH'])) {
            $settings['updateBranch'] = $repomanager_conf_array['UPDATE_BRANCH'];
        }

        if (!empty($repomanager_conf_array['UPDATE_BACKUP_ENABLED'])) {
            $settings['updateBackup'] = $repomanager_conf_array['UPDATE_BACKUP_ENABLED'];
        }

        if (!empty($repomanager_conf_array['BACKUP_DIR'])) {
            $settings['updateBackupDir'] = $repomanager_conf_array['BACKUP_DIR'];
        }

        if (!empty($repomanager_conf_array['RPM_REPO'])) {
            $settings['rpmRepo'] = $repomanager_conf_array['RPM_REPO'];
        }

        if (!empty($repomanager_conf_array['RPM_SIGN_PACKAGES'])) {
            $settings['rpmSignPackages'] = $repomanager_conf_array['RPM_SIGN_PACKAGES'];
        }

        if (!empty($repomanager_conf_array['RPM_SIGN_METHOD'])) {
            $settings['rpmSignMethod'] = $repomanager_conf_array['RPM_SIGN_METHOD'];
        }

        if (!empty($repomanager_conf_array['RELEASEVER'])) {
            $settings['releasever'] = $repomanager_conf_array['RELEASEVER'];
        }

        if (!empty($repomanager_conf_array['RPM_DEFAULT_ARCH'])) {
            $settings['rpmDefaultArch'] = explode(',', $repomanager_conf_array['RPM_DEFAULT_ARCH']);
        }

        if (!empty($repomanager_conf_array['RPM_INCLUDE_SOURCE'])) {
            $settings['rpmIncludeSource'] = $repomanager_conf_array['RPM_INCLUDE_SOURCE'];
        }

        if (!empty($repomanager_conf_array['DEB_REPO'])) {
            $settings['debRepo'] = $repomanager_conf_array['DEB_REPO'];
        } else {
            $settings['debRepo'] = 'false';
        }

        if (!empty($repomanager_conf_array['DEB_SIGN_REPO'])) {
            $settings['debSignRepo'] = $repomanager_conf_array['DEB_SIGN_REPO'];
        }

        if (!empty($repomanager_conf_array['DEB_DEFAULT_ARCH'])) {
            $settings['debDefaultArch'] = explode(',', $repomanager_conf_array['DEB_DEFAULT_ARCH']);
        }

        if (!empty($repomanager_conf_array['DEB_INCLUDE_SOURCE'])) {
            $settings['debIncludeSource'] = $repomanager_conf_array['DEB_INCLUDE_SOURCE'];
        }

        if (!empty($repomanager_conf_array['DEB_DEFAULT_TRANSLATION'])) {
            $settings['debDefaultTranslation'] = explode(',', $repomanager_conf_array['DEB_DEFAULT_TRANSLATION']);
        }

        if (!empty($repomanager_conf_array['GPG_SIGNING_KEYID'])) {
            $settings['gpgKeyID'] = $repomanager_conf_array['GPG_SIGNING_KEYID'];
        }

        if (!empty($repomanager_conf_array['PLANS_ENABLED'])) {
            $settings['plansEnable'] = $repomanager_conf_array['PLANS_ENABLED'];
        }

        if (!empty($repomanager_conf_array['PLAN_REMINDERS_ENABLED'])) {
            $settings['plansRemindersEnable'] = $repomanager_conf_array['PLAN_REMINDERS_ENABLED'];
        }

        if (!empty($repomanager_conf_array['ALLOW_AUTODELETE_ARCHIVED_REPOS'])) {
            $settings['plansUpdateRepo'] = $repomanager_conf_array['ALLOW_AUTODELETE_ARCHIVED_REPOS'];
        }

        if (!empty($repomanager_conf_array['PLANS_CLEAN_REPOS'])) {
            $settings['plansCleanRepo'] = $repomanager_conf_array['PLANS_CLEAN_REPOS'];
        }

        if (isset($repomanager_conf_array['RETENTION']) and $repomanager_conf_array['RETENTION'] >= 0) {
            $settings['retention'] = $repomanager_conf_array['RETENTION'];
        }

        if (!empty($repomanager_conf_array['STATS_ENABLED'])) {
            $settings['statsEnable'] = $repomanager_conf_array['STATS_ENABLED'];
        }

        if (!empty($repomanager_conf_array['STATS_LOG_PATH'])) {
            $settings['statsLogPath'] = $repomanager_conf_array['STATS_LOG_PATH'];
            if (!is_file($settings['statsLogPath']) or !is_readable($settings['statsLogPath'])) {
                $settings['statsLogPath'] = '';
            }
        }

        if (!empty($repomanager_conf_array['MANAGE_HOSTS'])) {
            $settings['manageHosts'] = $repomanager_conf_array['MANAGE_HOSTS'];
        }

        if (!empty($repomanager_conf_array['MANAGE_PROFILES'])) {
            $settings['manageProfiles'] = $repomanager_conf_array['MANAGE_PROFILES'];
        }

        try {
            $this->apply($settings);

            /**
             *  Rename the config file to prevent it from being imported again
             */
            rename($configFile, $configFile . '.imported');
        } catch (Exception $e) {
            throw new Exception('Error while applying settings: ' . $e->getMessage());
        }
    }
}
