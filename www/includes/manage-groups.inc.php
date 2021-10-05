<?php
require_once('class/Group.php');
$group = new Group();

/**
 * 	Cas où on souhaite ajouter un nouveau groupe
 */
if (!empty($_POST['addGroupName'])) {
  	$group->new(validateData($_POST['addGroupName']));
}

/**
 * 	Cas où on souhaite supprimer un groupe
 */
if (!empty($_GET['action']) AND (validateData($_GET['action']) == "deleteGroup") AND !empty($_GET['groupName'])) {
  	$group->delete(validateData($_GET['groupName']));
}

/**
 * 	Cas où on souhaite modifier la liste des repos d'un groupe
 */
if (!empty($_POST['actualGroupName']) AND !empty($_POST['groupAddRepoName'])) {
	$mygroup = new Group(array('groupName' => validateData($_POST['actualGroupName'])));
  	// Pas de validateData sur $_POST['groupAddRepoName'], il est opéré dans la fonction addRepo directement :
	$mygroup->addRepo($_POST['groupAddRepoName']);
	unset($mygroup);
}

/**
 * 	Cas où on souhaite renommer un groupe
 */
if (!empty($_POST['newGroupName']) AND !empty($_POST['actualGroupName'])) {
  	$group->rename(validateData($_POST['actualGroupName']), validateData($_POST['newGroupName']));
}
?>

<img id="GroupsListCloseButton" title="Fermer" class="icon-lowopacity" src="icons/close.png" />
<h3>GROUPES</h3>
<p>Les groupes permettent de regrouper plusieurs repos afin de les trier ou d'effectuer une action commune.</p>
<br>

<p><b>Ajouter un nouveau groupe :</b></p>
<form action="<?php echo "${actual_uri}";?>" method="post" autocomplete="off">
  	<input type="text" class="input-medium" name="addGroupName" /></td>
  	<button type="submit" class="button-submit-xxsmall-blue" title="Ajouter">+</button></td>
</form>

<br>
  	<?php
  	/**
   	 *  AFFICHAGE DES GROUPES ACTUELS
     */

    /**
     *  1. Récupération de tous les noms de groupes (en excluant le groupe par défaut)
     */
    $groupsList = $group->listAllName();

    /**
     *  2. Affichage des groupes si il y en a
     */

    if (!empty($groupsList)) {
		echo "<p><b>Groupes actuels :</b></p>";
		$i = 0;

      	foreach($groupsList as $groupName) {
			echo '<div class="header-container">';
        		echo '<div class="header-blue-min">';

					/**
					 *   3. On créé un formulaire pour chaque groupe, car chaque groupe sera modifiable :
					 */
					echo "<form action=\"${actual_uri}\" method=\"post\" autocomplete=\"off\">";

					// On veut pouvoir renommer le groupe, ou ajouter des repos à ce groupe, donc il faut transmettre le nom de groupe actuel (actualGroupName) :
					echo "<input type=\"hidden\" name=\"actualGroupName\" value=\"${groupName}\" />";

					echo '<table class="table-large">';
					echo '<tr>';
					// On affiche le nom actuel du groupe dans un input type=text qui permet de renseigner un nouveau nom si on le souhaite (newGroupeName) :
					echo "<td><input type=\"text\" value=\"${groupName}\" name=\"newGroupName\" class=\"input-medium invisibleInput-blue\" /></td>";
				
					// Boutons configuration et suppression du groupe
					echo '<td class="td-fit">';
					echo "<img id=\"groupConfigurationToggleButton-${groupName}\" class=\"icon-mediumopacity\" title=\"Configuration de $groupName\" src=\"icons/cog.png\" />";
					echo "<img src=\"icons/bin.png\" class=\"groupDeleteToggleButton-${groupName} icon-lowopacity\" title=\"Supprimer le groupe ${groupName}\" />";
					deleteConfirm("Etes-vous sûr de vouloir supprimer le groupe $groupName", "?action=deleteGroup&groupName=${groupName}", "groupDeleteDiv-${groupName}", "groupDeleteToggleButton-${groupName}");
					echo '</td>';
					echo '</tr>';
					echo '</table>';
					echo '</form>';
				echo '</div>'; // cloture de header-blue-min

				/**
				 *  4. La liste des repos du groupe est placée dans un div caché
				 */
				echo "<div id=\"groupConfigurationDiv-${groupName}\" class=\"hide detailsDiv\">";
					// On va récupérer la liste des repos du groupe et les afficher si il y en a (résultat non vide)           
					echo "<form action=\"${actual_uri}\" method=\"post\" autocomplete=\"off\">";
					
					// Il faut transmettre le nom du groupe dans le formulaire, donc on ajoute un input caché avec le nom du groupe
					echo "<input type=\"hidden\" name=\"actualGroupName\" value=\"${groupName}\" />";

					if ($OS_FAMILY == "Redhat") echo '<p><b>Repos</b></p>';
					if ($OS_FAMILY == "Debian") echo '<p><b>Sections de repos</b></p>';

					echo '<table class="table-large">';
					echo '<tr>';
					echo '<td>';
					$group->selectRepos($groupName);
					echo '</td>';
					echo '<td class="td-fit"><button type="submit" class="button-submit-xxsmall-blue" title="Enregistrer">💾</button></td>';
					echo '</tr>';
					echo '</table>';
					echo '</form>';
				echo '</div>'; // cloture de groupConfigurationDiv${i}

				// Afficher ou masquer la div 'groupConfigurationDiv' :
				echo "<script>";
				echo "$(document).ready(function(){";
				echo "$(\"#groupConfigurationToggleButton-${groupName}\").click(function(){";
					echo "$(\"div#groupConfigurationDiv-${groupName}\").slideToggle(150);";
					echo '$(this).toggleClass("open");';
				echo "});";
				echo "});";
				echo "</script>";
				++$i;
			echo '</div>'; // cloture de header-container
      	}
    }
   ?>
  </table>

<script>
// Script Select2 pour transformer un select multiple en liste déroulante
$('.reposSelectList').select2({
  closeOnSelect: false,
  placeholder: 'Ajouter un repo...'
});
</script>