<?php

namespace Controllers\Task;

use Exception;
use Controllers\Repo\Repo;
use Controllers\Repo\Listing as RepoListing;

/**
 *  A task target describes a dynamic set of repositories, instead of a fixed list of snapshots.
 *  It is used by scheduled tasks that must always run on the latest snapshot of every matching
 *  repository, including repositories created after the task has been scheduled.
 */
class Target
{
    /**
     *  Actions that support a dynamic target
     */
    private $validActions = ['update', 'env', 'rebuild'];

    /**
     *  Return the list of actions supporting a dynamic target
     */
    public function getValidActions(): array
    {
        return $this->validActions;
    }

    /**
     *  Return true if the specified task parameters target a dynamic set of repositories
     */
    public static function isDynamic(array $taskParams): bool
    {
        return !empty($taskParams['target']) and is_array($taskParams['target']);
    }

    /**
     *  Validate and clean a target definition
     *  Returns the sanitized target
     */
    public function validate(array $target): array
    {
        $groupId = '';
        $tags = [];
        $packageType = '';

        /**
         *  Check group, if any
         *  The group Id is stored instead of its name, so that renaming a group has no impact on the target
         *  Group Id 0 is a fictitious group ('Default') holding the repositories that do not belong to any group
         */
        if (isset($target['group']) and $target['group'] !== '') {
            if (!is_numeric($target['group'])) {
                throw new Exception('Invalid target group Id');
            }

            $groupId = strval(intval($target['group']));

            if ($groupId !== '0') {
                $groupController = new \Controllers\Group\Repo();

                if (!$groupController->existsId(intval($groupId))) {
                    throw new Exception('Target group does not exist: #' . $groupId);
                }
            }
        }

        // Check tags, if any
        if (!empty($target['tags'])) {
            if (!is_array($target['tags'])) {
                throw new Exception('Invalid target tags');
            }

            foreach ($target['tags'] as $tag) {
                if (!is_string($tag)) {
                    throw new Exception('Invalid target tag');
                }

                $tag = trim($tag);

                if ($tag === '') {
                    continue;
                }

                // Tags are stored as a comma-separated string, so a tag cannot contain a comma
                if (str_contains($tag, ',')) {
                    throw new Exception('Invalid target tag: ' . $tag);
                }

                $tags[] = $tag;
            }
        }

        // Check package type, if any
        if (!empty($target['package-type'])) {
            if (!in_array($target['package-type'], ['deb', 'rpm'])) {
                throw new Exception('Invalid target package type');
            }

            $packageType = $target['package-type'];
        }

        return [
            'group' => $groupId,
            'tags' => $tags,
            'package-type' => $packageType
        ];
    }

    /**
     *  Return the list of repositories matching the target, with their latest active snapshot Id
     */
    public function resolve(array $target): array
    {
        $target = $this->validate($target);

        $repoListingController = new RepoListing();

        return $repoListingController->listByTarget($target['group'], $target['tags'], $target['package-type']);
    }

    /**
     *  Return the list of mirrors matching the target, with the same filters as repositories, and their latest active snapshot
     */
    public function resolveMirrors(array $target): array
    {
        $target = $this->validate($target);
        $mirrors = [];

        $mirrorController = new \Controllers\Mirror\Mirror();
        $mirrorSnapshotController = new \Controllers\Mirror\Snapshot();
        $mirrorsGroupsIds = $mirrorController->getGroupsIds();

        foreach ($mirrorController->get() as $mirror) {
            // Filter on package type
            if ($target['package-type'] !== '' and $mirror['Package_type'] != $target['package-type']) {
                continue;
            }

            // Filter on group, group Id 0 is the fictitious 'Default' group containing the mirrors that do not belong to any group
            if ($target['group'] !== '' and strval($mirrorsGroupsIds[$mirror['Id']] ?? 0) !== $target['group']) {
                continue;
            }

            // Filter on tags, a mirror must have ALL the specified tags to be selected
            $mirrorTags = array_filter(explode(',', $mirror['Tags'] ?? ''));

            if (!empty(array_diff($target['tags'], $mirrorTags))) {
                continue;
            }

            $mirror['latestSnapId'] = $mirrorSnapshotController->getByMirrorId($mirror['Id'])[0]['Id'] ?? null;
            $mirrors[] = $mirror;
        }

        return $mirrors;
    }

    /**
     *  Return a human readable description of the target
     *  The group name is resolved from its Id, so the description always reflects the current name
     */
    public static function describe(array $target): string
    {
        $filters = [];

        if (isset($target['group']) and $target['group'] !== '') {
            /**
             *  Keep <b> markup in the template, but escape the group name itself
             *  because it comes from data and may contain HTML special characters
             */
            $filters[] = 'from group <b>' . htmlspecialchars(self::groupName(intval($target['group'])), ENT_QUOTES) . '</b>';
        }

        if (!empty($target['tags'])) {
            /**
             *  Escape each tag value before injecting it in the HTML-formatted
             *  description. This preserves the surrounding <b> tags while ensuring
             *  tag content is displayed as text.
             */
            $escapedTags = array_map(function ($tag) {
                return htmlspecialchars($tag, ENT_QUOTES);
            }, $target['tags']);

            $filters[] = 'with tag' . (count($escapedTags) > 1 ? 's' : '') . ' <b>#' . implode(' #', $escapedTags) . '</b>';
        }

        if (!empty($target['package-type'])) {
            $filters[] = 'with package type <b>' . $target['package-type'] . '</b>';
        }

        if (empty($filters)) {
            return 'All repositories (latest snapshots)';
        }

        return 'All repositories ' . implode(' and ', $filters) . ' (latest snapshots)';
    }

    /**
     *  Return the current name of a group from its Id
     *  Group Id 0 is the fictitious 'Default' group
     */
    private static function groupName(int $groupId): string
    {
        if ($groupId === 0) {
            return 'Default';
        }

        try {
            $groupController = new \Controllers\Group\Repo();

            return $groupController->getNameById(intval($groupId));
        } catch (Exception $e) {
            // The group may have been deleted since the task was scheduled
            return '#' . $groupId . ' (deleted)';
        }
    }

    /**
     *  Expand a dynamic task into a list of concrete task parameters, one per matching repository
     *  Each generated task targets the latest snapshot of the repository and is meant to be executed immediately
     */
    public function expand(array $taskParams): array
    {
        $tasksParams = [];

        if (empty($taskParams['action'])) {
            throw new Exception('No action has been specified');
        }

        $action = $taskParams['action'];

        if (!in_array($action, $this->validActions)) {
            throw new Exception('Action does not support a dynamic target: ' . $action);
        }

        if (empty($taskParams['target'])) {
            throw new Exception('No target has been specified');
        }

        // Retrieve all the repositories matching the target
        $repos = $this->resolve($taskParams['target']);

        foreach ($repos as $repo) {
            $params = [
                'action' => $action,
                'repo-id' => strval($repo['repoId']),
                'snap-id' => strval($repo['snapId']),
                'env-id' => '',

                // Generated tasks are executed immediately, the recurrence is carried by the parent task
                'schedule' => [
                    'scheduled' => 'false'
                ]
            ];

            /**
             *  Retrieve the current repository settings, as some parameters cannot be known
             *  when the task is scheduled (e.g. the architectures of a repository created later)
             */
            $repoController = new Repo();
            $repoController->getAllById(null, $repo['snapId'], null);

            if ($action == 'update') {
                // Each repository is updated with its own current architectures and advanced parameters
                $params['arch'] = $repoController->getArch();
                $params['advanced-params'] = $repoController->getAdvancedParams();
                $params['gpg-sign'] = $taskParams['gpg-sign'] ?? 'false';

                // GPG check only applies to curated repositories
                if ($repoController->getType() == 'curated') {
                    $params['gpg-check'] = $taskParams['gpg-check'] ?? 'false';
                }

                // Environments to point to the new snapshot are optional
                if (!empty($taskParams['env'])) {
                    $params['env'] = $taskParams['env'];
                }
            }

            if ($action == 'env') {
                $params['env'] = $taskParams['env'] ?? [];
            }

            if ($action == 'rebuild') {
                $params['gpg-sign'] = $taskParams['gpg-sign'] ?? 'false';
            }

            unset($repoController);

            $tasksParams[] = $params;
        }

        // Mirrors matching the target, the rebuild action does not apply to mirrors (their metadata are never rebuilt)
        if (in_array($action, ['update', 'env'])) {
            foreach ($this->resolveMirrors($taskParams['target']) as $mirror) {
                $params = [
                    'domain' => 'mirror',
                    'action' => $action,
                    'mirror-id' => strval($mirror['Id']),

                    // Generated tasks are executed immediately, the recurrence is carried by the parent task
                    'schedule' => [
                        'scheduled' => 'false'
                    ]
                ];

                // Environments to point to the new snapshot are optional
                if ($action == 'update' and !empty($taskParams['env'])) {
                    $params['env'] = $taskParams['env'];
                }

                // Environments are pointed to the latest snapshot of the mirror, a mirror without snapshot is ignored
                if ($action == 'env') {
                    if (empty($mirror['latestSnapId'])) {
                        continue;
                    }

                    $params['mirror-snap-id'] = strval($mirror['latestSnapId']);
                    $params['env'] = $taskParams['env'] ?? [];
                }

                $tasksParams[] = $params;
            }
        }

        return $tasksParams;
    }
}
