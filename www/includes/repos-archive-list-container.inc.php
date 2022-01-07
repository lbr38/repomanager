<h3>REPOS ARCHIVÉS</h3>

<table class="list-repos">
<?php 
/**
 *  Génération de la page en html et stockage en ram
 */
if (CACHE_REPOS_LIST == "yes") {
     if (!file_exists(WWW_CACHE."/repomanager-repos-archived-list.html")) {
        touch(WWW_CACHE."/repomanager-repos-archived-list.html");
        ob_start();
        include(__DIR__.'/repos-archive-list.inc.php');
        $content = ob_get_clean();
        file_put_contents(WWW_CACHE."/repomanager-repos-archived-list.html", $content);
    }
    /**
     *  Enfin on affiche le fichier html généré
     */
    include(WWW_CACHE."/repomanager-repos-archived-list.html");
} else {
    include(__DIR__.'/repos-archive-list.inc.php');
}
unset($repoGroups, $groupName, $repoGroupList, $rows, $row, $rowData, $repoFullInformations, $repoName, $repoDist, $repoSection, $repoEnv, $repoDate, $repoDescription, $repoSize, $repoLastName, $repoLastDist, $repoLastSection, $repoLastEnv); ?>
</table>