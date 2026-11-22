<?php
/**
 *  Import the Alpine Linux source repository, used by the alpine mirror
 */
$sourceController = new \Controllers\Repo\Source\Source();
$sourceController->import(['github/alpine/alpine']);
