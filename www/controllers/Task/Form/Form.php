<?php

namespace Controllers\Task\Form;

use Exception;
use Controllers\User\User;
use Controllers\Repo\Repo;
use Controllers\Utils\Validate;
use Controllers\Repo\Environment;
use Controllers\Task\Scheduled as ScheduledTask;
use Controllers\User\Permission\Repo as RepoPermission;

class Form
{
    private $validActions = ['create', 'update', 'env', 'duplicate', 'delete', 'removeEnv', 'rebuild', 'rename'];

    /**
     *  Return the task form to the user according to his selection
     */
    public function get(string $action, array $repos) : string
    {
        $userController = new User();
        $usersEmail = $userController->getEmails();

        $content = '<form id="task-form" autocomplete="off">';

        foreach ($repos as $repo) {
            $repoController = new Repo();
            $repoEnvController = new Environment();
            $scheduledTaskController = new ScheduledTask();
            $repoId = null;
            $snapId = null;
            $envId  = null;
            $scheduledTasksCount = 0;

            if (empty($repo['repo-id'])) {
                throw new Exception('Repository Id is required');
            }

            if (!is_numeric($repo['repo-id'])) {
                throw new Exception('Repository Id is invalid');
            }

            $repoId = Validate::int($repo['repo-id']);

            // If a snapshot Id is provided
            if (!empty($repo['snap-id'])) {
                if (!is_numeric($repo['snap-id'])) {
                    throw new Exception('Snapshot Id is invalid');
                }

                $snapId = Validate::int($repo['snap-id']);
            }

            // If an environment points to the snapshot (snapId), retrieve the envId from the repo array
            if (!empty($repo['env-id'])) {
                if (!is_numeric($repo['env-id'])) {
                    throw new Exception('Environment Id is invalid');
                }

                $envId = Validate::int($repo['env-id']);
            }

            // Check that the Ids exist in the database
            if (!$repoController->existsId($repoId)) {
                throw new Exception("Repository Id does not exist");
            }
            if (!empty($snapId) and !$repoController->existsSnapId($snapId)) {
                throw new Exception("Snapshot Id does not exist");
            }
            if (!empty($envId) and !$repoEnvController->exists($envId)) {
                throw new Exception("Environment Id does not exist");
            }

            // Retrieve all repo data from the Ids
            $repoController->getAllById($repoId, $snapId, $envId);

            // Retrieve the package type of the repo
            $packageType = $repoController->getPackageType();

            // Get scheduled tasks on the snapshot (if any) and count them
            if (!empty($snapId)) {
                $scheduledTasks = $scheduledTaskController->getBySnapId($snapId);
                $scheduledTasksCount = count($scheduledTasks);
            }

            // Build the form from a template
            ob_start();

            echo '<div class="task-form-params" repo-id="' . $repoId . '" snap-id="' . $snapId . '" env-id="' . $envId . '" action="' . $action . '">';

            // Include form template
            include(ROOT . '/views/includes/forms/tasks/' . $action . '.inc.php');

            echo '</div>';

            echo '<br><hr>';

            $content .= ob_get_clean();
        }

        ob_start();

        // Include schedule task template
        include(ROOT . '/views/includes/forms/tasks/schedule.inc.php');

        $content .= ob_get_clean();

        // Add submit button and close form
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
            // Retrieve action
            if (empty($task['action'])) {
                throw new Exception('No action has been specified');
            }

            if (!in_array($task['action'], $this->validActions)) {
                throw new Exception('Invalid action: ' . $task['action']);
            }

            // If the user does not have permission to perform the specified action, prevent execution of the task.
            if (!RepoPermission::allowedAction($task['action'])) {
                throw new Exception('You are not allowed to execute this action');
            }

            // Generate controller name
            $controllerPath = '\Controllers\Task\Form\\' . ucfirst($task['action']);

            // Check if class exists, otherwise the action might be invalid
            if (!class_exists($controllerPath)) {
                throw new Exception('Invalid action: ' . $task['action']);
            }

            // Validate form by calling the controller
            $controller = new $controllerPath();
            $controller->validate($task);
        }
    }
}
