<?php

namespace Controllers;

use Exception;

/**
 *  Exclusive lock shared between processes, based on flock() on a file in LOCKS_DIR
 *  The kernel releases the lock when the process holding it ends, so a crashed process never leaves it locked
 */
class Lock
{
    private string $file;
    private $handle = null;

    public function __construct(string $name)
    {
        $this->file = LOCKS_DIR . '/' . $name . '.lock';
    }

    /**
     *  Acquire the lock, waiting at most $timeout seconds for another process to release it
     */
    public function acquire(int $timeout = 30): void
    {
        if ($this->handle !== null) {
            throw new Exception('Lock ' . $this->file . ' is already acquired');
        }

        $handle = fopen($this->file, 'c');

        if ($handle === false) {
            throw new Exception('Cannot open lock file ' . $this->file);
        }

        $deadline = microtime(true) + $timeout;

        // Non-blocking attempts, so that a process never waits forever for the lock
        while (!flock($handle, LOCK_EX | LOCK_NB)) {
            if (microtime(true) >= $deadline) {
                fclose($handle);
                throw new Exception('Timeout while waiting for lock ' . $this->file);
            }

            usleep(50000);
        }

        $this->handle = $handle;
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
}
