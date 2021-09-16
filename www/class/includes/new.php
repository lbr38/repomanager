<?php
trait newMirror {
    public function exec_new() {
        global $TEMP_DIR;
        global $OS_FAMILY;
        global $WWW_DIR;
        global $PID_DIR;

        /**
         *  Démarrage de l'opération
         *  On indique à startOperation, le nom du repo/section en cours de création. A la fin de l'opération, on remplacera cette valeur directement par 
         *  l'ID en BDD de ce repo/section créé.
         *  On indique également si on a activé ou non gpgCheck et gpgResign.
         */
        if ($OS_FAMILY == "Redhat") $this->startOperation(array('id_repo_target' => $this->repo->name, 'gpgCheck' => $this->repo->gpgCheck, 'gpgResign' => $this->repo->gpgResign));
        if ($OS_FAMILY == "Debian") $this->startOperation(array('id_repo_target' => "{$this->repo->name}|{$this->repo->dist}|{$this->repo->section}", 'gpgCheck' => $this->repo->gpgCheck, 'gpgResign' => $this->repo->gpgResign));

        /**
         *  Ajout du PID de ce processus dans le fichier PID
         */
        $this->log->addsubpid(getmypid());

        /**
         *  Lancement du script externe qui va construire le fichier de log principal à partir des petits fichiers de log de chaque étape
         */
        $steps = 5;
        exec("php ${WWW_DIR}/operations/logbuilder.php ${PID_DIR}/{$this->log->pid}.pid {$this->log->location} $TEMP_DIR/{$this->log->pid} $steps >/dev/null 2>/dev/null &");

        try {
            /**
             *  Etape 0 : Afficher le titre de l'opération
             */
            $this->log->steplog(0);
            if ($OS_FAMILY == "Redhat") { file_put_contents($this->log->steplog, "<h3>CREATION D'UN NOUVEAU REPO</h3>"); }
            if ($OS_FAMILY == "Debian") { file_put_contents($this->log->steplog, "<h3>CREATION D'UNE NOUVELLE SECTION DE REPO</h3>"); }
            /**
             *  Etape 1 : Afficher les détails de l'opération
             */
            $this->log->steplog(1);
            $this->op_printDetails();
            /**
            *   Etape 2 : récupération des paquets
            */
            $this->log->steplog(2);
            $this->op_getPackages('new');
            /**
            *   Etape 3 : signature des paquets/du repo
            */
            $this->log->steplog(3);
            $this->op_signPackages();
            /**
            *   Etape 4 : Création du repo et liens symboliques
            */
            $this->log->steplog(4);
            $this->op_createRepo();
            /**
            *   Etape 5 : Finalisation du repo (ajout en BDD et application des droits)
            */
            $this->log->steplog(5);
            $this->op_finalize('new');

            /**
             *  Passage du status de l'opération en done
             */
            $this->status = 'done';

        } catch(Exception $e) {
            $this->log->steplogError($e->getMessage()); // On transmets l'erreur à $this->log->steplogError() qui va se charger de l'afficher en rouge dans le fichier de log

            /**
             *  Passage du status de l'opération en erreur
             */
            $this->status = 'error';
        }
        /**
         *  Cloture de l'opération
         */
        $this->log->closeStepOperation();
        /**
         *  On récupère l'ID en BDD du repo/section qu'on vient de créer, ceci afin de mettre à jour les infos de l'opération en BDD avant de la clore
         */
        $this->repo->db_getId();
        $this->db_update_idrepo_target($this->repo->id);
        $this->closeOperation();
    }
}
?>