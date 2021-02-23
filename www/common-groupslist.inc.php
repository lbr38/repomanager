<div class="divGroupsList">
<img id="GroupsListCloseButton" title="Fermer" class="icon-lowopacity" src="icons/close.png" />
<h5>GESTION DES GROUPES</h5>
<p>Les groupes permettent de regrouper plusieurs repos afin de les trier ou d'effectuer une action commune.</p>
<br>
<p><b>Ajouter un nouveau groupe :</b></p>
<form action="<?php echo "${actual_uri}";?>" method="post" autocomplete="off">
  <input type="text" class="input-medium" name="addGroupName" /></td>
  <button type="submit" class="button-submit-xxsmall-blue" title="Ajouter">+</button></td>
</form>
<br>
  <?php
    $repoGroups = shell_exec("grep '^\[@.*\]' $GROUPS_CONF"); // récupération de tous les noms de groupes si il y en a 
    // on va afficher le tableau de groupe seulement si la commande précédente a trouvé des groupes dans le fichier (résultat non vide) :
    if (!empty($repoGroups)) {
      echo "<p><b>Groupes actuels :</b></p>";
      echo '<div class="groupDivContainer">';
      $repoGroups = preg_split('/\s+/', trim($repoGroups)); // on éclate le résultat précédent car tout a été récupéré sur une seule ligne
      $i = 0;
      $j = 0;
      foreach($repoGroups as $groupName) {
        $groupName = str_replace(["[", "]"], "", $groupName); // On retire les [ ] autour du nom du groupe
        echo "<div class=\"groupDiv\">";
        // on créé un formulaire pour chaque groupe, car chaque groupe sera modifiable :
        echo "<form action=\"${actual_uri}\" method=\"post\" autocomplete=\"off\">";
        echo '<table class="table-large">';
        // On veut pouvoir renommer le groupe, ou ajouter des repos à ce groupe, donc il faut transmettre le nom de groupe actuel (actualGroupName) :
        echo "<input type=\"hidden\" name=\"actualGroupName\" value=\"${groupName}\" />";
        echo '<tr>';
        echo '<td>';
        // on affiche le nom actuel du groupe dans un input type=text qui permet de renseigner un nouveau nom si on le souhaite (newGroupeName) :
        echo "<input type=\"text\" value=\"${groupName}\" name=\"newGroupName\" class=\"input-medium invisibleInput-blue\" />";
        echo '</td>'; 
        echo '<td class="td-fit">';
        echo "<img id=\"groupConfigurationToggleButton${i}\" class=\"icon-mediumopacity\" title=\"Configuration de $groupName\" src=\"icons/cog.png\" />";
        echo "<img src=\"icons/bin.png\" class=\"groupDeleteToggleButton${i} icon-lowopacity\" title=\"Supprimer le groupe ${groupName}\" />";
        deleteConfirm("Etes-vous sûr de vouloir supprimer le groupe $groupName", "?action=deleteGroup&groupName=${groupName}", "groupDeleteDiv${i}", "groupDeleteToggleButton${i}");
        echo '</td>';
        echo '</tr>';
        echo '</table>';
        echo '</form>';

        // Configuration de ce groupe dans un div caché
        echo "<div id=\"groupConfigurationTbody${i}\" class=\"hide groupDivConf\">";
        // On va récupérer la liste des repos du groupe et les afficher si il y en a (résultat non vide)
        $repoGroupList = shell_exec("sed -n '/\[${groupName}\]/,/\[/p' $GROUPS_CONF | sed '/^$/d' | grep -v '^\['"); // récupération des repos de ce groupe, en supprimant les lignes vides
        echo "<form action=\"${actual_uri}\" method=\"post\" autocomplete=\"off\">";
        // Il faut transmettre le nom du groupe dans le formulaire, donc on ajoute un input caché avec le nom du groupe
        echo "<input type=\"hidden\" name=\"actualGroupName\" value=\"${groupName}\" />";
        echo '<table class="table-large">';
        echo '<tr>';
        echo '<td class="td-fit"></td>';
        echo '<td class="td-medium"><b>Repo</b></td>';
        if ($OS_FAMILY == "Debian") { echo '<td class="td-medium"><b>Distribution</b></td>'; }
        if ($OS_FAMILY == "Debian") { echo '<td class="td-medium"><b>Section</b></td>'; }
        echo '</tr>';

        // affichage des repos du groupe si il y en a
        if (!empty($repoGroupList)) {
            $repoGroupList = preg_split('/\s+/', trim($repoGroupList)); // on éclate le résultat précédent car tout a été récupéré sur une seule ligne
            foreach($repoGroupList as $repoName) {
                $rowData = explode(',', $repoName);
                $repoName = str_replace(['Name=', '"'], "", $rowData[0]); // on récupère la données et on formate à la volée en retirant Name=""
                if ($OS_FAMILY == "Debian") { // si Debian on récupère aussi la distrib et la section
                  $repoDist = str_replace(['Dist=', '"'], "", $rowData[1]); // on récupère la données et on formate à la volée en retirant Dist=""
                  $repoSection = str_replace(['Section=', '"'], "", $rowData[2]); // on récupère la données et on formate à la volée en retirant Section=""
                }
                echo '<tr>';
                if ($OS_FAMILY == "Redhat") {
                  echo "<td class=\"td-fit\"><img src=\"icons/bin.png\" class=\"groupDeleteRepoToggleButton${j} icon-lowopacity\" title=\"Retirer le repo ${repoName} du groupe ${groupName}\" /></td>";
                  deleteConfirm("Êtes-vous sûr de vouloir retirer le repo $repoName du groupe $groupName", "?action=deleteGroupRepo&groupName=${groupName}&repoName=${repoName}", "groupDeleteRepoDiv${j}", "groupDeleteRepoToggleButton${j}");
                }
                if ($OS_FAMILY == "Debian") {
                  echo "<td class=\"td-fit\"><img src=\"icons/bin.png\" class=\"groupDeleteRepoToggleButton${j} icon-lowopacity\" title=\"Retirer la section ${repoSection} (repo ${repoName}) du groupe ${groupName}\" /></td>";
                  deleteConfirm("Êtes-vous sûr de vouloir retirer la section $repoSection ($repoName $repoDist) du groupe $groupName", "?action=deleteGroupRepo&groupName=${groupName}&repoName=${repoName}&repoDist=${repoDist}&repoSection=${repoSection}", "groupDeleteRepoDiv${j}", "groupDeleteRepoToggleButton${j}");
                }
                echo "<td class=\"td-auto\">${repoName}</td>";
                if ($OS_FAMILY == "Debian") {
                  echo "<td class=\"td-auto\">${repoDist}</td>";
                  echo "<td class=\"td-auto\">${repoSection}</td>";
                }
                echo '</tr>';
                ++$j;
            }
        } else {
          echo '<tr>';
          echo '<td class="td-fit"></td>';
          echo '<td>Aucun</td>';
          echo '</tr>';
        }
        echo '<tr><td colspan="100%"><hr></td></tr>';
        echo '<tr>';
        echo '<td colspan="100%">';
        // select permettant d'ajouter un repo au groupe. Pour rappel le nom du groupe est transmis en hidden (voir début du formulaire) :
        echo '<select class="reposSelectList" name="groupAddRepoName[]" multiple>';
        reposSelectList();
        echo '</select>';
        echo '</td>';
        echo '<td class="td-fit"><button type="submit" class="button-submit-xxsmall-blue" title="Ajouter">+</button></td>';
        echo '</tr>';
        echo '</table>';
        echo '</form>';
        echo '</div>'; // cloture de groupConfigurationTbody${i}
        // Afficher ou masquer la div 'groupConfigurationTbody' :
        echo "<script>";
        echo "$(document).ready(function(){";
          echo "$(\"#groupConfigurationToggleButton${i}\").click(function(){";
            echo "$(\"div#groupConfigurationTbody${i}\").slideToggle(150);";
            echo '$(this).toggleClass("open");';
          echo "});";
        echo "});";
        echo "</script>";
        ++$i;
        echo '</div>'; // cloture de groupDiv
      }
      echo '</div>'; // cloture de groupDivContainer
    }?>
  </table>
</div>

<script> 
// Afficher ou masquer la div permettant de gérer les groupes (div s'affichant en bas de la page)
$(document).ready(function(){
    // Le bouton up permet d'afficher la div et également de la fermer si on reclique dessus
    $('#GroupsListSlideUpButton').click(function() {
        $('div.divGroupsList').slideToggle(150);
    });

    // Le bouton down (petite croix) permet la même chose, il sera surtout utilisé pour fermer la div
    $('#GroupsListCloseButton').click(function() {
      $('div.divGroupsList').slideToggle(150);
    });
});
</script>
<script>
// Script Select2 pour transformer un select multiple en liste déroulante
$('.reposSelectList').select2({
  closeOnSelect: false,
  placeholder: 'Ajouter un repo...'
});
</script>