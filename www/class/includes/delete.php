<?php
trait delete {
    /**
     *  SUPPRESSION D'UN REPO
     */
    public function exec_delete() {
        global $OS_FAMILY;
        global $REPOS_DIR;

        ob_start();

        /**
         *  0. Si Redhat on aura besoin de la date du repo à supprimer
         */
        if ($OS_FAMILY == "Redhat") $this->repo->db_getDate();

        /**
         *  1. Génération du tableau récapitulatif de l'opération
         */
        echo "<h3>SUPPRESSION D'UN REPO</h3>";
        echo "<table class=\"op-table\">
        <tr>
            <th>Nom du repo :</th>
            <td><b>{$this->repo->name}</b></td>
        </tr>
        </table>";

        $this->log->steplog(1);
        $this->log->steplogInitialize('deleteRepo');
        $this->log->steplogTitle('SUPPRESSION');
        $this->log->steplogLoading();

        /**
         *  2. On vérifie que le repo renseigné existe bien
         */
        if ($this->repo->exists($this->repo->name) == false) throw new Exception("le repo <b>{$this->repo->name}</b> n'existe pas");

        /**
         *  3. Suppression du repo
         *   Si Redhat : Suppression du lien symbolique du repo
         *   Si Debian : Suppression du répertoire du repo
         */
        if ($OS_FAMILY == "Redhat") {
            if (file_exists("${REPOS_DIR}/{$this->repo->name}_{$this->repo->env}")) {
                if (!unlink("${REPOS_DIR}/{$this->repo->name}_{$this->repo->env}")) throw new Exception('impossible de supprimer le repo');
            }
        }
        if ($OS_FAMILY == "Debian") {
            if (file_exists("${REPOS_DIR}/{$this->repo->name}")) {
                exec("rm ${REPOS_DIR}/{$this->repo->name} -rf", $output, $result);
                if ($result != 0) throw new Exception('impossible de supprimer le repo');
            }
        }

        /**
         *  4. Mise à jour de la BDD
         */
        if ($OS_FAMILY == "Redhat") {
            $stmt = $this->repo->db->prepare("UPDATE repos SET status = 'deleted' WHERE Name=:name AND Env=:env AND Date=:date AND Status = 'active'");
            $stmt->bindValue(':name', $this->repo->name);
            $stmt->bindValue(':env', $this->repo->env);
            $stmt->bindValue(':date', $this->repo->date);
            $stmt->execute();
        }
        if ($OS_FAMILY == "Debian") {
            /**
             *  Sur Debian, la suppression d'un repo entier entraine la suppression des sections archivées si il y en a, donc on met aussi à jour repos_archived
             */
            $stmt = $this->repo->db->prepare("UPDATE repos SET status = 'deleted' WHERE Name=:name AND Status = 'active'");
            $stmt->bindValue(':name', $this->repo->name);
            $stmt->execute();

            $stmt = $this->repo->db->prepare("UPDATE repos_archived SET status = 'deleted' WHERE Name=:name AND Status = 'active'");
            $stmt->bindValue(':name', $this->repo->name);
            $stmt->execute();
        }
        
        unset($stmt);

        /**
         *  5. Redhat : Si il n'y a plus de trace du repo en BDD alors on peut supprimer son miroir définitivement
         *  Ainsi que son fichier de conf .repo
         */
        if ($OS_FAMILY == "Redhat") {
            if ($this->repo->exists($this->repo->name) === false) {
                exec("rm ${REPOS_DIR}/{$this->repo->dateFormatted}_{$this->repo->name}/ -rf");

                // Suppression du fichier de conf repo en local (ces fichiers sont utilisés pour les profils)
                $this->repo->deleteConf();
            }
        }

        /**
         *  6. Supprime le repo/les sections des groupes où il apparait
         */
        $group = new Group();
        $group->clean();

        $this->log->steplogOK();
    }
}
?>