<?php

namespace Controllers;

require_once('Autoloader.php');

use Exception;

class Controller
{
    public static function render()
    {
        /**
         *  Getting target URI
         */
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = explode('/', $uri);
        $targetUri = $uri[1];

        /**
         *  If target URI is login or logout then load minimal necessary
         */
        if ($targetUri == 'login' or $targetUri == 'logout') {
            Autoloader::loadFromLogin();
        } else {
            Autoloader::load();
        }

        /**
         *  If target URI is 'index.php' then redirect to /
         */
        if ($targetUri == 'index.php') {
            header('Location: /');
        }

        /**
         *  Rendering
         */
        if ($targetUri == '') {
            /**
             *  Render 'REPOS' main tab
             */
            self::renderRepos();
        } elseif ($targetUri == 'plans') {
            /**
             *  Render 'PLANIFICATIONS' tab
             */
            self::renderPlans();
        } elseif ($targetUri == 'hosts') {
            /**
             *  Render 'MANAGE HOSTS' tab
             */
            self::renderHosts();
        } elseif ($targetUri == 'host') {
            /**
             *  Render single host page
             */
            self::renderHost();
        } elseif ($targetUri == 'host-details') {
            /**
             *  Render host details page
             */
            self::renderHostDetails();
        } elseif ($targetUri == 'profiles') {
            /**
             *  Render 'MANAGE PROFILES' tab
             */
            self::renderProfiles();
        } elseif ($targetUri == 'settings') {
            /**
             *  Render 'SETTINGS' tab
             */
            self::renderSettings();
        } elseif ($targetUri == 'run') {
            /**
             *  Render 'OPERATIONS' tab
             */
            self::renderOperations();
        } elseif ($targetUri == 'browse') {
            /**
             *  Render browse page
             */
            self::renderBrowse();
        } elseif ($targetUri == 'stats') {
            /**
             *  Render stats page
             */
            self::renderStats();
        } elseif ($targetUri == 'history') {
            /**
             *  Render history page
             */
            self::renderHistory();
        } elseif ($targetUri == 'userspace') {
            /**
             *  Render userpace page
             */
            self::renderUserspace();
        } elseif ($targetUri == 'login') {
            /**
             *  Render login page
             */
            self::renderLogin();
        } elseif ($targetUri == 'logout') {
            /**
             *  Logout
             */
            self::logout();
        } else {
            /**
             *  Render page not found
             */
            self::renderNotfound();
        }
    }

    /**
     *  Render 'REPOS' tab
     */
    private static function renderRepos()
    {
        $myrepo = new \Controllers\Repo();

        /**
         *  Get total repos count
         */
        $totalRepos = $myrepo->count('active');

        /**
         *  Get used space
         */
        $diskTotalSpace = disk_total_space(REPOS_DIR);
        $diskFreeSpace = disk_free_space(REPOS_DIR);
        $diskUsedSpace = $diskTotalSpace - $diskFreeSpace;
        $diskTotalSpace = $diskTotalSpace / 1073741824;
        $diskUsedSpace = $diskUsedSpace / 1073741824;
        /**
         *  Format data to get a percent result without comma
         */
        $diskFreeSpace = round(100 - (($diskUsedSpace / $diskTotalSpace) * 100));
        $diskFreeSpacePercent = $diskFreeSpace;
        $diskUsedSpace = round(100 - ($diskFreeSpace));
        $diskUsedSpacePercent = round(100 - ($diskFreeSpace));

        /**
         *  If plans are enabled the get last and next plan results
         */
        if (PLANS_ENABLED == "yes") {
            $plan = new \Controllers\Planification();
            $lastPlan = $plan->listLast();
            $nextPlan = $plan->listNext();
        }

        /**
         *  Get current CPU load
         */
        $currentLoad = sys_getloadavg();
        $currentLoad = substr($currentLoad[0], 0, 4);
        $currentLoadColor = 'green';

        if ($currentLoad >= 2) {
            $currentLoadColor = 'yellow';
        }
        if ($currentLoad >= 3) {
            $currentLoadColor = 'red';
        }

        ob_start();
        include_once(ROOT . '/views/repos.template.php');
        $content = ob_get_clean();

        include_once(ROOT . '/views/layout.html.php');
    }

    /**
     *  Render 'PLANIFICATIONS' tab
     */
    private static function renderPlans()
    {
        $myplan = new \Controllers\Planification();
        $mygroup = new \Controllers\Group('repo');
        $myrepo = new \Controllers\Repo();

        /**
         *  Récupération de la liste des planifications en liste d'attente ou en cours d'exécution
         */
        $planQueueList = $myplan->listQueue();
        $planRunningList = $myplan->listRunning();
        $planList = array_merge($planRunningList, $planQueueList);
        array_multisort(array_column($planList, 'Date'), SORT_ASC, array_column($planList, 'Time'), SORT_ASC, $planList);

        ob_start();
        include_once(ROOT . '/views/plans.template.php');
        $content = ob_get_clean();

        include_once(ROOT . '/views/layout.html.php');
    }

    /**
     *  Render 'MANAGE HOSTS' tab
     */
    private static function renderHosts()
    {
        $group = new \Controllers\Group('host');
        $myhost = new \Controllers\Host();
        $mycolor = new \Controllers\Common();

        /**
         *  Case general hosts threshold settings form has been sent
         */
        if (!empty($_POST['settings-pkgs-considered-outdated']) and !empty($_POST['settings-pkgs-considered-critical'])) {
            $pkgs_considered_outdated = \Controllers\Common::validateData($_POST['settings-pkgs-considered-outdated']);
            $pkgs_considered_critical = \Controllers\Common::validateData($_POST['settings-pkgs-considered-critical']);

            $myhost = new \Controllers\Host();
            $myhost->setSettings($pkgs_considered_outdated, $pkgs_considered_critical);
        }

        /**
         *  Getting general hosts threshold settings
         */
        $hostsSettings = $myhost->getSettings();

        /**
         *  Threshold of the maximum number of available update above which the host is considered as 'not up to date' (but not critical)
         */
        $pkgs_count_considered_outdated = $hostsSettings['pkgs_count_considered_outdated'];

        /**
         *  Threshold of the maximum number of available update above which the host is considered as 'not up to date' (critical)
         */
        $pkgs_count_considered_critical = $hostsSettings['pkgs_count_considered_critical'];

        /**
         *  Getting total hosts
         */
        $totalHosts = count($myhost->listAll('active'));

        /**
         *  Initializing counters for doughnut chart
         */
        $totalUptodate = 0;
        $totalNotUptodate = 0;

        /**
         *  Getting a list of all hosts OS (bar chart)
         */
        $osList = $myhost->listCountOS();

        /**
         *  Getting a list of all hosts kernel
         */
        $kernelList = $myhost->listCountKernel();
        array_multisort(array_column($kernelList, 'Kernel_count'), SORT_DESC, $kernelList);

        /**
         *  Getting a list of all hosts arch
         */
        $archList = $myhost->listCountArch();

        /**
         *  Getting a list of all hosts environments
         */
        $envsList = $myhost->listCountEnv();

        /**
         *  Getting a list of all hosts profiles
         */
        $profilesList = $myhost->listCountProfile();
        array_multisort(array_column($profilesList, 'Profile_count'), SORT_DESC, $profilesList);

        /**
         *  Getting a list of all hosts agent status
         */
        $agentStatusList = $myhost->listCountAgentStatus();

        /**
         *  Getting a list of all hosts agent release version
         */
        $agentVersionList = $myhost->listCountAgentVersion();

        ob_start();
        include_once(ROOT . '/views/hosts.template.php');
        $content = ob_get_clean();

        include_once(ROOT . '/views/layout.html.php');
    }

    /**
     *  Render single host page
     */
    private static function renderHost()
    {
        ob_start();
        include_once(ROOT . '/views/host.template.php');
        $content = ob_get_clean();

        include_once(ROOT . '/views/layout.html.php');
    }

    /**
     *  Render host details page
     */
    private static function renderHostDetails()
    {
        include_once(ROOT . '/views/host-inc.template.php');
    }

    /**
     *  Render 'MANAGE PROFILES' tab
     */
    private static function renderProfiles()
    {
        /**
         *  Only admin have access to this page
         */
        if (!IS_ADMIN) {
            header('Location: /');
            exit;
        }

        $myprofile = new \Controllers\Profile();
        $myrepo = new \Controllers\Repo();

        /**
         *  On tente de récupérer la configuration serveur en base de données
         */
        $serverConfiguration = $myprofile->getServerConfiguration();

        /**
         *  Si certaines valeurs sont vides alors on set des valeurs par défaut déterminées par l'autoloader, car tous les champs doivent être complétés.
         *  On indiquera à l'utilisateur qu'il faudra valider le formulaire pour appliquer la configuration.
         */
        $serverConfApplyNeeded = 0;

        if (!empty($serverConfiguration['Package_type'])) {
            $serverPackageType = $serverConfiguration['Package_type'];
        } else {
            /**
             *  Si aucun type de paquets n'est spécifié alors on va déduire en fonction du type de système sur lequel repomanager est installé
             */
            if (OS_FAMILY == 'Redhat') {
                $serverPackageType = 'rpm';
            }
            if (OS_FAMILY == 'Debian') {
                $serverPackageType = 'deb';
            }
            $serverConfApplyNeeded++;
        }

        if (!empty($serverConfiguration['Manage_client_conf'])) {
            $serverManageClientConf = $serverConfiguration['Manage_client_conf'];
        } else {
            $serverManageClientConf = 'no';
            $serverConfApplyNeeded++;
        }

        if (!empty($serverConfiguration['Manage_client_repos'])) {
            $serverManageClientRepos = $serverConfiguration['Manage_client_repos'];
        } else {
            $serverManageClientRepos = 'no';
            $serverConfApplyNeeded++;
        }

        /**
         *  Getting all profiles names
         */
        $profiles = $myprofile->list();

        ob_start();
        include_once(ROOT . '/views/profiles.template.php');
        $content = ob_get_clean();

        include_once(ROOT . '/views/layout.html.php');
    }

    /**
     *  Render 'SETTINGS' tab
     */
    private static function renderSettings()
    {
        /**
         *  Only admin have access to this page
         */
        if (!IS_ADMIN) {
            header('Location: /');
            exit;
        }

        /**
         *  Mise à jour de Repomanager
         */
        if (!empty($_GET['action']) and \Controllers\Common::validateData($_GET['action']) == "update") {
            $myupdate = new \Controllers\Update();
            $updateStatus = $myupdate->update();
        }

        /**
         *  Si un des formulaires de la page a été validé alors on entre dans cette condition
         */
        if (!empty($_POST['action']) and \Controllers\Common::validateData($_POST['action']) === "applyConfiguration") {
            /**
             *  Récupération de tous les paramètres définis dans le fichier repomanager.conf
             */
            $repomanager_conf_array = parse_ini_file(REPOMANAGER_CONF, true);

            /**
             *  Section PATHS
             */

            /**
             *  Chemin du répertoire des repos sur le serveur
             */
            if (!empty($_POST['reposDir'])) {
                $reposDir = \Controllers\Common::validateData($_POST['reposDir']);
                /**
                 *  Le chemin ne doit comporter que des lettres, des chiffres, des tirets et des slashs
                 */
                if (\Controllers\Common::isAlphanumDash($reposDir, array('/'))) {
                    /**
                     *  Suppression du dernier slash si il y en a un
                     */
                    $repomanager_conf_array['PATHS']['REPOS_DIR'] = rtrim($reposDir, '/');
                }
            }

            /**
             *  Section CONFIGURATION
             */

            /**
             *  Adresse mail destinatrice des alertes
             */
            if (!empty($_POST['emailDest'])) {
                $emailDest = \Controllers\Common::validateData($_POST['emailDest']);

                if (\Controllers\Common::isAlphanumDash($emailDest, array('@', '.'))) {
                    $repomanager_conf_array['CONFIGURATION']['EMAIL_DEST'] = trim($emailDest);
                }
            }

            /**
             *  Si on souhaite activer ou non la gestion des hôtes
             */
            if (!empty($_POST['manageHosts']) and $_POST['manageHosts'] === "yes") {
                $repomanager_conf_array['CONFIGURATION']['MANAGE_HOSTS'] = 'yes';
            } else {
                $repomanager_conf_array['CONFIGURATION']['MANAGE_HOSTS'] = 'no';
            }

            /**
             *  Si on souhaite activer ou non la gestion des profils
             */
            if (!empty($_POST['manageProfiles']) and $_POST['manageProfiles'] === "yes") {
                $repomanager_conf_array['CONFIGURATION']['MANAGE_PROFILES'] = 'yes';
            } else {
                $repomanager_conf_array['CONFIGURATION']['MANAGE_PROFILES'] = 'no';
            }

            /**
             *  Modification du préfix des fichiers de conf repos
             */
            if (isset($_POST['repoConfPrefix'])) {
                $repomanager_conf_array['CONFIGURATION']['REPO_CONF_FILES_PREFIX'] = \Controllers\Common::validateData($_POST['repoConfPrefix']);
            }

            /**
             *  Section RPM
             */

            /**
             *  Activer/désactiver les repos RPM
             */
            if (!empty($_POST['rpmRepo']) and $_POST['rpmRepo'] === "enabled") {
                $repomanager_conf_array['RPM']['RPM_REPO'] = 'enabled';
            } else {
                $repomanager_conf_array['RPM']['RPM_REPO'] = 'disabled';
            }

            /**
             *  Activer/désactiver la signature des paquets avec GPG
             */
            if (!empty($_POST['rpmSignPackages']) and $_POST['rpmSignPackages'] === "yes") {
                $repomanager_conf_array['RPM']['RPM_SIGN_PACKAGES'] = 'yes';
            } else {
                $repomanager_conf_array['RPM']['RPM_SIGN_PACKAGES'] = 'no';
            }

            /**
             *  Email lié à la clé GPG qui signe les paquets
             */
            if (!empty($_POST['rpmGpgKeyID'])) {
                $rpmGpgKeyID = \Controllers\Common::validateData($_POST['rpmGpgKeyID']);

                if (\Controllers\Common::isAlphanumDash($rpmGpgKeyID, array('@', '.'))) {
                    $repomanager_conf_array['RPM']['RPM_SIGN_GPG_KEYID'] = trim($rpmGpgKeyID);
                }
            }

            if (!empty($_POST['releasever']) and is_numeric($_POST['releasever'])) {
                $repomanager_conf_array['RPM']['RELEASEVER'] = $_POST['releasever'];
            }

            /**
             *  Rpm mirror: default architecture
             */
            if (!empty($_POST['rpmDefaultArchitecture'])) {
                /**
                 *  Convert array to a string with values separated by a comma
                 */
                $rpmDefaultArchitecture = \Controllers\Common::validateData(implode(',', $_POST['rpmDefaultArchitecture']));
            } else {
                $rpmDefaultArchitecture = '';
            }
            if (\Controllers\Common::isAlphanumDash($rpmDefaultArchitecture, array(','))) {
                $repomanager_conf_array['RPM']['RPM_DEFAULT_ARCH'] = trim($rpmDefaultArchitecture);
            }

            /**
             *  Rpm mirror: include source
             */
            if (!empty($_POST['rpmIncludeSource']) and $_POST['rpmIncludeSource'] === "yes") {
                $repomanager_conf_array['RPM']['RPM_INCLUDE_SOURCE'] = 'yes';
            } else {
                $repomanager_conf_array['RPM']['RPM_INCLUDE_SOURCE'] = 'no';
            }


            /**
             *  Section DEB
             */

            /**
             *  Activer/désactiver les repos DEB
             */
            if (!empty($_POST['debRepo']) and $_POST['debRepo'] === "enabled") {
                $repomanager_conf_array['DEB']['DEB_REPO'] = 'enabled';
            } else {
                $repomanager_conf_array['DEB']['DEB_REPO'] = 'disabled';
            }

            /**
             *  Activer/désactiver la signature des repos avec GPG
             */
            if (!empty($_POST['debSignRepo']) and $_POST['debSignRepo'] === "yes") {
                $repomanager_conf_array['DEB']['DEB_SIGN_REPO'] = 'yes';
            } else {
                $repomanager_conf_array['DEB']['DEB_SIGN_REPO'] = 'no';
            }

            /**
             *  Email lié à la clé GPG qui signe les paquets
             */
            if (!empty($_POST['debGpgKeyID'])) {
                $debGpgKeyID = \Controllers\Common::validateData($_POST['debGpgKeyID']);

                if (\Controllers\Common::isAlphanumDash($debGpgKeyID, array('@', '.'))) {
                    $repomanager_conf_array['DEB']['DEB_SIGN_GPG_KEYID'] = trim($debGpgKeyID);
                }
            }

            /**
             *  Deb mirror: default architecture
             */
            if (!empty($_POST['debDefaultArchitecture'])) {
                /**
                 *  Convert array to a string with values separated by a comma
                 */
                $debDefaultArchitecture = \Controllers\Common::validateData(implode(',', $_POST['debDefaultArchitecture']));
            } else {
                $debDefaultArchitecture = '';
            }
            if (\Controllers\Common::isAlphanumDash($debDefaultArchitecture, array(','))) {
                $repomanager_conf_array['DEB']['DEB_DEFAULT_ARCH'] = trim($debDefaultArchitecture);
            }

            /**
             *  Deb mirror: include source
             */
            if (!empty($_POST['debIncludeSource']) and $_POST['debIncludeSource'] === "yes") {
                $repomanager_conf_array['DEB']['DEB_INCLUDE_SOURCE'] = 'yes';
            } else {
                $repomanager_conf_array['DEB']['DEB_INCLUDE_SOURCE'] = 'no';
            }

            /**
             *  Deb mirror: default translations
             */
            if (!empty($_POST['debDefaultTranslation'])) {
                /**
                 *  Convert array to a string with values separated by a comma
                 */
                $debDefaultTranslation = \Controllers\Common::validateData(implode(',', $_POST['debDefaultTranslation']));
            } else {
                $debDefaultTranslation = '';
            }
            if (\Controllers\Common::isAlphanumDash($debDefaultTranslation, array(','))) {
                $repomanager_conf_array['DEB']['DEB_DEFAULT_TRANSLATION'] = trim($debDefaultTranslation);
            }


            /**
             *  Section UPDATE
             */

            /**
             *  Activer / désactiver la mise à jour automatique de repomanager
             */
            if (!empty($_POST['updateAuto']) and \Controllers\Common::validateData($_POST['updateAuto']) === "yes") {
                $repomanager_conf_array['UPDATE']['UPDATE_AUTO'] = 'yes';
            } else {
                $repomanager_conf_array['UPDATE']['UPDATE_AUTO'] = 'no';
            }

            /**
             *  Activer / désactiver le backup de repomanager avant mise à jour
             */
            if (!empty($_POST['updateBackup']) and \Controllers\Common::validateData($_POST['updateBackup']) === "yes") {
                $repomanager_conf_array['UPDATE']['UPDATE_BACKUP_ENABLED'] = 'yes';
            } else {
                $repomanager_conf_array['UPDATE']['UPDATE_BACKUP_ENABLED'] = 'no';
            }


            /**
             *  Répertoire de destination des backups de repomanager sur le serveur si le paramètre UPDATE_BACKUP_ENABLED est activé
             */
            if (!empty($_POST['updateBackupDir'])) {
                $updateBackupDir = \Controllers\Common::validateData($_POST['updateBackupDir']);

                if (\Controllers\Common::isAlphanumDash($updateBackupDir, array('/'))) {
                    $repomanager_conf_array['UPDATE']['BACKUP_DIR'] = rtrim($updateBackupDir, '/');
                }
            }

            /**
             *  Branche git de mise à jour
             */
            if (!empty($_POST['updateBranch'])) {
                $updateBranch = \Controllers\Common::validateData($_POST['updateBranch']);

                if (\Controllers\Common::isAlphanum($updateBranch, array('/'))) {
                    $repomanager_conf_array['UPDATE']['UPDATE_BRANCH'] = $updateBranch;
                }
            }


            /**
             *  Section WWW
             */

            /**
             *  Utilisateur web exécutant le serveur web
             */
            if (!empty($_POST['wwwUser'])) {
                $wwwUser = \Controllers\Common::validateData($_POST['wwwUser']);

                if (\Controllers\Common::isAlphanumDash($wwwUser)) {
                    $repomanager_conf_array['WWW']['WWW_USER'] = trim($wwwUser);
                }
            }

            /**
             *  Adresse web hôte de repomanager (https://xxxx)
             */
            $OLD_WWW_HOSTNAME = WWW_HOSTNAME; // On conserve le hostname actuel car on va s'en servir pour le remplacer dans les fichiers de conf ci dessous
            if (!empty($_POST['wwwHostname']) and $OLD_WWW_HOSTNAME !== \Controllers\Common::validateData($_POST['wwwHostname']) and \Controllers\Common::isAlphanumDash(\Controllers\Common::validateData($_POST['wwwHostname']), array('.'))) {
                $NEW_WWW_HOSTNAME = trim(\Controllers\Common::validateData($_POST['wwwHostname']));
                $repomanager_conf_array['WWW']['WWW_HOSTNAME'] = "$NEW_WWW_HOSTNAME";
            }

            /**
             *  URL d'accès aux repos. Exemple : https://xxxxxxx/repo
             */
            if (!empty($_POST['wwwReposDirUrl'])) {
                $wwwReposDirUrl = \Controllers\Common::validateData($_POST['wwwReposDirUrl']);

                if (\Controllers\Common::isAlphanumDash($wwwReposDirUrl, array('.', '/', ':'))) {
                    $repomanager_conf_array['WWW']['WWW_REPOS_DIR_URL'] = rtrim($wwwReposDirUrl, '/');
                }
            }


            /**
             *  Section PLANS
             */

            /**
             *  Activation/désactivation de l'automatisation
             */
            if (!empty($_POST['automatisationEnable']) and \Controllers\Common::validateData($_POST['automatisationEnable']) === "yes") {
                $repomanager_conf_array['PLANS']['PLANS_ENABLED'] = 'yes';
            } else {
                $repomanager_conf_array['PLANS']['PLANS_ENABLED'] = 'no';
            }

            /**
             *  Autoriser ou non la mise à jour des repos par l'automatisation
             */
            if (!empty($_POST['allowAutoUpdateRepos']) and \Controllers\Common::validateData($_POST['allowAutoUpdateRepos']) === "yes") {
                $repomanager_conf_array['PLANS']['ALLOW_AUTOUPDATE_REPOS'] = 'yes';
            } else {
                $repomanager_conf_array['PLANS']['ALLOW_AUTOUPDATE_REPOS'] = 'no';
            }

            /**
             *  Autoriser ou non le changement d'environnement par l'automatisation
             */
            // if (!empty($_POST['allowAutoUpdateReposEnv']) and \Controllers\Common::validateData($_POST['allowAutoUpdateReposEnv']) === "yes") {
            //     $repomanager_conf_array['PLANS']['ALLOW_AUTOUPDATE_REPOS_ENV'] = 'yes';
            // } else {
            //     $repomanager_conf_array['PLANS']['ALLOW_AUTOUPDATE_REPOS_ENV'] = 'no';
            // }

            /**
             *  Autoriser ou non la suppression des repos archivés par l'automatisation
             */
            if (!empty($_POST['allowAutoDeleteArchivedRepos']) and \Controllers\Common::validateData($_POST['allowAutoDeleteArchivedRepos']) === "yes") {
                $repomanager_conf_array['PLANS']['ALLOW_AUTODELETE_ARCHIVED_REPOS'] = 'yes';
            } else {
                $repomanager_conf_array['PLANS']['ALLOW_AUTODELETE_ARCHIVED_REPOS'] = 'no';
            }

            /**
             *  Retention, nombre de repos à conserver avant suppression par l'automatisation
             */
            if (isset($_POST['retention'])) {
                $retention = \Controllers\Common::validateData($_POST['retention']);

                if (is_numeric($retention)) {
                    $repomanager_conf_array['PLANS']['RETENTION'] = $retention;
                }
            }

            /**
             *  Activer / désactiver l'envoie de rappels de planifications futures (seul paramètre cron à ne pas être regroupé avec les autres paramètres cron)
             */
            if (!empty($_POST['cronSendReminders']) and \Controllers\Common::validateData($_POST['cronSendReminders']) === "yes") {
                $repomanager_conf_array['PLANS']['PLAN_REMINDERS_ENABLED'] = 'yes';
            } else {
                $repomanager_conf_array['PLANS']['PLAN_REMINDERS_ENABLED'] = 'no';
            }


            /**
             *  Section STATS
             */

            /**
             *  Activer / désactiver les statistiques
             */
            if (!empty($_POST['cronStatsEnable']) and \Controllers\Common::validateData($_POST['cronStatsEnable']) === "yes") {
                $repomanager_conf_array['STATS']['STATS_ENABLED'] = 'yes';
            } else {
                $repomanager_conf_array['STATS']['STATS_ENABLED'] = 'no';
            }

            /**
             *  Chemin vers le fichier de log d'accès à analyser pour statistiques
             */
            if (!empty($_POST['statsLogPath'])) {
                $statsLogPath = \Controllers\Common::validateData($_POST['statsLogPath']);

                if (\Controllers\Common::isAlphanumDash($statsLogPath, array('.', '/'))) {
                    $repomanager_conf_array['STATS']['STATS_LOG_PATH'] = $statsLogPath;
                }

                /**
                 *  On stoppe le process stats-log-parser.sh actuel, il sera relancé au rechargement de la page
                 */
                \Controllers\Common::killStatsLogParser();
            }

            /**
             *  On écrit toutes les modifications dans le fichier repomanager.conf
             */
            \Controllers\Common::writeToIni(REPOMANAGER_CONF, $repomanager_conf_array);

            /**
             *  Puis rechargement de la page pour appliquer les modifications de configuration
             */
            header('Location: /settings');
            exit;

            /**
             *  Nettoyage du cache de repos-list
             */
            \Controllers\Common::clearCache();
        }

        /**
         *  Création d'un nouvel utilisateur
         */
        if (!empty($_POST['action']) and \Controllers\Common::validateData($_POST['action']) == 'createUser' and !empty($_POST['username']) and !empty($_POST['role'])) {
            $username = \Controllers\Common::validateData($_POST['username']);
            $role = \Controllers\Common::validateData($_POST['role']);
            $mylogin = new \Controllers\Login();

            try {
                $newUserUsername = $username;
                $newUserPassword = $mylogin->addUser($username, $role);
                \Controllers\Common::printAlert("User <b>$username</b> has been created", 'success');
            } catch (Exception $e) {
                \Controllers\Common::printAlert($e->getMessage(), 'error');
            }
        }

        /**
         *  Réinitialisation du mot de passe d'un utilisateur
         */
        if (isset($_GET['resetPassword']) and !empty($_GET['username'])) {
            $mylogin = new \Controllers\Login();

            $result = $mylogin->resetPassword($_GET['username']);

            try {
                $newResetedPwdUsername = $_GET['username'];
                $newResetedPwdPassword = $mylogin->resetPassword($_GET['username']);
                \Controllers\Common::printAlert('Password has been regenerated', 'success');
            } catch (Exception $e) {
                \Controllers\Common::printAlert($e->getMessage(), 'error');
            }
        }

        /**
         *  Suppression d'un utilisateur
         */
        if (isset($_GET['deleteUser']) and !empty($_GET['username'])) {
            $mylogin = new \Controllers\Login();

            try {
                $mylogin->deleteUser($_GET['username']);
                \Controllers\Common::printAlert('User <b>' . $_GET['username'] . '</b> has been deleted', 'success');
            } catch (Exception $e) {
                \Controllers\Common::printAlert($e->getMessage(), 'error');
            }
        }

        ob_start();
        include_once(ROOT . '/views/settings.template.php');
        $content = ob_get_clean();

        include_once(ROOT . '/views/layout.html.php');
    }

    /**
     *  Render 'OPERATIONS' tab
     */
    private static function renderOperations()
    {
        /**
         *  Bouton 'Stop' pour arrêter une opération en cours
         */
        if (!empty($_GET['stop'])) {
            $opToStop = new \Controllers\Operation();
            $opToStop->kill(\Controllers\Common::validateData($_GET['stop'])); // $_GET['stop'] contient le pid de l'opération
        }

        /**
         *  Récupération du fichier de log à visualiser si passé en GET
         */
        $logfile = 'none';

        if (!empty($_GET['logfile'])) {
            $logfile = \Controllers\Common::validateData($_GET['logfile']);
        }

        ob_start();
        include_once(ROOT . '/views/operations.template.php');
        $content = ob_get_clean();

        include_once(ROOT . '/views/layout.html.php');
    }

    /**
     *  Render browse page
     */
    private static function renderBrowse()
    {
        /**
         *  Cas où on souhaite reconstruire les fichiers de métadonnées du repo
         */
        if (!empty($_POST['action']) and \Controllers\Common::validateData($_POST['action']) === 'reconstruct' and !empty($_POST['snapId'])) {
            $snapId = \Controllers\Common::validateData($_POST['snapId']);

            /**
             *  Récupération de la valeur de GPG Resign
             *  Si on n'a rien transmis alors on set la valeur à 'no'
             *  Si on a transmis quelque chose alors on set la valeur à 'yes'
             */
            if (empty($_POST['repoGpgResign'])) {
                $repoGpgResign = 'no';
            } else {
                $repoGpgResign = 'yes';
            }

            /**
             *  On instancie un nouvel objet Repo avec les infos transmises, on va ensuite pouvoir vérifier que ce repo existe bien
             */
            $myrepo = new \Controllers\Repo();
            $myrepo->setSnapId($snapId);

            /**
             *  On vérifie que l'ID de repo transmis existe bien, si c'est le cas alors on lance l'opération en arrière plan
             */
            if ($myrepo->existsSnapId($snapId) === true) {
                /**
                 *  Création d'un fichier json qui défini l'opération à exécuter
                 */
                $params = array();
                $params['action'] = 'reconstruct';
                $params['snapId'] = $snapId;
                $params['targetGpgResign'] = $repoGpgResign;

                $myop = new \Controllers\Operation();
                $myop->execute(array($params));
            }

            /**
             *  Rafraichissement de la page
             */
            sleep(1);
            header('Location: ' . __ACTUAL_URL__);
            exit;
        }

        $pathError = 0;

        /**
         *  Récupération du repo transmis
         */
        if (empty($_GET['id'])) {
            $pathError++;
        } else {
            $snapId = \Controllers\Common::validateData($_GET['id']);
        }

        /**
         *  Le repo transmis doit être un numéro car il s'agit de l'ID en BDD
         */
        if (!is_numeric($snapId)) {
            $pathError++;
        }

        /**
         *  A partir de l'ID fourni, on récupère les infos du repo
         */
        if ($pathError == 0) {
            $myrepo = new \Controllers\Repo();
            $myrepo->setSnapId($snapId);
            $myrepo->getAllById('', $snapId, '');
            $reconstruct = $myrepo->getReconstruct();

            /**
             *  Si on n'a eu aucune erreur lors de la récupération des paramètres, alors on peut construire le chemin complet du repo
             */
            if ($myrepo->getPackageType() == "rpm") {
                $repoPath = REPOS_DIR . "/" . $myrepo->getDateFormatted() . "_" . $myrepo->getName();
            }
            if ($myrepo->getPackageType() == "deb") {
                $repoPath = REPOS_DIR . "/" . $myrepo->getName() . "/" . $myrepo->getDist() . "/" . $myrepo->getDateFormatted() . "_" . $myrepo->getSection();
            }

            /**
             *  Si le chemin construit n'existe pas sur le serveur alors on incrémente pathError qui affichera une erreur et empêchera toute action
             */
            if (!is_dir($repoPath)) {
                $pathError++;
            }
        }

        /**
         *  Cas où on upload un package dans un repo
         */
        if (!empty($_POST['action']) and \Controllers\Common::validateData($_POST['action']) == 'uploadPackage' and !empty($_FILES['packages']) and $pathError === 0 and !empty($repoPath)) {
            /**
             *  On définit le chemin d'upload comme étant le répertoire my_uploaded_packages à l'intérieur du répertoire du repo
             */
            $targetDir = $repoPath . '/my_uploaded_packages';

            /**
             *  Si ce répertoire n'existe pas encore alors on le créé
             */
            if (!is_dir($targetDir)) {
                if (!mkdir($targetDir, 0770, true)) {
                    \Controllers\Common::printAlert("Error: cannot create upload directory <b>$target_dir</b>", 'error');
                    return;
                }
            }

            /**
             *  On ré-arrange la liste des fichiers transmis
             */
            $packages = \Controllers\Browse::reArrayFiles($_FILES['packages']);

            $packageExists = ''; // contiendra la liste des paquets ignorés car existent déjà
            $packagesError = ''; // contiendra la liste des paquets uploadé avec une erreur
            $packageEmpty = ''; // contiendra la liste des paquets vides
            $packageInvalid = ''; // contiendra la liste des paquets dont le format est invalide

            foreach ($packages as $package) {
                $uploadError = 0;
                $packageName  = $package['name'];
                $packageType  = $package['type'];
                $packageSize  = $package['size'];
                $packageError = $package['error'];
                $packageTmpName = $package['tmp_name'];

                /**
                 *  Le nom du paquet ne doit pas contenir de caractère spéciaux, sinon on passe au suivant
                 *  On autorise seulement les tirets et les underscores (voir fonction isAlphanumDash), ainsi qu'un caractère supplémentaire : le point (car les nom de paquet contiennent des points)
                 */
                if (!\Controllers\Common::isAlphanumDash($packageName, array('.'))) {
                    $uploadError++;
                    $packageInvalid .= "$packageName, ";
                    continue;
                }

                /**
                 *  Si le paquet est en erreur alors on l'ignore et on passe au suivant
                 */
                if ($packageError != 0) {
                    $uploadError++;
                    $packagesError .= "$packageName, ";
                    continue;
                }

                /**
                 *  Si la taille du paquet est égale à 0 alors on l'ignore et on passe au suivant
                 */
                if ($packageSize == 0) {
                    $uploadError++;
                    $packageEmpty .= "$packageName, ";
                    continue;
                }

                /**
                 *  On vérifie que le paquet n'existe pas déjà, sinon on l'ignore et on l'ajoute à une liste de paquets déjà existants qu'on affichera après
                 */
                if (file_exists($targetDir . '/' . $packageName)) {
                    $uploadError++;
                    $packageExists .= "$packageName, ";
                    continue;
                }

                /**
                 *  On vérifie que le paquet est valide
                 */
                if ($packageType !== 'application/x-rpm' and $packageType !== 'application/vnd.debian.binary-package') {
                    $uploadError++;
                    $packageInvalid .= "$packageName, ";
                }

                /**
                 *  Si on n'a pas eu d'erreur jusque là, alors on peut déplacer le fichier dans son emplacement définitif
                 */
                if ($uploadError === 0 and file_exists($packageTmpName)) {
                    move_uploaded_file($packageTmpName, $targetDir . '/' . $packageName);
                }
            }

            if ($uploadError === 0) {
                \Controllers\Common::printAlert('Files have been uploaded', 'success');
            } else {
                \Controllers\Common::printAlert('Some files have not been uploaded', 'error');
            }
        }

        /**
         *  Cas où on supprime un ou plusieurs paquets d'un repo
         */
        if (!empty($_POST['action']) and \Controllers\Common::validateData($_POST['action']) == 'deletePackages' and !empty($_POST['packageName']) and $pathError === 0 and !empty($repoPath)) {
            $packagesToDeleteNonExists = ''; // contiendra la liste des fichiers qui n'existent pas, si on tente de supprimer un fichier qui n'existe pas
            $packagesDeleted = array();

            foreach ($_POST['packageName'] as $packageToDelete) {
                $packageName = \Controllers\Common::validateData($packageToDelete);
                $packagePath = REPOS_DIR . '/' . $packageName;

                /**
                 *  Le nom du paquet ne doit pas contenir de caractères spéciaux
                 *  On autorise seulement les tirets et les underscores (voir fonction isAlphanumDash), ainsi qu'un caractère supplémentaire : le point (car les nom de paquet contiennent des points)
                 *  On autorise également le slash car le chemin du fichier transmis contient aussi le ou les sous-dossiers vers le paquet à partir de la racine du repo
                 */
                if (!\Controllers\Common::isAlphanumDash($packageName, array('.', '/', '+', '~'))) {
                    continue;
                }

                /**
                 *  On vérifie que le chemin du fichier commence bien par REPOS_DIR et on supprime
                 *  Empeche une personne mal intentionnée de fournir un chemin qui n'a aucun rapport avec le répertoire de repos (par exemple /etc/... )
                 */
                if (preg_match("#^" . REPOS_DIR . "#", $packagePath)) {
                    /**
                     *  On vérifie que le fichier ciblé se termine par .deb ou .rpm sinon on passe au suivant
                     */
                    if (!preg_match("#.deb$#", $packagePath) and !preg_match("#.rpm$#", $packagePath)) {
                        continue;
                    }

                    /**
                     *  Si le fichier n'existe pas, on l'ignore et on passe au suivant
                     */
                    if (!file_exists($packagePath)) {
                        $packagesToDeleteNonExists .= "$packageName, ";
                        continue;
                    }

                    /**
                     *  Suppression
                     */
                    unlink($packagePath);

                    /**
                     *  On stocke le nom du fichier supprimé dans une liste car on va afficher cette liste plus bas pour confirmer à l'utilisateur le(s) paquet(s) supprimé(s)
                     *  Cependant on retire le chemin complet du fichier pour éviter d'afficher l'emplacement des fichiers en clair à l'écran...
                     */
                    $packagesDeleted[] = str_replace("$repoPath/", '', $packagePath);

                    $deleteRepo = new \Controllers\Repo();
                    $deleteRepo->snapSetReconstruct($snapId, 'needed');
                }
            }

            unset($packageName, $packagePath, $deleteRepo);
        }

        ob_start();
        include_once(ROOT . '/views/browse.template.php');
        $content = ob_get_clean();

        include_once(ROOT . '/views/layout.html.php');
    }

    /**
     *  Render stats page
     */
    private static function renderStats()
    {
        $mystats = new \Controllers\Stat();

        $repoError = 0;

        /**
         *  Récupération du snapshot et environnement transmis
         */
        if (empty($_GET['id'])) {
            $repoError++;
        } else {
            $envId = \Controllers\Common::validateData($_GET['id']);
        }

        /**
         *  Le repo transmis doit être un numéro car il s'agit de l'ID en BDD
         */
        if (!is_numeric($envId)) {
            $repoError++;
        }

        /**
         *  A partir de l'ID fourni, on récupère les infos du repo
         */
        if ($repoError == 0) {
            $myrepo = new \Controllers\Repo();
            $myrepo->setEnvId($envId);
            $myrepo->getAllById('', '', $envId);
        }

        /**
         *  Si un filtre a été sélectionné pour le graphique principal, la page est rechargée en arrière plan par jquery et récupère les données du graphique à partir du filtre sélectionné
         */
        if (!empty($_GET['repo_access_chart_filter'])) {
            if (\Controllers\Common::validateData($_GET['repo_access_chart_filter']) == "1week") {
                $repo_access_chart_filter = "1week";
            }
            if (\Controllers\Common::validateData($_GET['repo_access_chart_filter']) == "1month") {
                $repo_access_chart_filter = "1month";
            }
            if (\Controllers\Common::validateData($_GET['repo_access_chart_filter']) == "3months") {
                $repo_access_chart_filter = "3months";
            }
            if (\Controllers\Common::validateData($_GET['repo_access_chart_filter']) == "6months") {
                $repo_access_chart_filter = "6months";
            }
            if (\Controllers\Common::validateData($_GET['repo_access_chart_filter']) == "1year") {
                $repo_access_chart_filter = "1year";
            }
        }

        ob_start();
        include_once(ROOT . '/views/stats.template.php');
        $content = ob_get_clean();

        include_once(ROOT . '/views/layout.html.php');

        $mystats->closeConnection();
    }

    /**
     *  Render userspace page
     */
    private static function renderUserspace()
    {
        /**
         *  Update user personnal informations
         */
        if (!empty($_POST['action']) and $_POST['action'] == 'editPersonnalInfos') {
            $username = $_SESSION['username'];
            $firstName = '';
            $lastName = '';
            $email = '';

            /**
             *  Retrieving sended infos
             */

            /**
             *  First name
             */
            if (!empty($_POST['first_name'])) {
                $firstName = $_POST['first_name'];
            }

            /**
             *  Last name
             */
            if (!empty($_POST['last_name'])) {
                $lastName = $_POST['last_name'];
            }

            /**
             *  Email address
             */
            if (!empty($_POST['email'])) {
                $email = $_POST['email'];
            }

            /**
             *  Update in database
             */
            $mylogin = new \Controllers\Login();

            try {
                $mylogin->edit($username, $firstName, $lastName, $email);
                \Controllers\Common::printAlert('Changes have been taken into account', 'success');
            } catch (Exception $e) {
                \Controllers\Common::printAlert($e->getMessage(), 'error');
            }
        }

        /**
         *  Changing user password
         */
        if (!empty($_POST['action']) and $_POST['action'] == 'changePassword' and !empty($_POST['actual_password']) and !empty($_POST['new_password']) and !empty($_POST['new_password_retype'])) {
            $mylogin = new \Controllers\Login();

            try {
                $mylogin->changePassword($_SESSION['username'], $_POST['actual_password'], $_POST['new_password'], $_POST['new_password_retype']);
                \Controllers\Common::printAlert('Password has been changed', 'success');
            } catch (Exception $e) {
                \Controllers\Common::printAlert($e->getMessage(), 'error');
            }
        }

        ob_start();
        include_once(ROOT . '/views/userspace.template.php');
        $content = ob_get_clean();

        include_once(ROOT . '/views/layout.html.php');
    }

    /**
     *  Render history page
     */
    private static function renderHistory()
    {
        /**
         *  Only admin have access to this page
         */
        if (!IS_ADMIN) {
            header('Location: /');
            exit;
        }

        /**
         *  Cas où on souhaite filtrer par Id utilisateur
         */
        if (!empty($_POST['action']) and $_POST['action'] === "filterByUser" and !empty($_POST['userid'])) {
            $filterByUserId = \Controllers\Common::validateData($_POST['userid']);

            if (!is_numeric($filterByUserId)) {
                printAlert("User Id is invalid");
            } else {
                $filterByUser = "yes";
            }
        }

        /**
         *  Case it must be filtered by user
         */
        if (!empty($filterByUser) and $filterByUser == "yes") {
            $historyLines = \Models\History::getByUser($filterByUserId);
        } else {
            $historyLines = \Models\History::getAll();
        }

        /**
         *  Getting all usernames
         */
        $myusers = new \Controllers\Login();
        $users = $myusers->getUsers();

        ob_start();
        include_once(ROOT . '/views/history.template.php');
        $content = ob_get_clean();

        include_once(ROOT . '/views/layout.html.php');
    }

    /**
     *  Render login page
     */
    private static function renderLogin()
    {
        include_once(ROOT . '/views/login-layout.html.php');
    }

    /**
     *  Logout
     */
    private static function logout()
    {
        /**
         *  Destruction de la session en cours et redirection vers la page de login
         */

        /**
         *  On démarre la session
         */
        session_start();

        // Réinitialisation du tableau de session
        // On le vide intégralement
        $_SESSION = array();

        // If it's desired to kill the session, also delete the session cookie.
        // Note: This will destroy the session, and not just the session data!
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        }

        // Destruction de la session
        session_destroy();

        // Destruction du tableau de session
        unset($_SESSION);

        /**
         *  On redirige vers login
         */
        header('Location: /login');

        exit();
    }

    /**
     *  Render page not found using custom error pages
     */
    private static function renderNotfound()
    {
        include_once(ROOT . '/public/custom_errors/custom_404.html');
    }
}
