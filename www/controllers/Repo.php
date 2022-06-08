<?php

namespace Controllers;

use Exception;
use Datetime;

class Repo
{
    public $model;
    private $op;
    private $repoId;
    private $snapId;
    private $envId;
    private $planId;
    private $name;
    private $source;
    private $packageType;
    private $dist;
    private $section;
    private $date;
    private $dateFormatted;
    private $time;
    private $env;
    private $description;
    private $signed; // yes ou no
    private $type; // miroir ou local
    private $status;
    private $sourceFullUrl;
    private $hostUrl;
    private $rootUrl;
    private $gpgCheck;
    private $gpgResign;

    private $targetName;
    private $targetDate;
    private $targetTime;
    private $targetEnv;
    private $targetGroup;
    private $targetDescription;
    private $targetGpgCheck;
    private $targetGpgResign;

    public function __construct()
    {
        $this->model = new \Models\Repo();
    }

    public function setRepoId(string $id)
    {
        $this->repoId = \Models\Common::validateData($id);
    }

    public function setSnapId(string $id)
    {
        $this->snapId = \Models\Common::validateData($id);
    }

    public function setEnvId(string $id)
    {
        $this->envId = \Models\Common::validateData($id);
    }

    public function setPlanId(string $id)
    {
        $this->planId = \Models\Common::validateData($id);
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function setDist(string $dist)
    {
        $this->dist = $dist;
    }

    public function setSection(string $section)
    {
        $this->section = $section;
    }

    public function setEnv(string $env)
    {
        $this->env = $env;
    }

    public function setDate(string $date)
    {
        $this->date = $date;
        $this->dateFormatted = DateTime::createFromFormat('Y-m-d', $date)->format('d-m-Y');
    }

    public function setTargetDate(string $date)
    {
        $this->targetDate = $date;
        $this->targetDateFormatted = DateTime::createFromFormat('Y-m-d', $date)->format('d-m-Y');
    }

    public function setTime(string $time)
    {
        $this->time = $time;
    }

    public function setTargetTime(string $time)
    {
        $this->targetTime = $time;
    }

    public function setType(string $type)
    {
        $this->type = $type;
    }

    public function setSigned(string $signed)
    {
        $this->signed = $signed;
    }

    public function setStatus(string $status)
    {
        $this->status = $status;
    }

    public function setDescription($description = '')
    {
        if ($description == 'nodescription') {
            $description = '';
        }

        $this->description = \Models\Common::validateData($description);
    }

    public function setSource(string $source)
    {
        $this->source = $source;
    }

    public function setSourceFullUrl(string $fullUrl)
    {
        $this->sourceFullUrl = $fullUrl;
    }

    public function setSourceHostUrl(string $hostUrl)
    {
        $this->hostUrl = $hostUrl;
    }

    public function setSourceRoot(string $root)
    {
        $this->rootUrl = $root;
    }

    public function setPackageType(string $type)
    {
        $this->packageType = $type;
    }

    public function setTargetName(string $name)
    {
        $this->targetName = $name;
    }

    public function setTargetEnv(string $env)
    {
        $this->targetEnv = $env;
    }

    public function setTargetGroup(string $group)
    {
        if ($group == 'nogroup') {
            $this->targetGroup = '';
        } else {
            $this->targetGroup = $group;
        }
    }

    public function setTargetDescription(string $description)
    {
        if ($description == 'nodescription') {
            $this->targetDescription = '';
        } else {
            $this->targetDescription = $description;
        }
    }

    public function setTargetGpgCheck(string $gpgCheck)
    {
        $this->targetGpgCheck = $gpgCheck;
    }

    public function setTargetGpgResign(string $gpgResign)
    {
        $this->targetGpgResign = $gpgResign;
    }

    public function getRepoId()
    {
        return $this->repoId;
    }

    public function getSnapId()
    {
        return $this->snapId;
    }

    public function getEnvId()
    {
        return $this->envId;
    }

    public function getPlanId()
    {
        return $this->planId;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDist()
    {
        return $this->dist;
    }

    public function getSection()
    {
        return $this->section;
    }

    public function getPackageType()
    {
        return $this->packageType;
    }

    public function getEnv()
    {
        return $this->env;
    }

    public function getTargetEnv()
    {
        return $this->targetEnv;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function getDateFormatted()
    {
        return DateTime::createFromFormat('Y-m-d', $this->date)->format('d-m-Y');
    }

    public function getTargetDate()
    {
        return $this->targetDate;
    }

    public function getTargetDateFormatted()
    {
        return DateTime::createFromFormat('Y-m-d', $this->targetDate)->format('d-m-Y');
    }

    public function getTime()
    {
        return $this->time;
    }

    public function getTargetTime()
    {
        return $this->targetTime;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getSource()
    {
        return $this->source;
    }

    public function getHostUrl()
    {
        return $this->hostUrl;
    }

    public function getRootUrl()
    {
        return $this->rootUrl;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getSigned()
    {
        return $this->signed;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getTargetName()
    {
        return $this->targetName;
    }

    public function getTargetGroup()
    {
        return $this->targetGroup;
    }

    public function getTargetDescription()
    {
        return $this->targetDescription;
    }

    public function getTargetGpgCheck()
    {
        return $this->targetGpgCheck;
    }

    public function getTargetGpgResign()
    {
        return $this->targetGpgResign;
    }

    public function getOpLogLocation()
    {
        return $this->op->log->location;
    }

    /**
     *  Récupère toutes les informations d'un repo, snapshot en env en base de données
     */
    public function getAllById(string $repoId = null, string $snapId = null, string $envId = null)
    {
        $data = $this->model->getAllById($repoId, $snapId, $envId);

        $this->getAllByParser($data);
    }

    /**
     *  Fonction qui parse et récupère les résultats des fonctions getAllBy*
     */
    private function getAllByParser(array $data)
    {
        if (!empty($data['Source'])) {
            $this->setSource($data['Source']);
        }
        if (!empty($data['Name'])) {
            $this->setName($data['Name']);
        }
        if (!empty($data['Dist'])) {
            $this->setDist($data['Dist']);
        }
        if (!empty($data['Section'])) {
            $this->setSection($data['Section']);
        }
        if (!empty($data['Package_type'])) {
            $this->setPackageType($data['Package_type']);
        }
        if (!empty($data['Date'])) {
            $this->setDate($data['Date']);
        }
        if (!empty($data['Time'])) {
            $this->setTime($data['Time']);
        }
        if (!empty($data['Status'])) {
            $this->setStatus($data['Status']);
        }
        if (!empty($data['Env'])) {
            $this->setEnv($data['Env']);
        }
        if (!empty($data['Type'])) {
            $this->setType($data['Type']);
        }
        if (!empty($data['Signed'])) {
            $this->setSigned($data['Signed']);
        }
        if (!empty($data['Description'])) {
            $this->setDescription($data['Description']);
        }
        if (!empty($data['repoId'])) {
            $this->setRepoId($data['repoId']);
        }
        if (!empty($data['snapId'])) {
            $this->setSnapId($data['snapId']);
        }
        if (!empty($data['envId'])) {
            $this->setEnvId($data['envId']);
        }
        if ($this->packageType == 'deb' and $this->type == "mirror") {
            $this->getFullSource($this->source);
        }
    }

    /**
     *  Retourne l'Id d'un repo en base de données, à partir de son nom
     */
    public function getIdByName(string $name, string $dist = null, string $section = null)
    {
        return $this->model->getIdByName($name, $dist, $section);
    }

    /**
     *  Récupère l'url source complete avec la racine du dépot (Debian uniquement)
     */
    private function getFullSource(string $source)
    {
        /**
         *  Récupère l'url complète en base de données
         */
        $fullUrl = $this->model->getFullSource($source);

        if (empty($fullUrl)) {
            throw new Exception('impossible de déterminer l\'URL du repo source');
        }

        /**
         *  On retire http:// ou https:// du début de l'URL
         */
        $fullUrl = str_replace(array("http://", "https://"), '', $fullUrl);

        /**
         *  Extraction de l'adresse de l'hôte (server.domain.net) à partir de l'url http
         */
        $hostUrl = exec("echo '$fullUrl' | cut -d'/' -f1");

        /**
         *  Extraction de la racine de l'hôte (ex pour : ftp.fr.debian.org/debian ici la racine sera debian)
         */
        $root = str_replace($hostUrl, '', $fullUrl);

        if (empty($hostUrl)) {
            throw new Exception('impossible de déterminer l\'adresse du repo source');
        }
        if (empty($root)) {
            throw new Exception('impossible de déterminer la racine de l\'URL du repo source');
        }

        $this->setSourceFullUrl($fullUrl);
        $this->setSourceHostUrl($hostUrl);
        $this->setSourceRoot($root);
    }

    /**
     *  Retourne l'Id du snapshot le + récent du repo
     */
    public function getLastSnapshotId(string $repoId)
    {
        return $this->model->getLastSnapshotId($repoId);
    }

    /**
     *  Retoune true si l'Id de repo existe en base de données
     */
    public function existsId(string $repoId)
    {
        return $this->model->existsId($repoId);
    }

    /**
     *  Retourne true si un Id de snapshot existe en base de données
     */
    public function existsSnapId(string $snapId)
    {
        return $this->model->existsSnapId($snapId);
    }

    /**
     *  Vérifie si un repo existe et est actif (contient des snapshots actifs)
     */
    public function isActive(string $name, string $dist = null, string $section = null)
    {
        return $this->model->isActive($name, $dist, $section);
    }

    /**
     *  Retourne un array de tous les noms de repos, sans informations des snapshots et environnements associés
     *  Si le paramètre 'true' est passé alors la fonction renverra uniquement les noms des repos qui ont un snapshot actif rattaché
     *  Si le paramètre 'false' est passé alors la fonction renverra tous les noms de repos avec ou sans snapshot rattaché
     */
    public function listNameOnly(bool $bool = false)
    {
        return $this->model->listNameOnly($bool);
    }

    /**
     *  Retourne la liste des repos, leurs snapshots et leur environnements
     *  N'affiche pas les repos qui n'ont aucun environnement actif
     */
    public function list()
    {
        return $this->model->list();
    }

    /**
     *  Retourne la liste des repos éligibles aux planifications
     *  Il s'agit des repos ayant au moins 1 snapshot actif
     */
    public function listForPlan()
    {
        return $this->model->listForPlan();
    }

    /**
     *  Retourne la liste des repos par groupes
     */
    public function listByGroup(string $groupName)
    {
        return $this->model->listByGroup($groupName);
    }

    /**
     *  Retourne le liste des noms de repos actifs, par groupe
     *  Utilisée notamment pour les planifications de groupes
     */
    public function listNameByGroup(string $groupName)
    {
        return $this->model->listNameByGroup($groupName);
    }

    /**
     *  Retourne le nombre total de repos
     */
    public function count(string $status = 'active')
    {
        return $this->model->count($status);
    }

    /**
     *  Création d'un nouveau miroir de repo / section
     */
    public function new()
    {
        /**
         *  On défini la date du jour et l'environnement par défaut sur lesquels sera basé le nouveau miroir
         */
        $this->setTargetDate(date('Y-m-d'));
        $this->setTargetTime(date("H:i"));

        /**
         *  Démarrage de l'opération
         *  On indique à startOperation, le nom du repo/section en cours de création. A la fin de l'opération, on remplacera cette valeur directement par
         *  l'ID en BDD de ce repo/section créé.
         *  On indique également si on a activé ou non gpgCheck et gpgResign.
         */
        $this->op = new \Controllers\Operation();
        $this->op->setAction('new');
        $this->op->setType('manual');

        if ($this->packageType == "rpm") {
            $this->op->startOperation(
                array(
                    'id_repo_target' => $this->name,
                    'gpgCheck' => $this->targetGpgCheck,
                    'gpgResign' => $this->targetGpgResign
                )
            );
        }
        if ($this->packageType == "deb") {
            $this->op->startOperation(
                array(
                    'id_repo_target' => $this->name . '|' . $this->dist . '|' . $this->section,
                    'gpgCheck' => $this->targetGpgCheck,
                    'gpgResign' => $this->targetGpgResign
                )
            );
        }

        /**
         *  Ajout du PID de ce processus dans le fichier PID
         */
        $this->op->log->addsubpid(getmypid());

        /**
         *  Lancement du script externe qui va construire le fichier de log principal à partir des petits fichiers de log de chaque étape
         */
        $steps = 7;
        exec('php ' . ROOT . '/operations/logbuilder.php ' . PID_DIR . "/{$this->op->log->pid}.pid {$this->op->log->location} " . TEMP_DIR . "/{$this->op->log->pid} $steps >/dev/null 2>/dev/null &");

        try {
            /**
             *  Etape 0 : Afficher le titre de l'opération
             */
            $this->op->log->steplog(0);
            file_put_contents($this->op->log->steplog, "<h3>CREATION D'UN NOUVEAU REPO</h3>");
            /**
             *  Etape 1 : Afficher les détails de l'opération
             */
            $this->op->log->steplog(1);
            $this->printDetails();
            /**
             *   Etape 2 : récupération des paquets
             */
            $this->op->log->steplog(2);
            $this->getPackages();
            /**
             *   Etape 3 : signature des paquets/du repo
             */
            $this->op->log->steplog(3);
            $this->signPackages();
            /**
             *   Etape 4 : Création du repo et liens symboliques
             */
            $this->op->log->steplog(4);
            $this->createRepo();
            /**
             *   Etape 5 : Finalisation du repo (ajout en BDD et application des droits)
             */
            $this->op->log->steplog(5);
            $this->finalize();

            /**
             *  Passage du status de l'opération en done
             */
            $this->op->setStatus('done');
        } catch (\Exception $e) {
            $this->op->log->steplogError($e->getMessage()); // On transmets l'erreur à $this->op->log->steplogError() qui va se charger de l'afficher en rouge dans le fichier de log

            /**
             *  Passage du status de l'opération en erreur
             */
            $this->op->setStatus('error');
        }

        /**
         *  Cloture de l'opération
         */
        $this->op->log->closeStepOperation();
        $this->op->closeOperation();
    }

    /**
     *  Création d'un nouveau repo / section local
     */
    public function newLocalRepo()
    {
        /**
         *  On défini la date du jour et l'environnement par défaut sur lesquels sera basé le nouveau miroir
         */
        $this->setTargetDate(date('Y-m-d'));
        $this->setTargetTime(date("H:i"));

        /**
         *  Démarrage de l'opération
         */
        $this->op = new \Controllers\Operation();
        $this->op->setAction('new');
        $this->op->setType('manual');

        if ($this->packageType == "rpm") {
            $this->op->startOperation(array('id_repo_target' => $this->name));
        }
        if ($this->packageType == "deb") {
            $this->op->startOperation(array('id_repo_target' => $this->name . '|' . $this->dist . '|' . $this->section));
        }

        /**
         *  Ajout du PID de ce processus dans le fichier PID
         */
        $this->op->log->addsubpid(getmypid());

        /**
         *  Lancement du script externe qui va construire le fichier de log principal à partir des petits fichiers de log de chaque étape
         */
        $steps = 2;
        exec('php ' . ROOT . '/operations/logbuilder.php ' . PID_DIR . "/{$this->op->log->pid}.pid {$this->op->log->location} " . TEMP_DIR . "/{$this->op->log->pid} $steps >/dev/null 2>/dev/null &");

        try {
            ob_start();

            /**
             *  1. Génération du tableau récapitulatif de l'opération
             */
            include(ROOT . '/templates/tables/op-new-local.inc.php');

            $this->op->log->steplog(1);
            $this->op->log->steplogInitialize('createRepo');
            $this->op->log->steplogTitle('CREATION DU REPO');

            /**
             *  2. On vérifie que le nom du repo n'est pas vide
             */
            if (empty($this->name)) {
                throw new Exception('le nom du repo ne peut être vide');
            }

            /**
             *  3. Création du répertoire avec le nom du repo, et les sous-répertoires permettant d'acceuillir les futurs paquets
             */
            if ($this->packageType == 'rpm') {
                if (!file_exists(REPOS_DIR . '/' . $this->targetDateFormatted . '_' . $this->name . '/Packages')) {
                    if (!mkdir(REPOS_DIR . '/' . $this->targetDateFormatted . '_' . $this->name . '/Packages', 0770, true)) {
                        throw new Exception("impossible de créer le répertoire du repo {$this->name}");
                    }
                }
            }
            if ($this->packageType == 'deb') {
                if (!file_exists(REPOS_DIR . '/' . $this->name . '/' . $this->dist . '/' . $this->targetDateFormatted . '_' . $this->section . '/pool/' . $this->section)) {
                    if (!mkdir(REPOS_DIR . '/' . $this->name . '/' . $this->dist . '/' . $this->targetDateFormatted . '_' . $this->section . '/pool/' . $this->section, 0770, true)) {
                        throw new Exception('impossible de créer le répertoire de la section');
                    }
                }
            }

            /**
             *   4. Création du lien symbolique, si un environnement a été spécifié par l'utilisateur
             */
            if (!empty($this->targetEnv)) {
                if ($this->packageType == 'rpm') {
                    exec('cd ' . REPOS_DIR . '/ && ln -sfn ' . $this->targetDateFormatted . '_' . $this->name . ' ' . $this->name . '_' . $this->targetEnv, $output, $result);
                }
                if ($this->packageType == 'deb') {
                    exec('cd ' . REPOS_DIR . '/' . $this->name . '/' . $this->dist . '/ && ln -sfn ' . $this->targetDateFormatted . '_' . $this->section . ' ' . $this->section . '_' . $this->targetEnv, $output, $result);
                }
                if ($result != 0) {
                    throw new Exception('impossible de créer le repo');
                }
            }

            /**
             *  Vérification de l'existance du repo en base de données
             */
            if ($this->packageType == "rpm") {
                $exists = $this->model->exists($this->name);
            }
            if ($this->packageType == "deb") {
                $exists = $this->model->sectionExists($this->name, $this->dist, $this->section);
            }

            /**
             *  Si actuellement aucun repo de ce nom n'existe en base de données alors on l'ajoute
             *  Note : ici on renseigne la source comme étant $this->name
             */
            if ($exists === false) {
                if ($this->packageType == "rpm") {
                    $this->model->add($this->name, 'rpm', $this->name);
                }
                if ($this->packageType == "deb") {
                    $this->model->add($this->name, 'deb', $this->name, $this->dist, $this->section);
                }

                /**
                 *  L'Id du repo devient alors l'Id de la dernière ligne insérée en base de données
                 */
                $this->repoId = $this->model->getLastInsertRowID();

            /**
             *  Sinon si un repo de même nom existe, on rattache ce nouveau snapshot et ce nouvel env à ce repo
             */
            } else {
                /**
                 *  D'abord on récupère l'Id en base de données du repo
                 */
                if ($this->packageType == "rpm") {
                    $this->repoId = $this->model->getIdByName($this->name, '', '');
                }

                if ($this->packageType == "deb") {
                    $this->repoId = $this->model->getIdByName($this->name, $this->dist, $this->section);
                }
            }
            unset($exists);

            /**
             *  Ajout du snapshot en base de données
             */
            $this->model->addSnap($this->targetDate, $this->targetTime, 'no', $this->type, 'active', $this->repoId);

            /**
             *  Récupération de l'Id du snapshot ajouté précédemment
             */
            $this->setSnapId($this->model->getLastInsertRowID());

            /**
             *  Ajout de l'env en base de données si un env a été spécifié par l'utilisateur
             */
            if (!empty($this->targetEnv)) {
                $this->model->addEnv($this->targetEnv, $this->targetDescription, $this->snapId);
            }

            /**
             *  6. Application des droits sur le nouveau repo créé
             */
            if ($this->packageType == 'rpm') {
                exec('find ' . REPOS_DIR . '/' . $this->targetDateFormatted . '_' . $this->name . '/ -type f -exec chmod 0660 {} \;');
                exec('find ' . REPOS_DIR . '/' . $this->targetDateFormatted . '_' . $this->name . '/ -type d -exec chmod 0770 {} \;');
                exec('chown -R ' . WWW_USER . ':repomanager ' . REPOS_DIR . "/{$this->targetDateFormatted}_{$this->name}");
            }
            if ($this->packageType == 'deb') {
                exec('find ' . REPOS_DIR . '/' . $this->name . '/ -type f -exec chmod 0660 {} \;');
                exec('find ' . REPOS_DIR . '/' . $this->name . '/ -type d -exec chmod 0770 {} \;');
                exec('chown -R ' . WWW_USER . ':repomanager ' . REPOS_DIR . "/{$this->name}");
            }

            $this->op->log->steplogOK();

            /**
             *  7. Ajout de la section à un groupe si un groupe a été renseigné
             */
            if (!empty($this->targetGroup)) {
                $this->op->log->steplog(2);
                $this->op->log->steplogInitialize('addToGroup');
                $this->op->log->steplogTitle('AJOUT A UN GROUPE');
                $this->addRepoIdToGroup($this->repoId, $this->targetGroup);
                $this->op->log->steplogOK();
            }

            /**
             *  Nettoyage des repos inutilisés dans les groupes
             */
            $this->cleanGroups();

            /**
             *  Passage du status de l'opération en done
             */
            $this->op->setStatus('done');
        } catch (\Exception $e) {
            /**
             *  On transmets l'erreur à $this->op->log->steplogError() qui va se charger de l'afficher en rouge dans le fichier de log
             */
            $this->op->log->steplogError($e->getMessage());

            /**
             *  Passage du status de l'opération en erreur
             */
            $this->op->setStatus('error');
        }

        /**
         *  Cloture de l'opération
         */
        $this->op->log->closeStepOperation();
        $this->op->closeOperation();
    }

    /**
     *  Mise à jour d'un miroir de repo / section
     */
    public function update()
    {
        /**
         *  On défini la date du jour et l'environnement par défaut sur lesquels sera basé le nouveau miroir
         */
        $this->setTargetDate(date('Y-m-d'));
        $this->setTargetTime(date("H:i"));

        /**
         *  Création d'une opération en BDD, on indique également si on a activé ou non gpgCheck et gpgResign
         *  Si cette fonction est appelée par une planification, alors l'id de cette planification est stockée dans $this->id_plan, on l'indique également à startOperation()
         */
        $this->op = new \Controllers\Operation();
        $this->op->setAction('update');

        /**
         *  Si un Id de planification a été spécifié alors ça signifie que l'action a été initialisée par une planification
         */
        if (!empty($this->planId)) {
            $this->op->setType('plan');
        } else {
            $this->op->setType('manual');
        }

        if ($this->op->getType() == 'manual') {
            $this->op->startOperation(
                array(
                    'id_snap_target' => $this->snapId,
                    'gpgCheck' => $this->targetGpgCheck,
                    'gpgResign' => $this->targetGpgResign
                )
            );
        }
        if ($this->op->getType() == 'plan') {
            $this->op->startOperation(
                array(
                    'id_snap_target' => $this->snapId,
                    'gpgCheck' => $this->targetGpgCheck,
                    'gpgResign' => $this->targetGpgResign,
                    'id_plan' => $this->planId
                )
            );
        }

        /**
         *  Ajout du PID de ce processus dans le fichier PID
         */
        $this->op->log->addsubpid(getmypid());

        /**
         *  Lancement du script externe qui va construire le fichier de log principal à partir des petits fichiers de log de chaque étape
         */
        $steps = 7;
        exec('php ' . ROOT . '/operations/logbuilder.php ' . PID_DIR . "/{$this->op->log->pid}.pid {$this->op->log->location} " . TEMP_DIR . "/{$this->op->log->pid} $steps >/dev/null 2>/dev/null &");

        try {
            /**
             *  Etape 0 : Afficher le titre de l'opération
             */
            $this->op->log->steplog(0);
            file_put_contents($this->op->log->steplog, "<h3>MISE A JOUR D'UN REPO</h3>");
            /**
             *  Etape 1 : Afficher les détails de l'opération
             */
            $this->op->log->steplog(1);
            $this->printDetails();
            /**
            *   Etape 2 : récupération des paquets
            */
            $this->op->log->steplog(2);
            $this->getPackages();
            /**
            *   Etape 3 : signature des paquets/du repo
            */
            $this->op->log->steplog(3);
            $this->signPackages();
            /**
            *   Etape 4 : Création du repo et liens symboliques
            */
            $this->op->log->steplog(4);
            $this->createRepo();
            /**
            *   Etape 6 : Finalisation du repo (ajout en BDD et application des droits)
            */
            $this->op->log->steplog(5);
            $this->finalize();
            /**
             *  Passage du status de l'opération en done
             */
            $this->op->setStatus('done');
        } catch (\Exception $e) {
            $this->op->log->steplogError($e->getMessage()); // On transmets l'erreur à $this->op->log->steplogError() qui va se charger de l'afficher en rouge dans le fichier de log

            /**
             *  Passage du status de l'opération en erreur
             */
            $this->op->setStatus('error');

            /**
             *  Cloture de l'opération
             */
            $this->op->log->closeStepOperation();
            $this->op->closeOperation();

            /**
             *  Cas où cette fonction est lancée par une planification : la planif attend un retour, on lui renvoie false pour lui indiquer qu'il y a eu une erreur
             */
            return false;
        }
        /**
         *  Cloture de l'opération
         */
        $this->op->log->closeStepOperation();
        $this->op->closeOperation();
    }

    /**
     *  Dupliquer un snapshot de repo
     */
    public function duplicate()
    {
        /**
         *  Démarrage de l'opération
         */
        $this->op = new \Controllers\Operation();
        $this->op->setAction('duplicate');
        $this->op->setType('manual');

        if ($this->packageType == "rpm") {
            $this->op->startOperation(
                array(
                    'id_snap_source' => $this->snapId,
                    'id_repo_target' => $this->targetName
                )
            );
        }
        if ($this->packageType == "deb") {
            $this->op->startOperation(
                array(
                    'id_snap_source' => $this->snapId,
                    'id_repo_target' => $this->targetName . '|' . $this->dist . '|' . $this->section
                )
            );
        }

        /**
         *  Ajout du PID de ce processus dans le fichier PID
         */
        $this->op->log->addsubpid(getmypid());

        /**
         *  Lancement du script externe qui va construire le fichier de log principal à partir des petits fichiers de log de chaque étape
         */
        $steps = 4;
        exec("php " . ROOT . "/operations/logbuilder.php " . PID_DIR . "/{$this->op->log->pid}.pid {$this->op->log->location} " . TEMP_DIR . "/{$this->op->log->pid} $steps >/dev/null 2>/dev/null &");

        try {
            ob_start();

            /**
             *  1. Génération du tableau récapitulatif de l'opération
             */
            include(ROOT . '/templates/tables/op-duplicate.inc.php');

            $this->op->log->steplog(1);
            $this->op->log->steplogInitialize('duplicate');
            $this->op->log->steplogTitle('DUPLICATION');

            /**
             *  On vérifie que le snapshot source existe
             */
            if ($this->model->existsSnapId($this->snapId) === false) {
                throw new Exception("Le snapshot de repo source n'existe pas");
            }

            /**
             *  On vérifie qu'un repo de même nom cible n'existe pas déjà
             */
            if ($this->packageType == "rpm") {
                if ($this->model->isActive($this->targetName) === true) {
                    throw new Exception('un repo <span class="label-black">' . $this->targetName . '</span> existe déjà');
                }
            }
            if ($this->packageType == "deb") {
                if ($this->model->isActive($this->targetName, $this->dist, $this->section) === true) {
                    throw new Exception('un repo <span class="label-black">' . $this->targetName . ' ❯ ' . $this->dist . ' ❯ ' . $this->section . '</span> existe déjà');
                }
            }

            /**
             *  Création du nouveau répertoire avec le nouveau nom du repo :
             */
            if ($this->packageType == "rpm") {
                if (!file_exists(REPOS_DIR . '/' . $this->dateFormatted . '_' . $this->targetName)) {
                    if (!mkdir(REPOS_DIR . '/' . $this->dateFormatted . '_' . $this->targetName, 0770, true)) {
                        throw new Exception("impossible de créer le répertoire du nouveau repo <b>" . $this->targetName . "</b>");
                    }
                }
            }
            if ($this->packageType == "deb") {
                if (!file_exists(REPOS_DIR . '/' . $this->targetName . '/' . $this->dist . '/' . $this->dateFormatted . '_' . $this->section)) {
                    if (!mkdir(REPOS_DIR . '/' . $this->targetName . '/' . $this->dist . '/' . $this->dateFormatted . '_' . $this->section, 0770, true)) {
                        throw new Exception("impossible de créer le répertoire du nouveau repo <b>" . $this->targetName . "</b>");
                    }
                }
            }

            /**
             *  Copie du contenu du repo/de la section
             *  Anti-slash devant la commande cp pour forcer l'écrasement si un répertoire de même nom trainait par là
             */
            if ($this->packageType == "rpm") {
                exec('\cp -r ' . REPOS_DIR . '/' . $this->dateFormatted . '_' . $this->name . '/* ' . REPOS_DIR . '/' . $this->dateFormatted . '_' . $this->targetName . '/', $output, $result);
            }
            if ($this->packageType == "deb") {
                exec('\cp -r ' . REPOS_DIR . '/' . $this->name . '/' . $this->dist . '/' . $this->dateFormatted . '_' . $this->section . '/* ' . REPOS_DIR . '/' . $this->targetName . '/' . $this->dist . '/' . $this->dateFormatted . '_' . $this->section . '/', $output, $result);
            }
            if ($result != 0) {
                throw new Exception('impossible de copier les données du repo source vers le nouveau repo');
            }

            $this->op->log->steplogOK();

            /**
             *  Sur Debian il faut reconstruire les données du repo avec le nouveau nom du repo.
             */
            if ($this->packageType == "deb") {
                /**
                 *  Pour les besoins de la fonction createRepo(), il faut que le nom du repo à créer soit dans $name.
                 *  Du coup on backup temporairement le nom actuel et on le remplace par $this->targetName
                 */
                $backupName = $this->name;
                $this->setName($this->targetName);
                $this->setTargetDate($this->date);

                $this->op->log->steplog(2);
                $this->createRepo();

                /**
                 *  On remets en place le nom tel qu'il était
                 */
                $this->setName($backupName);
            }

            $this->op->log->steplog(3);
            $this->op->log->steplogInitialize('finalize');
            $this->op->log->steplogTitle('FINALISATION');

            /**
             *  Création du lien symbolique
             *  Seulement si l'utilisateur a spécifié un environnement
             */
            if (!empty($this->targetEnv)) {
                if ($this->packageType == "rpm") {
                    exec('cd ' . REPOS_DIR . '/ && ln -sfn ' . $this->dateFormatted . '_' . $this->targetName . ' ' .  $this->targetName . '_' . $this->targetEnv, $output, $result);
                }
                if ($this->packageType == "deb") {
                    exec('cd ' . REPOS_DIR . '/' . $this->targetName . '/' . $this->dist . '/ && ln -sfn ' . $this->dateFormatted . '_' . $this->section . ' ' . $this->section . '_' . $this->targetEnv, $output, $result);
                }
                if ($result != 0) {
                    throw new Exception('impossible de créer le nouveau repo');
                }
            }

            /**
             *  8. Insertion du nouveau repo en base de données
             */
            if ($this->packageType == "rpm") {
                $this->model->add($this->source, 'rpm', $this->targetName);
            }
            if ($this->packageType == "deb") {
                $this->model->add($this->source, 'deb', $this->targetName, $this->dist, $this->section);
            }

            /**
             *  On récupère l'Id du repo créé en base de données
             */
            $targetRepoId = $this->model->getLastInsertRowID();

            /**
             *  On ajoute le snapshot copié en base de données
             */
            $this->model->addSnap($this->date, $this->time, $this->signed, $this->type, $this->status, $targetRepoId);

            /**
             *  On récupère l'Id du snapshot créé en base de données
             */
            $targetSnapId = $this->model->getLastInsertRowID();

            /**
             *  On ajoute l'environnement créé
             *  Seulement si l'utilisateur a spécifié un environnement
             */
            if (!empty($this->targetEnv)) {
                $this->model->addEnv($this->targetEnv, $this->targetDescription, $targetSnapId);
            }

            /**
             *  9. Application des droits sur le nouveau repo créé
             */
            if ($this->packageType == "rpm") {
                exec('find ' . REPOS_DIR . '/' . $this->dateFormatted . '_' . $this->targetName . '/ -type f -exec chmod 0660 {} \;');
            }
            if ($this->packageType == "deb") {
                exec('find ' . REPOS_DIR . '/' . $this->targetName . '/ -type d -exec chmod 0770 {} \;');
            }
            exec('chown -R ' . WWW_USER . ':repomanager ' . REPOS_DIR . '/' . $this->targetName . '/');

            $this->op->log->steplogOK();

            /**
             *  10. Ajout de la section à un groupe si un groupe a été renseigné
             */
            if (!empty($this->targetGroup)) {
                $this->op->log->steplog(4);
                $this->op->log->steplogInitialize('addToGroup');
                $this->op->log->steplogTitle('AJOUT A UN GROUPE');

                /**
                 *  Ajout du repo créé au groupe spécifié
                 */
                $this->addRepoIdToGroup($targetRepoId, $this->targetGroup);

                $this->op->log->steplogOK();
            }

            /**
             *  Nettoyage des repos inutilisés dans les groupes
             */
            $this->cleanGroups();

            /**
             *  Passage du status de l'opération en done
             */
            $this->op->setStatus('done');
        } catch (\Exception $e) {
            /**
             *  On transmets l'erreur à $this->op->log->steplogError() qui va se charger de l'afficher en rouge dans le fichier de log
             */
            $this->op->log->steplogError($e->getMessage());

            /**
             *  Passage du status de l'opération en erreur
             */
            $this->op->setStatus('error');
        }

        /**
         *  Cloture de l'opération
         */
        $this->op->log->closeStepOperation();
        $this->op->closeOperation();
    }

    /**
     *  Reconstruire les métadonnées d'un snapshot
     */
    public function reconstruct()
    {
        $this->setTargetDate($this->getDate());

        /**
         *  Création d'une opération en BDD, on indique également si on a activé ou non gpgCheck et gpgResign
         *  Si cette fonction est appelée par une planification, alors l'id de cette planification est stockée dans $this->id_plan, on l'indique également à startOperation()
         */
        $this->op = new \Controllers\Operation();
        $this->op->setAction('reconstruct');
        $this->op->setType('manual');

        $this->op->startOperation(
            array(
                'id_snap_target' => $this->snapId,
                'gpgResign' => $this->targetGpgResign
            )
        );

        /**
         *  Ajout du PID de ce processus dans le fichier PID
         */
        $this->op->log->addsubpid(getmypid());

        /**
         *  Lancement du script externe qui va construire le fichier de log principal à partir des petits fichiers de log de chaque étape
         */
        $steps = 3;
        exec('php ' . ROOT . '/operations/logbuilder.php ' . PID_DIR . "/{$this->op->log->pid}.pid {$this->op->log->location} " . TEMP_DIR . "/{$this->op->log->pid} $steps >/dev/null 2>/dev/null &");

        try {
            /**
             *  Etape 0 : Afficher le titre de l'opération
             */
            $this->op->log->steplog(0);
            file_put_contents($this->op->log->steplog, "<h3>RECONSTRUCTION DES METADONNÉES DU REPO</h3>");
            /**
             *  Etape 1 : Afficher les détails de l'opération
             */
            $this->op->log->steplog(1);
            $this->printDetails();
            /**
            *   Etape 2 : signature des paquets/du repo
            */
            $this->op->log->steplog(2);
            $this->signPackages();
            /**
            *   Etape 3 : Création du repo et liens symboliques
            */
            $this->op->log->steplog(3);
            $this->createRepo();
            /**
             *  Etape 4 : on modifie l'état de la signature du repo en BDD
             *  Comme on a reconstruit les fichiers du repo, il est possible qu'on soit passé d'un repo signé à un repo non-signé, ou inversement
             *  Il faut donc modifier l'état en BDD
             */
            $this->model->snapSetSigned($this->snapId, $this->targetGpgResign);

            /**
             *  Passage du status de l'opération en done
             */
            $this->op->setStatus('done');
        } catch (\Exception $e) {
            $this->op->log->steplogError($e->getMessage()); // On transmets l'erreur à $this->op->log->steplogError() qui va se charger de l'afficher en rouge dans le fichier de log

            /**
             *  Passage du status de l'opération en erreur
             */
            $this->op->setStatus('error');
        }
        /**
         *  Cloture de l'opération
         */
        $this->op->log->closeStepOperation();
        $this->op->closeOperation();
    }

    /**
     *  Suppression d'un snapshot de repo
     */
    public function delete()
    {
        $this->op = new \Controllers\Operation();
        $this->op->setAction('delete');
        $this->op->setType('manual');
        $this->op->startOperation(array('id_snap_target' => $this->snapId));

        /**
         *  Ajout du PID de ce processus dans le fichier PID
         */
        $this->op->log->addsubpid(getmypid());

        /**
         *  Lancement du script externe qui va construire le fichier de log principal à partir des petits fichiers de log de chaque étape
         */
        $steps = 1;
        exec('php ' . ROOT . '/operations/logbuilder.php ' . PID_DIR . "/{$this->op->log->pid}.pid {$this->op->log->location} " . TEMP_DIR . "/{$this->op->log->pid} $steps >/dev/null 2>/dev/null &");

        try {
            ob_start();

            /**
             *  1. Génération du tableau récapitulatif de l'opération
             */
            include(ROOT . '/templates/tables/op-delete.inc.php');

            $this->op->log->steplog(1);
            $this->op->log->steplogInitialize('deleteSnapshot');
            $this->op->log->steplogTitle('SUPPRESSION');

            /**
             *  2. Suppression du snapshot
             */
            if ($this->getStatus() == 'active') {
                if ($this->packageType == "rpm") {
                    if (is_dir(REPOS_DIR . '/' . $this->dateFormatted . '_' . $this->name)) {
                        exec('rm ' . REPOS_DIR . '/' . $this->dateFormatted . '_' . $this->name . ' -rf', $output, $result);
                    }
                }
                if ($this->packageType == "deb") {
                    if (is_dir(REPOS_DIR . '/' . $this->name . '/' . $this->dist . '/' . $this->dateFormatted . '_' . $this->section)) {
                        exec('rm ' . REPOS_DIR . '/' . $this->name . '/' . $this->dist . '/' . $this->dateFormatted . '_' . $this->section . ' -rf', $output, $result);
                    }
                }
            }

            if ($result != 0) {
                throw new Exception('impossible de supprimer le snapshot du <span class="label-black">' . $this->dateFormatted . '</span>');
            }

            $this->op->log->steplogOK();

            /**
             *  Passage du snapshot en état 'deleted' en base de données
             */
            $this->model->snapSetStatus($this->snapId, 'deleted');

            /**
             *  Récupération des Id d'environnements qui pointaient vers ce snapshot
             */
            $envIds = $this->model->getEnvIdBySnapId($this->snapId);

            /**
             *  On traite chaque Id d'environnement qui pointait vers ce snapshot
             */
            if (!empty($envIds)) {
                foreach ($envIds as $envId) {
                    /**
                     *  Suppression des environnements pointant vers ce snapshot en base de données
                     */
                    $myrepo = new Repo();
                    $myrepo->getAllById('', '', $envId);

                    /**
                     *  Si un lien symbolique de cet environnement pointait vers le snapshot supprimé alors on peut supprimer le lien symbolique.
                     */
                    if ($this->getPackageType() == 'rpm') {
                        if (is_link(REPOS_DIR . '/' . $this->name . '_' . $myrepo->getEnv())) {
                            if (readlink(REPOS_DIR . '/' . $this->name . '_' . $myrepo->getEnv()) == $this->dateFormatted . '_' . $this->name) {
                                unlink(REPOS_DIR . '/' . $this->name . '_' . $myrepo->getEnv());
                            }
                        }
                    }
                    if ($this->getPackageType() == 'deb') {
                        if (is_link(REPOS_DIR . '/' . $this->name . '/' . $this->dist . '/' . $this->section . '_' . $myrepo->getEnv())) {
                            if (readlink(REPOS_DIR . '/' . $this->name . '/' . $this->dist . '/' . $this->section . '_' . $myrepo->getEnv()) == $this->dateFormatted . '_' . $this->section) {
                                unlink(REPOS_DIR . '/' . $this->name . '/' . $this->dist . '/' . $this->section . '_' . $myrepo->getEnv());
                            }
                        }
                    }
                    unset($myrepo);
                }
            }

            /**
             *  Nettoyage des repos inutilisés dans les groupes
             */
            $this->cleanGroups();

            /**
             *  Passage du status de l'opération en done
             */
            $this->op->setStatus('done');
        } catch (\Exception $e) {
            /**
             *  On transmets l'erreur à $this->op->log->steplogError() qui va se charger de l'afficher en rouge dans le fichier de log
             */
            $this->op->log->steplogError($e->getMessage());

            /**
             *  Passage du status de l'opération en erreur
             */
            $this->op->setStatus('error');
        }

        /**
         *  Cloture de l'opération
         */
        $this->op->log->closeStepOperation();
        $this->op->closeOperation();
    }

    /**
     *  Suppression d'un environnement
     */
    public function removeEnv()
    {
        $this->op = new \Controllers\Operation();
        $this->op->setAction('removeEnv');
        $this->op->setType('manual');

        $this->op->startOperation(array(
            'id_snap_target' => $this->snapId,
            'id_env_target' => $this->env));

        /**
         *  Ajout du PID de ce processus dans le fichier PID
         */
        $this->op->log->addsubpid(getmypid());

        /**
         *  Lancement du script externe qui va construire le fichier de log principal à partir des petits fichiers de log de chaque étape
         */
        $steps = 2;
        exec('php ' . ROOT . '/operations/logbuilder.php ' . PID_DIR . "/{$this->op->log->pid}.pid {$this->op->log->location} " . TEMP_DIR . "/{$this->op->log->pid} $steps >/dev/null 2>/dev/null &");

        try {
            ob_start();

            /**
             *  1. Génération du tableau récapitulatif de l'opération
             */
            include(ROOT . '/templates/tables/op-remove-env.inc.php');

            $this->op->log->steplog(1);
            $this->op->log->steplogInitialize('removeLink');
            $this->op->log->steplogTitle('SUPPRESSION');

            /**
             *  2. Suppression du lien symbolique de l'environnement
             */
            if ($this->packageType == "rpm") {
                if (file_exists(REPOS_DIR . '/' . $this->name . '_' . $this->env)) {
                    unlink(REPOS_DIR . '/' . $this->name . '_' . $this->env);
                }
            }
            if ($this->packageType == "deb") {
                if (file_exists(REPOS_DIR . '/' . $this->name . '/' . $this->dist . '/' . $this->section . '_' . $this->env)) {
                    unlink(REPOS_DIR . '/' . $this->name . '/' . $this->dist . '/' . $this->section . '_' . $this->env);
                }
            }

            /**
             *  3. Suppression de l'environnement en base de données
             */
            $this->model->deleteEnv($this->envId);

            $this->op->log->steplogOK();

            /**
             *  Nettoyage automatique des snapshots inutilisés
             */
            $snapshotsRemoved = $this->cleanSnapshots();

            if (!empty($snapshotsRemoved)) {
                $this->op->log->steplog(4);
                $this->op->log->steplogInitialize('removeSnapshots');
                $this->op->log->steplogTitle('NETTOYAGE');
                $this->op->log->steplogOK($snapshotsRemoved);
            }

            /**
             *  Nettoyage des repos inutilisés dans les groupes
             */
            $this->cleanGroups();

            /**
             *  Passage du status de l'opération en done
             */
            $this->op->setStatus('done');
        } catch (\Exception $e) {
            /**
             *  On transmets l'erreur à $this->op->log->steplogError() qui va se charger de l'afficher en rouge dans le fichier de log
             */
            $this->op->log->steplogError($e->getMessage());

            /**
             *  Passage du status de l'opération en erreur
             */
            $this->op->setStatus('error');
        }

        /**
         *  Cloture de l'opération
         */
        $this->op->log->closeStepOperation();
        $this->op->closeOperation();
    }

    /**
     *  Création d'un nouvel environnement de repo
     */
    public function env()
    {
        /**
         *  Démarrage d'une nouvelle opération
         */
        $this->op = new \Controllers\Operation();
        $this->op->setAction('env');
        $this->op->setType('manual');
        if ($this->op->getType() == 'manual') {
            $this->op->startOperation(array(
                'id_snap_target' => $this->snapId,
                'id_env_target' => $this->targetEnv));
        }
        // if ($this->op->getType() == 'plan') {
        //     $this->startOperation(array('id_repo_source' => $repoId, 'id_plan' => $this->getPlanId()));
        // }

        /**
         *  Ajout du PID de ce processus dans le fichier PID
         */
        $this->op->log->addsubpid(getmypid());

        /**
         *  Lancement du script externe qui va construire le fichier de log principal à partir des petits fichiers de log de chaque étape
         */
        $steps = 4;
        exec('php ' . ROOT . '/operations/logbuilder.php ' . PID_DIR . "/{$this->op->log->pid}.pid {$this->op->log->location} " . TEMP_DIR . "/{$this->op->log->pid} $steps >/dev/null 2>/dev/null &");

        try {
            ob_start();

            /**
             *  1. Génération du tableau récapitulatif de l'opération
             */
            include(ROOT . '/templates/tables/op-env.inc.php');

            $this->op->log->steplog(1);
            $this->op->log->steplogInitialize('createEnv');
            $this->op->log->steplogTitle('NOUVEL ENVIRONNEMENT ' . \Models\Common::envtag($this->targetEnv));

            /**
             *  2. On vérifie si le snapshot source existe
             */
            if ($this->model->existsSnapId($this->snapId) === false) {
                throw new Exception('Le snapshot cible n\'existe pas');
            }

            /**
             *  3. On vérifie qu'un même environnement pointant vers le snapshot cible n'existe pas déjà
             */
            if ($this->model->existsSnapIdEnv($this->snapId, $this->targetEnv) === true) {
                if ($this->packageType == 'rpm') {
                    throw new Exception('Un environnement ' . \Models\Common::envtag($this->targetEnv) . ' existe déjà sur <span class="label-white">' . $this->name . '</span>⟶<span class="label-black">' . $this->dateFormatted . '</span>');
                }

                if ($this->packageType == 'deb') {
                    throw new Exception('Un environnement ' . \Models\Common::envtag($this->targetEnv) . ' existe déjà sur <span class="label-white">' . $this->name . ' ❯ ' . $this->dist . ' ❯ ' . $this->section . '</span>⟶<span class="label-black">' . $this->dateFormatted . '</span>');
                }
            }

            /**
             *  Si l'utilisateur n'a précisé aucune description alors on récupère celle actuellement en place sur l'environnement de même nom (si l'environnement existe et si il possède une description)
             */
            if (empty($this->targetDescription)) {
                if ($this->packageType == 'rpm') {
                    $actualDescription = $this->model->getDescriptionByName($this->name, '', '', $this->targetEnv);
                }
                if ($this->packageType == 'deb') {
                    $actualDescription = $this->model->getDescriptionByName($this->name, $this->dist, $this->section, $this->targetEnv);
                }

                /**
                 *  Si la description récupérée est vide alors la description restera vide
                 */
                if (!empty($actualDescription)) {
                    $this->targetDescription = $actualDescription;
                } else {
                    $this->targetDescription = '';
                }
            }

            /**
             *  4. Traitement
             *  Deux cas possibles :
             *   1. Ce repo/section n'avait pas d'environnement pointant vers le snapshot cible, on crée simplement un lien symbo et on crée le nouvel environnement en base de données.
             *   2. Ce repo/section avait déjà un environnement pointant vers un snapshot, on le supprime et on fait pointer l'environnement vers le nouveau snapshot.
             */
            if ($this->packageType == 'rpm') {
                /**
                 *  Cas 1 : pas d'environnement de même nom existant sur ce snapshot
                 */
                if ($this->model->existsEnv($this->name, null, null, $this->targetEnv) === false) {
                    /**
                     *  Suppression du lien symbolique (on sait ne jamais si il existe)
                     */
                    if (is_link(REPOS_DIR . '/' . $this->name . '_' . $this->targetEnv)) {
                        unlink(REPOS_DIR . '/' . $this->name . '_' . $this->targetEnv);
                    }

                    /**
                     *  Création du lien symbolique
                     */
                    exec('cd ' . REPOS_DIR . '/ && ln -sfn ' . $this->dateFormatted . '_' . $this->name . ' ' . $this->name . '_' . $this->targetEnv);

                    /**
                     *  Ajout de l'environnement en BDD
                     */
                    $this->model->addEnv($this->targetEnv, $this->targetDescription, $this->snapId);

                    /**
                     *  Clôture de l'étape en cours
                     */
                    $this->op->log->steplogOK();

                /**
                 *  Cas 2 : Il y a déjà un environnement de repo du même nom pointant vers un snapshot.
                 */
                } else {
                    /**
                     *  On récupère l'Id de l'environnement déjà existant
                     */
                    $actualEnvId = $this->model->getEnvIdFromRepoName($this->name, null, null, $this->targetEnv);

                    /**
                     *  On supprime l'éventuel environnement de même nom pointant déjà vers un snapshot de ce repo (si il y en a un)
                     */
                    if (!empty($actualEnvId)) {
                        $this->model->deleteEnv($actualEnvId);
                    }

                    /**
                     *  Suppression du lien symbolique
                     */
                    if (is_link(REPOS_DIR . '/' . $this->name . '_' . $this->targetEnv)) {
                        unlink(REPOS_DIR . '/' . $this->name . '_' . $this->targetEnv);
                    }

                    /**
                     *  Création du nouveau lien symbolique, pointant vers le snapshot cible
                     */
                    exec('cd ' . REPOS_DIR . '/ && ln -sfn ' . $this->dateFormatted . '_' . $this->name . ' ' . $this->name . '_' . $this->targetEnv);

                    /**
                     *  Puis on déclare le nouvel environnement et on le fait pointer vers le snapshot précédemment créé
                     */
                    $this->model->addEnv($this->targetEnv, $this->targetDescription, $this->snapId);

                    /**
                     *  Clôture de l'étape en cours
                     */
                    $this->op->log->steplogOK();
                }
            }

            if ($this->packageType == 'deb') {
                /**
                 *  Cas 1 : pas d'environnement de même nom existant sur ce snapshot
                 */
                if ($this->model->existsEnv($this->name, $this->dist, $this->section, $this->targetEnv) === false) {
                    /**
                     *  Suppression du lien symbolique (on ne sait jamais si il existe)
                     */
                    if (is_link(REPOS_DIR . '/' . $this->name . '/' . $this->dist . '/' . $this->section . '_' . $this->targetEnv)) {
                        unlink(REPOS_DIR . '/' . $this->name . '/' . $this->dist . '/' . $this->section . '_' . $this->targetEnv);
                    }

                    /**
                     *  Création du lien symbolique
                     */
                    exec('cd ' . REPOS_DIR . '/' . $this->name . '/' . $this->dist . ' && ln -sfn ' . $this->dateFormatted . '_' . $this->section . ' ' . $this->section . '_' . $this->targetEnv);

                    /**
                     *  Ajout de l'environnement en BDD
                     */
                    $this->model->addEnv($this->targetEnv, $this->targetDescription, $this->snapId);

                    /**
                     *  Clôture de l'étape en cours
                     */
                    $this->op->log->steplogOK();

                /**
                 *  Cas 2 : Il y a déjà un environnement de repo du même nom pointant vers un snapshot.
                 */
                } else {
                    /**
                     *  D'abord on récupère l'Id de l'environnement déjà existant car on en aura besoin pour modifier son snapshot lié en base de données.
                     */
                    $actualEnvId = $this->model->getEnvIdFromRepoName($this->name, $this->dist, $this->section, $this->targetEnv);

                    /**
                     *  On supprime l'éventuel environnement de même nom pointant déjà vers un snapshot de ce repo (si il y en a un)
                     */
                    if (!empty($actualEnvId)) {
                        $this->model->deleteEnv($actualEnvId);
                    }

                    /**
                     *  Suppression du lien symbolique
                     */
                    if (is_link(REPOS_DIR . '/' . $this->name . '/' . $this->dist . '/' . $this->section . '_' . $this->targetEnv)) {
                        unlink(REPOS_DIR . '/' . $this->name . '/' . $this->dist . '/' . $this->section . '_' . $this->targetEnv);
                    }

                    /**
                     *  Création du nouveau lien symbolique, pointant vers le snapshot cible
                     */
                    exec('cd ' . REPOS_DIR . '/' . $this->name . '/' . $this->dist . ' && ln -sfn ' . $this->dateFormatted . '_' . $this->section . ' ' . $this->section . '_' . $this->targetEnv);

                    /**
                     *  Puis on déclare le nouvel environnement et on le fait pointer vers le snapshot précédemment créé
                     */
                    $this->model->addEnv($this->targetEnv, $this->targetDescription, $this->snapId);

                    /**
                     *  Clôture de l'étape en cours
                     */
                    $this->op->log->steplogOK();
                }
            }

            $this->op->log->steplog(3);
            $this->op->log->steplogInitialize('finalizeRepo');
            $this->op->log->steplogTitle("FINALISATION");

            /**
             *  8. Application des droits sur le repo/la section modifié
             */
            if ($this->packageType == 'rpm') {
                exec('find ' . REPOS_DIR . '/' . $this->dateFormatted . '_' . $this->name . '/ -type f -exec chmod 0660 {} \;');
                exec('find ' . REPOS_DIR . '/' . $this->dateFormatted . '_' . $this->name . '/ -type d -exec chmod 0770 {} \;');
            }

            if ($this->packageType == 'deb') {
                exec('find ' . REPOS_DIR . '/' . $this->name . '/' . $this->dist . '/' . $this->dateFormatted . '_' . $this->section . '/ -type f -exec chmod 0660 {} \;');
                exec('find ' . REPOS_DIR . '/' . $this->name . '/' . $this->dist . '/' . $this->dateFormatted . '_' . $this->section . '/ -type d -exec chmod 0770 {} \;');
            }

            /**
             *  Clôture de l'étape en cours
             */
            $this->op->log->steplogOK();

            /**
             *  Nettoyage automatique des snapshots inutilisés
             */
            $snapshotsRemoved = $this->cleanSnapshots();

            if (!empty($snapshotsRemoved)) {
                $this->op->log->steplog(4);
                $this->op->log->steplogInitialize('removeSnapshots');
                $this->op->log->steplogTitle('NETTOYAGE');
                $this->op->log->steplogOK($snapshotsRemoved);
            }

            /**
             *  Nettoyage des repos inutilisés dans les groupes
             */
            $this->cleanGroups();

            /**
             *  Nettoyage du cache
             */
            \Models\Common::clearCache();

            /**
             *  Passage du status de l'opération en done
             */
            $this->op->setStatus('done');
        } catch (\Exception $e) {
            /**
             *  On transmets l'erreur à $this->op->log->steplogError() qui va se charger de l'afficher en rouge dans le fichier de log
             */
            $this->op->log->steplogError($e->getMessage());

            /**
             *  Passage du status de l'opération en erreur
             */
            $this->op->setStatus('error');

            /**
             *  Cloture de l'opération
             */
            $this->op->log->closeStepOperation();
            $this->op->closeOperation();

            /**
             *  Cas où cette fonction est lancée par une planification : la planif attend un retour, on lui renvoie false pour lui indiquer qu'il y a eu une erreur
             */
            return false;
        }

        /**
         *  Cloture de l'opération
         */
        $this->op->log->closeStepOperation();
        $this->op->closeOperation();
    }

    /**
    *   Génération d'un tableau récapitulatif de l'opération
    *   Valide pour :
    *    - un nouveau repo/section
    *    - une mise à jour de repo/section
    *    - une reconstruction des métadonnées d'un repo/section
    */
    private function printDetails()
    {
        ob_start();

        /**
         *  Affichage du tableau récapitulatif de l'opération
         */
        include(ROOT . '/templates/tables/op-new-update-duplicate-reconstruct.inc.php');

        $this->logcontent = ob_get_clean();
        file_put_contents($this->op->log->steplog, $this->logcontent);

        return true;
    }

    /**
     *   Récupération des paquets à partir d'un repo source
     *   $this->action = new ou update en fonction de si il s'agit d'un nouveau repo ou d'une mise à jour
     */
    private function getPackages()
    {
        ob_start();

        $this->op->log->steplogInitialize('getPackages');
        $this->op->log->steplogTitle('RÉCUPÉRATION DES PAQUETS');

        /**
         *  Le type d'opération doit être renseigné pour cette fonction (soit "new" soit "update")
         */
        if (empty($this->op->getAction())) {
            throw new Exception("Type d'opération inconnu (vide)");
        }
        if ($this->op->getAction() != "new" and $this->op->getAction() != "update") {
            throw new Exception("Erreur : Type d'opération invalide");
        }

        //// VERIFICATIONS ////

        /**
         *  1 : Récupération du type du repo :
         *  Si il s'agit d'un repo de type 'local' alors on quitte à cette étape car on ne peut pas mettre à jour ce type de repo
         */
        if ($this->type == "local") {
            throw new Exception("Il n'est pas possible de mettre à jour un snapshot de repo local");
        }

        /**
         *  2 : Debian seulement : Si la section est un miroir alors il faut récupérer l'URL complète de sa source si ce n'est pas déjà fait
         */
        if ($this->packageType == "deb") {
            $this->getFullSource($this->source);
            ;
        }

        /**
         *  2. Si il s'agit d'un nouveau repo, on vérifie qu'un repo du même nom avec un ou plusieurs environnements actifs n'existe pas déjà.
         *  Un repo peut exister et n'avoir aucun snapshot / environnement rattachés (il sera invisible dans la liste) mais dans ce cas cela ne doit pas empêcher la création d'un nouveau repo
         *
         *  Cas nouveau snapshot de repo :
         */
        if ($this->op->getAction() == "new") {
            if ($this->packageType == "rpm") {
                if ($this->model->exists($this->name) === true) {
                    throw new Exception('Un repo <span class="label-white">' . $this->name . '</span> existe déjà');
                }
            }
            if ($this->packageType == "deb") {
                if ($this->model->sectionExists($this->name, $this->dist, $this->section) == true) {
                    throw new Exception('Un repo <span class="label-white">' . $this->name . ' ❯ ' . $this->dist . ' ❯ ' . $this->section . '</span> existe déjà');
                }
            }
        }

        /**
         *  Si il s'agit d'une mise à jour de snapshot de repo on vérifie que l'id du snapshot existe en base de données
         */
        if ($this->op->getAction() == "update") {
            /**
             *  Vérifie si le snapshot qu'on souhaite mettre à jour existe bien en base de données
             */
            if ($this->model->existsSnapId($this->snapId) === false) {
                throw new Exception("Le snapshot de repo spécifié n'existe pas");
            }

            /**
             *  On peut remettre à jour un snapshot dans la même journée, mais on ne peut pas mettre à jour un autre snapshot si un snapshot à la date du jour existe déjà
             *
             *  Du coup si la date du snapshot en cours de mise à jour == date du jour ($this->targetDate) alors on peut poursuivre l'opération
             *  Sinon on vérifie qu'un autre snapshot à la date du jour n'existe pas déjà, si c'est le cas on quitte
             */
            if ($this->model->getSnapDateById($this->snapId) != $this->targetDate) {
                if ($this->packageType == 'rpm') {
                    if ($this->model->existsRepoSnapDate($this->targetDate, $this->name) === true) {
                        throw new Exception('Un snapshot existe déjà en date du <span class="label-black">' . $this->targetDateFormatted . '</span>');
                    }
                }
                if ($this->packageType == 'deb') {
                    if ($this->model->existsRepoSnapDate($this->targetDate, $this->name, $this->dist, $this->section) === true) {
                        throw new Exception('Un snapshot existe déjà en date du <span class="label-black">' . $this->targetDateFormatted . '</span>');
                    }
                }
            }
        }

        $this->op->log->steplogWrite();

        //// TRAITEMENT ////

        /**
         *  2. Création du répertoire du repo/section
         */
        if ($this->packageType == "rpm") {
            $repoPath = REPOS_DIR . '/' . DATE_DMY . '_' . $this->name;
        }
        if ($this->packageType == "deb") {
            $repoPath = REPOS_DIR . '/' . $this->name . '/' . $this->dist . '/' . DATE_DMY . '_' . $this->section;
        }

        /**
         *  Si le répertoire existe déjà, on le supprime
         */
        if (is_dir($repoPath)) {
            exec("rm -rf " . $repoPath);
        }
        /**
         *  Création du répertoire
         */
        if (!mkdir($repoPath, 0770, true)) {
            throw new Exception("la création du répertoire <b>" . $repoPath . "</b> a échouée");
        }

        $this->op->log->steplogWrite();

        /**
         *  3. Récupération des paquets
         */
        echo '<div class="hide getPackagesDiv"><pre>';
        $this->op->log->steplogWrite();

        // File descriptors for each subprocess. http://phptutorial.info/?proc-open
        /* $descriptors = [
            0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
            1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
            2 => array("file", "{$this->op->log->steplog}", "a") // stderr is a file to write to
        ];*/
        // https://gist.github.com/swichers/027d5ae903350cbd4af8
        $descriptors = array(
            // Must use php://stdin(out) in order to allow display of command output
            // and the user to interact with the process.
            0 => array('file', 'php://stdin', 'r'),
            1 => array('file', 'php://stdout', 'w'),
            2 => array('pipe', 'w'),
        );

        if ($this->packageType == "rpm") {
            /**
             *  Vérification de la présence d'un lock de yum (autre tâche yum déjà en cours)
             *  Si c'est le cas on attend que le lock soit libéré
             */
            // $lockFile = 0;

            /**
             *  Compte le nombre de répertoires de lock présents
             */
            // foreach (glob("/var/tmp/yum-*") as $lockFound) {
            //     $lockFile++;
            // }

            // if ($lockFile != 0) {
            //     echo 'En attente de la libération du lock yum...';

            //     $this->op->log->steplogWrite();

            //     /**
            //      *  On boucle tant que le lock est en place
            //      */
            //     while ($lockFile != 0) {
            //         sleep(2);

            //         $lockFile = 0;

            //         foreach (glob("/var/tmp/yum-*") as $lockFound) {
            //             $lockFile++;
            //         }
            //     }
            // }

            /**
             *  Note : pour reposync il faut impérativement rediriger la sortie standard vers la sortie d'erreur car c'est uniquement cette dernière qui est capturée par proc_open. On fait ça pour avoir non seulement les erreurs mais aussi tout le déroulé normal de reposync.
             */
            if ($this->getTargetGpgCheck() == "no") {
                if (strpos(OS_VERSION, '7') === 0) {
                    $process = proc_open('exec reposync --config=' . REPOMANAGER_YUM_DIR . '/repomanager.conf -l --repoid=' . $this->source . ' --norepopath --download_path="' . $repoPath . '/" 1>&2', $descriptors, $pipes);
                }
                if (strpos(OS_VERSION, '8') === 0 or strpos(OS_VERSION, '9') === 0) {
                    $process = proc_open('exec reposync --config=' . REPOMANAGER_YUM_DIR . '/repomanager.conf --nogpgcheck --repoid=' . $this->source . ' --download-path "' . $repoPath . '/" 1>&2', $descriptors, $pipes);
                }
            } else { // Dans tous les autres cas (même si rien n'a été précisé) on active gpgcheck
                if (strpos(OS_VERSION, '7') === 0) {
                    $process = proc_open('exec reposync --config=' . REPOMANAGER_YUM_DIR . '/repomanager.conf --gpgcheck -l --repoid=' . $this->source . ' --norepopath --download_path="' . $repoPath . '/" 1>&2', $descriptors, $pipes);
                }
                if (strpos(OS_VERSION, '8') === 0 or strpos(OS_VERSION, '9') === 0) {
                    $process = proc_open('exec reposync --config=' . REPOMANAGER_YUM_DIR . '/repomanager.conf --repoid=' . $this->source . ' --download-path "' . $repoPath . '/" 1>&2', $descriptors, $pipes);
                }
            }
        }

        if ($this->packageType == "deb") {
            /**
             *  Dans le cas où on a précisé de ne pas vérifier les signatures GPG
             */
            if ($this->getTargetGpgCheck() == "no") {
                $process = proc_open('exec /usr/bin/debmirror --no-check-gpg --nosource --passive --method=http --rsync-extra=none --root=' . $this->rootUrl . ' --dist=' . $dist . ' --host=' . $this->hostUrl . ' --section=' . $this->section . ' --arch=amd64 ' . REPOS_DIR . '/' . $this->name . '/' . $this->dist . '/' . DATE_DMY . '_' . $this->section . ' --getcontents --ignore-release-gpg --progress --i18n --include="Translation-fr.*\.bz2" --postcleanup', $descriptors, $pipes);

            /**
             *  Dans tous les autres cas (même si rien n'a été précisé)
             */
            } else {
                $process = proc_open('exec /usr/bin/debmirror --check-gpg --keyring=' . GPGHOME . '/trustedkeys.gpg --nosource --passive --method=http --rsync-extra=none --root=' . $this->rootUrl . ' --dist=' . $this->dist . ' --host=' . $this->hostUrl . ' --section=' . $this->section . ' --arch=amd64 ' . REPOS_DIR . '/' . $this->name . '/' . $this->dist . '/' . DATE_DMY . '_' . $this->section . ' --getcontents --ignore-release-gpg --progress --i18n --include="Translation-fr.*\.bz2" --postcleanup', $descriptors, $pipes);
            }
        }

        /**
         *  Récupération du pid et du status du process lancé
         *  Puis écriture du pid de reposync/debmirror (lancé par proc_open) dans le fichier PID principal, ceci afin qu'il puisse être killé si l'utilisateur le souhaites
         */
        $proc_details = proc_get_status($process);
        file_put_contents(PID_DIR . "/{$this->op->log->pid}.pid", "SUBPID=\"" . $proc_details['pid'] . "\"" . PHP_EOL, FILE_APPEND);

        /**
         *  Tant que le process (lancé par proc_open) n'est pas terminé, on boucle afin de ne pas continuer les étapes suivantes
         */
        do {
            $status = proc_get_status($process);
            // If our stderr pipe has data, grab it for use later.
            if (!feof($pipes[2])) {
                // We're acting like passthru would and displaying errors as they come in.
                $error_line = fgets($pipes[2]);
                file_put_contents($this->op->log->steplog, $error_line, FILE_APPEND);
            }
        } while ($status['running'] === true);

        /**
         *  Clôture du process
         */
        proc_close($process);
        echo '</pre></div>';

        $this->op->log->steplogWrite();

        /**
         *  Récupération du code d'erreur de reposync/debmirror
         */
        $return = $status['exitcode'];

        if ($return != 0) {
            /**
             *  Suppression de ce qui a été fait :
             */
            if ($this->packageType == "rpm") {
                exec('rm -rf "' . REPOS_DIR . '/' . DATE_DMY . '_' . $this->name . '"');
            }
            if ($this->packageType == "deb") {
                exec('rm -rf "' . REPOS_DIR . '/' . $this->name . '/' . $this->dist . '/' . DATE_DMY . '_' . $this->section . '"');
            }

            throw new Exception('erreur lors de la récupération des paquets');
        }

        $this->op->log->steplogOK();

        return true;
    }

    /**
     *  Signature des paquets (Redhat) avec GPG
     *  Opération exclusive à Redhat car sous Debian c'est le fichier Release du repo qu'on signe
     */
    private function signPackages()
    {
        $warning = 0;

        ob_start();

        /**
         *  Signature des paquets du repo avec GPG
         *  Redhat seulement car sur Debian c'est le fichier Release qui est signé ors de la création du repo
         */
        if ($this->packageType == "rpm" and $this->targetGpgResign == "yes") {
            $this->op->log->steplogInitialize('signPackages');
            $this->op->log->steplogTitle('SIGNATURE DES PAQUETS (GPG)');

            $descriptors = array(
                0 => array('file', 'php://stdin', 'r'),
                1 => array('file', 'php://stdout', 'w'),
                2 => array('pipe', 'w')
            );

            echo '<div class="hide signRepoDiv"><pre>';
            $this->op->log->steplogWrite();

            /**
             *  On se mets à la racine du repo
             *  Activation de globstar (**), cela permet à bash d'aller chercher des fichiers .rpm récursivement, peu importe le nb de sous-répertoires
             */
            if (file_exists("/usr/bin/rpmresign")) {
                $process = proc_open('shopt -s globstar && cd "' . REPOS_DIR . '/' . $this->targetDateFormatted . '_' . $this->name . '" && /usr/bin/rpmresign --path "' . GPGHOME . '" --name "' . GPG_KEYID . '" --passwordfile "' . PASSPHRASE_FILE . '" **/*.rpm 1>&2', $descriptors, $pipes);
            }

            /**
             *  Récupération du pid et du status du process lancé
             *  Puis écriture du pid de reposync/debmirror (lancé par proc_open) dans le fichier PID principal, ceci afin qu'il puisse être killé si l'utilisateur le souhaites
             */
            $proc_details = proc_get_status($process);
            file_put_contents(PID_DIR . '/' . $this->op->log->pid . '.pid', 'SUBPID="' . $proc_details['pid'] . '"' . PHP_EOL, FILE_APPEND);

            /**
             *  Tant que le process (lancé par proc_open) n'est pas terminé, on boucle afin de ne pas continuer les étapes suivantes
             */
            do {
                $status = proc_get_status($process);
                // If our stderr pipe has data, grab it for use later.
                if (!feof($pipes[2])) {
                    // We're acting like passthru would and displaying errors as they come in.
                    $error_line = fgets($pipes[2]);
                    file_put_contents($this->op->log->steplog, $error_line, FILE_APPEND);
                }
            } while ($status['running'] === true);

                /**
                 *  Clôture du process
                 */
                proc_close($process);
                echo '</pre></div>';

            $this->op->log->steplogWrite();

            /**
             *  Récupération du code d'erreur de rpmresign
             */
            $return = $status['exitcode'];

            /**
             *  Si il y a un pb avec rpmresign, celui-ci renvoie systématiquement le code 0 même si il est en erreur.
             *  Du coup on vérifie directement dans l'output du programme qu'il n'y a pas eu de message d'erreur et si c'est le cas alors on incrémente $return
             */
            if (preg_match('/gpg: signing failed/', file_get_contents($this->op->log->steplog))) {
                ++$return;
            }
            if (preg_match('/No secret key/', file_get_contents($this->op->log->steplog))) {
                ++$return;
            }
            if (preg_match('/error: gpg/', file_get_contents($this->op->log->steplog))) {
                ++$return;
            }
            if (preg_match("/Can't resign/", file_get_contents($this->op->log->steplog))) {
                ++$return;
            }
            /**
             *  Cas particulier, on affichera un warning si le message suivant a été détecté dans les logs
             */
            if (preg_match("/gpg: WARNING:/", file_get_contents($this->op->log->steplog))) {
                ++$warning;
            }

            if ($warning != 0) {
                $this->op->log-> steplogWarning();
            }

            if ($return != 0) {
                /**
                 *  Si l'action est reconstruct alors on ne supprime pas ce qui a été fait (sinon ça supprime le repo!)
                 */
                if ($this->op->getAction() != "reconstruct") {
                    /**
                     *  Suppression de ce qui a été fait :
                     */
                    exec('rm -rf "' . REPOS_DIR . '/' . $dateFormatted . '_' . $name . '"');
                }

                throw new Exception('la signature des paquets a échouée');
            }

            $this->op->log->steplogOK();
        }

        return true;
    }

    /**
     *  Création des metadata du repo (Redhat) et des liens symboliques (environnements)
     */
    private function createRepo()
    {
        ob_start();

        $this->op->log->steplogInitialize('createRepo');
        $this->op->log->steplogTitle('CRÉATION DU REPO');

        echo '<div class="hide createRepoDiv"><pre>';

        $this->op->log->steplogWrite();

        if ($this->packageType == "rpm") {
            /**
             *  Si un répertoire my_uploaded_packages existe, alors on déplace ses éventuels packages
             */
            if (is_dir(REPOS_DIR . '/' . $this->targetDateFormatted . '_' . $this->name . '/my_uploaded_packages/')) {
                /**
                 *  Création du répertoire my_integrated_packages qui intègrera les paquets intégrés au repo
                 */
                if (!is_dir(REPOS_DIR . '/' . $this->targetDateFormatted . '_' . $this->name . '/my_integrated_packages/')) {
                    mkdir(REPOS_DIR . '/' . $this->targetDateFormatted . '_' . $this->name . '/my_integrated_packages/', 0770, true);
                }

                /**
                 *  Déplacement des paquets dans my_uploaded_packages vers my_integrated_packages
                 */
                if (!\Models\Common::dirIsEmpty(REPOS_DIR . '/' . $this->targetDateFormatted . '_' . $this->name . '/my_uploaded_packages/')) {
                    exec('mv -f ' . REPOS_DIR . '/' . $this->targetDateFormatted . '_' . $this->name . '/my_uploaded_packages/*.rpm ' . REPOS_DIR . '/' . $this->targetDateFormatted . '_' . $this->name . '/my_integrated_packages/');
                }

                /**
                 *  Suppression de my_uploaded_packages
                 */
                rmdir(REPOS_DIR . '/' . $this->targetDateFormatted . '_' . $this->name . '/my_uploaded_packages/');
            }

            exec('createrepo -v ' . REPOS_DIR . '/' . $this->targetDateFormatted . '_' . $this->name . '/ 1>&2 >> ' . $this->op->log->steplog, $output, $return);
            echo '</pre></div>';

            $this->op->log->steplogWrite();
        }

        if ($this->packageType == "deb") {
            $descriptors = array(
                0 => array('file', 'php://stdin', 'r'),
                1 => array('file', 'php://stdout', 'w'),
                2 => array('pipe', 'w')
            );

            /**
             *  On va créer et utiliser un répertoire temporaire pour travailler
             */
            $TMP_DIR = "/tmp/{$this->op->log->pid}_deb_packages";
            if (!mkdir($TMP_DIR, 0770, true)) {
                throw new Exception("impossible de créer le répertoire temporaire /tmp/{$this->op->log->pid}_deb_packages");
            }

            $this->op->log->steplogWrite();

            /**
             *  On se mets à la racine de la section
             *  On recherche tous les paquets .deb et on les déplace dans le répertoire temporaire
             */
            $sectionPath = REPOS_DIR . '/' . $this->name . '/' . $this->dist . '/' . $this->targetDateFormatted . '_' . $this->section;
            if (!is_dir($sectionPath)) {
                throw new Exception("le répertoire du repo n'existe pas");
            }
            if (!is_dir($TMP_DIR)) {
                throw new Exception("le répertoire temporaire n'existe pas");
            }
            exec("find $sectionPath/ -name '*.deb' -exec mv '{}' ${TMP_DIR}/ \;");

            /**
             *  Après avoir déplacé tous les paquets on peut supprimer tout le contenu de la section
             */
            exec("rm -rf $sectionPath/*");

            /**
             *  Création du répertoire 'conf' et des fichiers de conf du repo
             */
            if (!is_dir($sectionPath . '/conf')) {
                if (!mkdir($sectionPath . '/conf', 0770, true)) {
                    throw new Exception("impossible de créer le répertoire de configuration du repo (/conf)");
                }
            }

            /**
             *  Création du fichier "distributions"
             *  Son contenu sera différent suivant si on a choisi de chiffrer ou non le repo
             */
            if ($this->targetGpgResign == "yes") {
                $file_distributions_content = 'Origin: Repo ' . $this->name . ' sur ' . WWW_HOSTNAME . PHP_EOL . 'Label: apt repository' . PHP_EOL . 'Codename: ' . $this->dist . PHP_EOL . 'Architectures: i386 amd64' . PHP_EOL . 'Components: ' . $this->section . PHP_EOL . 'Description: Repo ' . $this->name . ', miroir du repo ' . $this->source . ', distribution ' . $this->dist . ', section ' . $this->section . PHP_EOL . 'SignWith: ' . GPG_KEYID . PHP_EOL . 'Pull: ' . $this->section;
            } else {
                $file_distributions_content = 'Origin: Repo ' . $this->name . ' sur ' . WWW_HOSTNAME . PHP_EOL . 'Label: apt repository' . PHP_EOL . 'Codename: ' . $this->dist . PHP_EOL . 'Architectures: i386 amd64' . PHP_EOL . 'Components: ' . $this->section . PHP_EOL . 'Description: Repo ' . $this->name . ', miroir du repo ' . $this->source . ', distribution ' . $this->dist . ', section ' . $this->section . PHP_EOL . 'Pull: ' . $this->section;
            }

            if (!file_put_contents($sectionPath . '/conf/distributions', $file_distributions_content . PHP_EOL)) {
                throw new Exception('impossible de créer le fichier de configuration du repo (distributions)');
            }

            /**
             *  Création du fichier "options"
             *  Son contenu sera différent suivant si on a choisi de chiffrer ou non le repo
             */
            if ($this->targetGpgResign == "yes") {
                $file_options_content = "basedir $sectionPath\nask-passphrase";
            } else {
                $file_options_content = "basedir $sectionPath";
            }

            if (!file_put_contents($sectionPath . '/conf/options', $file_options_content . PHP_EOL)) {
                throw new Exception('impossible de créer le fichier de configuration du repo (options)');
            }

            /**
             *  Si le répertoire temporaire ne contient aucun paquet (càd si le repo est vide) alors on ne traite pas et on incrémente $return afin d'afficher une erreur.
             */
            if (\Models\Common::dirIsEmpty($TMP_DIR) === true) {
                echo "Il n'y a aucun paquets dans ce repo";
                echo '</pre></div>';

                $return = 1;

            /**
             *  Sinon on peut traiter
             */
            } else {
                /**
                 *  Création du repo en incluant les paquets deb du répertoire temporaire, et signature du fichier Release
                 */
                if ($this->targetGpgResign == "yes") {
                    $process = proc_open("for DEB_PACKAGE in ${TMP_DIR}/*.deb; do /usr/bin/reprepro --basedir $sectionPath/ --gnupghome " . GPGHOME . " includedeb " . $this->dist . " \$DEB_PACKAGE; rm \$DEB_PACKAGE -f;done 1>&2", $descriptors, $pipes);
                } else {
                    $process = proc_open("for DEB_PACKAGE in ${TMP_DIR}/*.deb; do /usr/bin/reprepro --basedir $sectionPath/ includedeb " . $this->dist . " \$DEB_PACKAGE; rm \$DEB_PACKAGE -f;done 1>&2", $descriptors, $pipes);
                }

                /**
                 *  Récupération du pid et du status du process lancé
                 *  Ecriture du pid de reposync/debmirror (lancé par proc_open) dans le fichier PID principal, ceci afin qu'il puisse être killé si l'utilisateur le souhaites
                 */
                $proc_details = proc_get_status($process);
                file_put_contents(PID_DIR . '/' . $this->op->log->pid . '.pid', 'SUBPID="' . $proc_details['pid'] . '"' . PHP_EOL, FILE_APPEND);

                /**
                 *  Tant que le process (lancé par proc_open) n'est pas terminé, on boucle afin de ne pas continuer les étapes suivantes
                 */
                do {
                    $status = proc_get_status($process);

                    // If our stderr pipe has data, grab it for use later.
                    if (!feof($pipes[2])) {
                        // We're acting like passthru would and displaying errors as they come in.
                        $error_line = fgets($pipes[2]);
                        file_put_contents($this->op->log->steplog, $error_line, FILE_APPEND);
                    }
                } while ($status['running'] === true);

                /**
                 *  Clôture du process
                 */
                proc_close($process);
                echo '</pre></div>';

                $this->op->log->steplogWrite();

                /**
                 *  Suppression du répertoire temporaire
                 */
                if ($this->packageType == "deb" and is_dir($TMP_DIR)) {
                    exec("rm -rf '$TMP_DIR'");
                }

                /**
                 *  Récupération du code d'erreur de reprepro
                 */
                $return = $status['exitcode'];
            }
        }

        if ($return != 0) {
            /**
             *  Suppression de ce qui a été fait :
             */
            if ($this->op->getAction() != "reconstruct") {
                if ($this->packageType == "rpm") {
                    exec('rm -rf "' . REPOS_DIR . '/' . $this->targetDateFormatted . '_' . $this->name . '"');
                }
                if ($this->packageType == "deb") {
                    exec('rm -rf "' . REPOS_DIR . '/' . $this->name . '/' . $this->dist . '/' . $this->targetDateFormatted . '_' . $this->section . '"');
                }
            }

            throw new Exception('la création du repo a échouée');
        }

        $this->op->log->steplogWrite();

        /**
         *  Création du lien symbolique (environnement)
         *  Uniquement si l'utilisateur a spécifié de faire pointer un environnement sur le snapshot créé
         */
        if ($this->op->getAction() == "new" or $this->op->getAction() == "update") {
            if (!empty($this->targetEnv)) {
                if ($this->packageType == "rpm") {
                    exec('cd ' . REPOS_DIR . '/ && ln -sfn ' . $this->targetDateFormatted . '_' . $this->name . ' ' . $this->name . '_' . $this->targetEnv, $output, $result);
                }
                if ($this->packageType == "deb") {
                    exec('cd ' . REPOS_DIR . '/' . $this->name . '/' . $this->dist . '/ && ln -sfn ' . $this->targetDateFormatted . '_' . $this->section . ' ' . $this->section . '_' . $this->targetEnv, $output, $result);
                }
                if ($result != 0) {
                    throw new Exception('la finalisation du repo a échouée');
                }
            }
        }

        $this->op->log->steplogOK();

        return true;
    }

    /**
    *   Finalisation du repo : ajout en base de données et application des droits
    */
    private function finalize()
    {
        ob_start();

        $this->op->log->steplogInitialize('finalizeRepo');
        $this->op->log->steplogTitle('FINALISATION');

        /**
         *  Le type d'opération doit être renseigné pour cette fonction (soit "new" soit "update")
         */
        if (empty($this->op->getAction())) {
            throw new Exception("type d'opération inconnu (vide)");
        }
        if ($this->op->getAction() != "new" and $this->op->getAction() != "update") {
            throw new Exception("type d'opération invalide");
        }

        /**
         *  1. Mise à jour de la BDD
         *  - Si il s'agit d'un nouveau repo on l'ajoute en base de données
         */
        if ($this->op->getAction() == "new") {
            /**
             *  Vérification de l'existance du repo en base de données
             */
            if ($this->packageType == "rpm") {
                $exists = $this->model->exists($this->name);
            }
            if ($this->packageType == "deb") {
                $exists = $this->model->sectionExists($this->name, $this->dist, $this->section);
            }

            /**
             *  Si actuellement aucun repo de ce nom n'existe en base de données alors on l'ajoute
             */
            if ($exists === false) {
                if ($this->packageType == "rpm") {
                    $this->model->add($this->getSource(), 'rpm', $this->name);
                }
                if ($this->packageType == "deb") {
                    $this->model->add($this->getSource(), 'deb', $this->name, $this->dist, $this->section);
                }

                /**
                 *  L'Id du repo devient alors l'Id de la dernière ligne insérée en base de données
                 */
                $this->repoId = $this->model->getLastInsertRowID();

            /**
             *  Sinon si un repo de même nom existe, on rattache ce nouveau snapshot et ce nouvel env à ce repo
             */
            } else {
                /**
                 *  D'abord on récupère l'Id en base de données du repo
                 */
                if ($this->packageType == "rpm") {
                    $this->repoId = $this->model->db_getId($this->name, '', '');
                }

                if ($this->packageType == "deb") {
                    $this->repoId = $this->model->db_getId($this->name, $this->dist, $this->section);
                }
            }
            unset($exists);

            /**
             *  Ajout du snapshot en base de données
             */
            $this->model->addSnap($this->targetDate, $this->targetTime, $this->targetGpgResign, $this->type, 'active', $this->repoId);

            /**
             *  Récupération de l'Id du snapshot ajouté précédemment
             */
            $this->setSnapId($this->model->getLastInsertRowID());

            /**
             *  Ajout de l'env en base de données, si un environnement a été spécifié par l'utilisateur
             */
            if (!empty($this->targetEnv)) {
                $this->model->addEnv($this->targetEnv, $this->targetDescription, $this->snapId);
            }
        }

        if ($this->op->getAction() == "update") {
            /**
             *  Dans le cas où la nouvelle date du snapshot est la même que l'ancienne
             *  (cas où on remet à jour le même snapshot le même jour) alors on met seulement à jour quelques
             *  informations de base du repo en base de données et rien d'autre.
             */
            if ($this->targetDate == $this->date) {
                /**
                 *  Mise à jour de l'état de la signature GPG
                 */
                $this->model->snapSetSigned($this->snapId, $this->targetGpgResign);

                /**
                 *  Mise à jour de la date
                 */
                $this->model->snapSetDate($this->snapId, date('Y-m-d'));

                /**
                 *  Mise à jour de l'heure
                 */
                $this->model->snapSetTime($this->snapId, date('H:i'));

            /**
             *  Sinon on ajoute un nouveau snapshot en base de données à la date du jour
             */
            } else {
                /**
                 *  Cas où un nouveau snapshot a été créé, on l'ajoute en base de données
                 */
                $this->model->addSnap($this->targetDate, $this->targetTime, $this->targetGpgResign, 'mirror', 'active', $this->repoId);

                /**
                 *  On récupère l'Id du snapshot précédemment créé
                 *  Et on peut du coup définir que snapId = cet Id
                 */
                $this->snapId = $this->model->getLastInsertRowID();
            }
        }

        /**
         *  Si l'utilisateur a renseigné un environnement à faire pointer sur le snapshot créé
         */
        if (!empty($this->targetEnv)) {

            /**
             *  Si l'utilisateur n'a précisé aucune description alors on récupère celle actuellement en place sur l'environnement de même nom (si l'environnement existe et si il possède une description)
             */
            if (empty($this->targetDescription)) {
                if ($this->packageType == 'rpm') {
                    $actualDescription = $this->model->getDescriptionByName($this->name, '', '', $this->targetEnv);
                }
                if ($this->packageType == 'deb') {
                    $actualDescription = $this->model->getDescriptionByName($this->name, $this->dist, $this->section, $this->targetEnv);
                }

                /**
                 *  Si la description récupérée est vide alors la description restera vide
                 */
                if (!empty($actualDescription)) {
                    $this->targetDescription = $actualDescription;
                } else {
                    $this->targetDescription = '';
                }
            }

            /**
             *  On récupère l'Id de l'environnement actuellement an place (si il y en a un)
             */
            $actualEnvId = $this->model->getEnvIdFromRepoName($this->name, $this->dist, $this->section, $this->targetEnv);

            /**
             *  On supprime l'éventuel environnement de même nom pointant déjà vers un snapshot de ce repo (si il y en a un)
             */
            if (!empty($actualEnvId)) {
                $this->model->deleteEnv($actualEnvId);
            }

            /**
             *  Puis on déclare le nouvel environnement et on le fait pointer vers le snapshot précédemment créé
             */
            $this->model->addEnv($this->targetEnv, $this->targetDescription, $this->snapId);
        }

        /**
         *  3. Application des droits sur le snapshot créé
         */
        if ($this->packageType == "rpm") {
            exec('find ' . REPOS_DIR . '/' . $this->targetDateFormatted . '_' . $this->name . '/ -type f -exec chmod 0660 {} \;');
            exec('find ' . REPOS_DIR . '/' . $this->targetDateFormatted . '_' . $this->name . '/ -type d -exec chmod 0770 {} \;');
            exec('chown -R ' . WWW_USER . ':repomanager ' . REPOS_DIR . '/' . $this->targetDateFormatted . '_' . $this->name);
            /*if [ $? -ne "0" ];then
                echo "<br><span class=\"redtext\">Erreur :</span>l'application des permissions sur le repo <b>$this->name</b> a échoué"
            fi*/
        }
        if ($this->packageType == "deb") {
            exec('find ' . REPOS_DIR . '/' . $this->name . '/' . $this->dist . '/' . $this->targetDateFormatted . '_' . $this->section . '/ -type f -exec chmod 0660 {} \;');
            exec('find ' . REPOS_DIR . '/' . $this->name . '/' . $this->dist . '/' . $this->targetDateFormatted . '_' . $this->section . '/ -type d -exec chmod 0770 {} \;');
            exec('chown -R ' . WWW_USER . ':repomanager ' . REPOS_DIR . '/' . $this->name);
            /*if [ $? -ne "0" ];then
                echo "<br><span class=\"redtext\">Erreur :</span>l'application des permissions sur la section <b>$this->section</b> a échoué"
            fi*/
        }

        $this->op->log->steplogOK();

        /**
         *  Ajout du repo à un groupe si un groupe a été renseigné.
         *  Uniquement si il s'agit d'un nouveau repo/section ($this->op->getAction() = new)
         */
        if ($this->op->getAction() == 'new' and !empty($this->targetGroup)) {
            $this->op->log->steplogInitialize('addToGroup');
            $this->op->log->steplogTitle('AJOUT A UN GROUPE');
            $this->addRepoIdToGroup($this->repoId, $this->targetGroup);
            $this->op->log->steplogOK();
        }

        /**
         *  Nettoyage automatique des snapshots inutilisés
         */
        $snapshotsRemoved = $this->cleanSnapshots();

        if (!empty($snapshotsRemoved)) {
            $this->op->log->steplogInitialize('removeSnapshots');
            $this->op->log->steplogTitle('NETTOYAGE');
            $this->op->log->steplogOK($snapshotsRemoved);
        }

        /**
         *  Nettoyage des repos inutilisés dans les groupes
         */
        $this->cleanGroups();

        return true;
    }

    /**
     *  Ajouter / supprimer des repos dans un groupe
     */
    public function addReposIdToGroup(array $reposId = null, string $groupName)
    {
        /**
         *  On aura besoin d'un objet Group()
         */
        $mygroup = new \Controllers\Group('repo');
        $groupId = $mygroup->getIdByName($groupName);

        if (!empty($reposId)) {
            foreach ($reposId as $repoId) {
                /**
                 *  On vérifie que l'Id de repo spécifié existe en base de données
                 */
                if ($this->model->existsId($repoId) === false) {
                    throw new Exception("L'Id de repo $repoId spécifié n'existe pas");
                }

                $repo = $this->getAllById($repoId);

                $repoName = $this->name;
                $repoDist = $this->dist;
                $repoSection = $this->section;

                /**
                 *  Ajout du repo au groupe
                 */
                $this->model->addToGroup($repoId, $groupId);
            }
        }

        /**
         *  3. On récupère la liste des repos actuellement dans le groupe afin de supprimer ceux qui n'ont pas été sélectionnés
         */
        $actualReposMembers = $this->model->getReposGroupMembers($groupId);

        /**
         *  4. Parmis cette liste on ne récupère que les Id des repos actuellement membres
         */
        $actualReposId = array();

        foreach ($actualReposMembers as $actualRepoMember) {
            $actualReposId[] = $actualRepoMember['repoId'];
        }

        /**
         *  5. Enfin, on supprime tous les Id de repos actuellement membres qui n'ont pas été spécifiés par l'utilisateur
         */
        foreach ($actualReposId as $actualRepoId) {
            if (!in_array($actualRepoId, $reposId)) {
                $this->model->removeFromGroup($actualRepoId, $groupId);
            }
        }

        \Models\History::set($_SESSION['username'], "Modifications des repos/sections membres du groupe <b>$groupName</b>", 'success');

        \Models\Common::clearCache();
    }

    /**
     *  Ajouter un repo à un groupe par Id
     */
    public function addRepoIdToGroup(string $repoId, string $groupName)
    {
        /**
         *  On aura besoin d'un objet Group()
         */
        $mygroup = new \Controllers\Group('repo');
        $groupId = $mygroup->getIdByName($groupName);

        $this->model->addToGroup($repoId, $groupId);
    }

    public function envSetDescription(string $envId, string $description)
    {
        return $this->model->envSetDescription($envId, $description);
    }

    /**
     *  Génère un <select> contenant la liste des repos par groupe
     */
    public function selectRepoByGroup($groupName)
    {
        /**
         *  On aura besoin d'un objet Group()
         */
        $mygroup = new \Controllers\Group('repo');

        /**
         *  On vérifie que le groupe existe
         */
        if ($mygroup->exists($groupName) === false) {
            throw new Exception("Le groupe $groupName n'existe pas");
        }

        /**
         *  Récupération de l'Id du groupe en base de données
         */
        $groupId = $mygroup->getIdByName($groupName);

        /**
         *  Récupération de tous les repos membres de ce groupe
         */
        $reposIn = $this->model->getReposGroupMembers($groupId);

        /**
         *  Récupération de tous les repos membres d'aucun groupe
         */
        $reposNotIn = $this->model->getReposNotMembersOfAnyGroup();

        echo '<select class="reposSelectList" groupname="' . $groupName . '" name="groupAddRepoName[]" multiple>';

            /**
             *  Les repos membres du groupe seront par défaut sélectionnés dans la liste
             */
        if (!empty($reposIn)) {
            foreach ($reposIn as $repo) {
                $repoId = $repo['repoId'];
                $repoName = $repo['Name'];
                $repoDist = $repo['Dist'];
                $repoSection = $repo['Section'];
                $repoPackageType = $repo['Package_type'];

                if ($repoPackageType == "rpm") {
                    echo '<option value="' . $repoId . '" selected>' . $repoName . '</option>';
                }
                if ($repoPackageType == "deb") {
                    echo '<option value="' . $repoId . '" selected>' . $repoName . ' ❯ ' . $repoDist . ' ❯ ' . $repoSection . '</option>';
                }
            }
        }

            /**
             *  Les repos non-membres du groupe seront dé-sélectionnés dans la liste
             */
        if (!empty($reposNotIn)) {
            foreach ($reposNotIn as $repo) {
                $repoId = $repo['repoId'];
                $repoName = $repo['Name'];
                $repoDist = $repo['Dist'];
                $repoSection = $repo['Section'];
                $repoPackageType = $repo['Package_type'];

                if ($repoPackageType == "rpm") {
                    echo '<option value="' . $repoId . '">' . $repoName . '</option>';
                }
                if ($repoPackageType == "deb") {
                    echo '<option value="' . $repoId . '">' . $repoName . ' ❯ ' . $repoDist . ' ❯ ' . $repoSection . '</option>';
                }
            }
        }

        echo '</select>';

        unset($mygroup, $reposIn, $reposNotIn);
    }

    /**
     *  Nettoyage des snapshots inutilisés
     */
    public function cleanSnapshots()
    {
        /**
         *  1. Si le nettoyage automatique n'est pas autorisé alors on quitte la fonction
         */
        if (ALLOW_AUTODELETE_ARCHIVED_REPOS != "yes") {
            return;
        }

        if (!is_int(RETENTION) or RETENTION < 0) {
            return;
        }

        /**
         *  On récupère tous les Id et noms de repos
         */
        $repos = $this->model->listNameOnly(true);

        /**
         *  Pour chaque repo on récupère la liste des snapshots inutilisés (snapshots qui n'ont aucun environnement actif) et on les traite si il y en a
         */
        if (!empty($repos)) {
            foreach ($repos as $repo) {
                $repoId = $repo['Id'];
                $repoName = $repo['Name'];
                if (!empty($repo['Dist'])) {
                    $repoDist = $repo['Dist'];
                }
                if (!empty($repo['Section'])) {
                    $repoSection = $repo['Section'];
                }
                $packageType = $repo['Package_type'];

                /**
                 *  Récupération des snapshots inutilisés de ce repo
                 */
                $unusedSnapshots = $this->model->getUnunsedSnapshot($repoId, RETENTION);

                /**
                 *  Si il y a des snapshots inutilisés alors on traite
                 */
                if (!empty($unusedSnapshots)) {
                    $removedSnaps = array();
                    $removedSnapsError = array();
                    $output = '';

                    foreach ($unusedSnapshots as $unusedSnapshot) {
                        $snapId = $unusedSnapshot['snapId'];
                        $snapDate = $unusedSnapshot['Date'];
                        $snapDateFormatted = DateTime::createFromFormat('Y-m-d', $snapDate)->format('d-m-Y');

                        if ($packageType == 'rpm') {
                            if (is_dir(REPOS_DIR . '/' . $snapDateFormatted . '_' . $repoName)) {
                                exec('rm -rf ' . REPOS_DIR . '/' . $snapDateFormatted . '_' . $repoName, $output, $result);
                            }
                        }
                        if ($packageType == 'deb') {
                            if (is_dir(REPOS_DIR . '/' . $repoName . '/' . $repoDist . '/' . $snapDateFormatted . '_' . $repoSection)) {
                                exec('rm -rf ' . REPOS_DIR . '/' . $repoName . '/' . $repoDist . '/' . $snapDateFormatted . '_' . $repoSection, $output, $result);
                            }
                        }

                        if (is_numeric($result)) {
                            /**
                             *  Cas où le snapshot a été supprimé avec succès
                             */
                            if ($result == 0) {
                                if ($packageType == 'rpm') {
                                    $removedSnaps[] = 'Le snapshot <span class="label-white">' . $repoName . '</span>⟶<span class="label-black">' . $snapDateFormatted . '</span> a été supprimé';
                                }
                                if ($packageType == 'deb') {
                                    $removedSnaps[] = 'Le snapshot <span class="label-white">' . $repoName . ' ❯ ' . $repoDist . ' ❯ ' . $repoSection . '</span>⟶<span class="label-black">' . $snapDateFormatted . '</span> a été supprimé';
                                }

                            /**
                             *  Cas où il y a eu une erreur lors de la suppression
                             */
                            } else {
                                if ($packageType == 'rpm') {
                                    $removedSnapsError[] = 'Erreur lors de la suppression automatique du snapshot <span class="label-white">' . $repoName . '</span>⟶<span class="label-black">' . $snapDateFormatted . '</span>';
                                }
                                if ($packageType == 'deb') {
                                    $removedSnapsError[] = 'Erreur lors de la suppression automatique du snapshot <span class="label-white">' . $repoName . ' ❯ ' . $repoDist . ' ❯ ' . $repoSection . '</span>⟶<span class="label-black">' . $snapDateFormatted . '</span>';
                                }

                                /**
                                 *  On passe au snapshot suivant (et donc on ne change pas le status du snapshot en base de données puisqu'il n'a pas pu être supprimé)
                                 */
                                continue;
                            }
                        }

                        /**
                         *  Changement du status en base de données
                         */
                        $this->model->snapSetStatus($snapId, 'deleted');

                        /**
                         *  On merge les deux array
                         */
                        $removedSnapsFinalArray = array_merge($removedSnaps, $removedSnapsError);

                        /**
                         *  On forge le message qui sera affiché dans le log
                         */
                        foreach ($removedSnapsFinalArray as $removedSnap) {
                            $output .= $removedSnap . '<br>';
                        }

                        if (!empty($output)) {
                            return $output;
                        }
                    }
                }
            }
        }
    }

    /**
     *  Retire des groupes les repos qui n'ont plus aucun snapshot actif
     */
    public function cleanGroups()
    {
        /**
         *  D'abord on récupère tous les les Id de repos
         */
        $repoIds = $this->model->getAllRepoId();

        /**
         *  Pour chaque Id on regarde si il y a au moins 1 snapshot actif
         */
        foreach ($repoIds as $repoId) {
            $id = $repoId['Id'];
            $activeSnapshots = $this->model->getSnapByRepoId($id, 'active');

            /**
             *  Si le repo n'a plus aucun snapshot actif alors on le retire des groupes
             */
            if (empty($activeSnapshots)) {
                $this->model->removeFromGroup($id);
            }
        }
    }
}
