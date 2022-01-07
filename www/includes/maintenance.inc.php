<?php
if (UPDATE_RUNNING == "yes") {
    /**
     *  La page de maintenance s'affiche sur toutes les pages sauf sur configuration.php
     */
    if (__ACTUAL_URI__ != "/configuration.php") {
        echo '<div id="maintenance">';
        echo '<p>Mise à jour de repomanager en cours <img src="ressources/images/loading.gif" class="icon" /></p>';
        echo '</div>';
    }
}
?>