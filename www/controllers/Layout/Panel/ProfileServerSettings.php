<?php

namespace Controllers\Layout\Panel;

class ProfileServerSettings
{
    public static function render()
    {
        $myprofile = new \Controllers\Profile();

        $serverConfiguration = $myprofile->getServerConfiguration();
        $serverPackageType = $serverConfiguration['Package_type'];
        $serverManageClientConf = $serverConfiguration['Manage_client_conf'];
        $serverManageClientRepos = $serverConfiguration['Manage_client_repos'];

        include_once(ROOT . '/views/includes/panels/profile-server-settings.inc.php');
    }
}
