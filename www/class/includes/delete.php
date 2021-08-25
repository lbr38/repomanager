<?php
trait delete {
    /**
     *  SUPPRESSION D'UN REPO
     */
    public function exec_delete() {
        global $OS_FAMILY;
        global $REPOS_DIR;
        global $WWW_DIR;

        /**
         *  0. Si Redhat on aura besoin de la date du repo à supprimer
         */
        if ($OS_FAMILY == "Redhat") {
            $this->repo->db_getDate();
        }

        /**
         *  1. Génération du tableau récapitulatif de l'opération
         */
        echo "<table>
        <tr>
            <td>Nom du repo :</td>
            <td><b>{$this->repo->name}</b></td>
        </tr>
        </table>";

        /**
         *  2. On vérifie que le repo renseigné existe bien
         */

        if ($this->repo->exists($this->repo->name) == false) {
            echo "<p><span class=\"redtext\">Erreur : </span>le repo <b>{$this->repo->name}</b> n'existe pas</p>";
        }

        /**
         *  3. Suppression du repo
         *   Si Redhat : Suppression du lien symbolique du repo
         *   Si Debian : Suppression du répertoire du repo
         */
        if ($OS_FAMILY == "Redhat") {
            if (!unlink("${REPOS_DIR}/{$this->repo->name}_{$this->repo->env}"))  {
                echo '<p><span class="redtext">Erreur : </span>problème lors de la suppression du repo</p>';
                return false;
            }
        }
        if ($OS_FAMILY == "Debian") {
            exec("rm ${REPOS_DIR}/{$this->repo->name} -rf", $output, $result);
            if ($result != 0) {
                echo '<p><span class="redtext">Erreur : </span>impossible de supprimer le répertoire du repo</p>';
                return false;
            }
        }

        /**
         *  4. Mise à jour de la BDD
         */
        if ($OS_FAMILY == "Redhat") {
            $this->repo->db->exec("UPDATE repos SET status = 'deleted' WHERE Name = '{$this->repo->name}' AND Env = '{$this->repo->env}' AND Date = '{$this->repo->date}' AND Status = 'active'");
        }
        if ($OS_FAMILY == "Debian") {
            $this->repo->db->exec("UPDATE repos SET status = 'deleted' WHERE Name = '{$this->repo->name}' AND Status = 'active'");
        }

        /**
         *  5. Redhat : Si il n'y a plus de trace du repo en BDD alors on peut supprimer son miroir définitivement
         *  Ainsi que son fichier de conf .repo
         */
        if ($OS_FAMILY == "Redhat") {
            if ($this->repo->exists($this->repo->name) === false) {
                exec("rm ${REPOS_DIR}/{$this->repo->dateFormatted}_{$this->repo->name}/ -rf", $output, $result);
                if ($result != 0) {
                    echo '<p><span class="redtext">Erreur : </span>impossible de supprimer le répertoire du repo</p>';
                    return false;
                }

                // Suppression du fichier de conf repo en local (ces fichiers sont utilisés pour les profils)
                $this->repo->deleteConf();
            }
        }

        /**
         *  6. Supprime le repo/les sections des groupes où il apparait
         */
        $group = new Group();
        $group->clean();

        echo '<p>Supprimé <span class="greentext">✔</span></p>';
    }
}
?>