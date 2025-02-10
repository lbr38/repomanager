<?php
/**
 *  Mirror rpm repo
 */
$rawParams['action'] = 'create';
$rawParams['package-type'] = 'rpm';
$rawParams['repo-type'] = 'mirror';
$rawParams['source'] = 'centos9-extras-common';
$rawParams['alias'] = 'centos9-extras-common';
$rawParams['releasever'] = '9';
$rawParams['env'] = 'pprd';
$rawParams['description'] = 'CI - create centos9-extras-common repo';
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

$stmt = $this->db->prepare("INSERT INTO tasks (Type, Raw_params, Status) VALUES ('immediate', :rawParams, 'queued');");
$stmt->bindParam(':rawParams', json_encode($rawParams));
$stmt->execute();
