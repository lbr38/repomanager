<?php

namespace Controllers\Api\Repo;

use Exception;
use Datetime;

class Repo extends \Controllers\Api\Controller
{
    private $repoId;
    private $action;

    public function execute()
    {
        $repoController = new \Controllers\Repo\Repo();
        $repoListingController = new \Controllers\Repo\Listing();

        /**
         *  Case a repository Id is specified
         */
        if (!empty($this->uri[4]) and is_numeric($this->uri[4])) {
            // Retrieve repository Id if any
            if (!empty($this->uri[4])) {
                $this->repoId = $this->uri[4];
            }

            // Retrieve action if any
            if (!empty($this->uri[5])) {
                $this->action = $this->uri[5];
            }
        }

        /**
         *  If no repository Id is specified
         *  https://repomanager.mydomain.net/api/v2/repo/
         *  Get the list of all repositories
         */
        if (empty($this->repoId)) {
            if ($this->method == 'GET') {
                $repos = $repoListingController->listNameOnly(true);

                if (empty($repos)) {
                    throw new Exception('No repositories found');
                }

                return ['results' => $repos];
            }
        }

        /**
         *  If a repository Id is specified
         *  https://repomanager.mydomain.net/api/v2/repo/$this->repoId/
         */
        if (!empty($this->repoId)) {
            /**
             *  Get the list of all snapshots for a repository
             */
            if ($this->method == 'GET') {
                // Check if the repository exists
                if (!$repoController->existsId($this->repoId)) {
                    throw new Exception('Repository does not exist');
                }

                // Get the list of snapshots for the repository
                $snapshots = $repoListingController->listSnapshots($this->repoId);

                if (empty($snapshots)) {
                    throw new Exception('No snapshots found for this repository');
                }

                return ['results' => $snapshots];
            }
        }

        throw new Exception('Invalid request');
    }
}
