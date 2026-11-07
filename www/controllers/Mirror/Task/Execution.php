<?php

namespace Controllers\Mirror\Task;

use Controllers\Mirror\Mirror;
use Controllers\Filesystem\File;
use JsonException;
use Exception;

/**
 *  Base class for mirrors tasks
 *  Task start and end (status, duration, PID file, scheduled task notification) are inherited from the repositories tasks
 */
class Execution extends \Controllers\Task\Execution
{
    protected object $mirrorController;
    protected object $mirrorSnapshotController;
    protected object $mirrorEnvController;
    private $lock = null;
    private string|null $lockFile = null;

    public function __construct(int $taskId)
    {
        $this->taskController = new \Controllers\Task\Task();
        $this->taskLogStepController = new \Controllers\Task\Log\Step($taskId);
        $this->taskLogSubStepController = new \Controllers\Task\Log\SubStep($taskId);
        $this->profileController = new \Controllers\Profile();
        $this->layoutContainerReloadController = new \Controllers\Layout\ContainerReload();
        $this->mirrorController = new \Controllers\Mirror\Mirror();
        $this->mirrorSnapshotController = new \Controllers\Mirror\Snapshot();
        $this->mirrorEnvController = new \Controllers\Mirror\Environment();

        try {
            $this->taskId = $taskId;
            $this->date = date('Y-m-d');
            $this->time = date('H:i:s');

            $this->taskController->setId($taskId);
            $this->task = $this->taskController->getById($taskId);

            try {
                $this->params = json_decode($this->task['Raw_params'], true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new Exception('could not decode task #' . $taskId . ' parameters JSON: ' . $e->getMessage());
            }

            $this->action = $this->params['action'];

            $this->taskController->updateDate($taskId, $this->date);
            $this->taskController->updateTime($taskId, $this->time);

            $this->start();
        } catch (Exception $e) {
            throw new Exception('Task initialization error: ' . $e->getMessage());
        }
    }

    /**
     *  Lock the mirror so that only one sync runs at a time
     *  The lock is released automatically when the task process ends
     */
    protected function lock(string $dir): void
    {
        if (!is_dir(Mirror::getPath($dir))) {
            if (!mkdir(Mirror::getPath($dir), 0770, true)) {
                throw new Exception('Could not create directory ' . Mirror::getPath($dir));
            }
        }

        if (!$this->tryLock($dir)) {
            throw new Exception('Another task is already syncing this mirror');
        }
    }

    /**
     *  Try to lock the mirror, return false if another task already holds the lock
     *  The mirror directory must exist
     *  The lock file is deleted when the lock is released: once locked, check that the file has not been
     *  deleted or replaced in the meantime by the previous holder, otherwise lock the new file
     */
    protected function tryLock(string $dir): bool
    {
        if ($this->lock !== null) {
            return true;
        }

        $lockFile = Mirror::getLockFile($dir);

        for ($i = 0; $i < 10; $i++) {
            $lock = fopen($lockFile, 'c');

            if ($lock === false) {
                return false;
            }

            if (!flock($lock, LOCK_EX | LOCK_NB)) {
                fclose($lock);
                return false;
            }

            clearstatcache(true, $lockFile);
            $stat = @stat($lockFile);

            if ($stat !== false and $stat['ino'] == fstat($lock)['ino']) {
                $this->lock = $lock;
                $this->lockFile = $lockFile;
                return true;
            }

            fclose($lock);
        }

        return false;
    }

    /**
     *  Release the mirror lock and delete the lock file
     *  The file is deleted before being unlocked, so that no other task can lock it once deleted
     */
    protected function unlock(): void
    {
        if ($this->lock === null) {
            return;
        }

        if (file_exists($this->lockFile)) {
            unlink($this->lockFile);
        }

        flock($this->lock, LOCK_UN);
        fclose($this->lock);

        $this->lock = null;
        $this->lockFile = null;
    }

    /**
     *  End task execution, release the mirror lock first
     */
    public function end(): void
    {
        $this->unlock();

        parent::end();
    }

    /**
     *  Return the mirror targeted by the task (mirror-id parameter)
     */
    protected function getMirror(): array
    {
        if (empty($this->params['mirror-id']) or !is_numeric($this->params['mirror-id'])) {
            throw new Exception('Mirror Id must be specified');
        }

        $mirror = $this->mirrorController->getById((int) $this->params['mirror-id']);

        if (empty($mirror)) {
            throw new Exception('Mirror #' . (int) $this->params['mirror-id'] . ' does not exist');
        }

        return $mirror;
    }

    /**
     *  Return the mirror snapshot targeted by the task (mirror-snap-id parameter)
     *  'snap-id' is not used to never be confused with repositories snapshots
     */
    protected function getMirrorSnapshot(int $mirrorId): array
    {
        if (empty($this->params['mirror-snap-id']) or !is_numeric($this->params['mirror-snap-id'])) {
            throw new Exception('Snapshot Id must be specified');
        }

        $snapshot = $this->mirrorSnapshotController->getById((int) $this->params['mirror-snap-id']);

        if (empty($snapshot) or $snapshot['Id_mirror'] != $mirrorId or $snapshot['Status'] != 'active') {
            throw new Exception('Snapshot #' . (int) $this->params['mirror-snap-id'] . ' does not exist');
        }

        return $snapshot;
    }

    /**
     *  Return source repository definition
     */
    protected function getSourceDefinition(string $packageType, string $sourceName): array
    {
        $sourceController = new \Controllers\Repo\Source\Source();
        $source = $sourceController->get($packageType, $sourceName);

        if (empty($source['Definition'])) {
            throw new Exception('Could not retrieve source repository ' . $sourceName . '. Does the source repository still exist?');
        }

        try {
            return json_decode($source['Definition'], true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new Exception('Could not decode source repository definition: ' . $e->getMessage());
        }
    }

    /**
     *  Check and return mirror parameters from task parameters
     */
    protected function getMirrorParams(string $packageType): array
    {
        $params = [];

        Mirror::checkArchs($this->params['arch'] ?? null);

        $params['arch'] = array_values($this->params['arch']);
        $params['gpg-check'] = ($this->params['gpg-check'] ?? 'true') == 'false' ? 'false' : 'true';

        // A deb mirror can contain several distributions, sharing the same pool as upstream
        if ($packageType == 'deb') {
            $params['dists'] = array_values(array_filter((array) ($this->params['dist'] ?? [])));
            $params['components'] = array_values($this->params['components'] ?? []);

            Mirror::checkDebValues(array_merge($params['dists'], $params['components']));
        }

        if ($packageType == 'rpm') {
            Mirror::checkReleasever($this->params['releasever'] ?? '');

            $params['releasever'] = $this->params['releasever'];
        }

        // An Arch Linux mirror can contain several repositories (core, extra...)
        if ($packageType == 'arch') {
            $params['repos'] = array_values(array_filter((array) ($this->params['repos'] ?? [])));

            Mirror::checkArchRepos($params['repos']);
        }

        // Only retrieve the metadata, used to test the source repository templates (not available from the interface)
        if (($this->params['metadata-only'] ?? 'false') == 'true') {
            $params['metadata-only'] = 'true';
        }

        return $params;
    }

    /**
     *  Return sync controller for the specified package type
     */
    protected function getSyncController(string $packageType, string $dir, array $sourceDefinition, array $params): \Controllers\Mirror\Sync\Sync
    {
        $controllers = [
            'deb' => \Controllers\Mirror\Sync\Deb::class,
            'rpm' => \Controllers\Mirror\Sync\Rpm::class,
            'arch' => \Controllers\Mirror\Sync\Arch::class
        ];

        if (!isset($controllers[$packageType])) {
            throw new Exception('Package type ' . $packageType . ' is not supported yet for mirrors');
        }

        $sync = new $controllers[$packageType]($this->taskId, Mirror::getWorkingDir($dir));
        $sync->setSource($sourceDefinition);
        $sync->setParams($params);
        $sync->setCheckSignature($params['gpg-check'] == 'true');

        return $sync;
    }

    /**
     *  Lock the mirror and return the sync controller, ready to mirror the source repository
     */
    protected function prepareSync(string $packageType, string $dir, string $sourceName, array $params): \Controllers\Mirror\Sync\Sync
    {
        $sourceDefinition = $this->getSourceDefinition($packageType, $sourceName);

        $this->taskLogSubStepController->output('Source repository: ' . htmlspecialchars($sourceName) . ' (<span class="copy wordbreakall">' . htmlspecialchars($sourceDefinition['url'] ?? '') . '</span>)');

        $this->lock($dir);

        // A working directory may remain from a previous failed attempt, its files will be reused
        if (is_dir(Mirror::getWorkingDir($dir))) {
            $this->taskLogSubStepController->output('Resuming from a previous sync attempt', 'note');
        }

        return $this->getSyncController($packageType, $dir, $sourceDefinition, $params);
    }

    /**
     *  Add a published snapshot in database and point the specified environments to it, return the snapshot Id
     */
    protected function addSnapshot(int $mirrorId, string $dir, array $published, \Controllers\Mirror\Sync\Sync $sync, array $envs): int
    {
        [$date, $time, $snapDirName] = $published;

        $snapId = $this->mirrorSnapshotController->add($date, $time, 'active', $mirrorId);
        $this->mirrorSnapshotController->updateSize($snapId, $sync->getTotalSize());

        $this->pointEnvs($mirrorId, $dir, $snapId, $snapDirName, $envs);

        $this->taskLogSubStepController->completed('Snapshot ' . Mirror::getSnapshotLabel($snapDirName) . ' published');

        return $snapId;
    }

    /**
     *  Move the working directory to a new snapshot directory, return snapshot date, time and directory name
     */
    protected function publishSnapshot(string $dir): array
    {
        $date = date('Y-m-d');
        $time = date('H:i:s');
        $snapshotPath = Mirror::getSnapshotPath($dir, $date, $time);

        if (file_exists($snapshotPath)) {
            throw new Exception('Snapshot directory ' . $snapshotPath . ' already exists');
        }

        // Apply the same permissions as the repositories snapshots
        File::recursiveChmod(Mirror::getWorkingDir($dir), 'dir', 770);
        File::recursiveChmod(Mirror::getWorkingDir($dir), 'file', 660);

        if (!rename(Mirror::getWorkingDir($dir), $snapshotPath)) {
            throw new Exception('Could not move working directory to ' . $snapshotPath);
        }

        return [$date, $time, Mirror::getSnapshotDirName($date, $time)];
    }

    /**
     *  Check that environments exist, return them
     */
    protected function checkEnvs(array $envs): array
    {
        $existingEnvs = (new \Controllers\Environment())->getAllByName();

        foreach ($envs as $env) {
            if (!in_array($env, $existingEnvs)) {
                throw new Exception('Environment ' . htmlspecialchars($env) . ' does not exist');
            }
        }

        return array_values($envs);
    }

    /**
     *  Point environments to a snapshot
     */
    protected function pointEnvs(int $mirrorId, string $dir, int $snapId, string $snapDirName, array $envs): void
    {
        foreach ($envs as $env) {
            $this->mirrorEnvController->point($mirrorId, $dir, $snapId, $snapDirName, $env);
            $this->taskLogSubStepController->output('Environment ' . $env . ' now points to snapshot ' . Mirror::getSnapshotLabel($snapDirName));
        }
    }
}
