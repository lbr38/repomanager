<?php

namespace Models\History;

use Exception;

class Save extends \Models\Model
{
    public function __construct()
    {
        $this->getConnection('main');
    }

    /**
     *  Add new history line in database
     */
    public function save(string $userId, string $username, string $action, string $ip, string $ipForwarded, string $userAgent, string $state) : void
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO history ('Date', 'Time', 'Id_user', 'Username', 'Action', 'Ip', 'Ip_forwarded', 'User_agent', 'State') VALUES (:date, :time, :id, :username, :action, :ip, :ipForwarded, :userAgent, :state)");
            $stmt->bindValue(':date', date('Y-m-d'));
            $stmt->bindValue(':time', date('H:i:s'));
            $stmt->bindValue(':id', $userId);
            $stmt->bindValue(':username', $username);
            $stmt->bindValue(':action', $action);
            $stmt->bindValue(':ip', $ip);
            $stmt->bindValue(':ipForwarded', $ipForwarded);
            $stmt->bindValue(':userAgent', $userAgent);
            $stmt->bindValue(':state', $state);
            $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }
    }
}
