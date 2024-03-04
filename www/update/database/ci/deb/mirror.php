<?php
/**
 *  Mirror deb repo
 */
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
$rawParams['repo-id'] = 'debian|bookworm|contrib';

$stmt = $this->db->prepare("INSERT INTO tasks (Type, Raw_params, Status) VALUES ('immediate', :rawParams, 'new');");
$stmt->bindParam(':rawParams', json_encode($rawParams));
$stmt->execute();
