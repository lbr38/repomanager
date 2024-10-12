<?php

namespace Controllers;

use Exception;

class Source
{
    private $model;

    public function __construct()
    {
        $this->model = new \Models\Source();
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

    public function getType(string $id)
    {
        return $this->model->getType($id);
    }

    /**
     *  Import list(s) of source repositories
     */
    public function import(array $listFiles)
    {
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

                    /**
                     *  Throw error if some informations are missing
                     */
                    if (empty($repo['name'])) {
                        throw new Exception('source repository name is empty');
                    }
                    if (empty($repo['url'])) {
                        throw new Exception('source repository URL is empty');
                    }
                    if (empty($repo['type'])) {
                        throw new Exception('source repository type is empty');
                    }
                    if (empty($repo['architectures'])) {
                        throw new Exception('source repository architectures is empty');
                    }

                    if ($repo['type'] == 'deb') {
                        if (empty($repo['distributions'])) {
                            throw new Exception('source repository distributions is empty');
                        }
                        if (empty($repo['components'])) {
                            throw new Exception('source repository components is empty');
                        }
                    }

                    /**
                     *  If a repository with the same name already exists, then update it
                     *  Otherwise, add it
                     */
                    if ($this->exists($repo['type'], $repo['name'])) {
                        /**
                         *  If the repository already exists, then update it
                         *  First get it's Id
                         */
                        $id = $this->getIdByName($repo['type'], $repo['name']);

                        /**
                         *  Edit the source repository
                         */
                        $this->edit($id, $repo['name'], $repo['url']);
                    } else {
                        /**
                         *  Add the new source repository
                         */
                        $this->new($repo['type'], $repo['name'], $repo['url']);
                    }
                }
            }
        } catch (Exception $e) {
            throw new Exception('Could not import source repositories: ' . $e->getMessage());
        }
    }

    /**
     *  Add a new source repo
     */
    public function new(string $type, string $name, string $url, string $gpgKeyURL = null, string $gpgKeyText = null)
    {
        $name = \Controllers\Common::validateData($name);

        /**
         *  Check that source repo name is valid
         */
        if (!\Controllers\Common::isAlphanumDash($name)) {
            throw new Exception('Source repo name cannot contain special characters except hyphen and underscore');
        }

        /**
         *  Format specified URL
         *  Delete spaces
         *  Delete anti-slash
         */
        $url = trim($url);
        $url = stripslashes($url);

        /**
         *  Check that URL is valid
         *  Allow ? and & characters for query strings
         *  Allow $ character for variables (e.g $releasever)
         *  Allow @ and : character for basic authentification (e.g http://user:password@url)
         */
        if (!\Controllers\Common::isAlphanumDash($url, array('http://', 'https://', '/', '.', '?', '&', '$', '@', ':'))) {
            throw new Exception('Specified URL contains invalid characters');
        }

        /**
         *  Check that URL starts with http(s)://
         */
        if (!preg_match('#^https?://#', $url)) {
            throw new Exception('Specified URL must start with <b>http(s)://</b>');
        }

        /**
         *  Check if a source repo with the same name does not already exist
         */
        if ($this->exists($type, $name) === true) {
            throw new Exception("A source repo <b>$name</b> already exists");
        }

        /**
         *  If a ASCII gpg key is specified then import it
         */
        if (!empty($gpgKeyText)) {
            $this->importGpgKey($gpgKeyText);
        }

        /**
         *  If an URL to a GPG key has been specified
         */
        if (!empty($gpgKeyURL)) {
            /**
             *  Check that the URL is valid
             */
            if (!preg_match('#^https?://#', $gpgKeyURL)) {
                throw new Exception('GPG key URL must start with <b>http(s)://</b>');
            }
        }

        /**
         *  Add source repo in database
         */
        $this->model->new($type, $name, $url, $gpgKeyURL, $gpgKeyText);
    }

    /**
     *  Edit a source repo
     */
    public function edit(int $id, string $name, string $url, string $gpgKeyURL = null, string $sslCertificatePath = null, string $sslPrivateKeyPath = null, string $sslCaCertificatePath = null)
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
        if (!\Controllers\Common::isAlphanumDash($name)) {
            throw new Exception('Source repository name cannot contain special characters except hyphen and underscore');
        }

        /**
         *  Get source type
         */
        $type = $this->getType($id);

        /**
         *  Check that source repo name is not already used by another source repo
         */
        if ($this->exists($type, $name)) {
            /**
             *  Retrieve the Id of the source repo with the same name
             */
            $testId = $this->getIdByName($type, $name);

            /**
             *  If the Id is different from the one we are editing, then the name is already used
             */
            if ($testId !== false and $testId != $id) {
                throw new Exception('<b>' . $name . '</b> source repository already exists');
            }
        }

        /**
         *  Format specified URL
         *  Delete spaces
         *  Delete anti-slash
         */
        $url = trim($url);
        $url = stripslashes($url);

        /**
         *  Check that URL is valid
         *  Allow ? and & characters for query strings
         *  Allow $ character for variables (e.g $releasever)
         *  Allow @ and : character for basic authentification (e.g http://user:password@url)
         */
        if (!\Controllers\Common::isAlphanumDash($url, array('http://', 'https://', '/', '.', '?', '&', '$', '@', ':'))) {
            throw new Exception('Specified URL contains invalid characters');
        }

        /**
         *  Check that URL starts with http(s)://
         */
        if (!preg_match('#^https?://#', $url)) {
            throw new Exception('Specified URL must start with <b>http(s)://</b>');
        }

        /**
         *  GPG key URL can either be empty, either start with http(s)://
         */
        if (!empty($gpgKeyURL) and !preg_match('#^https?://#', $gpgKeyURL)) {
            throw new Exception('GPG signing key URL must start with http(s)://');
        }

        /**
         *  SSL certificate file must be a file that exist and is readable
         */
        if (!empty($sslCertificatePath)) {
            if (!file_exists($sslCertificatePath)) {
                throw new Exception('Specified certificate file does not exist');
            }
            if (!is_readable($sslCertificatePath)) {
                throw new Exception('Specified certificate file is not readable');
            }
        }

        /**
         *  SSL private key file must be a file that exists and is readable
         */
        if (!empty($sslPrivateKeyPath)) {
            if (!file_exists($sslPrivateKeyPath)) {
                throw new Exception('Specified private key file does not exist');
            }
            if (!is_readable($sslPrivateKeyPath)) {
                throw new Exception('Specified private key file is not readable');
            }
        }

        /**
         *  SSL CA certificate file must be a file that exists and is readable
         */
        if (!empty($sslCaCertificatePath)) {
            if (!file_exists($sslCaCertificatePath)) {
                throw new Exception('Specified CA certificate file does not exist');
            }
            if (!is_readable($sslCaCertificatePath)) {
                throw new Exception('Specified CA certificate file is not readable');
            }
        }

        $this->model->edit($id, $name, $url, $gpgKeyURL, $sslCertificatePath, $sslPrivateKeyPath, $sslCaCertificatePath);
    }

    /**
     *  Delete a source repo
     */
    public function delete(string $sourceId)
    {
        $this->model->delete($sourceId);
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
}
