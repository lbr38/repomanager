<?php

namespace Controllers;

use Exception;
use Datetime;

class Host
{
    protected $dedicatedDb;
    private $model;
    private $layoutContainerReloadController;
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
        $this->model = new \Models\Host();
        $this->layoutContainerReloadController = new \Controllers\Layout\ContainerReload();
    }

    public function setId(string $id)
    {
        $this->id = \Controllers\Common::validateData($id);
    }

    public function setIp(string $ip)
    {
        $this->ip = \Controllers\Common::validateData($ip);
    }

    public function setHostname(string $hostname)
    {
        $this->hostname = \Controllers\Common::validateData($hostname);
    }

    public function setOS(string $os)
    {
        $this->os = \Controllers\Common::validateData($os);
    }

    public function setOsVersion(string $os_version)
    {
        $this->os_version = \Controllers\Common::validateData($os_version);
    }

    public function setOsFamily(string $os_family)
    {
        $this->os_family = \Controllers\Common::validateData($os_family);
    }

    public function setType(string $type)
    {
        $this->type = \Controllers\Common::validateData($type);
    }

    public function setKernel(string $kernel)
    {
        $this->kernel = \Controllers\Common::validateData($kernel);
    }

    public function setArch(string $arch)
    {
        $this->arch = \Controllers\Common::validateData($arch);
    }

    public function setProfile(string $profile)
    {
        $this->profile = \Controllers\Common::validateData($profile);
    }

    public function setEnv(string $env)
    {
        $this->env = \Controllers\Common::validateData($env);
    }

    public function setPackageId(string $packageId)
    {
        $this->packageId = \Controllers\Common::validateData($packageId);
    }

    public function setPackageName(string $packageName)
    {
        $this->packageName = \Controllers\Common::validateData($packageName);
    }

    public function setPackageVersion(string $packageVersion)
    {
        $this->packageVersion = \Controllers\Common::validateData($packageVersion);
    }

    public function setAuthId(string $authId)
    {
        $this->authId = \Controllers\Common::validateData($authId);
    }

    public function setToken(string $token)
    {
        $this->token = \Controllers\Common::validateData($token);
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
    public function getIdByAuth(string $authId)
    {
        $id = $this->model->getIdByAuth($authId);

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
        return $this->model->getAllById($id);
    }

    /**
     *  Return the hostname of the host by its Id
     */
    public function getHostnameById(int $id)
    {
        return $this->model->getHostnameById($id);
    }

    /**
     *  Return hosts that have the specified kernel
     */
    public function getHostWithKernel(string $kernel)
    {
        return $this->model->getHostWithKernel($kernel);
    }

    /**
     *  Return hosts that have the specified profile
     */
    public function getHostWithProfile(string $profile)
    {
        return $this->model->getHostWithProfile($profile);
    }

    /**
     *  Retrieve the list of requests sent to the host
     *  It is possible to add an offset to the request
     */
    public function getRequests(int $id, bool $withOffset = false, int $offset = 0)
    {
        return $this->model->getRequests($id, $withOffset, $offset);
    }

    /**
     *  Return the last pending request sent to the host
     */
    public function getLastPendingRequest(int $id)
    {
        return $this->model->getLastPendingRequest($id);
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
     *  Edit the display settings on the hosts page
     */
    public function setSettings(string $packagesConsideredOutdated, string $packagesConsideredCritical)
    {
        if (!is_numeric($packagesConsideredOutdated) or !is_numeric($packagesConsideredCritical)) {
            throw new Exception('Parameters must be numeric');
        }

        /**
         *  Parameters must be greater than 0
         */
        if ($packagesConsideredOutdated <= 0 or $packagesConsideredCritical <= 0) {
            throw new Exception('Parameters must be greater than 0');
        }

        $this->model->setSettings($packagesConsideredOutdated, $packagesConsideredCritical);
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
        $packagesList = array_filter(explode(",", \Controllers\Common::validateData($packagesInventory)));

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
                    $this->setPackageVersion('unknown');
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
            $packagesList = array_filter(explode(",", \Controllers\Common::validateData($packagesAvailable)));
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
                    $this->setPackageVersion('unknown');
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
     *  Return true if the host Id exists in the database
     */
    public function existsId(int $id)
    {
        return $this->model->existsId($id);
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
        // if (!defined('HOSTS_DIR')) {
        //     define('HOSTS_DIR', DATA_DIR . '/hosts');
        // }

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
         *  The agent status is set to 'unknown' when we register a new host for the first time
         */
        $this->onlineStatus = 'unknown';

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
             *  Add the host in database
             */
            $this->model->add($this->ip, $this->hostname, $this->authId, $this->token, $this->onlineStatus, date('Y-m-d'), date('H:i:s'));

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
     *  Delete a host from the database
     */
    public function delete(int $id)
    {
        $hostRequestController = new \Controllers\Host\Request();

        /**
         *  Delete host from database
         */
        $this->model->delete($id);

        /**
         *  Add a new ws request to disconnect the host
         */
        $hostRequestController->new($id, 'disconnect');

        /**
         *  Delete host's dedicated database
         */
        if (is_dir(HOSTS_DIR . '/' . $id)) {
            \Controllers\Filesystem\Directory::deleteRecursive(HOSTS_DIR . '/' . $id);
        }

        unset($hostRequestController);
    }

    /**
     *  Ask one or more host(s) to execute an action
     */
    public function hostExec(array $hostsId, string $action)
    {
        $hostRequestController = new \Controllers\Host\Request();

        /**
         *  Only admins should be able to perform actions
         */
        if (!IS_ADMIN) {
            throw new Exception('You are not allowed to perform this action');
        }

        $validActions = ['reset', 'delete', 'request-general-infos', 'request-packages-infos'];

        /**
         *  Check if the action to execute is valid
         */
        if (!in_array($action, $validActions)) {
            throw new Exception('Action to execute is invalid');
        }

        $hostUpdateError               = array();
        $hostUpdateOK                  = array();
        $hostResetError                = array();
        $hostResetOK                   = array();
        $hostDeleteError               = array();
        $hostDeleteOK                  = array();
        $hostGeneralUpdateError        = array();
        $hostGeneralUpdateOK           = array();
        $hostPackagesStatusUpdateError = array();
        $hostPackagesStatusUpdateOK    = array();

        /**
         *  First check that hosts Id are valid
         */
        foreach ($hostsId as $hostId) {
            if (!is_numeric($hostId)) {
                throw new Exception('Invalid host Id: ' . $hostId);
            }
        }

        foreach ($hostsId as $hostId) {
            $hostId = \Controllers\Common::validateData($hostId);

            /**
             *  Retrieve the IP and hostname of the host to be processed
             */
            $hostname = $this->getHostnameById($hostId);
            $ip = $this->model->getIpById($hostId);

            /**
             *  If the retrieved ip is empty, we move on to the next host
             */
            if (empty($ip)) {
                continue;
            }

            $this->setIp($ip);
            if (!empty($hostname)) {
                $this->setHostname($hostname);
            }

            /**
             *  Open host database
             */
            $this->openHostDb($hostId);

            /**
             *  Case where the requested action is a reset
             */
            if ($action == 'reset') {
                /**
                 *  Reset host data in database
                 */
                $this->model->resetHost($hostId);
            }

            /**
             *  Case where the requested action is a delete
             */
            if ($action == 'delete') {
                /**
                 *  Set host status to 'deleted' in database
                 */
                $this->delete($hostId);
            }

            /**
             *  Case where the requested action is a general status update
             */
            if ($action == 'request-general-infos') {
                /**
                 *  Add a new websocket request in the database
                 */
                $hostRequestController->new($hostId, 'request-general-infos');
            }

            /**
             *  Case where the requested action is a packages status update
             */
            if ($action == 'request-packages-infos') {
                /**
                 *  Add a new websocket request in the database
                 */
                $hostRequestController->new($hostId, 'request-packages-infos');
            }

            /**
             *  If the host has a hostname, we push it in the array, otherwise we push only its ip
             */
            if (!empty($this->hostname)) {
                $hosts[] = array('ip' => $this->ip, 'hostname' => $this->hostname);
            } else {
                $hosts[] = array('ip' => $this->ip);
            }

            /**
             *  Close host database
             */
            $this->closeHostDb();
        }

        /**
         *  Generate a confirmation message with the name/ip of the hosts on which the action has been performed
         */
        if ($action == 'reset') {
            $message = 'Following hosts have been reseted:';
            $this->layoutContainerReloadController->reload('hosts/overview');
            $this->layoutContainerReloadController->reload('hosts/list');
            $this->layoutContainerReloadController->reload('host/summary');
            $this->layoutContainerReloadController->reload('host/packages');
            $this->layoutContainerReloadController->reload('host/history');
        }

        if ($action == 'delete') {
            $message = 'Following hosts have been deleted:';
            $this->layoutContainerReloadController->reload('hosts/overview');
            $this->layoutContainerReloadController->reload('hosts/list');
        }

        if ($action == 'request-all-packages-update') {
            $message = 'Requesting packages update to the following hosts:';
            $this->layoutContainerReloadController->reload('host/requests');
        }

        if ($action == 'request-general-infos') {
            $message = 'Requesting general informations to the following hosts:';
            $this->layoutContainerReloadController->reload('host/requests');
        }

        if ($action == 'request-packages-infos') {
            $message = 'Requesting packages informations to the following hosts:';
            $this->layoutContainerReloadController->reload('host/requests');
        }

        $message .= '<div class="grid grid-2 column-gap-10 row-gap-10 margin-top-5">';

        /**
         *  Print the hostname and ip of the hosts on which the action has been performed
         *  Do not print more than 10 hosts, print +X more if there are more than 10 hosts
         */
        $count = 1;
        foreach ($hosts as $host) {
            if ($count > 10) {
                $message .= '<p><b>+' . (count($hosts) - 10) . ' more</b></p>';
                break;
            }

            $message .= '<span class="label-white">' . $host['hostname'] . ' (' . $host['ip'] . ')</span> ';
            $count++;
        }

        $message .= '</div>';

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
        $this->model->updateHostname($this->id, \Controllers\Common::validateData($hostname));
    }

    /**
     *  Update OS in database
     */
    public function updateOS(string $os)
    {
        $this->model->updateOS($this->id, \Controllers\Common::validateData($os));
    }

    /**
     *  Update OS version in database
     */
    public function updateOsVersion(string $osVersion)
    {
        $this->model->updateOsVersion($this->id, \Controllers\Common::validateData($osVersion));
    }

    /**
     *  Update OS family in database
     */
    public function updateOsFamily(string $osFamily)
    {
        $this->model->updateOsFamily($this->id, \Controllers\Common::validateData($osFamily));
    }

    /**
     *  Update virtualization type in database
     */
    public function updateType(string $virtType)
    {
        $this->model->updateType($this->id, \Controllers\Common::validateData($virtType));
    }

    /**
     *  Update kernel version in database
     */
    public function updateKernel(string $kernel)
    {
        $this->model->updateKernel($this->id, \Controllers\Common::validateData($kernel));
    }

    /**
     *  Update arch in database
     */
    public function updateArch(string $arch)
    {
        $this->model->updateArch($this->id, \Controllers\Common::validateData($arch));
    }

    /**
     *  Update profile in database
     */
    public function updateProfile(string $profile)
    {
        $this->model->updateProfile($this->id, \Controllers\Common::validateData($profile));
    }

    /**
     *  Update environment in database
     */
    public function updateEnv(string $env)
    {
        $this->model->updateEnv($this->id, \Controllers\Common::validateData($env));
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
        $this->model->updateLinupdateVersion($this->id, \Controllers\Common::validateData($version));
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
                if ($this->existsId($hostId) === false) {
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
