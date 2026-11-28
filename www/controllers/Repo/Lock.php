<?php

namespace Controllers\Repo;

use Exception;

/**
 *  Lock of a repository: a release version of a rpm repository, or a section of a deb repository distribution
 *
 *  A task modifying a repository (its snapshots directories, its environments, its database entries) holds its lock,
 *  so that only one task at a time can modify it. A task targeting a repository locked by another task waits for it to end.
 *  The lock is a flock() on a file in LOCKS_DIR: the kernel releases it when the process holding it ends, so that a crashed
 *  or killed task never leaves its repository locked. The file contains the Id of the task holding the lock.
 */
class Lock
{
    private string $key;
    private string $label;
    private string $file;
    private $handle = null;

    /**
     *  @param array $repo Repository details, as returned by Repo::getTaskInfo() (package-type, name, releasever or dist and section)
     */
    public function __construct(array $repo)
    {
        if (empty($repo['package-type']) or empty($repo['name'])) {
            throw new Exception('Cannot lock repository: its package type and name must be specified');
        }

        // The key is the path of the repository directory (relative to REPOS_DIR)
        if ($repo['package-type'] == 'deb') {
            $this->key = 'deb/' . $repo['name'] . '/' . ($repo['dist'] ?? '') . '/' . ($repo['section'] ?? '');
            $this->label = $repo['name'] . ' ❯ ' . ($repo['dist'] ?? '') . ' ❯ ' . ($repo['section'] ?? '');
        } else {
            $this->key = $repo['package-type'] . '/' . $repo['name'] . '/' . ($repo['releasever'] ?? '');
            $this->label = $repo['name'] . ' ❯ ' . ($repo['releasever'] ?? '');
        }

        // The key can contain characters that are not allowed in a file name (e.g. deb distribution 'bookworm/updates')
        $this->file = LOCKS_DIR . '/repo-' . md5($this->key) . '.lock';
    }

    /**
     *  Return the key identifying the repository (its directory path relative to REPOS_DIR)
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     *  Return the repository label (e.g. 'name ❯ releasever')
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     *  Acquire the lock for the specified task without waiting, return false if another task holds it
     */
    public function tryAcquire(int $taskId): bool
    {
        $handle = $this->open();

        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);
            return false;
        }

        $this->hold($handle, $taskId);

        return true;
    }

    /**
     *  Acquire the lock for the specified task, waiting as long as needed for the task holding it to end
     */
    public function acquire(int $taskId): void
    {
        $handle = $this->open();

        if (!flock($handle, LOCK_EX)) {
            fclose($handle);
            throw new Exception('Cannot lock repository ' . $this->label);
        }

        $this->hold($handle, $taskId);
    }

    /**
     *  Return the Id of the task holding the lock, or null if it is unknown
     *  The file keeps the Id of the last task that held the lock: it is only meaningful while the lock is held by another task
     */
    public function getHolder(): int|null
    {
        if (!is_file($this->file)) {
            return null;
        }

        $content = trim((string) file_get_contents($this->file));

        if (!ctype_digit($content)) {
            return null;
        }

        return (int) $content;
    }

    /**
     *  Release the lock
     */
    public function release(): void
    {
        if ($this->handle === null) {
            return;
        }

        flock($this->handle, LOCK_UN);
        fclose($this->handle);
        $this->handle = null;
    }

    /**
     *  Open the lock file, it is created if it does not exist
     */
    private function open()
    {
        if ($this->handle !== null) {
            throw new Exception('Repository ' . $this->label . ' is already locked by this task');
        }

        $handle = fopen($this->file, 'c');

        if ($handle === false) {
            throw new Exception('Cannot open lock file ' . $this->file);
        }

        return $handle;
    }

    /**
     *  Keep the lock, and write the Id of the task holding it in the file for the tasks waiting for it
     */
    private function hold($handle, int $taskId): void
    {
        ftruncate($handle, 0);
        fwrite($handle, (string) $taskId);
        fflush($handle);

        $this->handle = $handle;
    }
}
