<?php
trait restore {
    /**
     *  RESTAURER UN(E) REPO/SECTION ARCHIVÉ(E)
     */
    public function exec_restore() {
        global $OS_FAMILY;
        global $REPOS_DIR;

        ob_start();

        /**
         *  1. Génération du tableau récapitulatif de l'opération
         */
        if ($OS_FAMILY == "Redhat") echo '<h3>RESTAURER UN REPO ARCHIVÉ</h3>';
        if ($OS_FAMILY == "Debian") echo '<h3>RESTAURER UNE SECTION ARCHIVÉE</h3>';
        echo "<table class=\"op-table\">
        <tr>
            <td>Nom du repo :</td>
            <td><b>{$this->repo->name}</b></td>
        </tr>";
        if ($OS_FAMILY == "Debian") {
            echo "<tr>
                <td>Distribution :</td>
                <td><b>{$this->repo->dist}</b></td>
            </tr>
            <tr>
                <td>Section :</td>
                <td><b>{$this->repo->section}</b></td>
            </tr>";
        }
        echo "<tr>
            <td>Date :</td>
            <td><b>{$this->repo->dateFormatted}</b></td>
        </tr>
        <tr>
            <td>Environnement cible :</td>
            <td>".envtag($this->repo->newEnv)."</td>
        </tr>";
        if (!empty($this->repo->description)) {
            echo "<tr>
                <td>Description :</td>
                <td><b>{$this->repo->description}</b></td>
            </tr>";
        }
        echo "</table>";

        $this->log->steplog(1);
        $this->log->steplogInitialize('restore');
        $this->log->steplogTitle('RESTAURATION');
        $this->log->steplogLoading();

        /**
         *  1. On récupère la source, le type et la signature du repo/section archivé(e) qui va être restauré(e)
         */
        if ($OS_FAMILY == "Redhat") {
            $resultSource = $this->repo->db->querySingleRow("SELECT Source FROM repos_archived WHERE Name = '{$this->repo->name}' AND Date = '{$this->repo->date}' AND Status = 'active'");
            $resultSigned = $this->repo->db->querySingleRow("SELECT Signed FROM repos_archived WHERE Name = '{$this->repo->name}' AND Date = '{$this->repo->date}' AND Status = 'active'");
            $resultType = $this->repo->db->querySingleRow("SELECT Type FROM repos_archived WHERE Name = '{$this->repo->name}' AND Date = '{$this->repo->date}' AND Status = 'active'");
        }
        if ($OS_FAMILY == "Debian") {
            $resultSource = $this->repo->db->querySingleRow("SELECT Source FROM repos_archived WHERE Name = '{$this->repo->name}' AND Dist = '{$this->repo->dist}' AND Section = '{$this->repo->section}' AND Date = '{$this->repo->date}' AND Status = 'active'");
            $resultSigned = $this->repo->db->querySingleRow("SELECT Signed FROM repos_archived WHERE Name = '{$this->repo->name}' AND Date = '{$this->repo->date}' AND Status = 'active'");
            $resultType = $this->repo->db->querySingleRow("SELECT Type FROM repos_archived WHERE Name = '{$this->repo->name}' AND Date = '{$this->repo->date}' AND Status = 'active'");
        }
        $this->repo->source = $resultSource['Source'];
        $this->repo->signed = $resultSigned['Signed'];
        $this->repo->type = $resultType['Type'];

        /**
         *  2. On vérifie que le repo renseigné est bien présent dans le fichier repos-archive.list, si oui alors on peut commencer l'opération
         */
        if ($OS_FAMILY == "Redhat") $result = $this->repo->db->countRows("SELECT * FROM repos_archived WHERE Name = '{$this->repo->name}' AND Date = '{$this->repo->date}' AND Status = 'active'");
        if ($OS_FAMILY == "Debian") $result = $this->repo->db->countRows("SELECT * FROM repos_archived WHERE Name = '{$this->repo->name}' AND Dist = '{$this->repo->dist}' AND Section = '{$this->repo->section}' AND Date = '{$this->repo->date}' AND Status = 'active'");
        if ($result == 0) {
            if ($OS_FAMILY == "Redhat") throw new Exception ("il n'existe aucun repo archivé <b>{$this->repo->name}</b>");
            if ($OS_FAMILY == "Debian") throw new Exception ("il n'existe aucune section de repo archivée <b>{$this->repo->name}</b>");
        }

        /**
         *  3. On récupère des informations du repo du même nom actuellement en place et qui va être remplacé
         */
        if ($OS_FAMILY == "Redhat") $result = $this->repo->db->querySingleRow("SELECT Date FROM repos WHERE Name = '{$this->repo->name}' AND Env = '{$this->repo->newEnv}' AND Status = 'active'");
        if ($OS_FAMILY == "Debian") $result = $this->repo->db->querySingleRow("SELECT Date FROM repos WHERE Name = '{$this->repo->name}' AND Dist = '{$this->repo->dist}' AND Section = '{$this->repo->section}' AND Env = '{$this->repo->newEnv}' AND Status = 'active'");
        if (!empty($result['Date'])) $repoActualDate = $result['Date'];
        if (!empty($repoActualDate)) $repoActualDateFormatted = DateTime::createFromFormat('Y-m-d', $repoActualDate)->format('d-m-Y');

        /**
         *  4. Suppression du lien symbolique du repo actuellement en place sur $this->repo->newEnv
         */
        if ($OS_FAMILY == "Redhat") {
            if (file_exists("${REPOS_DIR}/{$this->repo->name}_{$this->repo->newEnv}")) {
                unlink("${REPOS_DIR}/{$this->repo->name}_{$this->repo->newEnv}");
            }
        }
        if ($OS_FAMILY == "Debian") {
            if (file_exists("${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/{$this->repo->section}_{$this->repo->newEnv}")) {
                unlink("${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/{$this->repo->section}_{$this->repo->newEnv}");
            }
        }

        /**
         *  5. Remise en place de l'ancien miroir
         */
        if ($OS_FAMILY == "Redhat") {
            if (!rename("${REPOS_DIR}/archived_{$this->repo->dateFormatted}_{$this->repo->name}", "${REPOS_DIR}/{$this->repo->dateFormatted}_{$this->repo->name}")) {
                throw new Exception("impossible de restaurer le miroir du <b>{$this->repo->dateFormatted}</b>");
            }
        }
        if ($OS_FAMILY == "Debian") {
            if (!rename("${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/archived_{$this->repo->dateFormatted}_{$this->repo->section}", "${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/{$this->repo->dateFormatted}_{$this->repo->section}")) {
                throw new Exception("impossible de restaurer le miroir du <b>{$this->repo->dateFormatted}</b>");
            }
        }

        /**
         *  6. Création du lien symbolique
         */
        if ($OS_FAMILY == "Redhat") {
            if (!file_exists("${REPOS_DIR}/{$this->repo->name}_{$this->repo->newEnv}")) {
                exec("cd ${REPOS_DIR} && ln -sfn {$this->repo->dateFormatted}_{$this->repo->name}/ {$this->repo->name}_{$this->repo->newEnv}");
            }
        }
        if ($OS_FAMILY == "Debian") {
            if (!file_exists("${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/{$this->repo->name}_{$this->repo->newEnv}")) {
                exec("cd ${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/ && ln -sfn {$this->repo->dateFormatted}_{$this->repo->section}/ {$this->repo->section}_{$this->repo->newEnv}");
            }
        }

        /**
         *  7. Archivage de la version du repo (qui vient d'être remplacée par le repo restauré) si elle n'est plus utilisée par d'autres envs
         *  On vérifie que la version du repo n'est pas utilisée par d'autres environnements avant de l'archiver
         */
        if ($OS_FAMILY == "Redhat") {
            $checkIfStillUsed = $this->repo->db->countRows("SELECT * FROM repos WHERE Name = '{$this->repo->name}' AND Date = '${repoActualDate}' AND Env != '{$this->repo->newEnv}' AND Status = 'active'");
            $checkIfStillUsed_2 = $this->repo->db->countRows("SELECT * FROM repos WHERE Name = '{$this->repo->name}' AND Env = '{$this->repo->newEnv}' AND Status = 'active'");
        }
        if ($OS_FAMILY == "Debian") {
            $checkIfStillUsed = $this->repo->db->countRows("SELECT * FROM repos WHERE Name = '{$this->repo->name}' AND Dist = '{$this->repo->dist}' AND Section = '{$this->repo->section}' AND Date = '${repoActualDate}' AND Env != '{$this->repo->newEnv}' AND Status = 'active'");
            $checkIfStillUsed_2 = $this->repo->db->countRows("SELECT * FROM repos WHERE Name = '{$this->repo->name}' AND Dist = '{$this->repo->dist}' AND Section = '{$this->repo->section}' AND Env = '{$this->repo->newEnv}' AND Status = 'active'");
        }

        /**
         *  Cas 1 : Si la version qui vient d'être remplacée est utilisée par d'autres envs, alors on ne l'archive pas
         */
        if ($checkIfStillUsed != 0) {

            /**
             *  Mise à jour en BDD
             */
            if ($OS_FAMILY == "Redhat") {
                // Récupération de l'Id du repo actuellement en place
                $result = $this->repo->db->querySingleRow("SELECT Id FROM repos WHERE Name = '{$this->repo->name}' AND Env = '{$this->repo->newEnv}' AND Date = '$repoActualDate' AND Status = 'active'");
                $repoActualId = $result['Id'];
                // Suppression du repo actuellement en place
                $this->repo->db->exec("DELETE FROM repos WHERE Name = '{$this->repo->name}' AND Env = '{$this->repo->newEnv}' AND Date = '${repoActualDate}' AND Status = 'active'");
                // Puis on rajoute celui qui vient d'être restauré (ya que la date qui change au final)
                $this->repo->db->exec("INSERT INTO repos (Name, Source, Env, Date, Time, Description, Signed, Type, Status) VALUES ('{$this->repo->name}', '{$this->repo->source}', '{$this->repo->newEnv}', '{$this->repo->date}', '{$this->repo->time}', '{$this->repo->description}', '{$this->repo->signed}', '{$this->repo->type}', 'active')");
                // Récupération de l'ID du repos précédemment inséré car on va en avoir besoin pour l'ajouter au même groupe que le repo remplacé
                $this->repo->id = $this->repo->db->lastInsertRowID();
                // Suppression du repo archivé de la table repo_archived
                $this->repo->db->exec("DELETE FROM repos_archived WHERE Name = '{$this->repo->name}' AND Source = '{$this->repo->source}' AND Date = '{$this->repo->date}' AND Status = 'active'");
            }
            if ($OS_FAMILY == "Debian") {
                // Récupération de l'Id du repo actuellement en place
                $result = $this->repo->db->querySingleRow("SELECT Id FROM repos WHERE Name = '{$this->repo->name}' AND Dist = '{$this->repo->dist}' AND Section = '{$this->repo->section}' AND Env = '{$this->repo->newEnv}' AND Date = '$repoActualDate' AND Status = 'active'");
                $repoActualId = $result['Id'];
                // Suppression du repo actuellement en place
                $this->repo->db->exec("DELETE FROM repos WHERE Name = '{$this->repo->name}' AND Dist = '{$this->repo->dist}' AND Section = '{$this->repo->section}' AND Env = '{$this->repo->newEnv}' AND Date = '${repoActualDate}' AND Status = 'active'");
                // Puis on rajoute celui qui vient d'être restauré (ya que la date qui change au final)
                $this->repo->db->exec("INSERT INTO repos (Name, Source, Dist, Section, Env, Date, Time, Description, Signed, Type, Status) VALUES ('{$this->repo->name}', '{$this->repo->source}', '{$this->repo->dist}', '{$this->repo->section}', '{$this->repo->newEnv}', '{$this->repo->date}', '{$this->repo->time}', '{$this->repo->description}', '{$this->repo->signed}', '{$this->repo->type}', 'active')");
                // Récupération de l'ID du repos précédemment inséré car on va en avoir besoin pour l'ajouter au même groupe que le repo remplacé
                $this->repo->id = $this->repo->db->lastInsertRowID();
                // Suppression du repo archivé de la table repo_archived
                $this->repo->db->exec("DELETE FROM repos_archived WHERE Name = '{$this->repo->name}' AND Dist = '{$this->repo->dist}' AND Section = '{$this->repo->section}' AND Source = '{$this->repo->source}' AND Date = '{$this->repo->date}' AND Status = 'active'");
            }
            /**
             *  Dans la table group_members on remplace l'id du repo qui vient d'etre remplacé par l'id du repo remplacant, afin que le repo remplacant apparaisse bien dans le même groupe
             */
            $this->repo->db->exec("UPDATE group_members SET Id_repo = '{$this->repo->id}' WHERE Id_repo = '$repoActualId'");

            $this->log->steplogOK("Le miroir en date du <b>${repoActualDateFormatted}</b> est toujours utilisé par d'autres environnements, il n'a donc pas été archivé");

        /**
         *  Cas 2 : Si le repo qu'on vient de restaurer n'a remplacé aucun repo (comprendre il n'y avait aucun repo en cours sur $repoEnv), alors on mets à jour les infos dans repos.list. Pas d'archivage de quoi que ce soit.
         *  Mise à jour en BDD
         */
        } elseif ($checkIfStillUsed_2 == 0) {
            if ($OS_FAMILY == "Redhat") {
                // Ajout du repo qui vient d'etre restauré
                $this->repo->db->exec("INSERT INTO repos (Name, Source, Env, Date, Time, Description, Signed, Type, Status) VALUES ('{$this->repo->name}', '{$this->repo->source}', '{$this->repo->newEnv}', '{$this->repo->date}', '{$this->repo->time}', '{$this->repo->description}', '{$this->repo->signed}', '{$this->repo->type}', 'active')");
                // Puis suppression de ce même repo de repos_archived
                $this->repo->db->exec("DELETE FROM repos_archived WHERE Name = '{$this->repo->name}' AND Source = '{$this->repo->source}' AND Date = '{$this->repo->date}' AND Status = 'active'");
            }
            if ($OS_FAMILY == "Debian") {
                // Ajout du repo qui vient d'etre restauré
                $this->repo->db->exec("INSERT INTO repos (Name, Source, Dist, Section, Env, Date, Time, Description, Signed, Type, Status) VALUES ('{$this->repo->name}', '{$this->repo->source}', '{$this->repo->dist}', '{$this->repo->section}', '{$this->repo->newEnv}', '{$this->repo->date}', '{$this->repo->time}', '{$this->repo->description}', '{$this->repo->signed}', '{$this->repo->type}', 'active')");
                // Puis suppression de ce même repo de repos_archived
                $this->repo->db->exec("DELETE FROM repos_archived WHERE Name = '{$this->repo->name}' AND Dist = '{$this->repo->dist}' AND Section = '{$this->repo->section}' AND Source = '{$this->repo->source}' AND Date = '{$this->repo->date}' AND Status = 'active'");
            }

            $this->log->steplogOK();

        /**
         *  Cas 3 : Si la version remplacée n'est plus utilisée pour quelconque environnement, alors on l'archive
         */
        } else {
            /**
             *  Clôture de l'étape RESTAURATION
             */
            $this->log->steplogOK();

            /**
             *  Nouvelle étape ARCHIVAGE
             */
            $this->log->steplog(2);
            $this->log->steplogInitialize('archive');
            $this->log->steplogTitle('ARCHIVAGE');
            $this->log->steplogLoading();
            
            /**
             *  On récupère des informations supplémentaires sur le repo qui va être remplacé
             */
            if ($OS_FAMILY == "Redhat") $resultActualRepo = $this->repo->db->queryArray("SELECT * FROM repos WHERE Name = '{$this->repo->name}' AND Env = '{$this->repo->newEnv}' AND Date = '$repoActualDate' AND Status = 'active'");
            if ($OS_FAMILY == "Debian") $resultActualRepo = $this->repo->db->queryArray("SELECT * FROM repos WHERE Name = '{$this->repo->name}' AND Dist = '{$this->repo->dist}' AND Section = '{$this->repo->section}' AND Env = '{$this->repo->newEnv}' AND Date = '$repoActualDate' AND Status = 'active'");

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
                if (!rename("${REPOS_DIR}/${repoActualDateFormatted}_{$this->repo->name}", "${REPOS_DIR}/archived_${repoActualDateFormatted}_{$this->repo->name}")) {
                    throw new Exception("impossible d'archiver le miroir en date du <b>$repoActualDateFormatted</b>");
                }
            }
            if ($OS_FAMILY == "Debian") {
                if (!rename("${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/${repoActualDateFormatted}_{$this->repo->section}", "${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/archived_${repoActualDateFormatted}_{$this->repo->section}")) {
                    throw new Exception("impossible d'archiver le miroir en date du <b>$repoActualDateFormatted</b>");
                }
            }

            /**
             *  Mise à jour des informations en BDD
             */
            if ($OS_FAMILY == "Redhat") {
                // Maj de la table repos
                $this->repo->db->exec("DELETE FROM repos WHERE Name = '{$this->repo->name}' AND Env = '{$this->repo->newEnv}' AND Date = '$repoActualDate' AND Status = 'active'");
                $this->repo->db->exec("INSERT INTO repos (Name, Source, Env, Date, Time, Description, Signed, Type, Status) VALUES ('{$this->repo->name}', '{$this->repo->source}', '{$this->repo->newEnv}', '{$this->repo->date}', '{$this->repo->time}', '{$this->repo->description}', '{$this->repo->signed}', '{$this->repo->type}', 'active')");
                // Récupération de l'ID du repos précédemment inséré car on va en avoir besoin pour l'ajouter au même groupe que le repo remplacé
                $this->repo->id = $this->repo->db->lastInsertRowID();
                // Maj de la table repos_archived
                // Supprime le repo qu'on a restauré :
                $this->repo->db->exec("DELETE FROM repos_archived WHERE Name = '{$this->repo->name}' AND Source = '{$this->repo->source}' AND Date = '{$this->repo->date}' AND Status = 'active'");
                // Ajoute dans la table repos_archived le repo qui s'est fait remplacer :
                $this->repo->db->exec("INSERT INTO repos_archived (Name, Source, Date, Time, Description, Signed, Type, Status) VALUES ('{$this->repo->name}', '$repoActualSource', '$repoActualDate', '$repoActualTime', '$repoActualDescription', '$repoActualSigned', '$repoActualType', 'active')");
            }
            if ($OS_FAMILY == "Debian") {
                // Maj de la table repos
                $this->repo->db->exec("DELETE FROM repos WHERE Name = '{$this->repo->name}' AND Dist = '{$this->repo->dist}' AND Section = '{$this->repo->section}' AND Env = '{$this->repo->newEnv}' AND Date = '$repoActualDate' AND Status = 'active'");
                $this->repo->db->exec("INSERT INTO repos (Name, Source, Dist, Section, Env, Date, Time, Description, Signed, Type, Status) VALUES ('{$this->repo->name}', '{$this->repo->source}', '{$this->repo->dist}', '{$this->repo->section}', '{$this->repo->newEnv}', '{$this->repo->date}', '{$this->repo->time}', '{$this->repo->description}', '{$this->repo->signed}', '{$this->repo->type}', 'active')");
                // Récupération de l'ID du repos précédemment inséré car on va en avoir besoin pour l'ajouter au même groupe que le repo remplacé
                $this->repo->id = $this->repo->db->lastInsertRowID();
                // Maj de la table repos_archived
                // Supprime le repo qu'on a restauré :
                $this->repo->db->exec("DELETE FROM repos_archived WHERE Name = '{$this->repo->name}' AND Dist = '{$this->repo->dist}' AND Section = '{$this->repo->section}' AND Source = '{$this->repo->source}' AND Date = '{$this->repo->date}' AND Status = 'active'");
                // Ajoute dans la table repos_archived le repo qui s'est fait remplacer :
                $this->repo->db->exec("INSERT INTO repos_archived (Name, Source, Dist, Section, Date, Time, Description, Signed, Type, Status) VALUES ('{$this->repo->name}', '$repoActualSource', '{$this->repo->dist}', '{$this->repo->section}', '$repoActualDate', '$repoActualTime', '$repoActualDescription', '$repoActualSigned', '$repoActualType', 'active')");
            }
            /**
             *  Dans la table group_members on remplace l'id du repo qui vient d'etre remplacé par l'id du repo remplacant, afin que le repo remplacant apparaisse bien dans le même groupe
             */
            $this->repo->db->exec("UPDATE group_members SET Id_repo = '{$this->repo->id}' WHERE Id_repo = '$repoActualId'");

            $this->log->steplogOK("Le miroir en date du <b>$repoActualDateFormatted</b> a été archivé car il n'est plus utilisé par quelconque environnement");
        }
    }
}
?>