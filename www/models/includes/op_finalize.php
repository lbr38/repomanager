<?php
trait op_finalize {
    /**
    *   Finalisation du repo : ajout en BDD et application des droits
    */
    public function op_finalize($op_type, $params) {
        extract($params);

        ob_start();

        $this->log->steplogInitialize('finalizeRepo');
        $this->log->steplogTitle('FINALISATION');
        $this->log->steplogLoading();

        /**
         *  Le type d'opération doit être renseigné pour cette fonction (soit "new" soit "update")
         */
        if (empty($op_type)) throw new Exception("type d'opération inconnu (vide)");
        if ($op_type != "new" AND $op_type != "update") throw new Exception("type d'opération invalide");

        /**
         *  1. Mise à jour de la BDD 
         *  - Si il s'agit d'un nouveau repo on l'ajoute en BDD
         *  - Si il s'agit d'une mise à jour de repo, on ne fait rien (les informations ont été mises à jour à l'étape op_archive)
         */
        if ($op_type == "new") {
            try {
                if (OS_FAMILY == "Redhat") $stmt = $this->db->prepare("INSERT INTO repos (Name, Source, Env, Date, Time, Description, Signed, Type, Status) VALUES (:name, :source, :env, :date, :time, :description, :signed, :type, 'active')");
                if (OS_FAMILY == "Debian") $stmt = $this->db->prepare("INSERT INTO repos (Name, Source, Dist, Section, Env, Date, Time, Description, Signed, Type, Status) VALUES (:name, :source, :dist, :section, :env, :date, :time, :description, :signed, :type, 'active')");
                $stmt->bindValue(':name', $name);
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
        }

        /**
         *  2. Ajout à un groupe si un groupe a été renseigné. Uniquement si il s'agit d'un nouveau repo/section ($op_type = new)
         */
        if ($op_type == "new") {
            if (!empty($targetGroup)) {
                try {
                    if (OS_FAMILY == "Redhat") $stmt = $this->db->prepare("SELECT repos.Id AS repoId, groups.Id AS groupId FROM repos, groups WHERE repos.Name = :name AND repos.Status = 'active' AND groups.Name = :groupname");
                    if (OS_FAMILY == "Debian") $stmt = $this->db->prepare("SELECT repos.Id AS repoId, groups.Id AS groupId FROM repos, groups WHERE repos.Name = :name AND repos.Dist = :dist AND repos.Section = :section AND repos.Status = 'active' AND groups.Name = :groupname");
                    $stmt->bindValue(':name', $name);
                    $stmt->bindValue(':groupname', $targetGroup);
                    if (OS_FAMILY == "Debian") {
                        $stmt->bindValue(':dist', $dist);
                        $stmt->bindValue(':section', $section);
                    }
                    $result = $stmt->execute();
                } catch(Exception $e) {
                    Common::dbError($e);
                }

                while ($data = $result->fetchArray(SQLITE3_ASSOC)) {
                    $repoId = $data['repoId'];
                    $groupId = $data['groupId'];
                }

                if (empty($repoId)){
                    $this->log->steplogError("Ajout à un groupe : impossible de récupérer l'id du repo $name");
                    return;
                }

                if (empty($groupId)) {
                    $this->log->steplogError("Ajout à un groupe : impossible de récupérer l'id du groupe $targetGroup");
                    return;
                }

                try {
                    $stmt = $this->db->prepare("INSERT INTO group_members (Id_repo, Id_group) VALUES (:idrepo, :idgroup)");
                    $stmt->bindValue(':idrepo', $repoId);
                    $stmt->bindValue(':idgroup', $groupId);
                    $stmt->execute();
                } catch(Exception $e) {
                    Common::dbError($e);
                }
            }
        }

        /**
         *  3. Application des droits sur le repo/section créé
         */
        if (OS_FAMILY == "Redhat") {
            exec("find ".REPOS_DIR."/".DATE_DMY."_${name}/ -type f -exec chmod 0660 {} \;");
            exec("find ".REPOS_DIR."/".DATE_DMY."_${name}/ -type d -exec chmod 0770 {} \;");
            /*if [ $? -ne "0" ];then
                echo "<br><span class=\"redtext\">Erreur :</span>l'application des permissions sur le repo <b>$this->name</b> a échoué"
            fi*/
        }
        if (OS_FAMILY == "Debian") {
            exec("find ".REPOS_DIR."/${name}/${dist}/".DATE_DMY."_${section}/ -type f -exec chmod 0660 {} \;");
            exec("find ".REPOS_DIR."/${name}/${dist}/".DATE_DMY."_${section}/ -type d -exec chmod 0770 {} \;");
            /*if [ $? -ne "0" ];then
                echo "<br><span class=\"redtext\">Erreur :</span>l'application des permissions sur la section <b>$this->section</b> a échoué"
            fi*/
        }

        /**
         *  4. Génération du fichier de conf repo en local (ces fichiers sont utilisés pour les profils)
         */
        $this->repo->generateConf('default');

        $this->log->steplogOK();

        $this->repo->cleanArchives();
        $this->log->steplogWrite();

        return true;
    }
}
?>