<?php
$hostListingController = new \Controllers\Host\Listing();

/**
 *  Getting total hosts
 */
$totalHosts = count($hostListingController->get());

/**
 *  Getting a list of all hosts kernel
 */
$kernels = $hostListingController->getKernel();
array_multisort(array_column($kernels, 'Count'), SORT_DESC, $kernels);

/**
 *  Getting a list of all hosts profiles
 */
$profiles = $hostListingController->getProfile();
array_multisort(array_column($profiles, 'Count'), SORT_DESC, $profiles);

/**
 *  Getting a list of all hosts requiring a reboot
 */
$rebootRequiredList = $hostListingController->getRebootRequired();
$rebootRequiredCount = count($rebootRequiredList);

unset($hostListingController);
