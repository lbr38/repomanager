<?php
trait update {
    public function exec_update() {
        /**
         *  Récupération des propriétés de l'objet Repo
         */
        $id                = $this->repo->getId();
        $name              = $this->repo->getName();
        $date              = date("Y-m-d");
        $dateFormatted     = date("d-m-Y");
        $time              = date("H:i");
        $env               = $this->repo->getEnv();
        $source            = $this->repo->getSource();
        $type              = $this->repo->getType();
        $targetGpgCheck    = $this->repo->getTargetGpgCheck();
        $targetGpgResign   = $this->repo->getTargetGpgResign();
        $signed            = $targetGpgResign;
        if (OS_FAMILY == 'Debian') {
            $dist          = $this->repo->getDist();
            $section       = $this->repo->getSection();
        }
        /**
         *  On concatène tous les paramètres dans un array car on en aura besoin pour les transmettre à certaines fonctions
         */
        if (OS_FAMILY == 'Redhat') $params = compact('id', 'name', 'date', 'dateFormatted', 'time', 'env', 'source', 'type', 'targetGpgCheck', 'targetGpgResign', 'signed');
        if (OS_FAMILY == 'Debian') $params = compact('id', 'name', 'dist', 'section', 'date', 'dateFormatted', 'time', 'env', 'source', 'type', 'targetGpgCheck', 'targetGpgResign', 'signed');

        /**
         *  Création d'une opération en BDD, on indique également si on a activé ou non gpgCheck et gpgResign
         *  Si cette fonction est appelée par une planification, alors l'id de cette planification est stockée dans $this->id_plan, on l'indique également à startOperation()
         */
        if ($this->type == 'manual') $this->startOperation(array('id_repo_target' => $id, 'gpgCheck' => $targetGpgCheck, 'gpgResign' => $targetGpgResign));
        if ($this->type == 'plan')   $this->startOperation(array('id_repo_target' => $id, 'gpgCheck' => $targetGpgCheck, 'gpgResign' => $targetGpgResign, 'id_plan' => $this->getId_plan()));

        /**
         *  Ajout du PID de ce processus dans le fichier PID
         */
        $this->log->addsubpid(getmypid());

        /**
         *  Lancement du script externe qui va construire le fichier de log principal à partir des petits fichiers de log de chaque étape
         */
        $steps = 6;
        exec("php ".ROOT."/operations/logbuilder.php ".PID_DIR."/{$this->log->pid}.pid {$this->log->location} ".TEMP_DIR."/{$this->log->pid} $steps >/dev/null 2>/dev/null &");

        try {
            /**
             *  Etape 0 : Afficher le titre de l'opération
             */
            $this->log->steplog(0);
            if (OS_FAMILY == "Redhat") file_put_contents($this->log->steplog, "<h3>MISE A JOUR D'UN REPO</h3>");
            if (OS_FAMILY == "Debian") file_put_contents($this->log->steplog, "<h3>MISE A JOUR D'UNE SECTION DE REPO</h3>");        
            
            /**
             *  Etape 1 : Afficher les détails de l'opération
             */
            $this->log->steplog(1);
            $this->op_printDetails($params);
            /**
            *   Etape 2 : récupération des paquets
            */
            $this->log->steplog(2);
            $this->op_getPackages('update', $params);
            /**
            *   Etape 3 : signature des paquets/du repo
            */
            $this->log->steplog(3);
            $this->op_signPackages($params);
            /**
            *   Etape 4 : Création du repo et liens symboliques
            */
            $this->log->steplog(4);
            $this->op_createRepo($params);
            /**
             *  Etape 5 : Archivage de l'ancien repo/section
             */
            $this->log->steplog(5);
            $this->op_archive($params);
            /**
            *   Etape 6 : Finalisation du repo (ajout en BDD et application des droits)
            */
            $this->log->steplog(6);
            $this->op_finalize('update', $params);

            /**
             *  Passage du status de l'opération en done
             */
            $this->setStatus('done');

        } catch(Exception $e) {
            $this->log->steplogError($e->getMessage()); // On transmets l'erreur à $this->log->steplogError() qui va se charger de l'afficher en rouge dans le fichier de log

            /**
             *  Passage du status de l'opération en erreur
             */
            $this->setStatus('error');

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