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
     *  Return source repo URL
     */
    public function getUrl(string $sourceType, string $sourceName)
    {
        return $this->model->getUrl($sourceType, $sourceName);
    }

    /**
     *  Get GPG key URL of the specified source repo
     */
    public function getGpgKeyUrl(string $sourceType, string $sourceName)
    {
        return $this->model->getGpgKeyUrl($sourceType, $sourceName);
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
         *  Check that URL is valid and starts with http(s)://
         */
        if (!\Controllers\Common::isAlphanumDash($url, array('=', ':', '/', '.', '?', '$', '&', ','))) {
            throw new Exception('Source repo URL contains invalid characters');
        }
        if (!preg_match('#^https?://#', $url)) {
            throw new Exception('Source repo URL must start with <b>http(s)://</b>');
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
     *  Delete a source repo
     */
    public function delete(string $sourceId)
    {
        $this->model->delete($sourceId);
    }

    /**
     *  Rename a source repo
     */
    public function rename(string $type, string $name, string $newName)
    {
        if ($type != 'rpm' and $type != 'deb') {
            throw new Exception('Repo type is invalid');
        }

        $name = \Controllers\Common::validateData($name);
        $newName = \Controllers\Common::validateData($newName);

        /**
         *  Check that names does not contains invalid characters
         */
        if (\Controllers\Common::isAlphanumDash($name) === false) {
            throw new Exception('Repo name contains invalid characters');
        }
        if (\Controllers\Common::isAlphanumDash($newName) === false) {
            throw new Exception('Repo new name contains invalid characters');
        }

        /**
         *  New name must be different than the actual name
         */
        if ($name == $newName) {
            throw new Exception('You must specify a different name from the actual');
        }

        /**
         *  Check if a source repo with the same name already exists
         */
        if ($this->exists($type, $newName) === true) {
            throw new Exception("Source repo <b>$newName</b> already exists");
        }

        $this->model->rename($type, $name, $newName);
    }

    /**
     *  Edit source repo URL
     */
    public function editUrl(string $type, string $name, string $url)
    {
        $type = \Controllers\Common::validateData($type);
        $name = \Controllers\Common::validateData($name);

        /**
         *  Format URL
         */
        $url = trim($url);
        $url = stripslashes($url); // Remove anti-slash
        $url = strtolower($url);

        /**
         *  Check that URL is valid
         */
        if (\Controllers\Common::isAlphanumDash($url, array('http://', 'https://', '/', '.', '?', '&', '$')) === false) {
            throw new Exception("Specified URL contains invalid characters");
        }

        /**
         *  Check that URL starts with http(s)://
         */
        if (!preg_match('#^https?://#', $url)) {
            throw new Exception("Specified URL must start with http(s)://");
        }

        $this->model->editUrl($type, $name, $url);
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

        if ($myprocess->getExitCode() != 0) {
            throw new Exception('Error while importing specified GPG key');
        }

        $myprocess->close();

        /**
         *  Delete temp file
         */
        unlink($gpgTempFile);
    }

    /**
     *  Edit source repo GPG key URL
     */
    public function editGpgKey(string $sourceId, string $url = '')
    {
        /**
         *  Key URL can either be empty, either start with http(s)://
         */
        if (!empty($url) and !preg_match('#^https?://#', $url)) {
            throw new Exception('Specified URL must start with http(s)://');
        }

        $this->model->editGpgKey($sourceId, $url);
    }

    /**
     *  Edit source repo SSL certificate file path
     */
    public function editSslCertificatePath(string $sourceId, string $path = '')
    {
        /**
         *  SSL certificate file must be a file that exist and is readable
         */
        if (!empty($path)) {
            if (!file_exists($path)) {
                throw new Exception('Specified certificate file does not exist');
            }
            if (!is_readable($path)) {
                throw new Exception('Specified certificate file is not readable');
            }
        }

        $this->model->editSslCertificatePath($sourceId, $path);
    }

    /**
     *  Edit source repo SSL private key file path
     */
    public function editSslPrivateKeyPath(string $sourceId, string $path = '')
    {
        /**
         *  SSL private key file must be a file that exists and is readable
         */
        if (!empty($path)) {
            if (!file_exists($path)) {
                throw new Exception('Specified private key file does not exist');
            }
            if (!is_readable($path)) {
                throw new Exception('Specified private key file is not readable');
            }
        }

        $this->model->editSslPrivateKeyPath($sourceId, $path);
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
    public function exists(string $type, string $sourceName)
    {
        return $this->model->exists($type, $sourceName);
    }

    /**
     *  List all source repos
     */
    public function listAll(string $type = null)
    {
        return $this->model->listAll($type);
    }
}
