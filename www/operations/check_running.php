<?php

if (!empty($argv[1])) { $LOGFILE = $argv[1]; } // Chemin du fichier de log principal (logs/main/repomanager...)
if (!empty($argv[2])) { $OPERATION_TEMP_DIR = $argv[2]; } // Chemin du répertoire temporaire de l'opération en cours (.temp/PID/)
if (!empty($argv[3])) { $steps = $argv[3]; } // Nombre d'étapes totales


function writeStepLog($LOGFILE, $OPERATION_TEMP_DIR, $steps) {
    $j = 0;
    // Suppression du fichier de log avant de le reconstruire
    unlink($LOGFILE);
    touch($LOGFILE);

    // On ajoute chaque log d'étape au fichier de log principal
    // Exemple : ./temp/PID/1/1.log
    while ($j != ($steps + 1)) { // On boucle sur tous les petits fichiers de log d'étapes jusqu'à atteindre le nombre d'étapes totales
        $stepLog = "$OPERATION_TEMP_DIR/${j}/${j}.log";
        if (file_exists($stepLog)) { file_put_contents($LOGFILE, file_get_contents("$stepLog"), FILE_APPEND); }
        ++$j;
    }    
}

// Tant qu'un fichier "completed" n'existe pas dans le répertoire temporaire, on ré-écrit le fichier de log afin qu'il soit le plus à jour possible
while (!file_exists("${OPERATION_TEMP_DIR}/completed")) {
    writeStepLog($LOGFILE, $OPERATION_TEMP_DIR, $steps);
	sleep(1);
}

// On écrit une dernière fois le fichier pour être sûr d'avoir récupéré tous les petits logs
writeStepLog($LOGFILE, $OPERATION_TEMP_DIR, $steps);

exit(0);
?>