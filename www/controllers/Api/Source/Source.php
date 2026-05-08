<?php

namespace Controllers\Api\Source;

use Controllers\User\Permission\Repo as RepoPermission;
use Controllers\Repo\Source\Source as SourceRepository;
use Exception;

class Source extends \Controllers\Api\Controller
{
    private $postFiles;

    public function execute()
    {
        $sourceController = new SourceRepository();

        // Check user permissions
        if (!RepoPermission::allowedAction('edit-source')) {
            throw new Exception('You are not allowed to edit source repositories');
        }

        // Retrieve uploaded FILES if any
        if (!empty($_FILES)) {
            $this->postFiles = $_FILES;
        }

        /**
         *  https://repomanager.mydomain.net/api/v2/source/
         *  Print all source repositories
         */
        if (empty($this->uri[4]) and $this->method == 'GET') {
            return ['results' => $sourceController->listAll()];
        }

        /**
         *  If a source type is specified (deb, rpm)
         *  https://repomanager.mydomain.net/api/v2/source/{type}/
         */
        if (!empty($this->uri[4])) {
            if (!in_array($this->uri[4], array('deb', 'rpm'))) {
                throw new Exception('Invalid source repository type (deb or rpm)');
            }

            // If no source name or action is specified, then list all sources of the specified type
            if (empty($this->uri[5]) and $this->method == 'GET') {
                return ['results' => $sourceController->listAll($this->uri[4])];
            }

            // If a source repository name or an action is specified
            if (!empty($this->uri[5])) {
                // If the action is import, import source repositories from a template file
                if ($this->uri[5] == 'import' and $this->method == 'POST') {
                    if (empty($this->postFiles)) {
                        throw new Exception('You must provide a template file');
                    }

                    // Only one file is allowed
                    if (count($this->postFiles) > 1) {
                        throw new Exception('Please, only provide one file at a time');
                    }

                    // The file must be prefixed with name 'template'
                    if (!isset($this->postFiles['template'])) {
                        throw new Exception('The file must be prefixed with "template"');
                    }

                    // Import source repositories from the template file
                    $sourceController->importYamlFromApi($this->postFiles['template']['tmp_name']);

                    return ['results' => 'Source repositories imported successfully'];
                }

                // Else, return source repository details by its name
                if ($this->uri[5] != 'import') {
                    if ($this->method == 'GET') {
                        return ['results' => $sourceController->get($this->uri[4], $this->uri[5])];
                    }
                }
            }
        }

        throw new Exception('Invalid request');
    }
}
