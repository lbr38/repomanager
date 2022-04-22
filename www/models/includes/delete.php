<?php
trait delete {
    /**
     *  SUPPRESSION D'UN REPO
     */
    public function exec_delete(string $id, string $status) {
        $this->repo = new Repo();
        $this->repo->setId($id);
        $this->repo->db_getAllById($status);

        /**
         *  Récupération des propriétés de l'objet Repo
         */
        $name          = $this->repo->getName();
        if (OS_FAMILY == 'Debian') {
            $dist      = $this->repo->getDist();
            $section   = $this->repo->getSection();
        }
        $date          = $this->repo->getDate();
        $dateFormatted = $this->repo->getDateFormatted();
        if ($status == 'active') {
            $env       = $this->repo->getEnv();
        }

        $this->startOperation(array('id_repo_target' => $id));

        /**
         *  Ajout du PID de ce processus dans le fichier PID
         */
        $this->log->addsubpid(getmypid());

        /**
         *  Lancement du script externe qui va construire le fichier de log principal à partir des petits fichiers de log de chaque étape
         */
        $steps = 1;
        exec("php ".ROOT."/operations/logbuilder.php ".PID_DIR."/{$this->log->pid}.pid {$this->log->location} ".TEMP_DIR."/{$this->log->pid} $steps >/dev/null 2>/dev/null &");
        
        try {
            ob_start();

            /**
             *  1. Génération du tableau récapitulatif de l'opération
             */
            include(ROOT.'/templates/tables/op-delete.inc.php');

            $this->log->steplog(1);
            $this->log->steplogInitialize('deleteRepo');
            $this->log->steplogTitle('SUPPRESSION');
            $this->log->steplogLoading();

            /**
             *  2. On vérifie que le repo renseigné existe bien
             */
            if ($this->repo->existsId($id, $status) === false) throw new Exception("le repo <b>${name}</b> n'existe pas");

            /**
             *  3. Suppression du repo
             */
            /**
             *  Dans le cas d'un repo 'active' on supprime le lien symbolique
             */
            if ($status == 'active') {
                if (OS_FAMILY == "Redhat") {
                    if (file_exists(REPOS_DIR."/${name}_${env}")) {
                        if (!unlink(REPOS_DIR."/${name}_${env}")) throw new Exception('impossible de supprimer le repo');
                    }
                }
                if (OS_FAMILY == "Debian") {
                    if (file_exists(REPOS_DIR."/${name}/${dist}/${section}_${env}")) {
                        if (!unlink(REPOS_DIR."/${name}/${dist}/${section}_${env}")) throw new Exception("impossible de supprimer la section");
                    }
                }
            }
            /**
             *  Dans le cas d'un repo 'archived' on supprime le répertoire
             */
            if ($status == 'archived') {
                if (OS_FAMILY == "Redhat") {
                    if (is_dir(REPOS_DIR."/archived_${dateFormatted}_${name}")) {
                        exec("rm ".REPOS_DIR."/archived_${dateFormatted}_${name} -rf", $output, $result);
                    }
                }
                if (OS_FAMILY == "Debian") {
                    if (is_dir(REPOS_DIR."/${name}/${dist}/archived_${dateFormatted}_${section}")) {
                        exec("rm ".REPOS_DIR."/${name}/${dist}/archived_${dateFormatted}_${section} -rf", $output, $result);
                    }
                }
                if ($result != 0) {
                    throw new Exception('impossible de supprimer le miroir');
                }

                $this->log->steplogOK();
            }

            /**
             *  4. Mise à jour de la BDD
             */
            try {
                if ($status == 'active')   $stmt = $this->repo->db->prepare("UPDATE repos SET status = 'deleted' WHERE Id = :id and Status = 'active'");
                if ($status == 'archived') $stmt = $this->repo->db->prepare("UPDATE repos_archived SET Status = 'deleted' WHERE Id = :id and Status = 'active'");
                $stmt->bindValue(':id', $id);
                $stmt->execute();
                
            } catch (Exception $e) {
                Common::dbError($e);
            }

            /**
             *  5. Suppression définitive du miroir
             *  Redhat : Si il n'y a plus de trace du repo en BDD alors on peut supprimer son miroir définitivement
             *  Ainsi que son fichier de conf .repo
             */
            if ($status == 'active') {
                if (OS_FAMILY == "Redhat") {
                    if ($this->repo->existsDate($name, $date, 'active') === false) {
                        exec("rm ".REPOS_DIR."/${dateFormatted}_${name}/ -rf", $output, $result);
                        if ($result != 0) {
                            throw new Exception('impossible de supprimer le miroir');
                        }

                        /**
                         *  Suppression du fichier de conf repo en local (ces fichiers sont utilisés pour les profils)
                         */
                        $this->repo->deleteConf();

                        $this->log->steplogOK();
                    }
                }

                if (OS_FAMILY == 'Debian') {
                    if ($this->repo->section_existsDate($name, $dist, $section, $date, 'active') === false) {
                        /**
                         *  Suppression du miroir puisque'il n'est plus utilisé par aucun environnement
                         */
                        exec("rm ".REPOS_DIR."/${name}/${dist}/${dateFormatted}_${section} -rf", $output, $result);
                        if ($result != 0) {
                            throw new Exception('impossible de supprimer le miroir');
                        }

                        /**
                         *  Si il n'y a plus du tout de trace de la section en BDD, alors on peut supprimer son fichier de conf .list
                         */
                        if ($this->repo->section_exists($name, $dist, $section) === false) {
                            $this->repo->deleteConf();
                        }
                        
                        $this->log->steplogOK();

                    } else {
                        $this->log->steplogOK("La version du miroir de cette section est toujours utilisée pour d'autres environnements. Le miroir du <b>{$dateFormatted}</b> n'est donc pas supprimé");
                    }
                }

                /**
                 *  6. Supprime le repo/les sections des groupes où il apparait
                 */
                $group = new Group('repo');
                $group->cleanRepos();
            }

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