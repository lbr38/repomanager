<?php

namespace Controllers\Layout\Panel;

class RepoGroup
{
    public static function render()
    {
        $myrepo = new \Controllers\Repo();
        $mygroup = new \Controllers\Group('repo');

        /**
         *  Get repos groups list
         */
        $repoGroupsList = $mygroup->listAllName();

        include_once(ROOT . '/views/includes/panels/repo-group.inc.php');
    }
}
