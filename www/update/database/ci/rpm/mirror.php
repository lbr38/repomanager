<?php
/**
 *  Mirror rpm repo
 */
$rawParams['action'] = 'create';
$rawParams['package-type'] = 'rpm';
$rawParams['repo-type'] = 'mirror';
$rawParams['source'] = 'centos-extras-common';
$rawParams['alias'] = 'centos-extras-common';
$rawParams['releasever'] = '9';
$rawParams['env'] = ['pprd'];
$rawParams['description'] = 'CI - create centos-extras-common repo';
$rawParams['group'] = '';
$rawParams['gpg-check'] = 'false';
$rawParams['gpg-sign'] = 'true';
$rawParams['arch'] = ['x86_64', 'noarch'];
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
