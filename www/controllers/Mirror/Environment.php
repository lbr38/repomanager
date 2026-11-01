<?php

namespace Controllers\Mirror;

use Controllers\Utils\Validate;
use Exception;

class Environment
{
    private $model;

    public function __construct()
    {
        $this->model = new \Models\Mirror\Environment();
    }

    /**
     *  Return all environments associated to a snapshot Id
     */
    public function getBySnapId(int $snapId): array
    {
        return $this->model->getBySnapId($snapId);
    }

    /**
     *  Return environments of a mirror, as an array env => snapshot Id
     */
    public function getByMirrorId(int $mirrorId): array
    {
        return $this->model->getByMirrorId($mirrorId);
    }

    /**
     *  Point an environment to a snapshot
     *  The symlink is switched atomically (new symlink created under a temporary name, then renamed over the old one)
     *  so that clients never get a 404 during the switch
     */
    public function point(int $mirrorId, string $mirrorDir, int $snapId, string $snapDirName, string $env): void
    {
        if (!Validate::alphaNumericHyphen($env)) {
            throw new Exception('Environment name contains invalid characters');
        }

        $link = Mirror::getPath($mirrorDir) . '/' . $env;
        $tmpLink = Mirror::getPath($mirrorDir) . '/.tmp-link-' . $env;

        if (!is_dir(Mirror::getPath($mirrorDir) . '/' . $snapDirName)) {
            throw new Exception('Snapshot directory ' . $snapDirName . ' does not exist');
        }

        // Remove a temporary symlink that may remain from a previous failed switch
        if (is_link($tmpLink)) {
            if (!unlink($tmpLink)) {
                throw new Exception('Could not remove temporary symlink ' . $tmpLink);
            }
        }

        if (!symlink($snapDirName, $tmpLink)) {
            throw new Exception('Could not create symlink ' . $tmpLink);
        }

        if (!rename($tmpLink, $link)) {
            throw new Exception('Could not switch environment ' . $env . ' to ' . $snapDirName);
        }

        // An environment can only point to one snapshot of the mirror
        $this->model->removeFromMirror($mirrorId, $env);
        $this->model->add($snapId, $env);
    }

    /**
     *  Remove an environment from a mirror
     */
    public function remove(int $mirrorId, string $mirrorDir, string $env): void
    {
        if (!Validate::alphaNumericHyphen($env)) {
            throw new Exception('Environment name contains invalid characters');
        }

        $link = Mirror::getPath($mirrorDir) . '/' . $env;

        if (is_link($link)) {
            if (!unlink($link)) {
                throw new Exception('Could not remove symlink ' . $link);
            }
        }

        $this->model->removeFromMirror($mirrorId, $env);
    }

    /**
     *  Remove all environments associated to a snapshot Id (database only)
     */
    public function removeBySnapId(int $snapId): void
    {
        $this->model->removeBySnapId($snapId);
    }
}
