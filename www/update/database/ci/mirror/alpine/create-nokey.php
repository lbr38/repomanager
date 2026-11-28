<?php
/**
 *  Create alpine mirror from a copy of the Alpine Linux source repository without its signing keys
 *  The index signature cannot be verified: the task must fail before retrieving any package
 */
$this->db->exec("INSERT INTO sources (Definition, Method) SELECT json_set(Definition, '$.name', 'alpine-nokey', '$.keys', json('[]')), Method FROM sources WHERE json_extract(Definition, '$.type') = 'alpine' AND json_extract(Definition, '$.name') = 'alpine'");

$rawParams['action'] = 'create';
$rawParams['domain'] = 'mirror';
$rawParams['package-type'] = 'alpine';
$rawParams['name'] = 'alpine-nokey';
$rawParams['source'] = 'alpine-nokey';
$rawParams['branch'] = 'v3.3';
$rawParams['repos'] = ['community'];
$rawParams['arch'] = ['x86'];
$rawParams['gpg-check'] = 'true';
$rawParams['env'] = ['pprd'];
$rawParams['description'] = 'CI - create alpine mirror without signing key';
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
