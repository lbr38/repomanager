<?php

namespace Controllers\Task\Form;

use Exception;
use Controllers\User\User;
use Controllers\Repo\Repo;
use Controllers\Environment;
use Controllers\Task\Target;
use Controllers\Utils\Validate;
use Controllers\Repo\Listing as RepoListing;
use Controllers\Repo\Environment as RepoEnvironment;
use Controllers\History\Save as History;
use Controllers\Task\Scheduled as ScheduledTask;
use Controllers\User\Permission\Repo as RepoPermission;

class Form
{
    private $validActions = ['create', 'update', 'env', 'duplicate', 'delete', 'removeEnv', 'rebuild', 'rename'];

    /**
     *  Return the task form to the user according to his selection
     */
    public function get(string $action, array $repos): string
    {
        $userController = new User();
        $repoEnvController = new RepoEnvironment();
        $repoListingController = new RepoListing();
        $scheduledTaskController = new ScheduledTask();

        // Get the list of email addresses to populate the recipient selector
        $usersEmail = $userController->getEmails();

        // Get current tags to populate the tag selector
        $tags = $repoListingController->listTags();

        // Initialize the form content
        $content = '<form id="task-form" autocomplete="off">';

        // Add each repository form to the content
        foreach ($repos as $repo) {
            $repoController = new Repo();
            $scheduledTasksCount = 0;
            $repoId = null;
            $snapId = null;
            $envId  = null;

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

            echo '<div class="task-form-params form-block form-block-accent-' . ($packageType == 'deb' ? 'red' : 'blue') . '" repo-id="' . $repoId . '" snap-id="' . $snapId . '" env-id="' . $envId . '" action="' . $action . '">';

            // Include form template
            include(ROOT . '/views/includes/forms/tasks/' . $action . '.inc.php');

            echo '</div>';

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

    /**
     *  Validate a task targeting a dynamic set of repositories (all latest snapshots matching filters)
     *  Such a task has no fixed snapshot, so the per-action validators cannot be used here
     *  @param array $taskParams
     */
    public function validateTarget(array $taskParams): array
    {
        $targetController = new Target();
        $envController = new Environment();

        // Retrieve action
        if (empty($taskParams['action'])) {
            throw new Exception('No action has been specified');
        }

        if (!in_array($taskParams['action'], $targetController->getValidActions())) {
            throw new Exception('This action cannot target all repositories: ' . $taskParams['action']);
        }

        // If the user does not have permission to perform the specified action, prevent creation of the task
        if (!RepoPermission::allowedAction($taskParams['action'])) {
            throw new Exception('You are not allowed to execute this action');
        }

        if (empty($taskParams['target'])) {
            throw new Exception('No target has been specified');
        }

        // Validate and clean the target definition
        $taskParams['target'] = $targetController->validate($taskParams['target']);

        // A dynamic task is only useful when scheduled
        if (empty($taskParams['schedule']['scheduled']) or $taskParams['schedule']['scheduled'] != 'true') {
            throw new Exception('A task targeting all repositories must be scheduled');
        }

        // Check scheduling parameters
        Param\Schedule::check($taskParams['schedule']);

        // Environment is required for the 'env' action
        if ($taskParams['action'] == 'env') {
            Param\Environment::check($taskParams['env'] ?? []);

            // Check if the env is protected
            foreach ($taskParams['env'] as $env) {
                if ($envController->isProtected($env)) {
                    throw new Exception('Environment ' . $env . ' is protected and cannot be moved');
                }
            }
        }

        // Environment is optional for the 'update' action
        if ($taskParams['action'] == 'update' and !empty($taskParams['env'])) {
            Param\Environment::check($taskParams['env']);
        }

        // Check GPG parameters
        if (in_array($taskParams['action'], ['update', 'rebuild'])) {
            Param\GpgSign::check($taskParams['gpg-sign'] ?? 'false');
        }

        if ($taskParams['action'] == 'update') {
            Param\GpgCheck::check($taskParams['gpg-check'] ?? 'false');
        }

        History::set('Scheduling task: ' . $taskParams['action'] . ' on <span class="label-white">' . Target::describe($taskParams['target']) . '</span>');

        return $taskParams;
    }
}
