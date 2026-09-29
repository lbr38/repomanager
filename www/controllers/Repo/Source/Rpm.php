<?php

namespace Controllers\Repo\Source;

use Exception;
use Controllers\Utils\Validate;

class Rpm extends \Controllers\Repo\Source\Source
{
    /**
     *  Add a new rpm source repository release version
     */
    public function addReleasever(string $name): void
    {
        $name = Validate::string($name);

        // Check that a release version with the same name does not already exist
        if (!empty($this->currentDefinition['releasever'])) {
            foreach ($this->currentDefinition['releasever'] as $releasever) {
                if ($releasever['name'] === $name) {
                    throw new Exception('Release version ' . $name . ' already exists');
                }
            }
        }

        // Add the new release version
        $this->currentDefinition['releasever'][] = [
            'name' => $name,
            'description' => '',
            'eol' => ''
        ];

        // Save the new source repository definition
        $this->editDefinition($this->id, $this->currentDefinition);
    }

    /**
     *  Edit a rpm source repository release version
     */
    public function editReleasever(string $releaseverId, array $params): void
    {
        // Check that release version Id exists in the source repository
        if (!isset($this->currentDefinition['releasever'][$releaseverId])) {
            throw new Exception('Release version does not exist');
        }

        // Check that release version name is not empty
        if (empty($params['name'])) {
            throw new Exception('Release version is required');
        }

        // Check that a release version with the same name does not already exist
        foreach ($this->currentDefinition['releasever'] as $currentReleaseverId => $releasever) {
            if ($currentReleaseverId != $releaseverId and $releasever['name'] === $params['name']) {
                throw new Exception('Release version ' . $params['name'] . ' already exists');
            }
        }

        // Set new release version params
        $this->currentDefinition['releasever'][$releaseverId]['name'] = $params['name'];
        $this->currentDefinition['releasever'][$releaseverId]['description'] = $params['description'];
        $this->currentDefinition['releasever'][$releaseverId]['eol'] = $params['eol'];
        $this->currentDefinition['releasever'][$releaseverId]['archs'] = $params['archs'] ?? [];

        // Save the new source repository definition
        $this->editDefinition($this->id, $this->currentDefinition);
    }

    /**
     *  Remove a release version from a rpm source repository
     */
    public function removeReleasever(string $releaseverId): void
    {
        // Check that the release version exists in the source repository
        if (!isset($this->currentDefinition['releasever'][$releaseverId])) {
            throw new Exception('Release version does not exist');
        }

        // Remove the release version from the source repository definition
        unset($this->currentDefinition['releasever'][$releaseverId]);

        // Save the updated source repository definition
        $this->editDefinition($this->id, $this->currentDefinition);
    }

    /**
     *  Add a gpg key from a deb source repository release version
     */
    public function addGpgKey(string $releaseverId, string $gpgKeyUrl, string $gpgKeyFingerprint, string $gpgKeyPlainText): void
    {
        $gpgController = new \Controllers\Gpg();

        // Check that the release version exists in the source repository
        if (!isset($this->currentDefinition['releasever'][$releaseverId])) {
            throw new Exception('Release version Id ' . $releaseverId . ' does not exist');
        }

        // Import the gpg key and get the fingerprints
        $fingerprints = $gpgController->import($gpgKeyUrl, $gpgKeyFingerprint, $gpgKeyPlainText);

        /**
         *  Add the new gpg key to the release version
         *  Instead of adding the provided gpg key, we add all the fingerprints found in the key
         */
        foreach ($fingerprints as $fingerprint) {
            // Ignore fingerprint if already exists
            if (!empty($this->currentDefinition['releasever'][$releaseverId]['gpgkeys'])) {
                foreach ($this->currentDefinition['releasever'][$releaseverId]['gpgkeys'] as $gpgKeyDefinition) {
                    if (isset($gpgKeyDefinition['fingerprint']) and $gpgKeyDefinition['fingerprint'] == $fingerprint) {
                        continue 2;
                    }
                }
            }

            // Otherwise add the fingerprint
            $this->currentDefinition['releasever'][$releaseverId]['gpgkeys'][] = [
                'fingerprint' => $fingerprint
            ];
        }

        // Save the updated source repository definition
        $this->editDefinition($this->id, $this->currentDefinition);
    }

    /**
     *  Remove a gpg key from a rpm source repository release version
     */
    public function removeGpgKey(string $releaseverId, int $gpgKeyId): void
    {
        // Check that the release version exists in the source repository
        if (!isset($this->currentDefinition['releasever'][$releaseverId])) {
            throw new Exception('Release version Id ' . $releaseverId . ' does not exist');
        }

        // Check that the gpg key exists in the release version
        if (!isset($this->currentDefinition['releasever'][$releaseverId]['gpgkeys'][$gpgKeyId])) {
            throw new Exception('GPG key Id does not exist');
        }

        // Remove the gpg key from the release version
        unset($this->currentDefinition['releasever'][$releaseverId]['gpgkeys'][$gpgKeyId]);

        // Save the updated source repository definition
        $this->editDefinition($this->id, $this->currentDefinition);
    }

    /**
     *  Return an array of predefined release versions for a source repository
     *  The array is used in the frontend to populate the release versions select box
     */
    public function getPredefinedReleasever(string $source): array
    {
        $data = [];
        $predefinedReleaseVersions = [];
        $possibleReleaseVersions = [];
        $source = Validate::string($source);

        // Check if source is valid
        if (empty($source)) {
            throw new Exception('Source is required');
        }

        // Check if source exists
        if (!$this->exists('rpm', $source)) {
            throw new Exception('Source ' . $source . ' does not exist');
        }

        // Get the source Id based on the type and name
        $id = $this->getIdByTypeName('rpm', $source);

        // Get the source repository definition
        $definition = $this->getDefinition($id);

        // Build predefined releasever from definition, if any
        if (!empty($definition['releasever'])) {
            foreach ($definition['releasever'] as $releasever) {
                $name = '';
                $description = '';

                if (!empty($releasever['name'])) {
                    $name = $releasever['name'];
                }
                if (!empty($releasever['description'])) {
                    $description = '(' . $releasever['description'] . ')';
                }

                $predefinedReleaseVersions[] = [
                    'id' => $name,
                    'text' => $name . ' ' . $description
                ];
            }
        }

        // Build possible release version from default values
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

        // Add predefined release versions if any
        if (!empty($predefinedReleaseVersions)) {
            $data[] = [
                "text" => "Suggested release versions",
                "children" => $predefinedReleaseVersions
            ];
        }

        // Add possible release versions if any
        if (!empty($possibleReleaseVersions)) {
            $data[] = [
                "text" => "Possible release versions",
                "children" => $possibleReleaseVersions
            ];
        }

        return $data;
    }
}
