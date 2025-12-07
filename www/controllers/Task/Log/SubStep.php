<?php

namespace Controllers\Task\Log;

use JsonException;

class SubStep extends Log
{
    private $stepController;
    private $logController;

    public function __construct(int $taskId)
    {
        parent::__construct($taskId);

        // Override parent model with the SubStep model
        $this->model = new \Models\Task\Log\SubStep($taskId);

        $this->stepController = new Step($taskId);
        $this->logController = new \Controllers\Log\Log();
    }

    /**
     *  Add a new sub-step to the latest step
     */
    public function new(string $identifier, string $title = '', string $note = '') : void
    {
        /**
         *  First, close previous sub-step if any
         */
        $this->completed();

        /**
         *  Get latest step Id
         */
        $stepId = $this->stepController->getLatestStepId($this->taskId);

        /**
         *  Create new substep in database
         */
        $this->model->new($stepId, $identifier, $title, $note);

        unset($stepId, $identifier, $title, $note);
    }

    /**
     *  Set latest sub-step as completed
     */
    public function completed(string|null $message = '', string|null $identifier = '') : void
    {
        /**
         *  Get latest step Id
         */
        $stepId = $this->stepController->getLatestStepId($this->taskId);

        // If there was no step, return
        if (empty($stepId)) {
            return;
        }

        /**
         *  If no identifier is provided, get latest sub-step key name, otherwise use the provided identifier
         */
        if (!empty($identifier)) {
            // Get substep Id by identifier
            $substepId = $this->getSubStepIdByIdentifier($stepId, $identifier);
        } else {
            // Get latest substep Id
            $substepId = $this->getLatestSubStepId($stepId);
        }

        /**
         *  First, add message to output, if any
         */
        $this->output($message);

        /**
         *  If there was substeps, mark latest substep as completed in database
         */
        if (!empty($substepId)) {
            $this->model->status($substepId, 'completed');
        }
    }

    /**
     *  Set a warning message for the latest sub-step
     */
    public function warning(string $message, string|null $outputType = null) : void
    {
        /**
         *  Get latest step Id
         */
        $stepId = $this->stepController->getLatestStepId($this->taskId);

        /**
         *  Get latest sub-step Id
         */
        $substepId = $this->getLatestSubStepId($stepId);

        /**
         *  Add warning message to output
         */
        $this->output($message, 'warning');

        /**
         *  If there was substeps, mark latest substep as warning in database
         */
        if (!empty($substepId)) {
            $this->model->status($substepId, 'warning');
        }
    }

    /**
     *  Set the latest sub-step as error
     */
    public function error(string $message) : void
    {
        /**
         *  Get latest step Id
         */
        $stepId = $this->stepController->getLatestStepId($this->taskId);

        /**
         *  Get latest sub-step Id
         */
        $substepId = $this->getLatestSubStepId($stepId);

        /**
         *  Add error message to output
         */
        $this->output($message, 'error');

        /**
         *  If there was substeps, mark latest substep as error in database
         */
        if (!empty($substepId)) {
            $this->model->status($substepId, 'error');
        }
    }

    /**
     *  Set the latest sub-step as stopped
     */
    public function stopped() : void
    {
        /**
         *  Get latest step Id
         */
        $stepId = $this->stepController->getLatestStepId($this->taskId);

        /**
         *  Get latest sub-step Id
         */
        $substepId = $this->getLatestSubStepId($stepId);

        /**
         *  If there was substeps, mark latest substep as stopped in database
         */
        if (!empty($substepId)) {
            $this->model->status($substepId, 'stopped');
        }
    }

    /**
     *  Add output to the latest sub-step
     */
    public function output(string $message, string|null $type = null) : void
    {
        $output = [];

        if (empty($message)) {
            return;
        }

        if (empty($type)) {
            $type = 'info';
        }

        /**
         *  Get latest step Id
         */
        $stepId = $this->stepController->getLatestStepId($this->taskId);

        /**
         *  Get latest sub-step Id
         */
        $substepId = $this->getLatestSubStepId($stepId);

        /**
         *  If there was no substep, return
         */
        if (empty($substepId)) {
            return;
        }

        /**
         *  Get current output
         */
        $currentOutput = $this->getOutput($substepId);

        /**
         *  If there was output, decode it
         */
        if (!empty($currentOutput)) {
            try {
                // Decode JSON output
                $output = json_decode($currentOutput, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                $this->logController->log('error', 'Task logging', 'Could not decode output for substep ' . $substepId);
                return;
            }
        }

        /**
         *  Add new output
         */
        $output[] = [
            'time' => microtime(true),
            'type' => $type,
            'message' => $message
        ];

        /**
         *  Encode output
         */
        try {
            $output = json_encode($output, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $this->logController->log('error', 'Task logging', 'Could not encode output for substep ' . $substepId);
            return;
        }

        /**
         *  Write output to database
         */
        $this->writeOutput($substepId, $output);

        unset($stepId, $substepId, $output);
    }

    /**
     *  Get sub-steps by step Id
     */
    public function get(int $stepId) : array
    {
        return $this->model->get($stepId);
    }

    /**
     *  Return the latest sub-step Id
     */
    public function getLatestSubStepId(int $stepId) : int|null
    {
        return $this->model->getLatestSubStepId($stepId);
    }

    /**
     *  Return the sub-step Id by identifier
     */
    public function getSubStepIdByIdentifier(int $stepId, string $identifier) : int
    {
        return $this->model->getSubStepIdByIdentifier($stepId, $identifier);
    }

    /**
     *  Return the output of the latest sub-step
     */
    private function getOutput(int $substepId) : string|null
    {
        return $this->model->getOutput($substepId);
    }

    /**
     *  Write substep output to the database
     */
    private function writeOutput(int $substepId, string $output) : void
    {
        $this->model->writeOutput($substepId, $output);
    }
}
