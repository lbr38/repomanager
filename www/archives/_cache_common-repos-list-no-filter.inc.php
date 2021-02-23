<?php
    if (!file_exists($WWW_CACHE)) {
        if (file_exists("/dev/shm")) {
            exec("cd $WWW_DIR && ln -sf /dev/shm cache");
        } else {
            mkdir("${WWW_DIR}/cache", 0770, true);
        }
    }

    if (!file_exists("${WWW_CACHE}/repos-list-no-filter.html")) {
        touch("${WWW_CACHE}/repos-list-no-filter.html");
    }

    function write($msg) {
        global $WWW_DIR;
        file_put_contents("${WWW_CACHE}/repos-list-no-filter.html", "$msg", FILE_APPEND);
    }

    // initialise des variables permettant de simplifier l'affichage dans la liste des repos
    $repoLastName = '';
    $repoLastDist = '';
    $repoLastSection = '';
    $repoLastEnv = '';
    $listColor = 'color1'; // initialise des variables permettant de changer la couleur dans l'affichage de la liste des repos
    write('<thead>
    <tr>
    <td class="td-fit"></td>
    <td>Repo</td>');
    if ($OS_FAMILY == "Debian") {
        // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque distribution
        write('<td class="td-xsmall"></td>
        <td>Distribution</td>
        <td class="td-xsmall"></td>
        <td>Section</td>');
    }
    // td de toute petite taille, permettra d'afficher une icone 'link' avant chaque date
    write('<td>Env</td>
    <td class="td-xsmall"></td>
    <td>Date</td>');
    if ($printRepoSize == "yes") { // On affiche la taille des repos seulement si souhaité
        write('<td>Taille</td>');
    }
    write('<td>Description</td>
    </tr>
    </thead>');
    $rows = explode("\n", file_get_contents($REPOS_LIST));
    foreach($rows as $row) {
        if(!empty($row) AND $row !== "[REPOS]") { // on ne traite pas les lignes vides ni la ligne [REPOS] (1ère ligne du fichier)
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
                    write('<tr>
                    <td colspan="100%"><hr></td>
                    </tr>');
                }
            }
            write("<tr class=\"$listColor\">");
            write('<td class="td-fit">');
            // Affichage de l'icone "corbeille" pour supprimer le repo
            if ($OS_FAMILY == "Redhat") { // si rpm on doit présicer repoEnv dans l'url
                write("<a href=\"check.php?actionId=deleteRepo&repoName=${repoName}&repoEnv=${repoEnv}\"><img class=\"icon-lowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer le repo ${repoName} (${repoEnv})\" /></a>");
            }
            if ($OS_FAMILY == "Debian") {
                write("<a href=\"check.php?actionId=deleteRepo&repoName=${repoName}\"><img class=\"icon-lowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer le repo ${repoName}\" /></a>");
            }
            // Affichage de l'icone "dupliquer" pour dupliquer le repo
            if ($OS_FAMILY == "Redhat") {
                write("<a href=\"check.php?actionId=duplicateRepo&repoName=${repoName}&repoEnv=${repoEnv}\"><img class=\"icon-lowopacity-blue\" src=\"icons/duplicate.png\" title=\"Dupliquer le repo ${repoName} (${repoEnv})\" /></a>");
            }
            if ($OS_FAMILY == "Debian") {
                write("<a href=\"check.php?actionId=duplicateRepo&repoName=${repoName}&repoDist=${repoDist}&repoSection=${repoSection}&repoEnv=${repoEnv}\"><img class=\"icon-lowopacity-blue\" src=\"icons/duplicate.png\" title=\"Dupliquer le repo ${repoName} avec sa distribution ${repoDist} et sa section ${repoSection} (${repoEnv})\" /></a>");
            }
            // Affichage de l'icone "terminal" pour afficher la conf repo à mettre en place sur les serveurs
            write("<img id=\"conftogg${i}\" class=\"icon-lowopacity\" src=\"icons/code.png\" title=\"Afficher la configuration client\" />");
            // Affichage de l'icone 'update' pour mettre à jour le repo/section. On affiche seulement si l'env du repo/section = $DEFAULT_ENV
            if ($repoEnv === $DEFAULT_ENV) {
                if ($OS_FAMILY == "Redhat") {
                    write("<a href=\"check.php?actionId=updateRepo&repoName=${repoName}\"><img class=\"icon-lowopacity-blue\" src=\"icons/update.png\" title=\"Mettre à jour le repo ${repoName} (${repoEnv})\" /></a>");
                }
                if ($OS_FAMILY == "Debian") {
                    write("<a href=\"check.php?actionId=updateRepo&repoName=${repoName}&repoDist=${repoDist}&repoSection=${repoSection}\"><img class=\"icon-lowopacity-blue\" src=\"icons/update.png\" title=\"Mettre à jour la section ${repoName} (${repoEnv})\" /></a>");
                }
            }
            write('</td>');
            // Si la vue simplifiée est activée (masquage du nom de repo si similaire au précédent) :
            if ($concatenateReposName == "yes" AND $repoName === $repoLastName) {
                write('<td></td>');
            } else {
                write("<td>$repoName</td>");
            }
            if ($OS_FAMILY == "Debian") {
                // Si la vue simplifiée est activée (masquage du nom de repo si similaire au précédent) :
                if ($concatenateReposName == "yes" AND $repoName === $repoLastName AND $repoDist === $repoLastDist) {
                    write('<td class="td-xsmall"></td>
                    <td></td>');
                } else {
                    // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque distribution
                    write("<td class=\"td-xsmall\"><a href=\"check.php?actionId=deleteDist&repoName=${repoName}&repoDist=${repoDist}\"><img class=\"icon-verylowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer la distribution ${repoDist}\" /></a></td>
                    <td>$repoDist</td>");
                }
                // Si la vue simplifiée est activée (masquage du nom de repo si similaire au précédent) :
                if ($concatenateReposName == "yes" AND $repoName === $repoLastName AND $repoDist === $repoLastDist AND $repoSection === $repoLastSection) {
                    write("<td class=\"td-xsmall\"><a href=\"check.php?actionId=deleteSection&repoName=${repoName}&repoDist=${repoDist}&repoSection=${repoSection}&repoEnv=${repoEnv}\"><img class=\"icon-verylowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer la section ${repoSection} (${repoEnv})\" /></a></td>"); // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque section
                    write('<td></td>');
                } else {
                    write("<td class=\"td-xsmall\"><a href=\"check.php?actionId=deleteSection&repoName=${repoName}&repoDist=${repoDist}&repoSection=${repoSection}&repoEnv=${repoEnv}\"><img class=\"icon-verylowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer la section ${repoSection} (${repoEnv})\" /></a></td>"); // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque section
                    write("<td>$repoSection</td>");
                }
            }
            // Affichage de l'env en couleur
            // On regarde d'abord combien d'environnements sont configurés. Si il n'y a qu'un environement, l'env restera blanc.
            if ($DEFAULT_ENV === $LAST_ENV) { // Cas où il n'y a qu'un seul env
                write("<td class=\"td-redbackground\"><span>$repoEnv</span></td>");
            } elseif ($repoEnv === $DEFAULT_ENV) { 
                write("<td class=\"td-whitebackground\"><span>$repoEnv</span></td>");
            } elseif ($repoEnv === $LAST_ENV) {
                write("<td class=\"td-redbackground\"><span>$repoEnv</span></td>");
            } else {
                write("<td class=\"td-whitebackground\"><span>$repoEnv</span></td>");
            }
            // Icone permettant d'ajouter un nouvel environnement, placée juste avant la date
            if ($OS_FAMILY == "Redhat") {
                write("<td class=\"td-xsmall\"><a href=\"check.php?actionId=changeEnv&repoName=${repoName}&repoEnv=${repoEnv}\"><img class=\"icon-verylowopacity-red\" src=\"icons/link.png\" title=\"Faire pointer un nouvel environnement sur le repo $repoName du $repoDate\" /></a></td>"); // td de toute petite taille, permettra d'afficher une icone 'link' avant chaque date
            }
            if ($OS_FAMILY == "Debian") {
                write("<td class=\"td-xsmall\"><a href=\"check.php?actionId=changeEnv&repoName=${repoName}&repoDist=${repoDist}&repoSection=${repoSection}&repoEnv=${repoEnv}\"><img class=\"icon-verylowopacity-red\" src=\"icons/link.png\" title=\"Faire pointer un nouvel environnement sur la section $repoSection du $repoDate\" /></a></td>"); // td de toute petite taille, permettra d'afficher une icone 'link' avant chaque date
            }
            write("<td>$repoDate</td>");
            // Afficher ou non la taille des repos
            if ($printRepoSize == "yes") {
                write("<td>$repoSize</td>");
            }
            write("<td class=\"td-fit\" title=\"${repoDescription}\">$repoDescription</td>"); // avec un title afin d'afficher une info-bulle au survol (utile pour les descriptions longues)
            write('</tr>
            <tr>
            <td colspan="100%">');
            write("<div id=\"confdiv${i}\" class=\"divReposConf\">");
            write('<h3>INSTALLATION</h3>');
            write('<p>Exécuter ces commandes directement dans le terminal de la machine cliente :</p>');
            write('<pre>');
            if ($OS_FAMILY == "Redhat") {
                write("echo -e '# Repo ${repoName} (${repoEnv}) sur ${WWW_HOSTNAME}\n[${REPO_CONF_FILES_PREFIX}${repoName}_${repoEnv}]\nname=Repo ${repoName} sur ${WWW_HOSTNAME}\ncomment=Repo ${repoName} sur ${WWW_HOSTNAME}\nbaseurl=${WWW_REPOS_DIR_URL}/${repoName}_${repoEnv}\nenabled=1\ngpgkey=${WWW_REPOS_DIR_URL}/${WWW_HOSTNAME}.pub\ngpgcheck=1' > /etc/yum.repos.d/${REPO_CONF_FILES_PREFIX}${repoName}.repo");
            }
            if ($OS_FAMILY == "Debian") {
                write("wget -qO https://${WWW_REPOS_DIR_URL}/${WWW_HOSTNAME}.pub | sudo apt-key add -\n\necho -e '# Repo ${repoName} (${repoEnv}) sur ${WWW_HOSTNAME}\ndeb ${WWW_REPOS_DIR_URL}/${repoName}/${repoDist}/${repoSection}_${repoEnv} ${repoDist} ${repoSection}' > /etc/apt/sources.list.d/${REPO_CONF_FILES_PREFIX}${repoName}_${repoDist}_${repoSection}.list");
            }
            write('</pre>
            </div>
            </td>
            </tr>');
            // Afficher ou masquer la div qui donne la conf des repos à mettre en place sur les serveurs clients (bouton ">_") :
            write('<script>');
            write('$(document).ready(function(){');
            write("$(\"img#conftogg${i}\").click(function(){");
            write("$(\"div#confdiv${i}\").slideToggle(250);");
            write('$(this).toggleClass("open");');
            write('});');
            write('});');
            write('</script>');
        }
        ++$i;
        if (!empty($repoName)) { $repoLastName = $repoName; }
        if ($OS_FAMILY == "Debian") {
            if (!empty($repoDist)) { $repoLastDist = $repoDist; }
            if (!empty($repoSection)) { $repoLastSection = $repoSection; }
        }
    }
?>