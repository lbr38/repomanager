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
        $myrepo = new Repo();

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
        if (PLANS_ENABLED == "true") {
            $plan = new Planification();
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
        $myplan = new Planification();
        $mygroup = new Group('repo');
        $myrepo = new Repo();
        $mylogin = new Login();

        /**
         *  Getting users email
         */
        $usersEmail = $mylogin->getEmails();

        /**
         *  Récupération de la liste des planifications en liste d'attente ou en cours d'exécution
         */
        $planQueueList = $myplan->listQueue();
        $planRunningList = $myplan->listRunning();
        $planDisabledList = $myplan->listDisabled();

        $planList = array_merge($planRunningList, $planQueueList, $planDisabledList);
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
        $group = new Group('host');
        $myhost = new Host();
        $mycolor = new Common();

        /**
         *  Case general hosts threshold settings form has been sent
         */
        if (!empty($_POST['settings-pkgs-considered-outdated']) and !empty($_POST['settings-pkgs-considered-critical'])) {
            $pkgs_considered_outdated = Common::validateData($_POST['settings-pkgs-considered-outdated']);
            $pkgs_considered_critical = Common::validateData($_POST['settings-pkgs-considered-critical']);

            $myhost = new Host();
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

        $myprofile = new Profile();
        $myrepo = new Repo();

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

        $mylogin = new Login();
        $users = $mylogin->getUsers();
        $usersEmail = $mylogin->getEmails();

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
            $opToStop = new Operation();
            $opToStop->kill(Common::validateData($_GET['stop'])); // $_GET['stop'] contient le pid de l'opération
        }

        /**
         *  Récupération du fichier de log à visualiser si passé en GET
         */
        $logfile = 'none';

        if (!empty($_GET['logfile'])) {
            $logfile = Common::validateData($_GET['logfile']);
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
        if (!empty($_POST['action']) and Common::validateData($_POST['action']) === 'reconstruct' and !empty($_POST['snapId'])) {
            $snapId = Common::validateData($_POST['snapId']);

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
            $myrepo = new Repo();
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

                $myop = new Operation();
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
            $snapId = Common::validateData($_GET['id']);
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
            $myrepo = new Repo();
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
        if (!empty($_POST['action']) and Common::validateData($_POST['action']) == 'uploadPackage' and !empty($_FILES['packages']) and $pathError === 0 and !empty($repoPath)) {
            /**
             *  On définit le chemin d'upload comme étant le répertoire my_uploaded_packages à l'intérieur du répertoire du repo
             */
            $targetDir = $repoPath . '/my_uploaded_packages';

            /**
             *  Si ce répertoire n'existe pas encore alors on le créé
             */
            if (!is_dir($targetDir)) {
                if (!mkdir($targetDir, 0770, true)) {
                    Common::printAlert("Error: cannot create upload directory <b>$target_dir</b>", 'error');
                    return;
                }
            }

            /**
             *  On ré-arrange la liste des fichiers transmis
             */
            $packages = Browse::reArrayFiles($_FILES['packages']);

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
                if (!Common::isAlphanumDash($packageName, array('.'))) {
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
                Common::printAlert('Files have been uploaded', 'success');
            } else {
                Common::printAlert('Some files have not been uploaded', 'error');
            }
        }

        /**
         *  Cas où on supprime un ou plusieurs paquets d'un repo
         */
        if (!empty($_POST['action']) and Common::validateData($_POST['action']) == 'deletePackages' and !empty($_POST['packageName']) and $pathError === 0 and !empty($repoPath)) {
            $packagesToDeleteNonExists = ''; // contiendra la liste des fichiers qui n'existent pas, si on tente de supprimer un fichier qui n'existe pas
            $packagesDeleted = array();

            foreach ($_POST['packageName'] as $packageToDelete) {
                $packageName = Common::validateData($packageToDelete);
                $packagePath = REPOS_DIR . '/' . $packageName;

                /**
                 *  Le nom du paquet ne doit pas contenir de caractères spéciaux
                 *  On autorise seulement les tirets et les underscores (voir fonction isAlphanumDash), ainsi qu'un caractère supplémentaire : le point (car les nom de paquet contiennent des points)
                 *  On autorise également le slash car le chemin du fichier transmis contient aussi le ou les sous-dossiers vers le paquet à partir de la racine du repo
                 */
                if (!Common::isAlphanumDash($packageName, array('.', '/', '+', '~'))) {
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

                    $deleteRepo = new Repo();
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
        $mystats = new Stat();

        $repoError = 0;

        /**
         *  Récupération du snapshot et environnement transmis
         */
        if (empty($_GET['id'])) {
            $repoError++;
        } else {
            $envId = Common::validateData($_GET['id']);
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
            $myrepo = new Repo();
            $myrepo->setEnvId($envId);
            $myrepo->getAllById('', '', $envId);
        }

        /**
         *  Si un filtre a été sélectionné pour le graphique principal, la page est rechargée en arrière plan par jquery et récupère les données du graphique à partir du filtre sélectionné
         */
        if (!empty($_GET['repo_access_chart_filter'])) {
            if (Common::validateData($_GET['repo_access_chart_filter']) == "1week") {
                $repo_access_chart_filter = "1week";
            }
            if (Common::validateData($_GET['repo_access_chart_filter']) == "1month") {
                $repo_access_chart_filter = "1month";
            }
            if (Common::validateData($_GET['repo_access_chart_filter']) == "3months") {
                $repo_access_chart_filter = "3months";
            }
            if (Common::validateData($_GET['repo_access_chart_filter']) == "6months") {
                $repo_access_chart_filter = "6months";
            }
            if (Common::validateData($_GET['repo_access_chart_filter']) == "1year") {
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
            $filterByUserId = Common::validateData($_POST['userid']);

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
        $myusers = new Login();
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
