<?php

namespace Controllers\Task;

use Controllers\Repo\Lock as RepoLock;
use Controllers\Utils\Convert;
use Controllers\Utils\Generate\Html\Label;
use Exception;
use JsonException;

class Execution
{
    protected object $repoController;
    protected object $sourceRepoController;
    protected object $repoSnapshotController;
    protected object $repoEnvController;
    protected object $rpmRepoController;
    protected object $debRepoController;
    protected object $taskController;
    protected object $taskLogStepController;
    protected object $taskLogSubStepController;
    protected object $profileController;
    protected object $layoutContainerReloadController;
    protected int $taskId;
    protected string $action;
    // Default task execution status is 'done', it will be changed to 'error' if an error occurs
    protected string $status = 'done';
    protected string $error = '';
    protected string $date;
    protected string $time;
    protected string $timeStart;
    protected array $task;
    protected array $params;
    // Snapshot selected by the user, before it is replaced by the latest snapshot of the repository (see 'use-latest-snapshot')
    // Not set for recurring tasks, which always update the latest snapshot whatever the snapshot selected when they were created
    protected int|null $requestedSnapId = null;
    // Locks of the repositories modified by the task (see lockRepositories())
    private array $repositoryLocks = [];

    public function __construct(int $taskId, string $action)
    {
        $this->repoController = new \Controllers\Repo\Repo();
        $this->sourceRepoController = new \Controllers\Repo\Repo();
        $this->repoSnapshotController = new \Controllers\Repo\Snapshot\Snapshot();
        $this->repoEnvController = new \Controllers\Repo\Environment();
        $this->rpmRepoController = new \Controllers\Repo\Rpm();
        $this->debRepoController = new \Controllers\Repo\Deb();
        $this->taskController = new \Controllers\Task\Task();
        $this->taskLogStepController = new \Controllers\Task\Log\Step($taskId);
        $this->taskLogSubStepController = new \Controllers\Task\Log\SubStep($taskId);
        $this->profileController = new \Controllers\Profile();
        $this->layoutContainerReloadController = new \Controllers\Layout\ContainerReload();

        try {
            $requiredParams = [];
            $optionalParams = [];
            $conditionalRequiredParams = [];
            $conditionalOptionalParams = [];

            // Prepare task and task log
            $date = date('Y-m-d');
            $time = date('H:i:s');

            // Set task properties
            $this->taskId = $taskId;
            $this->action = $action;
            $this->date   = $date;
            $this->time   = $time;

            // Retrieve tasks parameters definition
            include_once ROOT . '/config/tasks/' . $action . '.php';

            // Set task Id for taskController
            $this->taskController->setId($taskId);

            // Retrieve task params
            $this->task = $this->taskController->getById($taskId);

            try {
                $this->params = json_decode($this->task['Raw_params'], true);
            } catch (JsonException $e) {
                throw new Exception('could not decode task #' . $taskId . ' parameters JSON: ' . $e->getMessage());
            }

            // The task duration includes the time spent waiting for another task to release a repository
            $this->timeStart = microtime(true);

            /**
             *  Lock the repositories modified by the task, waiting for the task currently modifying one of them to end
             *  Done before anything is read from the repositories (e.g. the latest snapshot), as the other task may change them
             */
            $this->lockRepositories();

            // Retrieve the latest snapshot Id for the repository, if needed
            // This requires 'repo-id' to be defined in params and to be numeric
            if (isset($taskConfig['use-latest-snapshot']) and $taskConfig['use-latest-snapshot']) {
                if (!isset($this->params['repo-id'])) {
                    throw new Exception('parameter repo-id is undefined, cannot retrieve latest snapshot Id for the repository');
                }

                if (!is_numeric($this->params['repo-id'])) {
                    throw new Exception('parameter repo-id is not numeric, cannot retrieve latest snapshot Id for the repository');
                }

                // Get the latest snapshot Id for the repository, this can return null if no snapshot was found
                $latestSnapId = $this->repoController->getLatestSnapId($this->params['repo-id']);

                // If latest snapshot Id was not found, throw an exception
                if (empty($latestSnapId)) {
                    throw new Exception('could not find the latest snapshot Id repository #' . $this->params['repo-id']);
                }

                // Keep the snapshot selected by the user, the task checks that updating the latest snapshot instead does not replace another snapshot
                if (!empty($this->params['snap-id']) and is_numeric($this->params['snap-id']) and !$this->isRecurring()) {
                    $this->requestedSnapId = (int) $this->params['snap-id'];
                }

                // Update snap-id param to latest snapshot Id
                $this->params['snap-id'] = strval($latestSnapId);

                // Update Raw_params in the database
                try {
                    $this->taskController->updateRawParams($taskId, json_encode($this->params));
                } catch (JsonException $e) {
                    throw new Exception('could not update task #' . $taskId . ' parameters in database (JSON encode failed): ' . $e->getMessage());
                }
            }

            // Retrieve repository details from repo id, snap id and env id, if needed
            if (isset($taskConfig['retrieve-repo-from-all-id']) and $taskConfig['retrieve-repo-from-all-id']) {
                $this->repoController->getAllById($this->params['repo-id'], $this->params['snap-id'], $this->params['env-id']);
            }

            // Retrieve repository details from repo Id, if needed
            if (isset($taskConfig['retrieve-repo-from-repo-id']) and $taskConfig['retrieve-repo-from-repo-id']) {
                $this->repoController->getAllById($this->params['repo-id']);
            }

            // Retrieve repository details from snap Id, if needed
            if (isset($taskConfig['retrieve-repo-from-snap-id']) and $taskConfig['retrieve-repo-from-snap-id']) {
                $this->repoController->getAllById(null, $this->params['snap-id'], null);
            }

            // Retrieve required params for the specified action, if any
            if (isset($taskConfig['required-params'])) {
                array_push($requiredParams, ...$taskConfig['required-params']);
            }

            // Retrieve optional params for the specified action, if any
            if (isset($taskConfig['optional-params'])) {
                array_push($optionalParams, ...$taskConfig['optional-params']);
            }

            // If there are conditional required parameters, retrieve them too
            if (isset($taskConfig['conditional-required-params'])) {
                // $basedOnParam must be set in params, for example 'package-type' must exists in params if there are conditional required params based on 'package-type'
                foreach ($taskConfig['conditional-required-params'] as $basedOnParam => $conditions) {
                    // If based on user form input values
                    if ($taskConfig['conditional-compare-with'] == 'form') {
                        if (isset($this->params[$basedOnParam]) and isset($conditions[$this->params[$basedOnParam]])) {
                            array_push($requiredParams, ...$conditions[$this->params[$basedOnParam]]);
                        }
                    }

                    // If based on current repository values
                    if ($taskConfig['conditional-compare-with'] == 'current-repo') {
                        // Retrieve current repository details
                        if ($basedOnParam == 'repo-type') {
                            $value = $this->repoController->getType();
                        }
                        if ($basedOnParam == 'package-type') {
                            $value = $this->repoController->getPackageType();
                        }

                        if (isset($conditions[$value])) {
                            array_push($requiredParams, ...$conditions[$value]);
                        }
                    }
                }
            }

            // If there are conditional optional parameters, retrieve them too
            if (isset($taskConfig['conditional-optional-params'])) {
                // $basedOnParam must be set in params, for example 'package-type' must exists in params if there are conditional optional params based on 'package-type'
                foreach ($taskConfig['conditional-optional-params'] as $basedOnParam => $conditions) {
                    // If based on user form input values
                    if ($taskConfig['conditional-compare-with'] == 'form') {
                        if (isset($this->params[$basedOnParam]) and isset($conditions[$this->params[$basedOnParam]])) {
                            array_push($optionalParams, ...$conditions[$this->params[$basedOnParam]]);
                        }
                    }

                    // If based on current repository values
                    if ($taskConfig['conditional-compare-with'] == 'current-repo') {
                        // Retrieve current repository details
                        if ($basedOnParam == 'repo-type') {
                            $value = $this->repoController->getType();
                        }
                        if ($basedOnParam == 'package-type') {
                            $value = $this->repoController->getPackageType();
                        }

                        if (isset($conditions[$value])) {
                            array_push($optionalParams, ...$conditions[$value]);
                        }
                    }
                }
            }

            // Check required parameters
            try {
                $this->paramsCheck($requiredParams);
            } catch (Exception $e) {
                throw new Exception('parameters check error: ' . $e->getMessage());
            }

            // Set required and optional params
            try {
                $this->paramsSet($requiredParams, $optionalParams);
            } catch (Exception $e) {
                throw new Exception('parameters set error: ' . $e->getMessage());
            }

            // Update date and time in database
            $this->taskController->updateDate($taskId, $date);
            $this->taskController->updateTime($taskId, $time);

            // Start task
            $this->start();
        } catch (Exception $e) {
            throw new Exception('Task initialization error: ' . $e->getMessage());
        }
    }

    /**
     *  Return true if the task is a recurring task, or a sub-task of a recurring task
     */
    private function isRecurring(): bool
    {
        $params = $this->params;

        // Sub-tasks do not carry the schedule, their parent task does
        if (!empty($this->task['Parent_task_id'])) {
            $parentTask = $this->taskController->getById((int) $this->task['Parent_task_id']);
            $params = json_decode($parentTask['Raw_params'] ?? '', true) ?? [];
        }

        return ($params['schedule']['schedule-type'] ?? '') == 'recurring';
    }

    /**
     *  Lock the repositories modified by the task, so that only one task at a time can modify a repository
     *  If another task is modifying one of them, wait for it to end
     *  Locks are always acquired in the same order, so that two tasks modifying the same repositories never wait for each other
     */
    private function lockRepositories(): void
    {
        $locks = [];
        $waiting = false;

        foreach ($this->getModifiedRepositories() as $repo) {
            $lock = new RepoLock($repo);
            $locks[$lock->getKey()] = $lock;
        }

        ksort($locks, SORT_STRING);

        foreach ($locks as $lock) {
            if (!$lock->tryAcquire($this->taskId)) {
                if (!$waiting) {
                    $this->taskLogStepController->new('waiting', 'WAITING FOR ANOTHER TASK');
                    $waiting = true;
                }

                $holder = $lock->getHolder();
                $label = Label::white(htmlspecialchars($lock->getLabel()));

                if (!empty($holder)) {
                    $this->taskLogSubStepController->new('waiting-' . count($this->repositoryLocks), 'WAITING FOR TASK #' . $holder . ' TO END', 'Repository ' . $label . ' is being modified by task #' . $holder . ', only one task at a time can modify a repository');
                } else {
                    $this->taskLogSubStepController->new('waiting-' . count($this->repositoryLocks), 'WAITING FOR ANOTHER TASK TO END', 'Repository ' . $label . ' is being modified by another task, only one task at a time can modify a repository');
                }

                $lock->acquire($this->taskId);

                $this->taskLogSubStepController->completed();
            }

            $this->repositoryLocks[] = $lock;
        }

        if ($waiting) {
            $this->taskLogStepController->completed();
        }
    }

    /**
     *  Return the repositories modified by the task, with their details as returned by Repo::getTaskInfo()
     *  A repository that cannot be found is ignored: the task fails by itself when it does not find it
     */
    private function getModifiedRepositories(): array
    {
        // The repository to create is described by the task parameters, its name is the alias if set, otherwise the source repository name (see Create task)
        if ($this->action == 'create') {
            $repo = [
                'package-type' => $this->params['package-type'] ?? '',
                'name'         => !empty($this->params['alias']) ? $this->params['alias'] : ($this->params['source'] ?? ''),
                'releasever'   => $this->params['releasever'] ?? '',
                'dist'         => $this->params['dist'] ?? '',
                'section'      => $this->params['section'] ?? ''
            ];

            // Parameters of a task to create several repositories are split into one task per repository (see Task::execute())
            foreach ($repo as $key => $value) {
                if (!is_scalar($value)) {
                    return [];
                }

                $repo[$key] = (string) $value;
            }

            // Invalid parameters, the task fails by itself when checking them
            if (empty($repo['package-type']) or empty($repo['name'])) {
                return [];
            }

            return [$repo];
        }

        /**
         *  The repository is retrieved the same way the task retrieves it: from its Id for the tasks working on the repository
         *  (an update applies to its latest snapshot), from the snapshot Id otherwise
         */
        $repoController = new \Controllers\Repo\Repo();
        $byRepoId = in_array($this->action, ['update', 'rename']) || empty($this->params['snap-id']);

        try {
            if ($byRepoId and !empty($this->params['repo-id']) and is_numeric($this->params['repo-id'])) {
                $repoController->getAllById($this->params['repo-id']);
            } elseif (!empty($this->params['snap-id']) and is_numeric($this->params['snap-id'])) {
                $repoController->getAllById(null, $this->params['snap-id']);
            }
        } catch (Exception $e) {
            return [];
        }

        $repo = $repoController->getTaskInfo();

        if (empty($repo['name']) or empty($repo['package-type'])) {
            return [];
        }

        $repos = [$repo];

        // A duplicated repository is created under its new name, and its new release version (rpm) or distribution (deb) if specified (see Duplicate task)
        if ($this->action == 'duplicate' and !empty($this->params['name']) and is_scalar($this->params['name'])) {
            $repos[] = array_merge($repo, array_filter([
                'name'       => (string) $this->params['name'],
                'releasever' => is_scalar($this->params['releasever'] ?? null) ? (string) $this->params['releasever'] : '',
                'dist'       => is_scalar($this->params['dist'] ?? null) ? (string) $this->params['dist'] : ''
            ]));
        }

        // A renamed repository is moved under its new name (see Rename task)
        if ($this->action == 'rename' and !empty($this->params['name']) and is_scalar($this->params['name'])) {
            $repos[] = array_merge($repo, ['name' => (string) $this->params['name']]);
        }

        return $repos;
    }

    /**
     *  Release the locks of the repositories modified by the task
     */
    private function releaseRepositories(): void
    {
        foreach ($this->repositoryLocks as $lock) {
            $lock->release();
        }

        $this->repositoryLocks = [];
    }

    /**
     *  Check that required parameters are defined and not empty
     */
    public function paramsCheck($requiredParams)
    {
        foreach ($requiredParams as $param) {
            if (!isset($this->params[$param])) {
                throw new Exception('parameter ' . $param . ' is undefined');
            }

            if (empty($this->params[$param])) {
                throw new Exception('parameter ' . $param . ' is empty');
            }
        }
    }

    /**
     *  Set repository parameters for a task
     */
    public function paramsSet($requiredParams = [], $optionalParams = [])
    {
        // Repo controller setter functions depending on parameters
        $setters = [
            'repo-id' => 'setRepoId',
            'snap-id' => 'setSnapId',
            'env-id' => 'setEnvId',
            'package-type' => 'setPackageType',
            'repo-type' => 'setType',
            'name' => 'setName',
            'dist' => 'setDist',
            'section' => 'setSection',
            'source' => 'setSource',
            'arch' => 'setArch',
            'date' => 'setDate',
            'releasever' => 'setReleasever',
            'gpg-check' => 'setGpgCheck',
            'gpg-sign' => 'setGpgSign',
            'env' => 'setEnv',
            'description' => 'setDescription',
            'tags' => 'setTags',
            'group' => 'setGroup',
            'advanced-params' => 'setAdvancedParams'
        ];

        // Set required parameters, using the appropriate setter function
        if (!empty($requiredParams)) {
            foreach ($requiredParams as $param) {
                $setterFunction = $setters[$param];
                $this->repoController->$setterFunction($this->params[$param]);
            }
        }

        // Set optional parameters if defined, using the appropriate setter function
        if (!empty($optionalParams)) {
            foreach ($optionalParams as $param) {
                if (isset($this->params[$param])) {
                    $setterFunction = $setters[$param];
                    $this->repoController->$setterFunction($this->params[$param]);
                }
            }
        }
    }

    /**
     *  Start task execution
     */
    public function start() : void
    {
        // Generate time start, if not already set before waiting for a repository (see __construct())
        if (!isset($this->timeStart)) {
            $this->timeStart = microtime(true);
        }

        // Set status as 'running' in database
        $this->taskController->updateStatus($this->taskId, 'running');

        // Update layout containers states
        $this->layoutContainerReloadController->reload('header/menu');
        $this->layoutContainerReloadController->reload('repos/kpi');
        $this->layoutContainerReloadController->reload('repos/list');
        $this->layoutContainerReloadController->reload('tasks/tasks');
        $this->layoutContainerReloadController->reload('snapshot/kpi');
        $this->layoutContainerReloadController->reload('snapshot/list');
    }

    /**
     *  End task execution
     */
    public function end() : void
    {
        // The repositories are no longer modified, the tasks waiting for them can start
        $this->releaseRepositories();

        /**
         *  Set task status
         */
        $this->taskController->setStatus($this->status);
        $this->taskController->updateStatus($this->taskId, $this->status);

        if ($this->status == 'error') {
            $this->taskController->setError('Failed');

            // Set latest sub step error message and mark step as error
            $this->taskLogSubStepController->error($this->error);
            $this->taskLogStepController->error();
        }

        try {
            // Calculate total duration
            $duration = Convert::microtimeToHuman(microtime(true) - $this->timeStart);

            // Update duration
            $this->taskController->updateDuration($this->taskId, $duration);

            /**
             *  A task generated by a scheduled task is one of its sub-tasks: the parent task is
             *  closed and notified once all of its sub-tasks have ended
             */
            if (!empty($this->task['Parent_task_id'])) {
                $this->taskController->closeParent((int) $this->task['Parent_task_id']);

            // A scheduled task that has no sub-task ran on its own, so it reports its own result
            } elseif ($this->task['Type'] == 'scheduled') {
                $taskNotifyController = new Notify();
                $taskNotifyController->result($this->taskId, ['status' => $this->status]);
            }

            // Clean unused repos from profiles
            $this->profileController->cleanProfiles();

            // Update layout containers states
            $this->layoutContainerReloadController->reload('header/menu');
            $this->layoutContainerReloadController->reload('repos/kpi');
            $this->layoutContainerReloadController->reload('repos/list');
            $this->layoutContainerReloadController->reload('tasks/tasks');
            $this->layoutContainerReloadController->reload('tasks/log');
            $this->layoutContainerReloadController->reload('snapshot/kpi');
            $this->layoutContainerReloadController->reload('snapshot/list');
        } catch (Exception $e) {
            throw new Exception('Error while ending task: ' . $e->getMessage());
        }
    }
}
