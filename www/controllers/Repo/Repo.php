<?php

namespace Controllers\Repo;

use Exception;
use Datetime;

class Repo
{
    private $model;
    private $repoListingController;

    private $poolId;
    private $repoId;
    private $snapId;
    private $envId;
    private $planId;
    private $name;
    private $source;
    private $packageType;
    private $arch;
    private $translationIncluded;
    private $dist;
    private $section;
    private $date;
    private $dateFormatted;
    private $time;
    private $env;
    private $description;
    private $signed;
    private $type; // mirror or local
    private $status;
    private $reconstruct;
    private $targetName;
    private $targetDate;
    private $targetTime;
    private $targetEnv;
    private $targetGroup;
    private $targetDescription;
    private $targetGpgCheck;
    private $targetGpgResign;
    private $releasever;
    private $targetArch;
    private $targetPackageTranslation = array();

    /**
     *  Mirroring parameters
     */
    private $gpgCheck;
    private $gpgResign;
    private $workingDir;
    private $onlySyncDifference = 'no';

    public function __construct()
    {
        $this->model = new \Models\Repo\Repo();
        $this->repoListingController = new \Controllers\Repo\Listing();
    }

    public function setRepoId(string $id)
    {
        $this->repoId = \Controllers\Common::validateData($id);
    }

    public function setSnapId(string $id)
    {
        $this->snapId = \Controllers\Common::validateData($id);
    }

    public function setEnvId(string $id)
    {
        $this->envId = \Controllers\Common::validateData($id);
    }

    public function setPlanId(string $id)
    {
        $this->planId = \Controllers\Common::validateData($id);
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

        $this->description = \Controllers\Common::validateData($description);
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

    public function setPackageTranslation(array $translationIncluded)
    {
        $this->translationIncluded = $translationIncluded;
    }

    public function setReleasever(string $releasever)
    {
        $this->releasever = $releasever;
    }

    public function setTargetArch(array $targetArch)
    {
        $this->targetArch = $targetArch;
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

    public function setWorkingDir(string $workingDir)
    {
        $this->workingDir = $workingDir;
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

    public function getReleasever()
    {
        return $this->releasever;
    }

    public function getTargetArch()
    {
        return $this->targetArch;
    }

    public function getPackageTranslation()
    {
        return $this->translationIncluded;
    }

    public function getTargetPackageTranslation()
    {
        return $this->targetPackageTranslation;
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

    public function getOnlySyncDifference()
    {
        return $this->onlySyncDifference;
    }

    public function getPoolId()
    {
        return $this->poolId;
    }

    public function getWorkingDir()
    {
        return $this->workingDir;
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
        if (!empty($data['Releasever'])) {
            $this->setReleasever($data['Releasever']);
        }
        if (!empty($data['Dist'])) {
            $this->setDist($data['Dist']);
        } else {
            $this->setDist('');
        }
        if (!empty($data['Section'])) {
            $this->setSection($data['Section']);
        } else {
            $this->setSection('');
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
        if (!empty($data['Pkg_translation'])) {
            $this->setPackageTranslation(explode(',', $data['Pkg_translation']));
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
     *  Check if repo exists in database, by its name
     */
    public function exists(string $name, string $dist = '', string $section = '')
    {
        return $this->model->exists($name, $dist, $section);
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
     *  Return true if env exists, based on its name and the snapshot Id it points to
     */
    public function existsSnapIdEnv(string $snapId, string $env)
    {
        return $this->model->existsSnapIdEnv($snapId, $env);
    }

    /**
     *  Return true if env exists, based on its name and the repo name it points to
     */
    public function existsEnv(string $name, string $dist = null, string $section = null, string $env)
    {
        return $this->model->existsEnv($name, $dist, $section, $env);
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
     *  Retourne le nombre total de repos
     */
    public function count()
    {
        return $this->model->count();
    }

    /**
     *  Ajouter / supprimer des repos dans un groupe
     */
    public function addReposIdToGroup(array $reposId = null, int $groupId)
    {
        $mygroup = new \Controllers\Group('repo');

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
        $actualReposMembers = $mygroup->getReposMembers($groupId);

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

    /**
     *  Return environment Id from repo name
     */
    public function getEnvIdFromRepoName(string $name, string $dist = null, string $section = null, string $env)
    {
        return $this->model->getEnvIdFromRepoName($name, $dist, $section, $env);
    }

    /**
     *  Get env Id(s) by snapshot Id
     */
    public function getEnvIdBySnapId(string $snapId)
    {
        return $this->model->getEnvIdBySnapId($snapId);
    }

    public function envSetDescription(string $envId, string $description)
    {
        return $this->model->envSetDescription($envId, $description);
    }

    /**
     *  Get repository environment description by the repo name
     */
    public function getDescriptionByName(string $name, string $dist = null, string $section = null, string $env)
    {
        return $this->model->getDescriptionByName($name, $dist, $section, $env);
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
        if (PLANS_CLEAN_REPOS != "true") {
            return;
        }

        if (!is_int(RETENTION) or RETENTION < 0) {
            return;
        }

        /**
         *  On récupère tous les Id et noms de repos
         */
        $repos = $this->repoListingController->listNameOnly(true);

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
                                $result = \Controllers\Common::deleteRecursive(REPOS_DIR . '/' . $snapDateFormatted . '_' . $repoName);
                            }
                        }
                        if ($packageType == 'deb') {
                            if (is_dir(REPOS_DIR . '/' . $repoName . '/' . $repoDist . '/' . $snapDateFormatted . '_' . $repoSection)) {
                                $result = \Controllers\Common::deleteRecursive(REPOS_DIR . '/' . $repoName . '/' . $repoDist . '/' . $snapDateFormatted . '_' . $repoSection);
                            }
                        }

                        /**
                         *  Cas où le snapshot a été supprimé avec succès
                         */
                        if ($result === true) {
                            if ($packageType == 'rpm') {
                                $removedSnaps[] = '<span class="label-white">' . $repoName . '</span>⟶<span class="label-black">' . $snapDateFormatted . '</span> snapshot has been deleted';
                            }
                            if ($packageType == 'deb') {
                                $removedSnaps[] = '<span class="label-white">' . $repoName . ' ❯ ' . $repoDist . ' ❯ ' . $repoSection . '</span>⟶<span class="label-black">' . $snapDateFormatted . '</span> snapshot has been deleted';
                            }

                            /**
                             *  Changement du status en base de données
                             */
                            $this->snapSetStatus($snapId, 'deleted');

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

    /**
     *  Set snapshot date
     */
    public function snapSetDate(string $snapId, string $date)
    {
        $this->model->snapSetDate($snapId, $date);
    }

    /**
     *  Set snapshot time
     */
    public function snapSetTime(string $snapId, string $time)
    {
        $this->model->snapSetTime($snapId, $time);
    }

    /**
     *  Set snapshot status
     */
    public function snapSetStatus(string $snapId, string $status)
    {
        $this->model->snapSetStatus($snapId, $status);
    }

    /**
     *  Set snapshot signature status
     */
    public function snapSetSigned(string $snapId, string $signed)
    {
        $this->model->snapSetSigned($snapId, $signed);
    }

    /**
     *  Set snapshot architectures
     */
    public function snapSetArch(string $snapId, array $arch)
    {
        $this->model->snapSetArch($snapId, implode(',', $arch));
    }

    /**
     *  Add a repo in database
     */
    public function add(string $source, string $packageType, string $name)
    {
        $this->model->add($source, $packageType, $name);
    }

    /**
     *  Add a repo snapshot in database
     */
    public function addSnap(string $date, string $time, string $gpgSignature, array $arch, array $includeTranslation, string $type, string $status, string $repoId)
    {
        $this->model->addSnap($date, $time, $gpgSignature, $arch, $includeTranslation, $type, $status, $repoId);
    }

    /**
     *  Associate a new env to a snapshot
     */
    public function addEnv(string $env, string $description = null, string $snapId)
    {
        $this->model->addEnv($env, $description, $snapId);
    }

    /**
     *  Remove an env in database
     */
    public function removeEnv(string $envId)
    {
        $this->model->removeEnv($envId);
    }

    /**
     *  Return snapshot date from database, from its Id
     */
    public function getSnapDateById(string $snapId)
    {
        return $this->model->getSnapDateById($snapId);
    }

    /**
     *  Return true if a snapshot exists at a specific date in database, from the repo name and the date
     */
    public function existsRepoSnapDate(string $date, string $name, string $dist = null, string $section = null)
    {
        return $this->model->existsRepoSnapDate($date, $name, $dist, $section);
    }

    public function getLastInsertRowID()
    {
        return $this->model->getLastInsertRowID();
    }

    /**
     *  Update release version in database
     */
    public function updateReleasever(int $repoId, string $releasever)
    {
        $this->model->updateReleasever($repoId, $releasever);
    }

    /**
     *  Update dist in database
     */
    public function updateDist(int $repoId, string $dist)
    {
        $this->model->updateDist($repoId, $dist);
    }

    /**
     *  Update section in database
     */
    public function updateSection(int $repoId, string $section)
    {
        $this->model->updateSection($repoId, $section);
    }
}
