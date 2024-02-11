<?php
$myhost = new \Controllers\Host();
$mycolor = new \Controllers\Common();

/**
 *  Getting total hosts
 */
$totalHosts = count($myhost->listAll('active'));

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

unset($myhost);
