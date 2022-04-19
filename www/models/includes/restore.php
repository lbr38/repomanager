<?php
trait restore {
    /**
     *  RESTAURER UN(E) REPO/SECTION ARCHIVÉ(E)
     */
    public function exec_restore() {
        /** 
         *  Récupération des propriétés de l'objet Repo
         */
        $id                = $this->repo->getId();
        $name              = $this->repo->getName();
        if (OS_FAMILY == 'Debian') {
            $dist          = $this->repo->getDist();
            $section       = $this->repo->getSection();
        }
        $targetEnv         = $this->repo->getTargetEnv();
        $date              = $this->repo->getDate();
        $dateFormatted     = $this->repo->getDateFormatted();
        $time              = $this->repo->getTime();
        $source            = $this->repo->getSource();
        $type              = $this->repo->getType();
        $signed            = $this->repo->getSigned();
        $description       = $this->repo->getDescription();

        $case = 0;

        /**
         *  Démarrage de l'opération
         */
        if (OS_FAMILY == "Redhat") $this->startOperation(array('id_repo_target' => "$name"));
                if (OS_FAMILY == "Debian") $this->startOperation(array('id_repo_target' => "$name|$dist|$section"));

        /**
         *  Ajout du PID de ce processus dans le fichier PID
         */
        $this->log->addsubpid(getmypid());

        /**
         *  Lancement du script externe qui va construire le fichier de log principal à partir des petits fichiers de log de chaque étape
         */
        $steps = 2;
        exec("php ".ROOT."/operations/logbuilder.php ".PID_DIR."/{$this->log->pid}.pid {$this->log->location} ".TEMP_DIR."/{$this->log->pid} $steps >/dev/null 2>/dev/null &");
        
        try {

            ob_start();

            /**
             *  1. Génération du tableau récapitulatif de l'opération
             */
            include(ROOT.'/templates/tables/op-restore.inc.php');

            /**
             *  2. On vérifie que le repo renseigné est bien présent dans repos_archived, si oui alors on peut commencer l'opération
             */
            if ($this->repo->existsId($id, 'archived') === false) {
                throw new Exception("il n'existe aucun Id de repo <b>${id}</b>");
            }

            /**
             *  3. On récupère des informations du repo du même nom actuellement en place (si il y en a un) et qui va être remplacé
             */
            try {
                if (OS_FAMILY == "Redhat") $stmt = $this->repo->db->prepare("SELECT * FROM repos WHERE Name = :name and Env = :targetEnv and Status = 'active'");
                if (OS_FAMILY == "Debian") $stmt = $this->repo->db->prepare("SELECT * FROM repos WHERE Name = :name and Dist = :dist and Section = :section and Env = :targetEnv and Status = 'active'");
                $stmt->bindValue(':name', $name);
                $stmt->bindValue(':targetEnv', $targetEnv);
                if (OS_FAMILY == "Debian") {
                    $stmt->bindValue(':dist', $dist);
                    $stmt->bindValue(':section', $section);
                }
                $result = $stmt->execute();

            } catch (Exception $e) {
                Common::dbError($e);
            }

            /**
             *  4. Si le résultat retourné n'est pas vide alors un repo est actuellement en place sur l'environnement ciblé, on récupère alors ses informations
             */
            if ($this->repo->db->isempty($result) === false) {
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) $datas = $row;
        
                $actual_id            = $datas['Id'];
                $actual_name          = $datas['Name'];
                $actual_env           = $datas['Env'];
                $actual_date          = $datas['Date'];
                $actual_dateFormatted = DateTime::createFromFormat('Y-m-d', $actual_date)->format('d-m-Y');
                $actual_time          = $datas['Time'];
                $actual_source        = $datas['Source'];
                $actual_signed        = $datas['Signed'];
                $actual_type          = $datas['Type'];
                $actual_description   = $datas['Description'];

                if (OS_FAMILY == 'Debian') {
                    $actual_dist = $datas['Dist'];
                    $actual_section = $datas['Section'];
                }

                /**
                 *  On vérifie si la version du miroir du repo actuellement en place est utilisée par un autre environnement
                 *  Si c'est le cas alors on n'archive pas
                 *  Sinon on archive
                 */
                try {
                    if (OS_FAMILY == "Redhat") $stmt = $this->repo->db->prepare("SELECT Id FROM repos WHERE Name = :actual_name and Date = :actual_date and Env != :actual_env and Status = 'active'");
                    if (OS_FAMILY == "Debian") $stmt = $this->repo->db->prepare("SELECT Id FROM repos WHERE Name = :actual_name and Dist = :actual_dist and Section = :actual_section and Date = :actual_date and Env != :actual_env and Status = 'active'");
                    $stmt->bindValue(':actual_name', $actual_name);
                    $stmt->bindValue(':actual_env', $actual_env);
                    $stmt->bindValue(':actual_date', $actual_date);
                    if (OS_FAMILY == "Debian") {
                        $stmt->bindValue(':actual_dist', $actual_dist);
                        $stmt->bindValue(':actual_section', $actual_section);
                    }
                    $result = $stmt->execute();

                } catch (Exception $e) {
                    Common::dbError($e);
                }

                /**
                 *  Si le résultat retourné est vide alors la version du miroir n'est plus utilisée et on peut l'archiver
                 */
                if ($this->repo->db->isempty($result) === true) {
                    $case = 1;
                } else {
                    $case = 2;
                }

            } else {
                /**
                 *  Sinon si le résultat retourné est vide cela signifie qu'aucun repo n'est en place sur l'environnement ciblé, il n'y a donc aucune informations à récupérer et il n'y aura donc rien à archiver
                 */
                $case = 3;
            }

            /**
             *  5. Suppression du repo actuellement en place
             */

            /**
             *  Suppression du lien symbolique
             */
            if (OS_FAMILY == "Redhat") {
                if (file_exists(REPOS_DIR."/${name}_${targetEnv}")) {
                    unlink(REPOS_DIR."/${name}_${targetEnv}");
                }
            }
            if (OS_FAMILY == "Debian") {
                if (file_exists(REPOS_DIR."/${name}/${dist}/${section}_${targetEnv}")) {
                    unlink(REPOS_DIR."/${name}/${dist}/${section}_${targetEnv}");
                }
            }

            /**
             *  6. Archivage de la version actuellement en place
             */
            if ($case == 1) {
                /**
                 *  Nouvelle étape ARCHIVAGE
                 */
                $this->log->steplog(1);
                $this->log->steplogInitialize('archive');
                $this->log->steplogTitle('ARCHIVAGE');
                $this->log->steplogLoading();
                
                /**
                 *  Archivage du miroir en date du $actual_date car il n'est plus utilisé par quelconque environnement
                 */
                if (OS_FAMILY == "Redhat") {
                    if (!rename(REPOS_DIR."/${actual_dateFormatted}_${name}", REPOS_DIR."/archived_${actual_dateFormatted}_${name}")) {
                        throw new Exception("impossible d'archiver le miroir en date du <b>$actual_dateFormatted</b>");
                    }
                }
                if (OS_FAMILY == "Debian") {
                    if (!rename(REPOS_DIR."/${name}/${dist}/${actual_dateFormatted}_${section}", REPOS_DIR."/${name}/${dist}/archived_${actual_dateFormatted}_${section}")) {
                        throw new Exception("impossible d'archiver le miroir en date du <b>$actual_dateFormatted</b>");
                    }
                }

                /**
                 *  Maj de la table repos_archived
                 */
                try {
                    $stmt = $this->repo->db->prepare("UPDATE repos_archived SET Source = :source, Date = :date, Time = :time, Description = :description, Signed = :signed, Type = :type WHERE Id = :id");
                    $stmt->bindValue(':id', $id);
                    $stmt->bindValue(':source', $actual_source);
                    $stmt->bindValue(':date', $actual_date);
                    $stmt->bindValue(':time', $actual_time);
                    $stmt->bindValue(':description', $actual_description);
                    $stmt->bindValue(':signed', $actual_signed);
                    $stmt->bindValue(':type', $actual_type);
                    $stmt->execute();

                } catch (Exception $e) {
                    Common::dbError($e);
                }

                $this->log->steplogOK("Le miroir en date du <b>$actual_dateFormatted</b> a été archivé car il n'est plus utilisé par quelconque environnement");
            }

            /**
             *  7. Restauration du repo archivé
             */
            $this->log->steplog(2);
            $this->log->steplogInitialize('restore');
            $this->log->steplogTitle('RESTAURATION');
            $this->log->steplogLoading();

            /**
             *  Renommage du répertoire
             */
            if (OS_FAMILY == "Redhat") {
                if (!rename(REPOS_DIR."/archived_${dateFormatted}_${name}", REPOS_DIR."/${dateFormatted}_${name}")) {
                    throw new Exception("impossible de restaurer le miroir du <b>${dateFormatted}</b>");
                }
            }
            if (OS_FAMILY == "Debian") {
                if (!rename(REPOS_DIR."/${name}/${dist}/archived_${dateFormatted}_${section}", REPOS_DIR."/${name}/${dist}/${dateFormatted}_${section}")) {
                    throw new Exception("impossible de restaurer le miroir du <b>${dateFormatted}</b>");
                }
            }

            /**
             *  Création du lien symbolique
             */
            if (OS_FAMILY == "Redhat") {
                if (!file_exists(REPOS_DIR."/${name}_${targetEnv}")) {
                    exec("cd ".REPOS_DIR." && ln -sfn ${dateFormatted}_${name}/ ${name}_${targetEnv}");
                }
            }
            if (OS_FAMILY == "Debian") {
                if (!file_exists(REPOS_DIR."/${name}/${dist}/${name}_${targetEnv}")) {
                    exec("cd ".REPOS_DIR."/${name}/${dist}/ && ln -sfn ${dateFormatted}_${section}/ ${section}_${targetEnv}");
                }
            }

            /**
             *  8. Maj de la table repos
             *  Dans les cas 1 et 2 il y a déjà une entrée en base de données qu'on met à jour
             *  Dans le cas 3 il n'y a aucune entrée en base de données (puisqu'il n'y a aucun repo actuellement en place sur l'environnement ciblé), donc on ajoute une ligne
             */
            if ($case == 1 or $case == 2) {
                try {
                    $stmt = $this->repo->db->prepare("UPDATE repos SET Date = :date, Time = :time, Source = :source, Env = :env, Description = :description, Signed = :signed, Type = :type WHERE Id = :id");
                    $stmt->bindValue(':id', $actual_id);
                    $stmt->bindValue(':source', $source);
                    $stmt->bindValue(':env', $targetEnv);
                    $stmt->bindValue(':date', $date);
                    $stmt->bindValue(':time', $time);
                    $stmt->bindValue(':description', $description);
                    $stmt->bindValue(':signed', $signed);
                    $stmt->bindValue(':type', $type);
                    $stmt->execute();

                } catch (Exception $e) {
                    Common::dbError($e);
                }
            }

            if ($case == 3) {
                try {
                    if (OS_FAMILY == "Redhat") $stmt = $this->repo->db->prepare("INSERT INTO repos (Name, Source, Env, Date, Time, Description, Signed, Type, Status) VALUES (:name, :source, :env, :date, :time, :description, :signed, :type, 'active')");
                    if (OS_FAMILY == "Debian") $stmt = $this->repo->db->prepare("INSERT INTO repos (Name, Source, Dist, Section, Env, Date, Time, Description, Signed, Type, Status) VALUES (:name, :source, :dist, :section, :env, :date, :time, :description, :signed, :type, 'active')");
                    $stmt->bindValue(':name', $name);
                    $stmt->bindValue(':source', $source);
                    $stmt->bindValue(':env', $targetEnv);
                    $stmt->bindValue(':date', $date);
                    $stmt->bindValue(':time', $time);
                    $stmt->bindValue(':description', $description);
                    $stmt->bindValue(':signed', $signed);
                    $stmt->bindValue(':type', $type);
                    if (OS_FAMILY == "Debian") {
                        $stmt->bindValue(':dist', $dist);
                        $stmt->bindValue(':section', $section);
                    }
                    $stmt->execute();

                } catch (Exception $e) {
                    Common::dbError($e);
                }
            }

            /**
             *  9. Enfin pour les cas 2 et 3 on passe la ligne du repos restauré en 'restored'
             */
            if ($case == 2 or $case == 3) {
                /**
                 *  Maj de la table repos_archived
                 */
                try {
                    $stmt = $this->repo->db->prepare("UPDATE repos_archived SET Status = 'restored ' WHERE Id = :id");
                    $stmt->bindValue(':id', $id);
                    $stmt->execute();

                } catch (Exception $e) {
                    Common::dbError($e);
                }
            }

            /**
             *  Clôture de l'étape RESTAURATION
             */
            $this->log->steplogOK();

            /**
             *  Passage du status de l'opération en done
             */
            $this->setStatus('done');

        } catch (Exception $e) {
            /**
             *  On transmets l'erreur à $this->log->steplogError() qui va se charger de l'afficher en rouge dans le fichier de log
             */
            $this->log->steplogError($e->getMessage());

            /**
             *  Passage du status de l'opération en erreur
             */
            $this->setStatus('error');
        }

        /**
         *  Cloture de l'opération
         */
        $this->log->closeStepOperation();
        $this->closeOperation();
    }
}
?>