<!DOCTYPE html>
<html>
<?php include('includes/head.inc.php'); ?>

<?php
/**
 *  Import des variables et fonctions nécessaires
 */
require_once('functions/load_common_variables.php');
require_once('functions/load_display_variables.php');
require_once('functions/common-functions.php');
require_once('common.php');
require_once('class/Database.php');
require_once('class/Operation.php');

/**
 * 	Bouton 'Stop' pour arrêter une opération en cours
 */
if(!empty($_GET['stop'])) {
	$opToStop = new Operation();
	$opToStop->kill(validateData($_GET['stop'])); // $_GET['stop'] contient le pid de l'opération
}

/**
 * 	Récupération du fichier de log à visualiser si passé en GET
 */
$logfile = 'none'; if (!empty($_GET['logfile'])) { $logfile = validateData($_GET['logfile']); }

/**
 * 	Si on a activé l'affichage de tous les logs alors on fait apparaitre tous les div cachés
 */
if (!empty($_GET['displayFullLogs']) AND validateData($_GET['displayFullLogs']) == "yes") {
	echo '<style>';
	echo '.getPackagesDiv { display: block; }';
	echo '.signRepoDiv { display: block; }';
	echo '.createRepoDiv { display: block; }';
	echo '</style>';
} 

/**
 * 	Afficher l'en-tête d'une opération
 */
function printOp($myop, $optype = '') {
	global $OS_FAMILY;

	$opId = $myop['Id'];
	$opDate = DateTime::createFromFormat('Y-m-d', $myop['Date'])->format('d-m-Y');
	$opTime = $myop['Time'];
	$opAction = $myop['Action'];
	$opType = $myop['Type'];

	$db = new Database();

	/**
	 * 	Récupération du repo source si renseigné
	 */
	if (!empty($myop['Id_repo_source'])) {
		$opRepoSource = $myop['Id_repo_source'];
		
		/**
		 *  Si le repo source retourné est une chaine numérique, alors il s'agit de son ID en BDD. On va s'en servir pour récupérer les infos du repo concerné en BDD
		 */
		if (is_numeric($opRepoSource)) {
			// Si l'action traite un repo archivé, il faut récupérer l'information dans la table repos_archived. Exception pour l'action restore qui cible un repos dans la tbale repos.
			if ($opAction == "deleteArchive") {
				$stmt = $db->prepare("SELECT * FROM repos_archived WHERE Id=:id");
			} else {
				$stmt = $db->prepare("SELECT * FROM repos WHERE Id=:id");
			}
			$stmt->bindValue(':id', $opRepoSource);
			$result = $stmt->execute();
			while ($datas = $result->fetchArray()) {
				$opRepoSourceName = $datas['Name'];
				if ($OS_FAMILY == "Debian") {
					$opRepoSourceDist = $datas['Dist'];
					$opRepoSourceSection = $datas['Section'];
				}
			}
			unset($datas, $result, $stmt);
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
			// Si l'action traite un repo archivé, il faut récupérer l'information dans la table repos_archived. Exception pour l'action restore qui cible un repos dans la tbale repos.
			if ($opAction == "deleteArchive") {
				$stmt = $db->prepare("SELECT * FROM repos_archived WHERE Id=:id");
			} else {
				$stmt = $db->prepare("SELECT * FROM repos WHERE Id=:id");
			}
			//$stmt = $db->prepare("SELECT * FROM repos WHERE Id=:id AND Status = 'active'");
			
			$stmt->bindValue(':id', $opRepoTarget);
			$result = $stmt->execute();
			while ($datas = $result->fetchArray()) {
				$opRepoTargetName = $datas['Name'];
				if ($OS_FAMILY == "Debian") {
					$opRepoTargetDist = $datas['Dist'];
					$opRepoTargetSection = $datas['Section'];
				}
			}
			unset($datas, $result, $stmt);
		/**
		 *  Si le repo cible retourné n'est pas un entier, c'est qu'il n'a pas encore été intégré en BDD et qu'il ne possède donc pas d'ID, on récupère alors directement son nom
		 */
		} else {
			$opRepoTarget = explode('|', $opRepoTarget);
			$opRepoTargetName = $opRepoTarget[0];
			if ($OS_FAMILY == "Debian") {
				if (!empty($opRepoTarget[1])) {	$opRepoTargetDist = $opRepoTarget[1]; }
				if (!empty($opRepoTarget[2])) { $opRepoTargetSection = $opRepoTarget[2]; }
			}
		}
	}

	/**
	 * 	Récupération des informations de la planification à partir de son ID (si renseigné)
	 */

	if (!empty($myop['GpgCheck']))  { $opGpgCheck = $myop['GpgCheck'];   }
	if (!empty($myop['GpgResign'])) { $opGpgResign = $myop['GpgResign']; }
	$opPid = $myop['Pid'];
	$opLogfile = $myop['Logfile'];
	$opStatus = $myop['Status'];

	if ($optype == 'plan') {
		echo '<div class="op-header-container">';
	} else {
		echo '<div class="header-container">';
	}
	echo '<div class="header-blue">';
		echo '<table>';
		echo '<tr>';
		echo '<td class="td-fit">';
		if ($opAction == "new") { echo '<img class="icon" src="icons/plus.png" title="Nouveau" />';	}
		if ($opAction == "update") { echo '<img class="icon" src="icons/update.png" title="Mise à jour" />'; } 
		if ($opAction == "changeEnv" OR strpos($opAction, '->') !== false) { echo '<img class="icon" src="icons/link.png" title="Créat. d\'environnement" />'; }
		if ($opAction == "duplicate") { echo '<img class="icon" src="icons/duplicate.png" title="Duplication" />'; }
		if ($opAction == "delete" OR $opAction == "deleteDist" OR $opAction == "deleteSection") { echo '<img class="icon" src="icons/bin.png" title="Suppression" />'; }
		if ($opAction == "deleteArchive") { echo '<img class="icon" src="icons/bin.png" title="Suppression d\'une archive" />'; }
		if ($opAction == "restore") { echo '<img class="icon" src="icons/arrow-up.png" title="Restauration d\'une archive" />'; }

		echo '</td>';
		echo "<td class=\"td-small\"><a href=\"run.php?logfile=${opLogfile}\">Le <b>$opDate</b> à <b>$opTime</b></a></td>";

		/**
		 * 	Affichage du repo ou du groupe concerné par cette opération
		 * 	Il s'agit soit du repo source, soit du repo cible
		 */
		echo '<td>';
		if (!empty($opGroup)) { echo "Groupe $opGroup"; }

		/**
		 * 	On affiche le repo source uniquement si il n'y a pas de repo cible renseigné. Sinon on se contentera d'afficher uniquement le repo cible.
		 */
		if (empty($opRepoTargetName)) {
			if (!empty($opRepoSourceName)) { echo $opRepoSourceName; }
			/**
			 * 	Dans le cas de Debian, on affiche aussi la distribution et la section
			 */
			if ($OS_FAMILY == "Debian" AND !empty($opRepoSourceDist) AND !empty($opRepoSourceSection)) {
				// Affichage de la distribution
				echo " - $opRepoSourceDist";
				// Affichage de la section
				echo " - $opRepoSourceSection";
			}
		}

		if (!empty($opRepoTargetName)) { echo $opRepoTargetName; }
		if ($OS_FAMILY == "Debian" AND !empty($opRepoTargetDist) AND !empty($opRepoTargetSection)) {
			// Affichage de la distribution
			echo " - $opRepoTargetDist";
			// Affichage de la section
			echo " - $opRepoTargetSection";
		}
		echo '</td>';

		/**
		 * 	Affichage de l'icone en cours ou terminée ou en erreur
		 */
		echo '<td class="td-fit">';
		if ($opStatus == "running") {
			echo 'en cours <img src="images/loading.gif" class="icon" title="en cours d\'exécution" />';
		}
		if ($opStatus == "done") {
			echo '<img class="icon-small" src="icons/greencircle.png" title="Opération terminée" />';
		}
		if ($opStatus == "error") {
			echo '<img class="icon-small" src="icons/redcircle.png" title="Opération en erreur" />';
		}
		if ($opStatus == "stopped") {
			echo '<img class="icon-small" src="icons/redcircle.png" title="Opération stoppée par l\'utilisateur" />';
		}
		echo '</td>';
		echo '</tr>';
		echo '</table>';
	echo '</div>';
	echo '</div>';
}
?>

<body>
<div id="top"></div> <!-- pour atteindre le haut de la page -->
<?php include('includes/header.inc.php'); ?>

<article>
<section class="mainSectionLeft">
	<section class="left">
		<h3>JOURNAL</h3>
		<div id="log">
		<?php
			if ($logfile == 'none') {
				$logfiles = explode("\n", exec("cd $MAIN_LOGS_DIR/ && ls -tr1"));
				$logfile = $logfiles[0];
			}

			/**
			 * 	Récupération du contenu du fichier de log
			 */
			$output = file_get_contents("$MAIN_LOGS_DIR/$logfile");

			/**
			 * 	Suppression des codes ANSI (couleurs) dans le fichier
			 */
			$output = preg_replace('/\x1b(\[|\(|\))[;?0-9]*[0-9A-Za-z]/', "",$output);
			echo $output;
		?>
			<!-- Boutons haut/bas de page - doivent être placées à l'intérieur du div #log -->
			<div id="scrollButtons-container">
				<div id="scrollButtons">
					<?php 
						if (!empty($_GET['displayFullLogs']) AND validateData($_GET['displayFullLogs']) == "yes") {
						if ($logfile == "none") {
							echo '<a href="run.php" class="button-top-down" title="Afficher les détails"><img src="icons/search.png" /></a>';
						} else {
							echo "<a href=\"run.php?logfile=${logfile}\" class=\"button-top-down\" title=\"Masquer les détails\"><img src=\"icons/search.png\" /></a>";
						}
					} else {
						if ($logfile == "none") {
							echo '<a href="run.php?displayFullLogs=yes" class="button-top-down" title="Afficher les détails"><img src="icons/search.png" /></a>';
						} else {
							echo "<a href=\"run.php?logfile=${logfile}&displayFullLogs=yes\" class=\"button-top-down\" title=\"Afficher les détails\"><img src=\"icons/search.png\" /></a>";
						}
					} ?>
					<br>
					<br>
					<a href="#top" class="button-top-down" title="Atteindre le haut de page"><img src="icons/arrow-circle-up.png" /></a>
					<a href="#bottom" class="button-top-down" title="Atteindre le bas de page"><img src="icons/arrow-circle-down.png" /></a>
				</div>
			</div>
		</div>
	</section>
</section>

<section class="mainSectionRight">
	<section class="right">
		<h3>HISTORIQUE</h3>

		<?php
			/**
			 * 	Instanciation d'objets Planification et Operation pour pouvoir récupérer l'historique
			 */
			$db = new Database();

			/**
			 * 	Récupère toutes les planifications en cours d'exécution
			 */
			$stmt = $db->prepare("SELECT * FROM planifications WHERE Status=:status ORDER BY Date DESC, Time DESC");
			$stmt->bindValue(':status', 'running');
			$result = $stmt->execute();
			while ($datas = $result->fetchArray()) { $plansRunning[] = $datas; }
			unset($stmt, $datas, $result);

			/**
			 * 	Récupère toutes les opérations en cours d'exécution et qui n'ont pas été lancées par une planification (type = manual)
			 */
			$stmt = $db->prepare("SELECT * FROM operations WHERE Status=:status AND Type=:type ORDER BY Date DESC, Time DESC");
			$stmt->bindValue(':status', 'running');
			$stmt->bindValue(':type', 'manual');
			$result = $stmt->execute();
			while ($datas = $result->fetchArray()) { $opsRunning[] = $datas; }
			unset($stmt, $datas, $result);

			/**
			 * 	Si les requêtes précédentes ont toutes les deux retourné un résultat, alors on merge ces résultats dans $totalRunning
			 */
			if (!empty($plansRunning) AND !empty($opsRunning)) {
				$totalRunning = array_merge($plansRunning, $opsRunning);
				array_multisort(array_column($totalRunning, 'Date'), SORT_DESC, $totalRunning); // On tri par date pour avoir le + récent en haut
			} elseif (!empty($plansRunning)) {
				$totalRunning = $plansRunning;
			} elseif (!empty($opsRunning)) {
				$totalRunning = $opsRunning;
			}

			/**
			 * 	Recupère toutes les planifications terminées
			 */
			$stmt = $db->prepare("SELECT * FROM planifications WHERE Status NOT IN ('running') ORDER BY Date DESC, Time DESC");
			$stmt->bindValue(':status', 'running');
			$result = $stmt->execute();
			while ($datas = $result->fetchArray()) { $plansDone[] = $datas; }
			unset($stmt, $datas, $result);

			/**
			 * 	Récupère toutes les opérations terminées et qui n'ont pas été lancées par une planification (type = manual)
			 */
			$stmt = $db->prepare("SELECT * FROM operations WHERE Type=:type AND Status NOT IN ('running') ORDER BY Date DESC, Time DESC");
			$stmt->bindValue(':type', 'manual');
			$result = $stmt->execute();
			while ($datas = $result->fetchArray()) { $opsDone[] = $datas; }
			unset($stmt, $datas, $result);

			/**
			 * 	Si les requêtes précédentes ont toutes les deux retourné un résultat, alors on merge ces résultats dans $totalRunning
			 */
			if (!empty($plansDone) AND !empty($opsDone)) {
				$totalDone = array_merge($plansDone, $opsDone);
				array_multisort(array_column($totalDone, 'Date'), SORT_DESC, $totalDone); // On tri par date pour avoir le + récent en haut
			} else if (!empty($plansDone)) {
				$totalDone = $plansDone;
			} else if (!empty($opsDone)) {
				$totalDone = $opsDone;
			}

			/**
			 * 	Affichage des données en cours d'exécution
			 */
			if (!empty($totalRunning)) {
				echo '<p>En cours :</p>';
				foreach ($totalRunning as $itemRunning) {
					if (array_key_exists('Reminder', $itemRunning)) {

						/**
						 * 	1. Récupération de toutes des informations concernant cette planification
						 */
						$planId = $itemRunning['Id'];
						$planDate = DateTime::createFromFormat('Y-m-d', $itemRunning['Date'])->format('d-m-Y');
						$planTime = $itemRunning['Time'];
						$planAction = $itemRunning['Action'];
						$planStatus = $itemRunning['Status'];
						$planLogfile = $itemRunning['Logfile'];

						/**
						 * 	2. Puis récupération des opérations qui ont été exécutées par cette planification
						 */
						$stmt = $db->prepare("SELECT * FROM operations WHERE id_plan=:id_plan AND status=:status");
						$stmt->bindValue(':id_plan', $planId);
						$stmt->bindValue(':status', 'running');
						$result = $stmt->execute();
						while ($datas = $result->fetchArray()) { $plan_opsRunning[] = $datas; }
						unset($stmt, $datas, $result);

						$stmt = $db->prepare("SELECT * FROM operations WHERE id_plan=:id_plan AND status IN ('done', 'error', 'stopped')");
						$stmt->bindValue(':id_plan', $planId);
						$result = $stmt->execute();
						while ($datas = $result->fetchArray()) { $plan_opsDone[] = $datas; }
						unset($stmt, $datas, $result);

						/**
						 * 	3. Affichage de l'en-tête de la planification
						 */
						echo '<div class="header-container">';
							echo '<div class="header-blue">';
							echo '<table>';
								echo '<tr>';
								echo '<td class="td-fit"><img class="icon" src="icons/calendar.png" title="Planification" /></td>';
								if (!empty($planLogfile)) { // On affiche un lien vers le fichier de log de la planification si il y en a un
									echo "<td><a href=\"run.php?logfile=${planLogfile}\">Planification du <b>$planDate</b> à <b>$planTime</b></a></td>";
								} else {
									echo "<td>Planification du <b>$planDate</b> à <b>$planTime</b></td>";
								}
								if ($planStatus == 'running') {
									echo '<td class="td-fit">en cours <img class="icon" src="images/loading.gif" title="En cours d\'exécution" /></td>';
								}
								echo '</tr>';
							echo '</table>';
							echo '</div>';
						echo '</div>';

						/**
						 * 	Si il y a des opérations en cours pour cette planification alors on l'affiche
						 */
						if (!empty($plan_opsRunning)) {
							foreach ($plan_opsRunning as $plan_opRunning) {
								printOp($plan_opRunning, 'plan');
							}
						}

						/**
						 * 	Si il y a des opérations terminées pour cette planification alors on l'affiche
						 */
						if (!empty($plan_opsDone)) {
							foreach ($plan_opsDone as $plan_opDone) {
								printOp($plan_opDone, 'plan');
							}
						}

					} else {
						printOp($itemRunning);
						
					}

					unset($plan_opsRunning, $plan_opsDone);
				}
			}

			/**
			 * 	Affichage des données terminées
			 */
			if (!empty($totalDone)) {
				echo '<p>Terminé :</p>';
				foreach ($totalDone as $itemDone) {
					if (array_key_exists('Reminder', $itemDone)) {

						/**
						 * 	1. Récupération de toutes des informations concernant cette planification
						 */
						$planId = $itemDone['Id'];
						$planDate = DateTime::createFromFormat('Y-m-d', $itemDone['Date'])->format('d-m-Y');
						$planTime = $itemDone['Time'];
						$planAction = $itemDone['Action'];
						$planStatus = $itemDone['Status'];
						$planLogfile = $itemDone['Logfile'];

						/**
						 * 	2. Puis récupération des opérations qui ont été exécutées par cette planification
						 */
						$stmt = $db->prepare("SELECT * FROM operations WHERE id_plan=:id_plan AND status IN ('done', 'error', 'stopped')");
						$stmt->bindValue(':id_plan', $planId);
						$result = $stmt->execute();
						while ($datas = $result->fetchArray()) { $plan_opsDone[] = $datas; }
						unset($stmt, $datas);
					
						/**
						 * 	3. Affichage de l'en-tête de la planification
						 */
						echo '<div class="header-container">';
							echo '<div class="header-blue">';
							echo '<table>';
								echo '<tr>';
								echo '<td class="td-fit"><img class="icon" src="icons/calendar.png" title="Planification" /></td>';
								if (!empty($planLogfile)) { // On affiche un lien vers le fichier de log de la planification si il y en a un
									echo "<td><a href=\"run.php?logfile=${planLogfile}\">Planification du <b>$planDate</b> à <b>$planTime</b></a></td>";
								} else {
									echo "<td>Planification du <b>$planDate</b> à <b>$planTime</b></td>";
								}
								if ($planStatus == "done") {
									echo '<td class="td-fit"><img class="icon-small" src="icons/greencircle.png" title="Opération terminée" /></td>';
								}
								if ($planStatus == "error") {
									echo '<td class="td-fit"><img class="icon-small" src="icons/redcircle.png" title="Opération en erreur" /></td>';
								}
								if ($planStatus == "stopped") {
									echo '<td class="td-fit"><img class="icon-small" src="icons/redcircle.png" title="Opération stoppée par l\'utilisateur" /></td>';
								}
								echo '</tr>';
							echo '</table>';
							echo '</div>';
						echo '</div>';

						/**
						 * 	Si il y a des opérations terminées pour cette planification alors on l'affiche
						 */
						if (!empty($plan_opsDone)) {
							foreach ($plan_opsDone as $plan_opDone) {
								printOp($plan_opDone, 'plan');
							}
						}

					} else {
						printOp($itemDone);
					}

					unset($plan_opsDone);
				}
			}
 ?>
	</section>
</section>
</article>

<?php include('includes/footer.inc.php'); ?>
<div id="bottom"></div> <!-- pour atteindre le bas de page -->
</body>

<script>
/**
 *	script jQuery d'autorechargement du journal et des opération en cours (panneau gauche et panneau droit)
 */
/*$(document).ready(function(){
	setInterval(function(){
		$(".mainSectionLeft").load(window.location.href + " .left" );
		$(".mainSectionRight").load(window.location.href + " .right" );
	}, 3000);
});*/
</script>
</html>