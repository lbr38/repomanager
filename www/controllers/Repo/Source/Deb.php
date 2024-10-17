<?php

namespace Controllers\Repo\Source;

use Exception;

class Deb extends \Controllers\Repo\Source\Source
{
    /**
     *  Import a deb source repository
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

        /**
         *  Format specified URL
         *  Delete spaces
         *  Delete anti-slash
         */
        $url = trim($repo['url']);
        $url = stripslashes($url);

        /**
         *  Check that URL is valid
         *  Allow ? and & characters for query strings
         *  Allow $ character for variables (e.g $releasever)
         *  Allow @ and : character for basic authentification (e.g http://user:password@url)
         */
        if (!\Controllers\Common::isAlphanumDash($url, array('http://', 'https://', '/', '.', '?', '&', '$', '@', ':'))) {
            throw new Exception('Specified URL contains invalid characters');
        }

        /**
         *  Check that URL starts with http(s)://
         */
        if (!preg_match('#^https?://#', $url)) {
            throw new Exception('Specified URL must start with <b>http(s)://</b>');
        }


        // TODO : ajouter + de vérifications



        /**
         *  If a repository with the same name already exists, then update it
         *  Otherwise, add it
         */
        if ($this->exists('deb', $repo['name'])) {
            /**
             *  If the repository already exists, then update it
             *  First get it's Id
             */
            $id = $this->getIdByName('deb', $repo['name']);

            /**
             *  Edit the source repository
             */
            $this->edit($id, $repo);
        } else {
            /**
             *  Add the new source repository
             */
            $this->new($repo);
        }
    }
}
