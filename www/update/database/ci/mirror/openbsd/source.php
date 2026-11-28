<?php
/**
 *  Import the OpenBSD source repository, used by the openbsd mirror
 */
$sourceController = new \Controllers\Repo\Source\Source();
$sourceController->import(['github/openbsd/openbsd']);
