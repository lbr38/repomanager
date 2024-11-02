<?php
$mysourceRepo = new \Controllers\Repo\Source\Source();
$myGpg = new \Controllers\Gpg();
$description = '';
$sections = [];
$gpgKeys = [];

/**
 *  Check that Id and distribution params have been sent
 */
if (!isset($item['id'])) {
    throw new Exception('Repository Id required');
}
if (!isset($item['distributionId'])) {
    throw new Exception('Distribution Id required');
}

/**
 *  Retrieve source and distribution Ids
 */
$sourceId = $item['id'];
$distributionId = $item['distributionId'];

/**
 *  Retrieve source repo details
 */
$sourceDefinition = json_decode($mysourceRepo->getDefinition($item['id']), true);

/**
 *  Retrieve distribution name
 */
$distribution = $sourceDefinition['distributions'][$distributionId]['name'];

/**
 *  Retrieve description if any
 */
if (!empty($sourceDefinition['distributions'][$distributionId]['description'])) {
    $description = $sourceDefinition['distributions'][$distributionId]['description'];
}

/**
 *  Retrieve sections if any
 */
if (!empty($sourceDefinition['distributions'][$distributionId]['components'])) {
    $sections = $sourceDefinition['distributions'][$distributionId]['components'];
}

/**
 *  Retrieve gpg keys if any
 */
if (!empty($sourceDefinition['distributions'][$distributionId]['gpgkeys'])) {
    $gpgKeys = $sourceDefinition['distributions'][$distributionId]['gpgkeys'];
}

/**
 *  Retrieve all trusted GPG keys from keyring
 */
$trustedGpgKeys = $myGpg->getTrustedKeys();
