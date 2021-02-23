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

    if (empty($_GET['logfile'])) {
		$logfile = "none";
	} else {
        $logfile = validateData($_GET['logfile']);
    }
?>

<body>
	<div id="top"></div> <!-- pour atteindre le haut de la page -->
	<?php include('common-header.inc.php'); ?>
	<section class="main">
		<?php
		selectlogs(); // Affichage de la liste des fichiers de logs
		selectPlanlogs();
		if ($logfile == "none") {
			echo "<p>Aucun fichier de log sélectionné</p>";
		} ?>
		<section class="center">
			<div id="log">
				<pre>
				<?php 
				if ($logfile !== "none") {
					$output = file_get_contents("${MAIN_LOGS_DIR}/${logfile}"); // recup du contenu du fichier de log
					$output = preg_replace('/\x1b(\[|\(|\))[;?0-9]*[0-9A-Za-z]/', "",$output); // suppression des codes ANSI (couleurs)
					echo $output;
				} ?>
				</pre>
			</div>
		</section>
		<!-- Boutons haut/bas de page -->
		<div id="scrollButtons">
			<a href="#top" class="button-top" title="Atteindre le haut de page"><img src="icons/arrow-circle-up.png" /></a>
			<a href="#bottom" class="button-down" title="Atteindre le bas de page"><img src="icons/arrow-circle-down.png" /></a>
		</div>
	</section>

	
	
	<div id="bottom"></div> <!-- pour atteindre le bas de page -->
	<?php include('common-footer.inc.php'); ?>
</body>
</html>