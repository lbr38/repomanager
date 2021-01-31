<?php
// Import des variables et fonctions nécessaires, ne pas changer l'ordre des requires
require_once 'vars/common.vars';
require_once 'common-functions.php';
require_once 'common.php';
require_once 'vars/display.vars';
if ($debugMode == "enabled") { echo "Mode debug activé : "; print_r($_POST); }

// On récupère la liste des fichiers de logs en les triant 
$logfiles = scandir("$MAIN_LOGS_DIR/", SCANDIR_SORT_DESCENDING);
$logfile = $logfiles[0]; // Celui en position 0 est le dernier fichier de log, c'est celui-ci qu'on affiche (lastlog)

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
	$output = file_get_contents("${MAIN_LOGS_DIR}/${logfile}"); // recup du contenu du fichier de log
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

		<section class="main">
			<?php
				selectlogs(); // Affichage de la liste des fichiers de logs
			?>

			<section class="log">
				<pre><div id="log"></div></pre>
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
<?php  } ?>

