<?php
    $listColor = 'color1'; // initialise des variables permettant de changer la couleur dans l'affichage de la liste des repos
    $repoGroups = shell_exec("grep '^\[@.*\]' $GROUPS_CONF"); // récupération de tous les noms de groupes si il y en a 
    // on va afficher le tableau de groupe seulement si la commande précédente a trouvé des groupes dans le fichier (résultat non vide) :
    if (!empty($repoGroups)) {
        $repoGroups = preg_split('/\s+/', trim($repoGroups)); // on éclate le résultat précédent car tout a été récupéré sur une seule ligne
        foreach($repoGroups as $groupName) {
            $listColor = 'color1'; // réinitialise à color1 à chaque groupe
            $groupName = strtr($groupName, ['[' => '', ']' => '']);
            echo "<tr><td colspan=\"100%\"><b>${groupName}</b></td></tr>";
            // On va récupérer la liste des repos du groupe
            $repoGroupList = shell_exec("sed -n '/\[${groupName}\]/,/\[/p' $GROUPS_CONF | sed '/^$/d' | grep -v '^\['"); // récupération des repos de ce groupe, en supprimant les lignes vides
            if (!empty($repoGroupList)) {
                $repoGroupList = preg_split('/\s+/', trim($repoGroupList)); // on éclate le résultat précédent car tout a été récupéré sur une seule ligne
                // Affichage de l'entête (Repo, Distrib, Section, Env, Date...)*
                echo '<tr class="reposListHead">';
                echo '<td class="td-fit"></td>';
                echo '<td>Repo</td>';
                if ($OS_FAMILY == "Debian") {
                    echo '<td class="td-xsmall"></td>'; // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque distribution
                    echo '<td>Distribution</td>';
                    echo '<td class="td-xsmall"></td>'; // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque section
                    echo '<td>Section</td>';
                }
                echo '<td>Env</td>';
                echo '<td class="td-xsmall"></td>'; // td de toute petite taille, permettra d'afficher une icone 'link' avant chaque date
                echo '<td>Date</td>';
                if ($printRepoSize == "yes") { // On affiche la taille des repos seulement si souhaité
                    echo '<td>Taille</td>';
                }
                echo '<td>Description</td>';
                echo '</tr>';                    
                // Pour chaque repo dans le groupe, on va devoir récupérer l'ensemble de ses informations dans le fichier repos.list, car le fichier de groups.list ne contient pas tout. Par exemple il ne contient pas les environnement, ni la date ou la description
                // D'abord on récupère le peu d'informations du repo contenu dans groups.list :
                foreach($repoGroupList as $repoName) {
                    // initialise des variables permettant de simplifier l'affichage dans la liste des repos
                    $repoLastName = '';
                    $repoLastDist = '';
                    $repoLastSection = '';
                    $repoLastEnv = '';
                    $rowData = explode(',', $repoName);
                    $repoName = strtr($rowData['0'], ['Name=' => '', '"' => '']); // on récupère la données et on formate à la volée en retirant Name=""
                    if ($OS_FAMILY == "Debian") { // si Debian on récupère aussi la distrib et la section
                        $repoDist = strtr($rowData['1'], ['Dist=' => '', '"' => ''] ); // on récupère la données et on formate à la volée en retirant Dist=""
                        $repoSection = strtr($rowData['2'], ['Section=' => '', '"' => '']); // on récupère la données et on formate à la volée en retirant Section=""
                    }
                    // Puis on recupère les informations manquantes dans le fichier repos.list
                    if ($OS_FAMILY == "Redhat") {
                        $repoFullInformations = explode('Name=', shell_exec("grep '^Name=\"${repoName}\",Realname=\".*\"' $REPOS_LIST"));
                    }
                    if ($OS_FAMILY == "Debian") {
                        $repoFullInformations = explode('Name=', shell_exec("grep '^Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\",Section=\"${repoSection}\"' $REPOS_LIST"));
                    }
                    //$repoFullInformations = explode('Name=', $repoFullInformations);
                    $repoFullInformations = array_filter($repoFullInformations); // on nettoie les valeurs vide de l'array
                    foreach($repoFullInformations as $repoFull) {
                        $rowData = explode(',', $repoFull);
                        $repoName = strtr($rowData['0'], ['Name=' => '', '"' => '']); // on récupère la données et on formate à la volée en retirant Name=""
                        if ($OS_FAMILY == "Redhat") {
                            $repoEnv = strtr($rowData['2'], ['Env=' => '', '"' => '']); // on récupère la données et on formate à la volée en retirant Env=""
                            $repoDate = strtr($rowData['3'], ['Date=' => '', '"' => '']); // on récupère la données et on formate à la volée en retirant Date=""
                            $repoDescription = strtr($rowData['4'], ['Description=' => '', '"' => '']); // on récupère la données et on formate à la volée en retirant Description=""
                        }
                        if ($OS_FAMILY == "Debian") { // si Debian on récupère aussi la distrib et la section
                            $repoDist = strtr($rowData['2'], ['Dist=' => '', '"' => '']); // on récupère la données et on formate à la volée en retirant Dist=""
                            $repoSection = strtr($rowData['3'], ['Section=' => '', '"' => '']); // on récupère la données et on formate à la volée en retirant Section=""
                            $repoEnv = strtr($rowData['4'], ['Env=' => '', '"' => '']); // on récupère la données et on formate à la volée en retirant Env=""
                            $repoDate = strtr($rowData['5'], ['Date=' => '', '"' => '']); // on récupère la données et on formate à la volée en retirant Date=""
                            $repoDescription = strtr($rowData['6'], ['Description=' => '', '"' => '']); // on récupère la données et on formate à la volée en retirant Description=""
                        }
                        // On calcule la taille des repos seulement si souhaité (car cela peut être une grosse opération si le repo est gros) :
                        if ($OS_FAMILY == "Redhat" AND $printRepoSize == "yes") {
                            $repoSize = exec("du -hs ${REPOS_DIR}/${repoDate}_${repoName} | awk '{print $1}'");
                        }
                        if ($OS_FAMILY == "Debian" AND $printRepoSize == "yes") {
                            $repoSize = exec("du -hs ${REPOS_DIR}/${repoName}/${repoDist}/${repoDate}_${repoSection} | awk '{print $1}'");
                        }
                        // Affichage des données
                        // on souhaite afficher des couleurs identiques si le nom du repo est identique avec le précédent affiché. Si ce n'est pas le cas alors on affiche une couleur différente afin de différencier les repos dans la liste
                        if ($alternateColors == "yes" AND $repoName !== $repoLastName) {
                            if ($listColor == "color1") { $listColor = 'color2'; }
                            elseif ($listColor == "color2") { $listColor = 'color1'; }
                        }
                        // Affichage ou non d'une ligne séparatrice entre chaque repo/section
                        if ($dividingLine === "yes") {
                            if ($repoName !== $repoLastName) {
                                echo '<tr>';
                                echo '<td colspan="100%"><hr></td>';
                                echo '</tr>';
                            }
                        }
                        echo "<tr class=\"$listColor\">";
                        echo '<td class="td-fit">';
                        // Affichage de l'icone "corbeille" pour supprimer le repo
                        if ($OS_FAMILY == "Redhat") { // si rpm on doit présicer repoEnv dans l'url
                            echo "<a href=\"check.php?actionId=deleteRepo&repoName=${repoName}&repoEnv=${repoEnv}\"><img class=\"icon-lowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer le repo ${repoName} (${repoEnv})\" /></a>";
                        }
                        if ($OS_FAMILY == "Debian") {
                            echo "<a href=\"check.php?actionId=deleteRepo&repoName=${repoName}\"><img class=\"icon-lowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer le repo ${repoName}\" /></a>";
                        }
                        // Affichage de l'icone "dupliquer" pour dupliquer le repo
                        if ($OS_FAMILY == "Redhat") {
                            echo "<a href=\"check.php?actionId=duplicateRepo&repoName=${repoName}&repoEnv=${repoEnv}\"><img class=\"icon-lowopacity-blue\" src=\"icons/duplicate.png\" title=\"Dupliquer le repo ${repoName} (${repoEnv})\" /></a>";
                        }
                        if ($OS_FAMILY == "Debian") {
                            echo "<a href=\"check.php?actionId=duplicateRepo&repoName=${repoName}&repoDist=${repoDist}&repoSection=${repoSection}&repoEnv=${repoEnv}\"><img class=\"icon-lowopacity-blue\" src=\"icons/duplicate.png\" title=\"Dupliquer le repo ${repoName} avec sa distribution ${repoDist} et sa section ${repoSection} (${repoEnv})\" /></a>";
                        }

                        // Affichage de l'icone "terminal" pour afficher la conf repo à mettre en place sur les serveurs
                        echo "<img id=\"clientConfToggle${i}\" class=\"icon-lowopacity\" src=\"icons/code.png\" title=\"Afficher la configuration client\" />";

                        // Affichage de l'icone 'update' pour mettre à jour le repo/section. On affiche seulement si l'env du repo/section = $DEFAULT_ENV
                        if ($repoEnv === $DEFAULT_ENV) {
                            if ($OS_FAMILY == "Redhat") {
                                echo "<a href=\"check.php?actionId=updateRepo&repoName=${repoName}\"><img class=\"icon-lowopacity-blue\" src=\"icons/update.png\" title=\"Mettre à jour le repo ${repoName} (${repoEnv})\" /></a>";
                            }
                            if ($OS_FAMILY == "Debian") {
                                echo "<a href=\"check.php?actionId=updateRepo&repoName=${repoName}&repoDist=${repoDist}&repoSection=${repoSection}\"><img class=\"icon-lowopacity-blue\" src=\"icons/update.png\" title=\"Mettre à jour la section ${repoName} (${repoEnv})\" /></a>";
                            }
                        }
                        echo '</td>';

                        // Si la vue simplifiée est activée (masquage du nom de repo si similaire au précédent) :
                        if ($concatenateReposName == "yes" AND $repoName === $repoLastName) {
                            echo '<td></td>';
                        } else {
                            echo "<td>$repoName</td>";
                        }

                        if ($OS_FAMILY == "Debian") {
                            // Si la vue simplifiée est activée (masquage du nom de repo si similaire au précédent) :
                            if ($concatenateReposName == "yes" AND $repoName === $repoLastName AND $repoDist === $repoLastDist) {
                                echo '<td class="td-xsmall"></td>';
                                echo '<td></td>';
                            } else {
                                echo "<td class=\"td-xsmall\"><a href=\"check.php?actionId=deleteDist&repoName=${repoName}&repoDist=${repoDist}\"><img class=\"icon-verylowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer la distribution ${repoDist}\" /></a></td>"; // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque distribution
                                echo "<td>$repoDist</td>";
                            }
                            // Si la vue simplifiée est activée (masquage du nom de repo si similaire au précédent) :
                            if ($concatenateReposName == "yes" AND $repoName === $repoLastName AND $repoDist === $repoLastDist AND $repoSection === $repoLastSection) {
                                echo "<td class=\"td-xsmall\"><a href=\"check.php?actionId=deleteSection&repoName=${repoName}&repoDist=${repoDist}&repoSection=${repoSection}&repoEnv=${repoEnv}\"><img class=\"icon-verylowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer la section ${repoSection} (${repoEnv})\" /></a></td>"; // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque section
                                echo '<td></td>';
                            } else {
                                echo "<td class=\"td-xsmall\"><a href=\"check.php?actionId=deleteSection&repoName=${repoName}&repoDist=${repoDist}&repoSection=${repoSection}&repoEnv=${repoEnv}\"><img class=\"icon-verylowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer la section ${repoSection} (${repoEnv})\" /></a></td>"; // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque section
                                echo "<td>$repoSection</td>";
                            }
                        }
                        // Affichage de l'env en couleur
                        // On regarde d'abord combien d'environnements sont configurés. Si il n'y a qu'un environement, l'env restera blanc.
                        if ($DEFAULT_ENV === $LAST_ENV) { // Cas où il n'y a qu'un seul env
                            echo "<td class=\"td-redbackground\"><span>$repoEnv</span></td>";
                        } elseif ($repoEnv === $DEFAULT_ENV) { 
                            echo "<td class=\"td-whitebackground\"><span>$repoEnv</span></td>";
                        } elseif ($repoEnv === $LAST_ENV) {
                            echo "<td class=\"td-redbackground\"><span>$repoEnv</span></td>";
                        } else {
                            echo "<td class=\"td-whitebackground\"><span>$repoEnv</span></td>";
                        }

                        if ($ENVS_TOTAL > 1) {
                            // Icone permettant d'ajouter un nouvel environnement, placée juste avant la date
                            if ($OS_FAMILY == "Redhat") {
                                echo "<td class=\"td-xsmall\"><a href=\"check.php?actionId=changeEnv&repoName=${repoName}&repoEnv=${repoEnv}\"><img class=\"icon-verylowopacity-red\" src=\"icons/link.png\" title=\"Faire pointer un nouvel environnement sur le repo $repoName du $repoDate\" /></a></td>"; // td de toute petite taille, permettra d'afficher une icone 'link' avant chaque date
                            }
                            if ($OS_FAMILY == "Debian") {
                                echo "<td class=\"td-xsmall\"><a href=\"check.php?actionId=changeEnv&repoName=${repoName}&repoDist=${repoDist}&repoSection=${repoSection}&repoEnv=${repoEnv}\"><img class=\"icon-verylowopacity-red\" src=\"icons/link.png\" title=\"Faire pointer un nouvel environnement sur la section $repoSection du $repoDate\" /></a></td>"; // td de toute petite taille, permettra d'afficher une icone 'link' avant chaque date
                            }
                        }
                        echo "<td>$repoDate</td>";
                        if ($printRepoSize == "yes") {
                            echo "<td>$repoSize</td>";
                        }
                        echo "<td class=\"td-fit\" title=\"${repoDescription}\">$repoDescription</td>"; // avec un title afin d'afficher une info-bulle au survol (utile pour les descriptions longues)
                        echo '</tr>';
                        echo '<tr>';
                            echo '<td colspan="100%">';
                            echo "<div id=\"clientConfDiv${i}\" class=\"divReposConf\">";
                            echo '<h3>INSTALLATION</h3>';
                            echo '<p>Exécuter ces commandes directement dans le terminal de la machine cliente :</p>';
                            echo '<pre>';
                            if ($OS_FAMILY == "Redhat") {
                                echo "echo -e '# Repo ${repoName} (${repoEnv}) sur ${WWW_HOSTNAME}\n[${REPO_CONF_FILES_PREFIX}${repoName}_${repoEnv}]\nname=Repo ${repoName} sur ${WWW_HOSTNAME}\ncomment=Repo ${repoName} sur ${WWW_HOSTNAME}\nbaseurl=${WWW_REPOS_DIR_URL}/${repoName}_${repoEnv}\nenabled=1\ngpgkey=${WWW_REPOS_DIR_URL}/${WWW_HOSTNAME}.pub\ngpgcheck=1' > /etc/yum.repos.d/${REPO_CONF_FILES_PREFIX}${repoName}.repo";
                            }
                            if ($OS_FAMILY == "Debian") {
                                echo "wget -qO https://${WWW_REPOS_DIR_URL}/${WWW_HOSTNAME}.pub | sudo apt-key add -\n\necho -e '# Repo ${repoName} (${repoEnv}) sur ${WWW_HOSTNAME}\ndeb ${WWW_REPOS_DIR_URL}/${repoName}/${repoDist}/${repoSection}_${repoEnv} ${repoDist} ${repoSection}' > /etc/apt/sources.list.d/${REPO_CONF_FILES_PREFIX}${repoName}_${repoDist}_${repoSection}.list";
                            }
                            echo '</pre>';
                            echo '</div>';
                            echo '</td>';
                        echo '</tr>';

                        // Afficher ou masquer la div qui donne la conf des repos à mettre en place sur les serveurs clients (bouton ">_") :
                        echo '<script>';
                        echo '$(document).ready(function(){';
                        echo "$(\"#clientConfToggle${i}\").click(function(){";
                        echo "$(\"#clientConfDiv${i}\").slideToggle(250);";
                        echo '$(this).toggleClass("open");';
                        echo '});';
                        echo '});';
                        echo '</script>';

                        // alternance des couleurs :
                        $repoLastName = $repoName;
                        if ($OS_FAMILY == "Debian") {
                            $repoLastDist = $repoDist;
                            $repoLastSection = $repoSection;
                        }
                        ++$i;
                    }
                }
            } else {
                echo '<tr><td colspan="100%">Il n\'y a aucun repo dans ce groupe</td></tr>';
            }
            echo '<tr><td><br></td></tr>'; // saut de ligne avant chaque nom de groupe
        }
    }
    // Enfin, on affiche un dernier groupe "Defaut" qui contiendra les repos qui ne sont pas dans des groupes. Ce groupe Défaut s'affiche même si il n'y a aucun repo dans groups.list
    echo '<tr><td><b>Défaut</b></td></tr>';
    echo '<tr class="reposListHead">';
    echo '<td class="td-fit"></td>';
    echo '<td>Repo</td>';
    if ($OS_FAMILY == "Debian") {
        echo '<td class="td-xsmall"></td>'; // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque distribution
        echo '<td>Distribution</td>';
        echo '<td class="td-xsmall"></td>'; // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque section
        echo '<td>Section</td>';
    }
    echo '<td>Env</td>';
    echo '<td class="td-xsmall"></td>'; // td de toute petite taille, permettra d'afficher une icone 'link' avant chaque date
    echo '<td>Date</td>';
    if ($printRepoSize == "yes") { // On affiche la taille des repos seulement si souhaité
        echo '<td>Taille</td>';
    }
    echo '<td>Description</td>';
    echo '</tr>';
    $repoLastName = '';
    $repoLastDist = '';
    $repoLastSection = '';
    $repoLastEnv = '';
    // On récupère tout le contenu du fichier de liste de repos, 
    // puis pour chaque repo, si celui-ci n'apparait dans aucun groupe alors on l'affiche ici dans le groupe "Défaut"
    $rows = explode("\n", file_get_contents($REPOS_LIST));
    foreach($rows as $row) {
        if(!empty($row) AND $row !== "[REPOS]") { // on ne traite pas les lignes vides ni la ligne [REPOS] (1ère ligne du fichier)
            //get row data
            $rowData = explode(',', $row);
            if ($OS_FAMILY == "Redhat") {
                $repoName = strtr($rowData['0'], ['Name=' => '', '"' => '']);
                $repoEnv = strtr($rowData['2'], ['Env=' => '', '"' => '']);
                $repoDate = strtr($rowData['3'], ['Date=' => '', '"' => '']);
                $repoDescription = strtr($rowData['4'], ['Description=' => '', '"' => '']);
            }
            if ($OS_FAMILY == "Debian") {
                $repoName = strtr($rowData['0'], ['Name=' => '', '"' => '']);
                $repoDist = strtr($rowData['2'], ['Dist=' => '', '"' => '']);
                $repoSection = strtr($rowData['3'], ['Section=' => '', '"' => '']);
                $repoEnv = strtr($rowData['4'], ['Env=' => '', '"' => '']);
                $repoDate = strtr($rowData['5'], ['Date=' => '', '"' => '']);
                $repoDescription = strtr($rowData['6'], ['Description=' => '', '"' => '']);
            }

            // On cherche dans le fichier de groupes si le repo apparait :
            if ($OS_FAMILY == "Redhat") {
                $checkIfRepoIsInAGroup = exec("grep '^Name=\"${repoName}\"' $GROUPS_CONF");
            }
            if ($OS_FAMILY == "Debian") {
                $checkIfRepoIsInAGroup = exec("grep '^Name=\"${repoName}\",Dist=\"${repoDist}\",Section=\"${repoSection}\"' $GROUPS_CONF");
            }
            // Si le repo apparait dans un groupe alors on n'exécute pas la suite et on traite l'itération suivante de la boucle :
            if (!empty($checkIfRepoIsInAGroup)) {
                continue; 
            }
            // On calcule la taille des repos seulement si souhaité (car cela peut être une grosse opération si le repo est gros) :
            if ($OS_FAMILY == "Redhat" AND $printRepoSize == "yes") {
                $repoSize = exec("du -hs ${REPOS_DIR}/${repoDate}_${repoName} | awk '{print $1}'");
            }
            if ($OS_FAMILY == "Debian" AND $printRepoSize == "yes") {
                $repoSize = exec("du -hs ${REPOS_DIR}/${repoName}/${repoDist}/${repoDate}_${repoSection} | awk '{print $1}'");
            }
            // Affichage des données
            // on souhaite afficher des couleurs identiques si le nom du repo est identique avec le précédent affiché. Si ce n'est pas le cas alors on affiche une couleur différente afin de différencier les repos dans la liste
            if ($alternateColors == "yes" AND $repoName !== $repoLastName) {
                if ($listColor == "color1") { $listColor = 'color2'; }
                elseif ($listColor == "color2") { $listColor = 'color1'; }
            }

            // Affichage ou non d'une ligne séparatrice entre chaque repo/section
            if ($dividingLine === "yes") {
                if ($repoName !== $repoLastName) {
                    echo '<tr>';
                    echo '<td colspan="100%"><hr></td>';
                    echo '</tr>';
                }
            }

            echo "<tr class=\"$listColor\">";
            echo '<td class="td-fit">';
            // Affichage de l'icone "corbeille" pour supprimer le repo
            if ($OS_FAMILY == "Redhat") { // si rpm on doit présicer repoEnv dans l'url
                echo "<a href=\"check.php?actionId=deleteRepo&repoName=${repoName}&repoEnv=${repoEnv}\"><img class=\"icon-lowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer le repo ${repoName} (${repoEnv})\" /></a>";
            }
            if ($OS_FAMILY == "Debian") {
                echo "<a href=\"check.php?actionId=deleteRepo&repoName=${repoName}\"><img class=\"icon-lowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer le repo ${repoName}\" /></a>";
            }

            // Affichage de l'icone "dupliquer" pour dupliquer le repo
            if ($OS_FAMILY == "Redhat") {
                echo "<a href=\"check.php?actionId=duplicateRepo&repoName=${repoName}&repoEnv=${repoEnv}\"><img class=\"icon-lowopacity-blue\" src=\"icons/duplicate.png\" title=\"Dupliquer le repo ${repoName} (${repoEnv})\" /></a>";
            }
            if ($OS_FAMILY == "Debian") {
                echo "<a href=\"check.php?actionId=duplicateRepo&repoName=${repoName}&repoDist=${repoDist}&repoSection=${repoSection}&repoEnv=${repoEnv}\"><img class=\"icon-lowopacity-blue\" src=\"icons/duplicate.png\" title=\"Dupliquer le repo ${repoName} avec sa distribution ${repoDist} et sa section ${repoSection} (${repoEnv})\" /></a>";
            }

            // Affichage de l'icone "terminal" pour afficher la conf repo à mettre en place sur les serveurs
            echo "<img id=\"clientConfToggle${i}\" class=\"icon-lowopacity\" src=\"icons/code.png\" title=\"Afficher la configuration client\" />";

            // Affichage de l'icone 'update' pour mettre à jour le repo/section. On affiche seulement si l'env du repo/section = $DEFAULT_ENV
            if ($repoEnv === $DEFAULT_ENV) {
                if ($OS_FAMILY == "Redhat") {
                    echo "<a href=\"check.php?actionId=updateRepo&repoName=${repoName}\"><img class=\"icon-lowopacity-blue\" src=\"icons/update.png\" title=\"Mettre à jour le repo ${repoName} (${repoEnv})\" /></a>";
                }
                if ($OS_FAMILY == "Debian") {
                    echo "<a href=\"check.php?actionId=updateRepo&repoName=${repoName}&repoDist=${repoDist}&repoSection=${repoSection}\"><img class=\"icon-lowopacity-blue\" src=\"icons/update.png\" title=\"Mettre à jour la section ${repoName} (${repoEnv})\" /></a>";
                }
            }
            echo '</td>';

            // Si la vue simplifiée est activée (masquage du nom de repo si similaire au précédent) :
            if ($concatenateReposName == "yes" AND $repoName === $repoLastName) {
                echo '<td></td>';
            } else {
                echo "<td>$repoName</td>";
            }

            if ($OS_FAMILY == "Debian") {
                // Si la vue simplifiée est activée (masquage du nom de repo si similaire au précédent) :
                if ($concatenateReposName == "yes" AND $repoName === $repoLastName AND $repoDist === $repoLastDist) {
                    echo '<td class="td-xsmall"></td>';
                    echo '<td></td>';
                } else {
                    echo "<td class=\"td-xsmall\"><a href=\"check.php?actionId=deleteDist&repoName=${repoName}&repoDist=${repoDist}\"><img class=\"icon-verylowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer la distribution ${repoDist}\" /></a></td>"; // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque distribution
                    echo "<td>$repoDist</td>";
                }
                // Si la vue simplifiée est activée (masquage du nom de repo si similaire au précédent) :
                if ($concatenateReposName == "yes" AND $repoName === $repoLastName AND $repoDist === $repoLastDist AND $repoSection === $repoLastSection) {
                    echo "<td class=\"td-xsmall\"><a href=\"check.php?actionId=deleteSection&repoName=${repoName}&repoDist=${repoDist}&repoSection=${repoSection}&repoEnv=${repoEnv}\"><img class=\"icon-verylowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer la section ${repoSection} (${repoEnv})\" /></a></td>"; // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque section
                    echo '<td></td>';
                } else {
                    echo "<td class=\"td-xsmall\"><a href=\"check.php?actionId=deleteSection&repoName=${repoName}&repoDist=${repoDist}&repoSection=${repoSection}&repoEnv=${repoEnv}\"><img class=\"icon-verylowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer la section ${repoSection} (${repoEnv})\" /></a></td>"; // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque section
                    echo "<td>$repoSection</td>";
                }
            }
            // Affichage de l'env en couleur
            // On regarde d'abord combien d'environnements sont configurés. Si il n'y a qu'un environement, l'env restera blanc.
            if ($DEFAULT_ENV === $LAST_ENV) { // Cas où il n'y a qu'un seul env
                echo "<td class=\"td-redbackground\"><span>$repoEnv</span></td>";
            } elseif ($repoEnv === $DEFAULT_ENV) { 
                echo "<td class=\"td-whitebackground\"><span>$repoEnv</span></td>";
            } elseif ($repoEnv === $LAST_ENV) {
                echo "<td class=\"td-redbackground\"><span>$repoEnv</span></td>";
            } else {
                echo "<td class=\"td-whitebackground\"><span>$repoEnv</span></td>";
            }

            // Icone permettant d'ajouter un nouvel environnement, placée juste avant la date
            if ($ENVS_TOTAL > 1) {
                if ($OS_FAMILY == "Redhat") {
                    echo "<td class=\"td-xsmall\"><a href=\"check.php?actionId=changeEnv&repoName=${repoName}&repoEnv=${repoEnv}\"><img class=\"icon-verylowopacity-red\" src=\"icons/link.png\" title=\"Faire pointer un nouvel environnement sur le repo $repoName du $repoDate\" /></a></td>"; // td de toute petite taille, permettra d'afficher une icone 'link' avant chaque date
                }
                if ($OS_FAMILY == "Debian") {
                    echo "<td class=\"td-xsmall\"><a href=\"check.php?actionId=changeEnv&repoName=${repoName}&repoDist=${repoDist}&repoSection=${repoSection}&repoEnv=${repoEnv}\"><img class=\"icon-verylowopacity-red\" src=\"icons/link.png\" title=\"Faire pointer un nouvel environnement sur la section $repoSection du $repoDate\" /></a></td>"; // td de toute petite taille, permettra d'afficher une icone 'link' avant chaque date
                }
            }
            echo "<td>$repoDate</td>";
            if ($printRepoSize == "yes") {
                echo "<td>$repoSize</td>";
            }
            echo "<td class=\"td-fit\" title=\"${repoDescription}\">$repoDescription</td>"; // avec un title afin d'afficher une info-bulle au survol (utile pour les descriptions longues)
            echo '</tr>';
            echo '<tr>';
            echo '<td colspan="100%">';
            echo "<div id=\"clientConfDiv${i}\" class=\"divReposConf\">";
            echo '<h3>INSTALLATION</h3>';
            echo '<p>Exécuter ces commandes directement dans le terminal de la machine cliente :</p>';
            echo '<pre>';
            if ($OS_FAMILY == "Redhat") {
                echo "echo -e '# Repo ${repoName} (${repoEnv}) sur ${WWW_HOSTNAME}\n[${REPO_CONF_FILES_PREFIX}${repoName}_${repoEnv}]\nname=Repo ${repoName} sur ${WWW_HOSTNAME}\ncomment=Repo ${repoName} sur ${WWW_HOSTNAME}\nbaseurl=${WWW_REPOS_DIR_URL}/${repoName}_${repoEnv}\nenabled=1\ngpgkey=${WWW_REPOS_DIR_URL}/${WWW_HOSTNAME}.pub\ngpgcheck=1' > /etc/yum.repos.d/${REPO_CONF_FILES_PREFIX}${repoName}.repo";
            }
            if ($OS_FAMILY == "Debian") {
                echo "wget -qO https://${WWW_REPOS_DIR_URL}/${WWW_HOSTNAME}.pub | sudo apt-key add -\n\necho -e '# Repo ${repoName} (${repoEnv}) sur ${WWW_HOSTNAME}\ndeb ${WWW_REPOS_DIR_URL}/${repoName}/${repoDist}/${repoSection}_${repoEnv} ${repoDist} ${repoSection}' > /etc/apt/sources.list.d/${REPO_CONF_FILES_PREFIX}${repoName}_${repoDist}_${repoSection}.list";
            }
            echo '</pre>';
            echo '</div>';
            echo '</td>';
            echo '</tr>';
            // Afficher ou masquer la div qui donne la conf des repos à mettre en place sur les serveurs clients (bouton ">_") :
            echo '<script>';
            echo '$(document).ready(function(){';
            echo "$(\"#clientConfToggle${i}\").click(function(){";
            echo "$(\"#clientConfDiv${i}\").slideToggle(250);";
            echo '$(this).toggleClass("open");';
            echo '});';
            echo '});';
            echo '</script>';
            // alternance des couleurs :
            $repoLastName = $repoName;
            if ($OS_FAMILY == "Debian") {
                $repoLastDist = $repoDist;
                $repoLastSection = $repoSection;
            }
            ++$i;
        }
    }
?>