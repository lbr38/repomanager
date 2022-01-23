<!DOCTYPE html>
<html>
<?php 
require_once('models/Autoloader.php');
Autoloader::load();
include_once('includes/head.inc.php');
require_once('functions/common-functions.php');
require_once('functions/run.functions.php');

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
$logfile = 'none';
if (!empty($_GET['logfile'])) $logfile = validateData($_GET['logfile']);
?>

<body>
<div id="top"></div> <!-- pour atteindre le haut de la page -->
<?php include('includes/header.inc.php'); ?>

<article>
<section class="mainSectionLeft">
	<section class="left">
		<h3>JOURNAL</h3>
		<div id="log-container">
			<div id="scrollButtons-container">
				<div id="scrollButtons">
					<?php 
						/**
						 * 	Si on a activé l'affichage de tous les logs alors on fait apparaitre tous les div cachés
						 */
						if (!empty($_COOKIE['displayFullLogs']) AND $_COOKIE['displayFullLogs'] == "yes") {
							echo '<button id="displayFullLogs-no" class="button-top-down-details pointer" title="Masquer les détails"><img src="ressources/icons/search.png" /></button>';
							echo '<style>';
							echo '.getPackagesDiv { display: block; }';
							echo '.signRepoDiv { display: block; }';
							echo '.createRepoDiv { display: block; }';
							echo '</style>';
						} else {
							echo '<button id="displayFullLogs-yes" class="button-top-down-details pointer" title="Afficher les détails"><img src="ressources/icons/search.png" /></button>';
						}
					?>
					<br>
					<br>
					<a href="#top" class="button-top-down" title="Atteindre le haut de page"><img src="ressources/icons/arrow-circle-up.png" /></a>
					<a href="#bottom" class="button-top-down" title="Atteindre le bas de page"><img src="ressources/icons/arrow-circle-down.png" /></a>
				</div>
			</div>

			<div id="log-refresh-container">
				<div id="log">
					<?php
					if ($logfile == 'none') {
						$logfiles = explode("\n", exec("cd ".MAIN_LOGS_DIR."/ && ls -tr1"));
						$logfile = $logfiles[0];
					}

					/**
					 * 	Récupération du contenu du fichier de log
					 */
					$output = file_get_contents(MAIN_LOGS_DIR."/$logfile");

					/**
					 * 	Suppression des codes ANSI (couleurs) dans le fichier
					 */
					$output = preg_replace('/\x1b(\[|\(|\))[;?0-9]*[0-9A-Za-z]/', "",$output);
					echo $output;
					?>
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
			$myplan = new Planification();
			$myop = new Operation();

			/**
			 * 	Récupère toutes les planifications en cours d'exécution
			 */
			$plansRunning = $myplan->listRunning();

			/**
			 * 	Récupère toutes les opérations en cours d'exécution et qui n'ont pas été lancées par une planification (type = manual)
			 */
			$opsRunning = $myop->listRunning('manual');

			/**
			 * 	Si les requêtes précédentes ont toutes les deux retourné un résultat, alors on merge ces résultats dans $totalRunning
			 */
			if (!empty($plansRunning) AND !empty($opsRunning)) {
				
				$totalRunning = array_merge($plansRunning, $opsRunning);
				array_multisort(array_column($totalRunning, 'Date'), SORT_DESC, array_column($totalRunning, 'Time'), SORT_DESC, $totalRunning); // On tri par date pour avoir le + récent en haut
			
			} elseif (!empty($plansRunning)) {

				$totalRunning = $plansRunning;

			} elseif (!empty($opsRunning)) {

				$totalRunning = $opsRunning;
			}

			/**
			 * 	Recupère toutes les planifications terminées
			 */
			$plansDone = $myplan->listDone();

			/**
			 * 	Récupère toutes les opérations terminées et qui n'ont pas été lancées par une planification (type = manual)
			 */
			$opsDone = $myop->listDone('manual');

			/**
			 * 	Récupère toutes les opérations terminées qui ont été lancées par une planification récurrente
			 */
			$opsFromRegularPlanDone = $myop->listDone('plan', 'regular');

			/**
			 * 	Si les requêtes précédentes ont toutes les deux retourné un résultat, alors on merge ces résultats dans $totalRunning
			 */
			if (!empty($plansDone) AND !empty($opsDone)) {
				
				$totalDone = array_merge($plansDone, $opsDone);
				array_multisort(array_column($totalDone, 'Date'), SORT_DESC, array_column($totalDone, 'Time'), SORT_DESC, $totalDone); // On tri par date pour avoir le + récent en haut
			
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
						$planType = $itemRunning['Type'];
						if (!empty($itemRunning['Frequency'])) $planFrequency = $itemRunning['Frequency'];
						if (!empty($itemRunning['Date']))      $planDate = DateTime::createFromFormat('Y-m-d', $itemRunning['Date'])->format('d-m-Y');
						if (!empty($itemRunning['Time']))      $planTime = $itemRunning['Time'];
						$planAction = $itemRunning['Action'];
						$planStatus = $itemRunning['Status'];
						$planLogfile = $itemRunning['Logfile'];

						/**
						 * 	2. Puis récupération des opérations qui ont été exécutées par cette planification
						 */
						$stmt = $myop->db->prepare("SELECT * FROM operations WHERE id_plan=:id_plan AND status=:status");
						$stmt->bindValue(':id_plan', $planId);
						$stmt->bindValue(':status', 'running');
						$result = $stmt->execute();
						while ($row = $result->fetchArray(SQLITE3_ASSOC)) $plan_opsRunning[] = $row;

						$stmt = $myop->db->prepare("SELECT * FROM operations WHERE id_plan=:id_plan AND status IN ('done', 'error', 'stopped')");
						$stmt->bindValue(':id_plan', $planId);
						$result = $stmt->execute();
						while ($row = $result->fetchArray(SQLITE3_ASSOC)) $plan_opsDone[] = $row;

						/**
						 * 	3. Affichage de l'en-tête de la planification
						 */
						echo '<div class="header-container">';
							echo '<div class="header-blue">';
							echo '<table>';
								echo '<tr>';
								echo '<td class="td-fit"><img class="icon" src="ressources/icons/calendar.png" title="Planification" /></td>';
								/**
								 * 	On affiche un lien vers le fichier de log de la planification si il y en a un
								 */
								if ($planType == "plan") {
									if (!empty($planLogfile)) {
										echo "<td><a href=\"run.php?logfile=${planLogfile}\">Planification du <b>$planDate</b> à <b>$planTime</b></a></td>";
									} else {
										echo "<td>Planification du <b>$planDate</b> à <b>$planTime</b></td>";
									}
								}
								if ($planType == "regular") {
									echo "<td>Planification récurrente</b></td>";
								}
								echo '<td class="td-fit">en cours <img class="icon" src="ressources/images/loading.gif" title="En cours d\'exécution" /></td>';
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
			if (!empty($totalDone) OR !empty($opsFromRegularPlanDone)) {

				/**
				 * 	Affichage des tâches terminées
				 */
				if (!empty($totalDone)) {

					echo '<p>Terminé :</p>';

					/**
					 * 	Nombre maximal d'opérations qu'on souhaite afficher par défaut, le reste est masqué et affichable par un bouton "Afficher tout"
					 * 	Lorsque $i a atteint le nombre maximal $printMaxItems, on commence à masquer les opérations
					 */
					$i = 0;
					$printMaxItems = 2;

					/**
					 * 	Traitement de toutes les opérations terminées
					 */
					foreach ($totalDone as $itemDone) {
						/**
						 * 	Si on a dépassé le nombre maximal d'opération qu'on souhaite afficher par défaut, alors les suivantes sont masquées dans un container caché
						 * 	Sauf si le cookie printAllOp = yes, dans ce cas on affiche tout
						 */
						if ($i > $printMaxItems) {
							if (!empty($_COOKIE['printAllOp']) AND $_COOKIE['printAllOp'] == "yes")
								echo '<div class="hidden-op">';
							else
								echo '<div class="hidden-op hide">';
						}

						/**
						 * 	Si l'élément comporte une colonne 'Reminder' alors l'élément est une planification.
						 * 	On va donc récupérer toutes les opérations liées à cette planification
						 */
						if (array_key_exists('Reminder', $itemDone)) {

							/**
							 * 	1. Récupération de toutes des informations concernant cette planification
							 */
							$planId = $itemDone['Id'];
							$planType = $itemDone['Type'];
							if (!empty($itemDone['Frequency'])) $planFrequency = $itemDone['Frequency'];
							if (!empty($itemDone['Date']))      $planDate = DateTime::createFromFormat('Y-m-d', $itemDone['Date'])->format('d-m-Y');
							if (!empty($itemDone['Time']))      $planTime = $itemDone['Time'];
							$planAction = $itemDone['Action'];
							$planStatus = $itemDone['Status'];
							$planLogfile = $itemDone['Logfile'];

							/**
							 * 	2. Puis récupération des opérations qui ont été exécutées par cette planification
							 */
							$stmt = $myop->db->prepare("SELECT * FROM operations WHERE id_plan=:id_plan AND status IN ('done', 'error', 'stopped')");
							$stmt->bindValue(':id_plan', $planId);
							$result = $stmt->execute();
							while ($row = $result->fetchArray(SQLITE3_ASSOC)) $plan_opsDone[] = $row;
						
							/**
							 * 	3. Affichage de l'en-tête de la planification
							 */
							echo '<div class="header-container">';
								echo '<div class="header-blue">';
								echo '<table>';
									echo '<tr>';
									echo '<td class="td-fit"><img class="icon" src="ressources/icons/calendar.png" title="Planification" /></td>';
									if ($planType == "plan") {
										if (!empty($planLogfile)) { // On affiche un lien vers le fichier de log de la planification si il y en a un
											echo "<td><a href=\"run.php?logfile=${planLogfile}\">Planification du <b>$planDate</b> à <b>$planTime</b></a></td>";
										} else {
											echo "<td>Planification du <b>$planDate</b> à <b>$planTime</b></td>";
										}
										if ($planStatus == "done") echo '<td class="td-fit"><img class="icon-small" src="ressources/icons/greencircle.png" title="Opération terminée" /></td>';
										if ($planStatus == "error") echo '<td class="td-fit"><img class="icon-small" src="ressources/icons/redcircle.png" title="Opération en erreur" /></td>';
										if ($planStatus == "stopped") echo '<td class="td-fit"><img class="icon-small" src="ressources/icons/redcircle.png" title="Opération stoppée par l\'utilisateur" /></td>';
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

						if ($i > $printMaxItems) echo '</div>'; // clôture de <div class="hidden-op hide">

						++$i;
					}

					if ($i > $printMaxItems) {
						/**
						 * 	On affiche le bouton Afficher uniquement si le cookie printAllOp n'est pas en place ou n'est pas égal à "yes"
						 */
						if (!isset($_COOKIE['printAllOp']) OR (!empty($_COOKIE['printAllOp']) AND $_COOKIE['printAllOp'] != "yes")) {
							echo '<p id="print-all-op" class="pointer center"><b>Afficher tout</b> <img src="ressources/icons/chevron-circle-down.png" class="icon" /></p>';
						}
					}
				}


				/**
				 * 	Affichage des tâches récurrentes terminées
				 */
				if (!empty($opsFromRegularPlanDone)) {
					
					echo '<p>Tâches récurrentes :</p>';

					/**
					 * 	Nombre maximal d'opérations qu'on souhaite afficher par défaut, le reste est masqué et affichable par un bouton "Afficher tout"
					 * 	Lorsque $i a atteint le nombre maximal $printMaxItems, on commence à masquer les opérations
					 */
					$i = 0;
					$printMaxItems = 2;


					foreach ($opsFromRegularPlanDone as $itemDone) {
						/**
						 * 	Si on a dépassé le nombre maximal d'opération qu'on souhaite afficher par défaut, alors les suivantes sont masquées dans un container caché
						 * 	Sauf si le cookie printAllRegularOp = yes, dans ce cas on affiche tout
						 */
						if ($i > $printMaxItems) {
							if (!empty($_COOKIE['printAllRegularOp']) AND $_COOKIE['printAllRegularOp'] == "yes")
								echo '<div class="hidden-regular-op">';
							else
								echo '<div class="hidden-regular-op hide">';
						}

						printOp($itemDone);

						if ($i > $printMaxItems) echo '</div>'; // clôture de <div class="hidden-regular-op hide">

						++$i;
					}

					if ($i > $printMaxItems) {
						/**
						 * 	On affiche le bouton Afficher tout uniquement si le cookie printAllRegularOp n'est pas en place ou n'est pas égal à "yes"
						 */
						if (!isset($_COOKIE['printAllRegularOp']) OR (!empty($_COOKIE['printAllRegularOp']) AND $_COOKIE['printAllRegularOp'] != "yes")) {
							echo '<p id="print-all-regular-op" class="pointer center"><b>Afficher tout</b> <img src="ressources/icons/chevron-circle-down.png" class="icon" /></p>';
						}
					}
				}
			} ?>
	</section>
</section>
</article>

<?php include('includes/footer.inc.php'); ?>
<div id="bottom"></div> <!-- pour atteindre le bas de page -->
</body>
<script>
$(document).ready(function(){
	/**
	 *	Autorechargement du journal et des opération en cours (panneau gauche et panneau droit)
	 */
	setInterval(function(){
		$(".mainSectionRight").load("run.php?reload .mainSectionRight > *");
		$(".mainSectionLeft").load(" .mainSectionLeft > *");
	}, 3000);

	/**
	*	Affiche des boutons de défilement si la page de log fait +700px de haut
	*/
	/*if ($('#log').height() < 700) {
		$(".button-top-down").hide();
	}*/

	/**
	*	Afficher toutes les opérations terminées
	*/
	$(document).on('click','#print-all-op',function(){
		$(".hidden-op").show();		// On affiche les opérations masquées
		$("#print-all-op").hide();	// On masque le bouton "Afficher tout"

		// Création d'un cookie (expiration 15min)
		document.cookie = "printAllOp=yes;max-age=900;";
	});
	/**
	*	Afficher toutes les opérations récurrentes terminées
	*/
	$(document).on('click','#print-all-regular-op',function(){
		$(".hidden-regular-op").show();		// On affiche les opérations masquées
		$("#print-all-regular-op").hide();	// On masque le bouton "Afficher tout"

		// Création d'un cookie (expiration 15min)
		document.cookie = "printAllRegularOp=yes;max-age=900;";
	});

	/**
	 *	Afficher ou non tout le détail d'une opération
	 */
	$(document).on('click','#displayFullLogs-yes',function(){
		document.cookie = "displayFullLogs=yes";
		$(".mainSectionLeft").load(" .mainSectionLeft > *");
	});

	$(document).on('click','#displayFullLogs-no',function(){
		document.cookie = "displayFullLogs=no";
		$(".mainSectionLeft").load(" .mainSectionLeft > *");
	});
});
</script>
</html>