<?php
trait env {
    
    public function exec_env() {
        /**
         *  Récupération des propriétés de l'objet Repo
         */
        $id                = $this->repo->getId();
        $name              = $this->repo->getName();
        if (OS_FAMILY == 'Debian') {
            $dist          = $this->repo->getDist();
            $section       = $this->repo->getSection();
        }
        $env               = $this->repo->getEnv();
        $date              = $this->repo->getDate();
        $dateFormatted     = $this->repo->getDateFormatted();
        $time              = $this->repo->getTime();
        $source            = $this->repo->getSource();
        $signed            = $this->repo->getSigned();
        $type              = $this->repo->getType();
        $targetEnv         = $this->repo->getTargetEnv();
        $targetDescription = $this->repo->getTargetDescription();

        $case = 0;

        if ($this->type == 'manual') $this->startOperation(array('id_repo_source' => $id));
        if ($this->type == 'plan')   $this->startOperation(array('id_repo_source' => $id, 'id_plan' => $this->getId_plan()));

        /**
         *  Ajout du PID de ce processus dans le fichier PID
         */
        $this->log->addsubpid(getmypid());

        /**
         *  Lancement du script externe qui va construire le fichier de log principal à partir des petits fichiers de log de chaque étape
         */
        $steps = 3;
        exec("php ".ROOT."/operations/logbuilder.php ".PID_DIR."/{$this->log->pid}.pid {$this->log->location} ".TEMP_DIR."/{$this->log->pid} $steps >/dev/null 2>/dev/null &");
        
        try {

            ob_start();

            /**
             *  1. Génération du tableau récapitulatif de l'opération
             */
            include(ROOT.'/templates/tables/op-env.inc.php');

            $this->log->steplog(1);
            $this->log->steplogInitialize('createEnv');
            $this->log->steplogTitle("CREATION DE L'ENVIRONNEMENT ".Common::envtag($targetEnv)."");
            $this->log->steplogLoading();

            /**
             *  2. On vérifie si le repo source existe
             */
            if (OS_FAMILY == "Redhat") {
                if ($this->repo->existsEnv($name, $env) === false) {
                    throw new Exception('ce repo n\'existe pas en '.Common::envtag($env));
                }
            }
            if (OS_FAMILY == "Debian") {
                if ($this->repo->section_existsEnv($name, $dist, $section, $env) === false) {
                    throw new Exception('cette section n\'existe pas en '.Common::envtag($env));
                }
            }

            /**
             *  3. On vérifie qu'un repo cible de même env et de même date n'existe pas déjà
             */
            if (OS_FAMILY == "Redhat") {
                if ($this->repo->existsDateEnv($name, $date, $targetEnv) === true) {
                    throw new Exception("un repo ".Common::envtag($targetEnv)." existe déjà au <b>".DateTime::createFromFormat('Y-m-d', $date)->format('d-m-Y')."</b>");
                }
            }
            if (OS_FAMILY == "Debian") {
                if ($this->repo->section_existsDateEnv($name, $dist, $section, $date, $targetEnv) === true) {
                    throw new Exception("une section ".Common::envtag($targetEnv)." existe déjà au <b>".DateTime::createFromFormat('Y-m-d', $date)->format('d-m-Y')."</b>");
                }
            }
            
            /**
             *  Si le repo source est membre d'un groupe, on récupère l'Id du groupe afin d'insérer le repo cible dans le même groupe
             */
            $targetGroup = $this->repo->db_getGroup($id);

            /**
             *  4. Si on n'a pas transmis de description, on va conserver celle actuellement en place sur $targetEnv si existe.
             *  Cependant si il n'y a pas de description ou qu'aucun repo n'existe actuellement dans l'env $targetEnv alors celle-ci restera vide
             */
            if (empty($targetDescription)) {
                try {
                    if (OS_FAMILY == "Redhat") $stmt = $this->db->prepare("SELECT Description FROM repos WHERE Name = :name AND Env = :targetenv AND Status = 'active'");
                    if (OS_FAMILY == "Debian") $stmt = $this->db->prepare("SELECT Description FROM repos WHERE Name = :name AND Dist = :dist AND Section = :section AND Env = :targetenv AND Status = 'active'");
                    $stmt->bindValue(':name', $name);
                    $stmt->bindValue(':targetenv', $targetEnv);
                    if (OS_FAMILY == "Debian") {
                        $stmt->bindValue(':dist', $dist);
                        $stmt->bindValue(':section', $section);
                    }
                    $result = $stmt->execute();

                } catch(Exception $e) {
                    Common::dbError($e);
                }

                /**
                 *  La description récupérée peut être vide, du coup on précise le paramètre 'ignore-null' afin que la fonction fetch() ne s'arrête pas si le résultat est vide
                 */
                $result = $this->repo->db->fetch($result, 'ignore-null');

                if (!empty($result))
                    $targetDescription = $result['Description'];
                else
                    $targetDescription = '';
            }

            /**
             *  5. Dernière vérif : on vérifie que le repo n'est pas déjà dans l'environnement souhaité (par exemple fait par quelqu'un d'autre), dans ce cas on annule l'opération
             */
            if (OS_FAMILY == "Redhat") {
                if ($this->repo->existsDateEnv($name, $date, $targetEnv) === true) {
                    throw new Exception("ce repo est déjà en ".Common::envtag($targetEnv)." au <b>${dateFormatted}</b>");
                }
            }
            if (OS_FAMILY == "Debian") {
                if ($this->repo->section_existsDateEnv($name, $dist, $section, $date, $targetEnv) === true) {
                    throw new Exception("cette section est déjà en ".Common::envtag($targetEnv)." au <b>${dateFormatted}</b>");
                }
            }

            /**
             *  6. Traitement
             *  Deux cas possibles :
             *  - ce repo/section n'avait pas de version dans l'environnement cible, on crée simplement un lien symbo
             *  - ce repo/section avait déjà une version dans l'environnement cible, on modifie le lien symbo et on passe la version précédente en archive
             */
            if (OS_FAMILY == "Redhat") {

                /**
                 *  Cas 1 : pas de version déjà en $targetEnv
                 */
                if ($this->repo->existsEnv($name, $targetEnv) === false) {
                    /**
                     *  On indique ne cas dans lequel on se trouve (sera utile plus bas pour l'ajout du repo à un groupe)
                     */
                    $case = 1;

                    /**
                     *  Suppression du lien symbolique (on sait jamais si il existe)
                     */
                    if (file_exists(REPOS_DIR."/${name}_${targetEnv}")) unlink(REPOS_DIR."/${name}_${targetEnv}");
        
                    /**
                     *  Création du lien symbolique
                     */
                    exec("cd ".REPOS_DIR."/ && ln -sfn ${dateFormatted}_${name}/ ${name}_${targetEnv}");
        
                    /**
                     *  Mise à jour en BDD
                     */
                    try {
                        $stmt = $this->db->prepare("INSERT INTO repos (Name, Source, Env, Date, Time, Description, Signed, Type, Status) VALUES (:name, :source, :newenv, :date, :time, :description, :signed, :type, 'active')");
                        $stmt->bindValue(':name', $name);
                        $stmt->bindValue(':source', $source);
                        $stmt->bindValue(':newenv', $targetEnv);
                        $stmt->bindValue(':date', $date);
                        $stmt->bindValue(':time', $time);
                        $stmt->bindValue(':description', $targetDescription);
                        $stmt->bindValue(':signed', $signed);
                        $stmt->bindValue(':type', $type);
                        $stmt->execute();
                    } catch(Exception $e) {
                        Common::dbError($e);
                    }
                    unset($stmt);

                    /**
                     *  Récupération de l'ID du repos précédemment inséré car on va en avoir besoin pour l'ajouter au même groupe que le repo sur $env
                     */
                    $targetId = $this->db->lastInsertRowID();

                    /**
                     *  Clôture de l'étape en cours
                     */
                    $this->log->steplogOK();

                /**
                 *  Cas 2 : Il y a déjà une version en $targetEnv qui va donc passer en archive. Modif du lien symbo + passage de la version précédente en archive :
                 */
                } else {

                    /**
                     *  Suppression du lien symbolique
                     */
                    if (file_exists(REPOS_DIR."/${name}_${targetEnv}")) unlink(REPOS_DIR."/${name}_${targetEnv}");

                    /**
                     *  Création du lien symbolique
                     */
                    exec("cd ".REPOS_DIR."/ && ln -sfn ${dateFormatted}_${name}/ ${name}_${targetEnv}");

                    /**
                     *  Clôture de l'étape en cours
                     */
                    $this->log->steplogOK();

                    /**
                     *  Passage de l'ancienne version de $targetEnv en archive
                     *  Pour cela on récupère la date et la description du repo qui va être archivé
                     */
                    try {
                        $stmt = $this->db->prepare("SELECT Date, Description FROM repos WHERE Name = :name AND Env = :newenv AND Status = 'active'");
                        $stmt->bindValue(':name', $name);
                        $stmt->bindValue(':newenv', $targetEnv);
                        $result = $stmt->execute();
                    } catch(Exception $e) {
                        Common::dbError($e);
                    }

                    $result = $this->repo->db->fetch($result);
                    $to_archive_date          = $result['Date'];
                    $to_archive_dateFormatted = DateTime::createFromFormat('Y-m-d', $to_archive_date)->format('d-m-Y');
                    $to_archive_description   = $result['Description'];

                    /**
                     *  Création d'un nouveau div dans le log pour l'étape d'archivage
                     */
                    $this->log->steplog(2);
                    $this->log->steplogInitialize('archiveRepo');
                    $this->log->steplogTitle("ARCHIVAGE DU MIROIR DU $to_archive_dateFormatted");
                    $this->log->steplogLoading();

                    /**
                     *  Renommage du répertoire en archived_
                     *  Si un répertoire du même nom existe déjà alors on le supprime
                     */
                    if (is_dir(REPOS_DIR."/archived_${to_archive_dateFormatted}_${name}")) exec("rm -rf '".REPOS_DIR."/archived_${to_archive_dateFormatted}_${name}'");
                    if (!rename(REPOS_DIR."/${to_archive_dateFormatted}_${name}", REPOS_DIR."/archived_${to_archive_dateFormatted}_${name}")) {
                        throw new Exception("un problème est survenu lors du passage de l'ancienne version du <b>$to_archive_dateFormatted</b> en archive");
                    }

                    /**
                     *  Mise à jour de la BDD
                     */
                    try {
                        $stmt = $this->db->prepare("UPDATE repos SET Date = :date, Time = :time, Description = :description, Signed = :signed WHERE Name = :name AND Env = :newenv AND Date = :to_archive_date AND Status = 'active'");
                        $stmt->bindValue(':date', $date);
                        $stmt->bindValue(':time', $time);
                        $stmt->bindValue(':description', $targetDescription);
                        $stmt->bindValue(':signed', $signed);
                        $stmt->bindValue(':name', $name);
                        $stmt->bindValue(':newenv', $targetEnv);
                        $stmt->bindValue(':to_archive_date', $to_archive_date);
                        $stmt->execute();
                    } catch(Exception $e) {
                        Common::dbError($e);
                    }

                    /**
                     *  Insertion du repo archivé dans repos_archived
                     */
                    try {
                        $stmt = $this->db->prepare("INSERT INTO repos_archived (Name, Source, Date, Time, Description, Signed, Type, Status) VALUES (:name, :source, :date, :time, :description, :signed, :type, 'active')");
                        $stmt->bindValue(':name', $name);
                        $stmt->bindValue(':source', $source);
                        $stmt->bindValue(':date', $to_archive_date);
                        $stmt->bindValue(':time', $time);
                        $stmt->bindValue(':decription', $to_archive_description);
                        $stmt->bindValue(':signed', $signed);
                        $stmt->bindValue(':type', $type);
                        $stmt->execute();
                    } catch(Exception $e) {
                        Common::dbError($e);
                    }

                    /**
                     *  Application des droits sur la section archivée
                     */
                    exec("find ".REPOS_DIR."/archived_${to_archive_dateFormatted}_${name}/ -type f -exec chmod 0660 {} \;");
                    exec("find ".REPOS_DIR."/archived_${to_archive_dateFormatted}_${name}/ -type d -exec chmod 0770 {} \;");

                    /**
                     *  Clôture de l'étape en cours
                     */
                    $this->log->steplogOK();
                }
            }

            if (OS_FAMILY == "Debian") {
                /**
                 *  Cas 1 : pas de version déjà en $targetEnv
                 */
                if ($this->repo->section_existsEnv($name, $dist, $section, $targetEnv) === false) {
                    /**
                     *  On indique ne cas dans lequel on se trouve (sera utile plus bas pour l'ajout du repo à un groupe)
                     */
                    $case = 1;

                    /**
                     *  Suppression du lien symbolique (on ne sait jamais si il existe)
                     */
                    if (file_exists(REPOS_DIR."/${name}/${dist}/${section}_${targetEnv}")) unlink(REPOS_DIR."/${name}/${dist}/${section}_${targetEnv}");

                    /**
                     *  Création du lien symbolique
                     */
                    exec("cd ".REPOS_DIR."/${name}/${dist}/ && ln -sfn ${dateFormatted}_${section}/ ${section}_${targetEnv}");

                    /**
                     *  Mise à jour en BDD
                     */
                    try {
                        $stmt = $this->db->prepare("INSERT INTO repos (Name, Source, Dist, Section, Env, Date, Time, Description, Signed, Type, Status) VALUES (:name, :source, :dist, :section, :newenv, :date, :time, :description, :signed, :type, 'active')");
                        $stmt->bindValue(':name', $name);
                        $stmt->bindValue(':source', $source);
                        $stmt->bindValue(':dist', $dist);
                        $stmt->bindValue(':section', $section);
                        $stmt->bindValue(':newenv', $targetEnv);
                        $stmt->bindValue(':date', $date);
                        $stmt->bindValue(':time', $time);
                        $stmt->bindValue(':description', $targetDescription);
                        $stmt->bindValue(':signed', $signed);
                        $stmt->bindValue(':type', $type);
                        $stmt->execute();
                    } catch(Exception $e) {
                        Common::dbError($e);
                    }

                    /**
                     *  Récupération de l'ID du repos précédemment inséré car on va en avoir besoin pour l'ajouter au même groupe que le repo sur $env
                     */
                    $targetId = $this->db->lastInsertRowID();

                    /**
                     *  Clôture de l'étape en cours
                     */
                    $this->log->steplogOK();
                
                /**
                 *  Cas 2 : Il y a déjà une version en $targetEnv qui va donc passer en archive. Modif du lien symbo + passage de la version précédente en archive :
                 */
                } else {

                    /**
                     *  Suppression du lien symbolique
                     */
                    if (file_exists(REPOS_DIR."/${name}/${dist}/${section}_${targetEnv}")) unlink(REPOS_DIR."/${name}/${dist}/${section}_${targetEnv}");

                    /**
                     *  Création du lien symbolique
                     */
                    exec("cd ".REPOS_DIR."/${name}/${dist}/ && ln -sfn ${dateFormatted}_${section}/ ${section}_${targetEnv}");

                    /**
                     *  Clôture de l'étape en cours
                     */
                    $this->log->steplogOK();

                    /**
                     *  Passage de l'ancienne version de $targetEnv en archive
                     *  Pour cela on récupère la date et la description du repo qui va être archivé
                     */
                    try {
                        $stmt = $this->db->prepare("SELECT Date, Description FROM repos WHERE Name = :name AND Dist = :dist AND Section = :section AND Env = :newenv AND Status = 'active'");
                        $stmt->bindValue(':name', $name);
                        $stmt->bindValue(':dist', $dist);
                        $stmt->bindValue(':section', $section);
                        $stmt->bindValue(':newenv', $targetEnv);
                        $result = $stmt->execute();
                    } catch(Exception $e) {
                        Common::dbError($e);
                    }

                    $result = $this->repo->db->fetch($result);
                    $to_archive_date          = $result['Date'];
                    $to_archive_dateFormatted = DateTime::createFromFormat('Y-m-d', $to_archive_date)->format('d-m-Y');
                    $to_archive_description   = $result['Description'];

                    /**
                     *  Création d'un nouveau div dans le log pour l'étape d'archivage
                     */
                    $this->log->steplog(2);
                    $this->log->steplogInitialize('archiveRepo');
                    $this->log->steplogTitle("ARCHIVAGE DU MIROIR DU $to_archive_dateFormatted");
                    $this->log->steplogLoading();

                    /**
                     *  Renommage du répertoire en archived_
                     *  Si un répertoire du même nom existe déjà alors on le supprime
                     */
                    if (is_dir(REPOS_DIR."/${name}/${dist}/archived_${to_archive_dateFormatted}_${section}")) exec("rm -rf '".REPOS_DIR."/${name}/${dist}/archived_${to_archive_dateFormatted}_${section}'");
                    if (!rename(REPOS_DIR."/${name}/${dist}/${to_archive_dateFormatted}_${section}", REPOS_DIR."/${name}/${dist}/archived_${to_archive_dateFormatted}_${section}")) {
                        throw new Exception("un problème est survenu lors du passage de l'ancienne version du <b>$to_archive_dateFormatted</b> en archive");
                    }

                    /**
                     *  Mise à jour de la BDD
                     */
                    try {
                        $stmt = $this->db->prepare("UPDATE repos SET Date = :date, Time = :time, Description = :description, Signed = :signed WHERE Name = :name AND Dist = :dist AND Section = :section AND Env = :newenv AND Date = :to_archive_date AND Status = 'active'");
                        $stmt->bindValue(':date', $date);
                        $stmt->bindValue(':time', $time);
                        $stmt->bindValue(':description', $targetDescription);
                        $stmt->bindValue(':signed', $signed);
                        $stmt->bindValue(':name', $name);
                        $stmt->bindValue(':dist', $dist);
                        $stmt->bindValue(':section', $section);
                        $stmt->bindValue(':newenv', $targetEnv);
                        $stmt->bindValue(':to_archive_date', $to_archive_date);
                        $stmt->execute();
                    } catch(Exception $e) {
                        Common::dbError($e);
                    }

                    /**
                     *  Insertion du repo archivé dans repos_archived
                     */
                    try {
                        $stmt = $this->db->prepare("INSERT INTO repos_archived (Name, Source, Dist, Section, Date, Time, Description, Signed, Type, Status) VALUES (:name, :source, :dist, :section, :olddate, :time, :description, :signed, :type, 'active')");
                        $stmt->bindValue(':name', $name);
                        $stmt->bindValue(':source', $source);
                        $stmt->bindValue(':dist', $dist);
                        $stmt->bindValue(':section', $section);
                        $stmt->bindValue(':olddate', $to_archive_date);
                        $stmt->bindValue(':time', $time);
                        $stmt->bindValue(':description', $to_archive_description);
                        $stmt->bindValue(':signed', $signed);
                        $stmt->bindValue(':type', $type);
                        $stmt->execute();
                    } catch(Exception $e) {
                        Common::dbError($e);
                    }

                    /**
                     *  Application des droits sur la section archivée
                     */
                    exec("find ".REPOS_DIR."/${name}/${dist}/archived_${to_archive_dateFormatted}_${section}/ -type f -exec chmod 0660 {} \;");
                    exec("find ".REPOS_DIR."/${name}/${dist}/archived_${to_archive_dateFormatted}_${section}/ -type d -exec chmod 0770 {} \;");

                    /**
                     *  Clôture de l'étape en cours
                     */
                    $this->log->steplogOK();
                }
            }

            $this->log->steplog(3);
            $this->log->steplogInitialize('finalizeRepo');
            $this->log->steplogTitle("FINALISATION");
            $this->log->steplogLoading();

            /**
             *  7. On ajoute le nouvel environnement de repo au même groupe que le repo sur $env 
             *  On traite ce cas uniquement si on est passé par le Cas n°1
             */
            if (!empty($targetGroup) AND $case == 1) {
                try {
                    $stmt = $this->db->prepare("INSERT INTO group_members (Id_repo, Id_group) VALUES (:repoid, :groupid)");
                    $stmt->bindValue(':repoid', $targetId);
                    $stmt->bindValue(':groupid', $targetGroup);
                    $stmt->execute();
                } catch(Exception $e) {
                    Common::dbError($e);
                }
            }

            /**
             *  8. Application des droits sur le repo/la section modifié
             */
            if (OS_FAMILY == "Redhat") {
                exec("find ".REPOS_DIR."/${dateFormatted}_${name}/ -type f -exec chmod 0660 {} \;");
                exec("find ".REPOS_DIR."/${dateFormatted}_${name}/ -type d -exec chmod 0770 {} \;");
            }

            if (OS_FAMILY == "Debian") {
                exec("find ".REPOS_DIR."/${name}/${dist}/${dateFormatted}_${section}/ -type f -exec chmod 0660 {} \;");
                exec("find ".REPOS_DIR."/${name}/${dist}/${dateFormatted}_${section}/ -type d -exec chmod 0770 {} \;");          
            }

            /**
             *  Clôture de l'étape en cours
             */
            $this->log->steplogOK();

            $this->repo->cleanArchives();

            /**
             *  Passage du status de l'opération en done
             */
            $this->setStatus('done');

        } catch(Exception $e) {
            /**
             *  On transmets l'erreur à $this->log->steplogError() qui va se charger de l'afficher en rouge dans le fichier de log
             */
            $this->log->steplogError($e->getMessage());

            /**
             *  Passage du status de l'opération en erreur
             */
            $this->setStatus('error');

            /**
             *  Cloture de l'opération
             */
            $this->log->closeStepOperation();
            $this->closeOperation();

            /**
             *  Cas où cette fonction est lancée par une planification : la planif attend un retour, on lui renvoie false pour lui indiquer qu'il y a eu une erreur
             */
            return false;
        }

        /**
         *  Cloture de l'opération
         */
        $this->log->closeStepOperation();
        $this->closeOperation();
    }
}
?>