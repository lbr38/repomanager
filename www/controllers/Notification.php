<?php

namespace Controllers;

use Exception;

class Notification
{
    private $model;

    public function __construct()
    {
        $this->model = new \Models\Notification();
    }

    /**
     *  Retrieve all notifications from github
     */
    public function retrieve()
    {
        $notifications = file_get_contents('https://raw.githubusercontent.com/lbr38/repomanager/stable/notifications/notifications.json');

        if ($notifications === false) {
            throw new Exception('Unable to retrieve notifications');
        }

        $notifications = json_decode($notifications, true);

        if (!empty($notifications)) {
            foreach ($notifications as $id => $notification) {
                $title = Common::validateData($notification['title']);
                $message = Common::validateData($notification['message']);

                /**
                 *  Insert notficiation in database if not already exists
                 */
                if (!$this->exists($id)) {
                    $this->add($id, $title, $message);
                }
            }
        }
    }

    /**
     *  Get all notifications
     */
    public function get()
    {
        return $this->model->get();
    }

    /**
     *  Get all unread notifications
     */
    public function getUnread()
    {
        return $this->model->getUnread();
    }

    /**
     *  Add a notification
     */
    public function add(string $id, string $title, string $message)
    {
        $this->model->add($id, $title, $message);
    }

    /**
     *  Mark a notification as read
     */
    public function acquit(string $id)
    {
        if (!is_numeric($id)) {
            throw new Exception('Invalid notification id');
        }

        $this->model->acquit($id);
    }

    /**
     *  Check if a notification exists
     */
    public function exists(string $id)
    {
        return $this->model->exists($id);
    }
}
