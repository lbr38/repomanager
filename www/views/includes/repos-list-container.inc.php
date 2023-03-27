<div id="title-button-div">
    <h3>REPOS</h3>
    
    <?php
    if (IS_ADMIN) : ?>
        <div id="title-button-container">
            <!-- Bouton "Affichage" -->
            <div class="slide-btn slide-panel-btn" slide-panel="display" title="Edit repos list display settings">
                <img src="assets/icons/cog.svg" />
                <span>Display settings</span>
            </div>

            <!-- Bouton "Gérer les groupes" -->
            <div class="slide-btn slide-panel-btn" slide-panel="repo-groups" title="Manage repos groups">
                <img src="assets/icons/folder.svg" />
                <span>Manage groups</span>
            </div>

            <!-- Bouton "Gérer les repos/hôtes sources" -->
            <div class="slide-btn slide-panel-btn" slide-panel="source-repo" title="Manage source repositories">
                <img src="assets/icons/internet.svg" />
                <span>Manage source repos</span>
            </div>

            <!-- Icone '+' faisant apparaitre la div cachée permettant de créer un nouveau repo/section -->
            <?php
            /**
             *  On affiche ce bouton uniquement sur / :
             */
            if (__ACTUAL_URI__ == "/") : ?>
                <div class="slide-btn slide-panel-btn" slide-panel="new-repo" title="Create a new mirror or local repository">
                    <img src="assets/icons/plus.svg" />
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
    <span id="hideAllReposGroups" class="lowopacity pointer" state="visible">Hide / show all<img src="assets/icons/up.svg" class="icon" /></span>
</div>

<div id="repos-list-container">
    <?php
    /**
     *  Génération de la page en html et stockage en ram
     */
    if (CACHE_REPOS_LIST == 'true') {
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