<?php
use \Controllers\Repo\Source\Source;
use \Controllers\Gpg;

$sourceRepoController = new Source();
$description = '';
$eol = '';
$components = [];
$gpgKeys = [];

// Check that Id and distribution params have been sent
if (!isset($item['id'])) {
    throw new Exception('Repository Id required');
}
if (!isset($item['distributionId'])) {
    throw new Exception('Distribution Id required');
}

// Retrieve source and distribution Ids
$sourceId = $item['id'];
$distributionId = $item['distributionId'];

// Retrieve source repo details
$sourceDefinition = $sourceRepoController->getDefinition($item['id']);

// Retrieve distribution name
$distribution = $sourceDefinition['distributions'][$distributionId]['name'];

// Retrieve description if any
if (!empty($sourceDefinition['distributions'][$distributionId]['description'])) {
    $description = $sourceDefinition['distributions'][$distributionId]['description'];
}

// Retrieve EOL date if any
if (!empty($sourceDefinition['distributions'][$distributionId]['eol'])) {
    $eol = $sourceDefinition['distributions'][$distributionId]['eol'];
}

// Retrieve components if any
if (!empty($sourceDefinition['distributions'][$distributionId]['components'])) {
    $components = $sourceDefinition['distributions'][$distributionId]['components'];
}

// Retrieve gpg keys if any
if (!empty($sourceDefinition['distributions'][$distributionId]['gpgkeys'])) {
    $gpgKeys = $sourceDefinition['distributions'][$distributionId]['gpgkeys'];
}

// Retrieve all trusted GPG keys from keyring
$trustedGpgKeys = Gpg::getTrustedKeys();

unset($sourceRepoController, $sourceDefinition);
