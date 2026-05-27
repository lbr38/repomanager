<?php

namespace Controllers\Repo;

use Exception;
use DateTime;
use JsonException;
use Controllers\Utils\Validate;
use Controllers\Group\Repo as RepoGroup;

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
    private $description;
    private $group;
    private $advancedParams = [];
    private $signed;
    private $status;
    private $rebuild;
    private $gpgCheck;
    private $gpgSign;
    private $releasever;

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

    public function setReleasever(string $releasever): void
    {
        $this->releasever = $releasever;
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

    public function setGroup(string $group = ''): void
    {
        $this->group = Validate::string($group);
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

    public function setAdvancedParams(array $advancedParams): void
    {
        $this->advancedParams = $advancedParams;
    }

    public function setTaskId(int $taskId): void
    {
        $this->taskId = $taskId;
    }

    public function getRepoId(): int
    {
        return $this->repoId;
    }

    public function getSnapId(): int
    {
        return $this->snapId;
    }

    public function getEnvId(): int
    {
        return $this->envId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDist(): string
    {
        return $this->dist;
    }

    public function getSection(): string
    {
        return $this->section;
    }

    public function getReleasever()
    {
        return $this->releasever;
    }

    public function getPackageType(): string
    {
        return $this->packageType;
    }

    public function getEnv(): string|array
    {
        return $this->env;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function getDateFormatted(): string
    {
        return DateTime::createFromFormat('Y-m-d', $this->date)->format('d-m-Y');
    }

    public function getTime(): string
    {
        return $this->time;
    }

    public function getRebuild(): string|null
    {
        return $this->rebuild;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getSigned(): string
    {
        return $this->signed;
    }

    public function getArch(): array
    {
        return $this->arch;
    }

    public function getAdvancedParams(): array
    {
        return $this->advancedParams;
    }

    public function getDescription(): string|null
    {
        return $this->description;
    }

    public function getGroup(): string|null
    {
        return $this->group;
    }

    public function getGpgCheck(): string
    {
        return $this->gpgCheck;
    }

    public function getGpgSign(): string
    {
        return $this->gpgSign;
    }

    public function getTaskId(): int
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

        if (!empty($data['repoId'])) {
            $this->setRepoId($data['repoId']);
        }
        if (!empty($data['snapId'])) {
            $this->setSnapId($data['snapId']);
        }
        if (!empty($data['envId'])) {
            $this->setEnvId($data['envId']);
        }
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
            $this->setRebuild($data['Reconstruct']);
        }
        if (!empty($data['Description'])) {
            $this->setDescription($data['Description']);
        }
        if (!empty($data['Arch'])) {
            $this->setArch(explode(',', $data['Arch']));
        }

        /**
         *  Extract Advanced_params JSON
         *  This includes package include/exclude, metadata custom fields and potentially other parameters in the future
         *  It is optional and can be empty
         */
        if (!empty($data['Advanced_params'])) {
            try {
                $advancedParams = json_decode($data['Advanced_params'], true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new Exception('Failed to decode advanced parameters JSON: ' . $e->getMessage());
            }

            $this->setAdvancedParams($advancedParams);
        }
    }

    /**
     *  Get unused repos Id (repos that have no active snapshot and so are not visible from web UI)
     */
    public function getUnused(): array
    {
        return $this->model->getUnused();
    }

    /**
     *  Return true if a repo Id exists in database
     */
    public function existsId(string $repoId): bool
    {
        return $this->model->existsId($repoId);
    }

    /**
     *  Return true if a snapshot Id exists in database
     */
    public function existsSnapId(string $snapId): bool
    {
        return $this->model->existsSnapId($snapId);
    }

    /**
     *  Return true if env exists, based on its name and the snapshot Id it points to
     */
    public function existsSnapIdEnv(string $snapId, string $env): bool
    {
        return $this->model->existsSnapIdEnv($snapId, $env);
    }

    /**
     *  Return the total number of repositories
     */
    public function count(): int
    {
        return $this->model->count();
    }

    /**
     *  Add / remove repositories to/from a group
     */
    public function addReposIdToGroup(int $groupId, array $reposId = []): void
    {
        $groupController = new RepoGroup();

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
        $actualReposMembers = $groupController->getReposMembers($groupId);

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

        unset($groupController);
    }

    /**
     *  Add a repository to a group
     */
    public function addRepoIdToGroup(int $repoId, string $groupName): void
    {
        $groupController = new RepoGroup();
        $groupId = $groupController->getIdByName($groupName);

        $this->model->addToGroup($repoId, $groupId);

        unset($groupController);
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

    public function getLastInsertRowID(): int
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
