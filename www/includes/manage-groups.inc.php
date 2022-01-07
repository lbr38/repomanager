<img id="GroupsListCloseButton" title="Fermer" class="icon-lowopacity" src="ressources/icons/close.png" />
<h3>GROUPES</h3>
<p>Les groupes permettent de regrouper plusieurs repos afin de les trier ou d'effectuer une action commune.</p>
<br>

<p><b>Ajouter un nouveau groupe :</b></p>
<form id="newGroupForm" autocomplete="off">
  	<input id="newGroupInput" type="text" class="input-medium" />
  	<button type="submit" class="btn-xxsmall-blue" title="Ajouter">+</button>
</form>

<br>
  	<?php
  	/**
   	 *  AFFICHAGE DES GROUPES ACTUELS
     */

    /**
     *  1. Récupération de tous les noms de groupes (en excluant le groupe par défaut)
     */
	$group = new Group();
    $groupsList = $group->listAllName();

    /**
     *  2. Affichage des groupes si il y en a
     */
    if (!empty($groupsList)) {
		echo "<p><b>Groupes actuels :</b></p>";

      	foreach($groupsList as $groupName) {?>
			<div class="header-container">
        		<div class="header-blue-min">
					<form class="groupForm" groupname="<?php echo $groupName;?>" autocomplete="off">
						<input type="hidden" name="actualGroupName" value="<?php echo $groupName;?>" />
						<table class="table-large">
							<tr>
								<td>
									<input class="groupFormInput input-medium invisibleInput-blue" groupname="<?php echo $groupName;?>" type="text" value="<?php echo $groupName;?>" />
								</td>
								<td class="td-fit">
									<img class="groupConfigurationButton icon-mediumopacity" name="<?php echo $groupName;?>" title="Configuration de <?php echo $groupName;?>" src="ressources/icons/cog.png" />
									<img src="ressources/icons/bin.png" class="deleteGroupButton icon-lowopacity" name="<?php echo $groupName;?>" title="Supprimer le groupe ${groupName}" />
								</td>
							</tr>
						</table>
					</form>
				</div>

				<div id="groupConfigurationDiv-<?php echo $groupName;?>" class="hide detailsDiv">
					<form class="groupReposForm" groupname="<?php echo $groupName;?>" autocomplete="off">
						<input type="hidden" name="actualGroupName" value="<?php echo $groupName;?>" />
						<?php
						if (OS_FAMILY == "Redhat") echo '<p><b>Repos</b></p>';
						if (OS_FAMILY == "Debian") echo '<p><b>Sections de repos</b></p>'; ?>
						<table class="table-large">
							<tr>
								<td>
									<?php $group->selectRepos($groupName); ?>
								</td>
								<td class="td-fit">
									<button type="submit" class="btn-xxsmall-blue" title="Enregistrer">💾</button>
								</td>
							</tr>
						</table>
					</form>
				</div>
			</div>
    <?php }
    } ?>

<script>
$(document).ready(function(){
	// Script Select2 pour transformer un select multiple en liste déroulante
	$('.reposSelectList').select2({
		closeOnSelect: false,
		placeholder: 'Ajouter un repo...'
	});
});
</script>