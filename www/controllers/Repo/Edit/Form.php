<?php

namespace Controllers\Repo\Edit;

use Exception;
use \Controllers\History\Save as History;
use \Controllers\Utils\Validate;

class Form
{
    /**
     *  Get edit repository form
     *  TODO : refacto avec \Controllers\Task\Form\Form
     */
    public function get(array $repos) : string
    {
        $mysource = new \Controllers\Repo\Source\Source();

        /**
         *  Get source repositories
         */
        $newRepoRpmSourcesList = $mysource->listAll('rpm');
        $newRepoDebSourcesList = $mysource->listAll('deb');

        $content = '<form id="edit-form" autocomplete="off">';

        foreach ($repos as $repo) {
            $repoController = new \Controllers\Repo\Repo();
            $repoId = Validate::string($repo['repo-id']);
            $snapId = Validate::string($repo['snap-id']);

            /**
             *  Check that the Ids are numeric
             */
            if (!is_numeric($repoId)) {
                throw new Exception('Repository Id is invalid');
            }
            if (!is_numeric($snapId)) {
                throw new Exception('Snapshot Id is invalid');
            }

            /**
             *  Check that the Ids exist in the database
             */
            if (!$repoController->existsId($repoId)) {
                throw new Exception('Repository Id does not exist');
            }
            if (!$repoController->existsSnapId($snapId)) {
                throw new Exception('Snapshot Id does not exist');
            }

            /**
             *  Retrieve all repo data from the Ids
             */
            $repoController->getAllById($repoId, $snapId);

            /**
             *  Retrieve the package type of the repo
             */
            $packageType = $repoController->getPackageType();

            /**
             *  Build the form from a template
             */
            ob_start();

            echo '<div class="edit-form-params" repo-id="' . $repoId . '" snap-id="' . $snapId . '">';

            /**
             *  Include form template
             */
            include(ROOT . '/views/includes/forms/edit.inc.php');

            echo '</div>';

            echo '<br><hr>';

            $content .= ob_get_clean();
        }

        $content .= '<br><button class="task-confirm-btn btn-large-red">Edit</button></form><br><br>';
        $content .= '</form>';

        return $content;
    }

    /**
     *  Validate the edit form filled by the user
     *  TODO : on utilise les mêmes controllers de vérification utilisés dans \Controllers\Task\Form\Form(), à refacto
     */
    public function validate(array $params) : void
    {
        foreach ($params as $param) {
            $repoController = new \Controllers\Repo\Repo();

            if (empty($param['repo-id'])) {
                throw new Exception('Repository Id is missing');
            }

            if (empty($param['snap-id'])) {
                throw new Exception('Snapshot Id is missing');
            }

            /**
             *  Check that the snapshot id is valid
             */
            \Controllers\Task\Form\Param\Snapshot::checkId($param['snap-id']);

            /**
             *  Retrieve all repo data from the Ids
             */
            $repoController->getAllById($param['repo-id'], $param['snap-id']);

            /**
             *  Check that the selected source repository is valid
             */
            if ($repoController->getType() == 'mirror') {
                \Controllers\Task\Form\Param\Source::check($param['source'], $repoController->getPackageType());
            }

            /**
             *  Check that the specified description is valid
             *  Description cannot contain single quotes or backslashes
             *  TODO: no editable description for now
             */
            // if (str_contains($param['description'], "'") || str_contains($param['description'], "\\")) {
            //     throw new Exception('Description contains invalid characters');
            // }
            // $description = Validate::string($param['description']);
        }
    }

    /**
     *  Edit the repositories
     */
    public function edit(array $params) : void
    {
        $layoutContainerReloadController = new \Controllers\Layout\ContainerReload();

        foreach ($params as $param) {
            $repoController = new \Controllers\Repo\Repo();

            /**
             *  Retrieve all repo data from the Ids
             */
            $repoController->getAllById($param['repo-id'], $param['snap-id']);

            /**
             *  Update repository source
             */
            if ($repoController->getType() == 'mirror') {
                $repoController->updateSource($param['repo-id'], $param['source']);
            }

            /**
             *  Update snapshot description
             *  TODO: no editable description for now
             */
            // $repoController->updateDescription($param['snap-id'], $description);
            // $snapshotController->updateDescription($param['snap-id'], $description);

            /**
             *  Add history
             */
            if ($repoController->getPackageType() == 'rpm') {
                History::set('Editing <span class="label-white">' . $repoController->getName() . '</span> repository properties (' . $repoController->getType() . ')');
            }
            if ($repoController->getPackageType() == 'deb') {
                History::set('Editing <span class="label-white">' . $repoController->getName() . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection() . '</span> repository properties (' . $repoController->getType() . ')');
            }

            /**
             *  Reload repositories list
             */
            $layoutContainerReloadController->reload('repos/list');
        }
    }
}
