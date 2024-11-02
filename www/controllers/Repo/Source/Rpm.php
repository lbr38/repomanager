<?php

namespace Controllers\Repo\Source;

use Exception;

class Rpm extends \Controllers\Repo\Source\Source
{
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
    public function addGpgKey(int $id, int $releaseverId, string $gpgKeyUrl, string $gpgKeyFingerprint, string $gpgKeyPlainText)
    {
        $gpgController = new \Controllers\Gpg();

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
         *  Import the gpg key and get the fingerprints
         */
        $fingerprints = $gpgController->import($gpgKeyUrl, $gpgKeyFingerprint, $gpgKeyPlainText);

        /**
         *  Add the new gpg key to the release version
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

    /**
     *  Return an array of predefined release versions for a source repository
     *  The array is used in the frontend to populate the release versions select box
     */
    public function getPredefinedReleasever(string $source)
    {
        $data = [];
        $predefinedReleaseVersions = [];
        $possibleReleaseVersions = [];
        $source = \Controllers\Common::validateData($source);

        /**
         *  Check if source is valid
         */
        if (empty($source)) {
            throw new Exception('Source is required');
        }

        /**
         *  Check if source exists
         */
        if (!$this->exists('rpm', $source)) {
            throw new Exception('Source ' . $source . ' does not exist');
        }

        /**
         *  Get source Id
         */
        $id = $this->getIdByTypeName('rpm', $source);

        /**
         *  Get source definition
         */
        $definition = json_decode($this->getDefinition($id), true);

        /**
         *  Build predefined releasever from definition, if any
         */
        if (!empty($definition['releasever'])) {
            foreach ($definition['releasever'] as $releasever) {
                $name = '';
                $description = '';

                if (!empty($releasever['name'])) {
                    $name = $releasever['name'];
                }
                if (!empty($releasever['description'])) {
                    $description = $releasever['description'];
                }

                $predefinedReleaseVersions[] = [
                    'id' => $name,
                    'text' => $name . ' (' . $description . ')'
                ];
            }
        }

        /**
         *  Build possible release version from default values
         */
        foreach (RPM_RELEASEVERS as $releaseverName => $releaseverDescription) {
            // Check if release version is already in the predefined release versions, if so, skip it
            if (!empty($predefinedReleaseVersions)) {
                foreach ($predefinedReleaseVersions as $predefinedReleaseVersion) {
                    if ($predefinedReleaseVersion['id'] == $releaseverName) {
                        continue 2;
                    }
                }
            }

            $possibleReleaseVersions[] = [
                'id' => $releaseverName,
                'text' => $releaseverName . ' (' . $releaseverDescription . ')',
            ];
        }

        /**
         *  Build final data array
         *  This is the array which will be returned to the frontend and used to populate the release versions select
         */

        /**
         *  Add predefined release versions if any
         */
        if (!empty($predefinedReleaseVersions)) {
            $data[] = array(
                "text" => "Suggested release versions",
                "children" => $predefinedReleaseVersions
            );
        }

        /**
         *  Add possible release versions
         */
        if (!empty($possibleReleaseVersions)) {
            $data[] = array(
                "text" => "Possible release versions",
                "children" => $possibleReleaseVersions
            );
        }

        return $data;
    }
}
