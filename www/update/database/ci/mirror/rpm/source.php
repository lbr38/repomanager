<?php
/**
 *  Import the nginx rpm source repository (and its GPG key), used by the rpm mirror
 */
$sourceController = new \Controllers\Repo\Source\Source();
$sourceController->import(['github/rpm/nginx']);
