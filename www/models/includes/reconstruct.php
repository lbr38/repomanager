<?php
trait reconstruct {
    public function exec_reconstruct() {
        /**
         *  Création d'une opération en BDD, on indique également si on a activé ou non gpgCheck et gpgResign
         *  Si cette fonction est appelée par une planification, alors l'id de cette planification est stockée dans $this->id_plan, on l'indique également à startOperation()
         */
        $this->startOperation(array('id_repo_target' => $this->repo->id, 'gpgResign' => $this->repo->gpgResign));

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
            /**
             *  Etape 0 : Afficher le titre de l'opération
             */
            $this->log->steplog(0);
            file_put_contents($this->log->steplog, "<h3>RECONSTRUCTION DES METADONNÉES DU REPO</h3>");
            /**
             *  Etape 1 : Afficher les détails de l'opération
             */
            $this->log->steplog(1);
            $this->op_printDetails();
            /**
            *   Etape 2 : signature des paquets/du repo
            */
            $this->log->steplog(2);
            $this->op_signPackages();
            /**
            *   Etape 3 : Création du repo et liens symboliques
            */
            $this->log->steplog(3);
            $this->op_createRepo();
            /**
             *  Etape 4 : on modifie l'état de la signature du repo en BDD
             *  Comme on a reconstruit les fichiers du repo, il est possible qu'on soit passé d'un repo signé à un repo non-signé, ou inversement
             *  Il faut donc modifier l'état en BDD
             */
            $this->repo->db_setsigned();

            /**
             *  Passage du status de l'opération en done
             */
            $this->status = 'done';

        } catch(Exception $e) {
            //file_put_contents($this->log->steplog, $e->getMessage(), FILE_APPEND);
            $this->log->steplogError($e->getMessage()); // On transmets l'erreur à $this->log->steplogError() qui va se charger de l'afficher en rouge dans le fichier de log

            /**
             *  Passage du status de l'opération en erreur
             */
            $this->status = 'error';

            /**
             *  Cloture de l'opération
             */
            $this->log->closeStepOperation();
            $this->closeOperation();
        }
        /**
         *  Cloture de l'opération
         */
        $this->log->closeStepOperation();
        $this->closeOperation();
    }
}
?>