<?php
trait restore {
    /**
     *  RESTAURER UN(E) REPO/SECTION ARCHIVÉ(E)
     */
    public function restore() {
        global $OS_FAMILY;
        global $REPOS_DIR;
        global $WWW_DIR;

        /**
         *  1. Génération du tableau récapitulatif de l'opération
         */
        echo "<table>
        <tr>
            <td>Nom du repo :</td>
            <td><b>$this->name</b></td>
        </tr>";
        if ($OS_FAMILY == "Debian") {
        echo "<tr>
            <td>Distribution :</td>
            <td><b>$this->dist</b></td>
        </tr>
        <tr>
            <td>Section :</td>
            <td><b>$this->section</b></td>
        </tr>";
        }
        echo "<tr>
        <td>Environnement cible :</td>
        <td><b>$this->env</b></td>
        </tr>";
        if (!empty($this->description)) {
          echo "<tr>
          <td>Description :</td>
          <td><b>{$this->description}</b></td>
          </tr>";
        }
        echo "</table>";

        /**
         *  1. On récupère la source, le type et la signature du repo/section archivé(e) qui va être restauré(e)
         */
        if ($OS_FAMILY == "Redhat") {
            $resultSource = $this->db->querySingleRow("SELECT Source FROM repos_archived WHERE Name = '$this->name' AND Date = '$this->date'");
            $resultSigned = $this->db->querySingleRow("SELECT Signed FROM repos_archived WHERE Name = '$this->name' AND Date = '$this->date'");
            $resultType = $this->db->querySingleRow("SELECT Type FROM repos_archived WHERE Name = '$this->name' AND Date = '$this->date'");
        }
        if ($OS_FAMILY == "Debian") {
            $resultSource = $this->db->querySingleRow("SELECT Source FROM repos_archived WHERE Name = '$this->name' AND Dist = '$this->dist' AND Section = '$this->section' AND Date = '$this->date'");
            $resultSigned = $this->db->querySingleRow("SELECT Signed FROM repos_archived WHERE Name = '$this->name' AND Date = '$this->date'");
            $resultType = $this->db->querySingleRow("SELECT Type FROM repos_archived WHERE Name = '$this->name' AND Date = '$this->date'");
        }
        $this->source = $resultSource['Source'];
        $this->signed = $resultSigned['Signed'];
        $this->type = $resultType['Type'];

        /**
         *  2. On vérifie que le repo renseigné est bien présent dans le fichier repos-archive.list, si oui alors on peut commencer l'opération
         */
        if ($OS_FAMILY == "Redhat") {
            $result = $this->db->countRows("SELECT * FROM repos_archived WHERE Name = '$this->name' AND Date = '$this->date'");
        }
        if ($OS_FAMILY == "Debian") {
            $result = $this->db->countRows("SELECT * FROM repos_archived WHERE Name = '$this->name' AND Dist = '$this->dist' AND Section = '$this->section' AND Date = '$this->date'");
        }
        if ($result == 0) {
            if ($OS_FAMILY == "Redhat") { echo "<tr><td colspan=\"100%\"><br><span class=\"redtext\">Erreur : </span>aucun repo archivé $this->name n'existe</td></tr>"; }
            if ($OS_FAMILY == "Debian") { echo "<tr><td colspan=\"100%\"><br><span class=\"redtext\">Erreur : </span>aucune section de repo archivée $this->name n'existe</td></tr>"; }
            return;
        }

        /**
         *  3. On récupère des informations du repo du même nom actuellement en place et qui va être remplacé
         */
        if ($OS_FAMILY == "Redhat") {
            $result = $this->db->querySingleRow("SELECT Date FROM repos WHERE Name = '$this->name' AND Env = '$this->env'");
        }
        if ($OS_FAMILY == "Debian") {
            $result = $this->db->querySingleRow("SELECT Date FROM repos WHERE Name = '$this->name' AND Dist = '$this->dist' AND Section = '$this->section' AND Env = '$this->env'");
        }
        if (!empty($result['Date'])) { $repoActualDate = $result['Date']; }
        if (!empty($repoActualDate)) { $repoActualDateFormatted = DateTime::createFromFormat('Y-m-d', $repoActualDate)->format('d-m-Y'); }

        /**
         *  4. Suppression du lien symbolique du repo actuellement en place sur $this->env
         */
        if ($OS_FAMILY == "Redhat") {
            if (file_exists("${REPOS_DIR}/{$this->name}_{$this->env}")) {
                unlink("${REPOS_DIR}/{$this->name}_{$this->env}");
            }
        }
        if ($OS_FAMILY == "Debian") {
            if (file_exists("${REPOS_DIR}/{$this->name}/{$this->dist}/{$this->section}_{$this->env}")) {
                unlink("${REPOS_DIR}/{$this->name}/{$this->dist}/{$this->section}_{$this->env}");
            }
        }

        /**
         *  5. Remise en place de l'ancien miroir
         */
        if ($OS_FAMILY == "Redhat") {
            if (!rename("${REPOS_DIR}/archived_{$this->dateFormatted}_{$this->name}", "${REPOS_DIR}/{$this->dateFormatted}_{$this->name}")) {
                echo "<p><span class=\"redtext\">Erreur : </span>impossible de restaurer le miroir du $this->dateFormatted</p>";
                return;
            }
        }
        if ($OS_FAMILY == "Debian") {
            if (!rename("${REPOS_DIR}/{$this->name}/{$this->dist}/archived_{$this->dateFormatted}_{$this->section}", "${REPOS_DIR}/{$this->name}/{$this->dist}/{$this->dateFormatted}_{$this->section}")) {
                echo "<p><span class=\"redtext\">Erreur : </span>impossible de restaurer le miroir du $this->dateFormatted</p>";
                return;
            }
        }

        /**
         *  6. Création du lien symbolique
         */
        if ($OS_FAMILY == "Redhat") {
            if (!file_exists("${REPOS_DIR}/{$this->name}_{$this->env}")) {
                exec("cd ${REPOS_DIR} && ln -s {$this->dateFormatted}_{$this->name}/ {$this->name}_{$this->env}");
            }
        }
        if ($OS_FAMILY == "Debian") {
            if (!file_exists("${REPOS_DIR}/{$this->name}/{$this->dist}/{$this->name}_{$this->env}")) {
                exec("cd ${REPOS_DIR}/{$this->name}/{$this->dist}/ && ln -s {$this->dateFormatted}_{$this->section}/ {$this->section}_{$this->env}");
            }
        }

        /**
         *  7. Archivage de la version du repo (qui vient d'être remplacée par le repo restauré) si elle n'est plus utilisée par d'autres envs
         *  On vérifie que la version du repo n'est pas utilisée par d'autres environnements avant de l'archiver
         */
        if ($OS_FAMILY == "Redhat") {
            $checkIfStillUsed = $this->db->countRows("SELECT * FROM repos WHERE Name = '$this->name' AND Date = '${repoActualDate}' AND Env != '$this->env'");
            $checkIfStillUsed_2 = $this->db->countRows("SELECT * FROM repos WHERE Name = '$this->name' AND Env = '$this->env'");
        }
        if ($OS_FAMILY == "Debian") {
            $checkIfStillUsed = $this->db->countRows("SELECT * FROM repos WHERE Name = '$this->name' AND Dist = '$this->dist' AND Section = '$this->section' AND Date = '${repoActualDate}' AND Env != '$this->env'");
            $checkIfStillUsed_2 = $this->db->countRows("SELECT * FROM repos WHERE Name = '$this->name' AND Dist = '$this->dist' AND Section = '$this->section' AND Env = '$this->env'");
        }

        /**
         *  Cas 1 : Si la version qui vient d'être remplacée est utilisée par d'autres envs, alors on ne l'archive pas
         */
        if ($checkIfStillUsed != 0) {
            echo "<p>Le miroir en date du ${repoActualDateFormatted} est toujours utilisé par d'autres environnements, il n'a donc pas été archivé</p>";

            /**
             *  Mise à jour en BDD
             */
            if ($OS_FAMILY == "Redhat") {
                // Récupération de l'Id du repo actuellement en place
                $result = $this->db->querySingleRow("SELECT Id FROM repos WHERE Name = '$this->name' AND Env = '$this->env' AND Date = '$repoActualDate'");
                $repoActualId = $result['Id'];
                // Suppression du repo actuellement en place
                $this->db->exec("DELETE FROM repos WHERE Name = '$this->name' AND Env = '$this->env' AND Date = '${repoActualDate}'");
                // Puis on rajoute celui qui vient d'être restauré (ya que la date qui change au final)
                $this->db->exec("INSERT INTO repos (Name, Source, Env, Date, Time, Description, Signed, Type) VALUES ('$this->name', '$this->source', '$this->env', '$this->date', '$this->time', '$this->description', '$this->signed', '$this->type')");
                // Récupération de l'ID du repos précédemment inséré car on va en avoir besoin pour l'ajouter au même groupe que le repo remplacé
                $this->id = $this->db->lastInsertRowID();
                // Suppression du repo archivé de la table repo_archived
                $this->db->exec("DELETE FROM repos_archived WHERE Name = '$this->name' AND Source = '$this->source' AND Date = '$this->date'");
            }
            if ($OS_FAMILY == "Debian") {
                // Récupération de l'Id du repo actuellement en place
                $result = $this->db->querySingleRow("SELECT Id FROM repos WHERE Name = '$this->name' AND Dist = '$this->dist' AND Section = '$this->section' AND Env = '$this->env' AND Date = '$repoActualDate'");
                $repoActualId = $result['Id'];
                // Suppression du repo actuellement en place
                $this->db->exec("DELETE FROM repos WHERE Name = '$this->name' AND Dist = '$this->dist' AND Section = '$this->section' AND Env = '$this->env' AND Date = '${repoActualDate}'");
                // Puis on rajoute celui qui vient d'être restauré (ya que la date qui change au final)
                $this->db->exec("INSERT INTO repos (Name, Source, Dist, Section, Env, Date, Time, Description, Signed, Type) VALUES ('$this->name', '$this->source', '$this->dist', '$this->section', '$this->env', '$this->date', '$this->time', '$this->description', '$this->signed', '$this->type')");
                // Récupération de l'ID du repos précédemment inséré car on va en avoir besoin pour l'ajouter au même groupe que le repo remplacé
                $this->id = $this->db->lastInsertRowID();
                // Suppression du repo archivé de la table repo_archived
                $this->db->exec("DELETE FROM repos_archived WHERE Name = '$this->name' AND Dist = '$this->dist' AND Section = '$this->section' AND Source = '$this->source' AND Date = '$this->date'");
            }
            /**
             *  Dans la table group_members on remplace l'id du repo qui vient d'etre remplacé par l'id du repo remplacant, afin que le repo remplacant apparaisse bien dans le même groupe
             */
            $this->db->exec("UPDATE group_members SET Id_repo = '$this->id' WHERE Id_repo = '$repoActualId'");

        /**
         *  Cas 2 : Si le repo qu'on vient de restaurer n'a remplacé aucun repo (comprendre il n'y avait aucun repo en cours sur $repoEnv), alors on mets à jour les infos dans repos.list. Pas d'archivage de quoi que ce soit.
         *  Mise à jour en BDD
         */
        } elseif ($checkIfStillUsed_2 == 0) {
            if ($OS_FAMILY == "Redhat") {
                // Ajout du repo qui vient d'etre restauré
                $this->db->exec("INSERT INTO repos (Name, Source, Env, Date, Time, Description, Signed, Type) VALUES ('$this->name', '$this->source', '$this->env', '$this->date', '$this->time', '$this->description', '$this->signed', '$this->type')");
                // Puis suppression de ce même repo de repos_archived
                $this->db->exec("DELETE FROM repos_archived WHERE Name = '$this->name' AND Source = '$this->source' AND Date = '$this->date'");
            }
            if ($OS_FAMILY == "Debian") {
                // Ajout du repo qui vient d'etre restauré
                $this->db->exec("INSERT INTO repos (Name, Source, Dist, Section, Env, Date, Time, Description, Signed, Type) VALUES ('$this->name', '$this->source', '$this->dist', '$this->section', '$this->env', '$this->date', '$this->time', '$this->description', '$this->signed', '$this->type')");
                // Puis suppression de ce même repo de repos_archived
                $this->db->exec("DELETE FROM repos_archived WHERE Name = '$this->name' AND Dist = '$this->dist' AND Section = '$this->section' AND Source = '$this->source' AND Date = '$this->date'");
            }

        /**
         *  Cas 3 : Si la version remplacée n'est plus utilisée pour quelconque environnement, alors on l'archive
         */
        } else {
            /**
             *  On récupère des informations supplémentaires sur le repo qui va être remplacé
             */
            if ($OS_FAMILY == "Redhat") {
                $resultActualRepo = $this->db->queryArray("SELECT * FROM repos WHERE Name = '$this->name' AND Env = '$this->env' AND Date = '$repoActualDate'");
            }
            if ($OS_FAMILY == "Debian") {
                $resultActualRepo = $this->db->queryArray("SELECT * FROM repos WHERE Name = '$this->name' AND Dist = '$this->dist' AND Section = '$this->section' AND Env = '$this->env' AND Date = '$repoActualDate'");
            }
            $repoActualId = $resultActualRepo['Id'];
            $repoActualSource = $resultActualRepo['Source'];
            $repoActualTime = $resultActualRepo['Time'];
            $repoActualDescription = $resultActualRepo['Description'];
            $repoActualSigned = $resultActualRepo['Signed'];
            $repoActualType = $resultActualRepo['Type'];

            /**
             *  Archivage du miroir en date du $repoActualDate car il n'est plus utilisé par quelconque environnement
             */
            if ($OS_FAMILY == "Redhat") {
                if (!rename("${REPOS_DIR}/${repoActualDateFormatted}_{$this->name}", "${REPOS_DIR}/archived_${repoActualDateFormatted}_{$this->name}")) {
                    echo "<p><span class=\"redtext\">Erreur : </span>impossible d'archiver le miroir en date du $repoActualDateFormatted</p>";
                    return;
                }
            }
            if ($OS_FAMILY == "Debian") {
                if (!rename("${REPOS_DIR}/{$this->name}/{$this->dist}/${repoActualDateFormatted}_{$this->section}", "${REPOS_DIR}/{$this->name}/{$this->dist}/archived_${repoActualDateFormatted}_{$this->section}")) {
                    echo "<p><span class=\"redtext\">Erreur : </span>impossible d'archiver le miroir en date du $repoActualDateFormatted</p>";
                    return;
                }
            }

            /**
             *  Mise à jour des informations en BDD
             */
            if ($OS_FAMILY == "Redhat") {
                // Maj de la table repos
                $this->db->exec("DELETE FROM repos WHERE Name = '$this->name' AND Env = '$this->env' AND Date = '$repoActualDate'");
                $this->db->exec("INSERT INTO repos (Name, Source, Env, Date, Time, Description, Signed, Type) VALUES ('$this->name', '$this->source', '$this->env', '$this->date', '$this->time', '$this->description', '$this->signed', '$this->type')");
                // Récupération de l'ID du repos précédemment inséré car on va en avoir besoin pour l'ajouter au même groupe que le repo remplacé
                $this->id = $this->db->lastInsertRowID();
                // Maj de la table repos_archived
                // Supprime le repo qu'on a restauré :
                $this->db->exec("DELETE FROM repos_archived WHERE Name = '$this->name' AND Source = '$this->source' AND Date = '$this->date'");
                // Ajoute dans la table repos_archived le repo qui s'est fait remplacer :
                $this->db->exec("INSERT INTO repos_archived (Name, Source, Date, Time, Description, Signed, Type) VALUES ('$this->name', '$repoActualSource', '$repoActualDate', '$repoActualTime', '$repoActualDescription', '$repoActualSigned', '$repoActualType' )");
                echo "<p>Le miroir en date du $repoActualDateFormatted a été archivé car il n'est plus utilisé par quelconque environnement</p>";
            }
            if ($OS_FAMILY == "Debian") {
                // Maj de la table repos
                $this->db->exec("DELETE FROM repos WHERE Name = '$this->name' AND Dist = '$this->dist' AND Section = '$this->section' AND Env = '$this->env' AND Date = '$repoActualDate'");
                $this->db->exec("INSERT INTO repos (Name, Source, Dist, Section, Env, Date, Time, Description, Signed, Type) VALUES ('$this->name', '$this->source', '$this->dist', '$this->section', '$this->env', '$this->date', '$this->time', '$this->description', '$this->signed', '$this->type')");
                // Récupération de l'ID du repos précédemment inséré car on va en avoir besoin pour l'ajouter au même groupe que le repo remplacé
                $this->id = $this->db->lastInsertRowID();
                // Maj de la table repos_archived
                // Supprime le repo qu'on a restauré :
                $this->db->exec("DELETE FROM repos_archived WHERE Name = '$this->name' AND Dist = '$this->dist' AND Section = '$this->section' AND Source = '$this->source' AND Date = '$this->date'");
                // Ajoute dans la table repos_archived le repo qui s'est fait remplacer :
                $this->db->exec("INSERT INTO repos_archived (Name, Source, Dist, Section, Date, Time, Description, Signed, Type) VALUES ('$this->name', '$repoActualSource', '$this->dist', '$this->section', '$repoActualDate', '$repoActualTime', '$repoActualDescription', '$repoActualSigned', '$repoActualType' )");
                echo "<p>Le miroir en date du $repoActualDateFormatted a été archivé car il n'est plus utilisé par quelconque environnement</p>";
            }
            /**
             *  Dans la table group_members on remplace l'id du repo qui vient d'etre remplacé par l'id du repo remplacant, afin que le repo remplacant apparaisse bien dans le même groupe
             */
            $this->db->exec("UPDATE group_members SET Id_repo = '$this->id' WHERE Id_repo = '$repoActualId'");
        }
        echo "<p>Restauré en <b>$this->env</b> <span class=\"greentext\">✔</span></p>";
    }
}
?>