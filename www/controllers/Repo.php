<?php

namespace Controllers;

use Exception;
use Datetime;

class Repo
{
    private $model;
    private $op;
    private $repoId;
    private $snapId;
    private $envId;
    private $planId;
    private $name;
    private $source;
    private $packageType;
    private $arch;
    private $sourcePackagesIncluded;
    private $translationIncluded;
    private $dist;
    private $section;
    private $date;
    private $dateFormatted;
    private $time;
    private $env;
    private $description;
    private $signed;
    private $type; // mirror ou local
    private $status;
    private $reconstruct;

    /**
     *  Mirroring parameters
     */
    private $fullUrl;
    private $hostUrl;
    private $rootUrl;
    private $gpgCheck;
    private $gpgResign;
    private $workingDir;
    private $rpmSignMethod = RPM_SIGN_METHOD;
    private $onlySyncDifference = 'no';

    private $targetName;
    private $targetDate;
    private $targetTime;
    private $targetEnv;
    private $targetGroup;
    private $targetDescription;
    private $targetGpgCheck;
    private $targetGpgResign;
    private $targetArch;
    private $targetSourcePackage = 'no';
    private $targetPackageTranslation = array();

    /**
     *  Operation properties
     */
    private $poolId;

    public function __construct()
    {
        $this->model = new \Models\Repo();
    }

    public function setRepoId(string $id)
    {
        $this->repoId = Common::validateData($id);
    }

    public function setSnapId(string $id)
    {
        $this->snapId = Common::validateData($id);
    }

    public function setEnvId(string $id)
    {
        $this->envId = Common::validateData($id);
    }

    public function setPlanId(string $id)
    {
        $this->planId = Common::validateData($id);
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

    public function setReconstruct(string $reconstruct)
    {
        $this->reconstruct = $reconstruct;
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

        $this->description = Common::validateData($description);
    }

    public function setSource(string $source)
    {
        $this->source = $source;
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

    public function setArch(array $arch)
    {
        $this->arch = $arch;
    }

    public function setIncludePackageSource(string $sourcePackagesIncluded)
    {
        $this->sourcePackagesIncluded = $sourcePackagesIncluded;
    }

    public function setPackageTranslation(array $translationIncluded)
    {
        $this->translationIncluded = $translationIncluded;
    }

    public function setTargetArch(array $targetArch)
    {
        $this->targetArch = $targetArch;
    }

    public function setTargetPackageSource(string $targetSourcePackage)
    {
        $this->targetSourcePackage = $targetSourcePackage;
    }

    public function setTargetPackageTranslation(array $targetPackageTranslation)
    {
        $this->targetPackageTranslation = $targetPackageTranslation;
    }

    public function setOnlySyncDifference(string $onlySyncDifference)
    {
        $this->onlySyncDifference = $onlySyncDifference;
    }

    public function setPoolId(string $poolId)
    {
        $this->poolId = $poolId;
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

    public function getReconstruct()
    {
        return $this->reconstruct;
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

    public function getArch()
    {
        return $this->arch;
    }

    public function getPackageSource()
    {
        return $this->sourcePackagesIncluded;
    }

    public function getPackageTranslation()
    {
        return $this->translationIncluded;
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

    public function getOpStatus()
    {
        return $this->op->getStatus();
    }

    public function getOpError()
    {
        return $this->op->getError();
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
        if (!empty($data['Reconstruct'])) {
            $this->setReconstruct($data['Reconstruct']);
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
        if (!empty($data['Arch'])) {
            $this->setArch(explode(',', $data['Arch']));
        }
        if (!empty($data['Pkg_source'])) {
            $this->setIncludePackageSource($data['Pkg_source']);
        }
        if (!empty($data['Pkg_translation'])) {
            $this->setPackageTranslation(explode(',', $data['Pkg_translation']));
        }
        // /**
        //  *  Get URL full source unless getFullSource is false
        //  */
        // if ($getFullSource !== false) {
        //     $this->getFullSource($this->packageType, $this->source);
        // }
    }

    /**
     *  Retourne l'Id d'un repo en base de données, à partir de son nom
     */
    public function getIdByName(string $name, string $dist = null, string $section = null)
    {
        return $this->model->getIdByName($name, $dist, $section);
    }

    // private function getFullSource(string $sourceType, string $sourceName)
    // {
    //     $mysource = new Source();

    //     $this->fullUrl = $mysource->getUrl($sourceType, $sourceName);

    //     if (empty($this->fullUrl)) {
    //         throw new Exception('Cannot determine repo source URL');
    //     }

    //     /**
    //      *  Get more informations if deb
    //      */
    //     if ($sourceType == 'deb') {
    //         /**
    //          *  Extract host address (server.domain.net) from the URL
    //          */
    //         $splitUrl = preg_split('#/#', $this->fullUrl);
    //         $this->hostUrl = $splitUrl[0];

    //         /**
    //          *  Extract root
    //          */
    //         $this->rootUrl = str_replace($this->hostUrl, '', $this->fullUrl);

    //         if (empty($this->hostUrl)) {
    //             throw new Exception('Cannot determine repo source address');
    //         }
    //         if (empty($this->rootUrl)) {
    //             throw new Exception('Cannot determine repo source URL root');
    //         }
    //     }

    //     unset($mysource, $splitUrl);
    // }

    /**
     *  Retourne l'Id du snapshot le + récent du repo
     */
    public function getLastSnapshotId(string $repoId)
    {
        return $this->model->getLastSnapshotId($repoId);
    }

    /**
     *  Get unused repos Id (repos that have no active snapshot and so are not visible from web UI)
     */
    public function getUnusedRepos()
    {
        return $this->model->getUnusedRepos();
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
     *  Retourne true si une opération est en cours sur l'Id de snapshot spécifié
     */
    public function snapOpIsRunning(string $snapId)
    {
        return $this->model->snapOpIsRunning($snapId);
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
    public function count()
    {
        return $this->model->count();
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
        $this->op = new Operation();
        $this->op->setAction('new');
        $this->op->setType('manual');
        $this->op->setPoolId($this->poolId);

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
         *  Nettoyage du cache
         */
        Common::clearCache();

        /**
         *  Lancement du script externe qui va construire le fichier de log principal à partir des petits fichiers de log de chaque étape
         */
        $steps = 7;
        exec('php ' . LOGBUILDER . ' ' . PID_DIR . "/{$this->op->log->pid}.pid {$this->op->log->location} " . TEMP_DIR . "/{$this->op->log->pid} $steps >/dev/null 2>/dev/null &");

        try {
            /**
             *  Etape 1 : Afficher les détails de l'opération
             */
            $this->printDetails('CREATE A NEW ' . strtoupper($this->packageType) . ' REPOSITORY MIRROR');

            /**
             *   Etape 2 : récupération des paquets
             */
            $this->getPackages();

            /**
             *   Etape 3 : signature des paquets/du repo
             */
            $this->signPackages();

            /**
             *   Etape 4 : Création du repo et liens symboliques
             */
            $this->createRepo();

            /**
             *   Etape 5 : Finalisation du repo (ajout en BDD et application des droits)
             */
            $this->finalize();

            /**
             *  Passage du status de l'opération en done
             */
            $this->op->setStatus('done');
        } catch (\Exception $e) {
            $this->op->stepError($e->getMessage()); // On transmets l'erreur à $this->op->stepError() qui va se charger de l'afficher en rouge dans le fichier de log

            /**
             *  Passage du status de l'opération en erreur
             */
            $this->op->setStatus('error');
            $this->op->setError($e->getMessage());
        }

        /**
         *  Cloture de l'opération
         */
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
        $this->op = new Operation();
        $this->op->setAction('new');
        $this->op->setType('manual');
        $this->op->setPoolId($this->poolId);

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
         *  Nettoyage du cache
         */
        Common::clearCache();

        /**
         *  Lancement du script externe qui va construire le fichier de log principal à partir des petits fichiers de log de chaque étape
         */
        $steps = 2;
        exec('php ' . LOGBUILDER . ' ' . PID_DIR . "/{$this->op->log->pid}.pid {$this->op->log->location} " . TEMP_DIR . "/{$this->op->log->pid} $steps >/dev/null 2>/dev/null &");

        try {
            ob_start();

            /**
             *  1. Génération du tableau récapitulatif de l'opération
             */
            include(ROOT . '/templates/tables/op-new-local.inc.php');

            $this->op->step('CREATING REPO');

            /**
             *  2. On vérifie que le nom du repo n'est pas vide
             */
            if (empty($this->name)) {
                throw new Exception('Repo name cannot be empty');
            }

            /**
             *  3. Création du répertoire avec le nom du repo, et les sous-répertoires permettant d'acceuillir les futurs paquets
             */
            if ($this->packageType == 'rpm') {
                if (!is_dir(REPOS_DIR . '/' . $this->targetDateFormatted . '_' . $this->name . '/packages')) {
                    if (!mkdir(REPOS_DIR . '/' . $this->targetDateFormatted . '_' . $this->name . '/packages', 0770, true)) {
                        throw new Exception('Could not create directory ' . REPOS_DIR . '/' . $this->targetDateFormatted . '_' . $this->name . '/packages');
                    }
                }
            }
            if ($this->packageType == 'deb') {
                if (!is_dir(REPOS_DIR . '/' . $this->name . '/' . $this->dist . '/' . $this->targetDateFormatted . '_' . $this->section . '/pool/' . $this->section)) {
                    if (!mkdir(REPOS_DIR . '/' . $this->name . '/' . $this->dist . '/' . $this->targetDateFormatted . '_' . $this->section . '/pool/' . $this->section, 0770, true)) {
                        throw new Exception('Could not create directory ' . REPOS_DIR . '/' . $this->name . '/' . $this->dist . '/' . $this->targetDateFormatted . '_' . $this->section . '/pool/' . $this->section);
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
                    throw new Exception('Could not point environment to the repository');
                }
            }

            /**
             *  Vérification de l'existance du repo en base de données
             */
            if ($this->packageType == "rpm") {
                $exists = $this->model->exists($this->name);
            }
            if ($this->packageType == "deb") {
                $exists = $this->model->exists($this->name, $this->dist, $this->section);
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
            $this->model->addSnap($this->targetDate, $this->targetTime, 'no', $this->targetArch, $this->targetSourcePackage, $this->targetPackageTranslation, $this->type, 'active', $this->repoId);

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

            $this->op->stepOK();

            /**
             *  7. Ajout de la section à un groupe si un groupe a été renseigné
             */
            if (!empty($this->targetGroup)) {
                $this->op->step('ADDING TO GROUP');
                $this->addRepoIdToGroup($this->repoId, $this->targetGroup);
                $this->op->stepOK();
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
             *  On transmets l'erreur à $this->op->stepError() qui va se charger de l'afficher en rouge dans le fichier de log
             */
            $this->op->stepError($e->getMessage());

            /**
             *  Passage du status de l'opération en erreur
             */
            $this->op->setStatus('error');
            $this->op->setError($e->getMessage());
        }

        /**
         *  Cloture de l'opération
         */
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
        $this->op = new Operation();
        $this->op->setAction('update');
        $this->op->setType('manual');
        $this->op->setPoolId($this->poolId);

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
         *  Nettoyage du cache
         */
        Common::clearCache();

        /**
         *  Lancement du script externe qui va construire le fichier de log principal à partir des petits fichiers de log de chaque étape
         */
        $steps = 7;
        exec('php ' . LOGBUILDER . ' ' . PID_DIR . "/{$this->op->log->pid}.pid {$this->op->log->location} " . TEMP_DIR . "/{$this->op->log->pid} $steps >/dev/null 2>/dev/null &");

        try {
            /**
             *  Etape 1 : Afficher les détails de l'opération
             */
            $this->printDetails('UPDATE REPO');

            /**
             *   Etape 2 : récupération des paquets
             */
            $this->getPackages();

            /**
             *   Etape 3 : signature des paquets/du repo
             */
            $this->signPackages();

            /**
             *   Etape 4 : Création du repo et liens symboliques
             */
            $this->createRepo();

            /**
             *   Etape 6 : Finalisation du repo (ajout en BDD et application des droits)
             */
            $this->finalize();

            /**
             *  Passage du status de l'opération en done
             */
            $this->op->setStatus('done');
        } catch (\Exception $e) {
            $this->op->stepError($e->getMessage()); // On transmets l'erreur à $this->op->stepError() qui va se charger de l'afficher en rouge dans le fichier de log

            /**
             *  Passage du status de l'opération en erreur
             */
            $this->op->setStatus('error');
            $this->op->setError($e->getMessage());

            /**
             *  Cloture de l'opération
             */
            $this->op->closeOperation();

            /**
             *  Cas où cette fonction est lancée par une planification : la planif attend un retour, on lui renvoie false pour lui indiquer qu'il y a eu une erreur
             */
            return false;
        }
        /**
         *  Cloture de l'opération
         */
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
        $this->op = new Operation();
        $this->op->setAction('duplicate');
        $this->op->setType('manual');
        $this->op->setPoolId($this->poolId);

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
         *  Nettoyage du cache
         */
        Common::clearCache();

        /**
         *  Lancement du script externe qui va construire le fichier de log principal à partir des petits fichiers de log de chaque étape
         */
        $steps = 4;
        exec('php ' . LOGBUILDER . ' ' . PID_DIR . "/{$this->op->log->pid}.pid {$this->op->log->location} " . TEMP_DIR . "/{$this->op->log->pid} $steps >/dev/null 2>/dev/null &");

        try {
            ob_start();

            /**
             *  1. Génération du tableau récapitulatif de l'opération
             */
            include(ROOT . '/templates/tables/op-duplicate.inc.php');

            $this->op->step('DUPLICATING');

            /**
             *  On vérifie que le snapshot source existe
             */
            if ($this->model->existsSnapId($this->snapId) === false) {
                throw new Exception("Source repo snapshot does not exist");
            }

            /**
             *  On vérifie qu'un repo de même nom cible n'existe pas déjà
             */
            if ($this->packageType == "rpm") {
                if ($this->model->isActive($this->targetName) === true) {
                    throw new Exception('a repo <span class="label-black">' . $this->targetName . '</span> already exists');
                }
            }
            if ($this->packageType == "deb") {
                if ($this->model->isActive($this->targetName, $this->dist, $this->section) === true) {
                    throw new Exception('a repo <span class="label-black">' . $this->targetName . ' ❯ ' . $this->dist . ' ❯ ' . $this->section . '</span> already exists');
                }
            }

            /**
             *  Création du nouveau répertoire avec le nouveau nom du repo :
             */
            if ($this->packageType == "rpm") {
                if (!file_exists(REPOS_DIR . '/' . $this->dateFormatted . '_' . $this->targetName)) {
                    if (!mkdir(REPOS_DIR . '/' . $this->dateFormatted . '_' . $this->targetName, 0770, true)) {
                        throw new Exception("cannot create directory for the new repo <b>" . $this->targetName . "</b>");
                    }
                }
            }
            if ($this->packageType == "deb") {
                if (!file_exists(REPOS_DIR . '/' . $this->targetName . '/' . $this->dist . '/' . $this->dateFormatted . '_' . $this->section)) {
                    if (!mkdir(REPOS_DIR . '/' . $this->targetName . '/' . $this->dist . '/' . $this->dateFormatted . '_' . $this->section, 0770, true)) {
                        throw new Exception("cannot create directory for the new repo <b>" . $this->targetName . "</b>");
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
                throw new Exception('cannot copy data from the source repo to the new repo');
            }

            $this->op->stepOK();

            /**
             *  On a deb repo, the duplicated repo must be rebuilded
             */
            if ($this->packageType == "deb") {
                /**
                 *  For the needs of the createRepo function, name of the repo to create must be in $name
                 *  Temporary backuping the actual name then replace it with $this->targetName
                 */
                $backupName = $this->name;
                $this->setName($this->targetName);
                $this->setTargetDate($this->date);

                $this->createRepo();

                /**
                 *  Set back the backuped name
                 */
                $this->setName($backupName);
            }

            $this->op->step('FINALIZING');

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
                    throw new Exception('cannot set repo environment');
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
            $this->model->addSnap($this->date, $this->time, $this->signed, $this->targetArch, $this->targetSourcePackage, $this->targetPackageTranslation, $this->type, $this->status, $targetRepoId);

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
                exec('find ' . REPOS_DIR . '/' . $this->dateFormatted . '_' . $this->targetName . '/ -type d -exec chmod 0770 {} \;');
                exec('chown -R ' . WWW_USER . ':repomanager ' . REPOS_DIR . '/' . $this->dateFormatted . '_' . $this->targetName);
            }
            if ($this->packageType == "deb") {
                exec('find ' . REPOS_DIR . '/' . $this->targetName . '/' . $this->dist . '/' . $this->dateFormatted . '_' . $this->section . '/ -type f -exec chmod 0660 {} \;');
                exec('find ' . REPOS_DIR . '/' . $this->targetName . '/' . $this->dist . '/' . $this->dateFormatted . '_' . $this->section . '/ -type d -exec chmod 0770 {} \;');
                exec('chown -R ' . WWW_USER . ':repomanager ' . REPOS_DIR . '/' . $this->targetName . '/' . $this->dist . '/' . $this->dateFormatted . '_' . $this->section);
            }

            $this->op->stepOK();

            /**
             *  10. Ajout de la section à un groupe si un groupe a été renseigné
             */
            if (!empty($this->targetGroup)) {
                $this->op->step('ADDING TO GROUP');

                /**
                 *  Ajout du repo créé au groupe spécifié
                 */
                $this->addRepoIdToGroup($targetRepoId, $this->targetGroup);

                $this->op->stepOK();
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
             *  On transmets l'erreur à $this->op->stepError() qui va se charger de l'afficher en rouge dans le fichier de log
             */
            $this->op->stepError($e->getMessage());

            /**
             *  Passage du status de l'opération en erreur
             */
            $this->op->setStatus('error');
            $this->op->setError($e->getMessage());
        }

        /**
         *  Cloture de l'opération
         */
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
        $this->op = new Operation();
        $this->op->setAction('reconstruct');
        $this->op->setType('manual');
        $this->op->setPoolId($this->poolId);

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
         *  Nettoyage du cache
         */
        Common::clearCache();

        /**
         *  Lancement du script externe qui va construire le fichier de log principal à partir des petits fichiers de log de chaque étape
         */
        $steps = 3;
        exec('php ' . LOGBUILDER . ' ' . PID_DIR . "/{$this->op->log->pid}.pid {$this->op->log->location} " . TEMP_DIR . "/{$this->op->log->pid} $steps >/dev/null 2>/dev/null &");

        /**
         *  Modification de l'état de reconstruction des métadonnées du snapshot en base de données
         */
        $this->model->snapSetReconstruct($this->snapId, 'running');

        try {
            /**
             *  Etape 1 : Afficher les détails de l'opération
             */
            $this->printDetails('REBUILD REPO METADATA');

            /**
            *   Etape 2 : signature des paquets/du repo
            */
            $this->signPackages();

            /**
            *   Etape 3 : Création du repo et liens symboliques
            */
            $this->createRepo();

            /**
             *  Etape 4 : on modifie l'état de la signature du repo en BDD
             *  Comme on a reconstruit les fichiers du repo, il est possible qu'on soit passé d'un repo signé à un repo non-signé, ou inversement
             *  Il faut donc modifier l'état en BDD
             */
            $this->model->snapSetSigned($this->snapId, $this->targetGpgResign);

            /**
             *  Modification de l'état de reconstruction des métadonnées du snapshot en base de données
             */
            $this->model->snapSetReconstruct($this->snapId, '');

            /**
             *  Passage du status de l'opération en done
             */
            $this->op->setStatus('done');
        } catch (\Exception $e) {
            $this->op->stepError($e->getMessage()); // On transmets l'erreur à $this->op->stepError() qui va se charger de l'afficher en rouge dans le fichier de log

            /**
             *  Passage du status de l'opération en erreur
             */
            $this->op->setStatus('error');
            $this->op->setError($e->getMessage());

            /**
             *  Modification de l'état de reconstruction des métadonnées du snapshot en base de données
             */
            $this->model->snapSetReconstruct($this->snapId, 'failed');
        }
        /**
         *  Cloture de l'opération
         */
        $this->op->closeOperation();
    }

    /**
     *  Suppression d'un snapshot de repo
     */
    public function delete()
    {
        $this->op = new Operation();
        $this->op->setAction('delete');
        $this->op->setType('manual');
        $this->op->setPoolId($this->poolId);

        $this->op->startOperation(array('id_snap_target' => $this->snapId));

        /**
         *  Ajout du PID de ce processus dans le fichier PID
         */
        $this->op->log->addsubpid(getmypid());

        /**
         *  Nettoyage du cache
         */
        Common::clearCache();

        /**
         *  Lancement du script externe qui va construire le fichier de log principal à partir des petits fichiers de log de chaque étape
         */
        $steps = 1;
        exec('php ' . LOGBUILDER . ' ' . PID_DIR . "/{$this->op->log->pid}.pid {$this->op->log->location} " . TEMP_DIR . "/{$this->op->log->pid} $steps >/dev/null 2>/dev/null &");

        try {
            ob_start();

            /**
             *  1. Génération du tableau récapitulatif de l'opération
             */
            include(ROOT . '/templates/tables/op-delete.inc.php');

            $this->op->step('DELETING');

            /**
             *  2. Suppression du snapshot
             */
            $result = 0;

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

            if ($result != 0) {
                throw new Exception('cannot delete snapshot of the <span class="label-black">' . $this->dateFormatted . '</span>');
            }

            $this->op->stepOK();

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
             *  On transmets l'erreur à $this->op->stepError() qui va se charger de l'afficher en rouge dans le fichier de log
             */
            $this->op->stepError($e->getMessage());

            /**
             *  Passage du status de l'opération en erreur
             */
            $this->op->setStatus('error');
            $this->op->setError($e->getMessage());
        }

        /**
         *  Cloture de l'opération
         */
        $this->op->closeOperation();
    }

    /**
     *  Suppression d'un environnement
     */
    public function removeEnv()
    {
        $this->op = new Operation();
        $this->op->setAction('removeEnv');
        $this->op->setType('manual');
        /**
         *  Ce type d'opération ne comporte pas de réel poolId car elle est exécutée en dehors du process habituel
         */
        $this->op->setPoolId('00000');

        $this->op->startOperation(array(
            'id_snap_target' => $this->snapId,
            'id_env_target' => $this->env));

        /**
         *  Ajout du PID de ce processus dans le fichier PID
         */
        $this->op->log->addsubpid(getmypid());

        /**
         *  Nettoyage du cache
         */
        Common::clearCache();

        /**
         *  Lancement du script externe qui va construire le fichier de log principal à partir des petits fichiers de log de chaque étape
         */
        $steps = 2;
        exec('php ' . LOGBUILDER . ' ' . PID_DIR . "/{$this->op->log->pid}.pid {$this->op->log->location} " . TEMP_DIR . "/{$this->op->log->pid} $steps >/dev/null 2>/dev/null &");

        try {
            ob_start();

            /**
             *  1. Génération du tableau récapitulatif de l'opération
             */
            include(ROOT . '/templates/tables/op-remove-env.inc.php');

            $this->op->step('DELETING');

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

            $this->op->stepOK();

            /**
             *  Nettoyage automatique des snapshots inutilisés
             */
            $snapshotsRemoved = $this->cleanSnapshots();

            if (!empty($snapshotsRemoved)) {
                $this->op->step('CLEANING');
                $this->op->stepOK($snapshotsRemoved);
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
             *  On transmets l'erreur à $this->op->stepError() qui va se charger de l'afficher en rouge dans le fichier de log
             */
            $this->op->stepError($e->getMessage());

            /**
             *  Passage du status de l'opération en erreur
             */
            $this->op->setStatus('error');
            $this->op->setError($e->getMessage());
        }

        /**
         *  Cloture de l'opération
         */
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
        $this->op = new Operation();
        $this->op->setAction('env');
        $this->op->setType('manual');
        $this->op->setPoolId($this->poolId);

        if ($this->op->getType() == 'manual') {
            $this->op->startOperation(array(
                'id_snap_target' => $this->snapId,
                'id_env_target' => $this->targetEnv));
        }

        /**
         *  Ajout du PID de ce processus dans le fichier PID
         */
        $this->op->log->addsubpid(getmypid());

        /**
         *  Nettoyage du cache
         */
        Common::clearCache();

        /**
         *  Lancement du script externe qui va construire le fichier de log principal à partir des petits fichiers de log de chaque étape
         */
        $steps = 4;
        exec('php ' . LOGBUILDER . ' ' . PID_DIR . "/{$this->op->log->pid}.pid {$this->op->log->location} " . TEMP_DIR . "/{$this->op->log->pid} $steps >/dev/null 2>/dev/null &");

        try {
            ob_start();

            /**
             *  1. Génération du tableau récapitulatif de l'opération
             */
            include(ROOT . '/templates/tables/op-env.inc.php');

            $this->op->step('ADDING NEW ENVIRONMENT ' . Common::envtag($this->targetEnv));

            /**
             *  2. On vérifie si le snapshot source existe
             */
            if ($this->model->existsSnapId($this->snapId) === false) {
                throw new Exception('Target snapshot does not exist');
            }

            /**
             *  3. On vérifie qu'un même environnement pointant vers le snapshot cible n'existe pas déjà
             */
            if ($this->model->existsSnapIdEnv($this->snapId, $this->targetEnv) === true) {
                if ($this->packageType == 'rpm') {
                    throw new Exception('A ' . Common::envtag($this->targetEnv) . ' environment already exists on <span class="label-white">' . $this->name . '</span>⟶<span class="label-black">' . $this->dateFormatted . '</span>');
                }

                if ($this->packageType == 'deb') {
                    throw new Exception('A ' . Common::envtag($this->targetEnv) . ' environment already exists on <span class="label-white">' . $this->name . ' ❯ ' . $this->dist . ' ❯ ' . $this->section . '</span>⟶<span class="label-black">' . $this->dateFormatted . '</span>');
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
                    $this->op->stepOK();

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
                    $this->op->stepOK();
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
                    $this->op->stepOK();

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
                    $this->op->stepOK();
                }
            }

            $this->op->step('FINALIZING');

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
            $this->op->stepOK();

            /**
             *  Nettoyage automatique des snapshots inutilisés
             */
            $snapshotsRemoved = $this->cleanSnapshots();

            if (!empty($snapshotsRemoved)) {
                $this->op->step('CLEANING');
                $this->op->stepOK($snapshotsRemoved);
            }

            /**
             *  Nettoyage des repos inutilisés dans les groupes
             */
            $this->cleanGroups();

            /**
             *  Nettoyage du cache
             */
            Common::clearCache();

            /**
             *  Passage du status de l'opération en done
             */
            $this->op->setStatus('done');
        } catch (\Exception $e) {
            /**
             *  On transmets l'erreur à $this->op->stepError() qui va se charger de l'afficher en rouge dans le fichier de log
             */
            $this->op->stepError($e->getMessage());

            /**
             *  Passage du status de l'opération en erreur
             */
            $this->op->setStatus('error');
            $this->op->setError($e->getMessage());

            /**
             *  Cloture de l'opération
             */
            $this->op->closeOperation();

            /**
             *  Cas où cette fonction est lancée par une planification : la planif attend un retour, on lui renvoie false pour lui indiquer qu'il y a eu une erreur
             */
            return false;
        }

        /**
         *  Cloture de l'opération
         */
        $this->op->closeOperation();
    }

    /**
    *   Génération d'un tableau récapitulatif de l'opération
    *   Valide pour :
    *    - un nouveau repo/section
    *    - une mise à jour de repo/section
    *    - une reconstruction des métadonnées d'un repo/section
    */
    private function printDetails(string $title)
    {
        $this->op->step();

        ob_start();

        /**
         *  Affichage du tableau récapitulatif de l'opération
         */
        include(ROOT . '/templates/tables/op-new-update-reconstruct.inc.php');

        $this->op->stepWriteToLog(ob_get_clean());

        return true;
    }

    /**
     *   Récupération des paquets à partir d'un repo source
     *   $this->action = new ou update en fonction de si il s'agit d'un nouveau repo ou d'une mise à jour
     */
    private function getPackages()
    {
        ob_start();

        $this->op->step('SYNCING PACKAGES');

        //// CHECKS ////

        /**
         *  Operation type must be specified ('new' or 'update')
         */
        if (empty($this->op->getAction())) {
            throw new Exception('Operation type unknow (empty)');
        }
        if ($this->op->getAction() != "new" and $this->op->getAction() != "update") {
            throw new Exception('Operation type is invalid');
        }

        /**
         *  Verify repo type (mirror or local)
         *  If it must be a local repo then quit because we can't update a local repo
         */
        if ($this->type == 'local') {
            throw new Exception('Local repo snapshot cannot be updated');
        }

        /**
         *  2. Si il s'agit d'un nouveau repo, on vérifie qu'un repo du même nom avec un ou plusieurs snapshots actifs n'existe pas déjà.
         *  Un repo peut exister et n'avoir aucun snapshot / environnement rattachés (il sera invisible dans la liste) mais dans ce cas cela ne doit pas empêcher la création d'un nouveau repo
         *
         *  Cas nouveau snapshot de repo :
         */
        if ($this->op->getAction() == "new") {
            if ($this->packageType == "rpm") {
                if ($this->model->isActive($this->name) === true) {
                    throw new Exception('Repo <span class="label-white">' . $this->name . '</span> already exists');
                }
            }
            if ($this->packageType == "deb") {
                if ($this->model->isActive($this->name, $this->dist, $this->section) === true) {
                    throw new Exception('Repo <span class="label-white">' . $this->name . ' ❯ ' . $this->dist . ' ❯ ' . $this->section . '</span> already exists');
                }
            }
        }

        /**
         *  Target arch must be specified
         */
        if (empty($this->targetArch)) {
            throw new Exception('Packages arch must be specified');
        }

        /**
         *  Si il s'agit d'une mise à jour de snapshot de repo on vérifie que l'id du snapshot existe en base de données
         */
        if ($this->op->getAction() == "update") {
            /**
             *  Vérifie si le snapshot qu'on souhaite mettre à jour existe bien en base de données
             */
            if ($this->model->existsSnapId($this->snapId) === false) {
                throw new Exception("Specified repo snapshot does not exist");
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
                        throw new Exception('A snapshot already exists on the <span class="label-black">' . $this->targetDateFormatted . '</span>');
                    }
                }
                if ($this->packageType == 'deb') {
                    if ($this->model->existsRepoSnapDate($this->targetDate, $this->name, $this->dist, $this->section) === true) {
                        throw new Exception('A snapshot already exists on the <span class="label-black">' . $this->targetDateFormatted . '</span>');
                    }
                }
            }
        }

        $this->op->stepWriteToLog();

        //// TRAITEMENT ////

        /**
         *  2. Define final repo/section directory path
         */
        if ($this->packageType == "rpm") {
            $repoPath = REPOS_DIR . '/' . DATE_DMY . '_' . $this->name;
            $this->workingDir = REPOS_DIR . '/download-mirror-' . $this->name . '-' . time();
        }
        if ($this->packageType == "deb") {
            $repoPath = REPOS_DIR . '/' . $this->name . '/' . $this->dist . '/' . DATE_DMY . '_' . $this->section;
            $this->workingDir = REPOS_DIR . '/download-mirror-' . $this->name . '-' . $this->dist . '-' . $this->section  . '-' . time();
        }

        /**
         *  If onlySyncDifference is true then copy source snapshot content to the working dir to avoid downloading packages that already exists, and only download the new packages.
         *  This parameter is used in the case of a snapshot update only (operation 'update').
         */
        if ($this->onlySyncDifference == 'yes') {
            /**
             *  Create working dir
             */
            if (!is_dir($this->workingDir)) {
                if (!mkdir($this->workingDir, 0770, true)) {
                    throw new Exception('Cannot create temporary working directory ' . $this->workingDir);
                }
            }
            if (!is_dir($this->workingDir . '/packages')) {
                if (!mkdir($this->workingDir . '/packages', 0770, true)) {
                    throw new Exception('Cannot create temporary working directory ' . $this->workingDir . '/packages');
                }
            }

            /**
             *  Get all source snapshot informations to retrieve snapshot directory path
             */
            $sourceSnapshot = new Repo();
            $sourceSnapshot->getAllById('', $this->snapId);

            /**
             *  Retrieve source snapshot directory from the informations
             */
            if ($this->packageType == "rpm") {
                $sourceSnapshotDir = REPOS_DIR . '/' . $sourceSnapshot->getDateFormatted() . '_' . $sourceSnapshot->getName();
            }
            if ($this->packageType == "deb") {
                $sourceSnapshotDir = REPOS_DIR . '/' . $sourceSnapshot->getName() . '/' . $sourceSnapshot->getDist() . '/' . $sourceSnapshot->getDateFormatted() . '_' . $sourceSnapshot->getSection();
            }

            /**
             *  Check that source snapshot directory exists
             */
            if (!is_dir($sourceSnapshotDir)) {
                throw new Exception('Source snapshot directory does not exist: ' . $sourceSnapshotDir);
            }

            /**
             *  Find source snapshot packages
             */
            if ($this->packageType == 'rpm') {
                $rpmPackages          = Common::findAndCopyRecursive($sourceSnapshotDir, $this->workingDir . '/packages', 'rpm', true);
            }
            if ($this->packageType == 'deb') {
                $debPackages          = Common::findAndCopyRecursive($sourceSnapshotDir . '/pool', $this->workingDir . '/packages', 'deb', true);
                $dscSourcesPackages   = Common::findAndCopyRecursive($sourceSnapshotDir . '/pool', $this->workingDir . '/packages', 'dsc', true);
                $tarxzSourcesPackages = Common::findAndCopyRecursive($sourceSnapshotDir . '/pool', $this->workingDir . '/packages', 'xz', true);
                $targzSourcesPackages = Common::findAndCopyRecursive($sourceSnapshotDir . '/pool', $this->workingDir . '/packages', 'gz', true);
            }

            unset($sourceSnapshot);
        }

        /**
         *  3. Retrieving packages
         */
        echo '<div class="hide getPackagesDiv"><pre>';
        $this->op->stepWriteToLog();

        /**
         *  If syncing packages using embedded mirroring tool (beta)
         */
        if ($this->packageType == 'deb') {
            try {
                /**
                 *  Get source repo informations
                 */
                $mysource = new Source();
                $sourceDetails = $mysource->getAll('deb', $this->source);

                /**
                 *  Check source repo informations
                 */
                if (empty($sourceDetails)) {
                    throw new Exception('Could not retrieve source repo informations. Does the source repo still exists?');
                }
                if (empty($sourceDetails['Url'])) {
                    throw new Exception('Could not retrieve source repo URL. Check source repo configuration.');
                }

                unset($mysource);

                $mymirror = new Mirror();
                $mymirror->setType('deb');
                $mymirror->setUrl($sourceDetails['Url']);
                $mymirror->setWorkingDir($this->workingDir);
                $mymirror->setDist($this->dist);
                $mymirror->setSection($this->section);
                $mymirror->setArch($this->targetArch);
                $mymirror->setSyncSource($this->targetSourcePackage);
                $mymirror->setCheckSignature($this->targetGpgCheck);
                $mymirror->setTranslation($this->targetPackageTranslation);
                $mymirror->setOutputFile($this->op->log->steplog);
                $mymirror->outputToFile(true);
                if (!empty($sourceDetails['Ssl_certificate_path'])) {
                    $mymirror->setSslCustomCertificate($sourceDetails['Ssl_certificate_path']);
                }
                if (!empty($sourceDetails['Ssl_private_key_path'])) {
                    $mymirror->setSslCustomPrivateKey($sourceDetails['Ssl_private_key_path']);
                }
                $mymirror->mirror();

                /**
                 *  Create repo and dist directories if not exist
                 */
                if (!is_dir(REPOS_DIR . '/' . $this->name . '/' . $this->dist)) {
                    if (!mkdir(REPOS_DIR . '/' . $this->name . '/' . $this->dist, 0770, true)) {
                        throw new Exception('Could not create directory: ' . REPOS_DIR . '/' . $this->name . '/' . $this->dist);
                    }
                }

                /**
                 *  Renaming working dir name to final name
                 *  First delete the target directory if it already exists
                 */
                if (is_dir($repoPath)) {
                    if (!Common::deleteRecursive($repoPath)) {
                        throw new Exception('Cannot delete existing directory: ' . $repoPath);
                    }
                }
                if (!rename($this->workingDir, $repoPath)) {
                    throw new Exception('Could not rename working directory ' . $this->workingDir);
                }
            } catch (Exception $e) {
                echo '</pre></div>';
                throw new Exception($e->getMessage());
            }
        }

        if ($this->packageType == 'rpm') {
            try {
                /**
                 *  Get source repo informations
                 */
                $mysource = new Source();
                $sourceDetails = $mysource->getAll('rpm', $this->source);

                /**
                 *  Check source repo informations
                 */
                if (empty($sourceDetails)) {
                    throw new Exception('Could not retrieve source repo informations. Does the source repo still exists?');
                }
                if (empty($sourceDetails['Url'])) {
                    throw new Exception('Could not retrieve source repo URL. Check source repo configuration.');
                }

                unset($mysource);

                $mymirror = new Mirror();
                $mymirror->setType('rpm');
                $mymirror->setUrl($sourceDetails['Url']);
                $mymirror->setWorkingDir($this->workingDir);
                $mymirror->setArch($this->targetArch);
                $mymirror->setSyncSource($this->targetSourcePackage);
                $mymirror->setCheckSignature($this->targetGpgCheck);
                $mymirror->setOutputFile($this->op->log->steplog);
                $mymirror->outputToFile(true);
                /**
                 *  If the source repo has a http:// GPG signature key, then it will be used to check for package signature
                 */
                if (!empty($sourceDetails['Gpgkey'])) {
                    $mymirror->setGpgKeyUrl($sourceDetails['Gpgkey']);
                }
                if (!empty($sourceDetails['Ssl_certificate_path'])) {
                    $mymirror->setSslCustomCertificate($sourceDetails['Ssl_certificate_path']);
                }
                if (!empty($sourceDetails['Ssl_private_key_path'])) {
                    $mymirror->setSslCustomPrivateKey($sourceDetails['Ssl_private_key_path']);
                }
                $mymirror->mirror();

                /**
                 *  Renaming working dir name to final name
                 *  First delete the target directory if it already exists
                 */
                if (is_dir($repoPath)) {
                    if (!Common::deleteRecursive($repoPath)) {
                        throw new Exception('Cannot delete existing directory: ' . $repoPath);
                    }
                }
                if (!rename($this->workingDir, $repoPath)) {
                    throw new Exception('Could not rename working directory ' . $this->workingDir);
                }
            } catch (Exception $e) {
                echo '</pre></div>';
                throw new Exception($e->getMessage());
            }
        }

        echo '</pre></div>';

        $this->op->stepOK();

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
         *  Redhat seulement car sur Debian c'est le fichier Release qui est signé lors de la création du repo
         */
        if ($this->packageType == "rpm" and $this->targetGpgResign == "yes") {
            $this->op->step('SIGNING PACKAGES (GPG)');

            echo '<div class="hide signRepoDiv"><pre>';
            $this->op->stepWriteToLog();

            /**
             *  Récupération de tous les fichiers RPMs de manière récursive
             */
            $rpmFiles = Common::findRecursive(REPOS_DIR . '/' . $this->targetDateFormatted . '_' . $this->name, 'rpm', true);

            $signErrors = 0;

            /**
             *  On traite chaque fichier trouvé
             */
            foreach ($rpmFiles as $rpmFile) {
                /**
                 *  Cas où on souhaite utiliser rpmresign pour signer
                 */
                if ($this->rpmSignMethod == 'rpmresign') {
                    if (file_exists("/usr/bin/rpmresign")) {
                        /**
                         *  Instanciation d'un nouveau Process
                         */
                        $myprocess = new Process('/usr/bin/rpmresign --path "' . GPGHOME . '" --name "' . RPM_SIGN_GPG_KEYID . '" --passwordfile "' . PASSPHRASE_FILE . '" ' . $rpmFile);
                    } else {
                        throw new Exception("rpmresign bin is not found on this system");
                    }
                }

                /**
                 *  Cas où on souhaite utiliser nativement gpg pour signer, avec rpmsign (équivalent rpm --sign)
                 */
                if ($this->rpmSignMethod == 'rpmsign') {
                    /**
                     *  On a besoin d'un fichier de macros gpg, on signe uniquement si le fichier de macros est présent, sinon on retourne une erreur
                     */
                    if (file_exists(MACROS_FILE)) {
                        /**
                         *  Instanciation d'un nouveau Process
                         */
                        $myprocess = new Process('/usr/bin/rpmsign --macros=' . MACROS_FILE . ' --addsign ' . $rpmFile, array('GPG_TTY' => '$(tty)'));
                    } else {
                        throw new Exception("GPG macros file for rpm does not exist.");
                    }
                }

                /**
                 *  Exécution
                 */
                $myprocess->setBackground(true);
                $myprocess->execute();

                /**
                 *  Récupération du pid du process lancé
                 *  Puis écriture du pid de rpmsign/rpmresign (lancé par proc_open) dans le fichier PID principal, ceci afin qu'il puisse être killé si l'utilisateur le souhaite
                 */
                file_put_contents(PID_DIR . '/' . $this->op->log->pid . '.pid', 'SUBPID="' . $myprocess->getPid() . '"' . PHP_EOL, FILE_APPEND);

                /**
                 *  Affichage de l'output du process en continue dans un fichier
                 */
                $myprocess->getOutput($this->op->log->steplog);

                /**
                 *  Si la signature du paquet en cours s'est mal terminée, on incrémente $signErrors pour
                 *  indiquer une erreur et on sort de la boucle pour ne pas traiter le paquet suivant
                 */
                if ($myprocess->getExitCode() != 0) {
                    $signErrors++;
                    break;
                }

                $myprocess->close();
            }
            echo '</pre></div>';

            $this->op->stepWriteToLog();

            /**
             *  A vérifier car depuis l'écriture de la class Process, les erreurs semblent mieux gérées :
             *
             *  Si il y a un pb lors de la signature, celui-ci renvoie systématiquement le code 0 même si il est en erreur.
             *  Du coup on vérifie directement dans l'output du programme qu'il n'y a pas eu de message d'erreur et si c'est le cas alors on incrémente $return
             */
            if (preg_match('/gpg: signing failed/', file_get_contents($this->op->log->steplog))) {
                ++$signErrors;
            }
            if (preg_match('/No secret key/', file_get_contents($this->op->log->steplog))) {
                ++$signErrors;
            }
            if (preg_match('/error: gpg/', file_get_contents($this->op->log->steplog))) {
                ++$signErrors;
            }
            if (preg_match("/Can't resign/", file_get_contents($this->op->log->steplog))) {
                ++$signErrors;
            }
            /**
             *  Cas particulier, on affichera un warning si le message suivant a été détecté dans les logs
             */
            if (preg_match("/gpg: WARNING:/", file_get_contents($this->op->log->steplog))) {
                ++$warning;
            }
            if (preg_match("/warning:/", file_get_contents($this->op->log->steplog))) {
                ++$warning;
            }

            if ($warning != 0) {
                $this->op->stepWarning();
            }

            if ($signErrors != 0) {
                /**
                 *  Si l'action est reconstruct alors on ne supprime pas ce qui a été fait (sinon ça supprime le repo!)
                 */
                if ($this->op->getAction() != "reconstruct") {
                    /**
                     *  Suppression de ce qui a été fait :
                     */
                    exec('rm -rf "' . REPOS_DIR . '/' . $this->targetDateFormatted . '_' . $this->name . '"');
                }

                throw new Exception('packages signature has failed');
            }

            $this->op->stepOK();
        }

        return true;
    }

    /**
     *  Création des metadata du repo (Redhat) et des liens symboliques (environnements)
     */
    private function createRepo()
    {
        $createRepoErrors = 0;
        $repreproErrors = 0;

        ob_start();

        $this->op->step('CREATING REPO');

        echo '<div class="hide createRepoDiv"><pre>';

        $this->op->stepWriteToLog();

        if ($this->packageType == "rpm") {
            $repoPath = REPOS_DIR . '/' . $this->targetDateFormatted . '_' . $this->name;
        }
        if ($this->packageType == "deb") {
            $repoPath = REPOS_DIR . '/' . $this->name . '/' . $this->dist . '/' . $this->targetDateFormatted . '_' . $this->section;
        }

        /**
         *  If a 'my_uploaded_packages' directory exists, move them packages into 'packages' directory
         */
        if (is_dir($repoPath . '/my_uploaded_packages/')) {
            /**
             *  Create 'packages' directory if not exist
             */
            if (!is_dir($repoPath . '/packages')) {
                if (!mkdir($repoPath . '/packages', 0770, true)) {
                    throw new Exception('Could not create ' . $repoPath . '/packages directory');
                }
            }

            /**
             *  Move packages to the 'packages' directory
             */
            if (!Common::dirIsEmpty($repoPath . '/my_uploaded_packages/')) {
                $myprocess = new Process('mv -f ' . $repoPath . '/my_uploaded_packages/* ' . $repoPath . '/packages/');
                $myprocess->execute();
                if ($myprocess->getExitCode() != 0) {
                    echo $myprocess->getOutput();
                    throw new Exception('Error while moving packages from ' . $repoPath . '/my_uploaded_packages/ to ' . $repoPath . '/packages/');
                }
            }

            /**
             *  Delete 'my_uploaded_packages' dir
             */
            if (!rmdir($repoPath . '/my_uploaded_packages')) {
                throw new Exception('Could not delete ' .$repoPath . '/my_uploaded_packages/ directory');
            }
        }

        if ($this->packageType == "rpm") {
            /**
             *  Check which of createrepo or createrepo_c is present on the system
             */
            if (file_exists('/usr/bin/createrepo')) {
                $createrepo = '/usr/bin/createrepo';
            }
            if (file_exists('/usr/bin/createrepo_c')) {
                $createrepo = '/usr/bin/createrepo_c';
            }
            if (empty($createrepo)) {
                throw new Exception('Could not find createrepo on the system');
            }

            /**
             *  Instanciation d'un nouveau Process
             */
            $myprocess = new Process($createrepo . ' -v ' . $repoPath . '/');

            /**
             *  Exécution
             */
            $myprocess->setBackground(true);
            $myprocess->execute();

            /**
             *  Récupération du pid du process lancé
             *  Puis écriture du pid de createrepo (lancé par proc_open) dans le fichier PID principal, ceci afin qu'il puisse être killé si l'utilisateur le souhaite
             */
            file_put_contents(PID_DIR . '/' . $this->op->log->pid . '.pid', 'SUBPID="' . $myprocess->getPid() . '"' . PHP_EOL, FILE_APPEND);

            /**
             *  Affichage de l'output du process en continue dans un fichier
             */
            $myprocess->getOutput($this->op->log->steplog);

            if ($myprocess->getExitCode() != 0) {
                $createRepoErrors++;
            }

            $myprocess->close();

            echo '</pre></div>';

            $this->op->stepWriteToLog();
        }

        if ($this->packageType == "deb") {
            $repreproArchs = '';
            $repreproGpgParams = '';

            if (!is_dir($repoPath)) {
                echo '</pre></div>';
                throw new Exception('Repo directory does not exist');
            }

            /**
             *  If this section already has a pool directory, then it means that it is an existing section that has been duplicated or that needs to be rebuilded.
             *  Packages and source packages in pool directory will be moved in dedicated directory as if it was a brand new repo.
             */
            if ($this->op->getAction() == 'duplicate' or $this->op->getAction() == 'reconstruct') {
                /**
                 *  Create packages and sources directory
                 */
                if (!is_dir($repoPath . '/packages')) {
                    if (!mkdir($repoPath . '/packages', 0770, true)) {
                        echo '</pre></div>';
                        throw new Exception('Error: could not create directory ' . $repoPath . '/packages');
                    }
                }
                if (!is_dir($repoPath . '/sources')) {
                    if (!mkdir($repoPath . '/sources', 0770, true)) {
                        echo '</pre></div>';
                        throw new Exception('Error: could not create directory ' . $repoPath . '/sources');
                    }
                }

                /**
                 *  Recursively find all packages and sources packages
                 */
                $debPackages          = Common::findRecursive($repoPath . '/pool', 'deb', true);
                $dscSourcesPackages   = Common::findRecursive($repoPath . '/pool', 'dsc', true);
                $tarxzSourcesPackages = Common::findRecursive($repoPath . '/pool', 'xz', true);
                $targzSourcesPackages = Common::findRecursive($repoPath . '/pool', 'gz', true);

                /**
                 *  Move packages to the packages directory
                 */
                if (!empty($debPackages)) {
                    foreach ($debPackages as $debPackage) {
                        $debPackageName = preg_split('#/#', $debPackage);
                        $debPackageName = end($debPackageName);

                        if (!rename($debPackage, $repoPath . '/packages/' . $debPackageName)) {
                            echo '</pre></div>';
                            throw new Exception('Error: could not move package ' . $debPackage . ' to the packages directory');
                        }
                    }
                }

                /**
                 *  Move source packages to the sources directory
                 */
                if (!empty($dscSourcesPackages)) {
                    foreach ($dscSourcesPackages as $dscSourcesPackage) {
                        $dscSourcesPackageName = preg_split('#/#', $dscSourcesPackage);
                        $dscSourcesPackageName = end($dscSourcesPackageName);

                        if (!rename($dscSourcesPackage, $repoPath . '/sources/' . $dscSourcesPackageName)) {
                            echo '</pre></div>';
                            throw new Exception('Error: could not move source package ' . $dscSourcesPackage . ' to the sources directory');
                        }
                    }
                }

                if (!empty($tarxzSourcesPackages)) {
                    foreach ($tarxzSourcesPackages as $tarxzSourcesPackage) {
                        $tarxzSourcesPackageName = preg_split('#/#', $tarxzSourcesPackage);
                        $tarxzSourcesPackageName = end($tarxzSourcesPackageName);

                        if (!preg_match('/.tar.xz/i', $tarxzSourcesPackageName)) {
                            continue;
                        }

                        if (!rename($tarxzSourcesPackage, $repoPath . '/sources/' . $tarxzSourcesPackageName)) {
                            echo '</pre></div>';
                            throw new Exception('Error: could not move source package ' . $tarxzSourcesPackage . ' to the sources directory');
                        }
                    }
                }

                if (!empty($targzSourcesPackages)) {
                    foreach ($targzSourcesPackages as $targzSourcesPackage) {
                        $targzSourcesPackageName = preg_split('#/#', $targzSourcesPackage);
                        $targzSourcesPackageName = end($targzSourcesPackageName);

                        if (!preg_match('/.tar.gz/i', $targzSourcesPackageName)) {
                            continue;
                        }

                        if (!rename($targzSourcesPackage, $repoPath . '/sources/' . $targzSourcesPackageName)) {
                            echo '</pre></div>';
                            throw new Exception('Error: could not move source package ' . $targzSourcesPackage . ' to the sources directory');
                        }
                    }
                }

                /**
                 *  Clean existing directories
                 */
                if (!Common::deleteRecursive($repoPath . '/conf')) {
                    echo '</pre></div>';
                    throw new Exception('Cannot delete existing directory: ' . $repoPath . '/conf');
                }
                if (!Common::deleteRecursive($repoPath . '/db')) {
                    echo '</pre></div>';
                    throw new Exception('Cannot delete existing directory: ' . $repoPath . '/db');
                }
                if (!Common::deleteRecursive($repoPath . '/dists')) {
                    echo '</pre></div>';
                    throw new Exception('Cannot delete existing directory: ' . $repoPath . '/dists');
                }
                if (!Common::deleteRecursive($repoPath . '/pool')) {
                    echo '</pre></div>';
                    throw new Exception('Cannot delete existing directory: ' . $repoPath . '/pool');
                }
            }

            /**
             *  Target arch must be specified
             */
            if (empty($this->targetArch)) {
                echo '</pre></div>';
                throw new Exception('Packages arch must be specified');
            }

            $this->op->stepWriteToLog();

            /**
             *  Création du répertoire 'conf' et des fichiers de conf du repo
             */
            if (!is_dir($repoPath . '/conf')) {
                if (!mkdir($repoPath . '/conf', 0770, true)) {
                    echo '</pre></div>';
                    throw new Exception("Could not create repo configuration directory <b>$repoPath/conf</b>");
                }
            }

            /**
             *  Create "distributions" file
             *  Its content will depend on repo signature, architecture specified...
             */

            /**
             *  Define archs
             */
            foreach ($this->targetArch as $arch) {
                $repreproArchs .= ' ' . $arch;
            }

            /**
             *  If packages sources must be included, then add 'source' to the archs
             *
             *  For action like 'duplicate' or 'reconstruct', if the source repo has source packages included, then include them in the new repo
             */
            if ($this->op->getAction() == 'duplicate' or $this->op->getAction() == 'reconstruct') {
                if ($this->sourcePackagesIncluded == 'yes') {
                    $repreproArchs .= ' source';
                }
            /**
             *  For other action, include source packages or not, as defined by the user
             */
            } else {
                if ($this->targetSourcePackage == 'yes') {
                    $repreproArchs .= ' source';
                }
            }

            $distributionsFileContent = 'Origin: ' . $this->name . ' repo on ' . WWW_HOSTNAME . PHP_EOL;
            $distributionsFileContent .= 'Label: apt repository' . PHP_EOL;
            $distributionsFileContent .= 'Codename: ' . $this->dist . PHP_EOL;
            $distributionsFileContent .= 'Suite: stable' . PHP_EOL;
            $distributionsFileContent .= 'Architectures: ' . $repreproArchs . PHP_EOL;
            $distributionsFileContent .= 'Components: ' . $this->section . PHP_EOL;
            $distributionsFileContent .= 'Description: ' . $this->name . ' repo, mirror of ' . $this->source . ' - ' . $this->dist . ' - ' . $this->section . PHP_EOL;
            if ($this->targetGpgResign == "yes") {
                $distributionsFileContent .= 'SignWith: ' . DEB_SIGN_GPG_KEYID . PHP_EOL;
            }
            $distributionsFileContent .= 'Pull: ' . $this->section;

            if (!file_put_contents($repoPath . '/conf/distributions', $distributionsFileContent . PHP_EOL)) {
                throw new Exception("Could not create repo distributions file <b>$repoPath/conf/distributions</b>");
            }

            /**
             *  Create "options" file
             */
            $optionsFileContent = "basedir $repoPath" . PHP_EOL;
            if ($this->targetGpgResign == "yes") {
                $optionsFileContent .= 'ask-passphrase';
            }

            if (!file_put_contents($repoPath . '/conf/options', $optionsFileContent . PHP_EOL)) {
                throw new Exception("Could not create repo options file <b>$repoPath/conf/options</b>");
            }

            /**
             *  Si le répertoire temporaire ne contient aucun paquet (càd si le repo est vide) alors on ne traite pas et on incrémente $return afin d'afficher une erreur.
             */
            if (Common::dirIsEmpty($repoPath . '/packages') === true) {
                echo 'Error: there is no package in this repo.';
                echo '</pre></div>';
                throw new Exception('No package found in this repo');

            /**
             *  Sinon on peut traiter
             */
            } else {
                /**
                 *  Get all .deb and .dsc files in working directory
                 */
                $debPackagesFiles = Common::findRecursive($repoPath . '/packages', 'deb', true);
                $dscPackagesFiles = Common::findRecursive($repoPath . '/sources', 'dsc', true);
                $packagesFiles = array_merge($debPackagesFiles, $dscPackagesFiles);

                /**
                 *  Get all translations files if any
                 */
                if (is_dir($repoPath . '/translations/')) {
                    $translationsFiles = glob($repoPath . '/translations/*.bz2', GLOB_BRACE);
                }

                /**
                 *  To avoid 'too many argument list' error, reprepro will have to import .deb packages by lot of 100.
                 *  So we are creating arrays of deb packages paths by lot of 100.
                 */
                $debFilesGlobalArray = array();
                $debFilesArray = array();
                $i = 0;

                $dscFilesGlobalArray = array();

                foreach ($packagesFiles as $packageFile) {
                    if (preg_match('/.deb$/', $packageFile)) {
                        /**
                         *  Add deb file path to the array and increment package counter
                         */
                        $debFilesArray[] = $packageFile;
                        $i++;

                        /**
                         *  If 100 packages paths have been collected, then push the array in the global array and create a new array
                         */
                        if ($i == '100') {
                            $debFilesGlobalArray[] = $debFilesArray;
                            $debFilesArray = array();

                            /**
                             *  Reset packages counter
                             */
                            $i = 0;
                        }
                    }

                    if (preg_match('/.dsc$/', $packageFile)) {
                        /**
                         *  Add deb file path to the array and increment package counter
                         */
                        $dscFilesGlobalArray[] = $packageFile;
                    }
                }

                /**
                 *  Add the last generated array, even if has not reached 100 packages, and if not empty
                 */
                if (!empty($debFilesArray)) {
                    $debFilesGlobalArray[] = $debFilesArray;
                }

                /**
                 *  Case repo GPG signature is enabled
                 */
                if ($this->targetGpgResign == 'yes') {
                    $repreproGpgParams = '--gnupghome ' . GPGHOME;
                }

                /**
                 *  Process each lot arrays to generate a one-liner path to packages. The paths to deb files are concatened and separated by a space.
                 *  It the only way to import multiple packages with reprepro (using * wildcard coult end in 'too many argument' error)
                 */
                if (!empty($debFilesGlobalArray)) {
                    foreach ($debFilesGlobalArray as $lotArray) {
                        /**
                         *  Convert each array of 100 packages to a string
                         *
                         *  e.g:
                         *
                         *  Array(
                         *      [0] => /home/repo/.../package1.deb
                         *      [1] => /home/repo/.../package2.deb
                         *      [2] => /home/repo/.../package3.deb
                         *      ...
                         *  )
                         *
                         *  is being converted to a oneliner string:
                         *
                         *  '/home/repo/.../package1.deb /home/repo/.../package2.deb /home/repo/.../package3.deb'
                         */
                        $debFilesConcatenatePaths = trim(implode(' ', $lotArray));

                        /**
                         *  Then build the includeb command from the string generated
                         */
                        $repreproIncludeParams = 'includedeb ' . $this->dist . ' ' . $debFilesConcatenatePaths;

                        /**
                         *  Proceed to import those 100 deb packages into the repo
                         *  Instanciate a new Process
                         */
                        $myprocess = new Process('/usr/bin/reprepro --keepunusednewfiles -P optionnal --basedir ' . $repoPath . '/ ' . $repreproGpgParams . ' ' . $repreproIncludeParams);

                        /**
                         *  Execute
                         */
                        $myprocess->setBackground(true);
                        $myprocess->execute();

                        /**
                         *  Récupération du pid du process lancé
                         *  Puis écriture du pid de reprepro (lancé par proc_open) dans le fichier PID principal, ceci afin qu'il puisse être killé si l'utilisateur le souhaite
                         */
                        file_put_contents(PID_DIR . '/' . $this->op->log->pid . '.pid', 'SUBPID="' . $myprocess->getPid() . '"' . PHP_EOL, FILE_APPEND);

                        /**
                         *  Affichage de l'output du process en continue dans un fichier
                         */
                        $myprocess->getOutput($this->op->log->steplog);

                        /**
                         *  Si la signature du paquet en cours s'est mal terminée, on incrémente $signErrors pour
                         *  indiquer une erreur et on sort de la boucle pour ne pas traiter le paquet suivant
                         */
                        if ($myprocess->getExitCode() != 0) {
                            $repreproErrors++;
                            break;
                        }

                        $myprocess->close();
                    }
                }

                /**
                 *  Case sources packages must be included in the repo too
                 */
                if (!empty($dscFilesGlobalArray)) {
                    /**
                     *  Reprepro can't deal with multiple .dsc files at the same time, so we have to proceed each file one by one
                     *  Known issue https://bugs.launchpad.net/ubuntu/+source/reprepro/+bug/1479148
                     */
                    foreach ($dscFilesGlobalArray as $dscFile) {
                        $repreproIncludeParams = '-S ' . $this->section . ' includedsc ' . $this->dist . ' ' . $dscFile;

                        /**
                         *  Proceed to import those 100 deb packages into the repo
                         *  Instanciate a new Process
                         */
                        $myprocess = new Process('/usr/bin/reprepro --keepunusednewfiles -P optionnal -V --basedir ' . $repoPath . '/ ' . $repreproGpgParams . ' ' . $repreproIncludeParams);

                        /**
                         *  Execute
                         */
                        $myprocess->setBackground(true);
                        $myprocess->execute();

                        /**
                         *  Récupération du pid du process lancé
                         *  Puis écriture du pid de reprepro (lancé par proc_open) dans le fichier PID principal, ceci afin qu'il puisse être killé si l'utilisateur le souhaite
                         */
                        file_put_contents(PID_DIR . '/' . $this->op->log->pid . '.pid', 'SUBPID="' . $myprocess->getPid() . '"' . PHP_EOL, FILE_APPEND);

                        /**
                         *  Affichage de l'output du process en continue dans un fichier
                         */
                        $myprocess->getOutput($this->op->log->steplog);

                        /**
                         *  Si la signature du paquet en cours s'est mal terminée, on incrémente $signErrors pour
                         *  indiquer une erreur et on sort de la boucle pour ne pas traiter le paquet suivant
                         */
                        if ($myprocess->getExitCode() != 0) {
                            $repreproErrors++;
                            break;
                        }

                        $myprocess->close();
                    }
                }

                echo '</pre></div>';

                $this->op->stepWriteToLog();

                /**
                 *  Delete temporary directories
                 */
                if ($this->packageType == "deb") {
                    if (is_dir($repoPath . '/packages')) {
                        if (!Common::deleteRecursive($repoPath . '/packages')) {
                            throw new Exception('Cannot delete temporary directory: ' .$repoPath . '/packages');
                        }
                    }
                    if (is_dir($repoPath . '/sources')) {
                        if (!Common::deleteRecursive($repoPath . '/sources')) {
                            throw new Exception('Cannot delete temporary directory: ' .$repoPath . '/sources');
                        }
                    }
                    if (is_dir($repoPath . '/translations')) {
                        if (!Common::deleteRecursive($repoPath . '/translations')) {
                            throw new Exception('Cannot delete temporary directory: ' .$repoPath . '/translations');
                        }
                    }
                }
            }
        }

        /**
         *  If there was error with createrepo or reprepro
         */
        if ($createRepoErrors != 0 or $repreproErrors != 0) {
            /**
             *  Delete everything to make sure the operation can be relaunched (except if action is 'reconstruct')
             */
            if ($this->op->getAction() != "reconstruct") {
                if ($this->packageType == "rpm") {
                    if (!Common::deleteRecursive($repoPath)) {
                        throw new Exception('Repo creation has failed and directory cannot be cleaned: ' . $repoPath);
                    }
                }
                if ($this->packageType == "deb") {
                    if (!Common::deleteRecursive($repoPath)) {
                        throw new Exception('Repo creation has failed and directory cannot be cleaned: ' . $repoPath);
                    }
                }
            }

            throw new Exception('Repo creation has failed');
        }

        $this->op->stepWriteToLog();

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
                    throw new Exception('Repo finalization has failed');
                }
            }
        }

        $this->op->stepOK();

        return true;
    }

    /**
    *   Finalisation du repo : ajout en base de données et application des droits
    */
    private function finalize()
    {
        ob_start();

        $this->op->step('FINALIZING');

        /**
         *  Le type d'opération doit être renseigné pour cette fonction (soit "new" soit "update")
         */
        if (empty($this->op->getAction())) {
            throw new Exception('operation type unknown (empty)');
        }
        if ($this->op->getAction() != "new" and $this->op->getAction() != "update") {
            throw new Exception('operation type is invalid');
        }

        /**
         *  1. Mise à jour de la BDD
         *  - Si il s'agit d'un nouveau repo alors on l'ajoute en base de données
         */
        if ($this->op->getAction() == "new") {
            /**
             *  Si actuellement aucun repo rpm de ce nom n'existe en base de données alors on l'ajoute
             */
            if ($this->packageType == "rpm") {
                if ($this->model->exists($this->name) === false) {
                    $this->model->add($this->getSource(), 'rpm', $this->name);

                    /**
                     *  L'Id du repo devient alors l'Id de la dernière ligne insérée en base de données
                     */
                    $this->repoId = $this->model->getLastInsertRowID();

                /**
                 *  Sinon si un repo de même nom existe, on récupère son Id en base de données
                 */
                } else {
                    $this->repoId = $this->model->getIdByName($this->name, '', '');
                }
            }

            /**
             *  Si actuellement aucun repo deb de ce nom n'existe en base de données alors on l'ajoute
             */
            if ($this->packageType == "deb") {
                if ($this->model->exists($this->name, $this->dist, $this->section) === false) {
                    $this->model->add($this->getSource(), 'deb', $this->name, $this->dist, $this->section);

                    /**
                     *  L'Id du repo devient alors l'Id de la dernière ligne insérée en base de données
                     */
                    $this->repoId = $this->model->getLastInsertRowID();

                /**
                 *  Sinon si un repo de même nom existe, on récupère son Id en base de données
                 */
                } else {
                    $this->repoId = $this->model->getIdByName($this->name, $this->dist, $this->section);
                }
            }

            /**
             *  Ajout du snapshot en base de données
             */
            $this->model->addSnap($this->targetDate, $this->targetTime, $this->targetGpgResign, $this->targetArch, $this->targetSourcePackage, $this->targetPackageTranslation, $this->type, 'active', $this->repoId);

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
                $this->model->addSnap($this->targetDate, $this->targetTime, $this->targetGpgResign, $this->targetArch, $this->targetSourcePackage, $this->targetPackageTranslation, 'mirror', 'active', $this->repoId);

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

        $this->op->stepOK();

        /**
         *  Ajout du repo à un groupe si un groupe a été renseigné.
         *  Uniquement si il s'agit d'un nouveau repo/section ($this->op->getAction() = new)
         */
        if ($this->op->getAction() == 'new' and !empty($this->targetGroup)) {
            $this->op->step('ADDING TO GROUP');
            $this->addRepoIdToGroup($this->repoId, $this->targetGroup);
            $this->op->stepOK();
        }

        /**
         *  Nettoyage automatique des snapshots inutilisés
         */
        $snapshotsRemoved = $this->cleanSnapshots();

        if (!empty($snapshotsRemoved)) {
            $this->op->step('CLEANING');
            $this->op->stepOK($snapshotsRemoved);
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
        $mygroup = new Group('repo');
        $groupId = $mygroup->getIdByName($groupName);

        if (!empty($reposId)) {
            foreach ($reposId as $repoId) {
                /**
                 *  On vérifie que l'Id de repo spécifié existe en base de données
                 */
                if ($this->model->existsId($repoId) === false) {
                    throw new Exception("Specified repo Id $repoId does not exist");
                }

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

        \Models\History::set($_SESSION['username'], 'Modification of repos members of the group <span class="label-white">' . $groupName . '</span>', 'success');

        Common::clearCache();
    }

    /**
     *  Ajouter un repo à un groupe par Id
     */
    public function addRepoIdToGroup(string $repoId, string $groupName)
    {
        /**
         *  On aura besoin d'un objet Group()
         */
        $mygroup = new Group('repo');
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
        $mygroup = new Group('repo');

        /**
         *  On vérifie que le groupe existe
         */
        if ($mygroup->exists($groupName) === false) {
            throw new Exception("Group $groupName does not exist");
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
                $this->packageType = $repo['Package_type'];

                if ($this->packageType == "rpm") {
                    echo '<option value="' . $repoId . '" selected>' . $repoName . '</option>';
                }
                if ($this->packageType == "deb") {
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
                $this->packageType = $repo['Package_type'];

                if ($this->packageType == "rpm") {
                    echo '<option value="' . $repoId . '">' . $repoName . '</option>';
                }
                if ($this->packageType == "deb") {
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
        $returnOutput = '';
        $removedSnaps = array();
        $removedSnapsError = array();
        $removedSnapsFinalArray = array();

        /**
         *  1. Si le nettoyage automatique n'est pas autorisé alors on quitte la fonction
         */
        if (ALLOW_AUTODELETE_ARCHIVED_REPOS != "true") {
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
                    foreach ($unusedSnapshots as $unusedSnapshot) {
                        $snapId = $unusedSnapshot['snapId'];
                        $snapDate = $unusedSnapshot['Date'];
                        $snapDateFormatted = DateTime::createFromFormat('Y-m-d', $snapDate)->format('d-m-Y');
                        $result = '';

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
                                    $removedSnaps[] = '<span class="label-white">' . $repoName . '</span>⟶<span class="label-black">' . $snapDateFormatted . '</span> snapshot has been deleted';
                                }
                                if ($packageType == 'deb') {
                                    $removedSnaps[] = '<span class="label-white">' . $repoName . ' ❯ ' . $repoDist . ' ❯ ' . $repoSection . '</span>⟶<span class="label-black">' . $snapDateFormatted . '</span> snapshot has been deleted';
                                }

                                /**
                                 *  Changement du status en base de données
                                 */
                                $this->model->snapSetStatus($snapId, 'deleted');

                            /**
                             *  Cas où il y a eu une erreur lors de la suppression
                             */
                            } else {
                                if ($packageType == 'rpm') {
                                    $removedSnapsError[] = 'Error while automatically deleting snapshot <span class="label-white">' . $repoName . '</span>⟶<span class="label-black">' . $snapDateFormatted . '</span>';
                                }
                                if ($packageType == 'deb') {
                                    $removedSnapsError[] = 'Error while automatically deleting snapshot <span class="label-white">' . $repoName . ' ❯ ' . $repoDist . ' ❯ ' . $repoSection . '</span>⟶<span class="label-black">' . $snapDateFormatted . '</span>';
                                }

                                /**
                                 *  On passe au snapshot suivant (et donc on ne change pas le status du snapshot en base de données puisqu'il n'a pas pu être supprimé)
                                 */
                                continue;
                            }
                        }
                    }
                }
            }

            /**
             *  On merge les deux array contenant des messages de suppression ou d'erreur
             */
            if (!empty($removedSnapsError)) {
                $removedSnapsFinalArray = array_merge($removedSnapsFinalArray, $removedSnapsError);
            }

            if (!empty($removedSnaps)) {
                $removedSnapsFinalArray = array_merge($removedSnapsFinalArray, $removedSnaps);
            }

            /**
             *  Si des messages ont été récupérés alors on forge le message qui sera affiché dans le log
             */
            if (!empty($removedSnapsFinalArray)) {
                foreach ($removedSnapsFinalArray as $removedSnap) {
                    $returnOutput .= $removedSnap . '<br>';
                }
            }
        }

        return $returnOutput;
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

    /**
     *  Modification de l'état de reconstruction des métadonnées du snapshot
     */
    public function snapSetReconstruct(string $snapId, string $status = null)
    {
        $this->model->snapSetReconstruct($snapId, $status);
    }
}
