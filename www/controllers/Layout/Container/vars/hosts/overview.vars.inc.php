<?php
$myhost = new \Controllers\Host();

/**
 *  Getting total hosts
 */
$totalHosts = count($myhost->listAll());

/**
 *  Getting a list of all hosts kernel
 */
$kernels = $myhost->listCountKernel();
array_multisort(array_column($kernels, 'Kernel_count'), SORT_DESC, $kernels);

/**
 *  Getting a list of all hosts profiles
 */
$profiles = $myhost->listCountProfile();
array_multisort(array_column($profiles, 'Profile_count'), SORT_DESC, $profiles);

/**
 *  Getting a list of all hosts requiring a reboot
 */
$rebootRequiredList = $myhost->listRebootRequired();
$rebootRequiredCount = count($rebootRequiredList);

unset($myhost);
