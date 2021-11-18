<?php
trait deleteDist {
    /**
     *  SUPPRESSION D'UNE DISTRIBUTION
     */
    public function exec_deleteDist() {
        global $REPOS_DIR;

        ob_start();

        /**
         *  1. Génération du tableau récapitulatif de l'opération
         */
        echo "<h3>SUPPRESSION D'UNE DISTRIBUTION</h3>";

        echo "<table class=\"op-table\">
        <tr>
            <th>NOM DU REPO :</th>
            <td><b>{$this->repo->name}</b></td>
        </tr>
        <tr>
            <th>DISTRIBUTION :</th>
            <td><b>{$this->repo->dist}</b></td>
        </tr>
        </table>";

        $this->log->steplog(1);
        $this->log->steplogInitialize('deleteDist');
        $this->log->steplogTitle('SUPPRESSION');
        $this->log->steplogLoading();

        /**
         *  2. On vérifie que la distribution renseignée existe bien
         */
        if ($this->repo->dist_exists($this->repo->name, $this->repo->dist) === false) throw new Exception("la distribution <b>{$this->repo->dist}</b> du repo <b>{$this->repo->name}</b> n'existe pas");

        /**
         *  3. Suppression du répertoire de la distribution
         */
        exec("rm ${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist} -rf", $output, $result);
        if ($result != 0) throw new Exception('impossible de supprimer le répertoire de la distribution');

        /**
         *  4. On supprime le répertoire parent (repo) si celui-ci est vide après la suppression de la distribution
         */
        $checkIfDirIsEmpty = exec("ls -A ${REPOS_DIR}/{$this->repo->name}/");
        if (empty($checkIfDirIsEmpty)) {
            exec("rm ${REPOS_DIR}/{$this->repo->name}/ -rf");
        }

        /**
         *  5. Mise à jour en BDD
         *  La suppression d'une distribution entière entraine la suppression des sections archivées si il y en a, donc on met aussi à jour repos_archived
         */
        $stmt =  $this->repo->db->prepare("UPDATE repos SET Status = 'deleted' WHERE Name=:name AND Dist=:dist AND Status = 'active'");
        $stmt2 = $this->repo->db->prepare("UPDATE repos_archived SET Status = 'deleted' WHERE Name=:name AND Dist=:dist AND Status = 'active'");
        $stmt->bindValue(':name', $this->repo->name);
        $stmt->bindValue(':dist', $this->repo->dist);
        $stmt2->bindValue(':name', $this->repo->name);
        $stmt2->bindValue(':dist', $this->repo->dist);
        $stmt->execute();
        $stmt2->execute();
        unset($stmt, $stmt2);
        
        /**
         *  6. Supprime les sections des groupes où elles apparaissent
         */
        $group = new Group();
        $group->cleanRepos();

        $this->log->steplogOK();
    }
}
?>