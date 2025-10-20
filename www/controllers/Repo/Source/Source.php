<?php

namespace Controllers\Repo\Source;

use Exception;
use JsonException;
use \Controllers\Utils\Validate;

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
     *  Get source repo definition from its Id
     */
    public function getDefinition(string $id)
    {
        return $this->model->getDefinition($id);
    }

    /**
     *  List all source repositories
     */
    public function listAll(string|null $type = null, bool $withOffset = false, int $offset = 0)
    {
        return $this->model->listAll($type, $withOffset, $offset);
    }

    /**
     *  Add a new source repository
     */
    public function new(string $method, array $params)
    {
        $gpgController = new \Controllers\Gpg();

        if (empty($params['name'])) {
            throw new Exception('Source repository name is empty');
        }
        if (empty($params['url'])) {
            throw new Exception('Source repository URL is empty');
        }
        if (empty($params['type'])) {
            throw new Exception('Source repository type is empty');
        }

        $name = Validate::string($params['name']);
        $type = Validate::string($params['type']);
        $url = trim($params['url']);
        $url = stripslashes($url);

        /**
         *  Check that source repo name is valid
         */
        if (!Validate::alphaNumericHyphen($name)) {
            throw new Exception('Source repository name cannot contain special characters except hyphen and underscore');
        }

        /**
         *  Check that type is valid
         */
        if (!in_array($type, ['deb', 'rpm'])) {
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
        if (!Validate::alphaNumericHyphen($url, ['http://', 'https://', '/', '.', '?', '&', '$', '@', ':'])) {
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
         *  If the method is import-X, rewrite the params with the validated values
         */
        if (preg_match('#^import-#', $method)) {
            $params['name'] = $name;
            $params['type'] = $type;
            $params['url'] = $url;
        }

        /**
         *  Import GPG keys if any
         *  Only if it is an import from a file
         */
        if (preg_match('#^import-#', $method)) {
            // Case it is a deb source repository
            if ($type == 'deb') {
                if (!empty($params['distributions'])) {
                    foreach ($params['distributions'] as $distributionId => $distribution) {
                        if (!empty($distribution['gpgkeys'])) {
                            $distributionGpgKeys = [];

                            foreach ($distribution['gpgkeys'] as $gpgKey) {
                                // Case the key is a fingerprint, the key has to be downloaded from a keyserver
                                if (!empty($gpgKey['fingerprint'])) {
                                    $fingerprints = $gpgController->importFromUrl('https://keyserver.ubuntu.com/pks/lookup?op=get&search=0x' . $gpgKey['fingerprint']);
                                }

                                // Case the key is a URL
                                if (!empty($gpgKey['link'])) {
                                    $fingerprints = $gpgController->importFromUrl($gpgKey['link']);
                                }

                                if (empty($fingerprints)) {
                                    throw new Exception('no fingerprints found');
                                }

                                /**
                                 *  Rewrite all the distribution gpg keys with the new ones
                                 */
                                foreach ($fingerprints as $fingerprint) {
                                    // Ignore fingerprint if already exists in $distributionGpgKeys[]
                                    foreach ($distributionGpgKeys as $gpgKeyDefinition) {
                                        if (isset($gpgKeyDefinition['fingerprint']) and $gpgKeyDefinition['fingerprint'] == $fingerprint) {
                                            continue 2;
                                        }
                                    }

                                    $distributionGpgKeys[] = array(
                                        'fingerprint' => $fingerprint
                                    );
                                }
                            }

                            /**
                             *  Rewrite the distribution gpg keys with the new ones
                             */
                            $params['distributions'][$distributionId]['gpgkeys'] = $distributionGpgKeys;
                        }
                    }
                }
            }

            // Case it is a rpm source repository
            if ($type == 'rpm') {
                if (!empty($params['releasever'])) {
                    foreach ($params['releasever'] as $releaseverId => $releasever) {
                        if (!empty($releasever['gpgkeys'])) {
                            $releaseverGpgKeys = [];

                            foreach ($releasever['gpgkeys'] as $gpgKey) {
                                // Case the key is a fingerprint, the key has to be downloaded from a keyserver
                                if (!empty($gpgKey['fingerprint'])) {
                                    $fingerprints = $gpgController->importFromUrl('https://keyserver.ubuntu.com/pks/lookup?op=get&search=0x' . $gpgKey['fingerprint']);
                                }

                                // Case the key is a URL, the key file has to be downloaded
                                if (!empty($gpgKey['link'])) {
                                    $fingerprints = $gpgController->importFromUrl($gpgKey['link']);
                                }

                                if (empty($fingerprints)) {
                                    throw new Exception('no fingerprints found');
                                }

                                /**
                                 *  Rewrite all the release version gpg keys with the new ones
                                 */
                                foreach ($fingerprints as $fingerprint) {
                                    // Ignore fingerprint if already exists in $releaseverGpgKeys[]
                                    foreach ($releaseverGpgKeys as $gpgKeyDefinition) {
                                        if (isset($gpgKeyDefinition['fingerprint']) and $gpgKeyDefinition['fingerprint'] == $fingerprint) {
                                            continue 2;
                                        }
                                    }

                                    $releaseverGpgKeys[] = array(
                                        'fingerprint' => $fingerprint
                                    );
                                }

                                /**
                                 *  Rewrite the releasever gpg keys with the new ones
                                 */
                                $params['releasever'][$releaseverId]['gpgkeys'] = $releaseverGpgKeys;
                            }
                        }
                    }
                }
            }
        }

        /**
         *  Add source repository in database
         */
        $this->model->new(json_encode($params), $method);
    }

    /**
     *  Edit a source repository
     */
    public function edit(int $id, array $params)
    {
        $description = '';
        $sslCertificate = '';
        $sslPrivateKey = '';
        $sslCaCertificate = '';

        /**
         *  Check that source repo exists
         */
        if (!$this->existsId($id)) {
            throw new Exception('Source repository does not exist');
        }

        /**
         *  Check that source repo name is valid
         */
        if (empty($params['name'])) {
            throw new Exception('Source repository name is empty');
        }

        if (!Validate::alphaNumericHyphen($params['name'])) {
            throw new Exception('Source repository name cannot contain special characters except hyphen and underscore');
        }

        /**
         *  Check that the type is valid
         */
        if (empty($params['type'])) {
            throw new Exception('Source repository type is empty');
        }

        if (!in_array($params['type'], ['deb', 'rpm'])) {
            throw new Exception('Invalid source repository type');
        }

        /**
         *  Check that source repo name is not already used by another source repo
         */
        if ($this->exists($params['type'], $params['name'])) {
            /**
             *  Retrieve the Id of the source repo with the same name
             */
            $testId = $this->getIdByTypeName($params['type'], $params['name']);

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
        if (!Validate::alphaNumericHyphen($url, ['http://', 'https://', '/', '.', '?', '&', '$', '@', ':'])) {
            throw new Exception('specified URL contains invalid characters');
        }

        /**
         *  Check that URL starts with http(s)://
         */
        if (!preg_match('#^https?://#', $url)) {
            throw new Exception('specified URL must start with <b>http(s)://</b>');
        }

        /**
         *  Check that non-compliant is valid
         */
        if (!empty($params['non-compliant']) and !in_array($params['non-compliant'], ['true', 'false'])) {
            throw new Exception('invalid non-compliant value');
        }

        if (!empty($params['description'])) {
            $description = Validate::string($params['description']);
        }

        /**
         *  SSL certificate file must be a file that exist and is readable
         */
        if (!empty($params['ssl-certificate'])) {
            $sslCertificate = Validate::string($params['ssl-certificate']);
        }

        /**
         *  SSL private key file must be a file that exists and is readable
         */
        if (!empty($params['ssl-private-key'])) {
            $sslPrivateKey = Validate::string($params['ssl-private-key']);
        }

        /**
         *  SSL CA certificate file must be a file that exists and is readable
         */
        if (!empty($params['ssl-ca-certificate'])) {
            $sslCaCertificate = Validate::string($params['ssl-ca-certificate']);
        }

        /**
         *  Get current source repo params
         */
        try {
            $currentParams = json_decode($this->getDefinition($id), true);
        } catch (JsonException $e) {
            throw new Exception('Could not decode source repository definition: ' . $e->getMessage());
        }

        /**
         *  Modify current params with new ones
         */
        $currentParams['name'] = $params['name'];
        $currentParams['type'] = $params['type'];
        $currentParams['url'] = $url;
        $currentParams['description'] = $description;
        $currentParams['ssl-authentication']['certificate'] = $sslCertificate;
        $currentParams['ssl-authentication']['private-key'] = $sslPrivateKey;
        $currentParams['ssl-authentication']['ca-certificate'] = $sslCaCertificate;
        // Additional param for deb source repos
        if ($params['type'] == 'deb') {
            $currentParams['non-compliant'] = $params['non-compliant'];
        }

        /**
         *  Edit source repo in database
         */
        $this->model->edit($id, json_encode($currentParams));
    }

    /**
     *  Delete a source repository
     */
    public function delete(array $sourcesId) : void
    {
        foreach ($sourcesId as $id) {
            // Check that source repo exists
            if (!$this->existsId($id)) {
                throw new Exception('Source repository with Id ' . $id . ' does not exist');
            }

            // Delete
            $this->model->delete($id);
        }
    }

    /**
     *  Import a YAML file from the API
     */
    public function importYamlFromApi($yamlFile)
    {
        try {
            /**
             *  Get file content
             */
            $content = file_get_contents($yamlFile);

            /**
             *  If an error occurred while reading the file content, throw an exception
             */
            if ($content === false) {
                throw new Exception('error while reading file content');
            }

            /**
             *  Import source repositories from the YAML content
             */
            $this->importYaml($content, 'import-api');
        } catch (Exception $e) {
            throw new Exception('Could not import source repositories: ' . $e->getMessage());
        }
    }

    /**
     *  Import source repositories from a YAML content
     */
    private function importYaml(string $content, string $importMethod)
    {
        /**
         *  Parse the YAML content
         */
        $yaml = yaml_parse($content);

        /**
         *  Ignore invalid YAML content
         */
        if ($yaml === false) {
            throw new Exception('error while reading list YAML content');
        }

        /**
         *  Check that the yaml file is not empty
         */
        if (empty($yaml)) {
            throw new Exception('YAML content is empty');
        }

        /**
         *  Check that main fields are specified
         */
        if (empty($yaml['repositories'])) {
            throw new Exception("'repositories' field is empty");
        }

        /**
         *  For each source repository in the list, check that the name, URL and type are specified
         *  Then import the complete source repository details
         */
        foreach ($yaml['repositories'] as $repo) {
            if (empty($repo['name'])) {
                throw new Exception('source repository name is empty');
            }
            if (empty($repo['url'])) {
                throw new Exception('source repository URL is empty');
            }
            if (empty($repo['type'])) {
                throw new Exception('source repository type is empty');
            }
            if (!in_array($repo['type'], ['deb', 'rpm'])) {
                throw new Exception('invalid source repository type');
            }

            /**
             *  Case it is a deb source repository
             */
            if ($repo['type'] == 'deb') {
                if (empty($repo['distributions'])) {
                    throw new Exception('source repository distributions is empty');
                }

                // Check that the components are specified
                foreach ($repo['distributions'] as $distribution) {
                    if (empty($distribution['components'])) {
                        throw new Exception('source repository distributions components is empty');
                    }
                }
            }

            /**
             *  Case it is a rpm source repository
             */
            if ($repo['type'] == 'rpm') {
                if (empty($repo['releasever'])) {
                    throw new Exception('source repository releasever is empty');
                }
            }

            /**
             *  If a repository with the same name already exists, then delete it before adding the new one
             */
            if ($this->exists($repo['type'], $repo['name'])) {
                /**
                 *  Get it's Id
                 */
                $id = $this->getIdByTypeName($repo['type'], $repo['name']);

                /**
                 *  Delete the existing source repository
                 */
                $this->delete([$id]);
            }

            /**
             *  Add the new source repository
             */
            $this->new($importMethod, $repo);
        }
    }

    /**
     *  Import list(s) of source repositories
     */
    public function import(array $lists)
    {
        try {
            foreach ($lists as $sourceList) {
                $listFile = Validate::string($sourceList);

                /**
                 *  If 'github/' string is found in the list name, then it is a default list
                 */
                if (preg_match('#^github/#', $listFile)) {
                    $listFile = str_replace('github/', '', $listFile);
                    $importMethod = 'import-github';

                    /**
                     *  Check that the list exists and load it
                     */
                    if (file_exists(DEFAULT_SOURCES_REPOS_LISTS_DIR . '/' . $listFile . '.yml') and is_readable(DEFAULT_SOURCES_REPOS_LISTS_DIR . '/' . $listFile . '.yml')) {
                        $content = file_get_contents(DEFAULT_SOURCES_REPOS_LISTS_DIR . '/' . $listFile . '.yml');
                    } else {
                        throw new Exception('specified list ' . basename($listFile) . '.yml not found');
                    }
                }

                /**
                 *  If 'custom/' string is found in the list name, then it is a custom list
                 */
                if (preg_match('#^custom/#', $listFile)) {
                    $listFile = str_replace('custom/', '', $listFile);
                    $importMethod = 'import-custom';

                    /**
                     *  Check that the list exists and load it
                     */
                    if (file_exists(CUSTOM_SOURCES_REPOS_LISTS_DIR . '/' . $listFile . '.yml') and is_readable(CUSTOM_SOURCES_REPOS_LISTS_DIR . '/' . $listFile . '.yml')) {
                        $content = file_get_contents(CUSTOM_SOURCES_REPOS_LISTS_DIR . '/' . $listFile . '.yml');
                    } else {
                        throw new Exception('specified list ' . basename($listFile) . '.yml not found');
                    }
                }

                if ($content === false) {
                    throw new Exception('error while reading list ' . $listFile);
                }

                $this->importYaml($content, $importMethod);
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
                'non-compliant' => 'false',
                'description' => '',
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
                'description' => '',
            ];
        }

        return $template;
    }
}
