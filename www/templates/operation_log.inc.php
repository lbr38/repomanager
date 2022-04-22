<?php

/**
 *  Template de fichier de log pour chaque opération
 */

$logContent = "
<span>Opération exécutée le : <b>" . DateTime::createFromFormat('Y-m-d', $this->log->date)->format('d-m-Y') . "</b> à <b>" . DateTime::createFromFormat('H-i-s', $this->log->time)->format('H:i:s') . "</b></span><br>
<span>PID : <b>{$this->log->pid}.pid</b></span><br><br>

<h3>$title</h3>

<br>

$content";
