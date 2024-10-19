<?php
$mysourceRepo = new \Controllers\Repo\Source\Source();
$description = '';
$components = [];

/**
 *  Check that Id and release version params have been sent
 */
if (!isset($item['id'])) {
    throw new Exception('Repository Id required');
}
if (!isset($item['releaseverId'])) {
    throw new Exception('Distribution Id required');
}

/**
 *  Retrieve source and release version Ids
 */
$sourceId = $item['id'];
$releaseverId = $item['releaseverId'];

/**
 *  Retrieve source repo details
 */
$sourceDefinition = json_decode($mysourceRepo->getDefinition($item['id']), true);

/**
 *  Retrieve release version name
 */
$releasever = $sourceDefinition['releasever'][$releaseverId]['name'];

/**
 *  Retrieve description if any
 */
if (!empty($sourceDefinition['releasever'][$releaseverId]['description'])) {
    $description = $sourceDefinition['releasever'][$releaseverId]['description'];
}
