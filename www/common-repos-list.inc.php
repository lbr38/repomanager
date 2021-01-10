<div class="div-flex">
    <h5>REPOS ACTIFS</h5>
    <div>
        <!-- Bouton "Affichage" -->
        <a href="#" id="ReposListDisplayToggle" title="Affichage"><span>Affichage</span><img src="icons/cog.png" class="icon"/></a>
        <!-- Bouton "Gérer les groupes" -->
        <a href="#" id="GroupsListToggle" title="Gérer les groupes"><span>Gérer les groupes</span><img src="icons/folder.png" class="icon"/></a>
        <!-- Bouton "Gérer les repos/hôtes sources" -->
        <?php
            if ($OS_TYPE == "rpm") { echo "<a href=\"#\" id=\"reposSourcesToggle\" title=\"Gérer les repos sources\"><span>Gérer les repos sources</span><img src=\"icons/world.png\" class=\"icon\"/></a>"; }
            if ($OS_TYPE == "deb") { echo "<a href=\"#\" id=\"reposSourcesToggle\" title=\"Gérer les hôtes sources\"><span>Gérer les hôtes sources</span><img src=\"icons/world.png\" class=\"icon\"/></a>"; }
        ?>
    </div>
</div>

<!-- div cachée, affichée par le bouton "Affichage" -->
<div id="divReposListDisplay" class="divReposListDisplay">
    <form action="" method="post">
    <?php
        echo "<input type=\"hidden\" name=\"printRepoSize\" value=\"off\" />"; // Valeur par défaut = "off" sauf si celle ci est overwritée par la checkbox cochée "on"
        if ($printRepoSize == "yes") {
            echo "<input type=\"checkbox\" id=\"printRepoSize\" name=\"printRepoSize\" value=\"on\" checked />";
        } else {
            echo "<input type=\"checkbox\" id=\"printRepoSize\" name=\"printRepoSize\" value=\"on\" />";
        }
        echo "<label for=\"printRepoSize\">Taille du repo</label>";
        
        echo "<br>";

        echo "<input type=\"hidden\" name=\"filterByGroups\" value=\"off\" />"; // Valeur par défaut = "off" sauf si celle ci est overwritée par la checkbox cochée "on"
        if ($filterByGroups == "yes") {
            echo "<input type=\"checkbox\" id=\"filterByGroups\" name=\"filterByGroups\" value=\"on\" checked />";
        } else {
            echo "<input type=\"checkbox\" id=\"filterByGroups\" name=\"filterByGroups\" value=\"on\" />";
        }
        echo "<label for=\"filterByGroups\">Filtrer par groupes</label>";

        echo "<br>";

        echo "<input type=\"hidden\" name=\"concatenateReposName\" value=\"off\" />"; // Valeur par défaut = "off" sauf si celle ci est overwritée par la checkbox cochée "on"
        if ($concatenateReposName == "yes") {
            echo "<input type=\"checkbox\" id=\"concatenateReposName\" name=\"concatenateReposName\" value=\"on\" checked />";
        } else {
            echo "<input type=\"checkbox\" id=\"concatenateReposName\" name=\"concatenateReposName\" value=\"on\" />";
        }
        echo "<label for=\"concatenateReposName\">Vue simplifiée</label>";

        echo "<br>";

        echo "<input type=\"hidden\" name=\"alternateColors\" value=\"off\" />"; // Valeur par défaut = "off" sauf si celle ci est overwritée par la checkbox cochée "on"
        if ($alternateColors == "yes") {
            echo "<input type=\"checkbox\" id=\"alternateColors\" name=\"alternateColors\" value=\"on\" checked />";
        } else {
            echo "<input type=\"checkbox\" id=\"alternateColors\" name=\"alternateColors\" value=\"on\" />";
        }
        echo "<label for=\"alternateColors\">Couleurs alternées</label>";
        echo "<br>";
        ?>
        <label for="alternativeColor1">Couleur 1 :</label>
        <input type="text" id="alternativeColor1" class="input-small" name="alternativeColor1" placeholder="couleur 1" value="<?php echo "$alternativeColor1"; ?>">
        <label for="alternativeColor2">Couleur 2 :</label>
        <input type="text" id="alternativeColor2" class="input-small" name="alternativeColor2" placeholder="couleur 2" value="<?php echo "$alternativeColor2"; ?>">
        <br>
        <br>
        <button type="submit" class="button-submit-medium-blue">Enregistrer</button>
    </form>
</div>

<script> // Afficher ou masquer la div qui gère les paramètres d'affichage (bouton "Affichage")
  $(document).ready(function(){
  $("a#ReposListDisplayToggle").click(function(){
    $("div.divReposListDisplay").slideToggle(100);
    $(this).toggleClass("open");
  });
});
</script>


<table class="list-repos">
<?php
$i = 0; // initialise un compteur qui sera incrémenté pour chaque conftoggX (affichage d'une div cachée contenant la conf des repo, bouton Conf)

// Filtre par noms de groupes
if ($filterByGroups == "yes") {
    $repoGroupsFile = file_get_contents($REPO_GROUPS_FILE); // récupération de tout le contenu du fichier de groupes
    $repoGroups = shell_exec("grep '^\[@.*\]' $REPO_GROUPS_FILE"); // récupération de tous les noms de groupes si il y en a 
    // on va afficher le tableau de groupe seulement si la commande précédente a trouvé des groupes dans le fichier (résultat non vide) :
    if (!empty($repoGroups)) {
        $repoGroups = preg_split('/\s+/', trim($repoGroups)); // on éclate le résultat précédent car tout a été récupéré sur une seule ligne
        foreach($repoGroups as $groupName) {
            $listColor = 'color1'; // initialise des variables permettant de changer la couleur dans l'affichage de la liste des repos
            $groupName = str_replace(["[", "]"], "", $groupName);
            echo "<tr><td colspan=\"100%\"><b>${groupName}</b></td></tr>";
            // On va récupérer la liste des repos du groupe
            $repoGroupList = shell_exec("sed -n '/\[${groupName}\]/,/\[/p' $REPO_GROUPS_FILE | sed '/^$/d' | grep -v '^\['"); // récupération des repos de ce groupe, en supprimant les lignes vides
            if (!empty($repoGroupList)) {
                $repoGroupList = preg_split('/\s+/', trim($repoGroupList)); // on éclate le résultat précédent car tout a été récupéré sur une seule ligne
                // Affichage de l'entête (Nom, Distrib, Section, Env, Date...)*
                echo "<tbody>";
                echo "<tr class=\"reposListHead\">";
                echo "<td class=\"td-auto\"></td>";
                echo "<td>Nom</td>";
                if ($OS_TYPE == "deb") {
                    echo "<td class=\"td-xsmall\"></td>"; // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque distribution
                    echo "<td>Distribution</td>";
                    echo "<td class=\"td-xsmall\"></td>"; // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque section
                    echo "<td>Section</td>";
                }
                echo "<td>Env</td>";
                echo "<td>Date</td>";
                if ($printRepoSize == "yes") { // On affiche la taille des repos seulement si souhaité
                    echo "<td>Taille</td>";
                }
                echo "<td>Description</td>";
                echo "</tr>";                    
                // Pour chaque repo dans le groupe, on va devoir récupérer l'ensemble de ses informations dans le fichier repos.list, car le fichier de groups.list ne contient pas tout. Par exemple il ne contient pas les environnement, ni la date ou la description
                // D'abord on récupère le peu d'informations du repo contenu dans groups.list :
                foreach($repoGroupList as $repoName) {
                    // initialise des variables permettant de simplifier l'affichage dans la liste des repos
                    $repoLastName = '';
                    $repoLastDist = '';
                    $repoLastSection = '';
                    $repoLastEnv = '';
                    $rowData = explode(',', $repoName);
                    $repoName = str_replace(['Name=', '"'], "", $rowData[0]); // on récupère la données et on formate à la volée en retirant Name=""
                    if ($OS_TYPE == "deb") { // si Debian on récupère aussi la distrib et la section
                        $repoDist = str_replace(['Dist=', '"'], "", $rowData[1]); // on récupère la données et on formate à la volée en retirant Dist=""
                        $repoSection = str_replace(['Section=', '"'], "", $rowData[2]); // on récupère la données et on formate à la volée en retirant Section=""
                    }
                    // Puis on recupère les informations manquantes dans le fichier repos.list
                    if ($OS_TYPE == "rpm") {
                        $repoFullInformations = shell_exec("grep '^Name=\"${repoName}\",Realname=\".*\"' $REPO_FILE");
                    }
                    if ($OS_TYPE == "deb") {
                        $repoFullInformations = shell_exec("grep '^Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\",Section=\"${repoSection}\"' $REPO_FILE");
                    }
                    $repoFullInformations = explode('Name=', $repoFullInformations);
                    $repoFullInformations = array_filter($repoFullInformations); // on nettoie les valeurs vide de l'array
                    //echo "<pre>";
                    //print_r($repoFullInformations);
                    //echo "</pre>";
                    foreach($repoFullInformations as $repoFull) {
                        $rowData = explode(',', $repoFull);
                        $repoName = str_replace(['Name=', '"'], "", $rowData[0]); // on récupère la données et on formate à la volée en retirant Name=""
                        if ($OS_TYPE == "rpm") {
                            $repoEnv = str_replace(['Env=', '"'], '', $rowData[2]); // on récupère la données et on formate à la volée en retirant Env=""
                            $repoDate = str_replace(['Date=', '"'], '', $rowData[3]); // on récupère la données et on formate à la volée en retirant Date=""
                            $repoDescription = str_replace(['Description=', '"'], '', $rowData[4]); // on récupère la données et on formate à la volée en retirant Description=""
                        }
                        if ($OS_TYPE == "deb") { // si Debian on récupère aussi la distrib et la section
                            $repoDist = str_replace(['Dist=', '"'], "", $rowData[2]); // on récupère la données et on formate à la volée en retirant Dist=""
                            $repoSection = str_replace(['Section=', '"'], "", $rowData[3]); // on récupère la données et on formate à la volée en retirant Section=""
                            $repoEnv = str_replace(['Env=', '"'], "", $rowData[4]); // on récupère la données et on formate à la volée en retirant Env=""
                            $repoDate = str_replace(['Date=', '"'], "", $rowData[5]); // on récupère la données et on formate à la volée en retirant Date=""
                            $repoDescription = str_replace(['Description=', '"'], "", $rowData[6]); // on récupère la données et on formate à la volée en retirant Description=""
                        }
                        // On calcule la taille des repos seulement si souhaité (car cela peut être une grosse opération si le repo est gros) :
                        if ($OS_TYPE == "rpm" AND $printRepoSize == "yes") {
                            $repoSize = exec("du -hs ${REPOS_DIR}/${repoDate}_${repoName} | awk '{print $1}'");
                        }
                        if ($OS_TYPE == "deb" AND $printRepoSize == "yes") {
                            $repoSize = exec("du -hs ${REPOS_DIR}/${repoName}/${repoDist}/${repoDate}_${repoSection} | awk '{print $1}'");
                        }
                        // Affichage des données
                        // on souhaite afficher des couleurs identiques si le nom du repo est identique avec le précédent affiché. Si ce n'est pas le cas alors on affiche une couleur différente afin de différencier les repos dans la liste
                        if ($alternateColors == "yes" AND $repoName !== $repoLastName) {
                            if ($listColor == "color1") { $listColor = 'color2'; }
                            elseif ($listColor == "color2") { $listColor = 'color1'; }
                        }
                        echo "<tr class=\"$listColor\">";
                        echo "<td>";
                        // Affichage de l'icone "corbeille" pour supprimer le repo
                        if ($OS_TYPE == "rpm") { // si rpm on doit présicer repoEnv dans l'url
                            echo "<a href=\"traitement.php?actionId=deleteRepo&repoName=${repoName}&repoEnv=${repoEnv}\"><img class=\"icon-lowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer le repo ${repoName} (${repoEnv})\" /></a>";
                        }
                        if ($OS_TYPE == "deb") {
                            echo "<a href=\"traitement.php?actionId=deleteRepo&repoName=${repoName}\"><img class=\"icon-lowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer le repo ${repoName}\" /></a>";
                        }

                        // Affichage de l'icone "dupliquer" pour dupliquer le repo
                        if ($OS_TYPE == "rpm") {
                            echo "<a href=\"traitement.php?actionId=duplicateRepo&repoName=${repoName}&repoEnv=${repoEnv}\"><img class=\"icon-lowopacity-blue\" src=\"icons/duplicate.png\" title=\"Dupliquer le repo ${repoName} (${repoEnv})\" /></a>";
                        }
                        if ($OS_TYPE == "deb") {
                            echo "<a href=\"traitement.php?actionId=duplicateRepo&repoName=${repoName}&repoDist=${repoDist}&repoSection=${repoSection}&repoEnv=${repoEnv}\"><img class=\"icon-lowopacity-blue\" src=\"icons/duplicate.png\" title=\"Dupliquer le repo ${repoName} avec sa distribution ${repoDist} et sa section ${repoSection} (${repoEnv})\" /></a>";
                        }

                        // Affichage de l'icone "terminal" pour afficher la conf repo à mettre en place sur les serveurs
                        echo "<a href=\"#\"><img id=\"conftogg${i}\" class=\"icon-lowopacity\" src=\"icons/code.png\" /></a>";

                        echo "</td>";

                        // Si la vue simplifiée est activée (masquage du nom de repo si similaire au précédent) :
                        if ($concatenateReposName == "yes" AND $repoName === $repoLastName) {
                            echo "<td></td>";
                        } else {
                            echo "<td>$repoName</td>";
                        }

                        if ($OS_TYPE == "deb") {
                            // Si la vue simplifiée est activée (masquage du nom de repo si similaire au précédent) :
                            if ($concatenateReposName == "yes" AND $repoName === $repoLastName AND $repoDist === $repoLastDist) {
                                echo "<td class=\"td-xsmall\"></td>";
                                echo "<td></td>";
                            } else {
                                echo "<td class=\"td-xsmall\"><a href=\"traitement.php?actionId=deleteDist&repoName=${repoName}&repoDist=${repoDist}\"><img class=\"icon-verylowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer la distribution ${repoDist}\" /></a></td>"; // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque distribution
                                echo "<td>$repoDist</td>";
                            }
                            // Si la vue simplifiée est activée (masquage du nom de repo si similaire au précédent) :
                            if ($concatenateReposName == "yes" AND $repoName === $repoLastName AND $repoDist === $repoLastDist AND $repoSection === $repoLastSection) {
                                echo "<td class=\"td-xsmall\"><a href=\"traitement.php?actionId=deleteSection&repoName=${repoName}&repoDist=${repoDist}&repoSection=${repoSection}&repoEnv=${repoEnv}\"><img class=\"icon-verylowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer la section ${repoSection} (${repoEnv})\" /></a></td>"; // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque section
                                echo "<td></td>";
                            } else {
                                echo "<td class=\"td-xsmall\"><a href=\"traitement.php?actionId=deleteSection&repoName=${repoName}&repoDist=${repoDist}&repoSection=${repoSection}&repoEnv=${repoEnv}\"><img class=\"icon-verylowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer la section ${repoSection} (${repoEnv})\" /></a></td>"; // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque section
                                echo "<td>$repoSection</td>";
                            }
                        }
                        // Affichage de l'env en couleur
                        // On regarde d'abord combien d'environnements sont configurés. Si il n'y a qu'un environement, l'env restera blanc.
                        if ($REPO_DEFAULT_ENV === $REPO_LAST_ENV) { // Cas où il n'y a qu'un seul env
                            echo "<td class=\"td-whitebackground\"><span>$repoEnv</span></td>";
                        } elseif ($repoEnv === $REPO_DEFAULT_ENV) { 
                            echo "<td class=\"td-greenbackground\"><span>$repoEnv</span></td>";
                        } elseif ($repoEnv === $REPO_LAST_ENV) {
                            echo "<td class=\"td-redbackground\"><span>$repoEnv</span></td>";
                        } else {
                            echo "<td class=\"td-whitebackground\"><span>$repoEnv</span></td>";
                        }
                        echo "<td>$repoDate</td>";
                        if ($printRepoSize == "yes") {
                            echo "<td>$repoSize</td>";
                        }
                        echo "<td title=\"${repoDescription}\">$repoDescription</td>"; // avec un title afin d'afficher une info-bulle au survol (utile pour les descriptions longues)
                        echo "</tr>";
                        echo "<tr>";
                            echo "<td colspan=\"100%\">";
                            echo "<div id=\"confdiv${i}\" class=\"divReposConf\">";
                            echo "<pre>";
                            if ($OS_TYPE == "rpm") {
                                echo "A exécuter directement depuis le terminal de la machine : \n\necho -e '# Repo ${repoName} (${repoEnv}) sur ${WWW_HOSTNAME}\n[${REPO_FILES_PREFIX}${repoName}_${repoEnv}]\nname=Repo ${repoName} sur ${WWW_HOSTNAME}\ncomment=Repo ${repoName} sur ${WWW_HOSTNAME}\nbaseurl=https://${WWW_HOSTNAME}/${repoName}_${repoEnv}\nenabled=1\ngpgkey=https://${WWW_HOSTNAME}/gpgkeys/${WWW_HOSTNAME}.pub\ngpgcheck=1' > /etc/yum.repos.d/${REPO_FILES_PREFIX}${repoName}.repo";
                            }
                            if ($OS_TYPE == "deb") {
                                echo "A exécuter directement depuis le terminal de la machine : \n\necho -e '# Repo ${repoName} (${repoEnv}) sur ${WWW_HOSTNAME}\ndeb https://${WWW_HOSTNAME}/${repoName}/${repoDist}/${repoSection}_${repoEnv} ${repoDist} ${repoSection}' > /etc/apt/sources.list.d/${REPO_FILES_PREFIX}${repoName}_${repoDist}_${repoSection}.list";
                            }
                            echo "</pre>";
                            echo "</div>";
                            echo "</td>";

                        echo "</tr>";
                        echo "</tbody>";

                        // Afficher ou masquer la div qui donne la conf des repos à mettre en place sur les serveurs clients (bouton ">_") :
                        echo "<script>";
                        echo "$(document).ready(function(){";
                        echo "$(\"img#conftogg${i}\").click(function(){";
                        echo "$(\"div#confdiv${i}\").slideToggle(250);";
                        echo "$(this).toggleClass(\"open\");";
                        echo "});";
                        echo "});";
                        echo "</script>";

                        // alternance des couleurs :
                        $repoLastName = $repoName;
                        if ($OS_TYPE == "deb") {
                            $repoLastDist = $repoDist;
                            $repoLastSection = $repoSection;
                        }
                        $i++;
                    }
                }
            } else {
                echo "<tr><td colspan=\"100%\">Il n'y a aucun repo dans ce groupe</td></tr>";
            }
            echo "<tr><td><br></td></tr>"; // saut de ligne avant chaque nom de groupe
        }
    }
    // Enfin, on affiche un dernier groupe "Defaut" qui contiendra les repos qui ne sont pas dans des groupes. Ce groupe Défaut s'affiche même si il n'y a aucun repo dans groups.list
    echo "<tr><td><b>Défaut</b></td></tr>";
    echo "<tr class=\"reposListHead\">";
    echo "<td class=\"td-auto\"></td>";
    echo "<td>Nom</td>";
    if ($OS_TYPE == "deb") {
        echo "<td class=\"td-xsmall\"></td>"; // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque distribution
        echo "<td>Distribution</td>";
        echo "<td class=\"td-xsmall\"></td>"; // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque section
        echo "<td>Section</td>";
    }
    echo "<td>Env</td>";
    echo "<td>Date</td>";
    if ($printRepoSize == "yes") { // On affiche la taille des repos seulement si souhaité
        echo "<td>Taille</td>";
    }
    echo "<td>Description</td>";
    echo "</tr>";
    $repoLastName = '';
    $repoLastDist = '';
    $repoLastSection = '';
    $repoLastEnv = '';
    // On récupère tout le contenu du fichier de liste de repos, 
    // puis pour chaque repo, si celui-ci n'apparait dans aucun groupe alors on l'affiche ici dans le groupe "Défaut"
    $repoFile = file_get_contents($REPO_FILE);
    $rows = explode("\n", $repoFile);
    foreach($rows as $row) {
        if(!empty($row) AND $row !== "[REPOS]") { // on ne traite pas les lignes vides ni la ligne [REPOS] (1ère ligne du fichier)
            //get row data
            $rowData = explode(',', $row);
            if ($OS_TYPE == "rpm") {
                $repoName = str_replace(['Name=', '"'], '', $rowData[0]);
                $repoEnv = str_replace(['Env=', '"'], '', $rowData[2]);
                $repoDate = str_replace(['Date=', '"'], '', $rowData[3]);
                $repoDescription = str_replace(['Description=', '"'], '', $rowData[4]);
            }
            if ($OS_TYPE == "deb") {
                $repoName = str_replace(['Name=', '"'], '', $rowData[0]);
                $repoDist = str_replace(['Dist=', '"'], '', $rowData[2]);
                $repoSection = str_replace(['Section=', '"'], '', $rowData[3]);
                $repoEnv = str_replace(['Env=', '"'], '', $rowData[4]);
                $repoDate = str_replace(['Date=', '"'], '', $rowData[5]);
                $repoDescription = str_replace(['Description=', '"'], '', $rowData[6]);
            }

            // On cherche dans le fichier de groupes si le repo apparait :
            if ($OS_TYPE == "rpm") {
                $checkIfRepoIsInAGroup = exec("grep '^Name=\"${repoName}\"' $REPO_GROUPS_FILE");
            }
            if ($OS_TYPE == "deb") {
                $checkIfRepoIsInAGroup = exec("grep '^Name=\"${repoName}\",Dist=\"${repoDist}\",Section=\"${repoSection}\"' $REPO_GROUPS_FILE");
            }
            // Si le repo apparait dans un groupe alors on n'exécute pas la suite et on traite l'itération suivante de la boucle :
            if (!empty($checkIfRepoIsInAGroup)) {
                continue; 
            }
            // On calcule la taille des repos seulement si souhaité (car cela peut être une grosse opération si le repo est gros) :
            if ($OS_TYPE == "rpm" AND $printRepoSize == "yes") {
                $repoSize = exec("du -hs ${REPOS_DIR}/${repoDate}_${repoName} | awk '{print $1}'");
            }
            if ($OS_TYPE == "deb" AND $printRepoSize == "yes") {
                $repoSize = exec("du -hs ${REPOS_DIR}/${repoName}/${repoDist}/${repoDate}_${repoSection} | awk '{print $1}'");
            }
            // Affichage des données
            // on souhaite afficher des couleurs identiques si le nom du repo est identique avec le précédent affiché. Si ce n'est pas le cas alors on affiche une couleur différente afin de différencier les repos dans la liste
            if ($alternateColors == "yes" AND $repoName !== $repoLastName) {
                if ($listColor == "color1") { $listColor = 'color2'; }
                elseif ($listColor == "color2") { $listColor = 'color1'; }
            }
            echo "<tr class=\"$listColor\">";
            echo "<td>";
            // Affichage de l'icone "corbeille" pour supprimer le repo
            if ($OS_TYPE == "rpm") { // si rpm on doit présicer repoEnv dans l'url
                echo "<a href=\"traitement.php?actionId=deleteRepo&repoName=${repoName}&repoEnv=${repoEnv}\"><img class=\"icon-lowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer le repo ${repoName} (${repoEnv})\" /></a>";
            }
            if ($OS_TYPE == "deb") {
                echo "<a href=\"traitement.php?actionId=deleteRepo&repoName=${repoName}\"><img class=\"icon-lowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer le repo ${repoName}\" /></a>";
            }

            // Affichage de l'icone "dupliquer" pour dupliquer le repo
            if ($OS_TYPE == "rpm") {
                echo "<a href=\"traitement.php?actionId=duplicateRepo&repoName=${repoName}&repoEnv=${repoEnv}\"><img class=\"icon-lowopacity-blue\" src=\"icons/duplicate.png\" title=\"Dupliquer le repo ${repoName} (${repoEnv})\" /></a>";
            }
            if ($OS_TYPE == "deb") {
                echo "<a href=\"traitement.php?actionId=duplicateRepo&repoName=${repoName}&repoDist=${repoDist}&repoSection=${repoSection}&repoEnv=${repoEnv}\"><img class=\"icon-lowopacity-blue\" src=\"icons/duplicate.png\" title=\"Dupliquer le repo ${repoName} avec sa distribution ${repoDist} et sa section ${repoSection} (${repoEnv})\" /></a>";
            }

            // Affichage de l'icone "terminal" pour afficher la conf repo à mettre en place sur les serveurs
            echo "<a href=\"#\"><img id=\"conftogg${i}\" class=\"icon-lowopacity\" src=\"icons/code.png\" /></a>";

            echo "</td>";
            // Si la vue simplifiée est activée (masquage du nom de repo si similaire au précédent) :
            if ($concatenateReposName == "yes" AND $repoName === $repoLastName) {
                echo "<td></td>";
            } else {
                echo "<td>$repoName</td>";
            }

            if ($OS_TYPE == "deb") {
                // Si la vue simplifiée est activée (masquage du nom de repo si similaire au précédent) :
                if ($concatenateReposName == "yes" AND $repoName === $repoLastName AND $repoDist === $repoLastDist) {
                    echo "<td class=\"td-xsmall\"></td>";
                    echo "<td></td>";
                } else {
                    echo "<td class=\"td-xsmall\"><a href=\"traitement.php?actionId=deleteDist&repoName=${repoName}&repoDist=${repoDist}\"><img class=\"icon-verylowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer la distribution ${repoDist}\" /></a></td>"; // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque distribution
                    echo "<td>$repoDist</td>";
                }
                // Si la vue simplifiée est activée (masquage du nom de repo si similaire au précédent) :
                if ($concatenateReposName == "yes" AND $repoName === $repoLastName AND $repoDist === $repoLastDist AND $repoSection === $repoLastSection) {
                    echo "<td class=\"td-xsmall\"><a href=\"traitement.php?actionId=deleteSection&repoName=${repoName}&repoDist=${repoDist}&repoSection=${repoSection}&repoEnv=${repoEnv}\"><img class=\"icon-verylowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer la section ${repoSection} (${repoEnv})\" /></a></td>"; // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque section
                    echo "<td></td>";
                } else {
                    echo "<td class=\"td-xsmall\"><a href=\"traitement.php?actionId=deleteSection&repoName=${repoName}&repoDist=${repoDist}&repoSection=${repoSection}&repoEnv=${repoEnv}\"><img class=\"icon-verylowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer la section ${repoSection} (${repoEnv})\" /></a></td>"; // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque section
                    echo "<td>$repoSection</td>";
                }
            }
            // Affichage de l'env en couleur
            // On regarde d'abord combien d'environnements sont configurés. Si il n'y a qu'un environement, l'env restera blanc.
            if ($REPO_DEFAULT_ENV === $REPO_LAST_ENV) { // Cas où il n'y a qu'un seul env
                echo "<td class=\"td-whitebackground\"><span>$repoEnv</span></td>";
            } elseif ($repoEnv === $REPO_DEFAULT_ENV) { 
                echo "<td class=\"td-greenbackground\"><span>$repoEnv</span></td>";
            } elseif ($repoEnv === $REPO_LAST_ENV) {
                echo "<td class=\"td-redbackground\"><span>$repoEnv</span></td>";
            } else {
                echo "<td class=\"td-whitebackground\"><span>$repoEnv</span></td>";
            }
            echo "<td>$repoDate</td>";
            if ($printRepoSize == "yes") {
                echo "<td>$repoSize</td>";
            }
            echo "<td title=\"${repoDescription}\">$repoDescription</td>"; // avec un title afin d'afficher une info-bulle au survol (utile pour les descriptions longues)
            echo "</tr>";
            echo "<tr>";
            echo "<td colspan=\"100%\">";
            echo "<div id=\"confdiv${i}\" class=\"divReposConf\">";
            echo "<pre>";
            if ($OS_TYPE == "rpm") {
                echo "A exécuter directement depuis le terminal de la machine : \n\necho -e '# Repo ${repoName} (${repoEnv}) sur ${WWW_HOSTNAME}\n[${REPO_FILES_PREFIX}${repoName}_${repoEnv}]\nname=Repo ${repoName} sur ${WWW_HOSTNAME}\ncomment=Repo ${repoName} sur ${WWW_HOSTNAME}\nbaseurl=https://${WWW_HOSTNAME}/${repoName}_${repoEnv}\nenabled=1\ngpgkey=https://${WWW_HOSTNAME}/gpgkeys/${WWW_HOSTNAME}.pub\ngpgcheck=1' > /etc/yum.repos.d/${REPO_FILES_PREFIX}${repoName}.repo";
            }
            if ($OS_TYPE == "deb") {
                echo "A exécuter directement depuis le terminal de la machine : \n\necho -e '# Repo ${repoName} (${repoEnv}) sur ${WWW_HOSTNAME}\ndeb https://${WWW_HOSTNAME}/${repoName}/${repoDist}/${repoSection}_${repoEnv} ${repoDist} ${repoSection}' > /etc/apt/sources.list.d/${REPO_FILES_PREFIX}${repoName}_${repoDist}_${repoSection}.list";
            }
            echo "</pre>";
            echo "</div>";
            echo "</td>";
            echo "</tr>";
            // Afficher ou masquer la div qui donne la conf des repos à mettre en place sur les serveurs clients (bouton ">_") :
            echo "<script>";
            echo "$(document).ready(function(){";
            echo "$(\"img#conftogg${i}\").click(function(){";
            echo "$(\"div#confdiv${i}\").slideToggle(250);";
            echo "$(this).toggleClass(\"open\");";
            echo "});";
            echo "});";
            echo "</script>";
            // alternance des couleurs :
            $repoLastName = $repoName;
            if ($OS_TYPE == "deb") {
                $repoLastDist = $repoDist;
                $repoLastSection = $repoSection;
            }
            $i++;
        }
    }
}


// Liste des repos sans filtre par groupe
if ($filterByGroups == "no") {
    // initialise des variables permettant de simplifier l'affichage dans la liste des repos
    $repoLastName = '';
    $repoLastDist = '';
    $repoLastSection = '';
    $repoLastEnv = '';
    $listColor = 'color1'; // initialise des variables permettant de changer la couleur dans l'affichage de la liste des repos

    echo "<thead>";
    echo "<tr>";
    echo "<td></td>";
    echo "<td>Nom</td>";
    if ($OS_TYPE == "deb") {
        echo "<td class=\"td-xsmall\"></td>"; // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque distribution
        echo "<td>Distribution</td>";
        echo "<td class=\"td-xsmall\"></td>"; // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque section
        echo "<td>Section</td>";
    }
    echo "<td>Env</td>";
    echo "<td>Date</td>";
    if ($printRepoSize == "yes") { // On affiche la taille des repos seulement si souhaité
        echo "<td>Taille</td>";
    }
    echo "<td>Description</td>";
    echo "</tr>";
    echo "</thead>";
    $repoFile = file_get_contents($REPO_FILE);
    $rows = explode("\n", $repoFile);
    foreach($rows as $row) {
        if(!empty($row) AND $row !== "[REPOS]") { // on ne traite pas les lignes vides ni la ligne [REPOS] (1ère ligne du fichier)
            //get row data
            $rowData = explode(',', $row);
            if ($OS_TYPE == "rpm") {
                $repoName = str_replace(['Name=', '"'], '', $rowData[0]);
                $repoEnv = str_replace(['Env=', '"'], '', $rowData[2]);
                $repoDate = str_replace(['Date=', '"'], '', $rowData[3]);
                $repoDescription = str_replace(['Description=', '"'], '', $rowData[4]);
            }
            if ($OS_TYPE == "deb") {
                $repoName = str_replace(['Name=', '"'], '', $rowData[0]);
                $repoDist = str_replace(['Dist=', '"'], '', $rowData[2]);
                $repoSection = str_replace(['Section=', '"'], '', $rowData[3]);
                $repoEnv = str_replace(['Env=', '"'], '', $rowData[4]);
                $repoDate = str_replace(['Date=', '"'], '', $rowData[5]);
                $repoDescription = str_replace(['Description=', '"'], '', $rowData[6]);
            }
            // On calcule la taille des repos seulement si souhaité (car cela peut être une grosse opération si le repo est gros) :
            if ($OS_TYPE == "rpm" AND $printRepoSize == "yes") {
                $repoSize = exec("du -hs ${REPOS_DIR}/${repoDate}_${repoName} | awk '{print $1}'");
            }
            if ($OS_TYPE == "deb" AND $printRepoSize == "yes") {
                $repoSize = exec("du -hs ${REPOS_DIR}/${repoName}/${repoDist}/${repoDate}_${repoSection} | awk '{print $1}'");
            }
            // Affichage des données
            // on souhaite afficher des couleurs identiques si le nom du repo est identique avec le précédent affiché. Si ce n'est pas le cas alors on affiche une couleur différente afin de différencier les repos dans la liste
            if ($alternateColors == "yes" AND $repoName !== $repoLastName) {
                if ($listColor == "color1") { $listColor = 'color2'; }
                elseif ($listColor == "color2") { $listColor = 'color1'; }
            }
            echo "<tr class=\"$listColor\">";
            echo "<td>";
            // Affichage de l'icone "corbeille" pour supprimer le repo
            if ($OS_TYPE == "rpm") { // si rpm on doit présicer repoEnv dans l'url
                echo "<a href=\"traitement.php?actionId=deleteRepo&repoName=${repoName}&repoEnv=${repoEnv}\"><img class=\"icon-lowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer le repo ${repoName} (${repoEnv})\" /></a>";
            }
            if ($OS_TYPE == "deb") {
                echo "<a href=\"traitement.php?actionId=deleteRepo&repoName=${repoName}\"><img class=\"icon-lowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer le repo ${repoName}\" /></a>";
            }

            // Affichage de l'icone "dupliquer" pour dupliquer le repo
            if ($OS_TYPE == "rpm") {
                echo "<a href=\"traitement.php?actionId=duplicateRepo&repoName=${repoName}&repoEnv=${repoEnv}\"><img class=\"icon-lowopacity-blue\" src=\"icons/duplicate.png\" title=\"Dupliquer le repo ${repoName} (${repoEnv})\" /></a>";
            }
            if ($OS_TYPE == "deb") {
                echo "<a href=\"traitement.php?actionId=duplicateRepo&repoName=${repoName}&repoDist=${repoDist}&repoSection=${repoSection}&repoEnv=${repoEnv}\"><img class=\"icon-lowopacity-blue\" src=\"icons/duplicate.png\" title=\"Dupliquer le repo ${repoName} avec sa distribution ${repoDist} et sa section ${repoSection} (${repoEnv})\" /></a>";
            }

            // Affichage de l'icone "terminal" pour afficher la conf repo à mettre en place sur les serveurs
            echo "<a href=\"#\"><img id=\"conftogg${i}\" class=\"icon-lowopacity\" src=\"icons/code.png\" /></a>";

            echo "</td>";
            // Si la vue simplifiée est activée (masquage du nom de repo si similaire au précédent) :
            if ($concatenateReposName == "yes" AND $repoName === $repoLastName) {
                echo "<td></td>";
            } else {
                echo "<td>$repoName</td>";
            }

            if ($OS_TYPE == "deb") {
                // Si la vue simplifiée est activée (masquage du nom de repo si similaire au précédent) :
                if ($concatenateReposName == "yes" AND $repoName === $repoLastName AND $repoDist === $repoLastDist) {
                    echo "<td class=\"td-xsmall\"></td>";
                    echo "<td></td>";
                } else {
                    echo "<td class=\"td-xsmall\"><a href=\"traitement.php?actionId=deleteDist&repoName=${repoName}&repoDist=${repoDist}\"><img class=\"icon-verylowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer la distribution ${repoDist}\" /></a></td>"; // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque distribution
                    echo "<td>$repoDist</td>";
                }
                // Si la vue simplifiée est activée (masquage du nom de repo si similaire au précédent) :
                if ($concatenateReposName == "yes" AND $repoName === $repoLastName AND $repoDist === $repoLastDist AND $repoSection === $repoLastSection) {
                    echo "<td class=\"td-xsmall\"><a href=\"traitement.php?actionId=deleteSection&repoName=${repoName}&repoDist=${repoDist}&repoSection=${repoSection}&repoEnv=${repoEnv}\"><img class=\"icon-verylowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer la section ${repoSection} (${repoEnv})\" /></a></td>"; // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque section
                    echo "<td></td>";
                } else {
                    echo "<td class=\"td-xsmall\"><a href=\"traitement.php?actionId=deleteSection&repoName=${repoName}&repoDist=${repoDist}&repoSection=${repoSection}&repoEnv=${repoEnv}\"><img class=\"icon-verylowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer la section ${repoSection} (${repoEnv})\" /></a></td>"; // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque section
                    echo "<td>$repoSection</td>";
                }
            }
            // Affichage de l'env en couleur
            // On regarde d'abord combien d'environnements sont configurés. Si il n'y a qu'un environement, l'env restera blanc.
            if ($REPO_DEFAULT_ENV === $REPO_LAST_ENV) { // Cas où il n'y a qu'un seul env
                echo "<td class=\"td-whitebackground\"><span>$repoEnv</span></td>";
            } elseif ($repoEnv === $REPO_DEFAULT_ENV) { 
                echo "<td class=\"td-greenbackground\"><span>$repoEnv</span></td>";
            } elseif ($repoEnv === $REPO_LAST_ENV) {
                echo "<td class=\"td-redbackground\"><span>$repoEnv</span></td>";
            } else {
                echo "<td class=\"td-whitebackground\"><span>$repoEnv</span></td>";
            }

            echo "<td>$repoDate</td>";
            if ($printRepoSize == "yes") {
                echo "<td>$repoSize</td>";
            }
            echo "<td title=\"${repoDescription}\">$repoDescription</td>"; // avec un title afin d'afficher une info-bulle au survol (utile pour les descriptions longues)
            echo "</tr>";
            echo "<tr>";
            echo "<td colspan=\"100%\">";
            echo "<div id=\"confdiv${i}\" class=\"divReposConf\">";
            echo "<pre>";
            if ($OS_TYPE == "rpm") {
                echo "A exécuter directement depuis le terminal de la machine : \n\necho -e '# Repo ${repoName} (${repoEnv}) sur ${WWW_HOSTNAME}\n[${REPO_FILES_PREFIX}${repoName}_${repoEnv}]\nname=Repo ${repoName} sur ${WWW_HOSTNAME}\ncomment=Repo ${repoName} sur ${WWW_HOSTNAME}\nbaseurl=https://${WWW_HOSTNAME}/${repoName}_${repoEnv}\nenabled=1\ngpgkey=https://${WWW_HOSTNAME}/gpgkeys/${WWW_HOSTNAME}.pub\ngpgcheck=1' > /etc/yum.repos.d/${REPO_FILES_PREFIX}${repoName}.repo";
            }
            if ($OS_TYPE == "deb") {
                echo "A exécuter directement depuis le terminal de la machine : \n\necho -e '# Repo ${repoName} (${repoEnv}) sur ${WWW_HOSTNAME}\ndeb https://${WWW_HOSTNAME}/${repoName}/${repoDist}/${repoSection}_${repoEnv} ${repoDist} ${repoSection}' > /etc/apt/sources.list.d/${REPO_FILES_PREFIX}${repoName}_${repoDist}_${repoSection}.list";
            }
            echo "</pre>";
            echo "</div>";
            echo "</td>";
            echo "</tr>";

            // Afficher ou masquer la div qui donne la conf des repos à mettre en place sur les serveurs clients (bouton ">_") :
            echo "<script>";
            echo "$(document).ready(function(){";
            echo "$(\"img#conftogg${i}\").click(function(){";
            echo "$(\"div#confdiv${i}\").slideToggle(250);";
            echo "$(this).toggleClass(\"open\");";
            echo "});";
            echo "});";
            echo "</script>";
        }
        $i++;
        if (!empty($repoName)) { $repoLastName = $repoName; }
        if ($OS_TYPE == "deb") {
            if (!empty($repoDist)) { $repoLastDist = $repoDist; }
            if (!empty($repoSection)) { $repoLastSection = $repoSection; }
        }
    }
}?>
</tbody>
</table>