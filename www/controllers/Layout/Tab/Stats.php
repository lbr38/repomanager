<?php

namespace Controllers\Layout\Tab;

class Stats
{
    public static function render()
    {
        $myrepo = new \Controllers\Repo();
        $mystats = new \Controllers\Stat();

        $repoError = 0;

        /**
         *  Récupération du snapshot et environnement transmis
         */
        if (empty($_GET['id'])) {
            $repoError++;
        } else {
            $envId = \Controllers\Common::validateData($_GET['id']);
        }

        /**
         *  Le repo transmis doit être un numéro car il s'agit de l'ID en BDD
         */
        if (!is_numeric($envId)) {
            $repoError++;
        }

        /**
         *  A partir de l'ID fourni, on récupère les infos du repo
         */
        if ($repoError == 0) {
            $myrepo->setEnvId($envId);
            $myrepo->getAllById('', '', $envId);
        }

        /**
         *  Si un filtre a été sélectionné pour le graphique principal, la page est rechargée en arrière plan par jquery et récupère les données du graphique à partir du filtre sélectionné
         */
        if (!empty($_GET['repo_access_chart_filter'])) {
            if (\Controllers\Common::validateData($_GET['repo_access_chart_filter']) == "1week") {
                $repo_access_chart_filter = "1week";
            }
            if (\Controllers\Common::validateData($_GET['repo_access_chart_filter']) == "1month") {
                $repo_access_chart_filter = "1month";
            }
            if (\Controllers\Common::validateData($_GET['repo_access_chart_filter']) == "3months") {
                $repo_access_chart_filter = "3months";
            }
            if (\Controllers\Common::validateData($_GET['repo_access_chart_filter']) == "6months") {
                $repo_access_chart_filter = "6months";
            }
            if (\Controllers\Common::validateData($_GET['repo_access_chart_filter']) == "1year") {
                $repo_access_chart_filter = "1year";
            }
        }

        include_once(ROOT . '/views/stats.template.php');

        $mystats->closeConnection();
    }
}
