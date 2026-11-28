<?php
/**
 *  Create openbsd mirror from a copy of the OpenBSD source repository without its signing keys
 *  The files list signature cannot be verified: the task must fail before retrieving any file
 */
$this->db->exec("INSERT INTO sources (Definition, Method) SELECT json_set(Definition, '$.name', 'openbsd-nokey', '$.keys', json('[]')), Method FROM sources WHERE json_extract(Definition, '$.type') = 'openbsd' AND json_extract(Definition, '$.name') = 'openbsd'");

$rawParams['action'] = 'create';
$rawParams['domain'] = 'mirror';
$rawParams['package-type'] = 'openbsd';
$rawParams['name'] = 'openbsd-nokey';
$rawParams['source'] = 'openbsd-nokey';
$rawParams['versions'] = ['7.9'];
$rawParams['content'] = ['syspatch'];
$rawParams['arch'] = ['amd64'];
$rawParams['gpg-check'] = 'true';
$rawParams['env'] = ['pprd'];
$rawParams['description'] = 'CI - create openbsd mirror without signing key';
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
