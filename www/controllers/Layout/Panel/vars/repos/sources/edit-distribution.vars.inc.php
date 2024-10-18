<?php
$mysourceRepo = new \Controllers\Repo\Source\Source();

/**
 *  Check that Id and distribution params have been sent
 */
if (empty($item['id'])) {
    throw new Exception('Missing repository Id');
}
if (empty($item['distribution'])) {
    throw new Exception('Missing distribution');
}

$id = $item['id'];
$distribution = $item['distribution'];
$description = '';
$components = [];

/**
 *  Retrieve source repo details
 */
$details = json_decode($mysourceRepo->getDetails($item['id']), true);

/**
 *  Retrieve description if any
 */
if (!empty($details['distributions'][$distribution]['description'])) {
    $description = $details['distributions'][$distribution]['description'];
}

/**
 *  Retrieve components if any
 */
if (!empty($details['distributions'][$distribution]['components'])) {
    $components = $details['distributions'][$distribution]['components'];
}

