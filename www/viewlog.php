<html>
<?php include('common-head.inc.php'); ?>

<?php
    // Import des variables et fonctions nécessaires, ne pas changer l'ordre des requires
    require_once 'vars/common.vars';
    require_once 'common-functions.php';
    require_once 'common.php';
    require_once 'vars/display.vars';
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
		<section class="log">
			<pre>
				<div id="log">
				<?php 
				if ($logfile !== "none") {
					$output = file_get_contents("${MAIN_LOGS_DIR}/${logfile}"); // recup du contenu du fichier de log
					$output = preg_replace('/\x1b(\[|\(|\))[;?0-9]*[0-9A-Za-z]/', "",$output); // suppression des codes ANSI (couleurs)
					echo $output;
				} ?>
				</div>
			</pre>
		</section>
	</section>

	<div id="bottom"></div> <!-- pour atteindre le bas de page -->

	<div id="scrollButtons">
		<a href="#top" class="button-top" title="Atteindre le haut de page"><img src="icons/arrow-circle-up.png" /></a>
		<a href="#bottom" class="button-down" title="Atteindre le bas de page"><img src="icons/arrow-circle-down.png" /></a>
	</div>
    
	<?php include('common-footer.inc.php'); ?>
</body>
</html>