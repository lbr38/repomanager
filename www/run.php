<!DOCTYPE html>
<html>
<?php include('common-head.inc.php'); ?>

<?php
/**
 *  Import des variables et fonctions nécessaires
 */
require_once('functions/load_common_variables.php');
require_once('functions/load_display_variables.php');
require_once('functions/common-functions.php');
require_once('common.php');

/**
 * 	Bouton 'Stop' pour arrêter une opération en cours
 */
if(isset($_GET['stop'])) {
	$pid = validateData($_GET['stop']);

	if (file_exists("${PID_DIR}/${pid}.pid")) {
		/**
		 * 	Récupération du nom de fichier de log car on va avoir besoin d'indiquer dedans que l'opération a été stoppée
		 */
		$logFile = exec("grep '^LOG=' ${PID_DIR}/${pid}.pid | sed 's/LOG=//g' | sed 's/\"//g'");

		/**
		 * 	Récupération des subpid car il va falloir les tuer aussi
		 */
		$subpids = shell_exec("grep -h '^SUBPID=' ${PID_DIR}/${pid}.pid | sed 's/SUBPID=//g' | sed 's/\"//g'");
		
		/**
		 * 	Kill des subpids si il y en a
		 */
		if (!empty($subpids)) {
			$subpids = explode("\n", trim($subpids));
			foreach($subpids as $subpid) {
				exec("kill -9 $subpid");
			}
		}

		/**
		 * 	Suppression du fichier pid principal
		 */
		unlink("${PID_DIR}/${pid}.pid");

		if (!empty($logFile)) {
			file_put_contents("$MAIN_LOGS_DIR/$logFile", '<p>Opération stoppée par l\'utilisateur</p>'.PHP_EOL, FILE_APPEND);
		}

		printAlert("L'opération a été arrêtée");
	}
}

if (empty($_GET['logfile'])) {
	$logfile = "none";
} else {
	$logfile = validateData($_GET['logfile']);
}

if (!empty($_GET['displayFullLogs']) AND validateData($_GET['displayFullLogs']) == "yes") {
	/**
	 * 	Si on a activé l'affichage de tous les logs alors on fait apparaitre tous les div cachés
	 */
	echo '<style>';
	echo '.getPackagesDiv { display: block; }';
	echo '.signRepoDiv { display: block; }';
	echo '.createRepoDiv { display: block; }';
	echo '</style>';
} ?>

<body>
<div id="top"></div> <!-- pour atteindre le haut de la page -->
<?php include('common-header.inc.php'); ?>
	<section class="main">
		<?php
			selectlogs(); // Affichage de la liste des fichiers de logs
			echo '<br>';
			selectPlanlogs(); // Affichage de la liste des fichiers de logs des planifications
			echo '<br>';
		?>
		<?php if (!empty($_GET['displayFullLogs']) AND validateData($_GET['displayFullLogs']) == "yes") {
			if ($logfile == "none") {
				echo "<a href=\"run.php\"><button class=\"button-submit-medium-blue float-right\">Masquer les détails</button></a>";
			} else {
				echo "<a href=\"run.php?logfile=${logfile}\"><button class=\"button-submit-medium-blue float-right\">Masquer les détails</button></a>";
			}
		} else {
			if ($logfile == "none") {
				echo "<a href=\"run.php?displayFullLogs=yes\"><button class=\"button-submit-medium-blue float-right\">Afficher les détails</button></a>";
			} else {
				echo "<a href=\"run.php?logfile=${logfile}&displayFullLogs=yes\"><button class=\"button-submit-medium-blue float-right\">Afficher les détails</button></button></a>";
			}
		} ?>
		<br><br>
		<section class="center">
			<div id="log">
				<?php
				if ($logfile == "none") {
					$logfiles = explode("\n", exec("cd $MAIN_LOGS_DIR/ && ls -tr1"));
					$logfile = "$logfiles[0]";
				}

				// Récupération du contenu du fichier de log
				$output = file_get_contents("$MAIN_LOGS_DIR/$logfile");
				// Suppression des codes ANSI (couleurs) dans le fichier
				$output = preg_replace('/\x1b(\[|\(|\))[;?0-9]*[0-9A-Za-z]/', "",$output);
				echo "$output"; ?>
			</div>
		</section>
		<!-- Boutons haut/bas de page -->
		<div id="scrollButtons">
			<a href="#top" class="button-top-down" title="Atteindre le haut de page"><img src="icons/arrow-circle-up.png" /></a>
			<a href="#bottom" class="button-top-down" title="Atteindre le bas de page"><img src="icons/arrow-circle-down.png" /></a>
		</div>
	</section>

	<?php include('common-footer.inc.php'); ?>
	<div id="bottom"></div> <!-- pour atteindre le bas de page -->
</body>

<script>
// script jQuery d'autorechargement du fichier de log :
$(document).ready(function(){
	setInterval(function(){
		$(".center").load(window.location.href + " #log" );
	}, 3000);
});
</script>
</html>