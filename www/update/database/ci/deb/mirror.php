<?php
/**
 *  Mirror deb repo
 */
$rawParams['repo-id'] = 'debian|bookworm|contrib';
$rawParams['action'] = 'create';
$rawParams['package-type'] = 'deb';
$rawParams['repo-type'] = 'mirror';
$rawParams['source'] = 'debian';
$rawParams['alias'] = 'debian';
$rawParams['dist'] = 'bookworm';
$rawParams['section'] = 'contrib';
$rawParams['env'] = 'pprd';
$rawParams['description'] = 'CI - create contrib repo';
$rawParams['group'] = '';
$rawParams['gpg-check'] = 'false';
$rawParams['gpg-sign'] = 'true';
$rawParams['arch'] = ['amd64', 'armhf'];
$rawParams['schedule'] = [
    'scheduled' => 'false',
    'schedule-type' => '',
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
