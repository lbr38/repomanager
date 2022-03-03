<?php
trait newMirror {
    public function exec_new() {
        /**
         *  Récupération des propriétés de l'objet Repo
         */
        $name              = $this->repo->getName();
        $date              = date("Y-m-d");
        $dateFormatted     = date("d-m-Y");
        $time              = date("H:i");
        $env               = DEFAULT_ENV;
        $source            = $this->repo->getSource();
        $type              = $this->repo->getType();
        $targetGpgCheck    = $this->repo->getTargetGpgCheck();
        $targetGpgResign   = $this->repo->getTargetGpgResign();
        $targetDescription = $this->repo->getTargetDescription();
        $targetGroup       = $this->repo->getTargetGroup();
        if (OS_FAMILY == 'Debian') {
            $dist          = $this->repo->getDist();
            $section       = $this->repo->getSection();
        }
        /**
         *  On concatène tous les paramètres dans un array car on en aura besoin pour les transmettre à certaines fonctions
         */
        if (OS_FAMILY == 'Redhat') $params = compact('name', 'date', 'dateFormatted', 'time', 'env', 'source', 'type', 'targetDescription', 'targetGroup', 'targetGpgCheck', 'targetGpgResign');
        if (OS_FAMILY == 'Debian') $params = compact('name', 'dist', 'section', 'date', 'dateFormatted', 'time', 'env', 'source', 'type', 'targetDescription', 'targetGroup', 'targetGpgCheck', 'targetGpgResign');

        /**
         *  Démarrage de l'opération
         *  On indique à startOperation, le nom du repo/section en cours de création. A la fin de l'opération, on remplacera cette valeur directement par 
         *  l'ID en BDD de ce repo/section créé.
         *  On indique également si on a activé ou non gpgCheck et gpgResign.
         */
        if (OS_FAMILY == "Redhat") $this->startOperation(array('id_repo_target' => $name, 'gpgCheck' => $targetGpgCheck, 'gpgResign' => $targetGpgResign));
        if (OS_FAMILY == "Debian") $this->startOperation(array('id_repo_target' => "$name|$dist|$section", 'gpgCheck' => $targetGpgCheck, 'gpgResign' => $targetGpgResign));

        /**
         *  Ajout du PID de ce processus dans le fichier PID
         */
        $this->log->addsubpid(getmypid());

        /**
         *  Lancement du script externe qui va construire le fichier de log principal à partir des petits fichiers de log de chaque étape
         */
        $steps = 5;
        exec("php ".ROOT."/operations/logbuilder.php ".PID_DIR."/{$this->log->pid}.pid {$this->log->location} ".TEMP_DIR."/{$this->log->pid} $steps >/dev/null 2>/dev/null &");

        try {
            /**
             *  Etape 0 : Afficher le titre de l'opération
             */
            $this->log->steplog(0);
            if (OS_FAMILY == "Redhat") file_put_contents($this->log->steplog, "<h3>CREATION D'UN NOUVEAU REPO</h3>");
            if (OS_FAMILY == "Debian") file_put_contents($this->log->steplog, "<h3>CREATION D'UNE NOUVELLE SECTION DE REPO</h3>");
            /**
             *  Etape 1 : Afficher les détails de l'opération
             */
            $this->log->steplog(1);
            $this->op_printDetails($params);
            /**
            *   Etape 2 : récupération des paquets
            */
            $this->log->steplog(2);
            $this->op_getPackages('new', $params);
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
            *   Etape 5 : Finalisation du repo (ajout en BDD et application des droits)
            */
            $this->log->steplog(5);
            $this->op_finalize('new', $params);

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
        }

        /**
         *  Cloture de l'opération
         */
        $this->log->closeStepOperation();
        
        $this->closeOperation();
    }
}
?>