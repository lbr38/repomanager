<?php

namespace Controllers\Repo;

use Exception;
use Datetime;
use \Controllers\Utils\Validate;

class Repo
{
    protected $model;
    private $taskId;
    private $repoId;
    private $snapId;
    private $envId;
    private $name;
    private $source;
    private $packageType;
    private $type;
    private $arch;
    private $dist;
    private $section;
    private $date;
    private $dateFormatted;
    private $time;
    private $env;
    // private $envs;
    private $description;
    private $group;
    private $packagesToInclude = [];
    private $packagesToExclude = [];
    private $signed;
    private $status;
    private $rebuild;
    private $gpgCheck;
    private $gpgSign;
    private $releasever;
    // private $targetArch;

    public function __construct()
    {
        $this->model = new \Models\Repo\Repo();
    }

    public function setRepoId(int $id): void
    {
        $this->repoId = $id;
    }

    public function setSnapId(int $id): void
    {
        $this->snapId = $id;
    }

    public function setEnvId(int $id): void
    {
        $this->envId = $id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setDist(string $dist): void
    {
        $this->dist = $dist;
    }

    public function setSection(string $section): void
    {
        $this->section = $section;
    }

    public function setEnv(string|array $env): void
    {
        $this->env = $env;
    }

    public function setDate(string $date): void
    {
        $this->date = $date;
        $this->dateFormatted = DateTime::createFromFormat('Y-m-d', $date)->format('d-m-Y');
    }

    public function setTime(string $time): void
    {
        $this->time = $time;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function setSigned(string $signed): void
    {
        $this->signed = $signed;
    }

    public function setRebuild(string $rebuild): void
    {
        $this->rebuild = $rebuild;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function setDescription($description = ''): void
    {
        if ($description == 'nodescription') {
            $description = '';
        }

        $this->description = Validate::string($description);
    }

    public function setSource(string $source): void
    {
        $this->source = $source;
    }

    public function setPackageType(string $type): void
    {
        $this->packageType = $type;
    }

    public function setGroup(string $group): void
    {
        if ($group == 'nogroup') {
            $this->group = '';
        } else {
            $this->group = $group;
        }
    }

    public function setGpgCheck(string $gpgCheck): void
    {
        $this->gpgCheck = $gpgCheck;
    }

    public function setGpgSign(string $gpgSign): void
    {
        $this->gpgSign = $gpgSign;
    }

    public function setArch(array $arch): void
    {
        $this->arch = $arch;
    }

    public function setPackagesToInclude(array $packages): void
    {
        $this->packagesToInclude = $packages;
    }

    public function setPackagesToExclude(array $packages): void
    {
        $this->packagesToExclude = $packages;
    }

    public function setReleasever(string $releasever): void
    {
        $this->releasever = $releasever;
    }

    public function setTaskId(string $taskId): void
    {
        $this->taskId = $taskId;
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

    public function getPackageType() : string
    {
        return $this->packageType;
    }

    public function getEnv()
    {
        return $this->env;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function getDateFormatted()
    {
        return DateTime::createFromFormat('Y-m-d', $this->date)->format('d-m-Y');
    }

    public function getTime()
    {
        return $this->time;
    }

    public function getRebuild()
    {
        return $this->rebuild;
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

    public function getPackagesToInclude()
    {
        return $this->packagesToInclude;
    }

    public function getPackagesToExclude()
    {
        return $this->packagesToExclude;
    }

    public function getReleasever()
    {
        return $this->releasever;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getGroup()
    {
        return $this->group;
    }

    public function getGpgCheck()
    {
        return $this->gpgCheck;
    }

    public function getGpgSign()
    {
        return $this->gpgSign;
    }

    public function getTaskId()
    {
        return $this->taskId;
    }

    /**
     *  Return all snapshots of a repository
     */
    public function getSnapshots(int $repoId, string $status = 'active'): array
    {
        return $this->model->getSnapshots($repoId, $status);
    }

    /**
     *  Retrieve all informations from a repo, snapshot and env in database
     */
    public function getAllById(string|int|null $repoId = null, string|int|null $snapId = null, string|int|null $envId = null) : void
    {
        $data = $this->model->getAllById($repoId, $snapId, $envId);

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
        } /*else {
            $this->setDist('');
        }*/
        if (!empty($data['Section'])) {
            $this->setSection($data['Section']);
        } /*else {
            $this->setSection('');
        }*/
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
            $this->setRebuild($data['Reconstruct']);
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
        if (!empty($data['Pkg_included'])) {
            $this->setPackagesToInclude(explode(',', $data['Pkg_included']));
        }
        if (!empty($data['Pkg_excluded'])) {
            $this->setPackagesToExclude(explode(',', $data['Pkg_excluded']));
        }
    }

    /**
     *  Get unused repos Id (repos that have no active snapshot and so are not visible from web UI)
     */
    public function getUnused() : array
    {
        return $this->model->getUnused();
    }

    /**
     *  Return true if a repo Id exists in database
     */
    public function existsId(string $repoId) : bool
    {
        return $this->model->existsId($repoId);
    }

    /**
     *  Return true if a snapshot Id exists in database
     */
    public function existsSnapId(string $snapId) : bool
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
     *  Return the total number of repositories
     */
    public function count()
    {
        return $this->model->count();
    }

    /**
     *  Add / remove repositories to/from a group
     */
    public function addReposIdToGroup(array $reposId = [], int $groupId)
    {
        $mygroup = new \Controllers\Group\Repo();

        if (!empty($reposId)) {
            foreach ($reposId as $repoId) {
                // Check that the specified repository Id exists in database
                if ($this->model->existsId($repoId) === false) {
                    throw new Exception("Specified repo Id $repoId does not exist");
                }

                // Add repository to group
                $this->model->addToGroup($repoId, $groupId);
            }
        }

        // Retrieve the list of repositories currently in the group in order to remove those that were not specified by the user
        $actualReposMembers = $mygroup->getReposMembers($groupId);

        // Among this list, only retrieve the Ids of the currently member repositories
        $actualReposId = [];

        foreach ($actualReposMembers as $actualRepoMember) {
            $actualReposId[] = $actualRepoMember['repoId'];
        }

        // Finally, remove all currently member repository Ids that were not specified by the user
        foreach ($actualReposId as $actualRepoId) {
            if (!in_array($actualRepoId, $reposId)) {
                $this->model->removeFromGroup($actualRepoId, $groupId);
            }
        }
    }

    /**
     *  Add a repository to a group
     */
    public function addRepoIdToGroup(string $repoId, string $groupName)
    {
        $mygroup = new \Controllers\Group\Repo();
        $groupId = $mygroup->getIdByName($groupName);

        $this->model->addToGroup($repoId, $groupId);
    }

    /**
     *  Return latest snapshot Id from repo Id
     */
    public function getLatestSnapId(int $repoId) : int|null
    {
        return $this->model->getLatestSnapId($repoId);
    }

    /**
     *  Get env Id(s) by snapshot Id
     */
    public function getEnvIdBySnapId(string $snapId)
    {
        return $this->model->getEnvIdBySnapId($snapId);
    }

    /**
     *  Clean repositories from groups when they have no active snapshot anymore
     */
    public function cleanGroups(): void
    {
        $repoIds = $this->model->getAllRepoId();

        foreach ($repoIds as $repoId) {
            // Check if the repo has at least one active snapshot
            $activeSnapshots = $this->getSnapshots($repoId['Id']);

            // If the repo has no active snapshot, remove it from groups
            if (empty($activeSnapshots)) {
                $this->model->removeFromGroup($repoId['Id']);
            }
        }
    }

    /**
     *  Set snapshot rebuild status in database, to know if the snapshot has to be rebuilt or not
     */
    public function snapSetRebuild(string $snapId, string $status = '') : void
    {
        $this->model->snapSetRebuild($snapId, $status);
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
     *  Set packages included
     */
    public function snapSetPackagesIncluded(int $snapId, array $packages)
    {
        $this->model->snapSetPackagesIncluded($snapId, implode(',', $packages));
    }

    /**
     *  Set packages excluded
     */
    public function snapSetPackagesExcluded(int $snapId, array $packages)
    {
        $this->model->snapSetPackagesExcluded($snapId, implode(',', $packages));
    }

    /**
     *  Return snapshot date from database, from its Id
     */
    public function getSnapDateById(string $snapId)
    {
        return $this->model->getSnapDateById($snapId);
    }

    public function getLastInsertRowID()
    {
        return $this->model->getLastInsertRowID();
    }

    /**
     *  Update name in database
     */
    public function updateName(int $repoId, string $name): void
    {
        $this->model->updateName($repoId, $name);
    }

    /**
     *  Update dist in database
     */
    public function updateDist(int $repoId, string $dist): void
    {
        $this->model->updateDist($repoId, $dist);
    }

    /**
     *  Update section in database
     */
    public function updateSection(int $repoId, string $section): void
    {
        $this->model->updateSection($repoId, $section);
    }

    /**
     *  Update release version in database
     */
    public function updateReleasever(int $repoId, string $releasever): void
    {
        $this->model->updateReleasever($repoId, $releasever);
    }

    /**
     *  Update source repository in database
     */
    public function updateSource(int $repoId, string $source): void
    {
        $this->model->updateSource($repoId, $source);
    }
}
