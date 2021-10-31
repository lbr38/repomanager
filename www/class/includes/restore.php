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
            <td>NOM DU REPO :</td>
            <td><b>{$this->repo->name}</b></td>
        </tr>";
        if ($OS_FAMILY == "Debian") {
            echo "<tr>
                <td>DISTRIBUTION :</td>
                <td><b>{$this->repo->dist}</b></td>
            </tr>
            <tr>
                <td>SECTION :</td>
                <td><b>{$this->repo->section}</b></td>
            </tr>";
        }
        echo "<tr>
            <td>DATE :</td>
            <td><b>{$this->repo->dateFormatted}</b></td>
        </tr>
        <tr>
            <td>ENVIRONNEMENT CIBLE :</td>
            <td>".envtag($this->repo->newEnv)."</td>
        </tr>";
        if (!empty($this->repo->description)) {
            echo "<tr>
                <td>DESCRIPTION :</td>
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
        if ($OS_FAMILY == "Redhat") $stmt = $this->repo->db->prepare("SELECT * FROM repos_archived WHERE Name=:name AND Date=:date AND Status = 'active'");
        if ($OS_FAMILY == "Debian") $stmt = $this->repo->db->prepare("SELECT * FROM repos_archived WHERE Name=:name AND Dist=:dist AND Section=:section AND Date=:date AND Status = 'active'");
        $stmt->bindValue(':name', $this->repo->name);
        $stmt->bindValue(':date', $this->repo->date);
        if ($OS_FAMILY == "Debian") {
            $stmt->bindValue(':dist', $this->repo->dist);
            $stmt->bindValue(':section', $this->repo->section);
        }
        $result = $stmt->execute();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) $datas = $row;
        $this->repo->source = $datas['Source'];
        $this->repo->signed = $datas['Signed'];
        $this->repo->type = $datas['Type'];
        unset($datas);

        /**
         *  2. On vérifie que le repo renseigné est bien présent dans le fichier repos-archive.list, si oui alors on peut commencer l'opération
         */
        if ($OS_FAMILY == "Redhat") $stmt = $this->repo->db->prepare("SELECT * FROM repos_archived WHERE Name=:name AND Date=:date AND Status = 'active'");
        if ($OS_FAMILY == "Debian") $stmt = $this->repo->db->prepare("SELECT * FROM repos_archived WHERE Name=:name AND Dist=:dist AND Section=:section AND Date=:date AND Status = 'active'");
        $stmt->bindValue(':name', $this->repo->name);
        $stmt->bindValue(':date', $this->repo->date);
        if ($OS_FAMILY == "Debian") {
            $stmt->bindValue(':dist', $this->repo->dist);
            $stmt->bindValue(':section', $this->repo->section);
        }
        $result = $stmt->execute();
        
        if ($this->repo->db->isempty($result) === true) {
            if ($OS_FAMILY == "Redhat") throw new Exception("il n'existe aucun repo archivé <b>{$this->repo->name}</b>");
            if ($OS_FAMILY == "Debian") throw new Exception("il n'existe aucune section de repo archivée <b>{$this->repo->name}</b>");
        }

        /**
         *  3. On récupère des informations du repo du même nom actuellement en place et qui va être remplacé
         */
        if ($OS_FAMILY == "Redhat") $stmt = $this->repo->db->prepare("SELECT Date FROM repos WHERE Name=:name AND Env=:newenv AND Status = 'active'");
        if ($OS_FAMILY == "Debian") $stmt = $this->repo->db->prepare("SELECT Date FROM repos WHERE Name=:name AND Dist=:dist AND Section=:section AND Env=:newenv AND Status = 'active'");
        $stmt->bindValue(':name', $this->repo->name);
        $stmt->bindValue(':newenv', $this->repo->newEnv);
        if ($OS_FAMILY == "Debian") {
            $stmt->bindValue(':dist', $this->repo->dist);
            $stmt->bindValue(':section', $this->repo->section);
        }
        $result = $stmt->execute();
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) $datas = $row;
        if (!empty($datas['Date'])) $repoActualDate = $datas['Date'];
        if (!empty($repoActualDate)) $repoActualDateFormatted = DateTime::createFromFormat('Y-m-d', $repoActualDate)->format('d-m-Y');
        unset($datas);

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
        if ($OS_FAMILY == "Redhat") $stmt = $this->repo->db->prepare("SELECT * FROM repos WHERE Name=:name AND Date=:date AND Env !=:newenv AND Status = 'active'");
        if ($OS_FAMILY == "Debian") $stmt = $this->repo->db->prepare("SELECT * FROM repos WHERE Name=:name AND Dist=:dist AND Section=:section AND Date=:date AND Env !=:newenv AND Status = 'active'");
        $stmt->bindValue(':name', $this->repo->name);
        $stmt->bindValue(':date', $repoActualDate);
        $stmt->bindValue(':newenv', $this->repo->newEnv);
        if ($OS_FAMILY == "Debian") {
            $stmt->bindValue(':dist', $this->repo->dist);
            $stmt->bindValue(':section', $this->repo->section);
        }
        $result = $stmt->execute();
        if ($this->repo->db->isempty($result) === true) {
            $checkIfStillUsed = 0;
        } else {
            $checkIfStillUsed = 1;
        }

        if ($OS_FAMILY == "Redhat") $stmt2 = $this->repo->db->prepare("SELECT * FROM repos WHERE Name=:name AND Env=:newenv AND Status = 'active'");
        if ($OS_FAMILY == "Debian") $stmt2 = $this->repo->db->prepare("SELECT * FROM repos WHERE Name=:name AND Dist=:dist AND Section=:section AND Env=:newenv AND Status = 'active'");
        $stmt2->bindValue(':name', $this->repo->name);
        $stmt2->bindValue(':newenv', $this->repo->newEnv);
        if ($OS_FAMILY == "Debian") {
            $stmt2->bindValue(':dist', $this->repo->dist);
            $stmt2->bindValue(':section', $this->repo->section);
        }
        $result2 = $stmt2->execute();
        if ($this->repo->db->isempty($result2) === true) {
            $checkIfStillUsed_2 = 0;
        } else {
            $checkIfStillUsed_2 = 1;
        }

        /**
         *  Cas 1 : Si la version qui vient d'être remplacée est utilisée par d'autres envs, alors on ne l'archive pas
         */
        if ($checkIfStillUsed != 0) {
            /**
             *  Mise à jour en BDD
             */

            // Récupération de l'Id du repo actuellement en place
            if ($OS_FAMILY == "Redhat") $stmt = $this->repo->db->prepare("SELECT Id FROM repos WHERE Name=:name AND Env=:newenv AND Date=:date AND Status = 'active'");
            if ($OS_FAMILY == "Debian") $stmt = $this->repo->db->prepare("SELECT Id FROM repos WHERE Name=:name AND Dist=:dist AND Section=:section AND Env=:newenv AND Date=:date AND Status = 'active'");
            $stmt->bindValue(':name', $this->repo->name);
            $stmt->bindValue(':newenv', $this->repo->newEnv);
            $stmt->bindValue(':date', $repoActualDate);
            if ($OS_FAMILY == "Debian") {
                $stmt->bindValue(':dist', $this->repo->dist);
                $stmt->bindValue(':section', $this->repo->section);
            }
            $result = $stmt->execute();

            while ($row = $result->fetchArray(SQLITE3_ASSOC)) $datas = $row;
            $repoActualId = $datas['Id'];
            unset($datas);

            // Suppression du repo actuellement en place
            if ($OS_FAMILY == "Redhat") $stmt = $this->repo->db->prepare("DELETE FROM repos WHERE Name=:name AND Env=:newenv AND Date=:date AND Status = 'active'");
            if ($OS_FAMILY == "Debian") $stmt = $this->repo->db->prepare("DELETE FROM repos WHERE Name=:name AND Dist=:dist AND Section=:section AND Env=:newenv AND Date=:date AND Status = 'active'");
            $stmt->bindValue(':name', $this->repo->name);
            $stmt->bindValue(':newenv', $this->repo->newEnv);
            $stmt->bindValue(':date', $repoActualDate);
            if ($OS_FAMILY == "Debian") {
                $stmt->bindValue(':dist', $this->repo->dist);
                $stmt->bindValue(':section', $this->repo->section);
            }
            $stmt->execute();

            // Puis on rajoute celui qui vient d'être restauré (ya que la date qui change au final)
            if ($OS_FAMILY == "Redhat") $stmt = $this->repo->db->prepare("INSERT INTO repos (Name, Source, Env, Date, Time, Description, Signed, Type, Status) VALUES (:name, :source, :newenv, :date, :time, :description, :signed, :type, 'active')");
            if ($OS_FAMILY == "Debian") $stmt = $this->repo->db->prepare("INSERT INTO repos (Name, Source, Dist, Section, Env, Date, Time, Description, Signed, Type, Status) VALUES (:name, :source, :dist, :section, :newenv, :date, :time, :description, :signed, :type, 'active')");
            $stmt->bindValue(':name', $this->repo->name);
            $stmt->bindValue(':source', $this->repo->source);
            $stmt->bindValue(':newenv', $this->repo->newEnv);
            $stmt->bindValue(':date', $this->repo->date);
            $stmt->bindValue(':time', $this->repo->time);
            $stmt->bindValue(':description', $this->repo->description);
            $stmt->bindValue(':signed', $this->repo->signed);
            $stmt->bindValue(':type', $this->repo->type);
            if ($OS_FAMILY == "Debian") {
                $stmt->bindValue(':dist', $this->repo->dist);
                $stmt->bindValue(':section', $this->repo->section);
            }
            $stmt->execute();

            // Récupération de l'ID du repos précédemment inséré car on va en avoir besoin pour l'ajouter au même groupe que le repo remplacé
            $this->repo->id = $this->repo->db->lastInsertRowID();

            // Suppression du repo archivé de la table repo_archived
            if ($OS_FAMILY == "Redhat") $stmt = $this->repo->db->prepare("DELETE FROM repos_archived WHERE Name=:name AND Source=:source AND Date=:date AND Status = 'active'");
            if ($OS_FAMILY == "Debian") $stmt = $this->repo->db->prepare("DELETE FROM repos_archived WHERE Name=:name AND Dist=:dist AND Section=:section AND Source=:source AND Date=:date AND Status = 'active'");
            $stmt->bindValue(':name', $this->repo->name);
            $stmt->bindValue(':source', $this->repo->source);
            $stmt->bindValue(':date', $this->repo->date);
            if ($OS_FAMILY == "Debian") {
                $stmt->bindValue(':dist', $this->repo->dist);
                $stmt->bindValue(':section', $this->repo->section);
            }
            $stmt->execute();

            /**
             *  Dans la table group_members on remplace l'id du repo qui vient d'etre remplacé par l'id du repo remplacant, afin que le repo remplacant apparaisse bien dans le même groupe
             */
            $stmt = $this->repo->db->prepare("UPDATE group_members SET Id_repo=:idrepo WHERE Id_repo=:actual_idrepo");
            $stmt->bindValue(':idrepo', $this->repo->id);
            $stmt->bindValue(':actual_idrepo', $repoActualId);
            $stmt->execute();

            $this->log->steplogOK("Le miroir en date du <b>${repoActualDateFormatted}</b> est toujours utilisé par d'autres environnements, il n'a donc pas été archivé");

        /**
         *  Cas 2 : Si le repo qu'on vient de restaurer n'a remplacé aucun repo (comprendre il n'y avait aucun repo en cours sur $repoEnv), alors on mets à jour les infos dans repos.list. Pas d'archivage de quoi que ce soit.
         *  Mise à jour en BDD
         */
        } elseif ($checkIfStillUsed_2 == 0) {
            // Ajout du repo qui vient d'etre restauré
            if ($OS_FAMILY == "Redhat") $stmt = $this->repo->db->prepare("INSERT INTO repos (Name, Source, Env, Date, Time, Description, Signed, Type, Status) VALUES (:name, :source, :newenv, :date, :time, :description, :signed, :type, 'active')");
            if ($OS_FAMILY == "Debian") $stmt = $this->repo->db->prepare("INSERT INTO repos (Name, Source, Dist, Section, Env, Date, Time, Description, Signed, Type, Status) VALUES (:name, :source, :dist, :section, :newenv, :date, :time, :description, :signed, :type, 'active')");
            $stmt->bindValue(':name', $this->repo->name);
            $stmt->bindValue(':source', $this->repo->source);
            $stmt->bindValue(':newenv', $this->repo->newEnv);
            $stmt->bindValue(':date', $this->repo->date);
            $stmt->bindValue(':time', $this->repo->time);
            $stmt->bindValue(':description', $this->repo->description);
            $stmt->bindValue(':signed', $this->repo->signed);
            $stmt->bindValue(':type', $this->repo->type);
            if ($OS_FAMILY == "Debian") {
                $stmt->bindValue(':dist', $this->repo->dist);
                $stmt->bindValue(':section', $this->repo->section);
            }
            $stmt->execute();

            // Puis suppression de ce même repo de repos_archived
            if ($OS_FAMILY == "Redhat") $stmt = $this->repo->db->prepare("DELETE FROM repos_archived WHERE Name=:name AND Source=:source AND Date=:date AND Status = 'active'");
            if ($OS_FAMILY == "Debian") $stmt = $this->repo->db->prepare("DELETE FROM repos_archived WHERE Name=:name AND Dist=:dist AND Section=:section AND Source=:source AND Date=:date AND Status = 'active'");
            $stmt->bindValue(':name', $this->repo->name);
            $stmt->bindValue(':source', $this->repo->source);
            $stmt->bindValue(':date', $this->repo->date);
            if ($OS_FAMILY == "Debian") {
                $stmt->bindValue(':dist', $this->repo->dist);
                $stmt->bindValue(':section', $this->repo->section);
            }
            $stmt->execute();

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
            if ($OS_FAMILY == "Redhat") $stmt = $this->repo->db->prepare("SELECT * FROM repos WHERE Name=:name AND Env=:newenv AND Date=:date AND Status = 'active'");
            if ($OS_FAMILY == "Debian") $stmt = $this->repo->db->prepare("SELECT * FROM repos WHERE Name=:name AND Dist=:dist AND Section=:section AND Env=:newenv AND Date=:date AND Status = 'active'");
            $stmt->bindValue(':name', $this->repo->name);
            $stmt->bindValue(':newenv', $this->repo->newEnv);
            $stmt->bindValue(':date', $repoActualDate);
            if ($OS_FAMILY == "Debian") {
                $stmt->bindValue(':dist', $this->repo->dist);
                $stmt->bindValue(':section', $this->repo->section);
            }
            $result = $stmt->execute();

            while ($row = $result->fetchArray(SQLITE3_ASSOC)) $datas = $row;
            $repoActualId = $datas['Id'];
            $repoActualSource = $datas['Source'];
            $repoActualTime = $datas['Time'];
            $repoActualDescription = $datas['Description'];
            $repoActualSigned = $datas['Signed'];
            $repoActualType = $datas['Type'];
            unset($datas);

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
            // Maj de la table repos
            if ($OS_FAMILY == "Redhat") $stmt = $this->repo->db->prepare("DELETE FROM repos WHERE Name=:name AND Env=:newenv AND Date=:date AND Status = 'active'");
            if ($OS_FAMILY == "Debian") $stmt = $this->repo->db->prepare("DELETE FROM repos WHERE Name=:name AND Dist=:dist AND Section=:section AND Env=:newenv AND Date=:date AND Status = 'active'");
            $stmt->bindValue(':name', $this->repo->name);
            $stmt->bindValue(':newenv', $this->repo->newEnv);
            $stmt->bindValue(':date', $repoActualDate);
            if ($OS_FAMILY == "Debian") {
                $stmt->bindValue(':dist', $this->repo->dist);
                $stmt->bindValue(':section', $this->repo->section);
            }
            $stmt->execute();

            if ($OS_FAMILY == "Redhat") $stmt = $this->repo->db->prepare("INSERT INTO repos (Name, Source, Env, Date, Time, Description, Signed, Type, Status) VALUES (:name, :source, :newenv, :date, :time, :description, :signed, :type, 'active')");
            if ($OS_FAMILY == "Debian") $stmt = $this->repo->db->prepare("INSERT INTO repos (Name, Source, Dist, Section, Env, Date, Time, Description, Signed, Type, Status) VALUES (:name, :source, :dist, :section, :newenv, :date, :time, :description, :signed, :type, 'active')");
            $stmt->bindValue(':name', $this->repo->name);
            $stmt->bindValue(':source', $this->repo->source);
            $stmt->bindValue(':newenv', $this->repo->newEnv);
            $stmt->bindValue(':date', $this->repo->date);
            $stmt->bindValue(':time', $this->repo->time);
            $stmt->bindValue(':description', $this->repo->description);
            $stmt->bindValue(':signed', $this->repo->signed);
            $stmt->bindValue(':type', $this->repo->type);
            if ($OS_FAMILY == "Debian") {
                $stmt->bindValue(':dist', $this->repo->dist);
                $stmt->bindValue(':section', $this->repo->section);
            }
            $stmt->execute();

            // Récupération de l'ID du repos précédemment inséré car on va en avoir besoin pour l'ajouter au même groupe que le repo remplacé
            $this->repo->id = $this->repo->db->lastInsertRowID();

            // Maj de la table repos_archived
            // Supprime le repo qu'on a restauré :
            if ($OS_FAMILY == "Redhat") $stmt = $this->repo->db->prepare("DELETE FROM repos_archived WHERE Name=:name AND Source=:source AND Date=:date AND Status = 'active'");
            if ($OS_FAMILY == "Debian") $stmt = $this->repo->db->prepare("DELETE FROM repos_archived WHERE Name=:name AND Dist=:dist AND Section=:section AND Source=:source AND Date=:date AND Status = 'active'");
            $stmt->bindValue(':name', $this->repo->name);
            $stmt->bindValue(':source', $this->repo->source);
            $stmt->bindValue(':date', $this->repo->date);
            if ($OS_FAMILY == "Debian") {
                $stmt->bindValue(':dist', $this->repo->dist);
                $stmt->bindValue(':section', $this->repo->section);
            }
            $stmt->execute();

            // Ajoute dans la table repos_archived le repo qui s'est fait remplacer :
            if ($OS_FAMILY == "Redhat") $stmt = $this->repo->db->prepare("INSERT INTO repos_archived (Name, Source, Date, Time, Description, Signed, Type, Status) VALUES (:name, :source, :date, :time, :description, :signed, :type, 'active')");
            if ($OS_FAMILY == "Debian") $stmt = $this->repo->db->prepare("INSERT INTO repos_archived (Name, Source, Dist, Section, Date, Time, Description, Signed, Type, Status) VALUES (:name, :source, :dist, :section, :date, :time, :description, :signed, :type, 'active')");
            $stmt->bindValue(':name', $this->repo->name);
            $stmt->bindValue(':source', $repoActualSource);
            $stmt->bindValue(':date', $repoActualDate);
            $stmt->bindValue(':time', $repoActualTime);
            $stmt->bindValue(':description', $repoActualDescription);
            $stmt->bindValue(':signed', $repoActualSigned);
            $stmt->bindValue(':type', $repoActualType);
            if ($OS_FAMILY == "Debian") {
                $stmt->bindValue(':dist', $this->repo->dist);
                $stmt->bindValue(':section', $this->repo->section);
            }
            $stmt->execute();

            /**
             *  Dans la table group_members on remplace l'id du repo qui vient d'etre remplacé par l'id du repo remplacant, afin que le repo remplacant apparaisse bien dans le même groupe
             */
            $stmt = $this->repo->db->prepare("UPDATE group_members SET Id_repo=:idrepo WHERE Id_repo=:actual_idrepo");
            $stmt->bindValue(':idrepo', $this->repo->id);
            $stmt->bindValue(':actual_idrepo', $repoActualId);
            $stmt->execute();

            $this->log->steplogOK("Le miroir en date du <b>$repoActualDateFormatted</b> a été archivé car il n'est plus utilisé par quelconque environnement");
        }
    }
}
?>