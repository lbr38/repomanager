<?php
trait duplicate {
    /**
     *  DUPLIQUER UN REPO/SECTION
     */
    public function exec_duplicate() { 
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
        $type              = $this->repo->getType();
        $targetGpgResign   = $this->repo->getSigned();
        $targetName        = $this->repo->getTargetName();
        $targetGroup       = $this->repo->getTargetGroup();
        $targetDescription = $this->repo->getTargetDescription();
        /**
         *  On concatène tous les paramètres dans un array car on en aura besoin pour les transmettre à certaines fonctions
         */
        if (OS_FAMILY == 'Redhat') $params = compact('id', 'name', 'source', 'date', 'dateFormatted', 'time', 'targetName', 'targetGroup', 'targetDescription', 'targetGpgResign');
        if (OS_FAMILY == 'Debian') $params = compact('id', 'name', 'source', 'dist', 'section', 'date', 'dateFormatted', 'time', 'targetName', 'targetGroup', 'targetDescription', 'targetGpgResign');

        /**
         *  Démarrage de l'opération
         */
        if (OS_FAMILY == "Redhat") $this->startOperation(array('id_repo_source' => $id, 'id_repo_target' => "$targetName"));
        if (OS_FAMILY == "Debian") $this->startOperation(array('id_repo_source' => $id, 'id_repo_target' => "$targetName|$dist|$section"));

        /**
         *  Ajout du PID de ce processus dans le fichier PID
         */
        $this->log->addsubpid(getmypid());

        /**
         *  Lancement du script externe qui va construire le fichier de log principal à partir des petits fichiers de log de chaque étape
         */
        $steps = 4;
        exec("php ".ROOT."/operations/logbuilder.php ".PID_DIR."/{$this->log->pid}.pid {$this->log->location} ".TEMP_DIR."/{$this->log->pid} $steps >/dev/null 2>/dev/null &");
        
        try {

            ob_start();

            /**
             *  1. Génération du tableau récapitulatif de l'opération
             */
            include(ROOT.'/templates/tables/op-duplicate.inc.php');

            $this->log->steplog(1);
            $this->log->steplogInitialize('duplicate');
            $this->log->steplogTitle('DUPLICATION');
            $this->log->steplogLoading();

            /** 
             *  2. On vérifie que le nouveau nom du repo n'est pas vide
             */
            if (empty($targetName)) throw new Exception('le nom du nouveau est vide');

            /**
             *  3. Vérifications : 
             *  On vérifie que le repo/section source (celui qui sera dupliqué) existe bien
             *  On vérifie que le nouveau nom du repo n'existe pas déjà
             */
            if (OS_FAMILY == "Redhat") {
                if ($this->repo->exists($name) === false) throw new Exception("le repo à dupliquer n'existe pas");
            }
            if (OS_FAMILY == "Debian") {
                if ($this->repo->section_exists($name, $dist, $section) === false) throw new Exception("le repo à dupliquer n'existe pas");
            }
            if ($this->repo->exists($targetName) === true) throw new Exception("un repo <b>$targetName</b> existe déjà");

            /**
             *  4. Création du nouveau répertoire avec le nouveau nom du repo :
             */
            if (OS_FAMILY == "Redhat") {
                if (!file_exists(REPOS_DIR."/${dateFormatted}_${targetName}")) {
                    if (!mkdir(REPOS_DIR."/${dateFormatted}_${targetName}", 0770, true)) throw new Exception("impossible de créer le répertoire du nouveau repo <b>${targetName}</b>");
                }
            }
            if (OS_FAMILY == "Debian") {
                if (!file_exists(REPOS_DIR."/${targetName}/${dist}/${dateFormatted}_${section}")) {
                    if (!mkdir(REPOS_DIR."/${targetName}/${dist}/${dateFormatted}_${section}", 0770, true)) throw new Exception("impossible de créer le répertoire du nouveau repo <b>${targetName}</b>");
                }
            }

            /**
             *  5. Copie du contenu du repo/de la section
             *  Anti-slash devant la commande cp pour forcer l'écrasement
             */
            if (OS_FAMILY == "Redhat") exec("\cp -r ".REPOS_DIR."/${dateFormatted}_${name}/* ".REPOS_DIR."/${dateFormatted}_${targetName}/", $output, $result);
            if (OS_FAMILY == "Debian") exec("\cp -r ".REPOS_DIR."/${name}/${dist}/${dateFormatted}_${section}/* ".REPOS_DIR."/${targetName}/${dist}/${dateFormatted}_${section}/", $output, $result);
            if ($result != 0) throw new Exception('impossible de copier les données du repo source vers le nouveau repo');

            /**
             *   6. Création du lien symbolique
             */
            if (OS_FAMILY == "Redhat") exec("cd ".REPOS_DIR."/ && ln -sfn ${dateFormatted}_${targetName}/ ${targetName}_${env}", $output, $result);            
            if (OS_FAMILY == "Debian") exec("cd ".REPOS_DIR."/${targetName}/${dist}/ && ln -sfn ${dateFormatted}_${section}/ ${section}_${env}", $output, $result);
            if ($result != 0) throw new Exception('impossible de créer le nouveau repo');

            $this->log->steplogOK();

            /**
             *  Sur Debian il faut reconstruire les données du repo avec le nouveau nom du repo.
             */
            if (OS_FAMILY == "Debian") {
                /**
                 *  Pour les besoins de la fonction op_createRepo(), il faut que le nom du repo à créer soit dans $name.
                 *  Du coup on backup temporairement le nom actuel et on le remplace par $targetName
                 */
                $backupName = $name;
                $name = $targetName;

                $this->log->steplog(2);
                $this->op_createRepo($params); // Si une exception est lancée au cours de la fonction op_createRepo() alors elle sera capturée par le try catch actuellement en place dans ce fichier (duplicate.php)

                /**
                 *  On remets en place le nom tel qu'il était
                 */
                $name = $backupName;
            }

            $this->log->steplog(3);
            $this->log->steplogInitialize('finalize');
            $this->log->steplogTitle('FINALISATION');
            $this->log->steplogLoading();

            /**
             *  8. Insertion en BDD du nouveau repo
             */
            try {
                if (OS_FAMILY == "Redhat") $stmt = $this->db->prepare("INSERT INTO repos (Name, Source, Env, Date, Time, Description, Signed, Type, Status) VALUES (:newname, :source, :env, :date, :time, :description, :signed, :type, 'active')");
                if (OS_FAMILY == "Debian") $stmt = $this->db->prepare("INSERT INTO repos (Name, Source, Dist, Section, Env, Date, Time, Description, Signed, Type, Status) VALUES (:newname, :source, :dist, :section, :env, :date, :time, :description, :signed, :type, 'active')");
                $stmt->bindValue(':newname', $targetName);
                $stmt->bindValue(':source', $source);
                $stmt->bindValue(':env', $env);
                $stmt->bindValue(':date', $date);
                $stmt->bindValue(':time', $time);
                $stmt->bindValue(':description', $targetDescription);
                $stmt->bindValue(':signed', $targetGpgResign);
                $stmt->bindValue(':type', $type);
                if (OS_FAMILY == "Debian") {
                    $stmt->bindValue(':dist', $dist);
                    $stmt->bindValue(':section', $section);
                }
                $stmt->execute();
            } catch(Exception $e) {
                Common::dbError($e);
            }

            /**
             *  Récupération de l'Id du repo qui vient d'être créé
             */
            $targetId = $this->db->lastInsertRowID();

            /**
             *  9. Application des droits sur le nouveau repo créé
             */
            if (OS_FAMILY == "Redhat") exec("find ".REPOS_DIR."/${dateFormatted}_${targetName}/ -type f -exec chmod 0660 {} \;");
            if (OS_FAMILY == "Debian") exec("find ".REPOS_DIR."/${targetName}/ -type d -exec chmod 0770 {} \;");
            exec("chown -R ".WWW_USER.":repomanager ".REPOS_DIR."/${targetName}/");

            $this->log->steplogOK();

            /**
             *  10. Ajout de la section à un groupe si un groupe a été renseigné
             */
            if (!empty($targetGroup)) {
                $this->log->steplog(4);
                $this->log->steplogInitialize('addToGroup');
                $this->log->steplogTitle('AJOUT A UN GROUPE');
                $this->log->steplogLoading();

                /**
                 *  Ajout du repo créé au groupe spécifié
                 */
                $this->repo->addToGroup($targetId, $targetGroup);

                $this->log->steplogOK();
            }

            /**
             *  11. Génération du fichier de conf repo en local (ces fichiers sont utilisés pour les profils)
             *  Pour les besoins de la fonction, on set $name = $targetName (sinon ça va générer un fichier pour le repo source, ce qu'on ne veut pas)
             */
            $name = $targetName;
            $this->repo->generateConf('default');

            /**
             *  Mise à jour de la tâche d'opération en BDD : on indique l'ID du repo qui vient d'être ajouté en BDD, à la place de son nom complet.
             */
            $this->db_update_idrepo_target($targetId);

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
        }

        /**
         *  Cloture de l'opération
         */
        $this->log->closeStepOperation();
        $this->closeOperation();
    }
}
?>