<?php

namespace Controllers;

use Exception;
use Datetime;

class Host
{
    protected $host_db; // BDD dédiée de l'hôte
    private $model;
    private $id;
    private $idArray = array();
    private $ip;
    private $hostname;
    private $os;
    private $os_version;
    private $os_family;
    private $type;
    private $kernel;
    private $arch;
    private $profile;
    private $env;
    private $authId;
    private $token;
    private $onlineStatus;

    /**
     *  Propriétés relatives aux paquets de l'hôte
     */
    private $packageId;
    private $packageName;
    private $packageVersion;

    public function __construct()
    {
        /**
         *  Ouverture de la base de données 'hosts' (repomanager-hosts.db)
         */
        $this->model = new \Models\Host();
    }

    public function setId(string $id)
    {
        $this->id = Common::validateData($id);
    }

    public function setIp(string $ip)
    {
        $this->ip = Common::validateData($ip);
    }

    public function setHostname(string $hostname)
    {
        $this->hostname = Common::validateData($hostname);
    }

    public function setOS(string $os)
    {
        $this->os = Common::validateData($os);
    }

    public function setOsVersion(string $os_version)
    {
        $this->os_version = Common::validateData($os_version);
    }

    public function setOsFamily(string $os_family)
    {
        $this->os_family = Common::validateData($os_family);
    }

    public function setType(string $type)
    {
        $this->type = Common::validateData($type);
    }

    public function setKernel(string $kernel)
    {
        $this->kernel = Common::validateData($kernel);
    }

    public function setArch(string $arch)
    {
        $this->arch = Common::validateData($arch);
    }

    public function setProfile(string $profile)
    {
        $this->profile = Common::validateData($profile);
    }

    public function setEnv(string $env)
    {
        $this->env = Common::validateData($env);
    }

    public function setPackageId(string $packageId)
    {
        $this->packageId = Common::validateData($packageId);
    }

    public function setPackageName(string $packageName)
    {
        $this->packageName = Common::validateData($packageName);
    }

    public function setPackageVersion(string $packageVersion)
    {
        $this->packageVersion = Common::validateData($packageVersion);
    }

    public function setAuthId(string $authId)
    {
        $this->authId = Common::validateData($authId);
    }

    public function setToken(string $token)
    {
        $this->token = Common::validateData($token);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getAuthId()
    {
        return $this->authId;
    }

    public function getToken()
    {
        return $this->token;
    }

    /**
     *  Récupération des paramètres généraux (table settings)
     */
    public function getSettings()
    {
        return $this->model->getSettings();
    }

    /**
     *  Récupère l'ID en BDD d'un hôte à partir de ses identifiants
     */
    public function getIdByAuth()
    {
        /**
         *  Récupération à partir d'un id d'hôte et du token
         */
        if (empty($this->authId)) {
            throw new Exception('Invalid authId identifier');
        }

        $id = $this->model->getIdByAuth($this->authId);

        if (empty($id)) {
            throw new Exception("No host Id has been found from this authId identifier");
        }

        return $id;
    }

    /**
     *  Récupère toutes les informations de l'hôte à partir de son ID
     */
    public function getAll(string $id)
    {
        if (!is_numeric($id)) {
            Common::printAlert('Specified Id is invalid', 'error');
            return false;
        }

        return $this->model->getAllById($id);
    }

    /**
     *  Return hosts that have the specified kernel
     */
    public function getHostWithKernel(string $kernel)
    {
        return $this->model->getHostWithKernel(Common::validateData($kernel));
    }

    /**
     *  Return hosts that have the specified profile
     */
    public function getHostWithProfile(string $kernel)
    {
        return $this->model->getHostWithProfile(Common::validateData($kernel));
    }

    /**
     *  Récupère la liste des paquets présents sur l'hôte
     */
    public function getPackagesInventory()
    {
        return $this->model->getPackagesInventory();
    }

    /**
     *  Retourne les paquets installés sur l'hôte
     */
    public function getPackagesInstalled()
    {
        return $this->model->getPackagesInstalled();
    }

    /**
     *  Récupère la liste des paquets disponibles pour mise à jour sur l'hôte
     */
    public function getPackagesAvailable()
    {
        return $this->model->getPackagesAvailable();
    }

    /**
     *  Récupère la liste des mises à jour demandées par repomanager à l'hôte
     */
    public function getUpdatesRequests()
    {
        return $this->model->getUpdatesRequests();
    }

    /**
     *  Récupère les informations de toutes les actions effectuées sur les paquets de l'hôte (installation, mise à jour, désinstallation...)
     */
    public function getEventsHistory()
    {
        return $this->model->getEventsHistory();
    }

    /**
     *  Récupère la liste des paquets issus d'un évènemnt et dont l'état des paquets est défini par $packageState (installed, upgraded, removed)
     *  Les informations sont récupérées à la fois dans la table packages et dans packages_history
     */
    public function getEventPackagesList(string $eventId, string $packageState)
    {
        return $this->model->getEventPackagesList($eventId, $packageState);
    }

    /**
     *  Récupère le détails d'un évènement sur un type de paquets en particulier (installés, mis à jour, etc...)
     *  Cette fonction est notamment déclenchée au passage de la souris sur une ligne de l'historique des évènements
     */
    public function getEventDetails(string $id, string $eventId, string $packageState)
    {
        /**
         *  Si il manque l'id de l'hôte, on quitte car on en a besoin pour ouvrir sa BDD dédiée
         */
        if (empty($id)) {
            throw new Exception('Host Id must be specified');
        }

        $packageState = Common::validateData($packageState);

        /**
         *  Ouverture de la BDD dédiée de l'hôte
         */
        $this->model->openHostDb($id);

        $packages = $this->model->getEventDetails($eventId, $packageState);

        if ($packageState == 'installed') {
            $content = '<p><b>Installed:</b></p>';
        }
        if ($packageState == 'dep-installed') {
            $content = '<p><b>Installed dependencies:</b></p>';
        }
        if ($packageState == 'upgraded') {
            $content = '<p><b>Updated:</b></p>';
        }
        if ($packageState == 'removed') {
            $content = '<p><b>Uninstalled:</b></p>';
        }
        if ($packageState == 'downgraded') {
            $content = '<p><b>Downgraded:</b></p>';
        }

        $content .= '<div class="grid grid-2 column-gap-10 row-gap-6 justify-space-between">';

        foreach ($packages as $package) {
            $content .= '<div class="flex align-item-center min-width-200">';
            $content .= Common::printProductIcon($package['Name']);
            $content .= '<span class="copy">' . $package['Name'] . '</span>';
            $content .= '</div>';
            $content .= '<span class="copy">' . $package['Version'] . '</span>';
        }

        $content .= '</div>';

        return $content;
    }

    /**
     *  Retrieve the complete history of a package (its installation, its updates, etc...)
     */
    public function getPackageTimeline(string $id, string $packageName)
    {
        /**
         *  If the host id is missing, we quit because we need it to open its dedicated DB
         */
        if (empty($id)) {
            throw new Exception('Host Id must be specified');
        }

        /**
         *  Open the dedicated DB of the host
         */
        $this->model->openHostDb($id);

        $events = $this->model->getPackageTimeline($packageName);

        /**
         *  Build the timeline to display and send it back to the ajax controller because it is jquery that will take care of displaying it afterwards
         */
        $content = '<h4>' . strtoupper($packageName) . ' PACKAGE HISTORY</h4>';
        $content .= '<div class="timeline">';

        /**
         *  The first block will be displayed on the left in the timeline
         */
        $contentPosition = 'left';

        foreach ($events as $event) {
            /**
             *  Add the date, time and state of the package
             */
            if ($event['State'] == "inventored") {
                $contentIcon = 'package';
                $contentText = 'Inventored';
            }
            if ($event['State'] == "installed") {
                $contentIcon = 'down';
                $contentText = 'Installed';
            }
            if ($event['State'] == "dep-installed") {
                $contentIcon = 'down';
                $contentText = 'Installed (as depencency)';
            }
            if ($event['State'] == "upgraded") {
                $contentIcon = 'update';
                $contentText = 'Updated';
            }
            if ($event['State'] == "removed") {
                $contentIcon = 'delete';
                $contentText = 'Uninstalled';
            }
            if ($event['State'] == "downgraded") {
                $contentIcon = 'arrow-back';
                $contentText = 'Downgraded';
            }
            if ($event['State'] == "reinstalled") {
                $contentIcon = 'down';
                $contentText = 'Reinstalled';
            }
            if ($event['State'] == "purged") {
                $contentIcon = 'delete';
                $contentText = 'Purged';
            }

            /**
             *  Position of the container block in the timeline according to the last displayed
             */
            if ($contentPosition == 'left') {
                $content .= '<div class="timeline-container timeline-container-left">';
            }
            if ($contentPosition == 'right') {
                $content .= '<div class="timeline-container timeline-container-right">';
            }

            $content .= '<div class="timeline-container-content">';
            $content .= '<span class="timeline-event-date">' . DateTime::createFromFormat('Y-m-d', $event['Date'])->format('d-m-Y') . ' ' . $event['Time'] . '</span>';
            $content .= '<div class="flex align-item-center">';
            $content .= '<img src="/assets/icons/' . $contentIcon . '.svg" class="icon" />';
            /**
             *  If this event is the result of an update, install or uninstall event, then we indicate the Id of the event
             */
            // if (!empty($event['Id_event'])) {
            //     $content .= '<a href="#' . $event['Id_event'] . '" >';
            // }
            $content .= '<span>' . $contentText . '</span>';
            $content .= '</div>';

            $content .= '<span class="timeline-event-version">Version : <b>' . $event['Version'] . '</b></span>';
            $content .= '</div>';

            /**
             *  If the previous block was on the left, we display the next one on the right and vice versa
             */
            if ($contentPosition == "left") {
                $contentPosition = 'right';
            } elseif ($contentPosition == "right") {
                $contentPosition = 'left';
            }

            $content .= '</div>';
        }

        /**
         *  Close the timeline
         */
        $content .= '</div>';

        return $content;
    }

    /**
     *  Récupère les informations concernant la dernière requête de mise à jour envoyée à l'hôte
     */
    public function getLastUpdateStatus()
    {
        $datas = $this->model->getLastUpdateStatus();

        /**
         *  Parmis les deux arrays récupérées, on tri par date pour avoir le + récent en haut
         */
        array_multisort(array_column($datas, 'Date'), SORT_DESC, array_column($datas, 'Time'), SORT_DESC, $datas);

        /**
         *  On ne retourne uniquement le 1er array (le plus récent)
         */
        if (!empty($datas[0])) {
            return $datas[0];
        }

        return '';
    }

    /**
     *  Récupère le status de la dernière demande de mise à jour de l'hôte
     */
    public function getLastRequestedUpdateStatus()
    {
        return $this->model->getLastRequestedUpdateStatus();
    }

    /**
     *  Compte le nombre de paquets installés, mis à jour, désinstallés... au cours des X derniers jours.
     *  Retourne un array contenant les dates => nombre de paquet
     *  Fonction utilisées notamment pour la création du graphique ChrtJS de type 'line' sur la page d'un hôte
     */
    public function getLastPackagesStatusCount(string $status, string $days)
    {
        if ($status != 'installed' and $status != 'upgraded' and $status != 'removed') {
            throw new Exception("Invalid status");
        }

        $dateEnd   = date('Y-m-d');
        $dateStart = date_create($dateEnd)->modify("-${days} days")->format('Y-m-d');

        return $this->model->getLastPackagesStatusCount($status, $dateStart, $dateEnd);
    }

    /**
     *  Modifie les paramètres d'affichage sur la page des hotes
     */
    public function setSettings(string $pkgs_considered_outdated, string $pkgs_considered_critical)
    {
        /**
         *  Les paramètres suivants doivent être des chiffres
         */
        if (!is_numeric($pkgs_considered_outdated) or !is_numeric($pkgs_considered_critical)) {
            Common::printAlert('Params must be numeric', 'error');
            return;
        }

        /**
         *  Les paramètres doivent être supérieurs à 0
         */
        if ($pkgs_considered_outdated <= 0 or $pkgs_considered_critical <= 0) {
            Common::printAlert('Params must be greater than or equal to 0', 'error');
            return;
        }

        $this->model->setSettings($pkgs_considered_outdated, $pkgs_considered_critical);

        Common::printAlert('Params have been taken into account', 'success');
    }

    /**
     *  Ajout d'un état de paquet en BDD
     */
    public function setPackageState(string $name, string $version, string $state, string $date, string $time, string $id_event = null)
    {
        /**
         *  Insertion en BDD
         *  Si le paquet existe déjà en BDD, on le mets à jour
         */
        if ($this->model->packageExists($name) === true) {
            /**
             *  D'abord on fait une copie de l'état actuel du paquet dans packages_history afin de conserver un suivi.
             *
             *  Récupération de l'Id du paquet au préalable
             */
            $packageId = $this->model->getPackageId($name);

            /**
             *  Sauvegarde de l'état actuel
             */
            $this->setPackageHistory($packageId);

            /**
             *  Puis on met à jour l'état du paquet et sa version en base par les infos qui ont été transmises
             */
            $this->model->setPackageState($name, $version, $state, $date, $time, $id_event);
        } else {
            /**
             *  Si le paquet n'existe pas on l'ajoute en BDD directement dans l'état spécifié (installed, upgraded, removed...)
             */
            $this->model->addPackage($name, $version, $state, 'package', $date, $time, $id_event);
        }

        /**
         *  Enfin si le paquet et sa version était présent dans packages_available on le retire
         */
        $this->model->deletePackageAvailable($name, $version);
    }

    /**
     *  Copie l'état actuel d'un paquet de la table packages vers la table packages_history afin de conserver une trace de cet état
     */
    private function setPackageHistory(string $packageId)
    {
        /**
         *  Récupération de toutes les infos concernant le paquet dans son état actuel
         */
        $data = $this->model->getPackageInfo($packageId);

        if (!empty($data['Name'])) {
            $packageName = $data['Name'];
        }
        if (!empty($data['Version'])) {
            $packageVersion = $data['Version'];
        }
        if (!empty($data['State'])) {
            $packageState = $data['State'];
        }
        if (!empty($data['Type'])) {
            $packageType = $data['Type'];
        }
        if (!empty($data['Date'])) {
            $packageDate = $data['Date'];
        }
        if (!empty($data['Time'])) {
            $packageTime = $data['Time'];
        }
        if (!empty($data['Id_event'])) {
            $package_id_event = $data['Id_event'];
        } else {
            $package_id_event = '';
        }

        /**
         *  Puis on copie cet état dans la table packages_history
         */
        $this->model->setPackageHistory($packageName, $packageVersion, $packageState, $packageType, $packageDate, $packageTime, $package_id_event);

        return true;
    }

    /**
     *  Mise à jour de l'inventaire des paquets installés sur l'hôte en BDD
     */
    public function setPackagesInventory(string $packagesInventory)
    {
        /**
         *  Si la liste des paquets est vide, on ne peut pas continuer
         */
        if (empty($packagesInventory)) {
            throw new Exception('Packages list is empty');
        }

        /**
         *  Si l'Id de l'hôte en BDD est vide, on ne peut pas continuer (utile pour ouvrir sa BDD)
         */
        if (empty($this->id)) {
            throw new Exception('Host Id is empty');
        }

        /**
         *  Les paquets sont transmis sous forme de chaine, séparés par une virgule. On explode cette chaine en array et on retire les entrées vides.
         */
        $packagesList = array_filter(explode(",", Common::validateData($packagesInventory)));

        /**
         *  On traite si l'array n'est pas vide
         */
        if (!empty($packagesList)) {
            /**
             *  Ouverture de la BDD dédiée de l'hôte
             */
            $this->openHostDb($this->id);

            foreach ($packagesList as $packageDetails) {
                /**
                 *  Chaque ligne contient le nom du paquet, sa version et sa description séparés par un | (ex : nginx|xxx-xxxx|nginx description)
                 */
                $packageDetails = explode('|', $packageDetails);

                /**
                 *  Récupération du nom du paquet, si celui-ci est vide alors on passe au suivant
                 */
                if (empty($packageDetails[0])) {
                    continue;
                }
                $this->setPackageName($packageDetails[0]);

                /**
                 *  Version du paquet
                 */
                if (!empty($packageDetails[1])) {
                    $this->setPackageVersion($packageDetails[1]);
                } else {
                    $this->setPackageVersion('unknow');
                }

                /**
                 *  Insertion en BDD
                 *  On vérifie d'abord si le paquet (son nom) existe en BDD
                 */
                if ($this->model->packageExists($this->packageName) === false) {
                    /**
                     *  Si il n'existe pas on l'ajoute en BDD (sinon on ne fait rien)
                     */
                    $this->model->addPackage($this->packageName, $this->packageVersion, 'inventored', 'package', date('Y-m-d'), date('H:i:s'));
                } else {
                    /**
                     *  Si le paquet existe, on va effectuer des actions différentes selon son état en BDD
                     */

                    /**
                     *  D'abord on récupère l'état actuel du paquet en base de données
                     */
                    $packageState = $this->model->getPackageState($this->packageName);

                    /**
                     *  Si le paquet est en état 'installed' ou 'inventored', on ne fait rien
                     *
                     *  En revanche, si le paquet est en état 'removed' ou 'upgraded', on met à jour les informations en base de données
                     */
                    if ($packageState == 'removed') {
                        /**
                         *  Ajout du paquet en base de données en état 'inventored'
                         */
                        $this->setPackageState($this->packageName, $this->packageVersion, 'inventored', date('Y-m-d'), date('H:i:s'));
                    }
                }
            }

            /**
             *  Fermeture de la BDD
             */
            $this->closeHostDb();
        }

        return true;
    }

    /**
     *  Mise à jour de l'état des paquets disponibles (à mettre à jour) sur l'hôte en BDD
     */
    public function setPackagesAvailable(string $packagesAvailable)
    {
        /**
         *  Si la liste des paquets est vide, on ne peut pas continuer
         */
        if (empty($packagesAvailable)) {
            throw new Exception('Packages list is empty');
        }

        /**
         *  Si l'Id de l'hôte en BDD est vide, on ne peut pas continuer (utile pour ouvrir sa BDD)
         */
        if (empty($this->id)) {
            throw new Exception('Host Id is empty');
        }

        /**
         *  2 possibilités :
         *  - soit on a transmis "none", ce qui signifie qu'il n'y a aucun paquet disponible sur l'hôte
         *  - soit on a transmis une liste de paquets séparés par une virgule
         */
        if ($packagesAvailable == "none") {
            $packagesList = "none";
        } else {
            /**
             *  Les paquets sont transmis sous forme de chaine, séparés par une virgule. On explode cette chaine en array et on retire les entrées vides.
             */
            $packagesList = array_filter(explode(",", Common::validateData($packagesAvailable)));
        }

        /**
         *  On traite si l'array n'est pas vide
         */
        if (!empty($packagesList)) {

            /**
             *  Ouverture de la BDD dédiée de l'hôte
             */
            $this->openHostDb($this->id);

            /**
             *  On efface la liste des paquets actuellement dans packages_available
             */
            $this->model->cleanPackageAvailableTable();

            /**
             *  Si l'hôte a transmis "none" (aucun paquet disponible pour mise à jour) alors on s'arrête là
             */
            if ($packagesList == "none") {
                return;
            }

            foreach ($packagesList as $packageDetails) {
                /**
                 *  Chaque ligne contient le nom du paquet, sa version et sa description séparés par un | (ex : nginx|xxx-xxxx|nginx description)
                 */
                $packageDetails = explode('|', $packageDetails);

                /**
                 *  Récupération du nom du paquet, si celui-ci est vide alors on passe au suivant
                 */
                if (empty($packageDetails[0])) {
                    continue;
                }
                $this->setPackageName($packageDetails[0]);

                /**
                 *  Version du paquet
                 */
                if (!empty($packageDetails[1])) {
                    $this->setPackageVersion($packageDetails[1]);
                } else {
                    $this->setPackageVersion('unknow');
                }

                /**
                 *  Si le paquet existe déjà dans packages_available alors on le met à jour (la version a peut être changée)
                 */
                if ($this->model->packageAvailableExists($this->packageName) === true) {
                    /**
                     *  Si il existe en BDD, on vérifie aussi la version présente en BDD.
                     *  Si la version en BDD est différente alors on met à jour le paquet en BDD, sinon on ne fait rien.
                     */
                    if ($this->model->packageVersionAvailableExists($this->packageName, $this->packageVersion) === true) {
                        $this->model->updatePackageAvailable($this->packageName, $this->packageVersion);
                    }
                } else {
                    /**
                     *  Si le paquet n'existe pas on l'ajoute en BDD
                     */
                    $this->model->addPackageAvailable($this->packageName, $this->packageVersion);
                }
            }
        }

        return true;
    }

    /**
     *  Ajout de l'historique des évènements relatifs aux paquets (installation, mise à jour, etc...) d'un hôte en base de données
     */
    public function setEventsFullHistory(array $history)
    {
        /**
         *  Si il manque l'id de l'hôte, on quitte car on en a besoin pour ouvrir sa BDD dédiée
         */
        if (empty($this->id)) {
            throw new Exception('Host Id is empty');
        }

        /**
         *  Ouverture de la BDD dédiée de l'hôte
         */
        $this->openHostDb($this->id);

        /**
         *  Chaque évènement est constitué d'une date et heure de début et de fin
         *  Puis d'une liste de paquets installés, mis à jour ou désinstallé..
         *  Exemple :
         *  "date_start":"2021-12-07",
         *  "date_end":"2021-12-07",
         *  "time_start":"17:32:45",
         *  "time_end":"17:34:49",
         *  "upgraded":[
         *    {
         *      "name":"bluez",
         *      "version":"5.48-0ubuntu3.5"
         *    }
         *  ]
         */

        foreach ($history as $event) {
            $event->date_start;
            $event->date_end;
            $event->time_start;
            $event->time_end;

            /**
             *  On vérifie qu'un évènement de la même date et de la même heure n'existe pas déjà, sinon on l'ignore et on passe au suivant
             */
            if ($this->model->eventExists($event->date_start, $event->time_start) === true) {
                continue;
            }

            /**
             *  Insertion de l'évènement en base de données
             */
            $this->model->addEvent($event->date_start, $event->date_end, $event->time_start, $event->time_end);

            /**
             *  Récupération de l'Id inséré en BDD
             */
            $id_event = $this->model->getHostLastInsertRowID();

            /**
             *  Si l'évènement comporte des paquets installés
             */
            if (!empty($event->installed)) {
                foreach ($event->installed as $package_installed) {
                    $this->setPackageState($package_installed->name, $package_installed->version, 'installed', $event->date_start, $event->time_start, $id_event);
                }
            }
            /**
             *  Si l'évènement comporte des dépendances installées
             */
            if (!empty($event->dep_installed)) {
                foreach ($event->dep_installed as $dep_installed) {
                    $this->setPackageState($dep_installed->name, $dep_installed->version, 'dep-installed', $event->date_start, $event->time_start, $id_event);
                }
            }
            /**
             *  Si l'évènement comporte des paquets mis à jour
             */
            if (!empty($event->upgraded)) {
                foreach ($event->upgraded as $package_upgraded) {
                    $this->setPackageState($package_upgraded->name, $package_upgraded->version, 'upgraded', $event->date_start, $event->time_start, $id_event);
                }
            }
            /**
             *  Si l'évènement comporte des paquets désinstallés
             */
            if (!empty($event->removed)) {
                foreach ($event->removed as $package_removed) {
                    $this->setPackageState($package_removed->name, $package_removed->version, 'removed', $event->date_start, $event->time_start, $id_event);
                }
            }
            /**
             *  Si l'évènement comporte des paquets rétrogradés
             */
            if (!empty($event->downgraded)) {
                foreach ($event->downgraded as $package_downgraded) {
                    $this->setPackageState($package_downgraded->name, $package_downgraded->version, 'downgraded', $event->date_start, $event->time_start, $id_event);
                }
            }
            /**
             *  Si l'évènement comporte des paquets réinstallés
             */
            if (!empty($event->reinstalled)) {
                foreach ($event->reinstalled as $package_reinstalled) {
                    $this->setPackageState($package_reinstalled->name, $package_reinstalled->version, 'reinstalled', $event->date_start, $event->time_start, $id_event);
                }
            }
            /**
             *  Si l'évènement comporte des paquets purgés
             */
            if (!empty($event->purged)) {
                foreach ($event->purged as $package_purged) {
                    $this->setPackageState($package_purged->name, $package_purged->version, 'purged', $event->date_start, $event->time_start, $id_event);
                }
            }
        }

        return true;
    }

    /**
     *  Vérifie si l'Ip existe en BDD parmis les hôtes actifs
     */
    private function ipExists(string $ip)
    {
        return $this->model->ipExists($ip);
    }

    /**
     *  Vérifie que le couple ID/token est valide
     */
    public function checkIdToken(string $authId, string $token)
    {
        /**
         *  Si l'ID ou le token est manquant alors on quittes
         */
        if (empty($authId) or empty($token)) {
            return false;
        }

        return $this->model->checkIdToken($authId, $token);
    }

    /**
     *  Retourne la liste de tous les hôtes d'un groupe
     */
    public function listByGroup(string $groupName)
    {
        return $this->model->listByGroup($groupName);
    }

    /**
     *  Liste tous les hôtes
     */
    public function listAll(string $status = 'active')
    {
        return $this->model->listAll($status);
    }

    /**
     *  Fonction qui liste tous les noms d'OS référencés en les comptant
     *  Retourne le nom des Os et leur nombre
     */
    public function listCountOS()
    {
        return $this->model->listCountOS();
    }

    /**
     *  Fonction qui liste tous les kernel d'hôtes référencés en les comptant
     *  Retourne la version des kernels et leur nombre
     */
    public function listCountKernel()
    {
        return $this->model->listCountKernel();
    }

    /**
     *  Fonction qui liste tous les arch d'hôtes référencés en les comptant
     *  Retourne la version des arch et leur nombre
     */
    public function listCountArch()
    {
        return $this->model->listCountArch();
    }

    /**
     *  Fonction qui liste tous les env d'hôtes référencés en les comptant
     *  Retourne le nom des env et leur nombre
     */
    public function listCountEnv()
    {
        return $this->model->listCountEnv();
    }

    /**
     *  Fonction qui liste tous les profils d'hôtes référencés en les comptant
     *  Retourne le nom des profils et leur nombre
     */
    public function listCountProfile()
    {
        return $this->model->listCountProfile();
    }

    /**
     *  List all hosts agent status and count them
     */
    public function listCountAgentStatus()
    {
        return $this->model->listCountAgentStatus();
    }

    /**
     *  List all hosts agent release version and count them
     *  Returns agent version and total
     */
    public function listCountAgentVersion()
    {
        return $this->model->listCountAgentVersion();
    }

    /**
     *  List all hosts that require a reboot
     */
    public function listRebootRequired()
    {
        return $this->model->listRebootRequired();
    }

    /**
     *  Retourne le nombre d'hôtes utilisant le profil spécifié
     */
    public function countByProfile(string $profile)
    {
        return $this->model->countByProfile($profile);
    }

    /**
     *  Ouverture de la BDD dédiée de l'hôte si ce n'est pas déjà fait
     *  Fournir l'id de l'hôte et le mode d'ouverture de la base (ro = lecture seule / rw = lecture-écriture)
     */
    public function openHostDb(string $hostId)
    {
        $this->model->getConnection('host', $hostId);
    }

    /**
     *  Fermeture de la BDD dédiée de l'hôte
     */
    public function closeHostDb()
    {
        $this->model->closeHostDb();
    }

    /**
     *  Ajoute un nouvel hôte en BDD
     *  Depuis l'interface web ou depuis l'API
     */
    public function register()
    {
        /**
         *  Lorsque appelé par l'api, HOSTS_DIR n'est pas setté, donc on le fait
         */
        if (!defined('HOSTS_DIR')) {
            define('HOSTS_DIR', ROOT . '/hosts');
        }

        /**
         *  Si on n'a pas renseigné l'IP ou le hostname alors on quitte
         */
        if (empty($this->ip) or empty($this->hostname)) {
            throw new Exception('You must provide IP address and hostname.');
        }

        /**
         *  On vérifie que le hostname n'existe pas déjà en base de données
         *  Si le hostname existe, on vérifie l'état de l'hôte :
         *   - si celui-ci est 'deleted' alors on peut le réactiver
         *   - si celui-ci est 'active' alors on ne peut pas enregistrer de nouveau cet hôte
         */
        if ($this->model->hostnameExists($this->hostname) === true) {
            /**
             *  On récupère l'état de l'hôte en base de données
             */
            $status = $this->model->getHostStatus($this->hostname);

            if (empty($status)) {
                throw new Exception('Server has encountered error while retrieving host status in database');
            }

            /**
             *  Si l'hôte en base de données est 'active' alors on ne peut pas l'enregistrer de nouveau
             */
            if ($status == 'active') {
                throw new Exception('Host is already registered.');
            }

            /**
             *  Sinon si l'hôte n'est pas 'actif', on set cette variable à 'yes' afin qu'il soit réactivé
             */
            $host_exists_and_is_unactive = 'yes';
        }

        /**
         *  Generate a new authId for this host
         *  This authId will be used to authenticate the host when it will try to connect to the API
         *  It must be unique so loop until we find a unique authId
         */
        $this->authId = 'id_' . bin2hex(openssl_random_pseudo_bytes(16));

        /**
         *  It must be unique so loop until we find a unique authId
         *  We check if an host exist with the same authId
         */
        while (!empty($this->model->getIdByAuth($this->authId))) {
            $this->authId = 'id_' . bin2hex(openssl_random_pseudo_bytes(16));
        }

        /**
         *  Generate a new token for this host
         */
        $this->token = bin2hex(openssl_random_pseudo_bytes(16));

        /**
         *  The agent status is set to 'unknow' when we register a new host for the first time
         */
        $this->onlineStatus = 'unknow';

        /**
         *  Ajout en BDD
         *  Si il s'agit d'un nouvel enregistrement, on ajoute l'hôte en base de données
         *  Si l'hôte existe déjà en base de données mais est inactif alors on le réactive et on met à jour ses données
         */
        /**
         *  Cas où l'hôte existe déjà et qu'il faut le réactiver
         *  On met à jour ses données (réactivation et nouvel id et token)
         */
        if (!empty($host_exists_and_is_unactive) and $host_exists_and_is_unactive == 'yes') {
            /**
             *  Mise à jour de l'hôte en base de données
             */
            $this->model->updateHost($this->ip, $this->hostname, $this->authId, $this->token, $this->onlineStatus, date('Y-m-d'), date('H:i:s'));

        /**
         *  Cas où on ajoute l'hôte en base de données
         */
        } else {
            /**
             *  Ajout de l'hôte en base de données
             */
            $this->model->addHost($this->ip, $this->hostname, $this->authId, $this->token, $this->onlineStatus, date('Y-m-d'), date('H:i:s'));

            /**
             *  Récupération de l'Id de l'hôte ajouté en BDD
             */
            $this->id = $this->model->getLastInsertRowID();

            /**
             *  Création d'un répertoire dédié pour cet hôte, à partir de son ID
             *  Sert à stocker des rapport de mise à jour et une BDD pour l'hôte
             */
            if (!mkdir(HOSTS_DIR . '/' . $this->id, 0770, true)) {
                throw new Exception('The server could not finalize registering.');
            }

            /**
             *  On effectue une première ouverture de la BDD dédiée à cet hôte afin de générer les tables
             */
            $this->openHostDb($this->id);
            $this->closeHostDb();

            /**
             *  Création d'un répertoire 'reports' pour cet hôte
             */
            if (!mkdir(HOSTS_DIR . '/' . $this->id . '/reports', 0770, true)) {
                throw new Exception('The server could not finalize registering.');
            }
        }

        return true;
    }

    /**
     *  Suppression d'un hôte depuis l'api
     */
    public function unregister()
    {
        /**
         *  Changement du status de l'hôte en base de données ('deleted')
         */
        $this->model->setHostInactive($this->id);
    }

    public function acknowledgeRequest(string $type, string $status)
    {
        $type = Common::validateData($type);
        $status = Common::validateData($status);

        /**
         *  On vérifie que l'action spécifiée par l'hôte est valide
         */
        if ($type != 'packages-update' and $type != 'general-status-update' and $type != 'packages-status-update' and $type != 'full-history-update') {
            throw new Exception('Invalid request type');
        }

        /**
         *  On vérifie que le status spécifié par l'hôte est valide
         */
        if ($status != 'running' and $status != 'done' and $status != 'error') {
            throw new Exception('Invalid request status');
        }

        /**
         *  Ouverture de la base de données de l'hôte
         */
        $this->model->openHostDb($this->id);

        $this->model->acknowledgeRequest($type, $status);

        return true;
    }

    /**
     *  Demande à un ou plusieurs hôte(s) d'exécuter une action
     *  - update
     *  - reset
     *  - delete
     */
    public function hostExec(array $hostsId, string $action)
    {
        /**
         *  On vérifie que l'action est valide
         */
        if (
            $action != 'delete'
            and $action != 'reset'
            and $action != 'update'
            and $action != 'general-status-update'
            and $action != 'packages-status-update'
        ) {
            throw new Exception('Action to execute is invalid');
        }

        $hostIdError                      = array();
        $hostUpdateError                  = array();
        $hostUpdateOK                     = array();
        $hostResetError                   = array();
        $hostResetOK                      = array();
        $hostDeleteError                  = array();
        $hostDeleteOK                     = array();
        $hostGeneralUpdateError           = array();
        $hostGeneralUpdateOK              = array();
        $hostPackagesStatusUpdateError    = array();
        $hostPackagesStatusUpdateOK       = array();

        /**
         *  On traite l'array contenant les Id d'hôtes à traiter
         */
        foreach ($hostsId as $hostId) {
            $this->setId(Common::validateData($hostId));

            /**
             *  Si l'Id de l'hôte n'est pas un chiffre, on enregistre son id dans $hostIdError[] puis on passe à l'hôte suivant
             */
            if (!is_numeric($this->id)) {
                $hostIdError[] = $this->id;
                continue;
            }

            /**
             *  D'abord on récupère l'IP et le hostname de l'hôte à traiter
             */
            $hostname = $this->model->getHostnameById($this->id);
            $ip = $this->model->getIpById($this->id);

            /**
             *  Si l'ip récupérée est vide, on passe à l'hôte suivant
             */
            if (empty($ip)) {
                continue;
            }
            $this->setIp($ip);

            if (!empty($hostname)) {
                $this->setHostname($hostname);
            }

            /**
             *  Ouverture de la base de données de l'hôte
             */
            $this->openHostDb($this->id);

            /**
             *  Cas où l'action demandée est une mise à jour
             */
            if ($action == 'update') {
                /**
                 *  Modification de l'état en BDD pour cet hôte (requested = demande envoyée, en attente)
                 */
                $this->model->addUpdateRequest('packages-update');

                /**
                 *  Envoi d'un ping avec le message 'r-update-pkgs' en hexadecimal pour ordonner à l'hôte de se mettre à jour
                 */
                exec("ping -W1 -c 1 -p 722d7570646174652d706b6773 $this->ip");

                /**
                 *  Si l'hôte a un Hostname, on le pousse dans l'array, sinon on pousse uniquement son adresse ip
                 */
                if (!empty($this->hostname)) {
                    $hostUpdateOK[] = array('ip' => $this->ip, 'hostname' => $this->hostname);
                } else {
                    $hostUpdateOK[] = array('ip' => $this->ip);
                }
            }

            /**
             *  Si l'action est un reset de l'hôte
             */
            if ($action == 'reset') {

                /**
                 *  Reset de certaines informations générales de l'hôte
                 */
                $this->model->resetHost($hostId);

                /**
                 *  Si l'hôte a un Hostname, on le pousse dans l'array, sinon on pousse uniquement son adresse ip
                 */
                if (!empty($this->hostname)) {
                    $hostResetOK[] = array('ip' => $this->ip, 'hostname' => $this->hostname);
                } else {
                    $hostResetOK[] = array('ip' => $this->ip);
                }
            }

            /**
             *  Si l'action est une suppression de l'hôte
             */
            if ($action == 'delete') {
                /**
                 *  Passage de l'hôte en état 'deleted' en BDD
                 */
                $this->model->setHostInactive($hostId);

                /**
                 *  Si l'hôte a un Hostname, on le pousse dans l'array, sinon on pousse uniquement son adresse ip
                 */
                if (!empty($this->hostname)) {
                    $hostDeleteOK[] = array('ip' => $this->ip, 'hostname' => $this->hostname);
                } else {
                    $hostDeleteOK[] = array('ip' => $this->ip);
                }
            }

            /**
             *  Si l'action correspond à l'une des suivantes, on ajoute une entrée dans la base de données de l'hôte
             */
            if (
                $action == 'general-status-update' or
                $action == 'packages-status-update'
            ) {

                /**
                 *  Modification de l'état en BDD pour cet hôte (requested = demande envoyée, en attente)
                 */
                $this->model->addUpdateRequest($action);
            }

            /**
             *  Si l'action est une demande de mise à jour des informations générales de l'hôte
             */
            if ($action == 'general-status-update') {
                /**
                 *  Envoi d'un ping avec le message 'r-general-status' en hexadecimal pour ordonner à l'hôte d'envoyer les informations
                 */
                exec("ping -W1 -c 1 -p 722d67656e6572616c2d737461747573 $this->ip");

                /**
                 *  Si l'hôte a un Hostname, on le pousse dans l'array, sinon on pousse uniquement son adresse ip
                 */
                if (!empty($this->hostname)) {
                    $hostGeneralUpdateOK[] = array('ip' => $this->ip, 'hostname' => $this->hostname);
                } else {
                    $hostGeneralUpdateOK[] = array('ip' => $this->ip);
                }
            }

            /**
             *  Si l'action est une demande de mise à jour des informations concernant les paquets sur l'hôte
             */
            if ($action == 'packages-status-update') {
                /**
                 *  Envoi d'un ping avec le message 'r-pkgs-status' en hexadecimal pour ordonner à l'hôte d'envoyer les informations
                 */
                exec("ping -W1 -c 1 -p 722d706b67732d737461747573 $this->ip");

                /**
                 *  Si l'hôte a un Hostname, on le pousse dans l'array, sinon on pousse uniquement son adresse ip
                 */
                if (!empty($this->hostname)) {
                    $hostPackagesStatusUpdateOK[] = array('ip' => $this->ip, 'hostname' => $this->hostname);
                } else {
                    $hostPackagesStatusUpdateOK[] = array('ip' => $this->ip);
                }
            }

            /**
             *  Clotûre de la base de données de l'hôte
             */
            $this->closeHostDb();
        }

        /**
         *  Génération d'un message de confirmation avec le nom/ip des hôtes sur lesquels l'action a été effectuée
         */
        $message = '';

        /**
         *  Génération des messages pour les hôtes dont l'id est invalide
         */
        if (!empty($hostIdError)) {
            $message .= "Following hosts Id are invalid:<br>";

            foreach ($hostIdError as $id) {
                $message .= $id . '<br>';
            }
        }

        /**
         *  Génération des messages pour une action de type 'update'
         */
        if (!empty($hostUpdateError)) {
            $message .= 'Update request has failed on the following hosts (unreachable) :<br>';

            foreach ($hostUpdateError as $host) {
                $message .= $host['hostname'] . ' (' . $host['ip'] . ')<br>';
            }
        }
        if (!empty($hostUpdateOK)) {
            $message .= 'Update request has been send to the following hosts:<br>';

            foreach ($hostUpdateOK as $host) {
                $message .= $host['hostname'] . ' (' . $host['ip'] . ')<br>';
            }
        }

        /**
         *  Génération des messages pour une action de type 'reset'
         */
        if (!empty($hostErrorError)) {
            $message .= 'Reset has failed for the following hosts:<br>';

            foreach ($hostErrorError as $host) {
                $message .= $host['hostname'] . ' (' . $host['ip'] . ')<br>';
            }
        }
        if (!empty($hostResetOK)) {
            $message .= 'Following hosts have been reseted:<br>';

            foreach ($hostResetOK as $host) {
                $message .= $host['hostname'] . ' (' . $host['ip'] . ')<br>';
            }
        }

        /**
         *  Génération des messages pour une action de type 'delete'
         */
        if (!empty($hostDeleteError)) {
            $message .= "Following hosts could not have been deleted:<br>";

            foreach ($hostDeleteError as $host) {
                $message .= $host['hostname'] . ' (' . $host['ip'] . ')<br>';
            }
        }
        if (!empty($hostDeleteOK)) {
            $message .= 'Following hosts have been deleted:<br>';

            foreach ($hostDeleteOK as $host) {
                $message .= $host['hostname'] . ' (' . $host['ip'] . ')<br>';
            }
        }

        /**
         *  Génération des messages pour une action de type 'general-status-update'
         */
        if (!empty($hostGeneralUpdateError)) {
            $message .= "Request has not been sent to the following host:<br>";

            foreach ($hostGeneralUpdateError as $host) {
                $message .= $host['hostname'] . ' (' . $host['ip'] . ')<br>';
            }
        }
        if (!empty($hostGeneralUpdateOK)) {
            $message .= 'Request has been sent to the following hosts:<br>';

            foreach ($hostGeneralUpdateOK as $host) {
                $message .= $host['hostname'] . ' (' . $host['ip'] . ')<br>';
            }
        }

        /**
         *  Génération des messages pour une action de type 'packages-status-update'
         */
        if (!empty($hostPackagesStatusUpdateError)) {
            $message .= "Request has not been sent to the following host:<br>";

            foreach ($hostPackagesStatusUpdateError as $host) {
                $message .= $host['hostname'] . ' (' . $host['ip'] . ')<br>';
            }
        }
        if (!empty($hostPackagesStatusUpdateOK)) {
            $message .= 'Request has been sent to the following hosts:<br>';

            foreach ($hostPackagesStatusUpdateOK as $host) {
                $message .= $host['hostname'] . ' (' . $host['ip'] . ')<br>';
            }
        }

        return $message;
    }

    /**
     *  Search hosts with specified package
     */
    public function getHostsWithPackage(array $hostsId, string $packageName)
    {
        $hosts = array();

        /**
         *  Si il manque l'id de l'hôte, on quitte car on en a besoin pour ouvrir sa BDD dédiée
         */
        if (empty($hostsId)) {
            throw new Exception("Host(s) Id must be specified");
        }
        if (!is_array($hostsId)) {
            throw new Exception("Host(s) Id must be an array");
        }

        /**
         *  On vérifie que le nom du paquet ne contient pas de caractères invalides
         */
        if (!Common::isAlphanumDash($packageName, array('*'))) {
            throw new Exception('Package name contains invalid characters');
        }

        foreach ($hostsId as $id) {
            /**
             *  Ouverture de la BDD dédiée de l'hôte
             */
            $this->model->openHostDb($id);
            $hosts[$id] = $this->model->getHostsWithPackage($packageName);
            $this->model->closeHostDb();
        }

        return $hosts;
    }

    /**
     *  Update hostname in database
     */
    public function updateHostname(string $hostname)
    {
        $this->model->updateHostname($this->id, Common::validateData($hostname));
    }

    /**
     *  Update OS in database
     */
    public function updateOS(string $os)
    {
        $this->model->updateOS($this->id, Common::validateData($os));
    }

    /**
     *  Update OS version in database
     */
    public function updateOsVersion(string $osVersion)
    {
        $this->model->updateOsVersion($this->id, Common::validateData($osVersion));
    }

    /**
     *  Update OS family in database
     */
    public function updateOsFamily(string $osFamily)
    {
        $this->model->updateOsFamily($this->id, Common::validateData($osFamily));
    }

    /**
     *  Update virtualization type in database
     */
    public function updateType(string $virtType)
    {
        $this->model->updateType($this->id, Common::validateData($virtType));
    }

    /**
     *  Update kernel version in database
     */
    public function updateKernel(string $kernel)
    {
        $this->model->updateKernel($this->id, Common::validateData($kernel));
    }

    /**
     *  Update arch in database
     */
    public function updateArch(string $arch)
    {
        $this->model->updateArch($this->id, Common::validateData($arch));
    }

    /**
     *  Update profile in database
     */
    public function updateProfile(string $profile)
    {
        $this->model->updateProfile($this->id, Common::validateData($profile));
    }

    /**
     *  Update environment in database
     */
    public function updateEnv(string $env)
    {
        $this->model->updateEnv($this->id, Common::validateData($env));
    }

    /**
     *  Update agent status in database
     */
    public function updateAgentStatus(string $status)
    {
        if ($status != 'running' and $status != 'stopped' and $status != 'disabled') {
            throw new Exception('Agent status is invalid');
        }

        $this->model->updateAgentStatus($this->id, $status);
    }

    /**
     *  Update host's linupdate version in database
     */
    public function updateLinupdateVersion(string $version)
    {
        $this->model->updateLinupdateVersion($this->id, Common::validateData($version));
    }

    /**
     *  Update host's reboot required status in database
     */
    public function updateRebootRequired(string $status)
    {
        if ($status != 'true' and $status != 'false') {
            throw new Exception('Reboot status is invalid');
        }

        $this->model->updateRebootRequired($this->id, $status);
    }

    /**
     *  Ajouter / supprimer des hôtes dans un groupe
     */
    public function addHostsIdToGroup(array $hostsId = null, int $groupId)
    {
        $mygroup = new \Controllers\Group('host');

        if (!empty($hostsId)) {
            foreach ($hostsId as $hostId) {
                /**
                 *  On vérifie que l'Id de l'hôte spécifié existe en base de données
                 */
                if ($this->model->existsId($hostId) === false) {
                    throw new Exception("Specified host <b>$hostId</b> Id does not exist");
                }

                /**
                 *  Ajout de l'hôte au groupe
                 */
                $this->model->addToGroup($hostId, $groupId);
            }
        }

        /**
         *  3. On récupère la liste des hôtes actuellement dans le groupe afin de supprimer ceux qui n'ont pas été sélectionnés
         */
        $actualHostsMembers = $mygroup->getHostsMembers($groupId);

        /**
         *  4. Parmis cette liste on ne récupère que les Id des repos actuellement membres
         */
        $actualHostsId = array();

        foreach ($actualHostsMembers as $actualHostsMember) {
            $actualHostsId[] = $actualHostsMember['Id'];
        }

        /**
         *  5. Enfin, on supprime tous les Id de repos actuellement membres qui n'ont pas été spécifiés par l'utilisateur
         */
        foreach ($actualHostsId as $actualHostId) {
            if (!in_array($actualHostId, $hostsId)) {
                $this->model->removeFromGroup($actualHostId, $groupId);
            }
        }
    }
}
