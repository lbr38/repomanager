<?php
/**
 *  Local deb repo
 */
$rawParams['repo-id'] = 'local-deb-for-test-user1|bookworm|main';
$rawParams['action'] = 'create';
$rawParams['package-type'] = 'deb';
$rawParams['repo-type'] = 'local';
$rawParams['source'] = '';
$rawParams['alias'] = 'local-deb-for-test-user1';
$rawParams['dist'] = 'bookworm';
$rawParams['section'] = 'main';
$rawParams['env'] = ['pprd'];
$rawParams['description'] = 'CI - test API - local deb repo for test-user1';
$rawParams['group'] = '';
$rawParams['gpg-check'] = 'false';
$rawParams['gpg-sign'] = 'false';
$rawParams['arch'] = ['amd64'];
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
