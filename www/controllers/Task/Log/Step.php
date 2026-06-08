<?php

namespace Controllers\Task\Log;

class Step extends Log
{
    public function __construct(int $taskId)
    {
        parent::__construct($taskId);

        // Override parent model with the SubStep model
        $this->model = new \Models\Task\Log\Step($taskId);
    }

    /**
     *  Add a new step to the log
     */
    public function new(string $identifier, string $title) : void
    {
        $this->model->new($identifier, $title);
    }

    /**
     *  Set the latest step with no status (none)
     */
    public function none(string $message = '') : void
    {
        // Get latest step Id from database
        $stepId = $this->getLatestStepId();

        // Mark step as completed in database
        $this->model->status($stepId, 'none', $message);
    }

    /**
     *  Set the latest step as completed
     */
    public function completed(string $message = '') : void
    {
        $status = 'completed';

        // Get latest step Id from database
        $stepId = $this->getLatestStepId();

        // Check if there was a substep in warning status
        if ($this->hasWarningSubSteps($stepId)) {
            // If there was a substep in warning status, mark step as warning
            $status = 'warning';
        }

        // Mark step as completed in database
        $this->model->status($stepId, $status, $message);
    }

    /**
     *  Set the latest step as error
     */
    public function error(string $message = '') : void
    {
        // Get latest step Id from database
        $stepId = $this->getLatestStepId();

        // Mark step as error in database
        $this->model->status($stepId, 'error', $message);
    }

    /**
     *  Set the latest step as stopped
     */
    public function stopped() : void
    {
        // Get latest step Id from database
        $stepId = $this->getLatestStepId();

        // Mark step as stopped in database
        $this->model->status($stepId, 'stopped');
    }

    /**
     *  Get steps for the provided task ID
     */
    public function get() : array
    {
        return $this->model->get();
    }

    /**
     *  Return the latest step ID for the provided task ID
     */
    public function getLatestStepId() : int|null
    {
        return $this->model->getLatestStepId();
    }

    /**
     *  Return true if there is at least one sub-step in warning status for the provided step ID, false otherwise
     */
    public function hasWarningSubSteps(int $stepId): bool
    {
        return $this->model->hasWarningSubSteps($stepId);
    }
}
