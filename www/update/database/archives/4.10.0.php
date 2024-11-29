<?php
/**
 *  4.10.0 update
 */

$hostDb = new \Models\Connection('hosts');

/**
 *  Drop 'Request_json' column from the ws_requests table
 */
if ($hostDb->columnExist('ws_requests', 'Request_json') === true) {
    $hostDb->exec("ALTER TABLE ws_requests DROP COLUMN Request_json");
    $hostDb->exec("VACUUM");
}

/**
 *  Drop 'Info_json' column from the ws_requests table
 */
if ($hostDb->columnExist('ws_requests', 'Info_json') === true) {
    $hostDb->exec("ALTER TABLE ws_requests DROP COLUMN Info_json");
    $hostDb->exec("VACUUM");
}

unset($hostDb);
