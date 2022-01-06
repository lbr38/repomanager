<?php
require_once("${WWW_DIR}/models/Model.php");
require_once("${WWW_DIR}/models/Log.php");
require_once("${WWW_DIR}/models/Repo.php");
require_once("${WWW_DIR}/models/Group.php");
require_once("${WWW_DIR}/models/Operation.php");

class Planification extends Model {
    private $id;
    private $action;    // contiendra 'update' ou 'env->env'
    private $gpgCheck;
    private $gpgResign;
    private $status;
    private $error;
    private $logfile;

    private $type;
    private $date;
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

    public function __construct(array $variables = []) {
        extract($variables);

        /**
         *  Ouverture d'une connexion à la base de données
         */
        $this->getConnection('main', 'rw');
    }

    public function setId(string $id)
    {
        $this->id = validateData($id);
    }

    public function setDate(string $date)
    {
        $this->date = validateData($date);
    }

    public function setTime(string $time)
    {
        $this->time = validateData($time);
    }

    public function setType(string $type)
    {
        $this->type = validateData($type);
    }

    public function setFrequency(string $frequency)
    {
        $this->frequency = validateData($frequency);
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
                    die();
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
        $mailRecipient = validateData($mailRecipient);

        /**
         *  On vérifie que la/les adresses renseignées sont valides
         *  Si la chaine contient une virgule alors il y a plusieurs adresses renseignées
         */
        if (preg_match('/,/', $mailRecipient)) {
            $mailRecipient_formatted = '';

            $mailRecipient = explode(',', $mailRecipient);
            foreach ($mailRecipient as $mail) {
                $mail = validateData($mail);
                /**
                 *  On vérifie que l'adresse email en est bien une
                 */
                if (validateMail($mail) === false) {
                    throw new Exception("Adresse email invalide : $mail");
                }

                /**
                 *  On concatène toutes les adresses en les séparant par un espace
                 */
                $mailRecipient_formatted .= "$mail ";
            }

            $mailRecipient = $mailRecipient_formatted;

        /**
         *  Cas où 1 seule adresse mail a été renseignée
         */
        } else {
            if (validateMail($mailRecipient) === false) {
                throw new Exception("Adresse email invalide : $mail");
            }
        }

        $this->mailRecipient = $mailRecipient;
    }

    public function setReminder(array $reminders)
    {
        /**
         *  Si la planification est de type 'regular' (planification récurrente) alors on ne set pas de rappel
         */
        if ($this->type == 'regular') return;

        $planReminder = '';

        /**
         *  On sépare chaque jour de rappel par une virgule
         */
        foreach ($reminders as $reminder) {
            $planReminder .= validateData($reminder).',';
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

        $this->gpgCheck = validateData($state);
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

        $this->gpgResign = validateData($state);
    }

    public function setRepoId(string $id)
    {
        $this->repoId = validateData($id);
    }

    public function setGroupId(string $id)
    {
        $this->groupId = validateData($id);
    }


    /**
     *  Ajout d'une nouvelle planification en BDD
     */
    public function new() {
        global $OS_FAMILY;

        /**
         *  Vérification des paramètres
         */

        /**
         *  Si aucun repo et aucun groupe n'a été renseigné alors on quitte
         */
        if (empty($this->repoId) AND empty($this->groupId)) {
            printAlert("Aucun repo ou groupe n'a été renseigné", 'error');
            return;
        }

        /**
         *  Si un repo ET un groupe ont été renseignés alors on quitte
         */
        if (!empty($this->repoId) AND !empty($this->groupId)) {
            printAlert("Il faut renseigner soit un repo, soit un groupe mais pas les deux", 'error');
            return;
        }

        /**
         *  Vérification de l'action
         */
        if (empty($this->action)) {
            printAlert("Erreur : action vide", 'error');
            return;
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

            if ($myrepo->existsId() === false) {
                printAlert("Le repo renseigné n'existe pas", 'error');
                return;
            }
        }

        /**
         *  Cas où on ajoute un groupe
         */
        if (!empty($this->groupId)) {
            /**
             *  On vérifie que l'Id de groupe renseigné existe
             */
            $mygroup = new Group();
            $mygroup->setId($this->groupId);

            if ($mygroup->existsId() === false) {
                printAlert("Le groupe renseigné n'existe pas", 'error');
                return;
            }
        }

        /**
         *  Insertion en base de données
         */
        $stmt = $this->db->prepare("INSERT INTO Planifications ('Type', 'Frequency', 'Date', 'Time', 'Action', 'Id_repo', 'Id_group', 'Gpgcheck', 'Gpgresign', 'Reminder', 'Notification_error', 'Notification_success', 'Mail_recipient', 'Status') VALUES (:plantype, :frequency, :date, :time, :action, :idrepo, :idgroup, :gpgcheck, :gpgresign, :reminder, :notification_error, :notification_success, :mailrecipient, 'queued')");
        $stmt->bindValue(':plantype', $this->type);
        $stmt->bindValue(':frequency', $this->frequency);
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
        
        printAlert("Planification créée", 'success');
    }

/**
 *  Suppression d'une planification
 */
    public function remove() {
        if (empty($this->id)) {
            printAlert('Erreur : ID de planification non renseigné', 'error');
            return;
        }

        $stmt = $this->db->prepare("UPDATE planifications SET Status = 'canceled' WHERE Id = :id");
        $stmt->bindValue(':id', $this->id);
        $stmt->execute();

        printAlert('La planification a été supprimée', 'success');
    }

/**
 *  Exécution d'une planification
 */
    public function exec() {
        global $WWW_DIR;
        global $OS_FAMILY;
        global $AUTOMATISATION_ENABLED;
        global $ALLOW_AUTOUPDATE_REPOS;
        global $ALLOW_AUTOUPDATE_REPOS_ENV;

        /**
         *  On génère un nouveau log pour cette planification
         *  Ce log général reprendra tous les sous-logs de chaque opération lancée par cette planification.
         */
        $this->log = new Log('plan');

        /**
         *  Passe le status de la planification à "running", jusqu'à maintenant le status était "queued"
         */
        $stmt = $this->db->prepare("UPDATE planifications SET Status = 'running' WHERE Id = :id");
        $stmt->bindValue(':id', $this->id);
        $stmt->execute();
        unset($stmt);

        /**
         *  0. Démarre l'enregistrement de la planification
         */
        ob_start();

        try {
            // VERIFICATIONS //
            
            /**
             *  1. Si les planifications ne sont pas activées, on quitte
             */
            if ($AUTOMATISATION_ENABLED != "yes") throw new Exception("Erreur (EP01) : Les planifications ne sont pas activées. Vous pouvez modifier ce paramètre depuis l'onglet Configuration.");

            /**
             *  2. On instancie des objets Repo et Group
             */
            $this->op = new Operation(array('op_type' => 'plan'));
            $this->op->group = new Group();

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
            if ($this->op->action == "update") {
                $this->checkAction_update_allowed();
                $this->checkAction_update_gpgCheck();
                $this->checkAction_update_gpgResign();
            }

            /**
             *  6. Si l'action est '->' alors on vérifie que cette action est autorisée
             */
            if (strpos($this->op->action, '->') !== false) {
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
            if (!empty($this->op->repo->name)) {
                $this->checkIfRepoExists();
            }

            /**
             *  9. Si on a renseigné un groupe plutôt qu'un seul repo à traiter, alors on vérifie que le groupe existe dans le fichier de groupe (il a pu être supprimé depuis que la planification a été créée)
             *  Puis on récupère toute la liste du groupe
             */
            if (!empty($this->op->group->name)) {
                // On vérifie que le groupe existe
                $this->checkIfGroupExists();
                // On récupère la liste des repos dans ce groupe
                $this->getGroupRepoList();
            }
        /**
         *  Clôture du try/catch pour la partie Vérifications
         */
        } catch(Exception $e) {
            $this->close(1, $e->getMessage());
        }


        // TRAITEMENT //

        /**
         *  1. Cas où on traite 1 repo seulement
         */
        if (!empty($this->op->repo->name) AND empty($this->op->group->name)) {

            /**
             *  Si $this->op->action = update alors on met à jour le repo
             */
            if ($this->op->action == "update") {
                /**
                 *  Traitement
                 *  On transmet l'ID de la planification dans $this->op->id_plan, ceci afin de déclarer une nouvelle opération en BDD avec l'id de la planification qui l'a lancée
                 *  Exécution de exec_update(), puis si cette opération s'est terminée avec une erreur, alors on clos la planification en erreur
                 */
                $this->op->id_plan = $this->id;
                if ($this->op->exec_update() === false) {
                    $this->close(2, 'Une erreur est survenue pendant le traitement, voir les logs');
                }
            }

            /**
             *  Si $this->op->action contient '->' alors il s'agit d'un changement d'env
             */
            if (strpos($this->op->action, '->') !== false) {
                /**
                 *  Récupération de l'environnement source et de l'environnement cible
                 */
                $this->op->repo->env = exec("echo '{$this->op->action}' | awk -F '->' '{print $1}'");
                $this->op->repo->newEnv = exec("echo '{$this->op->action}' | awk -F '->' '{print $2}'");
                if (empty($this->op->repo->env) OR empty($this->op->repo->newEnv)) {
                    $this->close(1, 'Erreur (EP04) : Environnement(s) non défini(s)'); // On sort avec 1 car on considère que c'est une erreur de type vérification
                }

                /**
                 *  Traitement
                 */
                $this->log->title = 'NOUVEL ENVIRONNEMENT';

                /**
                 *  Récupération dans $this->op->repo->id de l'ID en BDD du repo afin de l'inclure à l'opération ci-après
                 */
                $this->op->repo->db_getId();

                /**
                 *  On démarre une nouvelle opération en précisant le repo traité ainsi que l'ID de la planification qui a lancé cette opération
                 */
                $this->op->startOperation(array('id_repo_source' => $this->op->repo->id, 'id_plan' => $this->id));

                /**
                 *  Exécution de l'opération
                 */
                try {
                    $this->op->exec_changeEnv();
                    $this->op->status = 'done';

                } catch(Exception $e) {
                    $this->op->log->steplogError($e->getMessage()); // On transmets l'erreur à $this->log->steplogError() qui va se charger de l'afficher en rouge dans le fichier de log
                    $this->op->status = 'error';
                }

                $this->op->log->steplogBuild(2);
                $this->op->closeOperation();

                /**
                 *  Si cette opération s'est terminée avec une erreur, alors on clos la planification en erreur
                 */
                if ($this->op->status == 'error') $this->close(2, "Erreur : ".$e->getMessage());
            }
        }

        /**
         *  2. Cas où on traite un groupe de repos/sections
         */
        if (!empty($this->op->group->name) AND !empty($this->groupList)) {
            /**
             *  Comme on boucle pour traiter plusieurs repos/sections, on ne peut pas tout quitter en cas d'erreur tant qu'on a pas bouclé sur tous les repos.
             *  Du coup on initialise une variable qu'on incrémentera en cas d'erreur.
             *  A la fin si cette variable > 0 alors on pourra quitter ce script en erreur ($this->close 1)
             */
            $plan_error = 0;

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
                $this->op->repo->id = $repo['Id'];
                $this->op->repo->db_getAllById();
                
                /**
                 *  Si $this->op->action = update alors on met à jour le repo
                 */
                if ($this->op->action == "update") {
                    /**
                     *  Traitement
                     *  On transmet l'ID de la planification dans $this->op->id_plan, ceci afin de déclarer une nouvelle opération en BDD avec l'id de la planification qui l'a lancée
                     *  Exécution de exec_update(), puis si cette opération s'est terminée avec une erreur, alors on clos la planification en erreur
                     */
                    $this->op->id_plan = $this->id;
                    if ($this->op->exec_update() === false) {
                        $plan_error++;
                    }

                    $this->logList[] = $this->op->log->location;
                }

                /**
                 *  Si $this->op->action contient -> alors il s'agit d'un changement d'env
                 */
                if (strpos($this->op->action, '->') !== false) {
                    try {
                        $this->op->repo->env = exec("echo '{$this->op->action}' | awk -F '->' '{print $1}'");
                        $this->op->repo->newEnv = exec("echo '{$this->op->action}' | awk -F '->' '{print $2}'");
                        if (empty($this->op->repo->env) OR empty($this->op->repo->newEnv)) {
                            if (empty($this->op->repo->env) OR empty($this->op->repo->newEnv)) {
                                throw new Exception('Erreur (EP04) : Environnement(s) non défini(s)');
                            }
                        }
                    } catch(Exception $e) {
                        /**
                         *  L'erreur est suffisamment importante pour quitter toute la planification (un ou plusieurs environnements ne sont pas définis alors on ne peut pas continuer)
                         */
                        $this->close(2, $e->getMessage());
                    }
        
                    $this->log->title = 'NOUVEL ENVIRONNEMENT';

                    /**
                     *  Récupération dans $this->op->repo->id de l'ID en BDD du repo afin de l'inclure à l'opération ci-après
                     */
                    $this->op->repo->db_getId();

                    /**
                     *  On démarre une nouvelle opération en précisant le repo traité ainsi que l'ID de la planification qui a lancé cette opération
                     */
                    $this->op->startOperation(array('id_repo_source' => $this->op->repo->id, 'id_plan' => $this->id));

                    /**
                     *  Exécution de l'opération
                     */
                    try {
                        $this->op->exec_changeEnv();
                        $this->op->status = 'done';

                    } catch(Exception $e) {
                        $this->op->log->steplogError($e->getMessage()); // On transmets l'erreur à $this->log->steplogError() qui va se charger de l'afficher en rouge dans le fichier de log
                        $this->op->status = 'error';
                        $plan_error++;
                    }

                    $this->op->log->steplogBuild(2);
                    $this->op->closeOperation();

                    $this->logList[] = $this->op->log->location;
                }
            }

            /**
             *  Si on a rencontré des erreurs dans la boucle, alors on quitte le script
             */
            if ($plan_error > 0) $this->close(2, 'Une erreur est survenue pendant le traitement de ce groupe, voir les logs');
        }

        /**
         *  Si on est arrivé jusqu'ici alors on peut quitter sans erreur
         */
        $this->close(0, '');
    }

    /**
     *  Générer les messages de rappels
     *  Retourne le message approprié
     */
    public function generateReminders() {
        global $OS_FAMILY;
        global $DEFAULT_ENV;
    
        $this->repo = new Repo();
        $this->group = new Group();
            
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
            if (!empty($this->op->repo->name)) $this->checkIfRepoExists();
        
            /**
             *  5. Si on a renseigné un groupe (commence par @) plutôt qu'un seul repo à traiter, alors on vérifie que le groupe existe dans le fichier de groupe (il a pu être supprimé depuis que la planification a été créée)
             *  Puis on récupère toute la liste du groupe
             */
            if (!empty($this->group->name)) {
                /**
                 *  On vérifie que le groupe existe
                 */
                $this->checkIfGroupExists();

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
        if (!empty($this->op->repo->name)) {

            /**
             *  Cas où l'action prévue est une mise à jour
             */
            if ($this->action == "update") {
                if ($OS_FAMILY == "Redhat") return "Mise à jour du repo {$this->op->repo->name} <span class=\"td-whitebackground\">${DEFAULT_ENV}</span>";
                if ($OS_FAMILY == "Debian") return "Mise à jour de la section {$this->op->repo->section} du repo {$this->op->repo->name} (distribution {$this->op->repo->dist}) <span class=\"td-whitebackground\">${DEFAULT_ENV}</span>";
            }
    
            /**
             *  Cas où l'action prévue est une création d'env
             */
            if (strpos($this->action, '->') !== false) {
                $this->op->repo->env = exec("echo '$this->action' | awk -F '->' '{print $1}'");
                $this->op->repo->newEnv = exec("echo '$this->action' | awk -F '->' '{print $2}'");
        
                if (empty($this->op->repo->env) AND empty($this->op->repo->newEnv)) return "Erreur : l'environnement source ou de destination est inconnu";
        
                if ($OS_FAMILY == "Redhat") return "Changement d'environnement ({$this->op->repo->env} -> {$this->op->repo->newEnv}) du repo {$this->op->repo->name}";
                if ($OS_FAMILY == "Debian") return "Changement d'environnement ({$this->op->repo->env} -> {$this->op->repo->newEnv}) de la section {$this->op->repo->section} du repo {$this->op->repo->name} (distribution {$this->op->repo->dist})";
            }
        }
    
        /**
         *  Cas où la planif à rappeler concerne un groupe de repo
         */
        if (!empty($this->group->name) AND !empty($this->groupList)) {

            foreach($this->groupList as $line) {

                /**
                 *  Pour chaque ligne on récupère les infos du repo/section
                 */
                $this->op->repo->name = $line['Name'];
                if ($OS_FAMILY == "Debian") {
                    $this->op->repo->dist = $line['Dist'];
                    $this->op->repo->section = $line['Section'];
                }

                /**
                 *  Cas où l'action prévue est une mise à jour
                 */
                if ($this->action == "update") {
                    if ($OS_FAMILY == "Redhat") return "Mise à jour des repos du groupe {$this->group->name} (environnement ${DEFAULT_ENV})";
                    if ($OS_FAMILY == "Debian") return "Mise à jour des sections de repos du groupe {$this->group->name}";
                }
    
                /**
                 *  Cas où l'action prévue est un changement d'env
                 */
                if (strpos($this->action, '->') !== false) {
                    $this->op->repo->env = exec("echo '$this->action' | awk -F '->' '{print $1}'");
                    $this->op->repo->newEnv = exec("echo '$this->action' | awk -F '->' '{print $2}'");
                    if (empty($this->op->repo->env) AND empty($this->op->repo->newEnv)) return "Erreur : l'environnement source ou de destination est inconnu";

                    if ($OS_FAMILY == "Redhat") return "Changement d'environnement ({$this->op->repo->env} -> {$this->op->repo->newEnv}) des repos du groupe {$this->group->name}";
                    if ($OS_FAMILY == "Debian") return "Changement d'environnement ({$this->op->repo->env} -> {$this->op->repo->newEnv}) des sections de repos du groupe {$this->group->name}";
                }
            }
        }
    }

/**
 *  Clôture d'une planification exécutée
 *  Génère le récapitulatif, le fichier de log et envoi un mail d'erreur si il y a eu une erreur
 */
    public function close($planError, $plan_msg_error) {
        global $PLAN_LOGS_DIR;
        global $EMAIL_DEST;
        global $WWW_DIR;
        global $WWW_HOSTNAME;

        /**
         *  Suppression des lignes vides dans le message d'erreur si il y en a
         */
        if ($planError != 0) $plan_msg_error = exec("echo \"$plan_msg_error\" | sed '/^$/d'");

        /**
         *  Mise à jour du status de la planification en BDD
         *  On ne met à jour uniquement si le type de planification = 'plan'. Pour les planifications régulière ('regular') il faut les remettre en status queued.
         */
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

            $stmt->execute(); unset($stmt);
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

            $stmt->execute(); unset($stmt);
        }

        /**
         *  Si l'erreur est de type 1 (erreur lors des vérifications de l'opération), on affiche les erreurs avec echo, elles seront capturées par ob_get_clean()
         *  On ajoute également les données connues de la planification, le tableau récapitulatif n'ayant pas pu être généré par l'opération puisqu'on a rencontré une erreur avant qu'elle ne se lance.
         */
        if ($planError == 1) {
            echo "<span class='redtext'>${plan_msg_error}</span>";
            echo '<p><b>Détails de la planification :</b></p>';
            echo '<table>';
            echo "<tr><td><b>Action : </b></td><td>{$this->op->action}</td></tr>";
            if (!empty($this->op->group->name))   echo "<tr><td><b>Groupe : </b></td><td>{$this->op->group->name}</td></tr>";
            if (!empty($this->op->repo->name))    echo "<tr><td><b>Repo : </b></td><td>{$this->op->repo->name}</td></tr>";
            if (!empty($this->op->repo->dist))    echo "<tr><td><b>Dist : </b></td><td>{$this->op->repo->dist}</td></tr>";
            if (!empty($this->op->repo->section)) echo "<tr><td><b>Section : </b></td><td>{$this->op->repo->section}</td></tr>";
            echo '</table>';
        }

// Contenu du fichier de log de la planification //

        /**
         *  Cas où on traite un groupe de repo
         *  Dans ce cas, chaque repo mis à jour crée son propre fichier de log. On a récupéré le chemin de ces fichiers de log au cours de l'opération et on l'a placé dans un array $this->logList
         *  On parcourt donc cet array pour récupérer le contenu de chaque sous-fichier de log afin de créer un fichier de log global de la planification  
         */
        if (!empty($this->op->group->name)) {
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
        if (!empty($this->op->repo->name) AND empty($this->op->group->name)) {
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
        include_once("${WWW_DIR}/templates/planification_log.inc.php");
        $this->log->write($logContent);
        $this->log->close();

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
                    $plan_title   = "[OK] - Planification n°{$this->id} sur $WWW_HOSTNAME";
                    $plan_pre_msg = "Une planification vient de se terminer.";
                }
                if ($this->type == 'regular') {
                    $plan_title   = "[OK] - Planification récurrente n°{$this->id} sur $WWW_HOSTNAME";
                    $plan_pre_msg = "Une planification récurrente vient de se terminer.";   
                }
                $plan_msg = "La planification s'est terminée sans erreur.";

                /**
                 *  Template HTML du mail, inclu une variable $template contenant le corps du mail avec $plan_msg
                 */
                include("${WWW_DIR}/templates/plan_mail.inc.php");
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
                    $plan_title   = "[ERREUR] - Planification n°{$this->id} sur $WWW_HOSTNAME";
                    $plan_pre_msg = "Une planification s'est mal terminée.";
                }
                if ($this->type == 'regular') {
                    $plan_title   = "[ERREUR] - Planification récurrente n°{$this->id} sur $WWW_HOSTNAME";
                    $plan_pre_msg = "Une planification récurrente s'est mal terminée.";
                }
                $plan_msg = 'Cette planification a rencontré une erreur';

                /**
                 *  Template HTML du mail, inclu une variable $template contenant le corps du mail avec $plan_msg
                 */
                include("${WWW_DIR}/templates/plan_mail.inc.php");
                $this->sendMail($plan_title, $template);
            }
        }

        exit();
    }

/**
 *  Envoi d'un mail d'erreur ou de rappel de planification
 *  A partir d'une variable $template contenant le corps HTML du mail à envoyer
 */
public function sendMail($title, $template) {
    global $WWW_DIR;
    global $WWW_HOSTNAME;
    //global $EMAIL_DEST;
    
    /**
     *  On envoi un mail si une adresse de destination a été renseignée (non-vide et non null)
     */
    if (!empty($this->mailRecipient)) {
        /**
         *  Pour envoyer un mail HTML il faut inclure ces headers
         */
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=utf8';
        $headers[] = "From: noreply@${WWW_HOSTNAME}";
        $headers[] = "X-Sender: noreply@${WWW_HOSTNAME}";
        $headers[] = "Reply-To: noreply@${WWW_HOSTNAME}";
        //mail($EMAIL_DEST, $title, $template, implode("\r\n", $headers));
        mail($this->mailRecipient, $title, $template, implode("\r\n", $headers));
    }
}

/**
 *  VERIFICATIONS
 *  Code d'erreurs : CP "Check Planification"
 */
    private function checkAction() {
        if (empty($this->op->action)) throw new Exception("Erreur (CP01) : Aucune action n'est spécifiée dans cette planification");
    }

    private function checkAction_update_allowed() {
        global $ALLOW_AUTOUPDATE_REPOS;

        /**
         *  Si la mise à jour des repos n'est pas autorisée, on quitte
         */
        if ($ALLOW_AUTOUPDATE_REPOS != "yes") throw new Exception("Erreur (CP02) : La mise à jour des miroirs par planification n'est pas autorisée. Vous pouvez modifier ce paramètre depuis l'onglet Configuration");
    }

    private function checkAction_update_gpgCheck() {
        if (empty($this->op->repo->gpgCheck)) throw new Exception("Erreur (CP03) : Vérification des signatures GPG non spécifié dans cette planification");
    }

    private function checkAction_update_gpgResign() {
        if (empty($this->op->repo->gpgResign)) throw new Exception("Erreur (CP04) : Signature des paquets avec GPG non spécifié dans cette planification");
    }

    private function checkAction_env_allowed() {
        global $ALLOW_AUTOUPDATE_REPOS_ENV;

        /**
         *  Si le changement d'environnement n'est pas autorisé, on quitte
         */
        if ($ALLOW_AUTOUPDATE_REPOS_ENV != "yes") throw new Exception("Erreur (CP05) : Le changement d'environnement par planification n'est pas autorisé. Vous pouvez modifier ce paramètre depuis l'onglet Configuration.");
    }

    /**
     *  Vérification si on traite un repo seul ou un groupe
     */
    private function checkIfRepoOrGroup() {
        global $OS_FAMILY;

        if (empty($this->op->repo->name) AND empty($this->op->group->name)) throw new Exception("Erreur (CP06) : Aucun repo ou groupe spécifié");
    
        /**
         *  On va traiter soit un repo soit un groupe de repo, ça ne peut pas être les deux, donc on vérifie que planRepo et planGroup ne sont pas tous les deux renseignés en même temps :
         */
        if (!empty($this->op->repo->name) AND !empty($this->op->group->name)) {
            if ($OS_FAMILY == "Redhat") throw new Exception("Erreur (CP07) : Il n'est pas possible de traiter à la fois un repo et un groupe de repos");
            if ($OS_FAMILY == "Debian") throw new Exception("Erreur (CP07) : Il n'est pas possible de traiter à la fois une section et un groupe de sections");
        }
    }
  
    /**
     *  Vérification que le repo existe
     */
    private function checkIfRepoExists() {
        global $OS_FAMILY;
    
        if ($OS_FAMILY == "Redhat") {
            if ($this->op->repo->exists($this->op->repo->name) === false) throw new Exception("Erreur (CP08) : Le repo <b>{$this->op->repo->name}</b> n'existe pas");
        }
    
        if ($OS_FAMILY == "Debian") {       
            /**
             *  On vérifie qu'on a bien renseigné la distribution et la section
             */
            if (empty($this->op->repo->dist)) throw new Exception("Erreur (CP10) : Aucune distribution spécifiée");
            if (empty($this->op->repo->section)) throw new Exception("Erreur (CP11) : Aucune section spécifiée");
        
            /**
             *  Vérification que la section existe
             */
            if ($this->op->repo->section_exists($this->op->repo->name, $this->op->repo->dist, $this->op->repo->section) === false) throw new Exception("Erreur (CP12) : La section <b>{$this->op->repo->section}</b> du repo <b>{$this->op->repo->name}</b> (distribution <b>{$this->op->repo->dist}</b>) n'existe pas");
        }
    }  
  
    /**
     *  Vérification que le groupe existe
     */
    private function checkIfGroupExists() {
        $stmt = $this->db->prepare("SELECT * FROM groups WHERE Name=:name");
        $stmt->bindValue(':name', $this->op->group->name);
        $result = $stmt->execute();

        if ($this->db->isempty($result) === true) {
            throw new Exception("Erreur (CP14) : Le groupe <b>{$this->op->group->name}</b> n'existe pas");
        } 
    }
  
    /**
     *  Récupération de la liste des repo dans le groupe
     */
    private function getGroupRepoList() {
        global $OS_FAMILY;
        global $DEFAULT_ENV;

        /**
         *  On récupère tous les repos du groupe
         */
        $this->groupList = $this->op->group->listReposMembers_byEnv($this->op->group->name, $DEFAULT_ENV);
    
        if (empty($this->groupList)) {
            if ($OS_FAMILY == "Redhat") throw new Exception("Erreur (CP13) : Il n'y a aucun repo renseigné dans le groupe <b>{$this->op->group->name}</b>");
            if ($OS_FAMILY == "Debian") throw new Exception("Erreur (CP13) : Il n'y a aucune section renseignée dans le groupe <b>{$this->op->group->name}</b>");
        }
    
        /**
         *  Pour chaque repo/section renseigné(e), on vérifie qu'il/elle existe
         */
        $msg_error = '';
        foreach($this->groupList as $repo) {
            $repoId   = $repo['Id'];
            $repoName = $repo['Name'];
            if ($OS_FAMILY == "Debian") { // si Debian on récupère aussi la distrib et la section
                $repoDist = $repo['Dist'];
                $repoSection = $repo['Section'];
            }

            if ($OS_FAMILY == "Redhat") {
                if ($this->op->repo->exists($repoName) === false) $msg_error .= "Erreur (CP15) : Le repo <b>$repoName</b> dans le groupe <b>{$this->op->group->name}</b> n'existe pas/plus.".PHP_EOL;
            }
            
            if ($OS_FAMILY == "Debian") {
                if ($this->op->repo->section_exists($repoName, $repoDist, $repoSection) === false) $msg_error .= "Erreur (CP16) : La section <b>$repoSection</b> du repo <b>$repoName</b> (distribution <b>$repoDist</b>) dans le groupe <b>{$this->op->group->name}</b> n'existe pas/plus.".PHP_EOL;
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
    public function listDone() {
        $query = $this->db->query("SELECT * FROM planifications WHERE Status = 'done' OR Status = 'error' OR Status = 'stopped' ORDER BY Date DESC, Time DESC");
        
        $plans = array();

        while ($datas = $query->fetchArray(SQLITE3_ASSOC)) $plans[] = $datas;

        return $plans;
    }

    /**
     *  Liste la dernière planification exécutée
     */
    public function listLast() {
        $result = $this->db->queryArray("SELECT Date, Time FROM planifications WHERE Type = 'plan' AND (Status = 'done' OR Status = 'error') ORDER BY Date DESC, Time DESC LIMIT 1");
        return $result;
    }

    /**
     *  Liste la prochaine planification qui sera exécutée
     */
    public function listNext() {
        $result = $this->db->queryArray("SELECT Date, Time FROM planifications WHERE Type = 'plan' AND Status = 'queued' ORDER BY Date ASC, Time ASC LIMIT 1");
        return $result;
    }

    /**
    *   Récupère toutes les infos d'une planification
    *   Un objet Operation doit avoir été instancié pour récupérer les infos concernant le repo concerné par cette planification
    */
    private function getInfo() {
        if (empty($this->id)) throw new Exception("Erreur (EP02) Impossible de récupérer les informations de la planification car son ID est vide");

        $stmt = $this->db->prepare("SELECT * FROM planifications WHERE Id = :id");
        $stmt->bindValue(':id', $this->id);
        $result = $stmt->execute();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) $datas = $row;

        /**
         *  Type de planification
         */
        $this->setType($datas['Type']);

        /**
         *  Action
         */
        $this->op->action = $datas['Action'];

        /**
         *  Id du repo ou du groupe
         */
        if (!empty($datas['Id_repo']))  $this->op->repo->id  = $datas['Id_repo'];
        if (!empty($datas['Id_group'])) $this->op->group->id = $datas['Id_group'];

        /**
         *  On récupère les infos concernant le groupe à traiter (son nom)
         */
        if (!empty($this->op->group->id)) $this->op->group->db_getName();

        /**
         *  On récupère les infos concernant le repo à traiter (son nom, sa distribution...)
         */
        if (!empty($this->op->repo->id)) {
            $this->op->repo->db_getAllById();
        }

        /**
         *  Les paramètres GPG Check et GPG Resign sont conservées de côté et seront pris en compte au début de l'exécution de exec_update()
         */
        if (!empty($datas['Gpgcheck'])) {
            $this->op->gpgCheck       = $datas['Gpgcheck'];
            $this->op->repo->gpgCheck = $datas['Gpgcheck'];
        }
        if (!empty($datas['Gpgresign'])) {
            $this->op->gpgResign       = $datas['Gpgresign'];
            $this->op->repo->gpgResign = $datas['Gpgcheck'];
            $this->op->repo->signed    = $datas['Gpgcheck'];
        }

        /**
         *  Rappels de planification
         */
        $this->reminder = $datas['Reminder'];

        /**
         *  Notifications par mail si erreur ou si terminé
         */
        $this->setNotification('on-error', $datas['Notification_error']);
        $this->setNotification('on-success', $datas['Notification_success']);

        /**
         *  Adresse mail de destination
         */
        if (!empty($datas['Mail_recipient'])) {
            $this->mailRecipient = $datas['Mail_recipient'];
        }
    }
}
?>