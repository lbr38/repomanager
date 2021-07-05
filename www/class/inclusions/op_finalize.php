<?php
trait op_finalize {
    /**
    *   Finalisation du repo : ajout en BDD et application des droits
    */
    public function op_finalize($op_type) {
        global $OS_FAMILY;
        global $DATE_JMA;
        global $REPOS_DIR;
        global $DEFAULT_ENV;
        global $GROUPS_CONF;
        global $TEMP_DIR;

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
                $this->db->exec("INSERT INTO repos (Name, Source, Env, Date, Time, Description, Signed, Type) VALUES ('$this->name', '$this->source', '$this->env', '$this->date', '$this->time', '$this->description', '$this->signed', '$this->type')");
            }
            if ($OS_FAMILY == "Debian") {
                $this->db->exec("INSERT INTO repos (Name, Source, Dist, Section, Env, Date, Time, Description, Signed, Type) VALUES ('$this->name', '$this->source', '$this->dist', '$this->section', '$this->env', '$this->date', '$this->time', '$this->description', '$this->signed', '$this->type')");
            }
        }

        /**
         *  2. Ajout à un groupe si un groupe a été renseigné. Uniquement si il s'agit d'un nouveau repo/section ($op_type = new)
         */
        if ($op_type == "new") {
            if (!empty($this->group)) {
                if ($OS_FAMILY == "Redhat") {
                    $result = $this->db->query("SELECT repos.Id AS repoId, groups.Id AS groupId FROM repos, groups WHERE repos.Name = '$this->name' AND groups.Name = '$this->group'");
                }
                if ($OS_FAMILY == "Debian") {
                    $result = $this->db->query("SELECT repos.Id AS repoId, groups.Id AS groupId FROM repos, groups WHERE repos.Name = '$this->name' AND repos.Dist = '$this->dist' AND repos.Section = '$this->section' AND groups.Name = '$this->group'");
                }

                while ($data = $result->fetchArray()) {
                    $repoId = $data['repoId'];
                    $groupId = $data['groupId'];
                }

                if (empty($repoId)){
                    echo "<p><span class=\"redtext\">Erreur : </span>impossible de récupérer l'Id du repo $this->name</p>";
                    return;
                }

                if (empty($groupId)) {
                    echo "<p><span class=\"redtext\">Erreur : </span>impossible de récupérer l'Id du groupe $this->group</p>";
                    return;
                }
                $this->db->exec("INSERT INTO group_members (Id_repo, Id_group) VALUES ('$repoId', '$groupId')");
            }
        }

        /**
         *  3. Application des droits sur le repo/section créé
         */
        if ($OS_FAMILY == "Redhat") {
            exec("find ${REPOS_DIR}/${DATE_JMA}_{$this->name}/ -type f -exec chmod 0660 {} \;");
            exec("find ${REPOS_DIR}/${DATE_JMA}_{$this->name}/ -type d -exec chmod 0770 {} \;");
            /*if [ $? -ne "0" ];then
                echo "<br><span class=\"redtext\">Erreur :</span>l'application des permissions sur le repo <b>$this->name</b> a échoué"
            fi*/
        }
        if ($OS_FAMILY == "Debian") {
            exec("find ${REPOS_DIR}/{$this->name}/{$this->dist}/${DATE_JMA}_{$this->section}/ -type f -exec chmod 0660 {} \;");
            exec("find ${REPOS_DIR}/{$this->name}/{$this->dist}/${DATE_JMA}_{$this->section}/ -type d -exec chmod 0770 {} \;");
            /*if [ $? -ne "0" ];then
                echo "<br><span class=\"redtext\">Erreur :</span>l'application des permissions sur la section <b>$this->section</b> a échoué"
            fi*/
        }

        /**
         *  4. Génération du fichier de conf repo en local (ces fichiers sont utilisés pour les profils)
         */
        $this->generateConf('default');

        echo '<p>Terminé <span class="greentext">✔</span></p>';

        $this->cleanArchives();

        $this->logcontent = ob_get_clean(); file_put_contents($this->log->steplog, $this->logcontent, FILE_APPEND);

        return true;
    }
}
?>