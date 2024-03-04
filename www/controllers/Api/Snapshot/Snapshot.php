<?php

namespace Controllers\Api\Snapshot;

use Exception;
use Datetime;

class Snapshot extends \Controllers\Api\Controller
{
    private $snapId;
    private $postFiles;

    public function execute()
    {
        $myrepo = new \Controllers\Repo\Repo();
        $mypackage = new \Controllers\Repo\Package();

        /**
         *  Snapshot actions are only allowed for API admins
         */
        if (!IS_API_ADMIN) {
            throw new Exception('You are not allowed to access this resource.');
        }

        /**
         *  Retrieve snapshot Id if any
         */
        if (!empty($this->uri[4])) {
            $this->snapId = $this->uri[4];
        }

        /**
         *  Retrieve action if any
         */
        if (!empty($this->uri[5])) {
            $this->action = $this->uri[5];
        }

        /**
         *  Retrieve uploaded FILES if any
         */
        if (!empty($_FILES)) {
            $this->postFiles = $_FILES;
        }

        /**
         *  If a snapshot Id is specified
         *  https://repomanager.mydomain.net/api/v2/snapshot/$this->snapId/
         */
        if (!empty($this->snapId)) {
            if ($this->method == 'POST') {
                /**
                 *  Upload packages to a snapshot
                 *  https://repomanager.mydomain.net/api/v2/snapshot/$this->snapId/upload
                 */
                if ($this->action == 'upload' and !empty($this->postFiles)) {
                    $mypackage->upload($this->snapId, $this->postFiles);

                    return array('results' => 'Packages uploaded successfully');
                }
            }

            if ($this->method == 'PUT') {
                /**
                 *  Rebuild a snapshot
                 *  https://repomanager.mydomain.net/api/v2/snapshot/$this->snapId/rebuild
                 */
                if ($this->action == 'rebuild' and !empty($this->data->gpgSign)) {
                    /**
                     *  Same code as controllers/ajax/browse.php
                     *  TODO : find a way to not duplicate code
                     */
                    $mytask = new \Controllers\Task\Task();

                    if ($myrepo->existsSnapId($this->snapId) !== true) {
                        throw new Exception('Invalid repository snapshot Id');
                    }

                    if ($this->data->gpgSign != 'yes' and $this->data->gpgSign != 'no') {
                        throw new Exception('Invalid GPG sign value');
                    }

                    /**
                     *  Create a json file that defines the task to execute
                     */
                    $params = array();
                    $params['action'] = 'rebuild';
                    $params['snap-id'] = $this->snapId;
                    $params['gpg-sign'] = $this->data->gpgSign;
                    $params['schedule']['scheduled'] = 'false';

                    /**
                     *  Execute the task
                     */
                    $mytask->execute(array($params));

                    unset($mytask);

                    return array('results' => 'Snapshot metadata rebuild started');
                }
            }
        }

        throw new Exception('Invalid request');
    }
}
