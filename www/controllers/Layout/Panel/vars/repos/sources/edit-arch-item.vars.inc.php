<?php
use \Controllers\Repo\Source\Source;

// Check that Id, field and item Id params have been sent
if (!isset($item['id'])) {
    throw new Exception('Repository Id required');
}
if (!isset($item['field']) or !in_array($item['field'], ['repos', 'archs'])) {
    throw new Exception('Invalid field');
}
if (!isset($item['itemId']) or !is_numeric($item['itemId'])) {
    throw new Exception('Item Id required');
}

$sourceId = $item['id'];
$field = $item['field'];
$itemId = (int) $item['itemId'];
$itemType = $field == 'archs' ? 'architecture' : 'repository';

// Retrieve source repo details
$sourceDefinition = (new Source())->getDefinition($sourceId);

if (!isset($sourceDefinition[$field][$itemId])) {
    throw new Exception(ucfirst($itemType) . ' does not exist');
}

$name = $sourceDefinition[$field][$itemId]['name'];
$description = $sourceDefinition[$field][$itemId]['description'] ?? '';

unset($sourceDefinition);
