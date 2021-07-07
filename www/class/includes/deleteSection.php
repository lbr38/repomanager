<?php
trait deleteSection {
    /**
     *  SUPPRESSION D'UNE SECTION
     */
    public function exec_deleteSection() {
        global $REPOS_DIR;
        global $WWW_DIR;

        /**
         *  1. Génération du tableau récapitulatif de l'opération
         */
        echo "<table>
        <tr>
            <td>Section :</td>
            <td><b>{$this->repo->section}</b></td>
        </tr>
        <tr>
            <td>Environnement :</td>
            <td><b>{$this->repo->env}</b></td>
        </tr>
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
         *  2. On vérifie que la section renseignée existe bien
         */
        if ($this->repo->section_exists($this->repo->name, $this->repo->dist, $this->repo->section) === false) {
            echo '<p><span class="redtext">Erreur : </span>cette section n\'existe pas</p>';
            return false;
        }

        /**
         *  3. Récupération de la date de la section
         */
        $this->repo->db_getDate();

        /**
         *  4. Suppression du lien symbolique
         */
        if (file_exists("${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/{$this->repo->section}_{$this->repo->env}")) {
            if (!unlink("${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/{$this->repo->section}_{$this->repo->env}")) {
                echo '<p><span class="redtext">Erreur : </span>impossible de supprimer le lien symbolique de la section</p>';
                return false;
            }
        }

        /**
         *  5. On met à jour la BDD
         */
        $this->repo->db->exec("UPDATE repos SET Status = 'deleted' WHERE Name = '{$this->repo->name}' AND Dist = '{$this->repo->dist}' AND Section = '{$this->repo->section}' AND Env = '{$this->repo->env}' AND Date = '{$this->repo->date}' AND Status = 'active'");

        /**
         *  6. Vérifications avant suppression définitive du miroir
         */
        $result = $this->repo->db->countRows("SELECT * from repos WHERE Name = '{$this->repo->name}' AND Dist = '{$this->repo->dist}' AND Section = '{$this->repo->section}' AND Date = '{$this->repo->date}' AND Status = 'active'");
        if ($result != 0) {
            echo "<p>La version du miroir de cette section est toujours utilisée pour d'autres environnements. Le miroir du {$this->repo->dateFormatted} n'est donc pas supprimé.</p>";
        } else {
            // Suppression du miroir puisque'il n'est plus utilisé par aucun environnement
            exec("rm ${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/{$this->repo->dateFormatted}_{$this->repo->section} -rf", $output, $result);
            if ($result != 0) {
                echo '<p><span class="redtext">Erreur : </span>impossible de supprimer le miroir</p>';
                return false;
            }
        }

        /**
         *  7. Si il n'y a plus du tout de trace de la section en BDD, alors on peut supprimer son fichier de conf .list
         */
        if ($this->repo->section_exists($this->repo->name, $this->repo->dist, $this->repo->section) === false) {
            // Suppression du fichier de conf repo en local (ces fichiers sont utilisés pour les profils)
            $this->repo->deleteConf();
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