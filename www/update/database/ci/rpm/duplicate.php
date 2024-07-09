<?php
/**
 *  Duplicate rpm repo
 */
$rawParams['action'] = 'duplicate';
$rawParams['snap-id'] = '1';
$rawParams['env-id'] = '';
$rawParams['name'] = 'centos9-extras-common-copy';
$rawParams['env'] = 'pprd';
$rawParams['description'] = 'CI - duplicate repo';
$rawParams['group'] = '';
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
