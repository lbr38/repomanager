<?php

namespace Controllers\Layout\Panel;

class SourceRepo
{
    public static function render()
    {
        $mysource = new \Controllers\Source();
        $mygroup = new \Controllers\Group('repo');
        $mygpg = new \Controllers\Gpg();

        /**
         *  Get source repos list
         */
        $sourceReposList = $mysource->listAll();

        /**
         *  Get imported GPG signing keys
         */
        $knownPublicKeys = $mygpg->getTrustedKeys();

        include_once(ROOT . '/views/includes/panels/source-repo.inc.php');
    }
}
