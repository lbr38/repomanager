<!DOCTYPE html>
<html>
<?php include('common-head.inc.php'); ?>

<?php
// Import des variables et fonctions nécessaires, ne pas changer l'ordre des requires
require_once('functions/load_common_variables.php');
require_once('functions/load_display_variables.php');
require_once('functions/common-functions.php');
require_once('common.php');

// bouton "Tuer le process en cours"
if(isset($_GET['killprocess'])) {
	exec("killall -9 repomanager");
	if ($OS_FAMILY == "Redhat") {
		exec("killall -9 reposync");
		exec("killall -9 rpmresign");
		exec("killall -9 createrepo");
	}
	if ($OS_FAMILY == "Debian") {
		exec("killall -9 debmirror");
		exec("killall -9 reprepro");
	}
	header('Location: run.php'); // puis recharge la page
}

if (empty($_GET['logfile'])) {
	$logfile = "none";
} else {
	$logfile = validateData($_GET['logfile']);
}

if (!empty($_GET['displayFullLogs']) AND validateData($_GET['displayFullLogs']) == "yes") {
	// Si on a activé l'affichage de tous les logs alors on fait apparaitre tous les div cachés
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
				echo "<a href=\"run.php\">Masquer les détails</a>";
			} else {
				echo "<a href=\"run.php?logfile=${logfile}\">Masquer les détails</a>";
			}
		} else {
			if ($logfile == "none") {
				echo "<a href=\"run.php?displayFullLogs=yes\">Afficher les détails</a>";
			} else {
				echo "<a href=\"run.php?logfile=${logfile}&displayFullLogs=yes\">Afficher les détails</a>";
			}
		} ?>

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