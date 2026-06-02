<?php

namespace Models\Task\Log;

class Log extends \Models\Model
{
    public function __construct(int $taskId)
    {
        $this->getConnection('task-log', $taskId);
    }
}
