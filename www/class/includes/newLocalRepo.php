<?php
trait newLocalRepo {
    /**
     *  NOUVEAU REPO LOCAL
     */
    public function exec_newLocalRepo() {
        global $OS_FAMILY;
        global $REPOS_DIR;
        global $WWW_USER;

        /** 
         *  1. On vérifie que le nouveau nom du repo n'est pas vide
         */
        if (empty($this->repo->name)) {
            echo "Erreur : le nouveau nom du repo ne peut être vide";
            return false;
        }

        /**
         *  2. Génération du tableau récapitulatif de l'opération
         */
        echo '<table>';
        if ($OS_FAMILY == "Redhat") {
            echo "<tr>
                <td>Nom du repo :</td>
                <td><b>{$this->repo->name}</b></td>
            </tr>";
        }
        if ($OS_FAMILY == "Debian") {
            echo "<tr>
                <td>Nom du repo :</td>
                <td><b>{$this->repo->name}</b></td>
            </tr>
            <tr>
                <td>Distribution :</td>
                <td><b>{$this->repo->dist}</b></td>
            </tr>
            <tr>
                <td>Section :</td>
                <td><b>{$this->repo->section}</b></td>
            </tr>";
        }
        if (!empty($this->repo->gpgResign)) {
            echo "<tr>
            <td>Signature du repo avec GPG :</td>
            <td><b>{$this->repo->gpgResign}</b></td>
            </tr>";
        }
        if (!empty($this->repo->description)) {
        echo "<tr>
            <td>Description :</td>
            <td><b>{$this->repo->description}</b></td>
            </tr>";
        }
        if (!empty($this->repo->group)) {
            echo "<tr>
            <td>Ajout à un groupe :</td>
            <td><b>{$this->repo->group}</b></td>
            </tr>";
        }
        echo '</table>';

        /**
         *  3. Création du nouveau répertoire avec le nom du repo, et les sous-répertoires permettant d'accuillir les futurs paquets
         */
        if ($OS_FAMILY == "Redhat") {
            if (!file_exists("${REPOS_DIR}/{$this->repo->dateFormatted}_{$this->repo->name}/Packages")) {
                if (!mkdir("${REPOS_DIR}/{$this->repo->dateFormatted}_{$this->repo->name}/Packages", 0770, true)) {
                    echo "<p><span class=\"redtext\">Erreur : </span>impossible de créer le répertoire ${REPOS_DIR}/{$this->repo->name}</p>";
                    return false;
                }
            }
        }
        if ($OS_FAMILY == "Debian") {
            if (!file_exists("${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/{$this->repo->dateFormatted}_{$this->repo->section}/pool/{$this->repo->section}")) {
                if (!mkdir("${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/{$this->repo->dateFormatted}_{$this->repo->section}/pool/{$this->repo->section}", 0770, true)) {
                    echo "<p><span class=\"redtext\">Erreur : </span>impossible de créer le répertoire ${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/{$this->repo->dateFormatted}_{$this->repo->section}</p>";
                    return false;
                }
            }
        }

        /**
         *   4. Création du lien symbolique
         */
        if ($OS_FAMILY == "Redhat") {
            exec("cd ${REPOS_DIR}/ && ln -sfn {$this->repo->dateFormatted}_{$this->repo->name}/ {$this->repo->name}_{$this->repo->env}", $output, $result);            
        }
        if ($OS_FAMILY == "Debian") {
            exec("cd ${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/ && ln -sfn {$this->repo->dateFormatted}_{$this->repo->section}/ {$this->repo->section}_{$this->repo->env}", $output, $result);
        }
        if ($result != 0) {
            echo '<p><span class="redtext">Erreur : </span>création du lien symbolique impossible</p>';
            return false;
        }

        /**
         *  5. Insertion en BDD du nouveau repo
         */
        if ($OS_FAMILY == "Redhat") {
            $this->repo->db->exec("INSERT INTO repos (Name, Source, Env, Date, Time, Description, Signed, Type, Status) VALUES ('{$this->repo->name}', '{$this->repo->source}', '{$this->repo->env}', '{$this->repo->date}', '{$this->repo->time}', '{$this->repo->description}', '{$this->repo->signed}', 'local', 'active')");
        }
        if ($OS_FAMILY == "Debian") {
            $this->repo->db->exec("INSERT INTO repos (Name, Source, Dist, Section, Env, Date, Time, Description, Signed, Type, Status) VALUES ('{$this->repo->name}', '{$this->repo->source}', '{$this->repo->dist}', '{$this->repo->section}', '{$this->repo->env}', '{$this->repo->date}', '{$this->repo->time}', '{$this->repo->description}', '{$this->repo->signed}', 'local', 'active')");
        }

        /**
         *  6. Application des droits sur le nouveau repo créé
         */
        exec("find ${REPOS_DIR}/{$this->repo->name}/ -type f -exec chmod 0660 {} \;");
        exec("find ${REPOS_DIR}/{$this->repo->name}/ -type d -exec chmod 0770 {} \;");
        exec("chown -R ${WWW_USER}:repomanager ${REPOS_DIR}/{$this->repo->name}/");

        /**
         *  7. Ajout de la section à un groupe si un groupe a été renseigné
         */
        if (!empty($this->repo->group)) {
            if ($OS_FAMILY == "Redhat") {
                $result = $this->repo->db->query("SELECT repos.Id AS repoId, groups.Id AS groupId FROM repos, groups WHERE repos.Name = '{$this->repo->name}' AND repos.Status = 'active' AND groups.Name = '{$this->repo->group}'");
            }
            if ($OS_FAMILY == "Debian") {
                $result = $this->repo->db->query("SELECT repos.Id AS repoId, groups.Id AS groupId FROM repos, groups WHERE repos.Name = '{$this->repo->name}' AND repos.Dist = '{$this->repo->dist}' AND repos.Section = '{$this->repo->section}' AND repos.Status = 'active' AND groups.Name = '{$this->repo->group}'");
            }

            while ($data = $result->fetchArray()) {
                $repoId = $data['repoId'];
                $groupId = $data['groupId'];
            }

            if (empty($repoId)){
                echo "<p><span class=\"redtext\">Erreur : </span>impossible de récupérer l'Id du repo {$this->repo->name}</p>";
                return false;
            }

            if (empty($groupId)) {
                echo "<p><span class=\"redtext\">Erreur : </span>impossible de récupérer l'Id du groupe {$this->repo->group}</p>";
                return false;
            }
            $this->repo->db->exec("INSERT INTO group_members (Id_repo, Id_group) VALUES ('$repoId', '$groupId')");
        }

        /**
         *  8. Génération du fichier de conf repo en local (ces fichiers sont utilisés pour les profils)
         */
        $this->repo->generateConf('default');

        echo '<p>Terminé <span class="greentext">✔</span> Vous pouvez désormais ajouter des paquets à votre repo local.</p>';
    }
}
?>