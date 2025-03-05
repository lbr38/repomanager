<?php
/**
 *  Duplicate deb repo
 */
$rawParams['action'] = 'duplicate';
$rawParams['snap-id'] = '1';
$rawParams['env-id'] = '';
$rawParams['name'] = 'debian-copy';
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

try {
    $rawParams = json_encode($rawParams, JSON_THROW_ON_ERROR);
} catch (Exception $e) {
    throw new Exception('Error while encoding raw params to JSON: ' . $e->getMessage());
}

$stmt = $this->db->prepare("INSERT INTO tasks (Type, Raw_params, Status) VALUES ('immediate', :rawParams, 'queued');");
$stmt->bindParam(':rawParams', $rawParams);
$stmt->execute();
