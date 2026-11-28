<?php
/**
 *  Create alpine mirror (Alpine Linux 3.3 community repository, x86 architecture: about 540 packages, 1.1 GB, does not change anymore)
 *  The index signature is verified with the Alpine Linux keys of the source repository
 */
$rawParams['action'] = 'create';
$rawParams['domain'] = 'mirror';
$rawParams['package-type'] = 'alpine';
$rawParams['name'] = 'alpine';
$rawParams['source'] = 'alpine';
$rawParams['branch'] = 'v3.3';
$rawParams['repos'] = ['community'];
$rawParams['arch'] = ['x86'];
$rawParams['gpg-check'] = 'true';
$rawParams['env'] = ['pprd'];
$rawParams['description'] = 'CI - create alpine mirror';
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
