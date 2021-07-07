<?php
trait delete {
    /**
     *  SUPPRESSION D'UN REPO
     */
    public function delete() {
        global $OS_FAMILY;
        global $REPOS_DIR;
        global $WWW_DIR;

        /**
         *  0. Si Redhat on aura besoin de la date du repo à supprimer
         */
        if ($OS_FAMILY == "Redhat") {
            $this->db_getDate();
        }

        /**
         *  1. Génération du tableau récapitulatif de l'opération
         */
        echo "<table>
        <tr>
            <td>Nom du repo :</td>
            <td><b>$this->name</b></td>
        </tr>
        </table>";

        /**
         *  2. On vérifie que le repo renseigné existe bien
         */

        if ($this->exists($this->name) == false) {
            echo "<p><span class=\"redtext\">Erreur : </span>le repo <b>$this->name</b> n'existe pas</p>";
        }

        /**
         *  3. Suppression du repo
         *   Si Redhat : Suppression du lien symbolique du repo
         *   Si Debian : Suppression du répertoire du repo
         */
        if ($OS_FAMILY == "Redhat") {
            if (!unlink("${REPOS_DIR}/{$this->name}_{$this->env}"))  {
                echo '<p><span class="redtext">Erreur : </span>problème lors de la suppression du repo</p>';
                return;
            }
        }
        if ($OS_FAMILY == "Debian") {
            exec("rm ${REPOS_DIR}/{$this->name} -rf", $output, $result);
            if ($result != 0) {
                echo '<p><span class="redtext">Erreur : </span>impossible de supprimer le répertoire du repo</p>';
                return;
            }
        }

        /**
         *  4. Mise à jour de la BDD
         */
        if ($OS_FAMILY == "Redhat") {
            $this->db->exec("DELETE from repos WHERE Name = '$this->name' AND Env = '$this->env' AND Date = '$this->date'");
        }
        if ($OS_FAMILY == "Debian") {
            $this->db->exec("DELETE from repos WHERE Name = '$this->name'");
        }

        /**
         *  5. Redhat : Si il n'y a plus de trace du repo en BDD alors on peut supprimer son miroir définitivement
         *  Ainsi que son fichier de conf .repo
         */
        if ($OS_FAMILY == "Redhat") {
            if ($this->exists($this->name) === false) {
                exec("rm ${REPOS_DIR}/{$this->dateFormatted}_{$this->name}/ -rf", $output, $result);
                if ($result != 0) {
                    echo '<p><span class="redtext">Erreur : </span>impossible de supprimer le répertoire du repo</p>';
                    return;
                }

                // Suppression du fichier de conf repo en local (ces fichiers sont utilisés pour les profils)
                $this->deleteConf();
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