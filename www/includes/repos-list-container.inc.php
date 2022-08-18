<div class="div-flex">
    <h3>REPOS</h3>
    <?php if (Controllers\Common::isadmin()) { ?>
    <div>
        <!-- Bouton "Affichage" -->
        <span id="ReposListDisplayToggleButton" class="pointer" title="Display settings">Settings<img src="resources/icons/cog.png" class="icon"/></span>
        <!-- Bouton "Gérer les groupes" -->
        <span id="GroupsListToggleButton" class="pointer" title="Manage repositories groups">Manage groups<img src="resources/icons/folder.png" class="icon"/></span>
        <!-- Bouton "Gérer les repos/hôtes sources" -->
        <span id="ReposSourcesToggleButton" class="pointer" title="Manage source repositories">Manage source repos<img src="resources/icons/world.png" class="icon"/></span>
        <!-- Icone '+' faisant apparaitre la div cachée permettant de créer un nouveau repo/section -->
        <?php // on affiche ce bouton uniquement sur index.php :
        if ((__ACTUAL_URI__ == "/index.php") or (__ACTUAL_URI__ == "/")) {
            echo '<span id="newRepoToggleButton" action="new" class="pointer">Create a new repo<img class="icon" src="resources/icons/plus.png" title="Create a new mirror or local repository" /></span>';
        }
        ?>
    </div>
    <?php } ?>
</div>

<!-- Bouton permettant de masquer le contenu de tous les groupes de repos listés -->
<div class="relative">
    <span id="hideAllReposGroups" class="lowopacity pointer">Hide all<img src="resources/icons/chevron-circle-down.png" class="icon" /></span>
</div>

<div id="repos-list-container">
    <?php
    /**
     *  Génération de la page en html et stockage en ram
     */
    if (CACHE_REPOS_LIST == "yes") {
        if (!file_exists(WWW_CACHE . '/repomanager-repos-list-' . $_SESSION['role'] . '.html')) {
            \Controllers\Common::generateCache($_SESSION['role']);
        }
        /**
         *  Enfin on affiche le fichier html généré
         */
        include(WWW_CACHE . '/repomanager-repos-list-' . $_SESSION['role'] . '.html');
    } else {
        include(__DIR__ . '/repos-list.inc.php');
    } ?>
</div>