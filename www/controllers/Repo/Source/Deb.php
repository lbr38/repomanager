<?php

namespace Controllers\Repo\Source;

use Exception;

class Deb extends \Controllers\Repo\Source\Source
{
    /**
     *  Import a deb source repository
     *  TODO : à terminer
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
         *  Get complete source repository details
         */
        $currentParams = json_decode($this->getDefinition($id), true);

        /**
         *  Check that a distribution with the same name does not already exist
         */
        foreach ($currentParams['distributions'] as $distribution) {
            if ($distribution['name'] == $name) {
                throw new Exception('Distribution ' . $name . ' already exists');
            }
        }

        /**
         *  Add the new distribution
         */
        $currentParams['distributions'][] = array(
            'name' => $name,
            'description' => '',
            'components' => []
        );

        /**
         *  Save the new source repository details
         */
        $this->editDefinition($id, json_encode($currentParams));
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
         *  Get complete source repository details
         */
        $currentParams = json_decode($this->getDefinition($id), true);
    
        /**
         *  Check that distribution Id exists in the source repository
         */
        if (!isset($currentParams['distributions'][$distributionId])) {
            throw new Exception('Distribution does not exist');
        }

        /**
         *  Set new distribution params
         */
        $currentParams['distributions'][$distributionId]['name'] = $params['name'];
        $currentParams['distributions'][$distributionId]['description'] = $params['description'];

        /**
         *  Save the new source repository details
         */
        $this->editDefinition($id, json_encode($currentParams));
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
         *  Get complete source repository details
         */
        $currentParams = json_decode($this->getDefinition($sourceId), true);

        /**
         *  Check that distribution Id exists in the source repository
         */
        if (!isset($currentParams['distributions'][$distributionId])) {
            throw new Exception('Distribution does not exist');
        }

        /**
         *  Remove the distribution
         */
        unset($currentParams['distributions'][$distributionId]);

        /**
         *  Save the new source repository details
         */
        $this->editDefinition($sourceId, json_encode($currentParams));
    }
}
