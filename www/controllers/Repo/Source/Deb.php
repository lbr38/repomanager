<?php

namespace Controllers\Repo\Source;

use Exception;
use Controllers\Utils\Validate;

class Deb extends \Controllers\Repo\Source\Source
{
    /**
     *  Add a new deb source repository distribution
     */
    public function addDistribution(int $id, string $name): void
    {
        $name = Validate::string($name);

        // Check that a distribution with the same name does not already exist
        foreach ($this->currentDefinition['distributions'] as $distribution) {
            if ($distribution['name'] == $name) {
                throw new Exception('Distribution ' . $name . ' already exists');
            }
        }

        // Add the new distribution
        $this->currentDefinition['distributions'][] = [
            'name' => $name,
            'description' => '',
            'eol' => '',
            'components' => []
        ];

        // Save the new source repository definition
        $this->editDefinition($this->id, $this->currentDefinition);
    }

    /**
     *  Edit a deb source repository distribution
     */
    public function editDistribution(int $distributionId, array $params): void
    {
        // Check that distribution Id exists in the source repository
        if (!isset($this->currentDefinition['distributions'][$distributionId])) {
            throw new Exception('Distribution does not exist');
        }

        // Check that the distribution name is not empty
        if (empty($params['name'])) {
            throw new Exception('Distribution name is required');
        }

        // Check that the distribution name does not already exist
        foreach ($this->currentDefinition['distributions'] as $currentDistributionId => $distribution) {
            if ($distribution['name'] == $params['name'] and $currentDistributionId != $distributionId) {
                throw new Exception('Distribution ' . $params['name'] . ' already exists');
            }
        }

        // Set new distribution params
        $this->currentDefinition['distributions'][$distributionId]['name'] = $params['name'];
        $this->currentDefinition['distributions'][$distributionId]['description'] = $params['description'];
        $this->currentDefinition['distributions'][$distributionId]['eol'] = $params['eol'];

        // Save the new source repository definition
        $this->editDefinition($this->id, $this->currentDefinition);
    }

    /**
     *  Remove a distribution from a deb source repository
     */
    public function removeDistribution(int $distributionId): void
    {
        // Check that the distribution Id exists in the source repository
        if (!isset($this->currentDefinition['distributions'][$distributionId])) {
            throw new Exception('Distribution does not exist');
        }

        // Remove the distribution from the source repository definition
        unset($this->currentDefinition['distributions'][$distributionId]);

        // Save the updated source repository definition
        $this->editDefinition($this->id, $this->currentDefinition);
    }

    /**
     *  Add a distribution component to a deb source repository
     */
    public function addComponent(int $distributionId, string $component): void
    {
        $component = Validate::string($component);

        // Check that the distribution Id exists in the source repository
        if (!isset($this->currentDefinition['distributions'][$distributionId])) {
            throw new Exception('Distribution Id ' . $distributionId . ' does not exist');
        }

        // Check that the component does not already exist in the distribution
        foreach ($this->currentDefinition['distributions'][$distributionId]['components'] as $componentDefinition) {
            if ($componentDefinition['name'] == $component) {
                throw new Exception('Component ' . $component . ' already exists');
            }
        }

        // Add the new component to the distribution
        $this->currentDefinition['distributions'][$distributionId]['components'][] = [
            'name' => $component
        ];

        // Save the updated source repository definition
        $this->editDefinition($this->id, $this->currentDefinition);
    }

    /**
     *  Edit a component in a deb source repository
     */
    public function editComponent(int $distributionId, int $componentId, array $params): void
    {
        // Set the new component parameters
        $this->currentDefinition['distributions'][$distributionId]['components'][$componentId]['description'] = $params['description'];
        $this->currentDefinition['distributions'][$distributionId]['components'][$componentId]['archs'] = $params['archs'] ?? [];

        // Save the new source repository definition
        $this->editDefinition($this->id, $this->currentDefinition);
    }

    /**
     *  Remove a distribution component from a deb source repository
     */
    public function removeComponent(int $distributionId, int $componentId): void
    {
        // Check that the component exists in the distribution
        if (!isset($this->currentDefinition['distributions'][$distributionId]['components'][$componentId])) {
            throw new Exception('Component does not exist');
        }

        // Remove the component from the distribution
        unset($this->currentDefinition['distributions'][$distributionId]['components'][$componentId]);

        // Save the new source repository definition
        $this->editDefinition($this->id, $this->currentDefinition);
    }

    /**
     *  Add a gpg key from a deb source repository distribution
     */
    public function addGpgKey(int $distributionId, string $gpgKeyUrl, string $gpgKeyFingerprint, string $gpgKeyPlainText): void
    {
        $gpgController = new \Controllers\Gpg();

        // Check that the distribution Id exists in the source repository
        if (!isset($this->currentDefinition['distributions'][$distributionId])) {
            throw new Exception('Distribution Id ' . $distributionId . ' does not exist');
        }

        // Import the gpg key and get the fingerprints
        $fingerprints = $gpgController->import($gpgKeyUrl, $gpgKeyFingerprint, $gpgKeyPlainText);

        /**
         *  Add the new gpg key to the distribution
         *  Instead of adding the provided gpg key, we add all the fingerprints found in the key
         */
        foreach ($fingerprints as $fingerprint) {
            // Ignore fingerprint if already exists
            if (!empty($this->currentDefinition['distributions'][$distributionId]['gpgkeys'])) {
                foreach ($this->currentDefinition['distributions'][$distributionId]['gpgkeys'] as $gpgKeyDefinition) {
                    if (isset($gpgKeyDefinition['fingerprint']) and $gpgKeyDefinition['fingerprint'] == $fingerprint) {
                        continue 2;
                    }
                }
            }

            // Otherwise add the fingerprint
            $this->currentDefinition['distributions'][$distributionId]['gpgkeys'][] = [
                'fingerprint' => $fingerprint
            ];
        }

        // Save the updated source repository definition
        $this->editDefinition($this->id, $this->currentDefinition);
    }

    /**
     *  Remove a gpg key from a deb source repository distribution
     */
    public function removeGpgKey(int $distributionId, int $gpgKeyId): void
    {
        // Check that the distribution Id exists in the source repository
        if (!isset($this->currentDefinition['distributions'][$distributionId])) {
            throw new Exception('Distribution Id ' . $distributionId . ' does not exist');
        }

        // Check that the gpg key exists in the distribution
        if (!isset($this->currentDefinition['distributions'][$distributionId]['gpgkeys'][$gpgKeyId])) {
            throw new Exception('GPG key does not exist');
        }

        // Remove the gpg key from the distribution
        unset($this->currentDefinition['distributions'][$distributionId]['gpgkeys'][$gpgKeyId]);

        // Save the updated source repository definition
        $this->editDefinition($this->id, $this->currentDefinition);
    }

    /**
     *  Return an array of predefined distributions for a source repository
     *  The array is used in the frontend to populate the distributions select box
     */
    public function getPredefinedDistributions(string $source): array
    {
        $data = [];
        $predefinedDistributions = [];
        $possibleDistributions = [];
        $source = Validate::string($source);

        // Validate the source string
        if (empty($source)) {
            throw new Exception('Source is required');
        }

        // Check if the source exists in the repository
        if (!$this->exists('deb', $source)) {
            throw new Exception('Source ' . $source . ' does not exist');
        }

        // Get the source Id based on the type and name
        $id = $this->getIdByTypeName('deb', $source);

        // Get the complete source repository definition
        $definition = $this->getDefinition($id);

        // Build the list of predefined distributions from the source repository definition
        if (!empty($definition['distributions'])) {
            foreach ($definition['distributions'] as $distribution) {
                $name = '';
                $description = '';
                $eol = '';

                if (!empty($distribution['name'])) {
                    $name = $distribution['name'];
                }
                if (!empty($distribution['description'])) {
                    $description = '(' . $distribution['description'] . ')';
                }
                if (!empty($distribution['eol'])) {
                    if ($distribution['eol'] < DATE_YMD) {
                        $eol = '(EOL)';
                    }
                }

                $predefinedDistributions[] = [
                    'id' => $name,
                    'text' => $name . ' ' . $description . ' ' . $eol
                ];
            }
        }

        // Build the list of possible distributions from the default values
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

        // Add predefined distributions if any
        if (!empty($predefinedDistributions)) {
            $data[] = [
                "text" => "Suggested distributions",
                "children" => $predefinedDistributions
            ];
        }

        // Add possible distributions
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
    public function getPredefinedComponents(string $source, array $distributions): array
    {
        $data = [];
        $predefinedComponents = [];
        $possibleComponents = [];
        $source = Validate::string($source);

        // Check if source is provided
        if (empty($source)) {
            throw new Exception('Source is required');
        }

        // Check if distributions are provided
        if (empty($distributions)) {
            throw new Exception('Distribution(s) required');
        }

        // Validate each distribution string
        foreach ($distributions as $distribution) {
            if (!Validate::alphaNumericHyphen($distribution, ['.', '/'])) {
                throw new Exception('Distribution ' . $distribution . ' contains invalid characters');
            }
        }

        // Check if the source exists
        if (!$this->exists('deb', $source)) {
            throw new Exception('Source ' . $source . ' does not exist');
        }

        // Get the source Id based on the type and name
        $id = $this->getIdByTypeName('deb', $source);

        // Get source definition
        $definition = $this->getDefinition($id);

        // Build predefined components from the source definition, if any
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

        // Build possible components from the default values
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

        // Add predefined components if any
        if (!empty($predefinedComponents)) {
            $data[] = [
                "text" => "Suggested components",
                "children" => $predefinedComponents
            ];
        }

        // Add possible components if any
        if (!empty($possibleComponents)) {
            $data[] = [
                "text" => "Possible components",
                "children" => $possibleComponents
            ];
        }

        return $data;
    }
}
