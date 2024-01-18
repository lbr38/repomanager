<?php

namespace Controllers\Layout\Tab;

class Hosts
{
    public static function render()
    {
        $mycolor = new \Controllers\Common();

        /**
         *  Print hosts overview and list
         */
        \Controllers\Layout\Container\Render::render('hosts/overview');
        \Controllers\Layout\Container\Render::render('hosts/list');

        /**
         *  If user is admin, print host group and host settings panels
         */
        if (IS_ADMIN) {
            \Controllers\Layout\Panel\Hosts\Group::render();
            \Controllers\Layout\Panel\Hosts\Settings::render();
        }

        /**
         *  Print ChartJS
         */

        /**
         *  Hosts chart
         */
        $labels = "'Up to date', 'Need update'";
        $datas = "'" . HOSTS_TOTAL_UPTODATE . "', '" . HOSTS_TOTAL_NOT_UPTODATE . "'";
        $backgrounds = "'rgb(75, 192, 192)','rgb(255, 99, 132)'";
        $title = '';
        $chartId = 'hosts-count-chart';

        include(ROOT . '/views/includes/charts/hosts-pie-chart.inc.php');

        /**
         *  Profiles chart
         */
        if (!empty(HOSTS_PROFILES_LIST)) {
            $profileNameList = '';
            $profileCountList = '';
            $profileBackgroundColor = '';

            foreach (HOSTS_PROFILES_LIST as $profile) {
                if (empty($profile['Profile'])) {
                    $profileNameList .= "'Unknow',";
                } else {
                    $profileNameList .= "'" . $profile['Profile'] . "',";
                }
                $profileCountList .= "'" . $profile['Profile_count'] . "',";
                $profileBackgroundColor .= "'" . $mycolor->randomColor() . "',";
            }

            $labels = rtrim($profileNameList, ',');
            $datas = rtrim($profileCountList, ',');
            $backgrounds = rtrim($profileBackgroundColor, ',');
            $title = '';
            $chartId = 'hosts-profile-chart';

            include(ROOT . '/views/includes/charts/hosts-bar-chart.inc.php');
        }

        /**
         *  OS chart
         */
        if (!empty(HOSTS_OS_LIST)) {
            $osNameList = '';
            $osCountList = '';
            $osBackgroundColor = '';

            foreach (HOSTS_OS_LIST as $os) {
                if (empty($os['Os'])) {
                    $osNameList .= "'Unknow',";
                } else {
                    $osNameList .= "'" . ucfirst($os['Os']) . " " . $os['Os_version'] . "',";
                }
                $osCountList .= "'" . $os['Os_count'] . "',";
                $osBackgroundColor .= "'" . $mycolor->randomColor() . "',";
            }

            $labels = rtrim($osNameList, ',');
            $datas = rtrim($osCountList, ',');
            $backgrounds = rtrim($osBackgroundColor, ',');
            $title = '';
            $chartId = 'hosts-os-chart';

            include(ROOT . '/views/includes/charts/hosts-bar-chart.inc.php');
        }

        /**
         *  Arch chart
         */
        if (!empty(HOSTS_ARCHS_LIST)) {
            $archNameList = '';
            $archCountList = '';
            $archBackgroundColor = '';

            foreach (HOSTS_ARCHS_LIST as $arch) {
                if (empty($arch['Arch'])) {
                    $archNameList .= "'Unknow',";
                } else {
                    $archNameList .= "'" . $arch['Arch'] . "',";
                }
                $archCountList .= "'" . $arch['Arch_count'] . "',";
                $archBackgroundColor .= "'" . $mycolor->randomColor() . "',";
            }

            $labels = rtrim($archNameList, ',');
            $datas = rtrim($archCountList, ',');
            $backgrounds = rtrim($archBackgroundColor, ',');
            $title = '';
            $chartId = 'hosts-arch-chart';

            include(ROOT . '/views/includes/charts/hosts-pie-chart.inc.php');
        }

        /**
         *  Envs chart
         */
        if (!empty(HOSTS_ENVS_LIST)) {
            $envNameList = '';
            $envCountList = '';
            $envBackgroundColor = '';

            foreach (HOSTS_ENVS_LIST as $env) {
                if (empty($env['Env'])) {
                    $envNameList .= "'Unknow',";
                } else {
                    $envNameList .= "'" . $env['Env'] . "',";
                }
                $envCountList .= "'" . $env['Env_count'] . "',";

                if ($env['Env'] == LAST_ENV) {
                    $envBackgroundColor .= "'rgb(255, 99, 132)',";
                } else {
                    $envBackgroundColor .= "'" . $mycolor->randomColor() . "',";
                }
            }

            $labels = rtrim($envNameList, ',');
            $datas = rtrim($envCountList, ',');
            $backgrounds = rtrim($envBackgroundColor, ',');
            $title = '';
            $chartId = 'hosts-env-chart';

            include(ROOT . '/views/includes/charts/hosts-pie-chart.inc.php');
        }

        /**
         *  Agent status chart
         */
        if (!empty(HOSTS_AGENT_STATUS_LIST)) {
            $agentStatusNameList = '';
            $agentStatusCountList = '';
            $agentBackgroundColor = '';

            if (!empty(HOSTS_AGENT_STATUS_LIST['Linupdate_agent_status_online_count'])) {
                $agentStatusNameList .= "'Online',";
                $agentStatusCountList .= "'" . HOSTS_AGENT_STATUS_LIST['Linupdate_agent_status_online_count'] . "',";
                $agentBackgroundColor .= "'#24d794',";
            }

            if (!empty(HOSTS_AGENT_STATUS_LIST['Linupdate_agent_status_seems_stopped_count'])) {
                $agentStatusNameList .= "'Seems stopped',";
                $agentStatusCountList .= "'" . HOSTS_AGENT_STATUS_LIST['Linupdate_agent_status_seems_stopped_count'] . "',";
                $agentBackgroundColor .= "'#e0b05f',";
            }

            if (!empty(HOSTS_AGENT_STATUS_LIST['Linupdate_agent_status_stopped_count'])) {
                $agentStatusNameList .= "'Stopped',";
                $agentStatusCountList .= "'" . HOSTS_AGENT_STATUS_LIST['Linupdate_agent_status_stopped_count'] . "',";
                $agentBackgroundColor .= "'rgb(255, 99, 132)',";
            }

            if (!empty(HOSTS_AGENT_STATUS_LIST['Linupdate_agent_status_disabled_count'])) {
                $agentStatusNameList .= "'Disabled',";
                $agentStatusCountList .= "'" . HOSTS_AGENT_STATUS_LIST['Linupdate_agent_status_disabled_count'] . "',";
                $agentBackgroundColor .= "'rgb(255, 99, 132)',";
            }

            $labels = rtrim($agentStatusNameList, ',');
            $datas = rtrim($agentStatusCountList, ',');
            $backgrounds = rtrim($agentBackgroundColor, ',');
            $title = '';
            $chartId = 'hosts-agent-status-chart';

            include(ROOT . '/views/includes/charts/hosts-pie-chart.inc.php');
        }

        /**
         *  Agent release version chart
         */
        if (!empty(HOSTS_AGENT_VERSION_LIST)) {
            $agentNameList = '';
            $agentCountList = '';
            $agentBackgroundColor = '';

            foreach (HOSTS_AGENT_VERSION_LIST as $agent) {
                if (empty($agent['Linupdate_version'])) {
                    $agentNameList .= "'Unknow',";
                } else {
                    $agentNameList .= "'" . $agent['Linupdate_version'] . "',";
                }
                $agentCountList .= "'" . $agent['Linupdate_version_count'] . "',";
                $agentBackgroundColor .= "'" . $mycolor->randomColor() . "',";
            }

            $labels = rtrim($agentNameList, ',');
            $datas = rtrim($agentCountList, ',');
            $backgrounds = rtrim($agentBackgroundColor, ',');
            $title = '';
            $chartId = 'hosts-agent-version-chart';

            include(ROOT . '/views/includes/charts/hosts-pie-chart.inc.php');
        }
    }
}
