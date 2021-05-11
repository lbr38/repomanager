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
        global $TEMP_DIR;

        ob_start();

        // Affichage du récapitulatif de l'opération
        echo '<table>';
        if ($OS_FAMILY == "Redhat") {
            echo "<tr>
                <td>Repo source :</td>
                <td><b>$this->source</b></td>
            </tr>
            <tr>
                <td>Nom du repo :</td>
                <td><b>$this->name</b></td>
            </tr>";
        }
        if ($OS_FAMILY == "Debian") {
            echo "<tr>
                <td>Hôte source :</td>
                <td><b>$this->source ({$this->sourceFullUrl})</b></td>
            </tr>
            <tr>
                <td>Nom du repo :</td>
                <td><b>$this->name</b></td>
            </tr>
            <tr>
                <td>Distribution :</td>
                <td><b>$this->dist</b></td>
            </tr>
            <tr>
                <td>Section :</td>
                <td><b>$this->section</b></td>
            </tr>";
        }
        if (!empty($this->description)) {
        echo "<tr>
                <td>Description :</td>
                <td><b>$this->description</b></td>
            </tr>";
        }
        echo "<tr>
                <td>Vérification des signatures GPG :</td>
                <td><b>$this->gpgCheck</b></td>
            </tr>
            <tr>
                <td>Signature du repo :</td>
                <td><b>$this->signed</b></td>
            </tr>";
        if (!empty($this->group)) {
        echo "<tr>
                <td>Ajout à un groupe :</td>
                <td><b>$this->group</b></td>
            </tr>";
        }
        echo "</table>";

        $this->logcontent = ob_get_clean();
        file_put_contents($this->log->steplog, $this->logcontent);

        return true;
    }
}
?>