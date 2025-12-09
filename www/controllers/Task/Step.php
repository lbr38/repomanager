<?php

namespace Controllers\Task;

use Exception;
use JsonException;

class Step
{
    private $taskId;
    private $content;
    private $taskLogController;

    public function __construct(int $taskId)
    {
        $this->taskLogController = new \Controllers\Task\Log\Log($taskId);
        $this->taskId = $taskId;

        /**
         *  If task log does not exist, throw an exception
         */
        if (!file_exists(MAIN_LOGS_DIR . '/repomanager-task-' . $this->taskId . '-log.db')) {
            throw new Exception('No log file found for this task.');
        }

        /**
         *  Load task log content
         */
        $this->content = $this->taskLogController->getContent();
    }

    /**
     *  Get and return the steps name, status and HTML content
     */
    public function getSteps() : string
    {
        try {
            // For the needs of the include file
            $taskId = $this->taskId;

            if (!empty($this->content['steps'])) {
                foreach ($this->content['steps'] as $stepIdentifier => $step) {
                    // Get HTML content of the step
                    ob_start();
                    include(ROOT . '/views/includes/containers/tasks/log/step.inc.php');
                    $content = ob_get_clean();

                    // Add HTML content to the steps array
                    $this->content['steps'][$stepIdentifier]['html'] = $content;
                }
            }

            try {
                $content = json_encode($this->content, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new Exception('failed to encode log content: ' . $e->getMessage());
            }

            return $content;
        } catch (Exception $e) {
            throw new Exception('Cannot retrieve task steps: ' . $e->getMessage());
        }
    }

    /**
     *  Get and return the content of a specific step
     */
    public function getStepContent(string $stepIdentifier, bool $autoscroll) : string
    {
        try {
            $data = '';

            /**
             *  If step does not exist in the log, throw an exception
             */
            if (!isset($this->content['steps'][$stepIdentifier])) {
                throw new Exception('step does not exist in the log.');
            }

            // For the needs of the include file
            $taskId = $this->taskId;
            $step = $this->content['steps'][$stepIdentifier];

            ob_start();
            include(ROOT . '/views/includes/containers/tasks/log/step-content.inc.php');
            $data = ob_get_clean();

            return $data;
        } catch (Exception $e) {
            throw new Exception('Cannot retrieve step content: ' . $e->getMessage());
        }
    }

    /**
     *  Get task log lines
     */
    public function getLogLines(string $step, string $direction, string|null $key = null) : string
    {
        try {
            /**
             *  If step does not exist in the log, throw an exception
             */
            if (!array_key_exists($step, $this->content['steps'])) {
                throw new Exception('no step ' . $step . ' found in the log.');
            }

            /**
             *  If a substep key is provided
             */
            if (!empty($key)) {
                // If the substep does not exist in the log, throw an exception
                if (!array_key_exists($key, $this->content['steps'][$step]['substeps'])) {
                    throw new Exception('no substep ' . $key . ' found in the log.');
                }

                // If the substep key is the first one, return an empty content (because we cannot go up anymore)
                if ($key == array_key_first($this->content['steps'][$step]['substeps'])) {
                    return '';
                }
            }

            /**
             *  If direction is 'top', get the very first 30 substeps
             */
            if ($direction == 'top') {
                $substeps = array_slice($this->content['steps'][$step]['substeps'], 0, 30, true);
            }

            /**
             *  If direction is 'up', get the 10 substeps before the provided substep key
             */
            if ($direction == 'up') {
                $substeps = array_slice($this->content['steps'][$step]['substeps'], 0, array_search($key, array_keys($this->content['steps'][$step]['substeps'])), true);
                // Only keep the 10 previous substeps
                $substeps = array_slice($substeps, -10, 10, true);
            }

            /**
             *  If direction is 'down', get the 10 substeps after the provided substep key
             */
            if ($direction == 'down') {
                $substeps = array_slice($this->content['steps'][$step]['substeps'], array_search($key, array_keys($this->content['steps'][$step]['substeps'])) + 1, 10, true);
            }

            /**
             *  If direction is 'bottom', get the very last 30 substeps
             */
            if ($direction == 'bottom') {
                $substeps = array_slice($this->content['steps'][$step]['substeps'], -30, 30, true);
            }

            /**
             *  If there is no substep to display, return an empty string
             */
            if (empty($substeps)) {
                return '';
            }

            /**
             *  Load each substep content
             */
            ob_start();

            foreach ($substeps as $substepKey => $substep) {
                $substepTitle    = $substep['title'];
                $substepNote     = $substep['note'];
                $substepStatus   = $substep['status'];
                $substepOutput   = $substep['output'];
                $substepStart    = $substep['start'];
                $substepDuration = $substep['duration'];

                // Include substep template
                include(ROOT . '/views/includes/containers/tasks/log/substep.inc.php');
            }

            return ob_get_clean();
        } catch (Exception $e) {
            throw new Exception('Cannot load more logs: ' . $e->getMessage());
        }
    }
}
