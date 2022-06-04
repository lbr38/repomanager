<?php
if (UPDATE_RUNNING == "yes") {
    /**
     *  La page de maintenance s'affiche sur toutes les pages sauf sur configuration.php
     */
    if (__ACTUAL_URI__ != "/configuration.php") {?>
        <div id="maintenance-container">    
            <div id="maintenance">
                <h3>MISE A JOUR</h3>
                <p>Repomanager est en cours de mise à jour et sera de nouveau disponible prochainement.</p>
                <button class="btn-medium-blue" onClick="window.location.reload();">Actualiser</button>
            </div>
        </div>
        <?php
    }
}
?>