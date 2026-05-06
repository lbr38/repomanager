<?php
$myprofile = new \Controllers\Profile();
$myrepoListing = new \Controllers\Repo\Listing();
$hostListingController = new \Controllers\Host\Listing();

// Get all profiles names
$profiles = $myprofile->list();

// Selectables packages in the list of packages to exclude
$listPackages = $myprofile->getPackages();

// Get all services names
$services = $myprofile->getServices();

// Retrieve all active repos names
$reposList = $myrepoListing->listNameOnly(true);

unset($myrepoListing);
