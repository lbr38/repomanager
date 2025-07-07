<?php

namespace Controllers\Task\Form;

use Exception;

class Form
{
    private $validActions = array('create', 'update', 'env', 'duplicate', 'delete', 'removeEnv', 'rebuild');

    /**
     *  Return the task form to the user according to his selection
     */
    public function get(string $action, array $repos) : string
    {
        $userController = new \Controllers\User\User();
        $usersEmail = $userController->getEmails();

        $content = '<form id="task-form" autocomplete="off">';

        foreach ($repos as $repo) {
            $myrepo = new \Controllers\Repo\Repo();
            $repoId = \Controllers\Common::validateData($repo['repo-id']);
            $snapId = \Controllers\Common::validateData($repo['snap-id']);
            $envId  = null;

            /**
             *  If an environment points to the snapshot (snapId), retrieve the envId from the repo array
             */
            if (!empty($repo['envId'])) {
                $envId = \Controllers\Common::validateData($repo['env-id']);
            }

            /**
             *  Check that the Ids are numeric
             */
            if (!is_numeric($repoId)) {
                throw new Exception("Repo Id is invalid");
            }
            if (!is_numeric($snapId)) {
                throw new Exception("Snapshot Id is invalid");
            }
            if (!empty($envId)) {
                if (!is_numeric($envId)) {
                    throw new Exception("Environment Id is invalid");
                }
            }

            /**
             *  Check that the Ids exist in the database
             */
            if (!$myrepo->existsId($repoId)) {
                throw new Exception("Repo Id does not exist");
            }
            if (!$myrepo->existsSnapId($snapId)) {
                throw new Exception("Snapshot Id does not exist");
            }
            if (!is_null($envId)) {
                if (!$myrepo->existsEnvId($envId)) {
                    throw new Exception("Environment Id does not exist");
                }
            }

            /**
             *  Retrieve all repo data from the Ids
             */
            $myrepo->getAllById($repoId, $snapId, $envId);

            /**
             *  Retrieve the package type of the repo
             */
            $packageType = $myrepo->getPackageType();

            /**
             *  Build the form from a template
             */
            ob_start();

            echo '<div class="task-form-params" repo-id="' . $repoId . '" snap-id="' . $snapId . '" env-id="' . $envId . '" action="' . $action . '">';

            /**
             *  Include form template
             */
            include(ROOT . '/views/includes/forms/tasks/' . $action . '.inc.php');

            echo '</div>';

            echo '<br><hr>';

            $content .= ob_get_clean();
        }

        ob_start();

        /**
         *  Include schedule task template
         */
        include(ROOT . '/views/includes/forms/tasks/schedule.inc.php');

        $content .= ob_get_clean();

        $content .= '<br><button class="task-confirm-btn btn-large-red">Execute now</button></form><br><br>';

        return $content;
    }

    /**
     *  Validate the task form filled by the user
     *  @param array $tasksParams
     */
    public function validate(array $tasksParams) : void
    {
        foreach ($tasksParams as $task) {
            /**
             *  Retrieve action
             */
            if (empty($task['action'])) {
                throw new Exception('No action has been specified');
            }

            if (!in_array($task['action'], $this->validActions)) {
                throw new Exception('Invalid action: ' . $task['action']);
            }

            /**
             *  If the user is not an administrator or does not have permission to perform the specified action, prevent execution of the task.
             */
            if (!IS_ADMIN and !in_array($task['action'], USER_PERMISSIONS['repositories']['allowed-actions']['repos'])) {
                throw new Exception('You are not allowed to execute this action');
            }

            /**
             *  Generate controller name
             */
            $controllerPath = '\Controllers\Task\Form\\' . ucfirst($task['action']);

            /**
             *  Check if class exists, otherwise the action might be invalid
             */
            if (!class_exists($controllerPath)) {
                throw new Exception('Invalid action: ' . $task['action']);
            }

            /**
             *  Validate form by calling the controller
             */
            $controller = new $controllerPath();
            $controller->validate($task);
        }
    }
}
