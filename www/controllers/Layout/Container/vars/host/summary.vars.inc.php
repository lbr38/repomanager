<?php
$myhost = new \Controllers\Host();

if (empty(__ACTUAL_URI__[2])) {
    die('Error: no host ID specified.');
}

if (!is_numeric(__ACTUAL_URI__[2])) {
    die('Error: invalid host ID.');
}

$id = __ACTUAL_URI__[2];

/**
 *  Getting all informations about this host
 */
$hostProperties = $myhost->getAll($id);

$hostname         = $hostProperties['Hostname'];
$ip               = $hostProperties['Ip'];
$os               = $hostProperties['Os'];
$osVersion        = $hostProperties['Os_version'];
$type             = $hostProperties['Type'];
$kernel           = $hostProperties['Kernel'];
$arch             = $hostProperties['Arch'];
$profile          = $hostProperties['Profile'];
$env              = $hostProperties['Env'];
$status           = $hostProperties['Status'];
$agentStatus      = $hostProperties['Online_status'];
$agentVersion     = $hostProperties['Linupdate_version'];
$rebootRequired   = $hostProperties['Reboot_required'];

/**
 *  Last known agent state message
 */
$agentLastSendStatusMsg = 'state on ' . DateTime::createFromFormat('Y-m-d', $hostProperties['Online_status_date'])->format('d-m-Y') . ' at ' . $hostProperties['Online_status_time'];

/**
 *  Checking that the last time the agent has sent his status was before 1h10m
 */
if ($hostProperties['Online_status_date'] != DATE_YMD or $hostProperties['Online_status_time'] <= date('H:i:s', strtotime(date('H:i:s') . ' - 70 minutes'))) {
    $agentStatus = 'seems-stopped';
}

/**
 *  If the host has 'deleted' state then don't print it
 */
if ($status == 'deleted') {
    die('Error: host has been deleted.');
}

/**
 *  Open host database
 */
$myhost->openHostDb($id);

/**
 *  First create a list of dates on a 15days period
 */
$dates = array();
$dateStart = date_create(date('Y-m-d'))->modify("-15 days")->format('Y-m-d');
$dateEnd = date_create(date('Y-m-d'))->modify("+1 days")->format('Y-m-d');
$period = new DatePeriod(
    new DateTime($dateStart),
    new DateInterval('P1D'),
    new DateTime($dateEnd)
);

/**
 *  Then generate a new array from the date period. Every date is initialized with a 0 value.
 */
foreach ($period as $key => $value) {
    $dates[$value->format('Y-m-d')] = 0;
}

/**
 *  Getting last 15days installed packages
 */
$lastInstalledPackagesArray = $myhost->getLastPackagesStatusCount('installed', '15');

/**
 *  Getting last 15days updated packages
 */
$lastUpgradedPackagesArray = $myhost->getLastPackagesStatusCount('upgraded', '15');

/**
 *  Getting last 15days deleted packages
 */
$lastRemovedPackagesArray = $myhost->getLastPackagesStatusCount('removed', '15');

/**
 *  Merging all arrays with dates array
 */
$lastInstalledPackagesArray = array_merge($dates, $lastInstalledPackagesArray);
$lastUpgradedPackagesArray  = array_merge($dates, $lastUpgradedPackagesArray);
$lastRemovedPackagesArray   = array_merge($dates, $lastRemovedPackagesArray);

/**
 *  Formating values to ChartJS format
 *  Formating dates array to ChartJS format
 */
$lineChartInstalledPackagesCount = "'" . implode("','", $lastInstalledPackagesArray) . "'";
$lineChartUpgradedPackagesCount  = "'" . implode("','", $lastUpgradedPackagesArray) . "'";
$lineChartRemovedPackagesCount   = "'" . implode("','", $lastRemovedPackagesArray) . "'";
$lineChartDates = "'" . implode("','", array_keys($dates)) . "'";

/**
 *  Close host database
 */
$myhost->closeHostDb();

unset($myhost);
