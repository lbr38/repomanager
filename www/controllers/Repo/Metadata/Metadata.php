<?php

namespace Controllers\Repo\Metadata;

class Metadata
{
    protected $taskLogStepController;
    protected $taskLogSubStepController;

    public function __construct(int $taskId)
    {
        $this->taskLogStepController = new \Controllers\Task\Log\Step($taskId);
        $this->taskLogSubStepController = new \Controllers\Task\Log\SubStep($taskId);
    }
}
