<?php
trait update {
    public function exec_update() {
        global $TEMP_DIR;
        global $OS_FAMILY;
        global $WWW_DIR;
        global $PID_DIR;

        /**
         *  Démarrage de l'opération
         *  A partir de l'ID de repo fourni, on récupère toutes les infos du repo à mettre à jour
         */
        $this->repo->db_getAllById();
        
        /**
         *  On écrase les valeurs de GPG Check et GPG Resign précédemment récupérées par db_getAllById() par les valeurs que l'utilisateur aura choisi lors du lancement de l'opération ou lors de la création de la planification
         */
        if (!empty($this->gpgCheck))  $this->repo->gpgCheck  = $this->gpgCheck;  // $this->gpgCheck  = $op->gpgCheck  initié dans execute.php ou dans Planification.php par getInfo()
        if (!empty($this->gpgResign)) $this->repo->gpgResign = $this->gpgResign; // $this->gpgResign = $op->gpgResign initié dans execute.php ou dans Planification.php par getInfo()
        if (!empty($this->gpgResign)) $this->repo->signed    = $this->gpgResign; // $this->gpgResign = $op->gpgResign initié dans execute.php ou dans Planification.php par getInfo()
        /**
         *  On écrase également la date et le time précédemment récupérées par db_getAllById() par les valeurs actuelles, càd la date du jour et l'heure du moment
         */
        $this->repo->date          = date("Y-m-d");
        $this->repo->dateFormatted = date("d-m-Y");
        $this->repo->time          = date("H:i");

        /**
         *  Création d'une opération en BDD, on indique également si on a activé ou non gpgCheck et gpgResign
         *  Si cette fonction est appelée par une planification, alors l'id de cette planification est stockée dans $this->id_plan, on l'indique également à startOperation()
         */
        if ($this->type == 'manual') $this->startOperation(array('id_repo_target' => $this->repo->id, 'gpgCheck' => $this->repo->gpgCheck, 'gpgResign' => $this->repo->gpgResign));
        if ($this->type == 'plan')   $this->startOperation(array('id_repo_target' => $this->repo->id, 'gpgCheck' => $this->repo->gpgCheck, 'gpgResign' => $this->repo->gpgResign, 'id_plan' => $this->id_plan));

        /**
         *  Ajout du PID de ce processus dans le fichier PID
         */
        $this->log->addsubpid(getmypid());

        /**
         *  Lancement du script externe qui va construire le fichier de log principal à partir des petits fichiers de log de chaque étape
         */
        $steps = 6;
        exec("php ${WWW_DIR}/operations/logbuilder.php ${PID_DIR}/{$this->log->pid}.pid {$this->log->location} ${TEMP_DIR}/{$this->log->pid} $steps >/dev/null 2>/dev/null &");

        try {
            /**
             *  Etape 0 : Afficher le titre de l'opération
             */
            $this->log->steplog(0);
            if ($OS_FAMILY == "Redhat") file_put_contents($this->log->steplog, "<h3>MISE A JOUR D'UN REPO</h3>");
            if ($OS_FAMILY == "Debian") file_put_contents($this->log->steplog, "<h3>MISE A JOUR D'UNE SECTION DE REPO</h3>");        
            
            /**
             *  Récupère la source du repo si celle-ci est vide 
             *  ça peut être le cas lorsque l'opération est lancée par une planification
             */
            $this->repo->db_getSource();

            /**
             *  Etape 1 : Afficher les détails de l'opération
             */
            $this->log->steplog(1);
            $this->op_printDetails();
            /**
            *   Etape 2 : récupération des paquets
            */
            $this->log->steplog(2);
            $this->op_getPackages('update');
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
             *  Etape 5 : Archivage de l'ancien repo/section
             */
            $this->log->steplog(5);
            $this->op_archive();
            /**
            *   Etape 6 : Finalisation du repo (ajout en BDD et application des droits)
            */
            $this->log->steplog(6);
            $this->op_finalize('update');

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

            /**
             *  Cloture de l'opération
             */
            $this->log->closeStepOperation();
            $this->closeOperation();
            
            /**
             *  Cas où cette fonction est lancée par une planification : la planif attend un retour, on lui renvoie false pour lui indiquer qu'il y a eu une erreur
             */
            return false;
        }
        /**
         *  Cloture de l'opération
         */
        $this->log->closeStepOperation();
        $this->closeOperation();
    }
}
?>