<?php
trait op_archive {
    /**
     *  Archivage de l'ancien repo/section
     */
    public function op_archive() {
        global $OS_FAMILY;
        global $REPOS_DIR;
        global $DATE_YMD;

        ob_start();

        /**
         *  1. Avant d'archiver un repo/section, il faut vérifier qu'il n'est plus utilisé par aucun environnement
         *  Pour cela on a besoin de récupérer la date du repo/section qui va être potentiellement archivé.
         *  Puis on affiche toutes les occurences de ce repo/section en BDD en filtrant sur la date récupérée et en excluant l'environnement qui vient d'être mis à jour ($this->env)
         *  Si il y a 1 ou plusieurs occurences alors on ne peut pas archiver le repo/section à la date indiquée car il est toujours utilisé
         */
        if ($OS_FAMILY == "Redhat") {
            $resultDate = $this->db->query("SELECT Date FROM repos WHERE Name = '{$this->repo->name}' AND Env = '{$this->repo->env}' AND Status = 'active'");
            $resultDescription = $this->db->query("SELECT Description FROM repos WHERE Name = '{$this->repo->name}' AND Env = '{$this->repo->env}' AND Status = 'active'");
            $resultType = $this->db->query("SELECT Type FROM repos WHERE Name = '{$this->repo->name}' AND Env = '{$this->repo->env}' AND Status = 'active'");
            $resultTime = $this->db->query("SELECT Time FROM repos WHERE Name = '{$this->repo->name}' AND Env = '{$this->repo->env}' AND Status = 'active'");
        }
        if ($OS_FAMILY == "Debian") {
            $resultDate = $this->db->query("SELECT Date FROM repos WHERE Name = '{$this->repo->name}' AND Dist = '{$this->repo->dist}' AND Section = '{$this->repo->section}' AND Env = '{$this->repo->env}' AND Status = 'active'");
            $resultDescription = $this->db->query("SELECT Description FROM repos WHERE Name = '{$this->repo->name}' AND Dist = '{$this->repo->dist}' AND Section = '{$this->repo->section}' AND Env = '{$this->repo->env}' AND Status = 'active'");
            $resultType = $this->db->query("SELECT Type FROM repos WHERE Name = '{$this->repo->name}' AND Dist = '{$this->repo->dist}' AND Section = '{$this->repo->section}' AND Env = '{$this->repo->env}' AND Status = 'active'");
            $resultTime = $this->db->query("SELECT Time FROM repos WHERE Name = '{$this->repo->name}' AND Dist = '{$this->repo->dist}' AND Section = '{$this->repo->section}' AND Env = '{$this->repo->env}' AND Status = 'active'");
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
            $this->repo->description = $data['Description'];
        }
        // Et le type
        while ($data = $resultType->fetchArray()) {
            $this->repo->type = $data['Type'];
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
            $count = $this->db->countRows("SELECT * FROM repos WHERE Name = '{$this->repo->name}' AND Date = '$oldRepoDate' AND Env != '{$this->repo->env}' AND Status = 'active'");
        }
        if ($OS_FAMILY == "Debian") {
            $count = $this->db->countRows("SELECT * FROM repos WHERE Name = '{$this->repo->name}' AND Dist = '{$this->repo->dist}' AND Section = '{$this->repo->section}' AND Date = '$oldRepoDate' AND Env != '{$this->repo->env}' AND Status = 'active'");
        }

        /**
         *  Mise à jour de la date en BDD du repo qu'on vient de mettre à jour
         */
        if ($OS_FAMILY == "Redhat") {
            $this->db->exec("UPDATE repos SET Date = '$DATE_YMD', Time = '{$this->repo->time}' WHERE Name = '{$this->repo->name}' AND Env = '{$this->repo->env}' AND Date = '$oldRepoDate'");
        }
        if ($OS_FAMILY == "Debian") {
            $this->db->exec("UPDATE repos SET Date = '$DATE_YMD', Time = '{$this->repo->time}' WHERE Name = '{$this->repo->name}' AND Dist = '{$this->repo->dist}' AND Section = '{$this->repo->section}' AND Env = '{$this->repo->env}' AND Date = '$oldRepoDate'");
        }

        /**
         *  Cas où on archive l'ancien repo/section
         */
        if ($count == 0) {
            echo "<br><br>La version précédente du <b>${oldRepoDateFormatted}</b> n'est pas utilisée par d'autres environnements (donc elle n'est plus utilisée).";
            echo "<br>Archivage de l'ancienne version : ";
            $this->logcontent = ob_get_clean(); file_put_contents($this->log->steplog, $this->logcontent, FILE_APPEND); ob_start();

            if ($OS_FAMILY == "Redhat") {
                /**
                 *  Si un répertoire d'archive existe déjà alors on le supprime
                 */
                if (is_dir("${REPOS_DIR}/archived_${oldRepoDateFormatted}_{$this->repo->name}")) {
                    exec("rm -rf '${REPOS_DIR}/archived_${oldRepoDateFormatted}_{$this->repo->name}'");
                }

                if (!rename("${REPOS_DIR}/${oldRepoDateFormatted}_{$this->repo->name}", "${REPOS_DIR}/archived_${oldRepoDateFormatted}_{$this->repo->name}")) {
                    throw new Exception('<p><span class=\"redtext\">Erreur : </span>impossible d\'archiver l\'ancienne version</p>');
                }
                /**
                 *  Insertion en BDD du nouveau repo archivé
                 */
                $this->db->exec("INSERT INTO repos_archived (Name, Source, Date, Time, Description, Signed, Type, Status) VALUES ('{$this->repo->name}', '{$this->repo->source}', '$oldRepoDate', '$oldRepoTime', '{$this->repo->description}', '{$this->repo->signed}', '{$this->repo->type}', 'active')");
            }

            if ($OS_FAMILY == "Debian") {
                /**
                 *  Si un répertoire d'archive existe déjà alors on le supprime
                 */
                if (is_dir("${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/archived_${oldRepoDateFormatted}_{$this->repo->section}")) {
                    exec("rm -rf '${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/archived_${oldRepoDateFormatted}_{$this->repo->section}'");
                }

                if (!rename("${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/${oldRepoDateFormatted}_{$this->repo->section}", "${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/archived_${oldRepoDateFormatted}_{$this->repo->section}")) {
                    throw new Exception('<p><span class=\"redtext\">Erreur : </span>impossible d\'archiver l\'ancienne version</p>');
                }
                /**
                 *  Insertion en BDD du nouveau repo archivé
                 */
                $this->db->exec("INSERT INTO repos_archived (Name, Source, Dist, Section, Date, Time, Description, Signed, Type, Status) VALUES ('{$this->repo->name}', '{$this->repo->source}', '{$this->repo->dist}', '{$this->repo->section}', '$oldRepoDate', '$oldRepoTime', '{$this->repo->description}', '{$this->repo->signed}', '{$this->repo->type}', 'active')");
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