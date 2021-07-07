<?php
trait op_finalize {
    /**
    *   Finalisation du repo : ajout en BDD et application des droits
    */
    public function op_finalize($op_type) {
        global $OS_FAMILY;
        global $DATE_DMY;
        global $REPOS_DIR;

        if (empty($op_type)) {
            throw new Exception('<p><span class="redtext">Erreur : </span>type d\'opération inconnu (vide)</p>');
        }
        if ($op_type != "new" AND $op_type != "update") {
            throw new Exception('<p><span class="redtext">Erreur : </span>type d\'opération invalide</p>');
        }

        ob_start();

        /**
         *  1. Mise à jour de la BDD 
         *  - Si il s'agit d'un nouveau repo on l'ajoute en BDD
         *  - Si il s'agit d'une mise à jour de repo, on ne fait rien (les informations ont été mises à jour à l'étape op_archive)
         */
        if ($op_type == "new") {
            if ($OS_FAMILY == "Redhat") {
                $this->db->exec("INSERT INTO repos (Name, Source, Env, Date, Time, Description, Signed, Type, Status) VALUES ('{$this->repo->name}', '{$this->repo->source}', '{$this->repo->env}', '{$this->repo->date}', '{$this->repo->time}', '{$this->repo->description}', '{$this->repo->signed}', '{$this->repo->type}', 'active')");
            }
            if ($OS_FAMILY == "Debian") {
                $this->db->exec("INSERT INTO repos (Name, Source, Dist, Section, Env, Date, Time, Description, Signed, Type, Status) VALUES ('{$this->repo->name}', '{$this->repo->source}', '{$this->repo->dist}', '{$this->repo->section}', '{$this->repo->env}', '{$this->repo->date}', '{$this->repo->time}', '{$this->repo->description}', '{$this->repo->signed}', '{$this->repo->type}', 'active')");
            }
        }

        /**
         *  2. Ajout à un groupe si un groupe a été renseigné. Uniquement si il s'agit d'un nouveau repo/section ($op_type = new)
         */
        if ($op_type == "new") {
            if (!empty($this->repo->group)) {
                if ($OS_FAMILY == "Redhat") {
                    $result = $this->db->query("SELECT repos.Id AS repoId, groups.Id AS groupId FROM repos, groups WHERE repos.Name = '{$this->repo->name}' AND repos.Status = 'active' AND groups.Name = '{$this->repo->group}'");
                }
                if ($OS_FAMILY == "Debian") {
                    $result = $this->db->query("SELECT repos.Id AS repoId, groups.Id AS groupId FROM repos, groups WHERE repos.Name = '{$this->repo->name}' AND repos.Dist = '{$this->repo->dist}' AND repos.Section = '{$this->repo->section}' AND repos.Status = 'active' AND groups.Name = '{$this->repo->group}'");
                }

                while ($data = $result->fetchArray()) {
                    $repoId = $data['repoId'];
                    $groupId = $data['groupId'];
                }

                if (empty($repoId)){
                    echo "<p><span class=\"redtext\">Erreur : </span>impossible de récupérer l'Id du repo {$this->repo->name}</p>";
                    return;
                }

                if (empty($groupId)) {
                    echo "<p><span class=\"redtext\">Erreur : </span>impossible de récupérer l'Id du groupe {$this->repo->group}</p>";
                    return;
                }
                $this->db->exec("INSERT INTO group_members (Id_repo, Id_group) VALUES ('$repoId', '$groupId')");
            }
        }

        /**
         *  3. Application des droits sur le repo/section créé
         */
        if ($OS_FAMILY == "Redhat") {
            exec("find ${REPOS_DIR}/${DATE_DMY}_{$this->repo->name}/ -type f -exec chmod 0660 {} \;");
            exec("find ${REPOS_DIR}/${DATE_DMY}_{$this->repo->name}/ -type d -exec chmod 0770 {} \;");
            /*if [ $? -ne "0" ];then
                echo "<br><span class=\"redtext\">Erreur :</span>l'application des permissions sur le repo <b>$this->name</b> a échoué"
            fi*/
        }
        if ($OS_FAMILY == "Debian") {
            exec("find ${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/${DATE_DMY}_{$this->repo->section}/ -type f -exec chmod 0660 {} \;");
            exec("find ${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/${DATE_DMY}_{$this->repo->section}/ -type d -exec chmod 0770 {} \;");
            /*if [ $? -ne "0" ];then
                echo "<br><span class=\"redtext\">Erreur :</span>l'application des permissions sur la section <b>$this->section</b> a échoué"
            fi*/
        }

        /**
         *  4. Génération du fichier de conf repo en local (ces fichiers sont utilisés pour les profils)
         */
        $this->repo->generateConf('default');

        echo '<p>Terminé <span class="greentext">✔</span></p>';

        $this->repo->cleanArchives();

        $this->logcontent = ob_get_clean(); file_put_contents($this->log->steplog, $this->logcontent, FILE_APPEND);

        return true;
    }
}
?>