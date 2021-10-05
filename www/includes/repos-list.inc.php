<div class="div-flex">
    <h3>REPOS ACTIFS</h3>
    <div>
        <!-- Bouton "Affichage" -->
        <span id="ReposListDisplayToggleButton" class="pointer" title="Affichage">Affichage<img src="icons/cog.png" class="icon"/></span>
        <!-- Bouton "Gérer les groupes" -->
        <span id="GroupsListSlideUpButton" class="pointer" title="Gérer les groupes">Gérer les groupes<img src="icons/folder.png" class="icon"/></span>
        <!-- Bouton "Gérer les repos/hôtes sources" -->
        <span id="ReposSourcesSlideUpButton" class="pointer" title="Gérer les repos sources">Gérer les repos sources<img src="icons/world.png" class="icon"/></span>
        <!-- Icone '+' faisant apparaitre la div cachée permettant de créer un nouveau repo/section -->
        <?php // on affiche ce bouton uniquement sur index.php :
            if (($actual_uri == "/index.php") OR ($actual_uri == "/")) {
                if ($OS_FAMILY == "Redhat") { echo '<span id="newRepoSlideButton" class="pointer">Créer un nouveau repo<img class="icon" src="icons/plus.png" title="Créer un nouveau repo" /></span>'; }
                if ($OS_FAMILY == "Debian") { echo '<span id="newRepoSlideButton" class="pointer">Créer une nouvelle section<img class="icon" src="icons/plus.png" title="Créer une nouvelle section" /></span>'; }
            }
        ?>
    </div>
</div>

<script>
/* AFFICHAGE DES DIVS CACHÉES : Gérer les groupes, Gérer les repos sources, Créer un nouveau repo */
$(document).ready(function(){
    $("#GroupsListSlideUpButton").click(function(){           
        // affichage du div permettant de gérer les groupes
        $("#groupsDiv").animate({
            opacity: 1,
            width: '98%',
            padding: '10px'
        });
    });
    
    $("#GroupsListCloseButton").click(function(){
        // masquage du div permettant de gérer les groupes
        $("#groupsDiv").animate({
            opacity: 0,
            width: 0,
            padding: '0px'
        });
    });
});

$(document).ready(function(){
    $("#ReposSourcesSlideUpButton").click(function(){            
        // affichage du div permettant de gérer les sources
        $("#sourcesDiv").animate({
            width: '98%',
            padding: '10px',
            opacity: 1
        });
    });
    
    $("#ReposSourcesCloseButton").click(function(){
        // masquage du div permettant de gérer les sources
        $("#sourcesDiv").delay(50).animate({
            opacity: 0,
            width: 0,
            padding: '0px'
        });
    });
});

$(document).ready(function(){
    $("#newRepoSlideButton").click(function(){
        // affichage du div permettant de créer un nouveau repo/section à la place
        $("#newRepoSlideDiv").animate({
            opacity: 1,
            width: '98%',
            padding: '10px' // lorsqu'on affiche la section cachée, on ajoute un padding de 10 intérieur, voir la suite dans le fichier css pour '#newRepoSlideDiv'
        });
    });
    
    $("#newRepoCloseButton").click(function(){
        // masquage du div permettant de créer un nouveau repo/section
        $("#newRepoSlideDiv").animate({
            opacity: 0,
            width: 0,
            padding: '0px' // lorsqu'on masque la section, on retire le padding, afin que la section soit complètement masquée, voir la suite dans le fichier css pour '#newRepoSlideDiv'
        });
    });
});
</script>

<!-- div cachée, affichée par le bouton "Affichage" -->
<div id="divReposListDisplay" class="divReposListDisplay">
    <img id="DisplayCloseButton" title="Fermer" class="icon-lowopacity" src="icons/close.png" /> 
    <form action="<?php echo "$actual_uri"; ?>" method="post">
        <input type="hidden" name="action" value="configureDisplay" />
        <p><b>Informations</b></p>
        <?php
        // afficher ou non la taille des repos/sections
        echo '<label class="onoff-switch-label">';
        echo '<input type="hidden" name="printRepoSize" value="off" />'; // Valeur par défaut = "off" sauf si celle ci est overwritée par la checkbox cochée "on"
        echo '<input class="onoff-switch-input" type="checkbox" name="printRepoSize" value="on"'; if ($printRepoSize == "yes") { echo ' checked'; } echo ' />';
        echo '<span class="onoff-switch-slider"></span>';
        echo '</label>';
        echo '<span> Afficher la taille du repo</span><br>';

        // afficher ou non le type des repos (miroir ou local)
        echo '<label class="onoff-switch-label">';
        echo '<input type="hidden" name="printRepoType" value="off" />'; // Valeur par défaut = "off" sauf si celle ci est overwritée par la checkbox cochée "on"
        echo '<input class="onoff-switch-input" type="checkbox" name="printRepoType" value="on"'; if ($printRepoType == "yes") { echo ' checked'; } echo ' />';
        echo '<span class="onoff-switch-slider"></span>';
        echo '</label>';
        echo '<span> Afficher le type du repo</span><br>';

        // afficher ou non la signature gpg des repos
        echo '<label class="onoff-switch-label">';
        echo '<input type="hidden" name="printRepoSignature" value="off" />'; // Valeur par défaut = "off" sauf si celle ci est overwritée par la checkbox cochée "on"
        echo '<input class="onoff-switch-input" type="checkbox" name="printRepoSignature" value="on"'; if ($printRepoSignature == "yes") { echo ' checked'; } echo ' />';
        echo '<span class="onoff-switch-slider"></span>';
        echo '</label>';
        echo '<span> Afficher la signature du repo</span><br>';
        ?>

        <p><b>Filtrage</b></p>

        <?php
        // filtrer ou non par groupe
        echo '<label class="onoff-switch-label">';
        echo '<input type="hidden" name="filterByGroups" value="off" />'; // Valeur par défaut = "off" sauf si celle ci est overwritée par la checkbox cochée "on"
        echo '<input class="onoff-switch-input" type="checkbox" name="filterByGroups" value="on"'; if ($filterByGroups == "yes") { echo ' checked'; } echo ' />';
        echo '<span class="onoff-switch-slider"></span>';
        echo '</label>';
        echo '<span> Filtrer par groupes</span><br>';

        // concatener ou non les noms de repo/section
        echo '<label class="onoff-switch-label">';
        echo '<input type="hidden" name="concatenateReposName" value="off" />'; // Valeur par défaut = "off" sauf si celle ci est overwritée par la checkbox cochée "on"
        echo '<input class="onoff-switch-input" type="checkbox" name="concatenateReposName" value="on"'; if ($concatenateReposName == "yes") { echo ' checked'; } echo ' />';
        echo '<span class="onoff-switch-slider"></span>';
        echo '</label>';
        echo '<span> Vue simplifiée</span><br>';

        // Afficher ou non une ligne séparatrice entre chaque nom de repo/section
        echo '<label class="onoff-switch-label">';
        echo '<input type="hidden" name="dividingLine" value="off" />'; // Valeur par défaut = "off" sauf si celle ci est overwritée par la checkbox cochée "on"
        echo '<input class="onoff-switch-input" type="checkbox" name="dividingLine" value="on"'; if ($dividingLine == "yes") { echo ' checked'; } echo ' />';
        echo '<span class="onoff-switch-slider"></span>';
        echo '</label>';
        echo '<span> Ligne séparatrice</span><br>';

        // alterner ou non les couleurs dans la liste
        echo '<label class="onoff-switch-label">';
        echo '<input type="hidden" name="alternateColors" value="off" />'; // Valeur par défaut = "off" sauf si celle ci est overwritée par la checkbox cochée "on"
        echo '<input class="onoff-switch-input" type="checkbox" name="alternateColors" value="on"'; if ($alternateColors == "yes") { echo ' checked'; } echo ' />';
        echo '<span class="onoff-switch-slider"></span>';
        echo '</label>';
        echo '<span> Couleurs alternées</span>';

        // choix des couleurs :
        if ($alternateColors == "yes") {
            echo ' | ';
            echo '<label for="alternativeColor1"> Couleur 1 : </label>';
            echo "<input type=\"color\" class=\"color-xsmall\" name=\"alternativeColor1\" value=\"${alternativeColor1}\" id=\"alternativeColor1\">";
            echo '<label for="alternativeColor2"> Couleur 2 : </label>';
            echo "<input type=\"color\" class=\"color-xsmall\" name=\"alternativeColor2\" value=\"${alternativeColor2}\" id=\"alternativeColor2\">";
        } ?>

        <p><b>Cache</b></p>
        <p>Mettre en ram la liste des repos actifs dans <b>/dev/shm/</b> (expérimental)</p>
        <?php
        // mettre en cache ou non la liste des repos
        echo '<label class="onoff-switch-label">';
        echo '<input type="hidden" name="cache_repos_list" value="off" />'; // Valeur par défaut = "off" sauf si celle ci est overwritée par la checkbox cochée "on"
        echo '<input class="onoff-switch-input" type="checkbox" name="cache_repos_list" value="on"'; if ($cache_repos_list == "yes") { echo ' checked'; } echo ' />';
        echo '<span class="onoff-switch-slider"></span>';
        echo '</label>';
        echo '<span> Mise en cache</span><br>';
        ?>

        <br><br>
        <button type="submit" class="button-submit-medium-blue">Enregistrer</button>
    </form>
</div>
<script> // Afficher ou masquer la div qui gère les paramètres d'affichage (bouton "Affichage")
$(document).ready(function(){
   $("#ReposListDisplayToggleButton").click(function(){
      $("#divReposListDisplay").slideToggle(150);
      $(this).toggleClass("open");
    });
    // Le bouton down (petite croix) permet la même chose, il sera surtout utilisé pour fermer la div
    $('#DisplayCloseButton').click(function() {
      $('#divReposListDisplay').slideToggle(150);
    });
});
</script>

<!-- LISTE DES REPOS ACTIFS -->
<table class="list-repos">
<?php 
// Méthode génération de la page en html et stockage en ram (experimental)
if ($cache_repos_list == "yes") {
     if (!file_exists("${WWW_CACHE}/repomanager-repos-list.html")) {
        touch("${WWW_CACHE}/repomanager-repos-list.html");
        ob_start();
        include(__DIR__.'/repos-active-list.inc.php');
        $content = ob_get_clean();
        file_put_contents("${WWW_CACHE}/repomanager-repos-list.html", $content);
    }
    // Enfin on affiche le fichier html généré
    include("${WWW_CACHE}/repomanager-repos-list.html");
} else {
    include(__DIR__.'/repos-active-list.inc.php');
}

unset($repoGroups, $groupName, $repoGroupList, $rows, $row, $rowData, $repoFullInformations, $repoName, $repoDist, $repoSection, $repoEnv, $repoDate, $repoDescription, $repoSize, $repoLastName, $repoLastDist, $repoLastSection, $repoLastEnv);
?>
</table>