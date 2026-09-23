<?php
use \Controllers\Repo\Source\Source;

$sourceRepoController = new Source();
$description = '';
$archs = [];

// Check that Id, distribution and component params have been sent
if (!isset($item['id'])) {
    throw new Exception('Repository Id required');
}
if (!isset($item['distributionId'])) {
    throw new Exception('Distribution Id required');
}
if (!isset($item['componentId'])) {
    throw new Exception('Component Id required');
}

// Retrieve source and distribution Ids
$sourceId = $item['id'];
$distributionId = $item['distributionId'];
$componentId = $item['componentId'];

// Retrieve source repo details
$sourceDefinition = $sourceRepoController->getDefinition($item['id']);

// Retrieve component name
$componentName = $sourceDefinition['distributions'][$distributionId]['components'][$componentId]['name'];

// Retrieve description if any
if (!empty($sourceDefinition['distributions'][$distributionId]['components'][$componentId]['description'])) {
    $description = $sourceDefinition['distributions'][$distributionId]['components'][$componentId]['description'];
}

// Retrieve architectures if any
if (!empty($sourceDefinition['distributions'][$distributionId]['components'][$componentId]['archs'])) {
    $archs = $sourceDefinition['distributions'][$distributionId]['components'][$componentId]['archs'];
}

unset($sourceRepoController, $sourceDefinition);
