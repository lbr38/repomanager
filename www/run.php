<!DOCTYPE html>
<html>
<?php include('common-head.inc.php'); ?>

<?php
// Import des variables et fonctions nécessaires, ne pas changer l'ordre des requires
require 'vars/common.vars';
require 'common-functions.php';
require 'common.php';
require 'vars/display.vars';

if ($debugMode == "enabled") { echo "Mode debug activé : "; print_r($_POST); }

// bouton "Tuer le process en cours"
if(isset($_GET['killprocess'])) {
	exec("killall -9 repomanager");
	header('Location: journal.php'); // puis recharge la page
}
?>

<body>
<div id="top"></div> <!-- pour atteindre le haut de la page -->
<?php include('common-header.inc.php'); ?>
	<section class="main">
		<?php
			selectlogs(); // Affichage de la liste des fichiers de logs
		?>
		<section class="center">
			<div id="log">
				<?php
				// Si un lien symbolique lastlog.log existe dans le répertoire des log, alors on récupère le nom de fichier vers lequel il pointe
				if (file_exists("$MAIN_LOGS_DIR/lastlog.log")) {
					$logfile = readlink("${MAIN_LOGS_DIR}/lastlog.log"); // = "${MAIN_LOGS_DIR}/lastlog.log";
				} else {
					// Sinon on récupère la liste des fichiers de logs en les triant
					// Celui en position 0 est le dernier fichier de log
					$logfiles = scandir("$MAIN_LOGS_DIR/", SCANDIR_SORT_DESCENDING);
					$logfile = "${MAIN_LOGS_DIR}/$logfiles[0]";
				}

				// Récupération du contenu du fichier de log
				$output = file_get_contents("$logfile");
				// Suppression des codes ANSI (couleurs) dans le fichier
				$output = preg_replace('/\x1b(\[|\(|\))[;?0-9]*[0-9A-Za-z]/', "",$output);
				
				echo "$output"; ?>
			</div>
		</section>
		<!-- Boutons haut/bas de page -->
		<div id="scrollButtons">
			<a href="#top" class="button-top" title="Atteindre le haut de page"><img src="icons/arrow-circle-up.png" /></a>
			<a href="#bottom" class="button-down" title="Atteindre le bas de page"><img src="icons/arrow-circle-down.png" /></a>
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
}, 1000);
});
</script>
</html>