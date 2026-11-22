<?php
use \Controllers\Repo\Source\Items;

/**
 *  Edit an item (repository, architecture, branch, version) of an arch, alpine or openbsd source repository
 */

// Check that Id, field and item Id params have been sent
if (!isset($item['id']) or !is_numeric($item['id'])) {
    throw new Exception('Repository Id required');
}
if (!isset($item['field']) or !in_array($item['field'], ['repos', 'archs', 'branches', 'versions'])) {
    throw new Exception('Invalid field');
}
if (!isset($item['itemId']) or !is_numeric($item['itemId'])) {
    throw new Exception('Item Id required');
}

$sourceId = $item['id'];
$field = $item['field'];
$itemId = (int) $item['itemId'];

// Retrieve source repo details
$sourceController = Items::fromId((int) $sourceId);
$itemType = strtolower($sourceController::getItemLabel($field));
$sourceDefinition = $sourceController->getDefinition($sourceId);

if (!isset($sourceDefinition[$field][$itemId])) {
    throw new Exception(ucfirst($itemType) . ' does not exist');
}

$name = $sourceDefinition[$field][$itemId]['name'];
$description = $sourceDefinition[$field][$itemId]['description'] ?? '';

unset($sourceController, $sourceDefinition);
