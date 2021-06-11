<div class="div-flex">
    <h5>REPOS ACTIFS</h5>
    <div>
        <!-- Bouton "Affichage" -->
        <span id="ReposListDisplayToggleButton" class="pointer" title="Affichage">Affichage<img src="icons/cog.png" class="icon"/></span>
        <!-- Bouton "Gérer les groupes" -->
        <span id="GroupsListSlideUpButton" class="pointer" title="Gérer les groupes">Gérer les groupes<img src="icons/folder.png" class="icon"/></span>
        <!-- Bouton "Gérer les repos/hôtes sources" -->
        <?php
            if ($OS_FAMILY == "Redhat") { echo '<span id="ReposSourcesSlideUpButton" class="pointer" title="Gérer les repos sources">Gérer les repos sources<img src="icons/world.png" class="icon"/></span>'; }
            if ($OS_FAMILY == "Debian") { echo '<span id="ReposSourcesSlideUpButton" class="pointer" title="Gérer les hôtes sources">Gérer les hôtes sources<img src="icons/world.png" class="icon"/></span>'; }
        ?>
        <!-- Icone '+' faisant apparaitre la div cachée permettant de créer un nouveau repo/section -->
        <?php // on affiche ce bouton uniquement sur index.php :
            if (($actual_uri == "/index.php") OR ($actual_uri == "/")) {
                if ($OS_FAMILY == "Redhat") { echo '<span id="newRepoSlideButton" class="pointer">Créer un nouveau repo<img class="icon" src="icons/plus.png" title="Créer un nouveau repo" /></span>'; }
                if ($OS_FAMILY == "Debian") { echo '<span id="newRepoSlideButton" class="pointer">Créer une nouvelle section<img class="icon" src="icons/plus.png" title="Créer une nouvelle section" /></span>'; }
            }
        ?>
    </div>
</div>

<!-- div cachée, affichée par le bouton "Affichage" -->
<div id="divReposListDisplay" class="divReposListDisplay">
    <img id="DisplayCloseButton" title="Fermer" class="icon-lowopacity" src="icons/close.png" /> 
    <form action="<?php echo "$actual_uri"; ?>" method="post">
        <input type="hidden" name="action" value="configureDisplay" />
    <?php
        // afficher ou non la taille des repos/sections
        echo '<input type="hidden" name="printRepoSize" value="off" />'; // Valeur par défaut = "off" sauf si celle ci est overwritée par la checkbox cochée "on"
        if ($printRepoSize == "yes") {
            echo '<input type="checkbox" id="printRepoSize" name="printRepoSize" value="on" checked />';
        } else {
            echo '<input type="checkbox" id="printRepoSize" name="printRepoSize" value="on" />';
        }
        echo '<label for="printRepoSize">Afficher la taille du repo</label><br>';

        // afficher ou non le type des repos (miroir ou local)
        echo '<input type="hidden" name="printRepoType" value="off" />';
        if ($printRepoType == "yes") {
            echo '<input type="checkbox" id="printRepoType" name="printRepoType" value="on" checked />';
        } else {
            echo '<input type="checkbox" id="printRepoType" name="printRepoType" value="on" />';
        }
        echo '<label for="printRepoType">Afficher le type du repo</label><br>';

        // afficher ou non la signature gpg des repos
        echo '<input type="hidden" name="printRepoSignature" value="off" />';
        if ($printRepoSignature == "yes") {
            echo '<input type="checkbox" id="printRepoSignature" name="printRepoSignature" value="on" checked />';
        } else {
            echo '<input type="checkbox" id="printRepoSignature" name="printRepoSignature" value="on" />';
        }
        echo '<label for="printRepoSignature">Afficher la signature du repo</label><br>';

        // filtrer ou non par groupe
        echo '<input type="hidden" name="filterByGroups" value="off" />'; // Valeur par défaut = "off" sauf si celle ci est overwritée par la checkbox cochée "on"
        if ($filterByGroups == "yes") {
            echo '<input type="checkbox" id="filterByGroups" name="filterByGroups" value="on" checked />';
        } else {
            echo '<input type="checkbox" id="filterByGroups" name="filterByGroups" value="on" />';
        }
        echo '<label for="filterByGroups">Filtrer par groupes</label><br>';

        // concatener ou non les noms de repo/section
        echo '<input type="hidden" name="concatenateReposName" value="off" />'; // Valeur par défaut = "off" sauf si celle ci est overwritée par la checkbox cochée "on"
        if ($concatenateReposName == "yes") {
            echo '<input type="checkbox" id="concatenateReposName" name="concatenateReposName" value="on" checked />';
        } else {
            echo '<input type="checkbox" id="concatenateReposName" name="concatenateReposName" value="on" />';
        }
        echo '<label for="concatenateReposName">Vue simplifiée</label><br>';

        // Afficher ou non une ligne séparatrice entre chaque nom de repo/section
        echo '<input type="hidden" name="dividingLine" value="off" />'; // Valeur par défaut = "off" sauf si celle ci est overwritée par la checkbox cochée "on"
        if ($dividingLine == "yes") {
            echo '<input type="checkbox" id="dividingLine" name="dividingLine" value="on" checked />';
        } else {
            echo '<input type="checkbox" id="dividingLine" name="dividingLine" value="on" />';
        }
        echo '<label for="dividingLine">Ligne séparatrice</label><br>';

        // alterner ou non les couleurs dans la liste
        echo '<input type="hidden" name="alternateColors" value="off" />'; // Valeur par défaut = "off" sauf si celle ci est overwritée par la checkbox cochée "on"
        if ($alternateColors == "yes") {
            echo '<input type="checkbox" id="alternateColors" name="alternateColors" value="on" checked />';
        } else {
            echo '<input type="checkbox" id="alternateColors" name="alternateColors" value="on" />';
        }
        echo '<label for="alternateColors">Couleurs alternées</label>';
        // choix des couleurs :
        if ($alternateColors == "yes") {
            echo ' | ';
            echo '<label for="alternativeColor1"> Couleur 1 : </label>';
            echo "<input type=\"color\" class=\"color-xsmall\" name=\"alternativeColor1\" value=\"${alternativeColor1}\" id=\"alternativeColor1\">";
            echo '<label for="alternativeColor2"> Couleur 2 : </label>';
            echo "<input type=\"color\" class=\"color-xsmall\" name=\"alternativeColor2\" value=\"${alternativeColor2}\" id=\"alternativeColor1\">";
        }
        ?>
        <br><br>
        <button type="submit" class="button-submit-medium-blue">Enregistrer</button>
    </form>
</div>
<script> // Afficher ou masquer la div qui gère les paramètres d'affichage (bouton "Affichage")
$(document).ready(function(){
   $("#ReposListDisplayToggleButton").click(function(){
      $("div.divReposListDisplay").slideToggle(150);
      $(this).toggleClass("open");
    });
    // Le bouton down (petite croix) permet la même chose, il sera surtout utilisé pour fermer la div
    $('#DisplayCloseButton').click(function() {
      $('div.divReposListDisplay').slideToggle(150);
    });
});
</script>

<!-- div cachée, affichée par le bouton "Gérer les repos/hôtes sources" -->
<!-- REPOS/HOTES SOURCES -->
<?php include('common-repos-sources.inc.php'); ?>

<!-- LISTE DES REPOS ACTIFS -->
<table class="list-repos">
<?php
$i = 0; // initialise un compteur qui sera incrémenté pour chaque conftoggX (affichage d'une div cachée contenant la conf des repo, bouton Conf)
// Filtre par noms de groupes
if ($filterByGroups == "yes") {
    // Méthode génération de la page en html et stockage en ram (experimental) :
    if ($cache_repos_list == "yes") {
         if (!file_exists("${WWW_CACHE}/repos-list-filter-group.html")) {
            touch("${WWW_CACHE}/repos-list-filter-group.html");
            ob_start();
            include('common-repos-list-filter-groups.inc.php');
            $content = ob_get_clean();
            file_put_contents("${WWW_CACHE}/repos-list-filter-group.html", $content);
        }
        // Enfin on affiche le fichier html généré
        include("${WWW_CACHE}/repos-list-filter-group.html");
    } else {
        include('common-repos-list-filter-groups.inc.php');
    }
}

// Liste des repos sans filtre par groupe
if ($filterByGroups == "no") {
    // Méthode génération de la page en html et stockage en ram (experimental) :
    if ($cache_repos_list == "yes") {
        if (!file_exists("${WWW_CACHE}/repos-list-no-filter.html")) {
            touch("${WWW_CACHE}/repos-list-no-filter.html");
            ob_start();
            include('common-repos-list-no-filter.inc.php');
            $content = ob_get_clean();
            file_put_contents("${WWW_CACHE}/repos-list-no-filter.html", $content);
        }
        // Enfin on affiche le fichier html généré
        include("${WWW_CACHE}/repos-list-no-filter.html");
    } else {
        include('common-repos-list-no-filter.inc.php');
    }
}

unset($i, $j, $repoGroups, $groupName, $repoGroupList, $rows, $row, $rowData, $repoFullInformations, $repoName, $repoDist, $repoSection, $repoEnv, $repoDate, $repoDescription, $repoSize, $repoLastName, $repoLastDist, $repoLastSection, $repoLastEnv);  
?>
</table>