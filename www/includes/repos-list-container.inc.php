<div class="div-flex">
    <h3>REPOS ACTIFS</h3>
    <div>
        <!-- Bouton "Affichage" -->
        <span id="ReposListDisplayToggleButton" class="pointer" title="Affichage">Affichage<img src="ressources/icons/cog.png" class="icon"/></span>
        <!-- Bouton "Gérer les groupes" -->
        <span id="GroupsListToggleButton" class="pointer" title="Gérer les groupes">Gérer les groupes<img src="ressources/icons/folder.png" class="icon"/></span>
        <!-- Bouton "Gérer les repos/hôtes sources" -->
        <span id="ReposSourcesToggleButton" class="pointer" title="Gérer les repos sources">Gérer les repos sources<img src="ressources/icons/world.png" class="icon"/></span>
        <!-- Icone '+' faisant apparaitre la div cachée permettant de créer un nouveau repo/section -->
        <?php // on affiche ce bouton uniquement sur index.php :
            if ((__ACTUAL_URI__ == "/index.php") OR (__ACTUAL_URI__ == "/")) {
                if (OS_FAMILY == "Redhat") echo '<span id="newRepoToggleButton" class="pointer">Créer un nouveau repo<img class="icon" src="ressources/icons/plus.png" title="Créer un nouveau repo" /></span>';
                if (OS_FAMILY == "Debian") echo '<span id="newRepoToggleButton" class="pointer">Créer une nouvelle section<img class="icon" src="ressources/icons/plus.png" title="Créer une nouvelle section" /></span>';
            }
        ?>
    </div>
</div>

<!-- div cachée, affichée par le bouton "Affichage" -->
<div id="divReposListDisplay" class="divReposListDisplay">
    <img id="DisplayCloseButton" title="Fermer" class="icon-lowopacity" src="ressources/icons/close.png" /> 
    <form action="<?php echo __ACTUAL_URI__; ?>" method="post">
        <input type="hidden" name="action" value="configureDisplay" />
        <p><b>Informations</b></p>
        
        <!-- afficher ou non la taille des repos/sections -->
        <label class="onoff-switch-label">
            <input type="hidden" name="printRepoSize" value="off" />
            <input class="onoff-switch-input" type="checkbox" name="printRepoSize" value="on" <?php if (PRINT_REPO_SIZE == "yes") echo 'checked'; ?> />
            <span class="onoff-switch-slider"></span>
        </label>
        <span> Afficher la taille du repo</span><br>

        <!-- afficher ou non le type des repos (miroir ou local) -->
        <label class="onoff-switch-label">
            <input type="hidden" name="printRepoType" value="off" />
            <input class="onoff-switch-input" type="checkbox" name="printRepoType" value="on" <?php if (PRINT_REPO_TYPE == "yes") echo 'checked'; ?> />
            <span class="onoff-switch-slider"></span>
        </label>
        <span> Afficher le type du repo</span><br>

        <!-- afficher ou non la signature gpg des repos -->
        <label class="onoff-switch-label">
            <input type="hidden" name="printRepoSignature" value="off" />
            <input class="onoff-switch-input" type="checkbox" name="printRepoSignature" value="on" <?php if (PRINT_REPO_SIGNATURE == "yes") echo 'checked'; ?> />
            <span class="onoff-switch-slider"></span>
        </label>
        <span> Afficher la signature du repo</span><br>

        <p><b>Filtrage</b></p>

        <!-- filtrer ou non par groupe -->
        <label class="onoff-switch-label">
            <input type="hidden" name="filterByGroups" value="off" />
            <input class="onoff-switch-input" type="checkbox" name="filterByGroups" value="on" <?php if (FILTER_BY_GROUPS == "yes") echo 'checked'; ?> />
            <span class="onoff-switch-slider"></span>
        </label>
        <span> Filtrer par groupes</span><br>

        <!-- concatener ou non les noms de repo/section -->
        <label class="onoff-switch-label">
            <input type="hidden" name="concatenateReposName" value="off" />
            <input class="onoff-switch-input" type="checkbox" name="concatenateReposName" value="on" <?php if (CONCATENATE_REPOS_NAME == "yes") echo 'checked'; ?> />
            <span class="onoff-switch-slider"></span>
        </label>
        <span> Vue simplifiée</span><br>

        <!-- Afficher ou non une ligne séparatrice entre chaque nom de repo/section -->
        <label class="onoff-switch-label">
            <input type="hidden" name="dividingLine" value="off" />
            <input class="onoff-switch-input" type="checkbox" name="dividingLine" value="on" <?php if (DIVIDING_LINE == "yes") echo 'checked'; ?> />
            <span class="onoff-switch-slider"></span>
        </label>
        <span> Ligne séparatrice</span><br>

        <p><b>Cache</b></p>
        <p>Utiliser <b>/dev/shm</b> pour mettre en ram la liste des repos (recommandé)</p>
        <!-- mettre en cache ou non la liste des repos -->
        <label class="onoff-switch-label">
            <input type="hidden" name="cache_repos_list" value="off" />
            <input class="onoff-switch-input" type="checkbox" name="cache_repos_list" value="on" <?php if (CACHE_REPOS_LIST == "yes") echo 'checked'; ?> />
            <span class="onoff-switch-slider"></span>
        </label>
        <span> Mettre en cache dans /dev/shm</span><br>

        <br><br>
        <button type="submit" class="btn-medium-blue">Enregistrer</button>
    </form>
</div>

<!-- LISTE DES REPOS ACTIFS -->
<table class="list-repos">
<?php 
/**
 *  Génération de la page en html et stockage en ram
 */
if (CACHE_REPOS_LIST == "yes") {
     if (!file_exists(WWW_CACHE."/repomanager-repos-list.html")) {
        touch(WWW_CACHE."/repomanager-repos-list.html");
        ob_start();
        include(__DIR__.'/repos-active-list.inc.php');
        $content = ob_get_clean();
        file_put_contents(WWW_CACHE."/repomanager-repos-list.html", $content);
    }
    /**
     *  Enfin on affiche le fichier html généré
     */
    include(WWW_CACHE."/repomanager-repos-list.html");
} else {
    include(__DIR__.'/repos-active-list.inc.php');
}
unset($repoGroups, $groupName, $repoGroupList, $rows, $row, $rowData, $repoFullInformations, $repoName, $repoDist, $repoSection, $repoEnv, $repoDate, $repoDescription, $repoSize, $repoLastName, $repoLastDist, $repoLastSection, $repoLastEnv); ?>
</table>