<?php
trait deleteSection {
    /**
     *  SUPPRESSION D'UNE SECTION
     */
    public function deleteSection() {
        global $REPOS_DIR;
        global $WWW_DIR;

        /**
         *  1. Génération du tableau récapitulatif de l'opération
         */
        echo "<table>
        <tr>
            <td>Section :</td>
            <td><b>$this->section</b></td>
        </tr>
        <tr>
            <td>Environnement :</td>
            <td><b>$this->env</b></td>
        </tr>
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
         *  2. On vérifie que la section renseignée existe bien
         */
        if ($this->section_exists($this->name, $this->dist, $this->section) === false) {
            echo '<p><span class="redtext">Erreur : </span>cette section n\'existe pas</p>';
            return;
        }

        /**
         *  3. Récupération de la date de la section
         */
        $this->db_getDate();

        /**
         *  4. Suppression du lien symbolique
         */
        if (file_exists("${REPOS_DIR}/{$this->name}/{$this->dist}/{$this->section}_{$this->env}")) {
            if (!unlink("${REPOS_DIR}/{$this->name}/{$this->dist}/{$this->section}_{$this->env}")) {
                echo '<p><span class="redtext">Erreur : </span>impossible de supprimer le lien symbolique de la section</p>';
                return;
            }
        }

        /**
         *  5. On met à jour la BDD
         */
        $this->db->exec("DELETE FROM repos WHERE Name = '$this->name' AND Dist = '$this->dist' AND Section = '$this->section' AND Env = '$this->env' AND Date = '$this->date'");

        /**
         *  6. Vérifications avant suppression définitive du miroir
         */
        $result = $this->db->countRows("SELECT * from repos WHERE Name = '$this->name' AND Dist = '$this->dist' AND Section = '$this->section' AND Date = '$this->date'");
        if ($result != 0) {
            echo "<p>La version du miroir de cette section est toujours utilisée pour d'autres environnements. Le miroir du $this->dateFormatted n'est donc pas supprimé</p>";
        } else {
            // Suppression du miroir puisque'il n'est plus utilisé par aucun environnement
            exec("rm ${REPOS_DIR}/{$this->name}/{$this->dist}/{$this->dateFormatted}_{$this->section} -rf", $output, $result);
            if ($result != 0) {
                echo '<p><span class="redtext">Erreur : </span>impossible de supprimer le miroir</p>';
                return;
            }
        }

        /**
         *  7. Si il n'y a plus du tout de trace de la section en BDD, alors on peut supprimer son fichier de conf .list
         */
        if ($this->section_exists($this->name, $this->dist, $this->section) === false) {
            // Suppression du fichier de conf repo en local (ces fichiers sont utilisés pour les profils)
            $this->deleteConf();
        }

        /**
         *  6. Supprime la section des groupes où elle apparait
         */
        $group = new Group();
        $group->clean();

        echo '<p>Supprimée <span class="greentext">✔</span><p>';
    }
}
?>