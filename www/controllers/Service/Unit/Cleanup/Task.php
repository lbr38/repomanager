<?php

namespace Controllers\Service\Unit\Cleanup;

use Exception;

class Task extends \Controllers\Service\Service
{
    private $taskController;

    public function __construct(string $unit)
    {
        parent::__construct($unit);

        $this->taskController = new \Controllers\Task\Task();
    }

    /**
     *  Clean old tasks
     */
    public function run() : void
    {
        parent::log('Cleaning old tasks');

        $this->taskController->clean();

        parent::log('Tasks cleaned successfully');
    }
}
