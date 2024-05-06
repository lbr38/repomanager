<?php
/**
 *  Point environment to rpm repo
 */
$rawParams['action'] = 'env';
$rawParams['snap-id'] = '1';
$rawParams['env-id'] = '';
$rawParams['env'] = 'pprd';
$rawParams['description'] = 'CI - point env';
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
