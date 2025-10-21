<?php
$myhost = new \Controllers\Host();
$totalNotUptodate = 0;
$totalUptodate = 0;

/**
 *  Getting hosts list
 */
$hostsList = $myhost->listAll();

/**
 *  Getting total hosts
 */
$totalHosts = count($hostsList);

/**
 *  Getting general hosts threshold settings
 */
$hostsSettings = $myhost->getSettings();

/**
 *  Getting general hosts threshold settings
 */
$hostsSettings = $myhost->getSettings();

/**
 *  Threshold of the maximum number of available update above which the host is considered as 'not up to date' (but not critical)
 */
$packagesCountConsideredOutdated = $hostsSettings['pkgs_count_considered_outdated'];

/**
 *  Threshold of the maximum number of available update above which the host is considered as 'not up to date' (critical)
 */
$packagesCountConsideredCritical = $hostsSettings['pkgs_count_considered_critical'];

/**
 *
 */
foreach ($hostsList as $host) {
    /**
     *  Open the dedicated database of the host from its ID to be able to retrieve additional information
     */
    $hostPackageController = new \Controllers\Host\Package\Package($host['Id']);

    /**
     *  Retrieve the total number of available packages
     */
    $packagesAvailableTotal = count($hostPackageController->getAvailable());

    /**
     *  Retrieve the total number of installed packages
     */
    $packagesInstalledTotal = count($hostPackageController->getInstalled());

    /**
     *  If the total number of available packages retrieved previously is > $packagesCountConsideredOutdated (threshold defined by the user) then we increment $totalNotUptodate (counts the number of hosts that are not up to date in the chartjs)
     *  Else it's $totalUptodate that we increment.
     */
    if ($packagesAvailableTotal >= $packagesCountConsideredOutdated) {
        $totalNotUptodate++;
    } else {
        $totalUptodate++;
    }

    /**
     *  Close the dedicated database of the host
     */
}

/**
 *  Define the total number of hosts up to date and not up to date
 */
define('HOSTS_TOTAL_UPTODATE', $totalUptodate);
define('HOSTS_TOTAL_NOT_UPTODATE', $totalNotUptodate);

/**
 *  Getting a list of all hosts OS (bar chart)
 */
define('HOSTS_OS_LIST', $myhost->listCountOS());

/**
 *  Getting a list of all hosts kernel
 */
define('HOSTS_KERNEL_LIST', $myhost->listCountKernel());
array_multisort(array_column(HOSTS_KERNEL_LIST, 'Kernel_count'), SORT_DESC, HOSTS_KERNEL_LIST);

/**
 *  Getting a list of all hosts arch
 */
define('HOSTS_ARCHS_LIST', $myhost->listCountArch());

/**
 *  Getting a list of all hosts environments
 */
define('HOSTS_ENVS_LIST', $myhost->listCountEnv());

/**
 *  Getting a list of all hosts profiles
 */
define('HOSTS_PROFILES_LIST', $myhost->listCountProfile());
array_multisort(array_column(HOSTS_PROFILES_LIST, 'Profile_count'), SORT_DESC, HOSTS_PROFILES_LIST);

/**
 *  Getting a list of all hosts agent status
 */
define('HOSTS_AGENT_STATUS_LIST', $myhost->listCountAgentStatus());

/**
 *  Getting a list of all hosts agent release version
 */
define('HOSTS_AGENT_VERSION_LIST', $myhost->listCountAgentVersion());

/**
 *  Getting a list of all hosts requiring a reboot
 */
$rebootRequiredList = $myhost->listRebootRequired();
$rebootRequiredCount = count($rebootRequiredList);

unset($myhost, $hostsList, $hostsSettings, $totalNotUptodate, $totalUptodate, $packagesCountConsideredOutdated, $packagesCountConsideredCritical);
