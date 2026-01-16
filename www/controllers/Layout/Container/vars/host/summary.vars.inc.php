<?php
if (empty(__ACTUAL_URI__[2])) {
    die('Error: no host ID specified.');
}

if (!is_numeric(__ACTUAL_URI__[2])) {
    die('Error: invalid host ID.');
}

$id = __ACTUAL_URI__[2];
$myhost = new \Controllers\Host();
$hostPackageController = new \Controllers\Host\Package\Package($id);

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
$cpu              = $hostProperties['Cpu'];
$ram              = $hostProperties['Ram'];
$profile          = $hostProperties['Profile'];
$env              = $hostProperties['Env'];
$agentStatus      = $hostProperties['Online_status'];
$agentVersion     = $hostProperties['Linupdate_version'];
$rebootRequired   = $hostProperties['Reboot_required'];
$uptime           = $hostProperties['Uptime'];

// Uptime value is in timestamp format, convert it to a more readable format if it's in seconds
if (is_numeric($uptime)) {
    $boot = new DateTime('@' . $uptime); // @ tells PHP that $uptime is a timestamp value
    $now  = new DateTime('now');
    $interval = $boot->diff($now);
    $uptime = $interval->format('%a days, %h hours, %i minutes');
}

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

unset($myhost, $hostPackageController, $hostProperties);
