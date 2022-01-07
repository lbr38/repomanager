<?php
trait restore {
    /**
     *  RESTAURER UN(E) REPO/SECTION ARCHIVÉ(E)
     */
    public function exec_restore() {

        

        ob_start();

        /**
         *  1. Génération du tableau récapitulatif de l'opération
         */
        if (OS_FAMILY == "Redhat") echo '<h3>RESTAURER UN REPO ARCHIVÉ</h3>';
        if (OS_FAMILY == "Debian") echo '<h3>RESTAURER UNE SECTION ARCHIVÉE</h3>';
        echo "<table class=\"op-table\">
        <tr>
            <th>NOM DU REPO :</th>
            <td><b>{$this->repo->name}</b></td>
        </tr>";
        if (OS_FAMILY == "Debian") {
            echo "<tr>
                <th>DISTRIBUTION :</th>
                <td><b>{$this->repo->dist}</b></td>
            </tr>
            <tr>
                <th>SECTION :</th>
                <td><b>{$this->repo->section}</b></td>
            </tr>";
        }
        echo "<tr>
            <th>DATE :</th>
            <td><b>{$this->repo->dateFormatted}</b></td>
        </tr>
        <tr>
            <th>ENVIRONNEMENT CIBLE :</th>
            <td>".envtag($this->repo->newEnv)."</td>
        </tr>";
        if (!empty($this->repo->description)) {
            echo "<tr>
                <th>DESCRIPTION :</th>
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
        if (OS_FAMILY == "Redhat") $stmt = $this->repo->db->prepare("SELECT * FROM repos_archived WHERE Name=:name AND Date=:date AND Status = 'active'");
        if (OS_FAMILY == "Debian") $stmt = $this->repo->db->prepare("SELECT * FROM repos_archived WHERE Name=:name AND Dist=:dist AND Section=:section AND Date=:date AND Status = 'active'");
        $stmt->bindValue(':name', $this->repo->name);
        $stmt->bindValue(':date', $this->repo->date);
        if (OS_FAMILY == "Debian") {
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
         *  2. On vérifie que le repo renseigné est bien présent dans repos_archived, si oui alors on peut commencer l'opération
         */
        if (OS_FAMILY == "Redhat") {
            if ($this->repo->existsDate($this->repo->name, $this->repo->date, 'archived') === false) {
                throw new Exception("il n'existe aucun repo archivé <b>{$this->repo->name}</b>");
            }
        }
        if (OS_FAMILY == "Debian") {
            if ($this->repo->section_existsDate($this->repo->name, $this->repo->dist, $this->repo->section, $this->repo->date, 'archived') === false) {
                throw new Exception("il n'existe aucune section de repo archivée <b>{$this->repo->name}</b>");
            }
        }

        /**
         *  3. On récupère des informations du repo du même nom actuellement en place et qui va être remplacé
         */
        if (OS_FAMILY == "Redhat") $stmt = $this->repo->db->prepare("SELECT Date FROM repos WHERE Name=:name AND Env=:newenv AND Status = 'active'");
        if (OS_FAMILY == "Debian") $stmt = $this->repo->db->prepare("SELECT Date FROM repos WHERE Name=:name AND Dist=:dist AND Section=:section AND Env=:newenv AND Status = 'active'");
        $stmt->bindValue(':name', $this->repo->name);
        $stmt->bindValue(':newenv', $this->repo->newEnv);
        if (OS_FAMILY == "Debian") {
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
        if (OS_FAMILY == "Redhat") {
            if (file_exists(REPOS_DIR."/{$this->repo->name}_{$this->repo->newEnv}")) {
                unlink(REPOS_DIR."/{$this->repo->name}_{$this->repo->newEnv}");
            }
        }
        if (OS_FAMILY == "Debian") {
            if (file_exists(REPOS_DIR."/{$this->repo->name}/{$this->repo->dist}/{$this->repo->section}_{$this->repo->newEnv}")) {
                unlink(REPOS_DIR."/{$this->repo->name}/{$this->repo->dist}/{$this->repo->section}_{$this->repo->newEnv}");
            }
        }

        /**
         *  5. Remise en place de l'ancien miroir
         */
        if (OS_FAMILY == "Redhat") {
            if (!rename(REPOS_DIR."/archived_{$this->repo->dateFormatted}_{$this->repo->name}", REPOS_DIR."/{$this->repo->dateFormatted}_{$this->repo->name}")) {
                throw new Exception("impossible de restaurer le miroir du <b>{$this->repo->dateFormatted}</b>");
            }
        }
        if (OS_FAMILY == "Debian") {
            if (!rename(REPOS_DIR."/{$this->repo->name}/{$this->repo->dist}/archived_{$this->repo->dateFormatted}_{$this->repo->section}", REPOS_DIR."/{$this->repo->name}/{$this->repo->dist}/{$this->repo->dateFormatted}_{$this->repo->section}")) {
                throw new Exception("impossible de restaurer le miroir du <b>{$this->repo->dateFormatted}</b>");
            }
        }

        /**
         *  6. Création du lien symbolique
         */
        if (OS_FAMILY == "Redhat") {
            if (!file_exists(REPOS_DIR."/{$this->repo->name}_{$this->repo->newEnv}")) {
                exec("cd ".REPOS_DIR." && ln -sfn {$this->repo->dateFormatted}_{$this->repo->name}/ {$this->repo->name}_{$this->repo->newEnv}");
            }
        }
        if (OS_FAMILY == "Debian") {
            if (!file_exists(REPOS_DIR."/{$this->repo->name}/{$this->repo->dist}/{$this->repo->name}_{$this->repo->newEnv}")) {
                exec("cd ".REPOS_DIR."/{$this->repo->name}/{$this->repo->dist}/ && ln -sfn {$this->repo->dateFormatted}_{$this->repo->section}/ {$this->repo->section}_{$this->repo->newEnv}");
            }
        }

        /**
         *  7. Archivage de la version du repo (qui vient d'être remplacée par le repo restauré) si elle n'est plus utilisée par d'autres envs
         *  On vérifie que la version du repo n'est pas utilisée par d'autres environnements avant de l'archiver
         */
        if (OS_FAMILY == "Redhat") $stmt = $this->repo->db->prepare("SELECT * FROM repos WHERE Name=:name AND Date=:date AND Env !=:newenv AND Status = 'active'");
        if (OS_FAMILY == "Debian") $stmt = $this->repo->db->prepare("SELECT * FROM repos WHERE Name=:name AND Dist=:dist AND Section=:section AND Date=:date AND Env !=:newenv AND Status = 'active'");
        $stmt->bindValue(':name', $this->repo->name);
        $stmt->bindValue(':date', $repoActualDate);
        $stmt->bindValue(':newenv', $this->repo->newEnv);
        if (OS_FAMILY == "Debian") {
            $stmt->bindValue(':dist', $this->repo->dist);
            $stmt->bindValue(':section', $this->repo->section);
        }
        $result = $stmt->execute();
        if ($this->repo->db->isempty($result) === true) {
            $checkIfStillUsed = 0;
        } else {
            $checkIfStillUsed = 1;
        }

        if (OS_FAMILY == "Redhat") $stmt2 = $this->repo->db->prepare("SELECT * FROM repos WHERE Name=:name AND Env=:newenv AND Status = 'active'");
        if (OS_FAMILY == "Debian") $stmt2 = $this->repo->db->prepare("SELECT * FROM repos WHERE Name=:name AND Dist=:dist AND Section=:section AND Env=:newenv AND Status = 'active'");
        $stmt2->bindValue(':name', $this->repo->name);
        $stmt2->bindValue(':newenv', $this->repo->newEnv);
        if (OS_FAMILY == "Debian") {
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

            /**
             *  Récupération de l'Id du repo actuellement en place
             */
            if (OS_FAMILY == "Redhat") $stmt = $this->repo->db->prepare("SELECT Id FROM repos WHERE Name=:name AND Env=:newenv AND Date=:date AND Status = 'active'");
            if (OS_FAMILY == "Debian") $stmt = $this->repo->db->prepare("SELECT Id FROM repos WHERE Name=:name AND Dist=:dist AND Section=:section AND Env=:newenv AND Date=:date AND Status = 'active'");
            $stmt->bindValue(':name', $this->repo->name);
            $stmt->bindValue(':newenv', $this->repo->newEnv);
            $stmt->bindValue(':date', $repoActualDate);
            if (OS_FAMILY == "Debian") {
                $stmt->bindValue(':dist', $this->repo->dist);
                $stmt->bindValue(':section', $this->repo->section);
            }
            $result = $stmt->execute();

            while ($row = $result->fetchArray(SQLITE3_ASSOC)) $datas = $row;
            $repoActualId = $datas['Id'];
            unset($datas);   

            /**
             *  Mise à jour des informations du repo actuellement en place (on change essentiellement sa date, mais aussi la description, la signature gpg...)
             */
            $stmt = $this->repo->db->prepare("UPDATE repos SET Date = :date, Time = :time, Source = :source, Env = :newenv, Description = :description, Signed = :signed, Type = :type WHERE Id = :id");
            $stmt->bindValue(':id', $repoActualId);
            $stmt->bindValue(':date', $this->repo->date);
            $stmt->bindValue(':time', $this->repo->time);
            $stmt->bindValue(':source', $this->repo->source);
            $stmt->bindValue(':newenv', $this->repo->newEnv);
            $stmt->bindValue(':description', $this->repo->description);
            $stmt->bindValue(':signed', $this->repo->signed);
            $stmt->bindValue(':type', $this->repo->type);
            $stmt->execute();

            /**
             *  Mise à jour du repo dans repos_archived (il a été restauré alors on change son status en 'restored')
             */
            if (OS_FAMILY == "Redhat") $stmt = $this->repo->db->prepare("UPDATE repos_archived SET Status = 'restored' WHERE Name = :name AND Source = :source AND Date = :date AND Status = 'active'");
            if (OS_FAMILY == "Debian") $stmt = $this->repo->db->prepare("UPDATE repos_archived SET Status = 'restored' WHERE Name = :name AND Dist = :dist AND Section = :section AND Source = :source AND Date = :date AND Status = 'active'");
            $stmt->bindValue(':name', $this->repo->name);
            $stmt->bindValue(':source', $this->repo->source);
            $stmt->bindValue(':date', $this->repo->date);
            if (OS_FAMILY == "Debian") {
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
         *  Cas 2 : Si le repo qu'on vient de restaurer n'a remplacé aucun repo (comprendre il n'y avait aucun repo en cours sur $repoEnv), alors on mets à jour les infos dans la table repos. Pas d'archivage de quoi que ce soit.
         *  Mise à jour en BDD
         */
        } elseif ($checkIfStillUsed_2 == 0) {
            /**
             *  D'abord on regarde si un repo du même nom fait actuellement partie d'un groupe.
             *  Si c'est le cas alors on récupère l'Id du groupe afin d'ajouter le repo qui sera restauré dans le même groupe.
             */
            if (OS_FAMILY == "Redhat") $stmt = $this->repo->db->prepare("SELECT Id FROM repos WHERE Name = :name AND Status = 'active'");
            if (OS_FAMILY == "Debian") $stmt = $this->repo->db->prepare("SELECT Id FROM repos WHERE Name = :name AND Dist = :dist AND Section = :section AND Status = 'active'");
            $stmt->bindValue(':name', $this->repo->name);
            if (OS_FAMILY == "Debian") {
                $stmt->bindValue(':dist', $this->repo->dist);
                $stmt->bindValue(':section', $this->repo->section);
            }
            $result = $stmt->execute();

            while ($row = $result->fetchArray(SQLITE3_ASSOC)) $datas = $row;
            $idToCheck = $datas['Id'];

            /**
             *  On récupère l'Id du groupe dans lequel l'Id du repo pourrait éventuellement faire partie
             */
            $stmt = $this->repo->db->prepare("SELECT Id_group FROM group_members WHERE Id_repo = :idrepo");
            $stmt->bindValue(':idrepo', $idToCheck);
            $result = $stmt->execute();
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) $datas = $row;

            /**
             *  Si un Id de groupe a été retourné, alors il faudra ajouter le repo restauré dans un groupe
             */
            if (!empty($datas['Id_group'])) {
                $addToGroup = 'yes';
                $id_group = $datas['Id_group'];
            } else {
                $addToGroup = 'no';
            }

            /**
             *  Ajout du repo qui vient d'etre restauré dans la table repos
             */
            if (OS_FAMILY == "Redhat") $stmt = $this->repo->db->prepare("INSERT INTO repos (Name, Source, Env, Date, Time, Description, Signed, Type, Status) VALUES (:name, :source, :newenv, :date, :time, :description, :signed, :type, 'active')");
            if (OS_FAMILY == "Debian") $stmt = $this->repo->db->prepare("INSERT INTO repos (Name, Source, Dist, Section, Env, Date, Time, Description, Signed, Type, Status) VALUES (:name, :source, :dist, :section, :newenv, :date, :time, :description, :signed, :type, 'active')");
            $stmt->bindValue(':name', $this->repo->name);
            $stmt->bindValue(':source', $this->repo->source);
            $stmt->bindValue(':newenv', $this->repo->newEnv);
            $stmt->bindValue(':date', $this->repo->date);
            $stmt->bindValue(':time', $this->repo->time);
            $stmt->bindValue(':description', $this->repo->description);
            $stmt->bindValue(':signed', $this->repo->signed);
            $stmt->bindValue(':type', $this->repo->type);
            if (OS_FAMILY == "Debian") {
                $stmt->bindValue(':dist', $this->repo->dist);
                $stmt->bindValue(':section', $this->repo->section);
            }
            $stmt->execute();

            /**
             *  On récupère l'Id du repo qu'on vient d'insérer, on en aura peut être besoin pour ajouter le repo à un groupe
             */
            $id_repo = $this->repo->db->lastInsertRowID();

            /**
             *  Puis mise à jour de ce même repo de repos_archived (il a été restauré alors on change son status en 'restored')
             */
            if (OS_FAMILY == "Redhat") $stmt = $this->repo->db->prepare("UPDATE repos_archived SET Status = 'restored' WHERE Name = :name AND Source = :source AND Date = :date AND Status = 'active'");
            if (OS_FAMILY == "Debian") $stmt = $this->repo->db->prepare("UPDATE repos_archived SET Status = 'restored' WHERE Name = :name AND Dist = :dist AND Section = :section AND Source = :source AND Date = :date AND Status = 'active'");
            $stmt->bindValue(':name', $this->repo->name);
            $stmt->bindValue(':source', $this->repo->source);
            $stmt->bindValue(':date', $this->repo->date);
            if (OS_FAMILY == "Debian") {
                $stmt->bindValue(':dist', $this->repo->dist);
                $stmt->bindValue(':section', $this->repo->section);
            }
            $stmt->execute();

            $this->log->steplogOK();

            /**
             *  Enfin si le repo qui a été restauré doit être ajouté à un groupe alors on le fait
             */
            if ($addToGroup = 'yes' AND !empty($id_repo) AND !empty($id_group)) {
                $stmt = $this->repo->db->prepare("INSERT INTO group_members ('Id_repo', 'Id_group') VALUES (:id_repo, :id_group)");
                $stmt->bindValue(':id_repo', $id_repo);
                $stmt->bindValue(':id_group', $id_group);
                $stmt->execute();
            }


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
            if (OS_FAMILY == "Redhat") $stmt = $this->repo->db->prepare("SELECT * FROM repos WHERE Name=:name AND Env=:newenv AND Date=:date AND Status = 'active'");
            if (OS_FAMILY == "Debian") $stmt = $this->repo->db->prepare("SELECT * FROM repos WHERE Name=:name AND Dist=:dist AND Section=:section AND Env=:newenv AND Date=:date AND Status = 'active'");
            $stmt->bindValue(':name', $this->repo->name);
            $stmt->bindValue(':newenv', $this->repo->newEnv);
            $stmt->bindValue(':date', $repoActualDate);
            if (OS_FAMILY == "Debian") {
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
            if (OS_FAMILY == "Redhat") {
                if (!rename(REPOS_DIR."/${repoActualDateFormatted}_{$this->repo->name}", REPOS_DIR."/archived_${repoActualDateFormatted}_{$this->repo->name}")) {
                    throw new Exception("impossible d'archiver le miroir en date du <b>$repoActualDateFormatted</b>");
                }
            }
            if (OS_FAMILY == "Debian") {
                if (!rename(REPOS_DIR."/{$this->repo->name}/{$this->repo->dist}/${repoActualDateFormatted}_{$this->repo->section}", REPOS_DIR."/{$this->repo->name}/{$this->repo->dist}/archived_${repoActualDateFormatted}_{$this->repo->section}")) {
                    throw new Exception("impossible d'archiver le miroir en date du <b>$repoActualDateFormatted</b>");
                }
            }

            /**
             *  Mise à jour des informations en BDD
             */

            /**
             *  Maj de la table repos
             */
            $stmt = $this->repo->db->prepare("UPDATE repos SET Date = :date, Time = :time, Source = :source, Env = :newenv, Description = :description, Signed = :signed, Type = :type WHERE Id = :id");
            $stmt->bindValue(':id', $repoActualId);
            $stmt->bindValue(':source', $this->repo->source);
            $stmt->bindValue(':newenv', $this->repo->newEnv);
            $stmt->bindValue(':date', $this->repo->date);
            $stmt->bindValue(':time', $this->repo->time);
            $stmt->bindValue(':description', $this->repo->description);
            $stmt->bindValue(':signed', $this->repo->signed);
            $stmt->bindValue(':type', $this->repo->type);
            $stmt->execute();

            /**
             *  Maj de la table repos_archived
             */

            /**
             *  Récupération de l'ID du repo dans la table repos_archived
             */
            if (OS_FAMILY == "Redhat") $stmt = $this->repo->db->prepare("SELECT Id FROM repos_archived WHERE Name = :name AND Source = :source AND Date = :date AND Status = 'active'");
            if (OS_FAMILY == "Debian") $stmt = $this->repo->db->prepare("SELECT Id FROM repos_archived WHERE Name = :name AND Dist = :dist AND Section = :section AND Source = :source AND Date = :date AND Status = 'active'");
            $stmt->bindValue(':name', $this->repo->name);
            $stmt->bindValue(':source', $this->repo->source);
            $stmt->bindValue(':date', $this->repo->date);
            if (OS_FAMILY == "Debian") {
                $stmt->bindValue(':dist', $this->repo->dist);
                $stmt->bindValue(':section', $this->repo->section);
            }            
            $result = $stmt->execute();

            while ($row = $result->fetchArray(SQLITE3_ASSOC)) $datas = $row;
            $repoArchivedActualId = $datas['Id'];
            

            /**
             *  On mets à jour les données du repos dans repos_archived, il prend les données du repo qui était actuellement en place
             */
            $stmt = $this->repo->db->prepare("UPDATE repos_archived SET Source = :source, Date = :date, Time = :time, Description = :description, Signed = :signed, Type = :type WHERE Id = :id");
            $stmt->bindValue(':id', $repoArchivedActualId);
            $stmt->bindValue(':source', $repoActualSource);
            $stmt->bindValue(':date', $repoActualDate);
            $stmt->bindValue(':time', $repoActualTime);
            $stmt->bindValue(':description', $repoActualDescription);
            $stmt->bindValue(':signed', $repoActualSigned);
            $stmt->bindValue(':type', $repoActualType);
            $stmt->execute(); 

            $this->log->steplogOK("Le miroir en date du <b>$repoActualDateFormatted</b> a été archivé car il n'est plus utilisé par quelconque environnement");
        }
    }
}
?>