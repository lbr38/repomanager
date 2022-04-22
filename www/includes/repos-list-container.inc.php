<div class="div-flex">
    <h3>REPOS</h3>
    <?php if (Models\Common::isadmin()) { ?>
    <div>
        <!-- Bouton "Affichage" -->
        <span id="ReposListDisplayToggleButton" class="pointer" title="Affichage">Affichage<img src="ressources/icons/cog.png" class="icon"/></span>
        <!-- Bouton "Gérer les groupes" -->
        <span id="GroupsListToggleButton" class="pointer" title="Gérer les groupes">Gérer les groupes<img src="ressources/icons/folder.png" class="icon"/></span>
        <!-- Bouton "Gérer les repos/hôtes sources" -->
        <span id="ReposSourcesToggleButton" class="pointer" title="Gérer les repos sources">Gérer les repos sources<img src="ressources/icons/world.png" class="icon"/></span>
        <!-- Icone '+' faisant apparaitre la div cachée permettant de créer un nouveau repo/section -->
        <?php // on affiche ce bouton uniquement sur index.php :
        if ((__ACTUAL_URI__ == "/index.php") or (__ACTUAL_URI__ == "/")) {
            if (OS_FAMILY == "Redhat") {
                echo '<span id="newRepoToggleButton" action="new" class="pointer">Créer un nouveau repo<img class="icon" src="ressources/icons/plus.png" title="Créer un nouveau repo" /></span>';
            }
            if (OS_FAMILY == "Debian") {
                echo '<span id="newRepoToggleButton" action="new" class="pointer">Créer une nouvelle section<img class="icon" src="ressources/icons/plus.png" title="Créer une nouvelle section" /></span>';
            }
        }
        ?>
    </div>
    <?php } ?>
</div>

<!-- Bouton permettant de masquer le contenu de tous les groupes de repos listés -->
<div class="relative">
    <span id="hideAllReposGroups" class="lowopacity pointer">Tout masquer <img src="ressources/icons/chevron-circle-down.png" class="icon" /></span>
</div>

<div class="repos-list-container">
    <?php

    /**
     *  Génération de la page en html et stockage en ram
     */

    if (CACHE_REPOS_LIST == "yes") {
        if (!file_exists(WWW_CACHE . '/repomanager-repos-list-' . $_SESSION['role'] . '.html')) {
            touch(WWW_CACHE . '/repomanager-repos-list-' . $_SESSION['role'] . '.html');
            ob_start();
            include(__DIR__ . '/repos-active-list.inc.php');
            $content = ob_get_clean();
            file_put_contents(WWW_CACHE . '/repomanager-repos-list-' . $_SESSION['role'] . '.html', $content);
        }
        /**
         *  Enfin on affiche le fichier html généré
         */
        include(WWW_CACHE . '/repomanager-repos-list-' . $_SESSION['role'] . '.html');
    } else {
        include(__DIR__ . '/repos-active-list.inc.php');
    } ?>
</div>