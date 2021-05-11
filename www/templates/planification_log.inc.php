<?php

/**
 *  Template de fichier de log pour chaque planification
 */

$logContent = "
<span>Planification exécutée le : <b>{$this->log->date} à {$this->log->time}</b></span><br>
<span>PID : <b>{$this->log->pid}.pid</b></span><br><br>

<h5>{$this->log->title}</h5>

$content";
?>