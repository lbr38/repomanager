<?php
/**
 *  Create deb mirror (Debian bookworm and bookworm-updates distributions, contrib component, amd64 architecture)
 */
$rawParams['action'] = 'create';
$rawParams['domain'] = 'mirror';
$rawParams['package-type'] = 'deb';
$rawParams['name'] = 'debian';
$rawParams['source'] = 'debian';
$rawParams['dist'] = ['bookworm', 'bookworm-updates'];
$rawParams['components'] = ['contrib'];
$rawParams['arch'] = ['amd64'];
$rawParams['gpg-check'] = 'true';
$rawParams['env'] = ['pprd'];
$rawParams['description'] = 'CI - create deb mirror';
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
