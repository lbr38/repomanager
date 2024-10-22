<?php

namespace Controllers\Repo\Source;

use Exception;

class Source
{
    private $model;

    public function __construct()
    {
        $this->model = new \Models\Repo\Source\Source();
    }

    /**
     *  Get source repository definition
     */
    public function get(string $sourceType, string $sourceName)
    {
        return $this->model->get($sourceType, $sourceName);
    }

    /**
     *  Get source repo Id from its type and name
     */
    public function getIdByTypeName(string $type, string $name)
    {
        return $this->model->getIdByTypeName($type, $name);
    }

    /**
     *  Get source repository type from its Id
     */
    public function getType(string $id)
    {
        return $this->model->getType($id);
    }

    /**
     *  Get source repo definition from its Id
     */
    public function getDefinition(string $id)
    {
        return $this->model->getDefinition($id);
    }

    /**
     *  List all source repositories
     */
    public function listAll(string $type = null, bool $withOffset = false, int $offset = 0)
    {
        return $this->model->listAll($type, $withOffset, $offset);
    }

    /**
     *  Add a new source repository
     */
    public function new(string $method, array $params)
    {
        $validTypes = ['deb', 'rpm'];

        if (empty($params['name'])) {
            throw new Exception('Source repository name is empty');
        }
        if (empty($params['url'])) {
            throw new Exception('Source repository URL is empty');
        }
        if (empty($params['type'])) {
            throw new Exception('Source repository type is empty');
        }

        $name = \Controllers\Common::validateData($params['name']);
        $type = \Controllers\Common::validateData($params['type']);
        $url = trim($params['url']);
        $url = stripslashes($url);

        /**
         *  Check that source repo name is valid
         */
        if (!\Controllers\Common::isAlphanumDash($name)) {
            throw new Exception('Source repository name cannot contain special characters except hyphen and underscore');
        }

        /**
         *  Check that type is valid
         */
        if (!in_array($type, $validTypes)) {
            throw new Exception('Invalid source repository type');
        }

        /**
         *  Check that URL starts with http(s)://
         */
        if (!preg_match('#^https?://#', $url)) {
            throw new Exception('Specified URL must start with <b>http(s)://</b>');
        }

        /**
         *  Check that URL is valid
         */
        if (!\Controllers\Common::isAlphanumDash($url, array('http://', 'https://', '/', '.', '?', '&', '$', '@', ':'))) {
            throw new Exception('Specified URL contains invalid characters');
        }

        /**
         *  Check if a source repo with the same name does not already exist
         */
        if ($this->exists($type, $name) === true) {
            throw new Exception($name . ' source repository already exists');
        }

        /**
         *  If the method is manual, get the params template and rewrite it with the validated values
         */
        if ($method == 'manual') {
            /**
             *  Get params template for the specified type
             */
            $template = $this->template($type);

            /**
             *  Rewrite template with the validated values
             */
            $template['name'] = $name;
            $template['type'] = $type;
            $template['url'] = $url;
            $params = $template;
        }

        /**
         *  If the method is import, rewrite the params with the validated values
         */
        if ($method == 'import') {
            $params['name'] = $name;
            $params['type'] = $type;
            $params['url'] = $url;
        }

        /**
         *  Add source repo in database
         */
        $this->model->new(json_encode($params));
    }

    /**
     *  Edit a source repository
     */
    public function edit(int $id, array $params)
    {
        /**
         *  Check that source repo exists
         */
        if (!$this->model->existsId($id)) {
            throw new Exception('Source repository does not exist');
        }

        /**
         *  Check that source repo name is valid
         */
        if (empty($params['name'])) {
            throw new Exception('Source repository name is empty');
        }

        if (!\Controllers\Common::isAlphanumDash($params['name'])) {
            throw new Exception('Source repository name cannot contain special characters except hyphen and underscore');
        }

        /**
         *  Get source type
         */
        $type = $this->getType($id);

        /**
         *  Check that source repo name is not already used by another source repo
         */
        if ($this->exists($type, $params['name'])) {
            /**
             *  Retrieve the Id of the source repo with the same name
             */
            $testId = $this->getIdByTypeName($type, $params['name']);

            /**
             *  If the Id is different from the one we are editing, then the name is already used
             */
            if ($testId !== false and $testId != $id) {
                throw new Exception('<b>' . $params['name'] . '</b> source repository already exists');
            }
        }

        /**
         *  Format specified URL
         *  Delete spaces
         *  Delete anti-slash
         */
        $url = trim($params['url']);
        $url = stripslashes($url);

        /**
         *  Check that URL is valid
         *  Allow ? and & characters for query strings
         *  Allow $ character for variables (e.g $releasever)
         *  Allow @ and : character for basic authentification (e.g http://user:password@url)
         */
        if (!\Controllers\Common::isAlphanumDash($url, array('http://', 'https://', '/', '.', '?', '&', '$', '@', ':'))) {
            throw new Exception('specified URL contains invalid characters');
        }

        /**
         *  Check that URL starts with http(s)://
         */
        if (!preg_match('#^https?://#', $url)) {
            throw new Exception('specified URL must start with <b>http(s)://</b>');
        }

        /**
         *  SSL certificate file must be a file that exist and is readable
         */
        if (!empty($params['ssl-certificate-path'])) {
            if (!file_exists($params['ssl-certificate-path'])) {
                throw new Exception('Specified certificate file does not exist');
            }
            if (!is_readable($params['ssl-certificate-path'])) {
                throw new Exception('Specified certificate file is not readable');
            }
        }

        /**
         *  SSL private key file must be a file that exists and is readable
         */
        if (!empty($params['ssl-private-key-path'])) {
            if (!file_exists($params['ssl-private-key-path'])) {
                throw new Exception('Specified private key file does not exist');
            }
            if (!is_readable($params['ssl-private-key-path'])) {
                throw new Exception('Specified private key file is not readable');
            }
        }

        /**
         *  SSL CA certificate file must be a file that exists and is readable
         */
        if (!empty($params['ssl-ca-certificate-path'])) {
            if (!file_exists($params['ssl-ca-certificate-path'])) {
                throw new Exception('Specified CA certificate file does not exist');
            }
            if (!is_readable($params['ssl-ca-certificate-path'])) {
                throw new Exception('Specified CA certificate file is not readable');
            }
        }

        /**
         *  Get current source repo params
         */
        $currentParams = json_decode($this->getDefinition($id), true);

        /**
         *  Modify current params with new ones
         */
        $currentParams['name'] = $params['name'];
        $currentParams['type'] = $type;
        $currentParams['url'] = $url;
        $currentParams['ssl-authentication']['certificate-path'] = $params['ssl-certificate-path'];
        $currentParams['ssl-authentication']['private-key-path'] = $params['ssl-private-key-path'];
        $currentParams['ssl-authentication']['ca-certificate-path'] = $params['ssl-ca-certificate-path'];

        /**
         *  Edit source repo in database
         */
        $this->model->edit($id, json_encode($currentParams));
    }

    /**
     *  Delete a source repository
     */
    public function delete(int $id)
    {
        $this->model->delete($id);
    }

    /**
     *  Import list(s) of source repositories
     */
    public function import(array $listFiles)
    {
        $debSource = new \Controllers\Repo\Source\Deb();
        $rpmSource = new \Controllers\Repo\Source\Rpm();

        try {
            foreach ($listFiles as $sourceList) {
                $listFile = \Controllers\Common::validateData($sourceList);

                /**
                 *  Check that the list exists
                 */
                if (!file_exists(SOURCE_LISTS_DIR . '/' . $listFile . '.yml')) {
                    throw new Exception('specified list ' . $listFile . ' does not exist');
                }

                /**
                 *  Load the yaml file
                 */
                $yamlList = yaml_parse_file(SOURCE_LISTS_DIR . '/' . $listFile . '.yml');

                if ($yamlList === false) {
                    throw new Exception('error while reading list ' . $listFile);
                }

                /**
                 *  Check that the yaml file is not empty
                 */
                if (empty($yamlList)) {
                    throw new Exception('list ' . $listFile . ' is empty');
                }

                /**
                 *  For each source repository in the list, check that the name, URL and type are specified
                 *  Then import the complete source repository details
                 */
                foreach ($yamlList['repositories'] as $repo) {
                    if (empty($repo['type'])) {
                        throw new Exception('source repository type is empty');
                    }

                    if (!in_array($repo['type'], ['deb', 'rpm'])) {
                        throw new Exception('invalid source repository type');
                    }

                    // Case it is a deb source repository
                    if ($repo['type'] == 'deb') {
                        $debSource->import($repo);
                    }

                    // Case it is a rpm source repository
                    if ($repo['type'] == 'rpm') {
                        $rpmSource->import($repo);
                    }
                }
            }
        } catch (Exception $e) {
            throw new Exception('Could not import source repositories: ' . $e->getMessage());
        }
    }

    /**
     *  Check if source repo exists in database
     */
    public function exists(string $type, string $name)
    {
        return $this->model->exists($type, $name);
    }

    /**
     *  Check if source repo exists in database
     */
    public function existsId(string $id)
    {
        return $this->model->existsId($id);
    }

    /**
     *  Edit source repository definition params
     */
    public function editDefinition(int $id, string $definition)
    {
        $this->model->editDefinition($id, $definition);
    }

    /**
     *  Return the params template for the specified type
     */
    public function template(string $type)
    {
        if ($type == 'deb') {
            $template = [
                'name' => '',
                'type' => 'deb',
                'url' => '',
                'distributions' => [],
                'architectures' => [],
                'keyserver' => ''
            ];
        }

        if ($type == 'rpm') {
            $template = [
                'name' => '',
                'type' => 'rpm',
                'url' => '',
            ];
        }

        return $template;
    }
}
