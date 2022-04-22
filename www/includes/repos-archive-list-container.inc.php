<h3>REPOS ARCHIVÉS</h3>

<!-- Bouton permettant de masquer le contenu de tous les groupes -->
<div class="relative">
    <span id="hideArchivedReposGroups" class="lowopacity pointer">Tout masquer <img src="ressources/icons/chevron-circle-down.png" class="icon" /></span>
</div>

<div class="repos-list-container">
    <?php
    /**
     *  Génération de la page en html et stockage en ram
     */
    if (CACHE_REPOS_LIST == "yes") {
        if (!file_exists(WWW_CACHE.'/repomanager-repos-archived-list-'.$_SESSION['role'].'.html')) {
            touch(WWW_CACHE.'/repomanager-repos-archived-list-'.$_SESSION['role'].'.html');
            ob_start();
            include(__DIR__.'/repos-archive-list.inc.php');
            $content = ob_get_clean();
            file_put_contents(WWW_CACHE.'/repomanager-repos-archived-list-'.$_SESSION['role'].'.html', $content);
        }
        /**
         *  Enfin on affiche le fichier html généré
         */
        include(WWW_CACHE.'/repomanager-repos-archived-list-'.$_SESSION['role'].'.html');
    } else {
        include(__DIR__.'/repos-archive-list.inc.php');
    } ?>
</div>