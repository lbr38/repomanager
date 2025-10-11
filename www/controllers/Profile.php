<?php

namespace Controllers;

use Exception;
use Datetime;
use \Controllers\History\Save as History;

class Profile
{
    private $model;

    public function __construct()
    {
        $this->model = new \Models\Profile();
    }

    /**
     *  Return profile name by Id
     */
    public function getNameById(int $id)
    {
        return $this->model->getNameById($id);
    }

    /**
     *  Return profile Id by name
     */
    public function getIdByName(string $name)
    {
        return $this->model->getIdByName($name);
    }

    /**
     *  Return a list of all packages in profile_package table
     */
    public function getPackages()
    {
        return $this->model->getPackages();
    }

    /**
     *  Return a list of all services in profile_service table
     */
    public function getServices()
    {
        return $this->model->getServices();
    }

    /**
     *  Return server configuration for profiles management
     */
    public function getServerConfiguration()
    {
        return $this->model->getServerConfiguration();
    }

    /**
     *  Get profile full configuration from database
     */
    public function getProfileFullConfiguration(string $profile)
    {
        /**
         *  Check that profile exists
         */
        if ($this->exists($profile) === false) {
            throw new Exception($profile . ' profile does not exist');
        }

        /**
         *  Get profile Id from database
         */
        $profileId = $this->getIdByName($profile);

        /**
         *  Get profile full configuration
         */
        return $this->model->getProfileFullConfiguration($profileId);
    }

    /**
     *  Get profile configuration
     */
    public function getProfileConfiguration(string $profile)
    {
        $configuration = array();

        /**
         *  Get profile full configuration
         */
        $fullConfiguration = $this->getProfileFullConfiguration($profile);

        /**
         *  Return only main configuration
         *  For now we only return the profile name but we could return more in the future
         */
        $configuration['Name'] = $fullConfiguration['Name'];

        return $configuration;
    }

    /**
     *  Get profile packages excludes configuration
     */
    public function getProfilePackagesConfiguration(string $profile)
    {
        $configuration = array();

        /**
         *  Get profile full configuration
         */
        $fullConfiguration = $this->getProfileFullConfiguration($profile);

        /**
         *  Return only packages configuration
         */
        $configuration['Package_exclude'] = $fullConfiguration['Package_exclude'];
        $configuration['Package_exclude_major'] = $fullConfiguration['Package_exclude_major'];
        $configuration['Service_reload'] = $fullConfiguration['Service_reload'];
        $configuration['Service_restart'] = $fullConfiguration['Service_restart'];

        return $configuration;
    }

    /**
     *  Return an array containing repos members of a profile
     */
    public function getReposMembersList(string $profile)
    {
        /**
         *  First check that profile exists
         */
        if ($this->exists($profile) === false) {
            throw new Exception($profile . ' profile does not exist');
        }

        /**
         *  Retrieve profile Id from database
         */
        $profileId = $this->getIdByName($profile);

        /**
         *  Retrieve repos configuration
         */
        $repos = $this->model->getReposMembersList($profileId);

        /**
         *  Format repos members configuration files (.repo or .list) before sending to JSON format
         */
        $globalArray = array();

        foreach ($repos as $repo) {
            if ($repo['Package_type'] == 'rpm') {
                $repoArray = array(
                    // Legacy content
                    'filename' => REPO_CONF_FILES_PREFIX . $repo['Name'] . '.repo',
                    'description' => $repo['Name'] . ' repo on ' . __SERVER_URL__,
                    'content' => '[' . REPO_CONF_FILES_PREFIX . $repo['Name'] . '___ENV__]' . PHP_EOL . 'name=' . $repo['Name'] . ' repo on ' . WWW_HOSTNAME . PHP_EOL . 'baseurl=' . __SERVER_URL__ . '/repo/rpm/' . $repo['Name'] . '/' . $repo['Releasever'] . '/__ENV__' . PHP_EOL . 'enabled=1' . PHP_EOL . 'gpgkey=' . __SERVER_URL__ . '/repo/gpgkeys/' . WWW_HOSTNAME . '.pub' . PHP_EOL . 'gpgcheck=1',
                    // New content
                    'repo_server' => WWW_HOSTNAME,
                    'repo_name' => $repo['Name'],
                    'repo_releasever ' => $repo['Releasever'],
                    'repo_url' => __SERVER_URL__ . '/repo/rpm/' . $repo['Name'] . '/' . $repo['Releasever'] . '/__ENV__',
                    'gpgkey_url' => __SERVER_URL__ . '/repo/gpgkeys/' . WWW_HOSTNAME . '.pub',
                    'filename_prefix' => REPO_CONF_FILES_PREFIX
                );
            }

            if ($repo['Package_type'] == 'deb') {
                $repoArray = array(
                    // Legacy content
                    'filename' => REPO_CONF_FILES_PREFIX . $repo['Name'] . '.list',
                    'description' => $repo['Name'] . ' repo on ' . __SERVER_URL__,
                    'content' => 'deb ' . __SERVER_URL__ . '/repo/deb/' . $repo['Name'] . '/' . $repo['Dist'] . '/' . $repo['Section'] . '/__ENV__ ' . $repo['Dist'] . ' ' . $repo['Section'],
                    // New content
                    'repo_server' => WWW_HOSTNAME,
                    'repo_name' => $repo['Name'],
                    'repo_distribution' => $repo['Dist'],
                    'repo_component' => $repo['Section'],
                    'repo_url' => __SERVER_URL__ . '/repo/deb/' . $repo['Name'] . '/' . $repo['Dist'] . '/' . $repo['Section'] . '/__ENV__',
                    'gpgkey_url' => __SERVER_URL__ . '/repo/gpgkeys/' . WWW_HOSTNAME . '.pub',
                    'filename_prefix' => REPO_CONF_FILES_PREFIX
                );
            }

            $globalArray[] = $repoArray;
        }

        return $globalArray;
    }

    /**
     *  Return true if profile exists in database
     */
    public function exists(string $name)
    {
        return $this->model->exists($name);
    }

    /**
     *  Return true if profile Id exists in database
     */
    public function existsId(int $id)
    {
        return $this->model->existsId($id);
    }

    /**
     *  Return a list of all profiles names
     */
    public function listName()
    {
        return $this->model->listName();
    }

    /**
     *  Return a list of all profiles
     */
    public function list()
    {
        return $this->model->list();
    }

    /**
     *  Return the number of hosts using the specified profile
     */
    public function countHosts(string $profile)
    {
        return $this->model->countHosts($profile);
    }

    /**
     *  Create a new profile
     */
    public function new(string $name)
    {
        if (!IS_ADMIN) {
            throw new Exception('You are not allowed to perform this action');
        }

        $name = \Controllers\Common::validateData($name);

        /**
         *  Check that profile name does not contain forbidden characters
         */
        if (\Controllers\Common::isAlphanumDash($name) === false) {
            throw new Exception("<b>$name</b> profile contains invalid characters");
        }

        /**
         *  Check that profile does not already exist
         */
        if ($this->model->exists($name) === true) {
            throw new Exception("<b>$name</b> profile already exists");
        }

        $this->model->add($name);

        History::set('Create <code>' . $name . '</code> host profile');
    }

    /**
     *  Duplicate a profile and its configuration
     */
    public function duplicate(string $id)
    {
        if (!IS_ADMIN) {
            throw new Exception('You are not allowed to perform this action');
        }

        /**
         *  Check that profile exists
         */
        if (!$this->existsId($id)) {
            throw new Exception('Profile does not exist');
        }

        /**
         *  Retrieve profile name
         */
        $name = $this->model->getNameById($id);

        /**
         *  Generate a new profile name based on the duplicated profile name + a random number
         */
        $newName = $name . '-' . mt_rand(100000, 200000);

        /**
         *  Check that new profile name does not already exist
         */
        while ($this->model->exists($newName) === true) {
            /**
             *  Re-generate a new name if the previous one already exists
             */
            $newName = $name . '-' . mt_rand(100000, 200000);
        }

        /**
         *  Create new profile in database
         */
        $this->model->add($newName);

        /**
         *  Retrive new profile Id
         */
        $newProfileId = $this->model->getLastInsertRowID();

        /**
         *  Retrieve source profile configuration
         */
        $profileConf = $this->model->getProfileFullConfiguration($id);

        /**
         *  Copy source profile configuration to new profile
         */
        $this->model->configure($newProfileId, $newName, $profileConf['Package_exclude'], $profileConf['Package_exclude_major'], $profileConf['Service_reload'], $profileConf['Service_restart'], $profileConf['Notes']);

        /**
         *  Retrieve source profile repos members
         */
        $reposMembersId = $this->reposMembersIdList($id);

        /**
         *  Add each repo to new profile
         */
        foreach ($reposMembersId as $repoMemberId) {
            $this->model->addRepoToProfile($newProfileId, $repoMemberId);
        }

        History::set('Duplicate host profile <code>' . $name . '</code> to <code>' . $newName . '</code>');
    }

    /**
     *  Delete a profile
     */
    public function delete(array $profilesId) : void
    {
        if (!IS_ADMIN) {
            throw new Exception('You are not allowed to perform this action');
        }

        foreach ($profilesId as $id) {
            // Check that profile Id exists in database
            if (!$this->existsId($id)) {
                throw new Exception('Profile with id #' . $id . ' does not exist');
            }

            // Retrieve profile name for history
            $name = $this->model->getNameById($id);

            // Delete
            $this->model->delete($id);

            History::set('Delete <code>' . $name . '</code> host profile');
        }
    }

    /**
     *  Configure profile
     */
    public function configure(int $id, string $name, array $reposIds, array $packagesExcluded, array $packagesMajorExcluded, array $serviceNeedReload, array $serviceNeedRestart, string $notes)
    {
        if (!IS_ADMIN) {
            throw new Exception('You are not allowed to perform this action');
        }

        $myHost = new \Controllers\Host();
        $myHostRequest = new \Controllers\Host\Request();
        $error = 0;
        $name = \Controllers\Common::validateData($name);

        /**
         *  Check that profile name does not contain forbidden characters
         */
        if (\Controllers\Common::isAlphanumDash($name) === false) {
            throw new Exception($name . ' profile name contains invalid characters');
        }

        /**
         *  Check that profile exists
         */
        if ($this->existsId($id) === false) {
            throw new Exception($name . ' profile does not exist');
        }

        /**
         *  Retrieve actual profile name from its Id
         */
        $actualName = $this->model->getNameById($id);

        /**
         *  If the name is being changed then we check that the new name does not already exist
         */
        if ($name != $actualName) {
            if ($this->model->exists($name) === true) {
                throw new Exception($name . ' profile already exists');
            }
        }

        /**
         *  First we clean all profile repos members before adding the new ones
         */
        $this->model->cleanProfileRepoMembers($id);

        /**
         *  If $reposIds array is empty then we stop here, the profile will remain without any repo configured. Else we continue.
         */
        if (!empty($reposIds)) {
            /**
             *  Add each repo Id to profile_repo_members table
             */
            foreach ($reposIds as $repoId) {
                $this->model->addRepoToProfile($id, $repoId);
            }
        }

        /**
         *  Manage packages excludes and other parameters...
         *
         *  For each parameter below,
         *  If not empty then we implode the array into a string with each values separated by a comma because this is how they will be stored in database
         *  If empty then we set an empty value
         */

        /**
         *  Packages to exclude on major version update
         */
        if (!empty($packagesMajorExcluded)) {
            foreach ($packagesMajorExcluded as $packageName) {
                $packageName = \Controllers\Common::validateData($packageName);

                if (!\Controllers\Common::isAlphanumDash($packageName, array('.*'))) {
                    throw new Exception('Package ' . $packageName . ' contains invalid characters');
                }

                /**
                 *  For each package, we check its syntax then we add it to database if it does not already exist
                 *  If package contains a wildcard .* then we remove it before testing
                 */
                if (substr($packageName, -2) == ".*") {
                    $packageNameFormatted = rtrim($packageName, ".*");
                } else {
                    $packageNameFormatted = $packageName;
                }

                /**
                 *  Add package to profile_package table if it does not already exist
                 */
                $this->model->addPackage($packageNameFormatted);
            }
        }

        /**
         *  Packages to exclude
         */
        if (!empty($packagesExcluded)) {
            foreach ($packagesExcluded as $packageName) {
                $packageName = \Controllers\Common::validateData($packageName);

                if (!\Controllers\Common::isAlphanumDash($packageName, array('.*'))) {
                    throw new Exception('Package ' . $packageName . ' contains invalid characters');
                }

                /**
                 *  For each package, we check its syntax then we add it to database if it does not already exist
                 *  If package contains a wildcard .* then we remove it before testing
                 */
                if (substr($packageName, -2) == ".*") {
                    $packageNameFormatted = rtrim($packageName, ".*");
                } else {
                    $packageNameFormatted = $packageName;
                }

                /**
                 *  Add package to profile_package table if it does not already exist
                 */
                $this->model->addPackage($packageNameFormatted);
            }
        }

        /**
         *  Services to restart
         */
        if (!empty($serviceNeedRestart)) {
            foreach ($serviceNeedRestart as $serviceName) {
                $serviceName = \Controllers\Common::validateData($serviceName);

                /**
                 *  On vérifie que le nom du service ne contient pas de caractères interdits
                 */
                if (!\Controllers\Common::isAlphanumDash($serviceName, array('@', ':', '.*'))) {
                    throw new Exception('Service ' . $serviceName . ' contains invalid characters');
                }

                /**
                 *  Add service to profile_service table if it does not already exist
                 *  But only if it does not contain a conditionnal restart with a ':' character
                 */
                if (strpos($serviceName, ':') === false) {
                    $this->model->addService($serviceName);
                }
            }
        }

        /**
         *  If all checks are passed then we can insert data into database
         *  Implode all arrays into a string with each values separated by a comma
         */
        $packagesExcludedExploded = implode(',', $packagesExcluded);
        $packagesMajorExcludedExploded = implode(',', $packagesMajorExcluded);
        $serviceNeedReloadExploded = implode(',', $serviceNeedReload);
        $serviceNeedRestartExploded = implode(',', $serviceNeedRestart);

        /**
         *  Check notes
         */
        if (!empty($notes)) {
            $notes = \Controllers\Common::validateData($notes);
        }

        /**
         *  Insert new configuration into database
         */
        $this->model->configure($id, $name, $packagesExcludedExploded, $packagesMajorExcludedExploded, $serviceNeedReloadExploded, $serviceNeedRestartExploded, $notes);

        /**
         *  Get all hosts using this profile
         */
        $hosts = $myHost->getHostWithProfile($name);

        /**
         *  For each host, add a new request to apply the new profile configuration
         */
        foreach ($hosts as $host) {
            $myHostRequest->new($host['Id'], 'update-profile');
        }

        History::set('<code>' . $name . '</code> host profile configuration edited');
    }

    /**
     *  Return an array containing repos Id members of a profile
     */
    public function reposMembersIdList(string $profileId)
    {
        return $this->model->reposMembersIdList($profileId);
    }

    /**
     *  Remove unused repos from profiles (repos that have no active snapshot)
     */
    public function cleanProfiles()
    {
        /**
         *  Get unused repos Id (repos that have no active snapshot and so are not visible from web UI)
         */
        $myrepo = new \Controllers\Repo\Repo();
        $unusedRepos = $myrepo->getUnused();

        /**
         *  Remove those repos Id from profiles
         */
        if (!empty($unusedRepos)) {
            foreach ($unusedRepos as $unusedRepo) {
                $this->removeRepoMemberId($unusedRepo['Id']);
            }
        }

        unset($myrepo);
    }

    /**
     *  Remove repo Id from profile members
     */
    public function removeRepoMemberId(int $id)
    {
        $this->model->removeRepoMemberId($id);
    }
}
