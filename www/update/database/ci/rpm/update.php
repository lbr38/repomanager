<?php
/**
 *  Update rpm mirror repo
 */
$rawParams['action'] = 'update';
$rawParams['snap-id'] = '1';
$rawParams['env-id'] = '';
$rawParams['only-sync-difference'] = 'true';
$rawParams['arch'] = ['x86_64', 'noarch'];
$rawParams['env'] = 'pprd';
$rawParams['gpg-check'] = 'false';
$rawParams['gpg-sign'] = 'true';
$rawParams['schedule'] = [
    'scheduled' => 'false',
    'schedule-type' => '',
    'schedule-frequency' => '',
    'schedule-day' => [],
    'schedule-date' => '',
    'schedule-time' => '',
    'schedule-notify-error' => '',
    'schedule-notify-success' => '',
    'schedule-reminder' => [],
    'schedule-recipient' => ['']
];

$stmt = $this->db->prepare("INSERT INTO tasks (Type, Raw_params, Status) VALUES ('immediate', :rawParams, 'new');");
$stmt->bindParam(':rawParams', json_encode($rawParams));
$stmt->execute();
