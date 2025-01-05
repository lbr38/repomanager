<?php

namespace Controllers\Task\Log;

use Exception;
use Datetime;

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
        $this->model->new($this->taskId, $identifier, $title);
    }

    /**
     *  Set the latest step with no status (none)
     */
    public function none(string $message = '') : void
    {
        /**
         *  Get latest step Id from database
         */
        $stepId = $this->getLatestStepId($this->taskId);

        /**
         *  Mark step as completed in database
         */
        $this->model->status($stepId, 'none', $message);
    }

    /**
     *  Set the latest step as completed
     */
    public function completed(string $message = '') : void
    {
        /**
         *  Get latest step Id from database
         */
        $stepId = $this->getLatestStepId($this->taskId);

        /**
         *  Mark step as completed in database
         */
        $this->model->status($stepId, 'completed', $message);
    }

    /**
     *  Set the latest step as error
     */
    public function error(string $message = '') : void
    {
        /**
         *  Get latest step Id from database
         */
        $stepId = $this->getLatestStepId($this->taskId);

        /**
         *  Mark step as error in database
         */
        $this->model->status($stepId, 'error', $message);
    }

    /**
     *  Set the latest step as stopped
     */
    public function stopped() : void
    {
        /**
         *  Get latest step Id from database
         */
        $stepId = $this->getLatestStepId($this->taskId);

        /**
         *  Mark step as stopped in database
         */
        $this->model->status($stepId, 'stopped');
    }

    /**
     *  Get steps for the provided task ID
     */
    public function get(int $taskId) : array
    {
        return $this->model->get($taskId);
    }

    /**
     *  Return the latest step ID for the provided task ID
     */
    public function getLatestStepId(int $taskId) : int
    {
        return $this->model->getLatestStepId($taskId);
    }
}
