<?php
trait deleteArchive {
    /**
     *  SUPPRESSION D'UN REPO/SECTION ARCHIVÉ(E)
     */
    public function exec_deleteArchive() {

        

        ob_start();

        /**
         *  1. Génération du tableau récapitulatif de l'opération
         */
        if (OS_FAMILY == "Redhat") echo '<h3>SUPPRIMER UN REPO ARCHIVÉ</h3>';
        if (OS_FAMILY == "Debian") echo '<h3>SUPPRIMER UNE SECTION ARCHIVÉE</h3>';

        echo "<table class=\"op-table\">
        <tr>
            <th>NOM DU REPO :</th>
            <td><b>{$this->repo->name}</b></td>
        </tr>";
        if (OS_FAMILY == "Debian") {
            echo "<tr>
                <th>DISTRIBUTION :</th>
                <td><b>{$this->repo->dist}</b></td>
            </tr>
            <tr>
                <th>SECTION :</th>
                <td><b>{$this->repo->section}</b></td>
            </tr>";
        }
        echo "<tr>
            <th>DATE :</th>
            <td><b>{$this->repo->dateFormatted}</b></td>
        </tr>";
        echo '</table>';

        $this->log->steplog(1);
        $this->log->steplogInitialize('deleteArchive');
        $this->log->steplogTitle('SUPPRESSION');
        $this->log->steplogLoading();

        /**
         *  2. On vérifie que le repo/section archivé(e) existe bien
         */
        if (OS_FAMILY == "Redhat") {
            if ($this->repo->existsDate($this->repo->name, $this->repo->date, 'archived') === false) throw new Exception("le repo {$this->repo->name} archivé à la date du {$this->repo->date} n'existe pas");
        }
        if (OS_FAMILY == "Debian") {
            if ($this->repo->section_existsDate($this->repo->name, $this->repo->dist, $this->repo->section, $this->repo->date, 'archived') === false) throw new Exception("la section de repo {$this->repo->section} archivée à la date du {$this->repo->date} n'existe pas");
        }

        /**
         *  3. Suppression du repo/section archivé
         */
        if (OS_FAMILY == "Redhat") {
            if (is_dir(REPOS_DIR."/archived_{$this->repo->dateFormatted}_{$this->repo->name}")) {
                exec("rm ".REPOS_DIR."/archived_{$this->repo->dateFormatted}_{$this->repo->name} -rf", $output, $result);
            }
        }
        if (OS_FAMILY == "Debian") {
            if (is_dir(REPOS_DIR."/{$this->repo->name}/{$this->repo->dist}/archived_{$this->repo->dateFormatted}_{$this->repo->section}")) {
                exec("rm ".REPOS_DIR."/{$this->repo->name}/{$this->repo->dist}/archived_{$this->repo->dateFormatted}_{$this->repo->section} -rf", $output, $result);
            }
        }
        
        if (!empty($result) AND $result != 0) throw new Exception('impossible de supprimer le miroir');

        /**
         *  4. Mise à jour de la BDD
         */
        try {
            $stmt = $this->repo->db->prepare("UPDATE repos_archived SET Status = 'deleted' WHERE Id=:id AND Status = 'active'");
            $stmt->bindValue(':id', $this->repo->id);
            if (OS_FAMILY == "Debian") {
                $stmt->bindValue(':dist', $this->repo->dist);
                $stmt->bindValue(':section', $this->repo->section);
            }
            $stmt->bindValue(':date', $this->repo->date);
            $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

        $this->log->steplogOK();
    }
}
?>