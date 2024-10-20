<?php

namespace Controllers\Repo\Source;

use Exception;

class Deb extends \Controllers\Repo\Source\Source
{
    /**
     *  Import a deb source repository
     *  TODO : à finaliser
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
        if (empty($repo['architectures'])) {
            throw new Exception('source repository architectures is empty');
        }
        if (empty($repo['distributions'])) {
            throw new Exception('source repository distributions is empty');
        }
        foreach ($repo['distributions'] as $distribution) {
            if (empty($distribution['components'])) {
                throw new Exception('source repository distributions components is empty');
            }
        }

        // TODO : ajouter + de vérifications relatives à deb
        // Les verifs des paramètres de base sont effectuées par la fonction new() de la classe parente

        /**
         *  If a repository with the same name already exists, then delete it before adding the new one
         */
        if ($this->exists('deb', $repo['name'])) {
            /**
             *  Get it's Id
             */
            $id = $this->getIdByTypeName('deb', $repo['name']);

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
     *  Add a new deb source repository distribution
     */
    public function addDistribution(int $id, string $name)
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
        $currentDefinition['distributions'][] = array(
            'name' => $name,
            'description' => '',
            'components' => []
        );

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
        $section = \Controllers\Common::validateData($section);

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
        $currentDefinition['distributions'][$distributionId]['components'][] = array(
            'name' => $section
        );

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
    public function addGpgKey(int $id, int $distributionId, string $gpgKey)
    {
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
         *  Check that distribution Id exists in the source repository
         */
        if (!isset($currentParams['distributions'][$distributionId])) {
            throw new Exception('Distribution Id ' . $distributionId . ' does not exist');
        }

        /**
         *  Check that the gpg key does not already exist in the distribution
         */
        if ($type == 'link') {
            foreach ($currentParams['distributions'][$distributionId]['gpgkeys'] as $gpgKeyDefinition) {
                if (isset($gpgKeyDefinition['link']) and $gpgKeyDefinition['link'] == $gpgKey) {
                    throw new Exception('GPG key ' . $gpgKey . ' already exists');
                }
            }
        }
        if ($type == 'fingerprint') {
            foreach ($currentParams['distributions'][$distributionId]['gpgkeys'] as $gpgKeyDefinition) {
                if (isset($gpgKeyDefinition['fingerprint']) and $gpgKeyDefinition['fingerprint'] == $gpgKey) {
                    throw new Exception('GPG key ' . $gpgKey . ' already exists');
                }
            }
        }

        /**
         *  Add the new gpg key
         */
        if ($type == 'link') {
            $currentParams['distributions'][$distributionId]['gpgkeys'][] = array(
                'link' => $gpgKey
            );
        }
        if ($type == 'fingerprint') {
            $currentParams['distributions'][$distributionId]['gpgkeys'][] = array(
                'fingerprint' => $gpgKey
            );
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
}
