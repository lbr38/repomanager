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
    private $env = [];
    private $description;
    private $tags = [];
    private $retention;
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

    public function setTags(array $tags = []): void
    {
        $this->tags = $tags;
    }

    public function setRetention(int|null $retention): void
    {
        $this->retention = $retention;
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
        // return $this->env ?? [];
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

    /**
     *  Return the repository label: name ❯ release version (rpm) or name ❯ distribution ❯ section (deb)
     */
    public function getLabel(): string
    {
        if ($this->getPackageType() == 'deb') {
            return $this->getName() . ' ❯ ' . $this->getDist() . ' ❯ ' . $this->getSection();
        }

        return $this->getName() . ' ❯ ' . $this->getReleasever();
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

    public function getTags(): array
    {
        return $this->tags;
    }

    public function getRetention(): int|null
    {
        return $this->retention;
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

        try {
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
            if (!empty($data['Arch'])) {
                $this->setArch(explode(',', $data['Arch']));
            }
            if (!empty($data['Description'])) {
                $this->setDescription($data['Description']);
            }
            if (!empty($data['Tags'])) {
                $tags = explode(',', $data['Tags']);
                $this->setTags($tags);
            }
            if (isset($data['Retention'])) {
                $this->setRetention($data['Retention']);
            }

            /**
             *  Advanced_params includes package include/exclude, metadata custom fields and potentially other parameters in the future
             *  It is optional and can be empty
             */
            if (!empty($data['Advanced_params'])) {
                $advancedParams = json_decode($data['Advanced_params'], true, 512, JSON_THROW_ON_ERROR);
                $this->setAdvancedParams($advancedParams);
            }
        } catch (JsonException $e) {
            throw new Exception('Failed to decode JSON data from database: ' . $e->getMessage());
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
     *  Delete a repository from the database when it has no snapshot anymore, with its groups and profiles membership
     */
    public function deleteIfEmpty(int $repoId): void
    {
        if (!empty($this->getSnapshots($repoId))) {
            return;
        }

        $this->model->removeFromGroup($repoId);
        (new \Controllers\Profile())->removeRepoMemberId($repoId);
        $this->model->delete($repoId);
    }

    /**
     *  Return the repository details to store in a task parameters, so that the task can still be described once the repository has been deleted
     */
    public function getTaskInfo(): array
    {
        return array_filter([
            'name'         => $this->name,
            'package-type' => $this->packageType,
            'releasever'   => $this->releasever,
            'dist'         => $this->dist,
            'section'      => $this->section,
            'source'       => $this->source,
            'arch'         => $this->arch,
            'date'         => $this->date
        ]);
    }

    /**
     *  Set the repository details from the info stored in a task parameters (see getTaskInfo)
     */
    public function setFromTaskInfo(array $info): void
    {
        if (!empty($info['name'])) {
            $this->setName($info['name']);
        }
        if (!empty($info['package-type'])) {
            $this->setPackageType($info['package-type']);
        }
        if (!empty($info['releasever'])) {
            $this->setReleasever($info['releasever']);
        }
        if (!empty($info['dist'])) {
            $this->setDist($info['dist']);
        }
        if (!empty($info['section'])) {
            $this->setSection($info['section']);
        }
        if (!empty($info['source'])) {
            $this->setSource($info['source']);
        }
        if (!empty($info['arch'])) {
            $this->setArch($info['arch']);
        }
        if (!empty($info['date'])) {
            $this->setDate($info['date']);
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

    /**
     *  Update description
     */
    public function updateDescription(int $id, string $description): void
    {
        self::checkDescription($description);

        $this->model->updateDescription($id, Validate::string($description));
    }

    /**
     *  Check that a repository or mirror description is valid
     *  Description should not contain single quotes, backslashes or PHP tags
     */
    public static function checkDescription(string $description): void
    {
        if (str_contains($description, "'") || str_contains($description, "\\") || str_contains($description, '<?') || str_contains($description, '?>')) {
            throw new Exception('Description contains invalid characters');
        }
    }

    /**
     *  Update snapshots retention (null: global setting)
     */
    public function updateRetention(int $repoId, int|null $retention): void
    {
        $this->model->updateRetention($repoId, $retention);
    }

    /**
     *  Update tags
     */
    public function updateTags(int $id, array $tags): void
    {
        $this->model->updateTags($id, implode(',', self::cleanTags($tags)));
    }

    /**
     *  Check and return the tags of a repository or mirror: trimmed, without empty values and duplicates
     *  Tags must be alphanumeric (hyphen and underscore allowed)
     */
    public static function cleanTags(array $tags): array
    {
        $validTags = [];

        foreach ($tags as $tag) {
            if (!is_string($tag)) {
                throw new Exception('Tags must be strings');
            }

            $tag = trim($tag);

            // Ignore empty tags
            if ($tag === '') {
                continue;
            }

            if (!Validate::alphaNumericHyphen($tag)) {
                throw new Exception('Tag ' . htmlspecialchars($tag) . ' contains invalid characters');
            }

            // Avoid duplicates
            if (!in_array($tag, $validTags)) {
                $validTags[] = $tag;
            }
        }

        return $validTags;
    }
}
