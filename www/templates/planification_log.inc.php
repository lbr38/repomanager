<?php

/**
 *  Template de fichier de log pour chaque planification
 *  Inclu dans ce log :
 *  La date et l'heure de la planification (à ne pas confondre avec la date et l'heure de la/les opérations lancées par la planification)
 *  Le PID de la planification (à ne pas confondre avec le PID de la/les opérations lancées par la planification)
 *  Le titre de la planification (à ne pas confondre avec le titre de la/les opérations lancées par la planification même si c'est le même en soit)
 */

$logContent = '
<div class="div-generic-blue">
    <table class="op-table">
        <tr>
            <th>ACTION</td>
            <td><b>' . ucfirst($this->action) . '</b></td>
        </tr>
        <tr>
            <th>EXECUTION</td>
            <td><b>' . DateTime::createFromFormat('Y-m-d', $this->log->getDate())->format('d-m-Y') . ' ' . DateTime::createFromFormat('H-i-s', $this->log->getTime())->format('H:i:s') . '</b></td>
        </tr>
        <tr>
            <th>PID</td>
            <td>' . $this->log->getPid() . '</td>
        </tr>' .
        ((!empty($this->mailRecipient)) ?
        '<tr>
            <th>CONTACT</th>
            <td>' . $this->getMailRecipientFormatted() . '</td>
        </tr>
        ' : '') .
    '</table>
</div>
<br>
<hr>
<br>

' . $content;
