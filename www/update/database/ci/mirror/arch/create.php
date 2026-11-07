<?php
/**
 *  Create arch mirror (Arch Linux multilib and multilib-testing repositories, x86_64 architecture)
 *  Arch Linux official databases are not signed, packages signatures are checked by pacman on clients
 */
$rawParams['action'] = 'create';
$rawParams['domain'] = 'mirror';
$rawParams['package-type'] = 'arch';
$rawParams['name'] = 'archlinux';
$rawParams['source'] = 'archlinux';
$rawParams['repos'] = ['multilib', 'multilib-testing'];
$rawParams['arch'] = ['x86_64'];
$rawParams['gpg-check'] = 'false';
$rawParams['env'] = ['pprd'];
$rawParams['description'] = 'CI - create arch mirror';
$rawParams['tags'] = ['ci'];
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
} catch (JsonException $e) {
    throw new Exception('Error while encoding raw params to JSON: ' . $e->getMessage());
}

$stmt = $this->db->prepare("INSERT INTO tasks (Type, Raw_params, Status) VALUES ('immediate', :rawParams, 'queued');");
$stmt->bindParam(':rawParams', $rawParams);
$stmt->execute();
