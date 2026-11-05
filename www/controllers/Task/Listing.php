<?php

namespace Controllers\Task;

class Listing
{
    /**
     *  Actions that done tasks can be searched by
     */
    public const SEARCH_ACTIONS = [
        'create'    => 'Create',
        'update'    => 'Update / sync',
        'env'       => 'Point an environment',
        'removeEnv' => 'Remove an environment',
        'rebuild'   => 'Rebuild metadata',
        'duplicate' => 'Duplicate',
        'rename'    => 'Rename',
        'delete'    => 'Delete'
    ];

    private $model;

    public function __construct()
    {
        $this->model = new \Models\Task\Listing();
    }

    /**
     *  Get all tasks
     */
    public function get(): array
    {
        return $this->model->get();
    }

    /**
     *  Get all queued tasks
     *  It is possible to filter the type of task ('immediate' or 'scheduled')
     *  It is possible to add an offset to the request
     */
    public function getQueued(bool $withNoParent = true, bool $withOffset = false, int $offset = 0): array
    {
        return $this->model->getQueued($withNoParent, $withOffset, $offset);
    }

    /**
     *  Get all running tasks
     *  It is possible to filter the type of task ('immediate' or 'scheduled')
     *  It is possible to add an offset to the request
     */
    public function getRunning(string $type = '', bool $withOffset = false, int $offset = 0): array
    {
        return $this->model->getRunning($type, $withOffset, $offset);
    }

    /**
     *  Get all tasks having the given status that are actually executed
     *  Sub-tasks are included and parent tasks are excluded, as a parent task only groups sub-tasks and never runs itself
     */
    public function getExecutable(string $status, bool $onlyId = false): array
    {
        return $this->model->getExecutable($status, $onlyId);
    }

    /**
     *  Get all scheduled tasks
     *  It is possible to add an offset to the request
     */
    public function getScheduled(bool $withOffset = false, int $offset = 0): array
    {
        return $this->model->getScheduled($withOffset, $offset);
    }

    /**
     *  Get all done tasks (with or without errors)
     *  It is possible to filter the type of task ('immediate' or 'scheduled')
     *  It is possible to filter the status of task ('successful' or 'failed')
     *  It is possible to filter the tasks with search criteria (see getDoneSearch())
     *  It is possible to add an offset to the request
     */
    public function getDone(string $type = 'immediate', bool $withOffset = false, int $offset = 0, string $status = '', array $search = []): array
    {
        return $this->model->getDone($type, $withOffset, $offset, $status, $search);
    }

    /**
     *  Count done tasks (with or without errors)
     *  It is possible to filter the status of task ('successful' or 'failed')
     *  It is possible to filter the tasks with search criteria (see getDoneSearch())
     */
    public function countDone(string $status = '', array $search = []): int
    {
        return $this->model->countDone($status, $search);
    }

    /**
     *  Return the usernames of the users who launched done tasks, to filter done tasks by user
     */
    public function getDoneLaunchedBy(): array
    {
        return $this->model->getDoneLaunchedBy();
    }

    /**
     *  Return the search of done tasks made by the user, from its JSON representation (stored in a cookie by the tasks page):
     *  - 'opened':   true if the search bar is opened
     *  - 'query':    searched text, repository name / distribution / section / release version, and task Id (#<id>)
     *  - 'domain':   'repo' or 'mirror' to only search tasks targeting repositories or mirrors
     *  - 'action':   one of SEARCH_ACTIONS to only search tasks with this action
     *  - 'user':     username to only search tasks launched by this user
     *  - 'criteria': criteria to filter done tasks with, to pass to getDone() and countDone()
     *  Invalid values are ignored
     */
    public function getDoneSearch(string $json): array
    {
        $search = ['opened' => false, 'query' => '', 'domain' => '', 'action' => '', 'user' => ''];
        $values = json_decode($json, true);

        if (is_array($values)) {
            $search['opened'] = ($values['opened'] ?? false) === true;

            // Search input length is limited, a longer or a non UTF-8 value has not been typed in it
            if (is_string($values['query'] ?? null) and strlen($values['query']) <= 255 and preg_match('//u', $values['query'])) {
                $search['query'] = trim($values['query']);
            }

            if (in_array($values['domain'] ?? '', ['repo', 'mirror'], true)) {
                $search['domain'] = $values['domain'];
            }

            if (is_string($values['action'] ?? null) and array_key_exists($values['action'], self::SEARCH_ACTIONS)) {
                $search['action'] = $values['action'];
            }

            if (is_string($values['user'] ?? null) and strlen($values['user']) <= 255 and preg_match('//u', $values['user'])) {
                $search['user'] = $values['user'];
            }
        }

        $search['criteria'] = [
            'terms'  => [],
            'id'     => '',
            'domain' => $search['domain'],
            'action' => $search['action'],
            'user'   => $search['user']
        ];

        // The separator of the repositories labels is ignored, so that a label can be searched as printed in the tasks list
        foreach (preg_split('/\s+/u', str_replace('❯', ' ', $search['query']), -1, PREG_SPLIT_NO_EMPTY) as $term) {
            if (preg_match('/^#(\d+)$/', $term, $matches)) {
                $search['criteria']['id'] = $matches[1];
                continue;
            }

            $search['criteria']['terms'][] = $term;
        }

        $search['criteria']['terms'] = array_slice(array_unique($search['criteria']['terms']), 0, 10);

        return $search;
    }

    /**
     *  Get all sub-tasks generated by a parent task
     *  It is possible to add an offset to the request
     */
    public function getByParentId(int $parentTaskId, bool $withOffset = false, int $offset = 0): array
    {
        return $this->model->getByParentId($parentTaskId, $withOffset, $offset);
    }

    /**
     *  Count sub-tasks generated by a parent task, grouped by status
     */
    public function countStatusByParentId(int $parentTaskId): array
    {
        return $this->model->countStatusByParentId($parentTaskId);
    }

    /**
     *  Return a summary of the sub-tasks statuses of a parent task
     *  A parent task has no status of its own as it only dispatches sub-tasks, so its real status is a summary of theirs
     *  Return an empty array if the task has no sub-task
     */
    public function getSubTasksSummary(int $parentTaskId): array
    {
        $count = $this->countStatusByParentId($parentTaskId);

        if (empty($count)) {
            return [];
        }

        return $this->buildSubTasksSummary($count);
    }

    /**
     *  Same as getSubTasksSummary() but for multiple parent tasks at once
     *  Tasks having no sub-task are not returned
     */
    public function getSubTasksSummaries(array $parentTaskIds): array
    {
        $summaries = [];

        foreach ($this->model->countStatusByParentIds($parentTaskIds) as $parentTaskId => $count) {
            $summaries[$parentTaskId] = $this->buildSubTasksSummary($count);
        }

        return $summaries;
    }

    /**
     *  Build a sub-tasks summary from a count of sub-tasks per status
     */
    private function buildSubTasksSummary(array $count): array
    {
        $summary = [
            'total'   => array_sum($count),
            'success' => $count['done'] ?? 0,
            'failed'  => ($count['error'] ?? 0) + ($count['stopped'] ?? 0),
            'ongoing' => ($count['running'] ?? 0) + ($count['queued'] ?? 0)
        ];

        if ($summary['ongoing'] > 0) {
            $summary['status'] = 'running';
        } elseif ($summary['failed'] > 0) {
            $summary['status'] = 'error';
        } else {
            $summary['status'] = 'done';
        }

        return $summary;
    }
}
