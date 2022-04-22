<?php
trait newLocalRepo {
    /**
     *  NOUVEAU REPO LOCAL
     */
    public function exec_newLocalRepo() {
        /**
         *  Récupération des propriétés de l'objet Repo
         */
        $name              = $this->repo->getName();
        $date              = date("Y-m-d");
        $dateFormatted     = date("d-m-Y");
        $time              = date("H:i");
        $env               = DEFAULT_ENV;
        $source            = $this->repo->getSource();
        $type              = $this->repo->getType();
        $targetDescription = $this->repo->getTargetDescription();
        $targetGroup       = $this->repo->getTargetGroup();
        if (OS_FAMILY == 'Debian') {
            $dist          = $this->repo->getDist();
            $section       = $this->repo->getSection();
        }
 
        /**
         *  Démarrage de l'opération
         */
        if (OS_FAMILY == "Redhat") $this->startOperation(array('id_repo_target' => $name));
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
            include(ROOT.'/templates/tables/op-new-local.inc.php');

            $this->log->steplog(1);
            $this->log->steplogInitialize('createRepo');
            $this->log->steplogTitle('CREATION DU REPO');
            $this->log->steplogLoading();

            /** 
             *  2. On vérifie que le nom du repo n'est pas vide
             */
            if (empty($name)) throw new Exception('le nom du repo ne peut être vide');

            /**
             *  3. Création du répertoire avec le nom du repo, et les sous-répertoires permettant d'acceuillir les futurs paquets
             */
            if (OS_FAMILY == "Redhat") {
                if (!file_exists(REPOS_DIR."/${dateFormatted}_${name}/Packages")) {
                    if (!mkdir(REPOS_DIR."/${dateFormatted}_${name}/Packages", 0770, true)) throw new Exception("impossible de créer le répertoire du repo ${name}");
                }
            }
            if (OS_FAMILY == "Debian") {
                if (!file_exists(REPOS_DIR."/${name}/${dist}/${dateFormatted}_${section}/pool/${section}")) {
                    if (!mkdir(REPOS_DIR."/${name}/${dist}/${dateFormatted}_${section}/pool/${section}", 0770, true)) throw new Exception('impossible de créer le répertoire de la section');
                }
            }

            /**
             *   4. Création du lien symbolique
             */
            if (OS_FAMILY == "Redhat") exec("cd ".REPOS_DIR."/ && ln -sfn ${dateFormatted}_${name}/ ${name}_${env}", $output, $result);            
            if (OS_FAMILY == "Debian") exec("cd ".REPOS_DIR."/${name}/${dist}/ && ln -sfn ${dateFormatted}_${section}/ ${section}_${env}", $output, $result);
            if ($result != 0) throw new Exception('impossible de créer le repo');

            /**
             *  5. Insertion en BDD du nouveau repo
             */
            try {
                if (OS_FAMILY == "Redhat") $stmt = $this->repo->db->prepare("INSERT INTO repos (Name, Source, Env, Date, Time, Description, Signed, Type, Status) VALUES (:name, :source, :env, :date, :time, :description, :signed, 'local', 'active')");
                if (OS_FAMILY == "Debian") $stmt = $this->repo->db->prepare("INSERT INTO repos (Name, Source, Dist, Section, Env, Date, Time, Description, Signed, Type, Status) VALUES (:name, :source, :dist, :section, :env, :date, :time, :description, :signed, 'local', 'active')");
                $stmt->bindValue(':name', $name);
                $stmt->bindValue(':source', $name); // C'est un repo local, la source porte alors le même nom que le repo
                $stmt->bindValue(':env', $env);
                $stmt->bindValue(':date', $date);
                $stmt->bindValue(':time', $time);
                $stmt->bindValue(':description', $targetDescription);
                $stmt->bindValue(':signed', 'no');
                if (OS_FAMILY == "Debian") {
                    $stmt->bindValue(':dist', $dist);
                    $stmt->bindValue(':section', $section);
                }
                $stmt->execute();
            } catch (Exception $e) {
                Common::dbError($e);
            }
            unset($stmt);

            /**
             *  6. Application des droits sur le nouveau repo créé
             */
            exec("find ".REPOS_DIR."/${name}/ -type f -exec chmod 0660 {} \;");
            exec("find ".REPOS_DIR."/${name}/ -type d -exec chmod 0770 {} \;");
            exec("chown -R ".WWW_USER.":repomanager ".REPOS_DIR."/${name}/");

            $this->log->steplogOK();

            /**
             *  7. Ajout de la section à un groupe si un groupe a été renseigné
             */
            if (!empty($targetGroup)) {
                $this->log->steplog(2);
                $this->log->steplogInitialize('createRepo');
                $this->log->steplogTitle('AJOUT A UN GROUPE');
                $this->log->steplogLoading();

                try {
                    if (OS_FAMILY == "Redhat") $stmt = $this->repo->db->prepare("SELECT repos.Id AS repoId, groups.Id AS groupId FROM repos, groups WHERE repos.Name=:name and repos.Status = 'active' and groups.Name=:groupname");
                    if (OS_FAMILY == "Debian") $stmt = $this->repo->db->prepare("SELECT repos.Id AS repoId, groups.Id AS groupId FROM repos, groups WHERE repos.Name=:name and repos.Dist=:dist and repos.Section=:section and repos.Status = 'active' and groups.Name=:groupname");
                    $stmt->bindValue(':name', $name);
                    if (OS_FAMILY == "Debian") {
                        $stmt->bindValue(':dist', $dist);
                        $stmt->bindValue(':section', $section);
                    }
                    $stmt->bindValue(':groupname', $targetGroup);
                    $result = $stmt->execute();
                } catch (Exception $e) {
                    Common::dbError($e);
                }

                while ($data = $result->fetchArray(SQLITE3_ASSOC)) {
                    $repoId = $data['repoId'];
                    $groupId = $data['groupId'];
                }

                if (empty($repoId)) throw new Exception("impossible de récupérer l'id du repo ${name}");
                if (empty($groupId)) throw new Exception("impossible de récupérer l'id du groupe ${targetGroup}");
                
                try {
                    $stmt = $this->repo->db->prepare("INSERT INTO group_members (Id_repo, Id_group) VALUES (:repoId, :groupId)");
                    $stmt->bindValue(':repoId', $repoId);
                    $stmt->bindValue(':groupId', $groupId);
                    $stmt->execute();
                } catch (Exception $e) {
                    Common::dbError($e);
                }

                $this->log->steplogOK();
            }

            /**
             *  8. Génération du fichier de conf repo en local (ces fichiers sont utilisés pour les profils)
             */
            $this->repo->generateConf('default');

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