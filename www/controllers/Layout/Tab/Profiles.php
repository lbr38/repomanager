<?php

namespace Controllers\Layout\Tab;

class Profiles
{
    public static function render()
    {
        /**
         *  Only admin have access to this page
         */
        if (!IS_ADMIN) {
            header('Location: /');
            exit;
        }

        $myprofile = new \Controllers\Profile();
        $myrepo = new \Controllers\Repo\Repo();
        $myrepoListing = new \Controllers\Repo\Listing();

        $serverConfiguration = $myprofile->getServerConfiguration();
        $serverPackageType = $serverConfiguration['Package_type'];
        $serverManageClientConf = $serverConfiguration['Manage_client_conf'];
        $serverManageClientRepos = $serverConfiguration['Manage_client_repos'];

        /**
         *  Getting all profiles names
         */
        $profiles = $myprofile->list();

        include_once(ROOT . '/views/profiles.template.php');

        \Controllers\Layout\Panel\ProfileServerSettings::render();
    }
}
