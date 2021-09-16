<?php
trait deleteSection {
    /**
     *  SUPPRESSION D'UNE SECTION
     */
    public function exec_deleteSection() {
        global $REPOS_DIR;
        
        ob_start();

        /**
         *  1. Génération du tableau récapitulatif de l'opération
         */
        echo "<h3>SUPPRESSION D'UNE SECTION</h3>";

        echo "<table class=\"op-table\">
        <tr>
            <th>Nom du repo :</th>
            <td><b>{$this->repo->name}</b></td>
        </tr>
        <tr>
            <th>Distribution :</th>
            <td><b>{$this->repo->dist}</b></td>
        </tr>
        <tr>
            <th>Section :</th>
            <td><b>{$this->repo->section}</b> ".envtag($this->repo->env)."</td>
        </tr>
        </table>";

        $this->log->steplog(1);
        $this->log->steplogInitialize('deleteSection');
        $this->log->steplogTitle('SUPPRESSION');
        $this->log->steplogLoading();

        /**
         *  2. On vérifie que la section renseignée existe bien
         */
        if ($this->repo->section_exists($this->repo->name, $this->repo->dist, $this->repo->section) === false) throw new Exception("la section spécifiée n'existe pas");

        /**
         *  3. Récupération de la date de la section
         */
        $this->repo->db_getDate();

        /**
         *  4. Suppression du lien symbolique
         */
        if (file_exists("${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/{$this->repo->section}_{$this->repo->env}")) {
            if (!unlink("${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/{$this->repo->section}_{$this->repo->env}")) throw new Exception("impossible de supprimer la section");
        }

        /**
         *  5. On met à jour la BDD
         */
        $this->repo->db->exec("UPDATE repos SET Status = 'deleted' WHERE Name = '{$this->repo->name}' AND Dist = '{$this->repo->dist}' AND Section = '{$this->repo->section}' AND Env = '{$this->repo->env}' AND Date = '{$this->repo->date}' AND Status = 'active'");

        /**
         *  6. Vérifications avant suppression définitive du miroir
         */
        $result = $this->repo->db->countRows("SELECT * from repos WHERE Name = '{$this->repo->name}' AND Dist = '{$this->repo->dist}' AND Section = '{$this->repo->section}' AND Date = '{$this->repo->date}' AND Status = 'active'");
        if ($result != 0) {
            $this->log->steplogOK("La version du miroir de cette section est toujours utilisée pour d'autres environnements. Le miroir du <b>{$this->repo->dateFormatted}</b> n'est donc pas supprimé");
            
        } else {
            // Suppression du miroir puisque'il n'est plus utilisé par aucun environnement
            exec("rm ${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/{$this->repo->dateFormatted}_{$this->repo->section} -rf", $output, $result);
            if ($result != 0) throw new Exception('impossible de supprimer le miroir');

            $this->log->steplogOK();
        }

        /**
         *  7. Si il n'y a plus du tout de trace de la section en BDD, alors on peut supprimer son fichier de conf .list
         */
        if ($this->repo->section_exists($this->repo->name, $this->repo->dist, $this->repo->section) === false) {
            $this->repo->deleteConf();
        }

        /**
         *  6. Supprime la section des groupes où elle apparait
         */
        $group = new Group();
        $group->clean();
    }
}
?>