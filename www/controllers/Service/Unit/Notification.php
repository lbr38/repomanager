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

        try {
            $this->notificationController->retrieve();
            parent::log('Notifications retrieved');
        } catch (Exception $e) {
            parent::logError('Error retrieving notifications: ' . $e->getMessage());
        }
    }
}
