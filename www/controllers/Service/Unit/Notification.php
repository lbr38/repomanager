<?php

namespace Controllers\Service\Unit;

use Exception;

class Notification extends \Controllers\Service\Service
{
    private $notificationController;

    public function __construct(string $unit)
    {
        parent::__construct($unit);

        $this->notificationController = new \Controllers\Notification();
    }

    /**
     *  Get notifications
     */
    public function get() : void
    {
        parent::log('Getting notifications...');

        $this->notificationController->retrieve();

        parent::log('Notifications retrieved');
    }
}
