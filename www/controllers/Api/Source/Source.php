<?php

namespace Controllers\Api\Source;

use Exception;
use Datetime;

class Source extends \Controllers\Api\Controller
{
    private $source;
    private $type;
    private $component;
    private $nameOrAction;
    private $postFiles;

    public function execute()
    {
        $mysource = new \Controllers\Repo\Source\Source();

        /**
         *  Source repositories actions are only allowed for API admins
         */
        if (!IS_API_ADMIN) {
            throw new Exception('You are not allowed to access this resource.');
        }

        /**
         *  Retrieve source repository and actions from URI
         */
        if (isset($this->uri[4])) {
            $this->type = $this->uri[4];
        }
        if (isset($this->uri[5])) {
            $this->nameOrAction = $this->uri[5];
        }

        /**
         *  Retrieve uploaded FILES if any
         */
        if (!empty($_FILES)) {
            $this->postFiles = $_FILES;
        }

        /**
         *  https://repomanager.mydomain.net/api/v2/source/
         *  Print all source repositories
         */
        if (empty($this->type) and $this->method == 'GET') {
            return array('results' => $mysource->listAll());
        }

        /**
         *  If a source type is specified (deb, rpm)
         *  https://repomanager.mydomain.net/api/v2/source/$this->type/
         */
        if (!empty($this->type)) {
            if (!in_array($this->type, array('deb', 'rpm'))) {
                throw new Exception('Invalid source type');
            }

            /**
             *  If no source name or action is specified, then list all sources of the specified type
             */
            if (empty($this->nameOrAction) and $this->method == 'GET') {
                return array('results' => $mysource->listAll($this->type));
            }

            /**
             *  If a source repo name or an action is specified
             */
            if (!empty($this->nameOrAction)) {
                /**
                 *  If the action is import, import source repositories from a template file
                 */
                if ($this->nameOrAction == 'import' and $this->method == 'POST') {
                    if (empty($this->postFiles)) {
                        throw new Exception('You must provide a template file');
                    }

                    /**
                     *  Only one file is allowed
                     */
                    if (count($this->postFiles) > 1) {
                        throw new Exception('Please, only provide one file at a time');
                    }

                    /**
                     *  The file must be prefixed with name 'template'
                     */
                    if (!isset($this->postFiles['template'])) {
                        throw new Exception('The file must be prefixed with "template"');
                    }

                    /**
                     *  Import source repositories from the template file
                     */
                    $mysource->importYamlFromApi($this->postFiles['template']['tmp_name']);

                    return array('results' => 'Source repositories imported successfully');
                }

                /**
                 *  Else, return source repository details by its name
                 */
                if ($this->nameOrAction != 'import') {
                    if ($this->method == 'GET') {
                        return array('results' => $mysource->get($this->type, $this->nameOrAction));
                    }
                }
            }
        }

        throw new Exception('Invalid request');
    }
}
