<?php
trait deleteArchive {
    /**
     *  SUPPRESSION D'UN REPO/SECTION ARCHIVÉ(E)
     */
    public function exec_deleteArchive() {
        global $OS_FAMILY;
        global $REPOS_DIR;

        /**
         *  1. Génération du tableau récapitulatif de l'opération
         */
        echo '<table>';
        if ($OS_FAMILY == "Debian") {
        echo "<tr>
            <td>Section :</td>
            <td><b>{$this->repo->section}</b></td>
        </tr>";
        }
        echo "<tr>
            <td>Nom du repo :</td>
            <td><b>{$this->repo->name}</b></td>
        </tr>";
        if ($OS_FAMILY == "Debian") {
        echo "<tr>
            <td>Distribution :</td>
            <td><b>{$this->repo->dist}</b></td>
        </tr>";
        }
        echo "<tr>
            <td>Date :</td>
            <td><b>{$this->repo->dateFormatted}</b></td>
        </tr>";
        echo '</table>';

        /**
         *  2. On vérifie que le repo/section archivé(e) existe bien
         */
        if ($OS_FAMILY == "Redhat") {
            if ($this->repo->existsDate($this->repo->name, $this->repo->date, 'archived') === false) {
                echo "<p><span class=\"redtext\">Erreur : </span>le repo {$this->repo->name} archivé à la date du {$this->repo->date} n'existe pas</p>";
                return false;
            }
        }
        if ($OS_FAMILY == "Debian") {
            if ($this->repo->section_existsDate($this->repo->name, $this->repo->dist, $this->repo->section, $this->repo->date, 'archived') === false) {
                echo "<p><span class=\"redtext\">Erreur : </span>la section de repo {$this->repo->section} archivée à la date du {$this->repo->date} n'existe pas</p>";
                return false;
            }
        }

        /**
         *  3. Suppression du repo/section archivé
         */
        if ($OS_FAMILY == "Redhat") {
            if (is_dir("${REPOS_DIR}/archived_{$this->repo->dateFormatted}_{$this->repo->name}")) {
                exec("rm ${REPOS_DIR}/archived_{$this->repo->dateFormatted}_{$this->repo->name} -rf", $output, $result);
            }
        }
        if ($OS_FAMILY == "Debian") {
            if (is_dir("${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/archived_{$this->repo->dateFormatted}_{$this->repo->section}")) {
                exec("rm ${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/archived_{$this->repo->dateFormatted}_{$this->repo->section} -rf", $output, $result);
            }
        }
        if (!empty($result) AND $result != 0) {
            echo '<p><span class="redtext">Erreur : </span>lors de la suppression du miroir</p>';
            return false;
        }

        /**
         *  4. Mise à jour de la BDD
         */
        if ($OS_FAMILY == "Redhat") {
            $this->repo->db->exec("UPDATE repos_archived SET Status = 'deleted' WHERE Name = '{$this->repo->name}' AND Date = '{$this->repo->date}' AND Status = 'active'");
        }
        if ($OS_FAMILY == "Debian") {
            $this->repo->db->exec("UPDATE repos_archived SET Status = 'deleted' WHERE Name = '{$this->repo->name}' AND Dist = '{$this->repo->dist}' AND Section = '{$this->repo->section}' AND Date = '{$this->repo->date}' AND Status = 'active'");
        }

        if ($OS_FAMILY == "Redhat") {
            echo '<p>Supprimé <span class="greentext">✔</span></p>';
        }
        if ($OS_FAMILY == "Debian") {
            echo '<p>Supprimée <span class="greentext">✔</span></p>';
        }
    }
}
?>