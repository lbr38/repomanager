<?php
trait op_archive {
    /**
     *  Archivage de l'ancien repo/section
     */
    public function op_archive() {
        global $OS_FAMILY;
        global $REPOS_DIR;
        global $DEFAULT_ENV;

        ob_start();

        /**
         *  1. Avant d'archiver un repo/section, il faut vérifier qu'il n'est plus utilisé par aucun environnement
         *  Pour cela on a besoin de récupérer la date du repo/section qui va être potentiellement archivé.
         *  Puis on affiche toutes les occurences de ce repo/section en BDD en filtrant sur la date récupérée et en excluant l'environnement qui vient d'être mis à jour ($this->env)
         *  Si il y a 1 ou plusieurs occurences alors on ne peut pas archiver le repo/section à la date indiquée car il est toujours utilisé
         */
        if ($OS_FAMILY == "Redhat") {
            $resultDate = $this->db->query("SELECT Date FROM repos WHERE Name = '$this->name' AND Env = '$this->env'");
            $resultDescription = $this->db->query("SELECT Description FROM repos WHERE Name = '$this->name' AND Env = '$this->env'");
            $resultType = $this->db->query("SELECT Type FROM repos WHERE Name = '$this->name' AND Env = '$this->env'");
            $resultTime = $this->db->query("SELECT Time FROM repos WHERE Name = '$this->name' AND Env = '$this->env'");
        }
        if ($OS_FAMILY == "Debian") {
            $resultDate = $this->db->query("SELECT Date FROM repos WHERE Name = '$this->name' AND Dist = '$this->dist' AND Section = '$this->section' AND Env = '$this->env'");
            $resultDescription = $this->db->query("SELECT Description FROM repos WHERE Name = '$this->name' AND Dist = '$this->dist' AND Section = '$this->section' AND Env = '$this->env'");
            $resultType = $this->db->query("SELECT Type FROM repos WHERE Name = '$this->name' AND Dist = '$this->dist' AND Section = '$this->section' AND Env = '$this->env'");
            $resultTime = $this->db->query("SELECT Time FROM repos WHERE Name = '$this->name' AND Dist = '$this->dist' AND Section = '$this->section' AND Env = '$this->env'");
        }
        while ($data = $resultDate->fetchArray()) {
            $oldRepoDate = $data['Date'];
        }
        $oldRepoDateFormatted = DateTime::createFromFormat('Y-m-d', $oldRepoDate)->format('d-m-Y');
        if (empty($oldRepoDate) OR empty($oldRepoDateFormatted)) {
            throw new Exception('<p><span class="redtext">Erreur : </span>impossible de récupérer la date de l\'ancien repo</p>');
        }
        // On a également récupéré la description
        while ($data = $resultDescription->fetchArray()) {
            $this->description = $data['Description'];
        }
        // Et le type
        while ($data = $resultType->fetchArray()) {
            $this->type = $data['Type'];
        }
        // Et le time
        while ($data = $resultTime->fetchArray()) {
            $oldRepoTime = $data['Time'];
        }

        /**
         *  A partir de la date récupérée, on regarde si d'autres environnements pointent sur le repo/section à cette date
         *  On exclu $this->env de la recherche car il apparaitra forcémment sinon.
         */
        if ($OS_FAMILY == "Redhat") {
            $count = $this->db->countRows("SELECT * FROM repos WHERE Name = '$this->name' AND Date = '$oldRepoDate' AND Env != '$this->env'");
        }
        if ($OS_FAMILY == "Debian") {
            $count = $this->db->countRows("SELECT * FROM repos WHERE Name = '$this->name' AND Dist = '$this->dist' AND Section = '$this->section' AND Date = '$oldRepoDate' AND Env != '$this->env'");
        }

        /**
         *  Mise à jour de la date en BDD
         */
        if ($OS_FAMILY == "Redhat") {
            $this->db->exec("UPDATE repos SET Date = '$this->date', Time = '$this->time' WHERE Name = '$this->name' AND Env = '$this->env' AND Date = '$oldRepoDate'");
        }
        if ($OS_FAMILY == "Debian") {
            $this->db->exec("UPDATE repos SET Date = '$this->date', Time = '$this->time' WHERE Name = '$this->name' AND Dist = '$this->dist' AND Section = '$this->section' AND Env = '$this->env' AND Date = '$oldRepoDate'");
        }

        /**
         *  Cas où on archive l'ancien repo/section
         */
        if ($count == 0) {
            echo "<br>La version précédente du ${oldRepoDateFormatted} n'est pas utilisée par d'autres environnements (donc elle n'est plus utilisée).";
            echo "<br>Archivage de l'ancienne version : ";
            if ($OS_FAMILY == "Redhat") {
                if (!rename("${REPOS_DIR}/${oldRepoDateFormatted}_{$this->name}", "${REPOS_DIR}/archived_${oldRepoDateFormatted}_{$this->name}")) {
                    throw new Exception('<p><span class=\"redtext\">Erreur : </span>impossible d\'archiver l\'ancienne version</p>');
                }
                /**
                 *  Insertion en BDD du nouveau repo archivé
                 */
                $this->db->exec("INSERT INTO repos_archived (Name, Source, Date, Time, Description, Signed, Type) VALUES ('$this->name', '$this->source', '$oldRepoDate', '$oldRepoTime', '$this->description', '$this->signed', '$this->type')");
            }

            if ($OS_FAMILY == "Debian") {
                if (!rename("${REPOS_DIR}/{$this->name}/{$this->dist}/${oldRepoDateFormatted}_{$this->section}", "${REPOS_DIR}/{$this->name}/{$this->dist}/archived_${oldRepoDateFormatted}_{$this->section}")) {
                    throw new Exception('<p><span class=\"redtext\">Erreur : </span>impossible d\'archiver l\'ancienne version</p>');
                }
                /**
                 *  Insertion en BDD du nouveau repo archivé
                 */
                $this->db->exec("INSERT INTO repos_archived (Name, Source, Dist, Section, Date, Time, Description, Signed, Type) VALUES ('$this->name', '$this->source', '$this->dist', '$this->section', '$oldRepoDate', '$oldRepoTime', '$this->description', '$this->signed', '$this->type')");
            }
            echo '<span class="greentext">OK</span>';
        }
        /**
         *  Cas où on n'archive pas : on ne fait rien
         */
        
        $this->logcontent = ob_get_clean(); file_put_contents($this->log->steplog, $this->logcontent, FILE_APPEND); ob_start();

        return true;
    }
}
?>