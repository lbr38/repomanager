<?php
trait op_printDetails {
    /**
    *   Génération d'un tableau récapitulatif de l'opération
    *   Valide pour : 
    *    - un nouveau repo/section 
    *    - une mise à jour de repo/section
    */
    public function op_printDetails() {
        global $OS_FAMILY;

        ob_start();

        // Affichage du récapitulatif de l'opération
        echo '<table>';
        if ($OS_FAMILY == "Redhat") {
            echo "<tr>
                <td>REPO SOURCE :</td>
                <td><b>{$this->repo->source}</b></td>
            </tr>
            <tr>
                <td>NOM DU REPO :</td>
                <td><b>{$this->repo->name}</b></td>
            </tr>";
        }
        if ($OS_FAMILY == "Debian") {
            echo "<tr>
                <td>HOTE SOURCE :</td>
                <td><b>{$this->repo->source} ({$this->repo->sourceFullUrl})</b></td>
            </tr>
            <tr>
                <td>NOM DU REPO :</td>
                <td><b>{$this->repo->name}</b></td>
            </tr>
            <tr>
                <td>DISTRIBUTION :</td>
                <td><b>{$this->repo->dist}</b></td>
            </tr>
            <tr>
                <td>SECTION :</td>
                <td><b>{$this->repo->section}</b></td>
            </tr>";
        }
        if (!empty($this->repo->description)) {
        echo "<tr>
                <td>DESCRIPTION :</td>
                <td><b>{$this->repo->description}</b></td>
            </tr>";
        }
        echo "<tr>
                <td>VERIF. DES SIGNATURES GPG :</td>
                <td><b>{$this->repo->gpgCheck}</b></td>
            </tr>
            <tr>
                <td>SIGN. DU REPO AVEC GPG :</td>";
        if ($this->repo->signed == "yes" OR $this->repo->gpgResign == "yes") {
            echo '<td><b>yes</b></td>';
        } else {
            echo '<td><b>no</b></td>';
        }
        echo '</tr>';
        if (!empty($this->repo->group)) {
        echo "<tr>
                <td>AJOUT AU GROUPE :</td>
                <td><b>{$this->repo->group}</b></td>
            </tr>";
        }
        echo "</table>";

        $this->logcontent = ob_get_clean();
        file_put_contents($this->log->steplog, $this->logcontent);

        return true;
    }
}
?>