<?php

/**
 *  Not really a database update but a configuration update
 */
$configFile = '/var/lib/repomanager/configurations/repomanager.conf';
$gpgKeyId = '';

if (!file_exists($configFile)) {
    return;
}

$configuration = file_get_contents($configFile);

if ($configuration === false) {
    throw new Exception('Unable to read configuration file');
}

/**
 *  Quit if the configuration file already contains GPG_SIGNING_KEYID param
 */
if (preg_match('/GPG_SIGNING_KEYID/', $configuration)) {
    return;
}

/**
 *  Get current(s) GPG signing key Id
 */
preg_match('/DEB_SIGN_GPG_KEYID.*/', $configuration, $debMatch);
preg_match('/RPM_SIGN_GPG_KEYID.*/', $configuration, $rpmMatch);

if (!empty($debMatch)) {
    $explode = explode('=', $debMatch[0]);
    $debGpgKeyId = str_replace('"', '', $explode[1]);
    $debGpgKeyId = trim($debGpgKeyId);
}

if (!empty($rpmMatch)) {
    $explode = explode('=', $rpmMatch[0]);
    $rpmGpgKeyId = str_replace('"', '', $explode[1]);
    $rpmGpgKeyId = trim($rpmGpgKeyId);
}

if (!empty($rpmGpgKeyId)) {
    $gpgKeyId = $rpmGpgKeyId;
}
if (!empty($debGpgKeyId)) {
    $gpgKeyId = $debGpgKeyId;
}

/**
 *  Add a new section in the configuration file
 */
$configuration .= PHP_EOL . '[GPG]' . PHP_EOL . 'GPG_SIGNING_KEYID = "' . $gpgKeyId . '"' . PHP_EOL;

/**
 *  Write the new configuration file
 */
if (!file_put_contents($configFile, $configuration)) {
    throw new Exception('Unable to write configuration file');
}
