<?php
$myprofile = new \Controllers\Profile();
$myrepo = new \Controllers\Repo\Repo();
$myrepoListing = new \Controllers\Repo\Listing();
$myhost = new \Controllers\Host();

/**
 *  Getting all profiles names
 */
$profiles = $myprofile->list();

/**
 *  Retrieve all active repos names
 */
$reposList = $myrepoListing->listNameOnly(true);
