<?php
/**
 *  Mirror rpm repo
 */
$rawParams['action'] = 'create';
$rawParams['package-type'] = 'rpm';
$rawParams['repo-type'] = 'mirror';
$rawParams['source'] = 'extras';
$rawParams['alias'] = 'extras';
$rawParams['releasever'] = '7';
$rawParams['env'] = 'pprd';
$rawParams['description'] = 'CI - create extras repo';
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

$stmt = $this->db->prepare("INSERT INTO tasks (Type, Raw_params, Status) VALUES ('immediate', :rawParams, 'new');");
$stmt->bindParam(':rawParams', json_encode($rawParams));
$stmt->execute();
