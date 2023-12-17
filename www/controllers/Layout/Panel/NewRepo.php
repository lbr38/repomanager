<?php

namespace Controllers\Layout\Panel;

class NewRepo
{
    public static function render()
    {
        $myrepo = new \Controllers\Repo\Repo();
        $mygroup = new \Controllers\Group('repo');
        $mysource = new \Controllers\Source();

        /**
         *  New repo form variables
         */
        $newRepoRpmSourcesList = $mysource->listAll('rpm');
        $newRepoDebSourcesList = $mysource->listAll('deb');
        $newRepoFormGroupList = $mygroup->listAll();

        include_once(ROOT . '/views/includes/panels/repos/new.inc.php');
    }
}
