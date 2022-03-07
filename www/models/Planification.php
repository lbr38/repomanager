<?php

class Planification extends Model {
    private $id;
    private $action;    // contiendra 'update' ou 'env->env'
    private $gpgCheck;
    private $gpgResign;
    private $status;
    private $error;
    private $logfile;

    private $type;
    private $day = null;
    private $date = null;
    private $time = null;
    private $frequency = null;
    private $repoId = null;
    private $groupId = null;
    private $reminder = null;
    private $mailRecipient = null;
    private $notificationOnSuccess;
    private $notificationOnError;

    private $log;       // pour instancier un objet Log
    public  $repo;      // pour instancier un objet Repo
    public  $op;        // pour instancier un objet Operation
    public  $group;     // pour instancier un objet Group

    private $logList = array();
    private $groupList;

    public function __construct() {
        /**
         *  Ouverture d'une connexion à la base de données
         */
        $this->getConnection('main');
    }

    public function setId(string $id)
    {
        $this->id = Common::validateData($id);
    }

    public function setDay(array $days)
    {
        $planDay = '';

        /**
         *  On sépare chaque jour spécifié par une virgule
         */
        foreach ($days as $day) {
            $planDay .= Common::validateData($day).',';
        }

        /**
         *  Suppression de la dernière virgule
         */
        $this->day = rtrim($planDay, ",");
    }

    public function setDate(string $date)
    {
        $this->date = Common::validateData($date);
    }

    public function setTime(string $time)
    {
        $this->time = Common::validateData($time);
    }

    public function setType(string $type)
    {
        $this->type = Common::validateData($type);
    }

    public function setFrequency(string $frequency)
    {
        $this->frequency = Common::validateData($frequency);
    }

    public function setAction(string $action)
    {
        /**
         *  On vérifie que l'action renseignée est valide
         */
        /**
         *  Si l'action = update
         */
        if ($action == 'update') {
            $this->action = 'update';
        /**
         *  Si l'action contient un '->'
         */
        } elseif (preg_match('/->/', $action)) {
            /**
             *  On récupère chacun des environnement pour vérifier si ils existent
             */
            $envs = explode('->', $action);

            $myenv = new Environnement();
            foreach ($envs as $env) {
                if ($myenv->exists($env) === false) {
                    throw new Exception("Erreur : environnement inconnu : $env");
                }
            }

            /**
             *  Si tous les environnements sont valides
             */
            $this->action = $action;

        /**
         *  Si l'action ne correspond à aucune action valide alors on quitte
         */
        } else {
            throw new Exception('Erreur : action invalide');
            die();
        }
    }

    public function setMailRecipient(string $mailRecipient)
    {
        $this->mailRecipient = Common::validateData($mailRecipient);
    }

    public function setReminder($reminders)
    {
        /**
         *  Si la planification est de type 'regular' (planification récurrente) et que la fréquence est "every-day" ou "every-hour" alors on ne set pas de rappel
         */
        if ($this->type == 'regular' AND ($this->frequency == "every-day" OR $this->frequency == "every-hour")) return;

        $planReminder = '';

        /**
         *  Si $reminders est un array
         */
        if (is_array($reminders)) {
            /**
             *  On sépare chaque jour de rappel par une virgule
             */
            foreach ($reminders as $reminder) {
                $planReminder .= Common::validateData($reminder).',';
            }
        }

        /**
         *  Suppression de la dernière virgule
         */
        $this->reminder = rtrim($planReminder, ",");
    }

    public function setNotification(string $type, string $state)
    {
        /**
         *  Si state est différent de yes et no alors c'est invalide
         */
        if ($state != 'yes' AND $state != 'no') {
            throw new Exception('Erreur : type de notification invalide');
            die();
        }

        if ($type == "on-error") {
            $this->notificationOnError = $state;
        }

        if ($type == "on-success") {
            $this->notificationOnSuccess = $state;
        }
    }

    public function setGpgCheck(string $state)
    {
        /**
         *  Si state est différent de yes et no alors c'est invalide
         */
        if ($state != 'yes' AND $state != 'no') {
            throw new Exception('Erreur : état invalide');
            die();
        }

        $this->gpgCheck = Common::validateData($state);
    }

    public function setGpgResign(string $state)
    {
        /**
         *  Si state est différent de yes et no alors c'est invalide
         */
        if ($state != 'yes' AND $state != 'no') {
            throw new Exception('Erreur : état invalide');
            die();
        }

        $this->gpgResign = Common::validateData($state);
    }

    public function setRepoId(string $id)
    {
        $this->repoId = Common::validateData($id);
    }

    public function setGroupId(string $id)
    {
        $this->groupId = Common::validateData($id);
    }

    public function getAction()
    {
        return $this->action;
    }

    public function getMailRecipient()
    {
        return $this->mailRecipient;
    }

    public function getMailRecipientFormatted()
    {
        /**
         *  Lorsque plusieurs adresses mail sont resneignées, on formatte le résultat retourné pour un meilleur affichage et sans virgule séparant les adresses
         *  Si une seule adresse mail est renseignée, on ne fait rien
         */
        if (preg_match('/,/', $this->mailRecipient)) {
            $mailRecipientFormatted = '';

            $mailRecipient = explode(',', $this->mailRecipient);

            foreach ($mailRecipient as $mail) {
                $mailRecipientFormatted .= $mail.'<br>';
            }
            /**
             *  Suppression du dernier saut de ligne
             */
            $mailRecipientFormatted = rtrim($mailRecipientFormatted, '<br>');

            return $mailRecipientFormatted;
        }

        return $this->mailRecipient;
    }


    /**
     *  Ajout d'une nouvelle planification en BDD
     */
    public function new()
    {
        /**
         *  Vérification des paramètres
         */

        /**
         *  Vérification du type
         */
        if (empty($this->type)) {
            throw new Exception("Vous devez spécifier un type");
        }

        /**
         *  Vérification de la fréquence si il s'agit d'une tâche récurrente
         */
        if ($this->type == "regular" AND empty($this->frequency))  {
            throw new Exception("Vous devez spécifiez une fréquence");
        }

        /**
         *  Vérification du/des jour(s) dans le cas où il s'agit d'une planification récurrente "toutes les semaines"
         */
        if ($this->type == "regular" AND $this->frequency == "every-week" AND empty($this->day)) {
            throw new Exception("Vous devez spécifiez le(s) jour(s) de la semaine");
        }

        /**
         *  Vérification de la date (dans le cas où il s'agit d'une planification)
         */
        if ($this->type == 'plan' AND empty($this->date)) {
            throw new Exception("Vous devez spécifier une date");
        }

        /**
         *  Vérification de l'heure (dans le cas où il s'agit d'une planification ou d'une tâche récurrente "tous les jours" ou "toutes les semaines")
         */
        if ($this->type == 'plan' OR ($this->type == 'regular' AND $this->frequency == 'every-day') OR ($this->type == 'regular' AND $this->frequency == 'every-week')) {
            if (empty($this->time)) {
                throw new Exception("Vous devez spécifier une heure");
            }
        }

        /**
         *  Si aucun repo et aucun groupe n'a été renseigné alors on quitte
         */
        if (empty($this->repoId) AND empty($this->groupId)) {
            throw new Exception("Vous devez spéficier un repo ou un groupe");
        }

        /**
         *  Si un repo ET un groupe ont été renseignés alors on quitte
         */
        if (!empty($this->repoId) AND !empty($this->groupId)) {
            throw new Exception("Vous devez spécifier soit un repo, soit un groupe mais pas les deux");
        }

        /**
         *  Vérification de l'action
         */
        if (empty($this->action)) {
            throw new Exception("Vous devez spécifier une action");
        }

        /**
         *  Cas où on ajoute un repo seul
         */
        if (!empty($this->repoId)) {
            /**
             *  On vérifie que l'Id de repo renseigné existe
             */
            $myrepo = new Repo();
            $myrepo->setId($this->repoId);

            if ($myrepo->existsId($this->repoId) === false) {
                throw new Exception("Le repo spécifié n'existe pas");
            }
        }

        /**
         *  Cas où on ajoute un groupe
         */
        if (!empty($this->groupId)) {
            /**
             *  On vérifie que l'Id de groupe renseigné existe
             */
            $mygroup = new Group('repo');
            $mygroup->setId($this->groupId);

            if ($mygroup->existsId() === false) {
                throw new Exception("Le groupe spécifié n'existe pas");
            }
        }

        /**
         *  On vérifie que la/les adresses renseignées sont valides
         *  Si la chaine contient une virgule alors il y a plusieurs adresses renseignées
         *  On va devoir exploser la chaine pour pouvoir tester chaque adresse mail, puis reconstruire la chaine en ne conservant que les adresses valides
         */
        if (!empty($this->mailRecipient)) {
            if (preg_match('/,/', $this->mailRecipient)) {
                $mailRecipientTest = explode(',', $this->mailRecipient);

                foreach ($mailRecipientTest as $mail) {
                    $mail = Common::validateData($mail);
                    /**
                     *  On vérifie que l'adresse email est valide
                     */
                    if (Common::validateMail($mail) === false) {
                        throw new Exception("Adresse email invalide : $mail");
                    }
                }

            /**
             *  Cas où 1 seule adresse mail a été renseignée
             */
            } else {
                if (Common::validateMail($this->mailRecipient) === false) {
                    throw new Exception("Adresse email invalide : $mail");
                }
            }
        } else {
            /**
             *  Si aucune adresse mail de contact n'a été fournie, on passe les paramètres de notifications à 'no'
             */
            $this->setNotification('on-error', 'no');
            $this->setNotification('on-success', 'no');
        }

        /**
         *  Insertion en base de données
         */
        try {
            $stmt = $this->db->prepare("INSERT INTO Planifications ('Type', 'Frequency', 'Day', 'Date', 'Time', 'Action', 'Id_repo', 'Id_group', 'Gpgcheck', 'Gpgresign', 'Reminder', 'Notification_error', 'Notification_success', 'Mail_recipient', 'Status') VALUES (:plantype, :frequency, :day, :date, :time, :action, :idrepo, :idgroup, :gpgcheck, :gpgresign, :reminder, :notification_error, :notification_success, :mailrecipient, 'queued')");
            $stmt->bindValue(':plantype', $this->type);
            $stmt->bindValue(':frequency', $this->frequency);
            $stmt->bindValue(':day', $this->day);
            $stmt->bindValue(':date', $this->date);
            $stmt->bindValue(':time', $this->time);
            $stmt->bindValue(':action', $this->action);
            $stmt->bindValue(':idrepo', $this->repoId);
            $stmt->bindValue(':idgroup', $this->groupId);
            $stmt->bindValue(':gpgcheck', $this->gpgCheck);
            $stmt->bindValue(':gpgresign', $this->gpgResign);
            $stmt->bindValue(':notification_error', $this->notificationOnError);
            $stmt->bindValue(':notification_success', $this->notificationOnSuccess);
            $stmt->bindValue(':mailrecipient', $this->mailRecipient);
            $stmt->bindValue(':reminder', $this->reminder);
            $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }
    }

    /**
     *  Suppression d'une planification
     */
    public function remove(string $planId)
    {
        try {
            $stmt = $this->db->prepare("UPDATE planifications SET Status = 'canceled' WHERE Id = :id");
            $stmt->bindValue(':id', $planId);
            $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }
    }

    /**
     *  Exécution d'une planification
     */
    public function exec()
    {
        /**
         *  On génère un nouveau log pour cette planification
         *  Ce log général reprendra tous les sous-logs de chaque opération lancée par cette planification.
         */
        $this->log = new Log('plan');

        /**
         *  Passe le status de la planification à "running", jusqu'à maintenant le status était "queued"
         */
        try {
            $stmt = $this->db->prepare("UPDATE planifications SET Status = 'running' WHERE Id = :id");
            $stmt->bindValue(':id', $this->id);
            $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

        /**
         *  0. Démarre l'enregistrement de la planification
         */
        ob_start();

        try {
            // VERIFICATIONS //

            /**
             *  1. Si les planifications ne sont pas activées, on quitte
             */
            if (AUTOMATISATION_ENABLED != "yes") throw new Exception("Erreur (EP01) : Les planifications ne sont pas activées. Vous pouvez modifier ce paramètre depuis l'onglet Configuration.");
            
            /**
             *  2. On instancie des objets Operation et Group
             */
            $this->op = new Operation();
            $this->op->setType('plan');
            $this->op->repo = new Repo();
            $this->op->group = new Group('repo');

            /**
             *  3. Récupération des détails de la planification en cours d'exécution, afin de savoir quels repos ou quel groupe sont impliqués et quelle action effectuer
             */
            $this->getInfo();
            
            /**
             *  4. Vérification de l'action renseignée
             */
            $this->checkAction();

            /**
             *  5. Si l'action est 'update' alors on vérifie que cette action est autorisée et on doit avoir renseigné gpgCheck et gpgResign
             */
            if ($this->op->getAction() == "update") {
                $this->checkAction_update_allowed();
                $this->checkAction_update_gpgCheck();
                $this->checkAction_update_gpgResign();
            }

            /**
             *  6. Si l'action est '->' alors on vérifie que cette action est autorisée
             */
            if (strpos(htmlspecialchars_decode($this->op->getAction()), '->') !== false) {
                $this->checkAction_env_allowed();
            }

            /**
             *  7. Vérification si il s'agit d'un repo ou d'un groupe
             */
            $this->checkIfRepoOrGroup();

            /**
             *  8. Si on a renseigné un seul repo à traiter alors il faut vérifier qu'il existe bien (il a pu être supprimé depuis que la planification a été créée)
             *  Puis il faut récupérer son vrai nom (Redhat) ou son hôte source (Debian)
             */
            if (!empty($this->op->repo->getName())) {
                /**
                 *  On vérifie que l'Id de repo existe bien
                 */
                if ($this->op->repo->existsId($this->op->repo->getId(), 'active') === false) {
                    throw new Exception("Erreur (CP08) : L'Id de repo <b>".$this->op->repo->getId()."</b> n'existe pas");
                }
            }

            /**
             *  9. Si on a renseigné un groupe plutôt qu'un seul repo à traiter, alors on vérifie que le groupe existe dans le fichier de groupe (il a pu être supprimé depuis que la planification a été créée)
             *  Puis on récupère toute la liste du groupe
             */
            if (!empty($this->op->group->getName())) {
                /**
                 *  On vérifie que le groupe existe
                 */
                if ($this->op->group->exists($this->op->group->getName()) === false) {
                    throw new Exception("Erreur (CP14) : Le groupe <b>".$this->op->group->getName()."</b> n'existe pas");
                }

                /**
                 *  On récupère la liste des repos dans ce groupe
                 */
                $this->getGroupRepoList();
            }
        /**
         *  Clôture du try/catch pour la partie Vérifications
         */
        } catch(Exception $e) {
            $this->close(1, $e->getMessage());
            return;
        }

        // TRAITEMENT //

        /**
         *  On placera dans ce tableau les repos qui ont été traités par cette planification.
         */
        $processedRepos = array();

        /**
         *  1. Cas où on traite 1 repo seulement
         */
        if (!empty($this->op->repo->getName()) AND empty($this->op->group->getName())) {
            /**
             *  Traitement
             *  On transmet l'ID de la planification dans $this->op->id_plan, ceci afin de déclarer une nouvelle opération en BDD avec l'id de la planification qui l'a lancée
             */
            $this->op->setId_plan($this->id);

            /**
             *  Si $this->op->getAction() = update alors on met à jour le repo
             */
            if ($this->op->getAction() == "update") {
                /**
                 *  Set de GPG Check
                 */
                $this->op->repo->setTargetGpgCheck($this->op->getTargetGpgCheck());
                /**
                 *  Set de GPG Resign
                 */
                $this->op->repo->setTargetGpgResign($this->op->getTargetGpgResign());
                /**
                 *  Exécution de l'action 'update'
                 */
                $this->op->exec_update();
                /**
                 *  On ajoute le repo en erreur à la liste des repo traités par cette planifications
                 */
                if (OS_FAMILY == "Redhat") $processedRepos[] = array('Repo' => $this->op->repo->getName(), 'Status' => $this->op->getStatus());
                if (OS_FAMILY == "Debian") $processedRepos[] = array('Repo' => $this->op->repo->getName().' ('.$this->op->repo->getDist().') '.$this->op->repo->getSection(), 'Status' => $this->op->getStatus());
                /**
                 *  Si l'opération est en erreur, on quitte avec un message d'erreur
                 */
                if ($this->op->getStatus() == 'error') {
                    $this->close(2, 'Une erreur est survenue pendant la mise à jour du repo, voir les logs', $processedRepos);
                    return;
                }
            }

            /**
             *  Si $this->op->getAction() contient '->' alors il s'agit d'un changement d'env
             */
            if (strpos(htmlspecialchars_decode($this->op->getAction()), '->') !== false) {
                $envs = htmlspecialchars_decode($this->op->getAction());

                /**
                 *  Récupération de l'environnement source et de l'environnement cible
                 */
                $this->op->repo->setEnv(exec("echo '".$envs."' | awk -F '->' '{print $1}'"));
                $this->op->repo->setTargetEnv(exec("echo '".$envs."' | awk -F '->' '{print $2}'"));


                if (empty($this->op->repo->getEnv()) OR empty($this->op->repo->getTargetEnv())) {
                    /**
                     *  On ajoute le repo en erreur à la liste des repo traités par cette planifications
                     */
                    if (OS_FAMILY == "Redhat") $processedRepos[] = array('Repo' => $this->op->repo->getName(), 'Status' => 'error');
                    if (OS_FAMILY == "Debian") $processedRepos[] = array('Repo' => $this->op->repo->getName().' ('.$this->op->repo->getDist().') '.$this->op->repo->getSection(), 'Status' => 'error');

                    $this->close(1, 'Erreur (EP04) : Environnement(s) non défini(s)', $processedRepos); // On sort avec 1 car on considère que c'est une erreur de type vérification
                    return;
                }

                /**
                 *  Traitement
                 */
                $this->log->title = 'NOUVEL ENVIRONNEMENT';
                /**
                 *  Exécution de l'action 'env'
                 */
                $this->op->exec_env();
                /**
                 *  On ajoute le repo en erreur à la liste des repo traités par cette planification
                 */
                if (OS_FAMILY == "Redhat") $processedRepos[] = array('Repo' => $this->op->repo->getName(), 'Status' => $this->op->getStatus());
                if (OS_FAMILY == "Debian") $processedRepos[] = array('Repo' => $this->op->repo->getName().' ('.$this->op->repo->getDist().') '.$this->op->repo->getSection(), 'Status' => $this->op->getStatus());
                /**
                 *  Si l'opération est en erreur, on quitte avec un message d'erreur
                 */
                if ($this->op->getStatus() == 'error') {
                    $this->close(2, 'Une erreur est survenue pendant le traitement du repo, voir les logs', $processedRepos);
                    return;
                }
            }
        }

        /**
         *  2. Cas où on traite un groupe de repos/sections
         */
        if (!empty($this->op->group->getName()) AND !empty($this->groupList)) {
            /**
             *  Comme on boucle pour traiter plusieurs repos/sections, on ne peut pas tout quitter en cas d'erreur tant qu'on a pas bouclé sur tous les repos.
             *  Du coup on initialise une variable qu'on incrémentera en cas d'erreur.
             *  A la fin si cette variable > 0 alors on pourra quitter ce script en erreur ($this->close 1)
             */
            $plan_error = 0;

            /**
             *  On placera dans ce tableau les repos qui ont été traités par cette planification.
             */
            $processedRepos = array();

            /**
             *  Traitement
             *  On transmet l'ID de la planification dans $this->op->id_plan, ceci afin de déclarer une nouvelle opération en BDD avec l'id de la planification qui l'a lancée
             */
            $this->op->setId_plan($this->id);

            /**
             *  On traite chaque ligne de groupList
             */
            foreach($this->groupList as $repo) {
                /**
                 *  Pour chaque ligne on récupère les infos du repo/section grace à son Id
                 *  Les paramètres GPG Check et GPG Resign récupérées seront écrasés par les paramètres fournis par l'utilisateur lors 
                 *  de la création de la planification. Ces paramètres qui sont actuellement stockés dans $this->op->gpgCheck et $this->op->gpgResign/signed seront
                 *  rappelés au début de l'éxécution de exec_update (update.php) afin justement d'écraser $this->op->repo->gpgCheck et $this->op->repo->Resign
                 */
                $this->op->repo->setId($repo['Id']);
                $this->op->repo->db_getAllById();
                
                /**
                 *  Si $this->op->getAction() = update alors on met à jour le repo
                 */
                if ($this->op->getAction() == "update") {
                    /**
                     *  Set de GPG Check
                     */
                    $this->op->repo->setTargetGpgCheck($this->op->getTargetGpgCheck());
                    /**
                     *  Set de GPG Resign
                     */
                    $this->op->repo->setTargetGpgResign($this->op->getTargetGpgResign());
                    /**
                     *  Exécution de l'action 'update'
                     */
                    if ($this->op->exec_update() === false) {
                        $plan_error++;
                    }

                    $this->logList[] = $this->op->log->location;

                    /**
                     *  On ajoute le repo et son status (error ou done) à la liste des repo traités par cette planifications
                     */
                    if (OS_FAMILY == "Redhat") $processedRepos[] = array('Repo' => $this->op->repo->getName(), 'Status' => $this->op->getStatus());
                    if (OS_FAMILY == "Debian") $processedRepos[] = array('Repo' => $this->op->repo->getName().' ('.$this->op->repo->getDist().') '.$this->op->repo->getSection(), 'Status' => $this->op->getStatus());
                }

                /**
                 *  Si $this->op->getAction() contient -> alors il s'agit d'un changement d'env
                 */
                if (strpos(htmlspecialchars_decode($this->op->getAction()), '->') !== false) {
                    $envs = htmlspecialchars_decode($this->op->getAction());

                    $this->op->repo->setEnv(exec("echo '".$envs."' | awk -F '->' '{print $1}'"));
                    $this->op->repo->setTargetEnv(exec("echo '".$envs."' | awk -F '->' '{print $2}'"));
        
                    $this->log->title = 'NOUVEL ENVIRONNEMENT';

                    /**
                     *  Exécution de l'opération 'env'
                     */
                    if ($this->op->exec_env() === false) {
                        $plan_error++;
                    }

                    $this->logList[] = $this->op->log->location;

                    /**
                     *  On ajoute le repo et son status (error ou done) à la liste des repo traités par cette planifications
                     */
                    if (OS_FAMILY == "Redhat") $processedRepos[] = array('Repo' => $this->op->repo->getName(), 'Status' => $this->op->getStatus());
                    if (OS_FAMILY == "Debian") $processedRepos[] = array('Repo' => $this->op->repo->getName().' ('.$this->op->repo->getDist().') '.$this->op->repo->getSection(), 'Status' => $this->op->getStatus());
                }
            }

            /**
             *  Si on a rencontré des erreurs dans la boucle, alors on quitte avec un message d'erreur
             */
            if ($plan_error > 0) {
                $this->close(2, 'Une erreur est survenue pendant le traitement de ce groupe, voir les logs', $processedRepos);
                return;
            }
        }

        /**
         *  Si on est arrivé jusqu'ici alors on peut quitter sans erreur
         */
        $this->close(0, '', $processedRepos);
        return;
    }

    /**
     *  Générer les messages de rappels
     *  Retourne le message approprié
     */
    public function generateReminders() {
        $this->op = new Operation();
        $this->op->setType('plan');
        $this->op->group = new Group('repo');
            
        /**
         *  1. Récupération des informations de la planification
         */
        $this->getInfo();
    
        // VERIFICATIONS //
        try {
            /**
             *  2. Vérification de l'action renseignée
             */
            $this->checkAction();
            
            /**
             *  3. Vérification si il s'agit d'un repo ou d'un groupe
             */
            $this->checkIfRepoOrGroup();
            
            /**
             *  4. Si on a renseigné un seul repo à traiter alors il faut vérifier qu'il existe bien (il a pu être supprimé depuis que la planification a été créée)
             */
            if (!empty($this->op->repo->getName())) {
                /**
                 *  On vérifie que l'Id de repo existe bien
                 */
                if ($this->op->repo->existsId($this->op->repo->getId(), 'active') === false) {
                    throw new Exception("Erreur (CP08) : L'Id de repo <b>".$this->op->repo->getId()."</b> n'existe pas");
                }
            }
        
            /**
             *  5. Si on a renseigné un groupe (commence par @) plutôt qu'un seul repo à traiter, alors on vérifie que le groupe existe dans le fichier de groupe (il a pu être supprimé depuis que la planification a été créée)
             *  Puis on récupère toute la liste du groupe
             */
            if (!empty($this->op->group->getName())) {
                /**
                 *  On vérifie que le groupe existe
                 */
                if ($this->op->group->exists($this->op->group->getName()) === false) {
                    throw new Exception("Erreur (CP14) : Le groupe <b>".$this->op->group->getName()."</b> n'existe pas");
                }

                /**
                 *  On récupère la liste des repos dans ce groupe
                 */
                $this->getGroupRepoList();
            }

        /**
         *  Cloture du try/catch pour la partie Vérifications
         */
        } catch(Exception $e) {
            return $e->getMessage();
        }
    
    
        // TRAITEMENT //
        
        /**
         *  Cas où la planif à rappeler ne concerne qu'un seul repo/section
         */
        if (!empty($this->op->repo->getName())) {

            /**
             *  Cas où l'action prévue est une mise à jour
             */
            if ($this->op->getAction() == "update") {
                if (OS_FAMILY == "Redhat") return "Mise à jour du repo <b>".$this->op->repo->getName()."</b> <span>".DEFAULT_ENV."</span>";
                if (OS_FAMILY == "Debian") return "Mise à jour de la section <b>".$this->op->repo->getSection()."</b> du repo <b>".$this->op->repo->getName()."</b> (".$this->op->repo->getDist().") <span>".DEFAULT_ENV."</span>";
            }
    
            /**
             *  Cas où l'action prévue est une création d'env
             */
            if (strpos(htmlspecialchars_decode($this->op->getAction()), '->') !== false) {
                $envs = htmlspecialchars_decode($this->op->getAction());

                $this->op->repo->setEnv(exec("echo '".$envs."' | awk -F '->' '{print $1}'"));
                $this->op->repo->setTargetEnv(exec("echo '".$envs."' | awk -F '->' '{print $2}'"));
        
                if (empty($this->op->repo->getEnv()) AND empty($this->op->repo->getTargetEnv())) return "Erreur : l'environnement source ou de destination est inconnu";
        
                if (OS_FAMILY == "Redhat") return "Changement d'environnement (".$this->op->repo->getEnv()." -> ".$this->op->repo->getTargetEnv().") du repo <b>".$this->op->repo->getName()."</b>";
                if (OS_FAMILY == "Debian") return "Changement d'environnement (".$this->op->repo->getEnv()." -> ".$this->op->repo->getTargetEnv().") de la section <b>".$this->op->repo->getSection()."</b> du repo <b>".$this->op->repo->getName()."</b> (".$this->op->repo->getDist().")";
            }
        }
    
        /**
         *  Cas où la planif à rappeler concerne un groupe de repo
         */
        if (!empty($this->op->group->getName()) AND !empty($this->groupList)) {

            foreach($this->groupList as $line) {

                /**
                 *  Pour chaque ligne on récupère les infos du repo/section
                 */
                $this->op->repo->setName($line['Name']);
                if (OS_FAMILY == "Debian") {
                    $this->op->repo->setDist($line['Dist']);
                    $this->op->repo->setSection($line['Section']);
                }

                /**
                 *  Cas où l'action prévue est une mise à jour
                 */
                if ($this->op->getAction() == "update") {
                    if (OS_FAMILY == "Redhat") return "Mise à jour des repos du groupe <b>".$this->op->group->getName()."</b> (environnement ".DEFAULT_ENV.")";
                    if (OS_FAMILY == "Debian") return "Mise à jour des sections de repos du groupe <b>".$this->op->group->getName()."</b>";
                }
    
                /**
                 *  Cas où l'action prévue est un changement d'env
                 */
                if (strpos(htmlspecialchars_decode($this->op->getAction()), '->') !== false) {
                    $envs = htmlspecialchars_decode($this->op->getAction());

                    $this->op->repo->setEnv(exec("echo '".$envs."' | awk -F '->' '{print $1}'"));
                    $this->op->repo->setTargetEnv(exec("echo '".$envs."' | awk -F '->' '{print $2}'"));
                    if (empty($this->op->repo->getEnv()) AND empty($this->op->repo->getTargetEnv())) return "Erreur : l'environnement source ou de destination est inconnu";

                    if (OS_FAMILY == "Redhat") return "Changement d'environnement (".$this->op->repo->getEnv()." -> ".$this->op->repo->getTargetEnv().") des repos du groupe <b>".$this->op->group->getName()."</b>";
                    if (OS_FAMILY == "Debian") return "Changement d'environnement (".$this->op->repo->getEnv()." -> ".$this->op->repo->getTargetEnv().") des sections de repos du groupe <b>".$this->op->group->getName()."</b>";
                }
            }
        }
    }

    /**
     *  Clôture d'une planification exécutée
     *  Génère le récapitulatif, le fichier de log et envoi un mail d'erreur si il y a eu une erreur.
     */
    public function close($planError, $plan_msg_error, $processedRepos = null) {
        /**
         *  Suppression des lignes vides dans le message d'erreur si il y en a
         */
        if ($planError != 0) $plan_msg_error = exec("echo \"$plan_msg_error\" | sed '/^$/d'");

        /**
         *  Mise à jour du status de la planification en BDD
         *  On ne met à jour uniquement si le type de planification = 'plan'. Pour les planifications régulière ('regular') il faut les remettre en status queued.
         */
        try {
            if ($this->type == 'plan') {
                if ($planError == 0) {
                    $stmt = $this->db->prepare("UPDATE planifications SET Status = :plan_status, Logfile = :plan_logfile WHERE Id = :plan_id");
                    $stmt->bindValue(':plan_status', 'done');
                    $stmt->bindValue(':plan_logfile', $this->log->name);
                    $stmt->bindValue(':plan_id', $this->id);
                } else {
                    $stmt = $this->db->prepare("UPDATE planifications SET Status = :plan_status, Error = :plan_msg_error, Logfile = :plan_logfile WHERE Id = :plan_id");
                    $stmt->bindValue(':plan_status', 'error');
                    $stmt->bindValue(':plan_msg_error', $plan_msg_error);
                    $stmt->bindValue(':plan_logfile', $this->log->name);
                    $stmt->bindValue(':plan_id', $this->id);
                }
            }
            if ($this->type == 'regular') {
                if ($planError == 0) {
                    $stmt = $this->db->prepare("UPDATE planifications SET Status = :plan_status, Logfile = :plan_logfile WHERE Id = :plan_id");
                    $stmt->bindValue(':plan_status', 'queued');
                    $stmt->bindValue(':plan_logfile', $this->log->name);
                    $stmt->bindValue(':plan_id', $this->id);
                } else {
                    $stmt = $this->db->prepare("UPDATE planifications SET Status = :plan_status, Error = :plan_msg_error, Logfile = :plan_logfile WHERE Id = :plan_id");
                    $stmt->bindValue(':plan_status', 'queued');
                    $stmt->bindValue(':plan_msg_error', $plan_msg_error);
                    $stmt->bindValue(':plan_logfile', $this->log->name);
                    $stmt->bindValue(':plan_id', $this->id);
                }
            }
            $stmt->execute(); unset($stmt);
        } catch(Exception $e) {
            Common::dbError($e);
        }

        /**
         *  Si l'erreur est de type 1 (erreur lors des vérifications de l'opération), on affiche les erreurs avec echo, elles seront capturées par ob_get_clean() et affichées dans le fichier de log
         *  On ajoute également les données connues de la planification, le tableau récapitulatif n'ayant pas pu être généré par l'opération puisqu'on a rencontré une erreur avant qu'elle ne se lance.
         */
        if ($planError == 1) {
            echo "<span class='redtext'>$plan_msg_error</span>";
            echo '<p><b>Détails de la planification :</b></p>';
            echo '<table>';
            echo '<tr><td><b>Action : </b></td><td>'.$this->op->getAction().'</td></tr>';
            if (!empty($this->op->group->getName()))   echo "<tr><td><b>Groupe : </b></td><td>".$this->op->group->getName()."</td></tr>";
            if (!empty($this->op->repo->getName()))    echo "<tr><td><b>Repo : </b></td><td>".$this->op->repo->getName()."</td></tr>";
            if (!empty($this->op->repo->getDist()))    echo "<tr><td><b>Dist : </b></td><td>".$this->op->repo->getDist()."</td></tr>";
            if (!empty($this->op->repo->getSection())) echo "<tr><td><b>Section : </b></td><td>".$this->op->repo->getSection()."</td></tr>";
            echo '</table>';
        }

// Contenu du fichier de log de la planification //

        /**
         *  Cas où on traite un groupe de repo
         *  Dans ce cas, chaque repo mis à jour crée son propre fichier de log. On a récupéré le chemin de ces fichiers de log au cours de l'opération et on l'a placé dans un array $this->logList
         *  On parcourt donc cet array pour récupérer le contenu de chaque sous-fichier de log afin de créer un fichier de log global de la planification  
         */
        if (!empty($this->op->group->getName())) {
            if ($planError != 1) {
                /**
                 *  On laisse 3 secondes au script logbuilder.php (dans le cas où l'action est update) pour finir de forger les sous-fichiers de log, sinon on prend le risque de récupérer des contenus vide car on va trop vite
                 */
                sleep(3); 
                $content = '';
                
                /**
                 *  Si l'array $this->logList contient des sous-fichier de log alors on récupère leur contenu en le placant dans $content
                 */
                if (!empty($this->logList)) {
                    foreach ($this->logList as $log) {
                        $content .= file_get_contents($log) . '<br><hr><br>';
                    }
                }

            /**
             *  Cas où une erreur est survenue lors des vérifications
             */
            } else {
                $content = ob_get_clean();
            }
        }
        
        /**
         *  Cas où on traite un seul repo
         */
        if (!empty($this->op->repo->getName()) AND empty($this->op->group->getName())) {
            /**
             *  Si l'action est 'update', un sous-fichier de log sera créé par la fonction $repo->update(). Ce fichier de log existera uniquement si la fonction a pu se lancer (donc pas d'erreur lors des vérifications). On récupère donc le contenu de ce fichier uniquement si il n'y a pas eu d'erreur lors des vérifications ($planError != 1).
             */
            if ($planError != 1) {
                /**
                 *  On laisse 3 secondes au script logbuilder.php pour finir de forger le sous-fichier de log, sinon on prend le risque de récupérer un contenu vide car on va trop vite
                 */
                sleep(3);
                /**
                 *  On récupère le contenu du fichier de log généré par la fonction $repo->update() si celui-ci existe
                 *  Ce contenu sera ensuite injecté dans le fichier de log de la planification
                 */
                if (!empty($this->op->log->location)) {
                    $content = file_get_contents($this->op->log->location);
                } else {
                    $content = '';
                }

            /**
             *  Cas où une erreur est survenue lors des vérifications
             */
            } else {
                $content = ob_get_clean();
            }
        }

        /**
         *  Génération du fichier de log final à partir d'un template, le contenu précédemment récupéré est alors inclu dans le template
         */
        include(ROOT."/templates/planification_log.inc.php");
        $this->log->write($logContent);
        $this->log->close();

// Contenu du mail de la planification //

        /**
         *  On génère la liste du repo, ou du groupe traité
         */
        if (!empty($processedRepos)) {
            /**
             *  Ajout de l'action effectuée
             */
            $msg_processed_repos = '<br><br><b>Action</b> : ';

            if ($this->op->getAction() == 'update') {
                $msg_processed_repos .= 'mise à jour';
            } else {
                $msg_processed_repos .= "pointage de l'env ".$this->op->getAction();
            }
            
            $msg_processed_repos .= '<br><br><b>Repo(s) traité(s) :</b><br>';

            /**
             *  On trie l'array par status des repos afin de regrouper tous les repos OK et tous les repos en erreur
             */
            array_multisort(array_column($processedRepos, 'Status'), SORT_DESC, $processedRepos);

            /**
             *  On parcourt la liste des repos traités
             */
            if (is_array($processedRepos)) {
                foreach ($processedRepos as $processedRepo) {
                    if ($processedRepo['Status'] == 'done') {
                        $msg_processed_repos .= '[ OK ] ' . $processedRepo['Repo'] . '<br>';
                    }
                    if ($processedRepo['Status'] == 'error') {
                        $msg_processed_repos .= '[ ERREUR ] ' . $processedRepo['Repo'] . '<br>';
                    }
                }
            }
        }

        /**
         *  Envoi d'un mail si les notifications sont activées
         */

        /**
         *  Envoi d'un mail si il n'y a pas eu d'erreurs
         */
        if ($this->notificationOnSuccess == 'yes') {
            
            if ($planError == 0) {
                /**
                 *  Préparation du message à inclure dans le mail
                 */
                if ($this->type == 'plan') {
                    $plan_title   = "[OK] - Planification n°{$this->id} sur ".WWW_HOSTNAME;
                    $plan_pre_msg = "Une planification vient de se terminer.";
                }
                if ($this->type == 'regular') {
                    $plan_title   = "[OK] - Planification récurrente n°{$this->id} sur ".WWW_HOSTNAME;
                    $plan_pre_msg = "Une planification récurrente vient de se terminer.";   
                }
                $plan_msg = "La planification s'est terminée sans erreur.".PHP_EOL;

                /**
                 *  On ajoute le repo ou le groupe traité à la suite du message
                 */
                if (!empty($msg_processed_repos)) {
                    $plan_msg .= $msg_processed_repos . PHP_EOL;
                }

                /**
                 *  Template HTML du mail, inclu une variable $template contenant le corps du mail avec $plan_msg
                 */
                include(ROOT."/templates/plan_mail.inc.php");
                $this->sendMail($plan_title, $template);
            }
        }

        /**
         *  Envoi d'un mail si il y a eu des erreurs
         */
        if ($this->notificationOnError == 'yes') {

            if ($planError != 0) {
                /**
                 *  Préparation du message à inclure dans le mail
                 */
                if ($this->type == 'plan') {
                    $plan_title   = "[ERREUR] - Planification n°{$this->id} sur ".WWW_HOSTNAME;
                    $plan_pre_msg = "Une planification s'est mal terminée.";
                }
                if ($this->type == 'regular') {
                    $plan_title   = "[ERREUR] - Planification récurrente n°{$this->id} sur ".WWW_HOSTNAME;
                    $plan_pre_msg = "Une planification récurrente s'est mal terminée.";
                }
                $plan_msg = 'Cette planification a rencontré une erreur'.PHP_EOL;

                /**
                 *  On ajoute le repo ou le groupe traité à la suite du message
                 */
                if (!empty($msg_processed_repos)) {
                    $plan_msg .= $msg_processed_repos . PHP_EOL;
                }

                /**
                 *  Template HTML du mail, inclu une variable $template contenant le corps du mail avec $plan_msg
                 */
                include(ROOT."/templates/plan_mail.inc.php");
                $this->sendMail($plan_title, $template);
            }
        }
    }

    /**
     *  Envoi d'un mail d'erreur ou de rappel de planification
     *  A partir d'une variable $template contenant le corps HTML du mail à envoyer
     */
    public function sendMail($title, $template)
    {    
        /**
         *  On envoi un mail si une adresse de destination a été renseignée (non-vide et non null)
         */
        if (!empty($this->mailRecipient)) {
            /**
             *  Pour envoyer un mail HTML il faut inclure ces headers
             */
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-type: text/html; charset=utf8';
            $headers[] = "From: noreply@".WWW_HOSTNAME;
            $headers[] = "X-Sender: noreply@".WWW_HOSTNAME;
            $headers[] = "Reply-To: noreply@".WWW_HOSTNAME;
            mail($this->mailRecipient, $title, $template, implode("\r\n", $headers));
        }
    }

/**
 *  VERIFICATIONS
 *  Code d'erreurs : CP "Check Planification"
 */
    private function checkAction()
    {
        if (empty($this->op->getAction())) throw new Exception("Erreur (CP01) : Aucune action n'est spécifiée dans cette planification");
    }

    private function checkAction_update_allowed()
    {
        /**
         *  Si la mise à jour des repos n'est pas autorisée, on quitte
         */
        if (ALLOW_AUTOUPDATE_REPOS != "yes") throw new Exception("Erreur (CP02) : La mise à jour des miroirs par planification n'est pas autorisée. Vous pouvez modifier ce paramètre depuis l'onglet Configuration");
    }

    private function checkAction_update_gpgCheck()
    {
        if (empty($this->op->repo->getTargetGpgCheck())) throw new Exception("Erreur (CP03) : Vérification des signatures GPG non spécifié dans cette planification");
    }

    private function checkAction_update_gpgResign()
    {
        if (empty($this->op->repo->getTargetGpgResign())) throw new Exception("Erreur (CP04) : Signature des paquets avec GPG non spécifié dans cette planification");
    }

    private function checkAction_env_allowed()
    {
        /**
         *  Si le changement d'environnement n'est pas autorisé, on quitte
         */
        if (ALLOW_AUTOUPDATE_REPOS_ENV != "yes") throw new Exception("Erreur (CP05) : Le changement d'environnement par planification n'est pas autorisé. Vous pouvez modifier ce paramètre depuis l'onglet Configuration.");
    }

    /**
     *  Vérification si on traite un repo seul ou un groupe
     */
    private function checkIfRepoOrGroup()
    {
        if (empty($this->op->repo->getName()) AND empty($this->op->group->getName())) throw new Exception("Erreur (CP06) : Aucun repo ou groupe spécifié");
    
        /**
         *  On va traiter soit un repo soit un groupe de repo, ça ne peut pas être les deux, donc on vérifie que planRepo et planGroup ne sont pas tous les deux renseignés en même temps :
         */
        if (!empty($this->op->repo->getName()) AND !empty($this->op->group->getName())) {
            if (OS_FAMILY == "Redhat") throw new Exception("Erreur (CP07) : Il n'est pas possible de traiter à la fois un repo et un groupe de repos");
            if (OS_FAMILY == "Debian") throw new Exception("Erreur (CP07) : Il n'est pas possible de traiter à la fois une section et un groupe de sections");
        }
    }
  
    /**
     *  Récupération de la liste des repo dans le groupe
     */
    private function getGroupRepoList()
    {
        /**
         *  On récupère tous les repos du groupe
         */
        $this->groupList = $this->op->group->listReposMembers_byEnv($this->op->group->getName(), DEFAULT_ENV);
    
        if (empty($this->groupList)) {
            if (OS_FAMILY == "Redhat") throw new Exception("Erreur (CP13) : Il n'y a aucun repo renseigné dans le groupe <b>".$this->op->group->getName()."</b>");
            if (OS_FAMILY == "Debian") throw new Exception("Erreur (CP13) : Il n'y a aucune section renseignée dans le groupe <b>".$this->op->group->getName()."</b>");
        }
    
        /**
         *  Pour chaque repo/section renseigné(e), on vérifie qu'il/elle existe
         */
        $msg_error = '';
        foreach($this->groupList as $repo) {
            $repoId   = $repo['Id'];
            $repoName = $repo['Name'];
            if (OS_FAMILY == "Debian") { // si Debian on récupère aussi la distrib et la section
                $repoDist = $repo['Dist'];
                $repoSection = $repo['Section'];
            }

            if (OS_FAMILY == "Redhat") {
                if ($this->op->repo->exists($repoName) === false) $msg_error .= "Erreur (CP15) : Le repo <b>$repoName</b> dans le groupe <b>".$this->op->group->getName()."</b> n'existe pas/plus.".PHP_EOL;
            }
            
            if (OS_FAMILY == "Debian") {
                if ($this->op->repo->section_exists($repoName, $repoDist, $repoSection) === false) $msg_error .= "Erreur (CP16) : La section <b>$repoSection</b> du repo <b>$repoName</b> (distribution <b>$repoDist</b>) dans le groupe <b>".$this->op->group->getName()."</b> n'existe pas/plus.".PHP_EOL;
            }
        }
        /**
         *  Si des repos/sections n'existent plus alors on quitte
         */
        if (!empty($msg_error)) throw new Exception($msg_error);
    }

    /**
     *  Liste des planifications en attente d'exécution
     */
    public function listQueue()
    {
        $query = $this->db->query("SELECT * FROM planifications WHERE Status = 'queued'");

        $plans = array();

        while ($datas = $query->fetchArray(SQLITE3_ASSOC)) $plans[] = $datas;

        return $plans;
    }

    /**
     *  Liste les planifications en cours d'exécution
     */
    public function listRunning()
    {
        $query = $this->db->query("SELECT * FROM planifications WHERE Status = 'running' ORDER BY Date DESC, Time DESC");

        $plans = array();

        while ($datas = $query->fetchArray(SQLITE3_ASSOC)) $plans[] = $datas;
        
        return $plans;
    }

    /**
    *  Liste les planifications terminées (tout status compris sauf canceled)
    */
    public function listDone()
    {
        $query = $this->db->query("SELECT * FROM planifications WHERE Status = 'done' OR Status = 'error' OR Status = 'stopped' ORDER BY Date DESC, Time DESC");
        
        $plans = array();

        while ($datas = $query->fetchArray(SQLITE3_ASSOC)) $plans[] = $datas;

        return $plans;
    }

    /**
     *  Liste la dernière planification exécutée
     */
    public function listLast()
    {
        $query = $this->db->query("SELECT Date, Time, Status FROM planifications WHERE Type = 'plan' AND (Status = 'done' OR Status = 'error') ORDER BY Date DESC, Time DESC LIMIT 1");
        
        $plans = array();

        while ($datas = $query->fetchArray(SQLITE3_ASSOC)) $plans = $datas;

        return $plans;
    }

    /**
     *  Liste la prochaine planification qui sera exécutée
     */
    public function listNext()
    {
        $query  = $this->db->query("SELECT Date, Time FROM planifications WHERE Type = 'plan' AND Status = 'queued' ORDER BY Date ASC, Time ASC LIMIT 1");
        
        $plans = array();

        while ($datas = $query->fetchArray(SQLITE3_ASSOC)) $plans = $datas;

        return $plans;
    }

    /**
    *   Récupère toutes les infos d'une planification
    *   Un objet Operation doit avoir été instancié pour récupérer les infos concernant le repo concerné par cette planification
    */
    private function getInfo()
    {
        /**
         *  Si l'Id de la planification n'est pas renseignée on quitte
         */
        if (empty($this->id)) {
            throw new Exception("Erreur (EP02) Impossible de récupérer les informations de la planification car son ID est vide");
        }

        try {
            $stmt = $this->db->prepare("SELECT * FROM planifications WHERE Id = :id");
            $stmt->bindValue(':id', $this->id);
            $result = $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) $datas = $row;

        /**
         *  Type de planification
         */
        $this->setType($datas['Type']);

        /**
         *  Action
         */
        $this->op->setAction($datas['Action']);

        /**
         *  Id du repo ou du groupe
         */
        if (!empty($datas['Id_repo']))  $this->op->repo->setId($datas['Id_repo']);
        if (!empty($datas['Id_group'])) $this->op->group->setId($datas['Id_group']);

        /**
         *  On récupère les infos concernant le groupe à traiter (son nom)
         */
        if (!empty($this->op->group->getId())) {
            $this->op->group->db_getName();
        }

        /**
         *  On récupère les infos concernant le repo à traiter (son nom, sa distribution...)
         */
        if (!empty($this->op->repo->getId())) {
            $this->op->repo->db_getAllById();
        }

        /**
         *  Les paramètres GPG Check et GPG Resign sont conservées de côté et seront pris en compte au début de l'exécution de exec_update()
         */
        if (!empty($datas['Gpgcheck'])) {
            $this->op->setTargetGpgCheck($datas['Gpgcheck']);
            $this->op->repo->setTargetGpgCheck($datas['Gpgcheck']);
        }
        if (!empty($datas['Gpgresign'])) {
            $this->op->setTargetGpgResign($datas['Gpgresign']);
            $this->op->repo->setTargetGpgResign($datas['Gpgcheck']);
        }

        /**
         *  Rappels de planification
         */
        $this->setReminder($datas['Reminder']);

        /**
         *  Notifications par mail si erreur ou si terminé
         */
        $this->setNotification('on-error', $datas['Notification_error']);
        $this->setNotification('on-success', $datas['Notification_success']);

        /**
         *  Adresse mail de destination
         */
        if (!empty($datas['Mail_recipient'])) {
            $this->setMailRecipient($datas['Mail_recipient']);
        }
    }
}
?>