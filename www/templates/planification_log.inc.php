<?php

/**
 *  Template de fichier de log pour chaque planification
 *  Inclu dans ce log : 
 *  La date et l'heure de la planification (à ne pas confondre avec la date et l'heure de la/les opérations lancées par la planification)
 *  Le PID de la planification (à ne pas confondre avec le PID de la/les opérations lancées par la planification)
 *  Le titre de la planification (à ne pas confondre avec le titre de la/les opérations lancées par la planification même si c'est le même en soit)
 */

$logContent = "
<span>Planification exécutée le : <b>".DateTime::createFromFormat('Y-m-d', $this->log->date)->format('d-m-Y')."</b> à <b>".DateTime::createFromFormat('H-i-s', $this->log->time)->format('H:i:s')."</b></span><br>
<span>PID : <b>{$this->log->pid}.pid</b></span><br><br>

<h3>{$this->log->title}</h3>

$content";
?>