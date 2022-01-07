<?php
/**
 * 	Afficher l'en-tête d'une opération
 */
function printOp($myop, $optype = '') {
	$opId = $myop['Id'];
	$opDate = DateTime::createFromFormat('Y-m-d', $myop['Date'])->format('d-m-Y');
	$opTime = $myop['Time'];
	$opAction = $myop['Action'];
	$opType = $myop['Type'];

	$myrepo = new Repo();

	/**
	 * 	Récupération du repo source si renseigné
	 */
	if (!empty($myop['Id_repo_source'])) {
		$opRepoSource = $myop['Id_repo_source'];
		
		/**
		 *  Si le repo source retourné est une chaine numérique, alors il s'agit de son ID en BDD. On va s'en servir pour récupérer les infos du repo concerné en BDD
		 */
		if (is_numeric($opRepoSource)) {

			$myrepo->setId($opRepoSource);

			/**
			 * 	Si l'action traite un repo archivé, il faut récupérer l'information dans la table repos_archived.
			 * 	Exception pour l'action restore qui cible un repos dans la table repos.
			 */
			if ($opAction == "deleteArchive") {
				$myrepo->db_getAllById('archived');
			} else {
				$myrepo->db_getAllById('active');
			}
			$opRepoSourceName = $myrepo->name;
			if (OS_FAMILY == "Debian") {
				$opRepoSourceDist = $myrepo->dist;
				$opRepoSourceSection = $myrepo->section;
			}
		}
	}

	/**
	 * 	Récupération du repo cible si renseigné
	 */
	if (!empty($myop['Id_repo_target'])) {
		$opRepoTarget = $myop['Id_repo_target'];

		/**
		 *  Si le repo cible retourné est une chaine numérique, alors il s'agit de son ID en BDD. On va s'en servir pour récupérer les infos du repo concerné en BDD
		 */
		if (is_numeric($opRepoTarget)) {

			$myrepo->setId($opRepoTarget);

			/**
			 * 	Si l'action traite un repo archivé, il faut récupérer l'information dans la table repos_archived. Exception pour l'action restore qui cible un repos dans la tbale repos.
			 */
			if ($opAction == "deleteArchive") {
				$myrepo->db_getAllById('archived');
			} else {
				$myrepo->db_getAllById('active');
			}
			$opRepoSourceName = $myrepo->name;
			if (OS_FAMILY == "Debian") {
				$opRepoSourceDist = $myrepo->dist;
				$opRepoSourceSection = $myrepo->section;
			}
		/**
		 *  Si le repo cible retourné n'est pas un entier, c'est qu'il n'a pas encore été intégré en BDD et qu'il ne possède donc pas d'ID, on récupère alors directement son nom
		 */
		} else {
			$opRepoTarget = explode('|', $opRepoTarget);
			$opRepoTargetName = $opRepoTarget[0];
			if (OS_FAMILY == "Debian") {
				if (!empty($opRepoTarget[1])) $opRepoTargetDist = $opRepoTarget[1];
				if (!empty($opRepoTarget[2])) $opRepoTargetSection = $opRepoTarget[2];
			}
		}
	}

	/**
	 * 	Récupération des informations de la planification à partir de son ID (si renseigné)
	 */
	if (!empty($myop['GpgCheck']))  $opGpgCheck  = $myop['GpgCheck'];
	if (!empty($myop['GpgResign'])) $opGpgResign = $myop['GpgResign'];
	$opPid     = $myop['Pid'];
	$opLogfile = $myop['Logfile'];
	$opStatus  = $myop['Status'];

	if ($optype == 'plan') {
		echo '<div class="op-header-container">';
	} else {
		echo '<div class="header-container">';
	}
	echo '<div class="header-blue">';
		echo '<table>';
		echo '<tr>';
		echo '<td class="td-fit">';
		if ($opAction == "new") echo '<img class="icon" src="ressources/icons/plus.png" title="Nouveau" />';
		if ($opAction == "update") echo '<img class="icon" src="ressources/icons/update.png" title="Mise à jour" />';
		if ($opAction == "reconstruct") echo '<img class="icon" src="ressources/icons/update.png" title="Reconstruction des métadonnées" />';
		if ($opAction == "changeEnv" OR strpos($opAction, '->') !== false) echo '<img class="icon" src="ressources/icons/link.png" title="Créat. d\'environnement" />';
		if ($opAction == "duplicate") echo '<img class="icon" src="ressources/icons/duplicate.png" title="Duplication" />';
		if ($opAction == "delete" OR $opAction == "deleteDist" OR $opAction == "deleteSection") echo '<img class="icon" src="ressources/icons/bin.png" title="Suppression" />';
		if ($opAction == "deleteArchive") echo '<img class="icon" src="ressources/icons/bin.png" title="Suppression d\'une archive" />';
		if ($opAction == "restore") echo '<img class="icon" src="ressources/icons/arrow-circle-up.png" title="Restauration d\'une archive" />';

		echo '</td>';
		echo "<td class=\"td-small\"><a href=\"run.php?logfile=${opLogfile}\">Le <b>$opDate</b> à <b>$opTime</b></a></td>";

		/**
		 * 	Affichage du repo ou du groupe concerné par cette opération
		 * 	Il s'agit soit du repo source, soit du repo cible
		 */
		echo '<td>';
		if (!empty($opGroup)) echo "Groupe $opGroup";

		/**
		 * 	On affiche le repo source uniquement si il n'y a pas de repo cible renseigné. Sinon on se contentera d'afficher uniquement le repo cible.
		 */
		if (empty($opRepoTargetName)) {
			
			if (!empty($opRepoSourceName)) echo $opRepoSourceName;

			/**
			 * 	Dans le cas de Debian, on affiche aussi la distribution et la section
			 */
			if (OS_FAMILY == "Debian") {
				// Affichage de la distribution
				if (!empty($opRepoSourceDist)) echo " - $opRepoSourceDist";
				// Affichage de la section
				if (!empty($opRepoSourceSection)) echo " - $opRepoSourceSection";
			}
		}

		if (!empty($opRepoTargetName)) echo $opRepoTargetName;

		if (OS_FAMILY == "Debian") {
			// Affichage de la distribution
			if (!empty($opRepoTargetDist)) echo " - $opRepoTargetDist";
			// Affichage de la section
			if (!empty($opRepoTargetSection)) echo " - $opRepoTargetSection";
		}
		echo '</td>';

		/**
		 * 	Affichage de l'icone en cours ou terminée ou en erreur
		 */
		echo '<td class="td-fit">';
		if ($opStatus == "running") echo 'en cours <img src="ressources/images/loading.gif" class="icon" title="en cours d\'exécution" />';
		if ($opStatus == "done")    echo '<img class="icon-small" src="ressources/icons/greencircle.png" title="Opération terminée" />';
		if ($opStatus == "error")   echo '<img class="icon-small" src="ressources/icons/redcircle.png" title="Opération en erreur" />';
		if ($opStatus == "stopped") echo '<img class="icon-small" src="ressources/icons/redcircle.png" title="Opération stoppée par l\'utilisateur" />';
		echo '</td>';
		echo '</tr>';
		echo '</table>';
	echo '</div>';
	echo '</div>';
}
?>