<?php
trait deleteDist {
    /**
     *  SUPPRESSION D'UNE DISTRIBUTION
     */
    public function deleteDist() {
        global $REPOS_DIR;

        /**
         *  1. Génération du tableau récapitulatif de l'opération
         */
        echo "<table>
        <tr>
            <td>Nom du repo :</td>
            <td><b>$this->name</b></td>
        </tr>
        <tr>
            <td>Distribution :</td>
            <td><b>$this->dist</b></td>
        </tr>
        </table>";

        /**
         *  2. On vérifie que la distribution renseignée existe bien
         */
        if ($this->dist_exists($this->name, $this->dist) === false) {
            echo "<tr><td colspan=\"100%\"><br><span class=\"redtext\">Erreur : </span>la distribution <b>$this->dist</b> du repo <b>$this->name</b> n'existe pas</td></tr>";
            return;
        }

        /**
         *  3. Suppression du répertoire de la distribution
         */
        exec("rm ${REPOS_DIR}/{$this->name}/{$this->dist} -rf", $output, $result);
        if ($result != 0) {
            echo "<tr><td colspan=\"100%\"><br><span class=\"redtext\">Erreur : </span>impossible de supprimer le répertoire de la distribution</td></tr>";
            return;
        }

        /**
         *  4. On supprime le répertoire parent (repo) si celui-ci est vide après la suppression de la distribution
         */
        $checkIfDirIsEmpty = exec("ls -A ${REPOS_DIR}/{$this->name}/");
        if (empty($checkIfDirIsEmpty)) {
            exec("rm ${REPOS_DIR}/{$this->name}/ -rf");
        }

        /**
         *  5. Mise à jour en BDD
         */
        $this->db->exec("DELETE FROM repos WHERE Name = '$this->name' AND Dist = '$this->dist'");
        
        /**
         *  6. Supprime les sections des groupes où elles apparaissent
         */
        $group = new Group();
        $group->clean();

        echo '<p>Supprimée <span class="greentext">✔</span></p>';
    }
}
?>