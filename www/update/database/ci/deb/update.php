<?php
/**
 *  Update deb mirror repo
 */
$rawParams['action'] = 'update';
$rawParams['repo-id'] = '1';
$rawParams['snap-id'] = '1';
$rawParams['env-id'] = '';
$rawParams['arch'] = ['amd64', 'armhf'];
$rawParams['env'] = ['pprd'];
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

try {
    $rawParams = json_encode($rawParams, JSON_THROW_ON_ERROR);
} catch (Exception $e) {
    throw new Exception('Error while encoding raw params to JSON: ' . $e->getMessage());
}

$stmt = $this->db->prepare("INSERT INTO tasks (Type, Raw_params, Status) VALUES ('immediate', :rawParams, 'queued');");
$stmt->bindParam(':rawParams', $rawParams);
$stmt->execute();
