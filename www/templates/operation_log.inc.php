<?php

/**
 *  Template de fichier de log pour chaque opération
 */

$logContent = "
<span>Opération exécutée le : <b>{$log->date} à {$log->time}</b></span><br>
<span>PID : <b>{$log->pid}.pid</b></span><br><br>

<h5>${title}</h5>

$content";
?>