<?php
$taskController = new \Controllers\Task\Task();
$repoController = new \Controllers\Repo\Repo();
$legacyLog = false;
$output = '';

/**
 *  Get the log file of the task
 */
try {
    if (!empty(__ACTUAL_URI__[2]) and (is_numeric(__ACTUAL_URI__[2]))) {
        $taskId = __ACTUAL_URI__[2];
    } else {
        // Retrieve latest task Id
        $taskId = $taskController->getLastTaskId();
    }

    // Get task info
    $taskInfo = $taskController->getById($taskId);

    // If the task has a log file (old format)
    if (!empty($taskInfo['Logfile']) and file_exists(MAIN_LOGS_DIR . '/' . $taskInfo['Logfile'])) {
        $logfile = $taskInfo['Logfile'];
        $output = file_get_contents(MAIN_LOGS_DIR . '/' . $logfile);
        $legacyLog = true;

    // If the task has a json log in database (new format)
    } else {
        $taskLogController = new \Controllers\Task\Log\Log($taskId);

        // Get raw params from the task
        $rawParams = json_decode($taskInfo['Raw_params'], true);
        $repoId = null;
        $snapId = null;
        $envId = null;

        // Get log content
        $content = $taskLogController->getContent();

        // If log content is empty
        if (empty($content)) {
            throw new Exception('No log found for this task.');
        }

        if (empty($rawParams['action'])) {
            throw new Exception('No action found in the task.');
        }

        /**
         *  Get repository info
         */

        // If the action is create, the repository info is in the raw params
        if ($rawParams['action'] == 'create') {
            $repoController->setType($rawParams['repo-type']);

            if (!empty($rawParams['source'])) {
                $repoController->setSource($rawParams['source']);
            }
            if (!empty($rawParams['alias'])) {
                $repoController->setName($rawParams['alias']);
            } else {
                $repoController->setName($rawParams['source']);
            }
            if (!empty($rawParams['dist'])) {
                $repoController->setDist($rawParams['dist']);
            }
            if (!empty($rawParams['section'])) {
                $repoController->setSection($rawParams['section']);
            }
            if (!empty($rawParams['releasever'])) {
                $repoController->setReleasever($rawParams['releasever']);
            }
            if (!empty($rawParams['arch'])) {
                $repoController->setArch($rawParams['arch']);
            }
            if (!empty($rawParams['packages-include'])) {
                $repoController->setPackagesToInclude($rawParams['packages-include']);
            }
            if (!empty($rawParams['packages-exclude'])) {
                $repoController->setPackagesToExclude($rawParams['packages-exclude']);
            }
            if (!empty($rawParams['package-type'])) {
                $repoController->setPackageType($rawParams['package-type']);
            }
            if (!empty($rawParams['gpg-check'])) {
                $repoController->setGpgCheck($rawParams['gpg-check']);
            }
            if (!empty($rawParams['gpg-sign'])) {
                $repoController->setGpgSign($rawParams['gpg-sign']);
            }
            if (!empty($rawParams['description'])) {
                $repoController->setDescription($rawParams['description']);
            }
            if (!empty($rawParams['group'])) {
                $repoController->setGroup($rawParams['group']);
            }

        // Otherwise, we get the repository info from the database
        } else {
            if (!empty($rawParams['repo-id'])) {
                $repoId = $rawParams['repo-id'];
            }
            if (!empty($rawParams['snap-id'])) {
                $snapId = $rawParams['snap-id'];
            }
            // If the action is removeEnv, the environment has been removed, so it
            if ($rawParams['action'] != 'removeEnv') {
                if (!empty($rawParams['env-id'])) {
                    $envId = $rawParams['env-id'];
                }
            }

            $repoController->getAllById($repoId, $snapId, $envId);
        }

        /**
         *  Include table template for the task
         */
        ob_start();
        include_once(ROOT . '/views/templates/tasks/' . $rawParams['action'] . '.inc.php');
        $output .= ob_get_clean();

        /**
         *  The container which will contain all the steps
         */
        $output .= '<div class="steps-container" task-id="' . $taskId . '">';

        /**
         *  Include steps
         */
        foreach ($content['steps'] as $stepIdentifier => $step) {
            ob_start();
            include(ROOT . '/views/includes/containers/tasks/log/step.inc.php');
            include(ROOT . '/views/includes/containers/tasks/log/step-content.inc.php');
            $output .= ob_get_clean();
        }

        $output .= '</div>';
    }
} catch (Exception $e) {
    $output = '<p class="note">' . $e->getMessage() . '</p>';
}
