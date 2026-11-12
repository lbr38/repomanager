<?php

namespace Controllers\Api\Mirror;

use Controllers\Mirror\Mirror as MirrorController;
use Controllers\User\Permission\Repo as RepoPermission;
use Exception;

class Mirror extends \Controllers\Api\Controller
{
    public function execute(): array
    {
        $mirrorController = new MirrorController();

        /**
         *  List all mirrors the user is allowed to view
         *  https://repomanager.mydomain.net/api/v2/mirror/
         */
        if (empty($this->uri[4])) {
            if ($this->method == 'GET') {
                $mirrors = [];

                foreach ($mirrorController->get() as $mirror) {
                    if (RepoPermission::allowedToViewMirror((int) $mirror['Id'])) {
                        $mirrors[] = $this->format($mirror);
                    }
                }

                if (empty($mirrors)) {
                    throw new Exception('No mirrors found');
                }

                return ['results' => $mirrors];
            }

            throw new Exception('Invalid request');
        }

        // Same checks and error messages as the mirror pages: invalid Id, unknown mirror, mirror not allowed
        $mirror = $mirrorController->getForPage($this->uri[4]);

        /**
         *  Get mirror details with its snapshots, most recent first
         *  https://repomanager.mydomain.net/api/v2/mirror/{mirror-id}/
         */
        if (empty($this->uri[5]) and $this->method == 'GET') {
            return ['results' => $this->format($mirror, true)];
        }

        /**
         *  Sync the mirror into a new snapshot, optionally pointing environments to it
         *  https://repomanager.mydomain.net/api/v2/mirror/{mirror-id}/sync
         */
        if (($this->uri[5] ?? '') == 'sync' and $this->method == 'POST') {
            $taskId = $this->executeTask([
                'action' => 'update',
                'mirror-id' => strval($mirror['Id']),
                'env' => (array) ($this->data['env'] ?? [])
            ]);

            return ['rc' => 202, 'results' => 'Mirror sync started', 'task-id' => $taskId];
        }

        /**
         *  Point environments to a snapshot of the mirror
         *  https://repomanager.mydomain.net/api/v2/mirror/{mirror-id}/env
         */
        if (($this->uri[5] ?? '') == 'env' and $this->method == 'POST') {
            if (empty($this->data['snap-id']) or empty($this->data['env'])) {
                throw new Exception('Snapshot Id (snap-id) and environment(s) (env) must be specified');
            }

            $taskId = $this->executeTask([
                'action' => 'env',
                'mirror-id' => strval($mirror['Id']),
                'mirror-snap-id' => strval($this->data['snap-id']),
                'env' => (array) $this->data['env']
            ]);

            return ['rc' => 202, 'results' => 'Pointing environment(s) started', 'task-id' => $taskId];
        }

        throw new Exception('Invalid request');
    }

    /**
     *  Validate and execute a mirror task, as the mirror forms do, return the task Id
     *  Environments can be specified as a single name or a list of names
     */
    private function executeTask(array $params): int
    {
        $params['domain'] = 'mirror';
        $params['schedule'] = ['scheduled' => 'false'];

        (new \Controllers\Task\Form\Form())->validate([$params]);

        return (new \Controllers\Task\Task())->execute([$params]);
    }

    /**
     *  Return the public details of a mirror, with its snapshots and their environments if requested
     */
    private function format(array $mirror, bool $withSnapshots = false): array
    {
        $result = [
            'Id'           => $mirror['Id'],
            'Name'         => $mirror['Name'],
            'Releasever'   => $mirror['Releasever'],
            'Package_type' => $mirror['Package_type'],
            'Source'       => $mirror['Source'],
            'Params'       => MirrorController::getParams($mirror),
            'Description'  => $mirror['Description'],
            'Tags'         => array_values(array_filter(explode(',', $mirror['Tags'] ?? '')))
        ];

        if (!$withSnapshots) {
            return $result;
        }

        $mirrorSnapshotController = new \Controllers\Mirror\Snapshot();
        $mirrorEnvController = new \Controllers\Mirror\Environment();
        $url = WWW_MIRRORS_DIR_URL . '/' . MirrorController::getDirOf($mirror);

        $result['Snapshots'] = [];

        foreach ($mirrorSnapshotController->getByMirrorId($mirror['Id']) as $snapshot) {
            $envs = [];

            // URL to use on clients for each environment pointing to the snapshot
            foreach ($mirrorEnvController->getBySnapId((int) $snapshot['Id']) as $env) {
                $envs[] = ['Env' => $env, 'Url' => $url . '/' . $env . '/'];
            }

            $result['Snapshots'][] = [
                'Id'   => $snapshot['Id'],
                'Date' => $snapshot['Date'],
                'Time' => $snapshot['Time'],
                'Size' => $snapshot['Size'],
                'Envs' => $envs
            ];
        }

        return $result;
    }
}
