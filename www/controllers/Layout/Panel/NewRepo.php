<?php

namespace Controllers\Layout\Panel;

class NewRepo
{
    public static function render()
    {
        $myrepo = new \Controllers\Repo();
        $mygroup = new \Controllers\Group('repo');
        $mysource = new \Controllers\Source();

        /**
         *  Get repos groups list
         */
        $repoGroupsList = $mygroup->listAllName();

        /**
         *  New repo form variables
         */
        $newRepoRpmSourcesList = $mysource->listAll('rpm');
        $newRepoDebSourcesList = $mysource->listAll('deb');
        $newRepoFormGroupList = $mygroup->listAllName();

        include_once(ROOT . '/views/includes/panels/new-repo.inc.php');
    }
}
