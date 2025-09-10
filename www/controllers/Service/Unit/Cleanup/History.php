<?php

namespace Controllers\Service\Unit\Cleanup;

use Exception;

class History extends \Controllers\Service\Service
{
    private $historyController;

    public function __construct(string $unit)
    {
        parent::__construct($unit);

        $this->historyController = new \Controllers\History\History();
    }

    /**
     *  Clean old history logs
     */
    public function run() : void
    {
        parent::log('Cleaning history logs...');

        $this->historyController->cleanup(365);

        parent::log('History logs cleaned successfully');
    }
}
