<?php

namespace Controllers\Repo\Source;

use Exception;

class Rpm extends \Controllers\Repo\Source\Source
{
    /**
     *  Import a rpm source repository
     */
    public function import(array $repo)
    {
        /**
         *  Throw error if some informations are missing
         */
        if (empty($repo['name'])) {
            throw new Exception('source repository name is empty');
        }
        if (empty($repo['url'])) {
            throw new Exception('source repository URL is empty');
        }
        // if (empty($repo['architectures'])) {
        //     throw new Exception('source repository architectures is empty');
        // }
        if (empty($repo['releasever'])) {
            throw new Exception('source repository releasever is empty');
        }

        /**
         *  If a repository with the same name already exists, then delete it before adding the new one
         */
        if ($this->exists('rpm', $repo['name'])) {
            /**
             *  Get it's Id
             */
            $id = $this->getIdByTypeName('rpm', $repo['name']);

            /**
             *  Delete the existing source repository
             */
            $this->delete($id);
        }

        /**
         *  Add the new source repository
         */
        $this->new('import', $repo);
    }

    /**
     *  Add a new rpm source repository release version
     */
    public function addReleasever(int $id, string $name)
    {
        $name = \Controllers\Common::validateData($name);

        /**
         *  Check that the source repository exists
         */
        if (!$this->existsId($id)) {
            throw new Exception('Source repository does not exist');
        }

        /**
         *  Get complete source repository definition
         */
        $currentParams = json_decode($this->getDefinition($id), true);

        /**
         *  Check that a release version with the same name does not already exist
         */
        foreach ($currentParams['releasever'] as $releasever) {
            if ($releasever['name'] == $name) {
                throw new Exception('Release version ' . $name . ' already exists');
            }
        }

        /**
         *  Add the new release version
         */
        $currentParams['releasever'][] = array(
            'name' => $name,
            'description' => '',
        );

        /**
         *  Save the new source repository definition
         */
        $this->editDefinition($id, json_encode($currentParams));
    }

    /**
     *  Edit a deb source repository release version
     */
    public function editReleasever(int $id, int $releaseverId, array $params)
    {
        /**
         *  Check that the source repository exists
         */
        if (!$this->existsId($id)) {
            throw new Exception('Source repository does not exist');
        }

        /**
         *  Get complete source repository definition
         */
        $currentDefinition = json_decode($this->getDefinition($id), true);

        /**
         *  Check that release version Id exists in the source repository
         */
        if (!isset($currentDefinition['releasever'][$releaseverId])) {
            throw new Exception('Release version does not exist');
        }

        /**
         *  Set new release version params
         */
        $currentDefinition['releasever'][$releaseverId]['name'] = $params['name'];
        $currentDefinition['releasever'][$releaseverId]['description'] = $params['description'];

        /**
         *  Save the new source repository definition
         */
        $this->editDefinition($id, json_encode($currentDefinition));
    }

    /**
     *  Remove a release version from a rpm source repository
     */
    public function removeReleasever(int $sourceId, int $releaseverId)
    {
        /**
         *  Check that the source repository exists
         */
        if (!$this->existsId($sourceId)) {
            throw new Exception('Source repository does not exist');
        }

        /**
         *  Get complete source repository definition
         */
        $currentDefinition = json_decode($this->getDefinition($sourceId), true);

        /**
         *  Check that release version Id exists in the source repository
         */
        if (!isset($currentDefinition['releasever'][$releaseverId])) {
            throw new Exception('Release version does not exist');
        }

        /**
         *  Remove the release version
         */
        unset($currentDefinition['releasever'][$releaseverId]);

        /**
         *  Save the new source repository definition
         */
        $this->editDefinition($sourceId, json_encode($currentDefinition));
    }

    /**
     *  Add a gpg key from a deb source repository release version
     */
    public function addGpgKey(int $id, int $releaseverId, string $gpgKey)
    {
        $gpgController = new \Controllers\Gpg();
        $gpgKey = \Controllers\Common::validateData($gpgKey);

        /**
         *  If gpg key starts with http(s):// then it is a link
         *  Otherwise it is a fingerprint
         */
        if (preg_match('#^http(s)?://#', $gpgKey)) {
            $type = 'link';
        } else {
            $type = 'fingerprint';
        }

        /**
         *  Check that the source repository exists
         */
        if (!$this->existsId($id)) {
            throw new Exception('Source repository does not exist');
        }

        /**
         *  Get complete source repository definition
         */
        $currentParams = json_decode($this->getDefinition($id), true);

        /**
         *  Check that release version Id exists in the source repository
         */
        if (!isset($currentParams['releasever'][$releaseverId])) {
            throw new Exception('Release version Id ' . $releaseverId . ' does not exist');
        }

        /**
         *  Import GPG key
         */

        // Case it is a link
        if ($type == 'link') {
            // Import the key and get all the fingerprints GPG has found in it
            $fingerprints = $gpgController->importFromUrl($gpgKey);
        }

        // Case it is a fingerprint
        if ($type == 'fingerprint') {
            // Check if the fingerprint already exists
            foreach ($currentParams['releasever'][$releaseverId]['gpgkeys'] as $gpgKeyDefinition) {
                if (isset($gpgKeyDefinition['fingerprint']) and $gpgKeyDefinition['fingerprint'] == $gpgKey) {
                    throw new Exception('GPG key ' . $gpgKey . ' already exists');
                }
            }

            // Import the key and get all the fingerprints GPG has found in it
            $fingerprints = $gpgController->importFromUrl('https://keyserver.ubuntu.com/pks/lookup?op=get&search=0x' . $gpgKey);
        }

        if (empty($fingerprints)) {
            throw new Exception('no fingerprints found in the GPG key' . $gpgKey);
        }

        /**
         *  Add the new gpg key
         *  Instead of adding the provided gpg key, we add all the fingerprints found in the key
         */
        foreach ($fingerprints as $fingerprint) {
            // Ignore fingerprint if already exists
            foreach ($currentParams['releasever'][$releaseverId]['gpgkeys'] as $gpgKeyDefinition) {
                if (isset($gpgKeyDefinition['fingerprint']) and $gpgKeyDefinition['fingerprint'] == $fingerprint) {
                    continue 2;
                }
            }

            $currentParams['releasever'][$releaseverId]['gpgkeys'][] = array(
                'fingerprint' => $fingerprint
            );
        }

        /**
         *  Save the new source repository definition
         */
        $this->editDefinition($id, json_encode($currentParams));
    }

    /**
     *  Remove a gpg key from a rpm source repository release version
     */
    public function removeGpgKey(int $id, int $releaseverId, int $gpgKeyId)
    {
        /**
         *  Check that the source repository exists
         */
        if (!$this->existsId($id)) {
            throw new Exception('Source repository does not exist');
        }

        /**
         *  Get complete source repository definition
         */
        $currentParams = json_decode($this->getDefinition($id), true);

        /**
         *  Check that release version Id exists in the source repository
         */
        if (!isset($currentParams['releasever'][$releaseverId])) {
            throw new Exception('Release version Id ' . $releaseverId . ' does not exist');
        }

        /**
         *  Check that the gpg key exists in the release version
         */
        if (!isset($currentParams['releasever'][$releaseverId]['gpgkeys'][$gpgKeyId])) {
            throw new Exception('GPG key Id does not exist');
        }

        /**
         *  Remove the gpg key
         */
        unset($currentParams['releasever'][$releaseverId]['gpgkeys'][$gpgKeyId]);

        /**
         *  Save the new source repository definition
         */
        $this->editDefinition($id, json_encode($currentParams));
    }
}
