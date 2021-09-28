<?php
trait op_printDetails {
    /**
    *   Génération d'un tableau récapitulatif de l'opération
    *   Valide pour : 
    *    - un nouveau repo/section 
    *    - une mise à jour de repo/section
    *    - une reconstruction des métadonnées d'un repo/section
    */
    public function op_printDetails() {
        global $OS_FAMILY;

        ob_start();

        /**
         *  Affichage du tableau récapitulatif de l'opération
         */
        echo '<table class="op-table">';
        if (!empty($this->repo->source)) echo "<tr><th>REPO SOURCE :</th><td><b>{$this->repo->source}</b></td></tr>";
        if (!empty($this->repo->source) AND !empty($this->repo->sourceFullUrl)) echo "<tr><th>REPO SOURCE :</th><td><b>{$this->repo->source} ({$this->repo->sourceFullUrl})</b></td></tr>";
        if (!empty($this->repo->name)) echo "<tr><th>NOM DU REPO :</th><td><b>{$this->repo->name}</b></td></tr>";
        if (!empty($this->repo->dist)) echo "<tr><th>DISTRIBUTION :</th><td><b>{$this->repo->dist}</b></td></tr>";
        if (!empty($this->repo->section)) echo "<tr><th>SECTION :</th><td><b>{$this->repo->section}</b></td></tr>";
        if (!empty($this->repo->description)) echo "<tr><th>DESCRIPTION :</th><td><b>{$this->repo->description}</b></td></tr>";
        if (!empty($this->repo->gpgCheck)) echo "<tr><th>VERIF. DES SIGNATURES GPG :</th><td><b>{$this->repo->gpgCheck}</b></td></tr>";
        if (!empty($this->repo->signed) OR !empty($this->repo->gpgResign)) {
            echo "<tr><th>SIGN. DU REPO AVEC GPG :</th>";
            if ($this->repo->signed == "yes" OR $this->repo->gpgResign == "yes") echo '<td><b>yes</b></td>';
            else echo '<td><b>no</b></td>';
            echo '</tr>';
        }
        if (!empty($this->repo->group)) echo "<tr><th>AJOUT AU GROUPE :</th><td><b>{$this->repo->group}</b></td></tr>";
        echo "</table>";

        $this->logcontent = ob_get_clean();
        file_put_contents($this->log->steplog, $this->logcontent);

        return true;
    }
}
?>