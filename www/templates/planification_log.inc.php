<?php

/**
 *  Template de fichier de log pour chaque planification
 *  Inclu dans ce log : 
 *  La date et l'heure de la planification (à ne pas confondre avec la date et l'heure de la/les opérations lancées par la planification)
 *  Le PID de la planification (à ne pas confondre avec le PID de la/les opérations lancées par la planification)
 *  Le titre de la planification (à ne pas confondre avec le titre de la/les opérations lancées par la planification même si c'est le même en soit)
 */

$logContent = '
<h3>PLANIFICATION</h3>

<table class="op-table">
    <tr>
        <th>EXECUTÉE LE</td>
        <td><b>'.DateTime::createFromFormat('Y-m-d', $this->log->date)->format('d-m-Y').'</b> à <b>'.DateTime::createFromFormat('H-i-s', $this->log->time)->format('H:i:s').'</b></td>
    </tr>
    <tr>
        <th>PID</td>
        <td>'.$this->log->pid.'</td>
    </tr>'.
    ((!empty($this->mailRecipient)) ? 
    '<tr>
        <th>CONTACT</th>
        <td>'.$this->getMailRecipientFormatted().'</td>
    </tr>
    ' : '').
'</table>

<br>
<hr>
<br>

'.$content;
?>