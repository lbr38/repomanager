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
     *  $closePrevious: close the previous sub-step, unless sub-steps run in parallel (e.g. parallel downloads),
     *  in which case each sub-step must be closed with its identifier
     */
    public function new(string $identifier, string $title = '', string $note = '', bool $closePrevious = true) : void
    {
        // First, close previous sub-step if not already closed
        if ($closePrevious) {
            $this->completed();
        }

        // Get latest step Id
        $stepId = $this->stepController->getLatestStepId();

        // Create new substep in database
        $this->model->new($stepId, $identifier, $title, $note);

        unset($stepId, $identifier, $title, $note);
    }

    /**
     *  Set latest sub-step as completed
     */
    public function completed(string|null $message = '', string|null $identifier = '') : void
    {
        // Get latest step Id
        $stepId = $this->stepController->getLatestStepId();

        // If there was no step, return
        if (empty($stepId)) {
            return;
        }

        // If no identifier is provided, get latest sub-step, otherwise use the provided identifier
        $substepId = $this->getSubStepId($stepId, $identifier);

        // First, add message to output, if any
        $this->output($message, null, $identifier);

        // If there was substeps, mark latest substep as completed in database
        if (!empty($substepId)) {
            // Get substep status
            $status = $this->getStatus($substepId);

            // If substep has no status or is still running, mark it as completed
            if (is_null($status) or $status == 'running') {
                $this->model->status($substepId, 'completed');
            }
        }
    }

    /**
     *  Set a warning message for the latest sub-step, or for the sub-step with the provided identifier
     */
    public function warning(string $message, string $identifier = '') : void
    {
        // Get latest step Id
        $stepId = $this->stepController->getLatestStepId();

        // Get sub-step Id
        $substepId = $this->getSubStepId($stepId, $identifier);

        // Add warning message to output
        $this->output($message, 'warning', $identifier);

        // If there was substeps, mark latest substep as warning in database
        if (!empty($substepId)) {
            $this->model->status($substepId, 'warning');
        }
    }

    /**
     *  Set the latest sub-step as error
     */
    public function error(string $message) : void
    {
        // Get latest step Id
        $stepId = $this->stepController->getLatestStepId();

        // Get latest sub-step Id
        $substepId = $this->getLatestSubStepId($stepId);

        // Add error message to output
        $this->output($message, 'error');

        // If there was substeps, mark latest substep as error in database
        if (!empty($substepId)) {
            $this->model->status($substepId, 'error');
        }

        $this->stopRunning($stepId);
    }

    /**
     *  Set the latest sub-step as stopped
     */
    public function stopped() : void
    {
        // Get latest step Id
        $stepId = $this->stepController->getLatestStepId();

        // Get latest sub-step Id
        $substepId = $this->getLatestSubStepId($stepId);

        // If there was substeps, mark latest substep as stopped in database
        if (!empty($substepId)) {
            $this->model->status($substepId, 'stopped');
        }

        $this->stopRunning($stepId);
    }

    /**
     *  Mark the sub-steps still running in parallel of the latest one (e.g. parallel downloads) as stopped,
     *  when the task ends with an error or is stopped
     */
    private function stopRunning(int|null $stepId) : void
    {
        if (empty($stepId)) {
            return;
        }

        foreach ($this->model->getRunningIds($stepId) as $substepId) {
            $this->model->status($substepId, 'stopped');
        }
    }

    /**
     *  Add output to the latest sub-step, or to the sub-step with the provided identifier
     */
    public function output(string $message, string|null $type = null, string|null $identifier = '') : void
    {
        if (empty($message)) {
            return;
        }

        if (empty($type)) {
            $type = 'info';
        }

        $this->updateOutput(function (array $output) use ($message, $type) {
            $output[] = [
                'time' => microtime(true),
                'type' => $type,
                'message' => $message
            ];

            return $output;
        }, $identifier);
    }

    /**
     *  Set the progress message of the latest sub-step: unlike output(), it replaces the previous progress message
     *  so that a long running sub-step can display its progress frequently without growing its output
     *  A null message removes the progress message (e.g. once the sub-step is done and has output its summary)
     */
    public function progress(string|null $message) : void
    {
        $this->updateOutput(function (array $output) use ($message) {
            $output = array_values(array_filter($output, function ($entry) {
                return empty($entry['live']);
            }));

            if (!empty($message)) {
                $output[] = [
                    'time' => microtime(true),
                    'type' => 'note',
                    'message' => $message,
                    'live' => true
                ];
            }

            return $output;
        });
    }

    /**
     *  Read the output of the latest sub-step (or of the sub-step with the provided identifier), modify it with the callback and write it back
     */
    private function updateOutput(callable $callback, string|null $identifier = '') : void
    {
        $output = [];

        // Get latest step Id
        $stepId = $this->stepController->getLatestStepId();

        // If there was no step, return
        if (empty($stepId)) {
            return;
        }

        // Get sub-step Id
        $substepId = $this->getSubStepId($stepId, $identifier);

        // If there was no substep, return
        if (empty($substepId)) {
            return;
        }

        // Get current output
        $currentOutput = $this->getOutput($substepId);

        // If there was output, decode it
        if (!empty($currentOutput)) {
            try {
                // Decode JSON output
                $output = json_decode($currentOutput, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                $this->logController->log('error', 'Task logging', 'Could not decode output for substep ' . $substepId);
                return;
            }
        }

        $output = $callback($output);

        // Encode output
        try {
            $output = json_encode($output, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $this->logController->log('error', 'Task logging', 'Could not encode output for substep ' . $substepId);
            return;
        }

        // Write output to database
        $this->writeOutput($substepId, $output);
    }

    /**
     *  Get sub-steps by step Id
     */
    public function get(int $stepId) : array
    {
        return $this->model->get($stepId);
    }

    /**
     *  Return substep status
     */
    private function getStatus(int $substepId): string|null
    {
        return $this->model->getStatus($substepId);
    }

    /**
     *  Return the latest sub-step Id
     */
    public function getLatestSubStepId(int $stepId) : int|null
    {
        return $this->model->getLatestSubStepId($stepId);
    }

    /**
     *  Return the Id of the sub-step with the provided identifier, or of the latest sub-step if no identifier is provided
     */
    private function getSubStepId(int $stepId, string|null $identifier) : int|null
    {
        if (!empty($identifier)) {
            return $this->getSubStepIdByIdentifier($stepId, $identifier);
        }

        return $this->getLatestSubStepId($stepId);
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
