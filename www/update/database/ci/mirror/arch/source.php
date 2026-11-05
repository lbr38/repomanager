<?php
/**
 *  Import the Arch Linux source repository, used by the arch mirror
 */
$sourceController = new \Controllers\Repo\Source\Source();
$sourceController->import(['github/arch/archlinux']);
