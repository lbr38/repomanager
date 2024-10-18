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
     *  Return all source informations
     */
    public function getAll(string $sourceType, string $sourceName)
    {
        return $this->model->getAll($sourceType, $sourceName);
    }

    /**
     *  Get source repo Id from its name
     */
    public function getIdByName(string $type, string $name)
    {
        return $this->model->getIdByName($type, $name);
    }

    /**
     *  Get source repo type from its Id
     */
    public function getType(string $id)
    {
        return $this->model->getType($id);
    }

    /**
     *  Get source repo details from its Id
     */
    public function getDetails(string $id)
    {
        return $this->model->getDetails($id);
    }

    /**
     *  Add a new source repository
     */
    public function new(array $params)
    {
        if (empty($params['name'])) {
            throw new Exception('Source repository name is empty');
        }
        if (empty($params['url'])) {
            throw new Exception('Source repository URL is empty');
        }
        if (empty($params['type'])) {
            throw new Exception('Source repository type is empty');
        }

        $type = \Controllers\Common::validateData($params['type']);
        $name = \Controllers\Common::validateData($params['name']);

        /**
         *  Format specified URL
         *  Delete spaces
         *  Delete anti-slash
         */
        $url = trim($params['url']);
        $url = stripslashes($url);

        /**
         *  Check that type is valid
         */
        if (!in_array($type, ['deb', 'rpm'])) {
            throw new Exception('Invalid source repository type');
        }

        /**
         *  Check that source repo name is valid
         */
        if (!\Controllers\Common::isAlphanumDash($name)) {
            throw new Exception('Source repository name cannot contain special characters except hyphen and underscore');
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
         *  Rewrite params with the validated values
         */
        $params['name'] = $name;
        $params['url'] = $url;
        $params = json_encode($params);

        /**
         *  Add source repo in database
         */
        $this->model->new($type, $name, $params);
    }

    

    

    /**
     *  Edit a source repo
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
            $testId = $this->getIdByName($type, $params['name']);

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
         *  GPG key URL can either be empty, either start with http(s)://
         */
        // if (!empty($gpgKeyURL) and !preg_match('#^https?://#', $gpgKeyURL)) {
        //     throw new Exception('GPG signing key URL must start with http(s)://');
        // }

        // /**
        //  *  SSL certificate file must be a file that exist and is readable
        //  */
        // if (!empty($sslCertificatePath)) {
        //     if (!file_exists($sslCertificatePath)) {
        //         throw new Exception('Specified certificate file does not exist');
        //     }
        //     if (!is_readable($sslCertificatePath)) {
        //         throw new Exception('Specified certificate file is not readable');
        //     }
        // }

        // /**
        //  *  SSL private key file must be a file that exists and is readable
        //  */
        // if (!empty($sslPrivateKeyPath)) {
        //     if (!file_exists($sslPrivateKeyPath)) {
        //         throw new Exception('Specified private key file does not exist');
        //     }
        //     if (!is_readable($sslPrivateKeyPath)) {
        //         throw new Exception('Specified private key file is not readable');
        //     }
        // }

        // /**
        //  *  SSL CA certificate file must be a file that exists and is readable
        //  */
        // if (!empty($sslCaCertificatePath)) {
        //     if (!file_exists($sslCaCertificatePath)) {
        //         throw new Exception('Specified CA certificate file does not exist');
        //     }
        //     if (!is_readable($sslCaCertificatePath)) {
        //         throw new Exception('Specified CA certificate file is not readable');
        //     }
        // }

        /**
         *  Get current source repo params
         */
        $currentParams = json_decode($this->getDetails($id), true);

        /**
         *  Modify current params with new ones
         */
        $currentParams['name'] = $params['name'];
        $currentParams['url'] = $url;

        /**
         *  Convert the array to a JSON string
         */
        $newParams = json_encode($currentParams);

        /**
         *  Edit source repo in database
         */
        $this->model->edit($id, $params['name'], $newParams);
    }

    /**
     *  Delete a source repository
     */
    public function delete(string $id)
    {
        $this->model->delete($id);
    }

    /**
     *  Import list(s) of source repositories
     */
    public function import(array $listFiles)
    {
        $debSource = new \Controllers\Repo\Source\Deb();

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
                $lists = yaml_parse_file(SOURCE_LISTS_DIR . '/' . $listFile . '.yml');

                if ($lists === false) {
                    throw new Exception('error while reading list ' . $listFile);
                }

                /**
                 *  Check that the yaml file is not empty
                 */
                if (empty($lists)) {
                    throw new Exception('list ' . $listFile . ' is empty');
                }

                foreach ($lists as $repo) {
                    # TODO debug
                    file_put_contents(ROOT . '/toto', print_r($repo, true));

                    # TODO debug
                    $type = 'deb';
                
                    // if (empty($repo['type'])) {
                    //     throw new Exception('source repository type is empty');
                    // }

                    if ($type == 'deb') {
                        $debSource->import($repo);
                    }
                    if ($type == 'rpm') {
                        $rpmSource->import($repo);
                    }
                }
            }
        } catch (Exception $e) {
            throw new Exception('Could not import source repositories: ' . $e->getMessage());
        }
    }

    /**
     *  Import a new GPG key
     */
    public function importGpgKey(string $gpgKey)
    {
        $gpgKey = \Controllers\Common::validateData($gpgKey);
        $gpgKey = trim($gpgKey);

        /**
         *  Check if the ASCII text contains invalid characters
         */
        if (!\Controllers\Common::isAlphanum($gpgKey, array('-', '=', '+', '/', ' ', ':', '.', '(', ')', "\n", "\r"))) {
            throw new Exception('ASCII GPG key contains invalid characters');
        }

        /**
         *  Quit if the user tries to import a file on the system
         */
        if (file_exists($gpgKey)) {
            throw new Exception('GPG key must be specified in ASCII text format');
        }

        /**
         *  Create a temporary file with the ASCII text
         */
        $gpgTempFile = TEMP_DIR . '/repomanager-newgpgkey.tmp';
        file_put_contents($gpgTempFile, $gpgKey);

        /**
         *  Import file into the repomanager trusted keyring
         */
        $myprocess = new \Controllers\Process('/usr/bin/gpg --no-default-keyring --keyring ' . GPGHOME . '/trustedkeys.gpg --import ' . $gpgTempFile);
        $myprocess->execute();

        /**
         *  Delete temp file
         */
        unlink($gpgTempFile);

        if ($myprocess->getExitCode() != 0) {
            throw new Exception('Error while importing specified GPG key: <br>' . $myprocess->getOutput());
        }

        $myprocess->close();
    }

    /**
     *  Delete a GPG key from Repomanager's trusted keyring
     */
    public function deleteGpgKey(string $gpgKeyId)
    {
        $gpgKeyId = \Controllers\Common::validateData($gpgKeyId);

        /**
         *  Deleting key from the keyring, using its ID
         */
        $myprocess = new \Controllers\Process('gpg --no-default-keyring --homedir ' . GPGHOME . ' --keyring ' . GPGHOME . '/trustedkeys.gpg --no-greeting --delete-key --batch --yes ' . $gpgKeyId);
        $myprocess->execute();

        if ($myprocess->getExitCode() != 0) {
            throw new Exception("Error while deleting GPG key <b>$gpgKeyId</b>");
        }

        $myprocess->close();
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
     *  List all source repos
     */
    public function listAll(string $type = null, bool $withOffset = false, int $offset = 0)
    {
        return $this->model->listAll($type, $withOffset, $offset);
    }

    public function editDistribution(int $id, string $distribution, array $params)
    {
        $this->model->editDistribution($id, $distribution, $newDetails);
    }
}
