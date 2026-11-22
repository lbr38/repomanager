<?php
/**
 *  Create openbsd mirror (OpenBSD 7.9 binary patches, amd64: a few dozen files, less than 100 MB)
 *  The files list signature is verified with the signify keys of the source repository
 *  7.9 is the oldest version of the source repository template: update it when its keys are removed from the template
 */
$rawParams['action'] = 'create';
$rawParams['domain'] = 'mirror';
$rawParams['package-type'] = 'openbsd';
$rawParams['name'] = 'openbsd';
$rawParams['source'] = 'openbsd';
$rawParams['versions'] = ['7.9'];
$rawParams['content'] = ['syspatch'];
$rawParams['arch'] = ['amd64'];
$rawParams['gpg-check'] = 'true';
$rawParams['env'] = ['pprd'];
$rawParams['description'] = 'CI - create openbsd mirror';
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
