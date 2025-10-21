<?php

namespace Models\Websocket;

use Exception;
use \Controllers\Database\Log as DbLog;

class WebsocketServer extends \Models\Model
{
    public function __construct()
    {
        /**
         *  Open database
         */
        $this->getConnection('ws');
    }

    /**
     *  Clean ws connections from database
     */
    public function cleanWsConnections()
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM ws_connections");
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Add new ws connection in database
     */
    public function newWsConnection(int $connectionId)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO ws_connections ('Connection_id', 'Authenticated') VALUES (:id, 'false')");
            $stmt->bindValue(':id', $connectionId);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Set websocket connection type
     */
    public function setWsConnectionType(int $connectionId, string $type)
    {
        try {
            $stmt = $this->db->prepare("UPDATE ws_connections SET Type = :type WHERE Connection_id = :id");
            $stmt->bindValue(':id', $connectionId);
            $stmt->bindValue(':type', $type);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Update ws connection in database
     */
    public function updateWsConnection(int $connectionId, int $hostId, string $authenticated)
    {
        try {
            $stmt = $this->db->prepare("UPDATE ws_connections SET Id_host = :hostId, Authenticated = :authenticated WHERE Connection_id = :connectionId");
            $stmt->bindValue(':hostId', $hostId);
            $stmt->bindValue(':authenticated', $authenticated);
            $stmt->bindValue(':connectionId', $connectionId);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Return all authenticated websocket connections from database
     */
    public function getAuthenticatedWsConnections()
    {
        $connections = [];

        try {
            $stmt = $this->db->prepare("SELECT * FROM ws_connections WHERE Authenticated = 'true'");
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $connections[] = $row;
        }

        return $connections;
    }

    /**
     *  Return all websocket connections from database
     */
    public function getWsConnections(string|null $type = null)
    {
        $connections = [];

        try {
            // If a connection type is provided, return only connections of that type
            if (!empty($type)) {
                $stmt = $this->db->prepare("SELECT * FROM ws_connections WHERE Type = :type");
                $stmt->bindValue(':type', $type);
            } else {
                $stmt = $this->db->prepare("SELECT * FROM ws_connections");
            }

            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $connections[] = $row;
        }

        return $connections;
    }

    /**
     *  Return websocket connection Id by host Id
     */
    public function getWsConnectionIdByHostId(int $hostId)
    {
        $connectionId = '';

        try {
            $stmt = $this->db->prepare("SELECT Connection_id FROM ws_connections WHERE Id_host = :hostId");
            $stmt->bindValue(':hostId', $hostId);
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        $connectionId = '';

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $connectionId = $row['Connection_id'];
        }

        return $connectionId;
    }

    /**
     *  Delete ws connection from database
     */
    public function deleteWsConnection(int $connectionId)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM ws_connections WHERE Connection_id = :id");
            $stmt->bindValue(':id', $connectionId);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }
}
