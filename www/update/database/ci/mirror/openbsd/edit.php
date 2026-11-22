<?php
/**
 *  Replace the version of the openbsd mirror (7.9 by 8.0), as the mirror edit form does (see Controllers\Mirror\Edit), then sync it into
 *  a new snapshot pointed by the pprd environment: the new snapshot only contains the binary patches of the new version
 *  The parameters are edited in database: the edit form requires an authenticated user
 */
$result = $this->db->query("SELECT Id, Params FROM mirrors WHERE Package_type = 'openbsd' AND Name = 'openbsd'")->fetchArray(SQLITE3_ASSOC);

if (empty($result)) {
    throw new Exception('openbsd mirror not found');
}

$params = json_decode($result['Params'], true, 512, JSON_THROW_ON_ERROR);
$params['versions'] = ['8.0'];

$stmt = $this->db->prepare("UPDATE mirrors SET Params = :params WHERE Id = :id");
$stmt->bindValue(':params', json_encode($params, JSON_THROW_ON_ERROR));
$stmt->bindValue(':id', $result['Id']);
$stmt->execute();

$rawParams['action'] = 'update';
$rawParams['domain'] = 'mirror';
$rawParams['mirror-id'] = strval($result['Id']);
$rawParams['env'] = ['pprd'];
$rawParams['schedule'] = [
    'scheduled' => 'false',
    'schedule-type' => '',
    'schedule-frequency' => '',
    'schedule-day' => [],
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
