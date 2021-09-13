<?php
trait deleteDist {
    /**
     *  SUPPRESSION D'UNE DISTRIBUTION
     */
    public function exec_deleteDist() {
        global $REPOS_DIR;

        /**
         *  1. Génération du tableau récapitulatif de l'opération
         */
        echo "<table>
        <tr>
            <td>Nom du repo :</td>
            <td><b>{$this->repo->name}</b></td>
        </tr>
        <tr>
            <td>Distribution :</td>
            <td><b>{$this->repo->dist}</b></td>
        </tr>
        </table>";

        /**
         *  2. On vérifie que la distribution renseignée existe bien
         */
        if ($this->repo->dist_exists($this->repo->name, $this->repo->dist) === false) {
            echo "<p><span class=\"redtext\">Erreur : </span>la distribution <b>{$this->repo->dist}</b> du repo <b>{$this->repo->name}</b> n'existe pas</p>";
            return false;
        }

        /**
         *  3. Suppression du répertoire de la distribution
         */
        exec("rm ${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist} -rf", $output, $result);
        if ($result != 0) {
            echo '<p><span class="redtext">Erreur : </span>impossible de supprimer le répertoire de la distribution</p>';
            return false;
        }

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
        //$this->repo->db->exec("UPDATE repos SET Status = 'deleted' WHERE Name = '{$this->repo->name}' AND Dist = '{$this->repo->dist}' AND Status = 'active'");
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
        $group->clean();

        echo '<p>Supprimée <span class="greentext">✔</span></p>';
    }
}
?>