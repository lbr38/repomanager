<?php

namespace Models;

use Exception;

class Notification extends Model
{
    public function __construct()
    {
        /**
         *  Connect to the database
         */
        $this->getConnection('main');
    }

    /**
     *  Get all notifications
     */
    public function get()
    {
        $notifications = array();

        try {
            $result = $this->db->query("SELECT * FROM notifications");
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $notifications[] = $row;
        }

        return $notifications;
    }

    /**
     *  Get all unread notifications
     */
    public function getUnread()
    {
        $notifications = array();

        try {
            $result = $this->db->query("SELECT Id, Title, Message FROM notifications WHERE Status = 'new'");
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $notifications[] = $row;
        }

        return $notifications;
    }

    /**
     *  Add a notification
     */
    public function add(string $id, string $title, string $message)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO notifications ('Id_notification', 'Title', 'Message', 'Status') VALUES (:id, :title, :message, 'new')");
            $stmt->bindValue(':id', $id);
            $stmt->bindValue(':title', $title);
            $stmt->bindValue(':message', $message);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Mark a notification as read
     */
    public function acquit(string $id)
    {
        try {
            $stmt = $this->db->prepare("UPDATE notifications SET Status = 'acquitted' WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Check if a notification exists
     */
    public function exists(string $id)
    {
        try {
            $stmt = $this->db->prepare("SELECT Id FROM notifications WHERE Id_notification = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        if ($this->db->isempty($result)) {
            return false;
        }

        return true;
    }
}
