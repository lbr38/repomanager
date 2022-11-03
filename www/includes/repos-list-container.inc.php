<div id="title-button-div">
    <h3>REPOS</h3>
    
    <?php if (\Controllers\Common::isadmin()) : ?>
        <div id="title-button-container">
            <!-- Bouton "Affichage" -->
            <div id="ReposListDisplayToggleButton" class="slide-btn" title="Edit repos list display settings">
                <img src="resources/icons/cog.svg" />
                <span>Display settings</span>
            </div>

            <!-- Bouton "Gérer les groupes" -->
            <div id="GroupsListToggleButton" class="slide-btn" title="Manage repos groups">
                <img src="resources/icons/folder.svg" />
                <span>Manage groups</span>
            </div>

            <!-- Bouton "Gérer les repos/hôtes sources" -->
            <div id="source-repo-toggle-btn" class="slide-btn" title="Manage source repositories">
                <img src="resources/icons/internet.svg" />
                <span>Manage source repos</span>
            </div>

            <!-- Icone '+' faisant apparaitre la div cachée permettant de créer un nouveau repo/section -->
            <?php
            /**
             *  On affiche ce bouton uniquement sur index.php :
             */
            if ((__ACTUAL_URI__ == "/index.php") or (__ACTUAL_URI__ == "/")) : ?>
                <div id="newRepoToggleButton" action="new" class="slide-btn" title="Create a new mirror or local repository">
                    <img src="resources/icons/plus.svg" />
                    <span>Create a new repo</span>
                </div>
                <?php
            endif ?>
        </div>
        <?php
    endif ?>
</div>

<!-- Bouton permettant de masquer le contenu de tous les groupes de repos listés -->
<div class="relative">
    <span id="hideAllReposGroups" class="lowopacity pointer">Hide all<img src="resources/icons/down.svg" class="icon" /></span>
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