<?php

namespace Controllers\Layout\Tab;

class Hosts
{
    public static function render()
    {
        $mygroup = new \Controllers\Group('host');
        $myhost = new \Controllers\Host();
        $mycolor = new \Controllers\Common();

        /**
         *  Get hosts groups list
         */
        $hostGroupsList = $mygroup->listAllName();
        $hostGroupsListWithDefault = $mygroup->listAllWithDefault();

        /**
         *  Case general hosts threshold settings form has been sent
         */
        if (!empty($_POST['settings-pkgs-considered-outdated']) and !empty($_POST['settings-pkgs-considered-critical'])) {
            $pkgs_considered_outdated = \Controllers\Common::validateData($_POST['settings-pkgs-considered-outdated']);
            $pkgs_considered_critical = \Controllers\Common::validateData($_POST['settings-pkgs-considered-critical']);

            $myhost->setSettings($pkgs_considered_outdated, $pkgs_considered_critical);
        }

        /**
         *  Getting general hosts threshold settings
         */
        $hostsSettings = $myhost->getSettings();

        /**
         *  Threshold of the maximum number of available update above which the host is considered as 'not up to date' (but not critical)
         */
        $pkgs_count_considered_outdated = $hostsSettings['pkgs_count_considered_outdated'];

        /**
         *  Threshold of the maximum number of available update above which the host is considered as 'not up to date' (critical)
         */
        $pkgs_count_considered_critical = $hostsSettings['pkgs_count_considered_critical'];

        /**
         *  Getting total hosts
         */
        $totalHosts = count($myhost->listAll('active'));

        /**
         *  Initializing counters for doughnut chart
         */
        $totalUptodate = 0;
        $totalNotUptodate = 0;

        /**
         *  Getting a list of all hosts OS (bar chart)
         */
        $osList = $myhost->listCountOS();

        /**
         *  Getting a list of all hosts kernel
         */
        $kernelList = $myhost->listCountKernel();
        array_multisort(array_column($kernelList, 'Kernel_count'), SORT_DESC, $kernelList);

        /**
         *  Getting a list of all hosts arch
         */
        $archList = $myhost->listCountArch();

        /**
         *  Getting a list of all hosts environments
         */
        $envsList = $myhost->listCountEnv();

        /**
         *  Getting a list of all hosts profiles
         */
        $profilesList = $myhost->listCountProfile();
        array_multisort(array_column($profilesList, 'Profile_count'), SORT_DESC, $profilesList);

        /**
         *  Getting a list of all hosts agent status
         */
        $agentStatusList = $myhost->listCountAgentStatus();

        /**
         *  Getting a list of all hosts agent release version
         */
        $agentVersionList = $myhost->listCountAgentVersion();

        /**
         *  Getting a list of all hosts requiring a reboot
         */
        $rebootRequiredList = $myhost->listRebootRequired();
        $rebootRequiredCount = count($rebootRequiredList);

        include_once(ROOT . '/views/hosts.template.php');

        if (IS_ADMIN) {
            \Controllers\Layout\Panel\HostGroup::render();
        }
    }
}
