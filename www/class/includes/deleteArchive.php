<?php
trait deleteArchive {
    /**
     *  SUPPRESSION D'UN REPO/SECTION ARCHIVÉ(E)
     */
    public function deleteArchive() {
        global $OS_FAMILY;
        global $REPOS_DIR;

        /**
         *  1. Génération du tableau récapitulatif de l'opération
         */
        echo '<table>';
        if ($OS_FAMILY == "Debian") {
        echo "<tr>
            <td>Section :</td>
            <td><b>$this->section</b></td>
        </tr>";
        }
        echo "<tr>
            <td>Nom du repo :</td>
            <td><b>$this->name</b></td>
        </tr>";
        if ($OS_FAMILY == "Debian") {
        echo "<tr>
            <td>Distribution :</td>
            <td><b>$this->dist</b></td>
        </tr>";
        }
        echo "<tr>
            <td>Date :</td>
            <td><b>$this->dateFormatted</b></td>
        </tr>";
        echo '</table>';

        /**
         *  2. On vérifie que le repo/section archivé(e) existe bien
         */
        if ($OS_FAMILY == "Redhat") {
            if ($this->existsDate($this->name, $this->date, 'archived') === false) {
                echo "<p><span class=\"redtext\">Erreur : </span>le repo $repo->name archivé à la date du $repo->date n'existe pas</p>";
                return;
            }
        }
        if ($OS_FAMILY == "Debian") {
            if ($this->section_existsDate($this->name, $this->dist, $this->section, $this->date, 'archived') === false) {
                echo "<p><span class=\"redtext\">Erreur : </span>la section de repo $repo->section archivée à la date du $repo->date n'existe pas</p>";
                return;
            }
        }

        /**
         *  3. Suppression du repo/section archivé
         */
        if ($OS_FAMILY == "Redhat") {
            if (file_exists("${REPOS_DIR}/archived_{$this->dateFormatted}_{$this->name}")) {
                exec("rm ${REPOS_DIR}/archived_{$this->dateFormatted}_{$this->name} -rf", $output, $result);
            }
        }
        if ($OS_FAMILY == "Debian") {
            if (file_exists("${REPOS_DIR}/{$this->name}/{$this->dist}/archived_{$this->dateFormatted}_{$this->section}")) {
                exec("rm ${REPOS_DIR}/{$this->name}/{$this->dist}/archived_{$this->dateFormatted}_{$this->section} -rf", $output, $result);
            }
        }
        if ($result != 0) {
            echo '<p><span class="redtext">Erreur : </span>lors de la suppression du miroir</p>';
            return;
        }

        /**
         *  4. Mise à jour de la BDD
         */
        if ($OS_FAMILY == "Redhat") {
            $this->db->exec("DELETE FROM repos_archived WHERE Name = '$this->name' AND Date = '$this->date'");
        }
        if ($OS_FAMILY == "Debian") {
            $this->db->exec("DELETE FROM repos_archived WHERE Name = '$this->name' AND Dist = '$this->dist' AND Section = '$this->section' AND Date = '$this->date'");
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