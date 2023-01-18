<?php

namespace Controllers\Layout\Tab;

class Cve
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

        $mycve = new \Controllers\Cve\Cve();

        /**
         *  Retrieve name Id from URL
         */
        $nameId = \Controllers\Common::validateData($_GET['nameid']);

        if (!$mycve->nameExists($nameId)) {
            die('CVE not found');
        }

        /**
         *  Retrieve CVE Id and CVE details
         */
        $id = $mycve->getIdByName($nameId);
        $cveDetails = $mycve->get($id);
        $cveReferences = $mycve->getReferences($id);

        /**
         *  Retrieve CVE Affected Hosts
         */
        $possibleAffectedHosts = '';
        $affectedHosts = '';

        include_once(ROOT . '/views/cve.template.php');
    }
}
