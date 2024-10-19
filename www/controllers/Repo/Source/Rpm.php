<?php

namespace Controllers\Repo\Source;

use Exception;

class Rpm extends \Controllers\Repo\Source\Source
{
    /**
     *  Import a rpm source repository
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
        if (empty($repo['releasever'])) {
            throw new Exception('source repository releasever is empty');
        }

        // TODO : ajouter + de vérifications relatives à rpm
        // Les verifs des paramètres de base sont effectuées par la fonction new() de la classe parente

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
            throw new Exception('Distribution does not exist');
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
     *  Remove a gpg key from a rpm source repository release version
     */
    public function removeGpgKey(int $id, int $releaseverId, string $gpgKey)
    {
        $gpgKey = \Controllers\Common::validateData($gpgKey);

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
        if (!in_array($gpgKey, $currentParams['releasever'][$releaseverId]['gpgkeys'])) {
            throw new Exception('GPG key ' . $gpgKey . ' does not exist');
        }

        /**
         *  Remove the gpg key
         */
        $currentParams['releasever'][$releaseverId]['gpgkeys'] = array_diff($currentParams['releasever'][$releaseverId]['gpgkeys'], array($gpgKey));

        /**
         *  Save the new source repository definition
         */
        $this->editDefinition($id, json_encode($currentParams));
    }
}
