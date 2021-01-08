<?php
// Import des variables et fonctions nécessaires, ne pas changer l'ordre des requires
require 'common-vars.php';
require 'common-functions.php';
require 'common.php';
require 'display.php';
if ($debugMode == "enabled") { echo "Mode debug activé : "; print_r($_POST); }

// On récupère la liste des fichiers de logs en les triant 
$logFiles = scandir("${BASE_DIR}/logs/", SCANDIR_SORT_DESCENDING);

// Si un fichier de log a été sélectionné dans le formulaire, alors ce sera lui qui sera affiché
if (!empty($_POST['logselect'])) {
	//echo tutuuuuuuuu;
	$logFile = $_POST['logselect'];
} else { // aucun fichier de log n'a été sélectionné, alors on affichera le dernier en date
	//echo tototoooooooooooooo;
	$logFile = $logFiles[0]; // Celui en position 0 est le dernier fichier de log.
}

// bouton "Tuer le process en cours"
if(isset($_GET['killprocess'])) {
	exec("killall -9 repomanager");
	header('Location: journal.php'); // puis recharge la page
}

/*
 * Easy PHP Tail 
 * by: Thomas Depole
 * v1.0
 * 
 * just fill in the varibles bellow, open in a web browser and tail away 
 */


$interval = 100; //how often it checks the log file for changes, min 100
$textColor = ""; //use CSS color

// Don't have to change anything bellow
if(!$textColor) $textColor = "black";
if(isset($_GET['getLog'])) {
	$output = file_get_contents("${LOGS_DIR}/${logFile}"); // recup du contenu du fichier de log
	$output = preg_replace('/\x1b(\[|\(|\))[;?0-9]*[0-9A-Za-z]/', "",$output); // suppression des codes ANSI (couleurs)
	echo $output;
} else {
?>

<html>
	<?php include('common-head.inc.php'); ?>

	<title>Repomanager - traitement en cours</title>

	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.0/jquery.min.js" type="text/javascript"></script>
	<script>
		setInterval(readLogFile, <?php echo $interval; ?>);
		window.onload = readLogFile; 
		var pathname = window.location.pathname;
		var scrollLock = false;
		
		$(document).ready(function(){
			$('.disableScrollLock').click(function(){
				$("html,body").clearQueue()
				$(".disableScrollLock").hide();
				$(".enableScrollLock").show();
				scrollLock = false;
			});
			$('.enableScrollLock').click(function(){
				$("html,body").clearQueue()
				$(".enableScrollLock").hide();
				$(".disableScrollLock").show();
				scrollLock = false;
			});
		});

		function readLogFile(){
			$.get(pathname, { getLog : "true" }, function(data) {
				data = data.replace(new RegExp("\n", "g"), "<br />");
		        $("#log").html(data);
		        
		        if(scrollLock == true) { $('html,body').animate({scrollTop: $("#scrollLock").offset().top}, <?php echo $interval; ?>) };
		    });
		}
	</script>
	<body>
		<div id="top"></div> <!-- pour atteindre le haut de la page -->
		<?php include('common-header.inc.php'); ?>

		<article class="main">
			<form action="journal.php" method="post">
			<table>
			<tr>
				<td>
				<?php 
				echo "<select name=\"logselect\">";
				echo "<option value=\"$logFiles[0]\">Dernier fichier de log</option>";
				foreach($logFiles as $logFile) {
					if (($logFile != "..") AND ($logFile != ".") AND ($logFile != "lastlog.log")) { // on ne souhaite pas afficher les répertoires '..' '.' ni le fichier lastlog.log (déjà affiché en premier ci-dessus)
						echo "<option value=\"${logFile}\">${logFile}</option>";
					}
				}
				echo "</select>";
				?>
				</td>
				<td><button type="submit" class="button-submit-xsmall-blue">Afficher</button></td>
				</tr>
			</table>
			</form>

			<article class="log">
				<pre><div id="log"></div></pre>
			</article>
			
		</article>
		<div id="bottom"></div> <!-- pour atteindre le bas de page -->

		<div id="scrollLock"> 
			<!--<input class="disableScrollLock" style="display: none;" type="button" value="Désactiver le scroll auto" /> 
			<input class="enableScrollLock" type="button" value="Activer le scroll auto" />-->
			<a href="#top" class="button-small">Top of document</a>
			<a href="#bottom" class="button-small">Bottom of document</a>
		</div>
		<?php include('common-footer.inc.php'); ?>
	</body>
</html>
<?php  } ?>

