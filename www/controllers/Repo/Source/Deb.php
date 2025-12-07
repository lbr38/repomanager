<?php

namespace Controllers\Repo\Source;

use Exception;
use \Controllers\Utils\Validate;

class Deb extends \Controllers\Repo\Source\Source
{
    /**
     *  Add a new deb source repository distribution
     */
    public function addDistribution(int $id, string $name)
    {
        $name = Validate::string($name);

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
         *  Check that a distribution with the same name does not already exist
         */
        foreach ($currentDefinition['distributions'] as $distribution) {
            if ($distribution['name'] == $name) {
                throw new Exception('Distribution ' . $name . ' already exists');
            }
        }

        /**
         *  Add the new distribution
         */
        $currentDefinition['distributions'][] = [
            'name' => $name,
            'description' => '',
            'components' => []
        ];

        /**
         *  Save the new source repository definition
         */
        $this->editDefinition($id, json_encode($currentDefinition));
    }

    /**
     *  Edit a deb source repository distribution
     */
    public function editDistribution(int $id, int $distributionId, array $params)
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
         *  Check that distribution Id exists in the source repository
         */
        if (!isset($currentDefinition['distributions'][$distributionId])) {
            throw new Exception('Distribution does not exist');
        }

        /**
         *  Check that the distribution name is not empty
         */
        if (empty($params['name'])) {
            throw new Exception('Distribution name is required');
        }

        /**
         *  Check that the distribution name does not already exist
         */
        foreach ($currentDefinition['distributions'] as $currentDistributionId => $distribution) {
            if ($distribution['name'] == $params['name'] and $currentDistributionId != $distributionId) {
                throw new Exception('Distribution ' . $params['name'] . ' already exists');
            }
        }

        /**
         *  Set new distribution params
         */
        $currentDefinition['distributions'][$distributionId]['name'] = $params['name'];
        $currentDefinition['distributions'][$distributionId]['description'] = $params['description'];

        /**
         *  Save the new source repository definition
         */
        $this->editDefinition($id, json_encode($currentDefinition));
    }

    /**
     *  Remove a distribution from a deb source repository
     */
    public function removeDistribution(int $sourceId, int $distributionId)
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
         *  Check that distribution Id exists in the source repository
         */
        if (!isset($currentDefinition['distributions'][$distributionId])) {
            throw new Exception('Distribution does not exist');
        }

        /**
         *  Remove the distribution
         */
        unset($currentDefinition['distributions'][$distributionId]);

        /**
         *  Save the new source repository definition
         */
        $this->editDefinition($sourceId, json_encode($currentDefinition));
    }

    /**
     *  Add a distribution section from a deb source repository
     */
    public function addSection(int $sourceId, int $distributionId, string $section)
    {
        $section = Validate::string($section);

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
         *  Check that distribution Id exists in the source repository
         */
        if (!isset($currentDefinition['distributions'][$distributionId])) {
            throw new Exception('Distribution Id ' . $distributionId . ' does not exist');
        }

        /**
         *  Check that the section does not already exist in the distribution
         */
        foreach ($currentDefinition['distributions'][$distributionId]['components'] as $sectionDefinition) {
            if ($sectionDefinition['name'] == $section) {
                throw new Exception('Section ' . $section . ' already exists');
            }
        }

        /**
         *  Add the new section
         */
        $currentDefinition['distributions'][$distributionId]['components'][] = [
            'name' => $section
        ];

        /**
         *  Save the new source repository definition
         */
        $this->editDefinition($sourceId, json_encode($currentDefinition));
    }

    /**
     *  Remove a distribution section from a deb source repository
     */
    public function removeSection(int $sourceId, int $distributionId, int $sectionId)
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
         *  Check that section Id exists in the distribution
         */
        if (!isset($currentDefinition['distributions'][$distributionId]['components'][$sectionId])) {
            throw new Exception('Section does not exist');
        }

        /**
         *  Remove the section
         */
        unset($currentDefinition['distributions'][$distributionId]['components'][$sectionId]);

        /**
         *  Save the new source repository definition
         */
        $this->editDefinition($sourceId, json_encode($currentDefinition));
    }

    /**
     *  Add a gpg key from a deb source repository distribution
     */
    public function addGpgKey(int $id, int $distributionId, string $gpgKeyUrl, string $gpgKeyFingerprint, string $gpgKeyPlainText)
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
         *  Check that distribution Id exists in the source repository
         */
        if (!isset($currentParams['distributions'][$distributionId])) {
            throw new Exception('Distribution Id ' . $distributionId . ' does not exist');
        }

        /**
         *  Import the gpg key and get the fingerprints
         */
        $fingerprints = $gpgController->import($gpgKeyUrl, $gpgKeyFingerprint, $gpgKeyPlainText);

        /**
         *  Add the new gpg key to the distribution
         *  Instead of adding the provided gpg key, we add all the fingerprints found in the key
         */
        foreach ($fingerprints as $fingerprint) {
            // Ignore fingerprint if already exists
            if (!empty($currentParams['distributions'][$distributionId]['gpgkeys'])) {
                foreach ($currentParams['distributions'][$distributionId]['gpgkeys'] as $gpgKeyDefinition) {
                    if (isset($gpgKeyDefinition['fingerprint']) and $gpgKeyDefinition['fingerprint'] == $fingerprint) {
                        continue 2;
                    }
                }
            }

            // Otherwise add the fingerprint
            $currentParams['distributions'][$distributionId]['gpgkeys'][] = [
                'fingerprint' => $fingerprint
            ];
        }

        /**
         *  Save the new source repository definition
         */
        $this->editDefinition($id, json_encode($currentParams));
    }

    /**
     *  Remove a gpg key from a deb source repository distribution
     */
    public function removeGpgKey(int $id, int $distributionId, int $gpgKeyId)
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
         *  Check that distribution Id exists in the source repository
         */
        if (!isset($currentParams['distributions'][$distributionId])) {
            throw new Exception('Distribution Id ' . $distributionId . ' does not exist');
        }

        /**
         *  Check that the gpg key exists in the distribution
         */
        if (!isset($currentParams['distributions'][$distributionId]['gpgkeys'][$gpgKeyId])) {
            throw new Exception('GPG key does not exist');
        }

        /**
         *  Remove the gpg key
         */
        unset($currentParams['distributions'][$distributionId]['gpgkeys'][$gpgKeyId]);

        /**
         *  Save the new source repository definition
         */
        $this->editDefinition($id, json_encode($currentParams));
    }

    /**
     *  Return an array of predefined distributions for a source repository
     *  The array is used in the frontend to populate the distributions select box
     */
    public function getPredefinedDistributions(string $source)
    {
        $data = [];
        $predefinedDistributions = [];
        $possibleDistributions = [];
        $source = Validate::string($source);

        /**
         *  Check if source is valid
         */
        if (empty($source)) {
            throw new Exception('Source is required');
        }

        /**
         *  Check if source exists
         */
        if (!$this->exists('deb', $source)) {
            throw new Exception('Source ' . $source . ' does not exist');
        }

        /**
         *  Get source Id
         */
        $id = $this->getIdByTypeName('deb', $source);

        /**
         *  Get source definition
         */
        $definition = json_decode($this->getDefinition($id), true);

        /**
         *  Build predefined distributions from definition, if any
         */
        if (!empty($definition['distributions'])) {
            foreach ($definition['distributions'] as $distribution) {
                $name = '';
                $description = '';

                if (!empty($distribution['name'])) {
                    $name = $distribution['name'];
                }
                if (!empty($distribution['description'])) {
                    $description = '(' . $distribution['description'] . ')';
                }

                $predefinedDistributions[] = [
                    'id' => $name,
                    'text' => $name . ' ' . $description
                ];
            }
        }

        /**
         *  Build possible distributions from default values
         */
        foreach (DEB_DISTRIBUTIONS as $distributionName => $distributionDescription) {
            // Check if distribution is already in the predefined distributions, if so, skip it
            if (!empty($predefinedDistributions)) {
                foreach ($predefinedDistributions as $predefinedDistribution) {
                    if ($predefinedDistribution['id'] == $distributionName) {
                        continue 2;
                    }
                }
            }

            $possibleDistributions[] = [
                'id' => $distributionName,
                'text' => $distributionName . ' (' . $distributionDescription . ')',
            ];
        }

        /**
         *  Build final data array
         *  This is the array which will be returned to the frontend and used to populate the distributions select
         */

        /**
         *  Add predefined distributions if any
         */
        if (!empty($predefinedDistributions)) {
            $data[] = [
                "text" => "Suggested distributions",
                "children" => $predefinedDistributions
            ];
        }

        /**
         *  Add possible distributions
         */
        if (!empty($possibleDistributions)) {
            $data[] = [
                "text" => "Possible distributions",
                "children" => $possibleDistributions
            ];
        }


        return $data;
    }

    /**
     *  Return an array of predefined components for a distribution
     *  The array is used in the frontend to populate the components select box
     */
    public function getPredefinedComponents(string $source, array $distributions)
    {
        $data = [];
        $predefinedComponents = [];
        $possibleComponents = [];
        $source = Validate::string($source);

        /**
         *  Check if source is valid
         */
        if (empty($source)) {
            throw new Exception('Source is required');
        }

        if (empty($distributions)) {
            throw new Exception('Distribution(s) required');
        }

        /**
         *  Check that distributions are valid
         */
        foreach ($distributions as $distribution) {
            if (!Validate::alphaNumericHyphen($distribution, ['.', '/'])) {
                throw new Exception('Distribution ' . $distribution . ' contains invalid characters');
            }
        }

        /**
         *  Check if source exists
         */
        if (!$this->exists('deb', $source)) {
            throw new Exception('Source ' . $source . ' does not exist');
        }

        /**
         *  Get source Id
         */
        $id = $this->getIdByTypeName('deb', $source);

        /**
         *  Get source definition
         */
        $definition = json_decode($this->getDefinition($id), true);

        /**
         *  Build predefined components from definition, if any
         */
        if (!empty($definition['distributions'])) {
            foreach ($definition['distributions'] as $distribution) {
                // Continue if distribution is not in the list of selected distributions by the user
                if (!in_array($distribution['name'], $distributions)) {
                    continue;
                }

                // Continue if distribution has no components (should not happen)
                if (empty($distribution['components'])) {
                    continue;
                }

                // Loop through components and add them to the predefined components array, if not already in
                foreach ($distribution['components'] as $component) {
                    if (empty($component['name'])) {
                        continue;
                    }

                    // Check if component is already in the predefined components, if so, skip it
                    if (!empty($predefinedComponents)) {
                        foreach ($predefinedComponents as $predefinedComponent) {
                            if ($predefinedComponent['id'] == $component['name']) {
                                continue 2;
                            }
                        }
                    }

                    // Add component to the predefined components array
                    $predefinedComponents[] = [
                        'id' => $component['name'],
                        'text' => $component['name'],
                    ];
                }
            }
        }

        /**
         *  Build possible components from default values
         */
        foreach (DEB_COMPONENTS as $component) {
            // Check if component is already in the predefined components, if so, skip it
            if (!empty($predefinedComponents)) {
                foreach ($predefinedComponents as $predefinedComponent) {
                    if ($predefinedComponent['id'] == $component) {
                        continue 2;
                    }
                }
            }

            $possibleComponents[] = [
                'id' => $component,
                'text' => $component,
            ];
        }

        /**
         *  Build final data array
         *  This is the array which will be returned to the frontend and used to populate the components select
         */

        /**
         *  Add predefined components if any
         */
        if (!empty($predefinedComponents)) {
            $data[] = [
                "text" => "Suggested components",
                "children" => $predefinedComponents
            ];
        }

        /**
         *  Add possible components
         */
        if (!empty($possibleComponents)) {
            $data[] = [
                "text" => "Possible components",
                "children" => $possibleComponents
            ];
        }

        return $data;
    }
}
