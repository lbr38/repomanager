<?php
include_once(ROOT."/models/includes/new.php");
include_once(ROOT."/models/includes/update.php");
include_once(ROOT."/models/includes/op_printDetails.php");
include_once(ROOT."/models/includes/op_getPackages.php");
include_once(ROOT."/models/includes/op_signPackages.php");
include_once(ROOT."/models/includes/op_createRepo.php");
include_once(ROOT."/models/includes/op_archive.php");
include_once(ROOT."/models/includes/op_finalize.php");
include_once(ROOT."/models/includes/newLocalRepo.php");
include_once(ROOT."/models/includes/env.php");
include_once(ROOT."/models/includes/delete.php");
include_once(ROOT."/models/includes/duplicate.php");
include_once(ROOT."/models/includes/restore.php");
include_once(ROOT."/models/includes/cleanArchives.php");
include_once(ROOT."/models/includes/reconstruct.php");

class Operation extends Model {
    private $action;
    private $status;
    private $id;    // Id de l'opération en BDD
    private $type;
    private $date;
    private $time;
    private $id_plan; // Si une opération est lancée par une planification alors on peut stocker l'ID de cette planification dans cette variable
    private $targetGpgCheck;
    private $targetGpgResign;
    private $timeStart = "";
    private $timeEnd = "";

    public $repo;    // pour instancier un objet Repo
    public $log;     // pour instancier un objet Log
    
    /**
     *  Import des traits nécessaires pour les opérations sur les repos/sections
     */
    use newMirror, newLocalRepo, update, env, duplicate, delete, restore, cleanArchives;
    use op_printDetails, op_getPackages, op_signPackages, op_createRepo, op_archive, op_finalize, reconstruct;

    public function __construct() {
        /**
         *  Ouverture d'une connexion à la base de données
         */
        $this->getConnection('main');

        /**
         *  Instanciation d'un Repo
         */
        $this->repo = new Repo();
    }

    public function setId_plan(string $id_plan)
    {
        $this->id_plan = $id_plan;
    }

    public function setAction(string $action)
    {
        $this->action = Common::validateData($action);
    }

    public function setType(string $type)
    {
        if ($type !== 'manual' and $type !== 'plan') {
            throw new Exception("Le type d'opération est invalide");
        }

        $this->type = Common::validateData($type);
    }

    public function setStatus(string $status)
    {
        $this->status = $status;
    }

    public function setTargetGpgCheck(string $gpgCheck)
    {
        $this->targetGpgCheck = $gpgCheck;
    }

    public function setTargetGpgResign(string $gpgResign)
    {
        $this->targetGpgResign = $gpgResign;
    }

    public function getAction()
    {
        return $this->action;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getTargetGpgCheck()
    {
        return $this->targetGpgCheck;
    }

    public function getTargetGpgResign()
    {
        return $this->targetGpgResign;
    }

    public function getId_plan()
    {
        return $this->id_plan;
    }

    /**
     *  Lister les opérations en cours d'exécution en fonction du type souhaité (opérations manuelles ou planifiées)
     */
    public function listRunning(string $type = '')
    {
        /**
         *  Si le type est laissé vide alors on affiche tous les types d'opérations.
         *  Sinon on affiche les opérations selon le type souhaité (manuelles ou planifiées)
         */
        if (!empty($type) and $type != 'manual' and $type != 'plan') {
            throw new Error("Type d'opération non reconnu");
        }

        $operations = array();

        /**
         *  Cas où on souhaite tous les types
         */
        try {
            if (empty($type)) {
                $stmt = $this->db->prepare("SELECT * FROM operations WHERE Status = 'running' ORDER BY Date DESC, Time DESC");

            /**
             *  Cas où souhaite filtrer par un type en particulier
             */
            } else {
                $stmt = $this->db->prepare("SELECT * FROM operations WHERE Status = 'running' and Type=:type ORDER BY Date DESC, Time DESC");
                $stmt->bindValue(':type', $type);
            }
            $result = $stmt->execute();
        } catch (Exception $e) {
            Common::dbError($e);
        }

        while ($datas = $result->fetchArray()) $operations[] = $datas;

        return $operations;
    }

    /**
     *  Lister les opérations terminées (avec ou sans erreurs)
     *  Il est possible de filtrer le type d'opération ('manual' ou 'plan')
     *  Il est possible de filtrer si le type de planification qui a lancé cette opération ('plan' ou 'regular' (planification unique ou planification récurrente))
     */
    public function listDone(string $type = '', string $planType = '') {
        /**
         *  Si le type est laissé vide alors on affiche tous les types d'opérations. Sinon on affiche les opérations selon le type souhaité (manuelles ou planifiées)
         */
        if (!empty($type) and $type != 'manual' and $type != 'plan') {
            throw new Error("Type d'opération non reconnu");
        }

        try {
            /**
             *  Cas où on souhaite tous les types
             */
            if (empty($type) and empty($planType)) {
                $stmt = $this->db->prepare("SELECT * FROM operations WHERE Status = 'error' or Status = 'done' or Status = 'stopped' ORDER BY Date DESC, Time DESC");
            }

            /**
             *  Cas où on filtre par type d'opération seulement
             */
            if (!empty($type) and empty($planType)) {
                $stmt = $this->db->prepare("SELECT * FROM operations
                WHERE Type = :type and (Status = 'error' or Status = 'done' or Status = 'stopped')
                ORDER BY Date DESC, Time DESC");
                $stmt->bindValue(':type', $type);
            }

            /**
             *  Cas où on filtre par type de planification seulement
             */
            if (empty($type) and !empty($planType)) {
                $stmt = $this->db->prepare("SELECT * FROM operations 
                INNER JOIN planifications
                ON operations.Id_plan = planifications.Id
                WHERE planifications.Type = :plantype and (operations.Status = 'error' or operations.Status = 'done' or operations.Status = 'stopped')
                ORDER BY operations.Date DESC, operations.Time DESC");
                $stmt->bindValue(':plantype', $planType);
            }

            /**
             *  Cas où on filtre par type d'opération ET par type de planification
             */
            if (!empty($type) and !empty($planType)) {
                $stmt = $this->db->prepare("SELECT
                operations.Id,
                operations.Date,
                operations.Time,
                operations.Action,
                operations.Type,
                operations.Id_repo_source,
                operations.Id_repo_target,
                operations.Id_group,
                operations.Id_plan,
                operations.GpgCheck,
                operations.GpgResign,
                operations.Pid,
                operations.Logfile,
                operations.Status
                FROM operations 
                INNER JOIN planifications
                ON operations.Id_plan = planifications.Id
                WHERE operations.Type = :type
                and planifications.Type = :plantype
                and (operations.Status = 'error' or operations.Status = 'done' or operations.Status = 'stopped')
                ORDER BY operations.Date DESC, operations.Time DESC");
                $stmt->bindValue(':type', $type);
                $stmt->bindValue(':plantype', $planType);
            }
            $result = $stmt->execute();
        } catch (Exception $e) {
            Common::dbError($e);
        }

        $datas = array();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) $datas[] = $row;

        return $datas;
    }

    public function kill($pid) {
        if (file_exists(PID_DIR."/${pid}.pid")) {
            /**
             * 	Récupération du nom de fichier de log car on va avoir besoin d'indiquer dedans que l'opération a été stoppée
             */
            $logFile = exec("grep '^LOG=' ".PID_DIR."/${pid}.pid | sed 's/LOG=//g' | sed 's/\"//g'");
    
            /**
             * 	Récupération des subpid car il va falloir les tuer aussi
             */
            $subpids = shell_exec("grep -h '^SUBPID=' ".PID_DIR."/${pid}.pid | sed 's/SUBPID=//g' | sed 's/\"//g'");
            
            /**
             * 	Kill des subpids si il y en a
             */
            if (!empty($subpids)) {
                $subpids = explode("\n", trim($subpids));
                foreach ($subpids as $subpid) {
                    exec("kill -9 $subpid");
                }
            }
    
            /**
             * 	Suppression du fichier pid principal
             */
            unlink(PID_DIR."/${pid}.pid");
        }

        /**
         *  Si cette opération a été lancée par un planification, il faudra mettre à jour la planification en BDD
         *  On récupère d'abord l'ID de la planification
         */
        try {
            $stmt = $this->db->prepare("SELECT Id_plan FROM operations WHERE Pid=:pid and Status = 'running'");
            $stmt->bindValue(':pid', $pid);
            $result = $stmt->execute();
        } catch (Exception $e) {
            Common::dbError($e);
        }

        while ($datas = $result->fetchArray()) { $planId = $datas['Id_plan']; }

        /**
         *  Mise à jour de l'opération en BDD, on la passe en status = stopped
         */
        try {
            $stmt = $this->db->prepare("UPDATE operations SET Status = 'stopped' WHERE Pid=:pid and Status = 'running'");
            $stmt->bindValue(':pid', $pid);
            $stmt->execute();
        } catch (Exception $e) {
            Common::dbError($e);
        }

        /**
         *  Mise à jour de la planification en BDD
         */
        if (!empty($planId)) {
            try {
                $stmt = $this->db->prepare("UPDATE planifications SET Status = 'stopped' WHERE Id=:id and Status = 'running'");
                $stmt->bindValue(':id', $planId);
                $stmt->execute();
            } catch (Exception $e) {
                Common::dbError($e);
            }
        }
        unset($stmt, $planId, $datas, $result);

        Common::printAlert("L'opération a été arrêtée", 'success');

        Common::clearCache();
    }

    /**
     *  Vérifie que l'ID passé correspond bien à un repo en BDD
     */
    private function chk_param_id(string $id, string $status) {
        if (empty($id)) {
            throw new Exception("L'Id du repo ne peut pas être vide");
        }

        if (!is_numeric($id)) {
            throw new Exception("L'Id du repo doit être un nombre");
        }

        /**
         *  On a besoin de connaitre l'état du repo pour l'étape suivante
         */
        if (empty($status)) {
            throw new Exception("L'état du repo n'est pas renseigné");
        }
        if ($status !== "active" and $status !== "archived") {
            throw new Exception("L'état du repo est invalide");
        }

        /**
         *  On vérifie que l'ID spécifié existe en BDD
         */
        $myrepo = new Repo();
        if (!$myrepo->existsId($id, $status)) {
            throw new Exception("Le repo spécifié n'existe pas");
        }

        unset($myrepo);
    }

    private function chk_param_source(string $source)
    {
        if (empty($source)) {
            throw new Exception ("La source ne peut pas être vide");
        }

        if (!Common::is_alphanumdash($source, array('.'))) {
            throw new Exception ('La source du repo contient des caractères invalides');
        }
    }

    private function chk_param_type(string $type)
    {
        if (empty($type)) {
            throw new Exception("Le type du repo ne peut pas être vide");
        }

        if ($type !== "mirror" and $type !== "local") {
            throw new Exception('Le type du repo est invalide');
        }
    }

    private function chk_param_alias(string $alias)
    {
        if (!empty($alias)) {
            if (!Common::is_alphanum($alias, array('-'))) {
                throw new Exception('Le nom du repo ne peut pas contenir de caractères spéciaux hormis le tiret -');
            }
        }
    }

    private function chk_param_name(string $name)
    {
        if (empty($name)) {
            throw new Exception('Le nom du repo ne peut pas être vide');
        }

        if (!Common::is_alphanum($name, array('-'))) {
            throw new Exception('Le nom du repo ne peut pas contenir de caractères spéciaux hormis le tiret -');
        }
    }

    private function chk_param_targetName(string $targetName)
    {
        if (empty($targetName)) {
            throw new Exception('Vous devez spécifier un nouveau nom de repo');
        }
        if (!Common::is_alphanum($targetName, array('-'))) {
            throw new Exception('Le nouveau nom du repo ne peut pas contenir de caractères spéciaux hormis le tiret -');
        }
    }

    private function chk_param_dist(string $dist)
    {
        if (empty($dist)) {
            throw new Exception('Le nom de la distribution ne peut pas être vide');
        }

        if (!Common::is_alphanum($dist, array('-', '/'))) {
            throw new Exception('Le nom de la distribution ne peut pas contenir de caractères spéciaux hormis le tiret -');
        }
    }

    private function chk_param_section(string $section)
    {
        if (empty($section)) {
            throw new Exception('Le nom de la section ne peut pas être vide');
        }

        if (!Common::is_alphanum($section, array('-'))) {
            throw new Exception('Le nom de la section ne peut pas contenir de caractères spéciaux hormis le tiret -');
        }
    }

    private function chk_param_gpgCheck(string $gpgCheck)
    {
        if ($gpgCheck !== "yes" and $gpgCheck !== "no") {
            throw new Exception('Le paramètre de vérification des signatures GPG est invalide');
        }
    }

    private function chk_param_gpgResign(string $gpgResign)
    {
        if ($gpgResign !== "yes" and $gpgResign !== "no") {
            throw new Exception('Le paramètre de signature avec GPG est invalide');
        }
    }

    private function chk_param_group(string $group)
    {
        if (!empty($group)) {
            if (!Common::is_alphanumdash($group, array('-'))) {
                throw new Exception('Le groupe comporte des caractères invalides');
            }
        }
    }

    private function chk_param_description(string $description)
    {
        if (!empty($description)) {
            /**
             *  Vérification du contenu de la description
             *  On accepte certains caractères spéciaux
             */
            if (!Common::is_alphanumdash($description, array('.', '(', ')', '@', 'é', 'è', 'à', 'ç', 'ù', 'ê', 'ô', '+', '\'', ' '))) {
                throw new Exception('La description comporte des caractères invalides');
            }
        }
    }

    private function chk_param_env(string $env)
    {
        if (empty($env)) {
            throw new Exception("Le nom de l'environnement ne peut pas être vide");
        }
        if (!Common::is_alphanum($env, array('-'))) {
            throw new Exception("L'environnement comporte des caractères invalides");
        }
    }

    private function chk_param_date(string $date)
    {
        if (empty($date)) {
            throw new Exception("La date ne peut pas être vide");
        }
        if (preg_match('#^(\d\d\d\d)-(\d\d)-(\d\d)$#', $date) == false) {
            throw new Exception("Le format de la date est invalide");
        }
    }

    /**
     *  NOUVELLE OPERATION
     *  Ajout d'une nouvelle entrée en BDD
     */
    public function startOperation(array $variables = []) {
        extract($variables);

        $this->date = date("Y-m-d");
        $this->time = date("H:i:s");
        $this->timeStart = microtime(true); // timeStart sera destiné à calculer le temps écoulé pour l'opération.
        $this->status = 'running';
        $this->log = new Log('repomanager');

        try {
            $stmt = $this->db->prepare("INSERT INTO operations (date, time, action, type, pid, logfile, status) VALUES (:date, :time, :action, :type, :pid, :logfile, :status)");
            $stmt->bindValue(':date', $this->date);
            $stmt->bindValue(':time', $this->time);
            $stmt->bindValue(':action', $this->action);
            $stmt->bindValue(':type', $this->type);
            $stmt->bindValue(':pid', $this->log->pid);
            $stmt->bindValue(':logfile', $this->log->name);
            $stmt->bindValue(':status', $this->status);
            $stmt->execute();
        } catch (Exception $e) {
            Common::dbError($e);
        }

        unset($stmt);

        // Récupération de l'ID de l'opération précédemment créée en BDD car on en aura besoin pour clore l'opération
        $this->id = $this->db->lastInsertRowID();

        /**
         *  Si un ID de planification a été renseigné en appelant startOperation alors on l'ajoute directement en BDD
         */
        if (!empty($id_plan)) {
            $this->db_update_idplan($id_plan);
            unset($id_plan);
        }

        /**
         *  Si un ID de repo source a été renseigné en appelant startOperation alors on l'ajoute directement en BDD
         */
        if (!empty($id_repo_source)) {
            $this->db_update_idrepo_source($id_repo_source);
            unset($id_repo_source);
        }

        /**
         *  Si un ID de repo cible a été renseigné en appelant startOperation alors on l'ajoute directement en BDD
         */
        if (!empty($id_repo_target)) {
            $this->db_update_idrepo_target($id_repo_target);
            unset($id_repo_target);
        }

        /**
         *  Si un ID de groupe a été renseigné en appelant startOperation alors on l'ajoute directement en BDD
         */
        if (!empty($id_group)) {
            $this->db_update_idgroup($id_group);
            unset($id_group);
        }

        /**
         *  Si gpgCheck a été renseigné en appelant startOperation alors on l'ajoute directement en BDD
         */
        if (!empty($gpgCheck)) {
            $this->db_update_gpgCheck($gpgCheck);
            unset($gpgCheck);
        }

        /**
         *  Si gpgResign a été renseigné en appelant startOperation alors on l'ajoute directement en BDD
         */
        if (!empty($gpgResign)) {
            $this->db_update_gpgResign($gpgResign);
            unset($gpgResign);
        }
    }

    public function db_update_idplan($id_plan) {
        try {
            $stmt = $this->db->prepare("UPDATE operations SET Id_plan=:id_plan WHERE Id=:id");
            $stmt->bindValue(':id_plan', $id_plan);
            $stmt->bindValue(':id', $this->id);
            $stmt->execute();
        } catch (Exception $e) {
            Common::dbError($e);
        }
        unset($stmt);
    }

    public function db_update_idrepo_source($id_repo_source) {
        try {
            $stmt = $this->db->prepare("UPDATE operations SET Id_repo_source=:id_repo_source WHERE Id=:id");
            $stmt->bindValue(':id_repo_source', $id_repo_source);
            $stmt->bindValue(':id', $this->id);
            $stmt->execute();
        } catch (Exception $e) {
            Common::dbError($e);
        }
        unset($stmt);
    }

    public function db_update_idrepo_target($id_repo_target) {
        try {
            $stmt = $this->db->prepare("UPDATE operations SET Id_repo_target = :id_repo_target WHERE Id = :id");
            $stmt->bindValue(':id_repo_target', $id_repo_target);
            $stmt->bindValue(':id', $this->id);
            $stmt->execute();
        } catch (Exception $e) {
            Common::dbError($e);
        }
    }

    public function db_update_idgroup($id_group) {
        try {
            $stmt = $this->db->prepare("UPDATE operations SET Id_group=:id_group WHERE Id=:id");
            $stmt->bindValue(':id_group', $id_group);
            $stmt->bindValue(':id', $this->id);
            $stmt->execute();
        } catch (Exception $e) {
            Common::dbError($e);
        }
        unset($stmt);
    }

    public function db_update_gpgCheck($gpgCheck) {
        try {
            $stmt = $this->db->prepare("UPDATE operations SET GpgCheck=:gpgCheck WHERE Id=:id");
            $stmt->bindValue(':gpgCheck', $gpgCheck);
            $stmt->bindValue(':id', $this->id);
            $stmt->execute();
        } catch (Exception $e) {
            Common::dbError($e);
        }
        unset($stmt);
    }

    public function db_update_gpgResign($gpgResign) {
        try {
            $stmt = $this->db->prepare("UPDATE operations SET GpgResign=:gpgResign WHERE Id=:id");
            $stmt->bindValue(':gpgResign', $gpgResign);
            $stmt->bindValue(':id', $this->id);
            $stmt->execute();
        } catch (Exception $e) {
            Common::dbError($e);
        }
        unset($stmt);
    }

    /**
     *  CLOTURE D'UNE OPERATION
     *  Modifie le status en BDD
     */
    public function closeOperation()
    {
        $this->timeEnd = microtime(true);
        $this->duration = $this->timeEnd - $this->timeStart; // $this->duration = nombre de secondes totales pour l'exécution de l'opération

        try {
            $stmt = $this->db->prepare("UPDATE operations SET Status=:status, Duration=:duration WHERE Id=:id");
            $stmt->bindValue(':status', $this->status);
            $stmt->bindValue(':duration', $this->duration);
            $stmt->bindValue(':id', $this->id);
            $stmt->execute();
        } catch (Exception $e) {
            Common::dbError($e);
        }

        /**
         *  Cloture du fichier de log ouvert par startOperation()
         */
        $this->log->close();

        /**
         *  Nettoyage du cache de repos-list
         */
        Common::clearCache();
    }

    /**
     *  Renvoyer le formulaire d'opération à l'utilisateur en fonction de sa sélection
     */
    public function getForm(string $action, array $repos_array)
    {
        $action = Common::validateData($action);

        if ($action == 'update')      $title = '<h3>MISE A JOUR</h3>';
        if ($action == 'env')         $title = '<h3>NOUVEL ENVIRONNEMENT</h3>';
        if ($action == 'duplicate')   $title = '<h3>DUPLIQUER</h3>';
        if ($action == 'delete')      $title = '<h3>SUPPRIMER</h3>';
        if ($action == 'restore')     $title = '<h3>RESTAURER</h3>';
        if ($action == 'reconstruct') $title = '<h3>RECONSTRUIRE LE REPO</h3>';

        $content = $title.'<form class="operation-form-container" autocomplete="off">';

        foreach ($repos_array as $repo) {
            $id = Common::validateData($repo['repoId']);
            $status = Common::validateData($repo['repoStatus']);

            /**
             *  Vérification de l'id spécifié
             */
            if (!is_numeric($id)) {
                throw new Exception("L'Id du repo est invalide");
            }

            /**
             *  Vérification du status spécifié
             */
            if ($status != 'active' and $status != 'archived') {
                throw new Exception('Le status du repo est invalide');
            }

            /**
             *  On vérifie que l'id de repo existe en base de données
             */
            $myrepo = new Repo();
            $myrepo->setId($id);
            $myrepo->setStatus($status);
            if (!$myrepo->existsId($id, $status)) {
                throw new Exception("L'Id de repo spécifié n'existe pas");
            }

            /**
             *  On récupère toutes les données du repo à partir de son Id
             */
            $myrepo->db_getAllById($status);
            
            /**
             *  Construction du formulaire à partir d'un template
             */
            ob_start();

            echo '<div class="operation-form" repo-id="'.$id.'" repo-status="'.$status.'" action="'.$action.'">';
                /**
                 *  Si l'action est 'update'
                 */
                if ($action == 'update') {
                    include(ROOT.'/templates/forms/op-form-update.inc.php');
                }
                /**
                 *  Si l'action est duplicate
                 */
                if ($action == 'duplicate') {
                    include(ROOT.'/templates/forms/op-form-duplicate.inc.php');
                }
                /**
                 *  Si l'action est 'env'
                 */
                if ($action == 'env') {
                    include(ROOT.'/templates/forms/op-form-env.inc.php');
                }
                /**
                 *  Si l'action est 'delete'
                 */
                if ($action == 'delete') {
                    include(ROOT.'/templates/forms/op-form-delete.inc.php');
                }
                /**
                 *  Si l'action est 'restore'
                 */
                if ($action == 'restore') {
                    include(ROOT.'/templates/forms/op-form-restore.inc.php');
                }
                /**
                 *  Si l'action est 'reconstruct'
                 */
                if ($action == 'reconstruct') {
                    include(ROOT.'/templates/forms/op-form-reconstruct.inc.php');
                }
            echo '</div>';

            $content .= ob_get_clean();
        }

        $content .= '<br><button class="btn-large-red">Confirmer et exécuter<img src="ressources/icons/rocket.png" class="icon" /></button></form>';

        return $content;
    }

    /**
     *  Valider un formulaire d'opération complété par l'utilisateur
     */
    public function validateForm(array $operations_params)
    {
        /**
         *  L'array contient tous les paramètres de l'opération sur le(s) repo(s) à vérifier : l'action, l'id du repo et les paramètres nécéssaires que l'utilisateur a complété par le formulaire
         */
        foreach ($operations_params as $operation_params) {
            /**
             *  Récupération de l'action à exécuter sur le repo
             */
            $action = Common::validateData($operation_params['action']);
            /**
             *  Récupération de l'id de repo, sauf quand l'action est 'new'
             */
            if ($action !== 'new') {
                $repoId = Common::validateData($operation_params['repoId']);
            }
            /**
             *  On vérifie qu'une action est spécifiée
             */
            if (empty($action)) {
                throw new Exception("L'action spécifiée est vide");
            }
            /**
             *  On vérifie qu'un id de repo a été spécifié, sauf dans le cas d'une action 'new'
             */
            if ($action !== 'new' and empty($repoId)) {
                throw new Exception("L'Id de repo spécifié est vide");
            }
            /**
             *  On vérifie que l'action spécifiée est valide
             */
            if ($action !== 'new' AND
                $action !== 'update' AND
                $action !== 'duplicate' AND
                $action !== 'reconstruct' AND
                $action !== 'delete' AND
                $action !== 'env' AND
                $action != 'restore'
            ) {
                throw new Exception("L'action spécifiée est invalide");
            }

            /**
             *  Vérification des paramètres saisis par l'utilisateur dans le formaulaire
             *  Si un paramètre est invalide, il sera catché et une erreur sera affichée à l'écran de l'utilisateur
             */

            /**
             *  Vérification de l'id du repo, sauf lorsque l'action est 'new'
             */
            if ($action !== 'new') {
                $this->chk_param_id($repoId, $operation_params['repoStatus']);
            }

            /**
             *  Récupération de toutes les informations du repo à partir de son Id, sauf quand l'action est 'new'
             *  Ces informations seront surtout utiles pour donner plus de précisions à la fonction History::set()
             */
            if ($action !== 'new') {
                $myrepo = new Repo();
                $myrepo->setId($repoId);
                $myrepo->db_getAllById($operation_params['repoStatus']);
            }

            /**
             *  Si l'action est 'new'
             */
            if ($action == 'new') {
                $this->chk_param_type($operation_params['type']);
                if (OS_FAMILY == 'Debian') {
                    $this->chk_param_dist($operation_params['dist']);
                    $this->chk_param_section($operation_params['section']);
                }
                $this->chk_param_description($operation_params['targetDescription']);
                if (!empty($operation_params['targetGroup'])) {
                    $this->chk_param_group($operation_params['targetGroup']);
                }
                /**
                 *  Si le type de repo sélectionné est 'local' alors on vérifie qu'un nom a été fourni (peut rester vide dans le cas d'un miroir)
                 */
                if ($operation_params['type'] == "local") {
                    $this->chk_param_name($operation_params['alias']);
                }
                /**
                 *  Si le type de repo sélectionné est 'mirror' alors on vérifie des paramètres supplémentaires
                 */
                if ($operation_params['type'] == "mirror") {
                    $this->chk_param_source($operation_params['source']);
                    $this->chk_param_gpgCheck($operation_params['targetGpgCheck']);
                    $this->chk_param_gpgResign($operation_params['targetGpgResign']);
                }
                /**
                 *  On vérifie qu'un/une repo/section du même nom n'existe pas déjà
                 */
                if (OS_FAMILY == "Redhat" and $this->repo->exists($operation_params['alias']) === true) {
                    throw new Exception('Un repo du même nom existe déjà');
                } 
                if (OS_FAMILY == "Debian" and $this->repo->section_exists($operation_params['alias'], $operation_params['dist'], $operation_params['section']) === true) {
                    throw new Exception('Une section de repo du même nom existe déjà');
                }
                /**
                 *  On vérifie que le repo source existe dans /etc/yum.repos.d/repomanager/ (uniquement dans le cas d'un miroir)
                 */
                if ($operation_params['type'] == 'mirror') {
                    /**
                     *  Sur Redhat on vérifie que le nom de la source spécifiée apparait bien dans un des fichiers de repo source
                     */
                    if (OS_FAMILY == "Redhat") {   
                        $checkifRepoRealnameExist = exec("grep '^\\[".$operation_params['source']."\\]' ".REPOMANAGER_YUM_DIR."/*.repo");
                        if (empty($checkifRepoRealnameExist)) throw new Exception("Il n'existe aucun repo source nommé ".$operation_params['source']);
                    }
                    /**
                     *  Sur Debian on vérifie en base de données que la source spécifiée existe bien
                     */
                    if (OS_FAMILY == 'Debian') {
                        $mysource = new Source();
                        if ($mysource->exists($operation_params['source']) === false) {
                            throw new Exception("Il n'existe aucun repo source nommé ".$operation_params['source']);
                        }
                    }
                }

                if (OS_FAMILY == 'Redhat') History::set($_SESSION['username'], 'Lancement d\'une opération : création d\'un nouveau repo '.$operation_params['alias'].' ('.$operation_params['type'].')', 'success');
                if (OS_FAMILY == 'Debian') History::set($_SESSION['username'], 'Lancement d\'une opération : création d\'une nouvelle section de repo '.$operation_params['alias'].' - '.$operation_params['dist'].' - '.$operation_params['section'].' ('.$operation_params['type'].')', 'success');
            }

            /**
             *  Si l'action est 'update'
             */
            if ($action == 'update') {
                $this->chk_param_gpgCheck($operation_params['targetGpgCheck']);
                $this->chk_param_gpgResign($operation_params['targetGpgResign']);

                if (OS_FAMILY == 'Redhat') History::set($_SESSION['username'], 'Lancement d\'une opération : mise à jour du repo <span class="label-white">'.$myrepo->getName().'</span> ('.$myrepo->getType().')', 'success');
                if (OS_FAMILY == 'Debian') History::set($_SESSION['username'], 'Lancement d\'une opération : mise à jour de la section de repo <span class="label-white">'.$myrepo->getName().' ❯ '.$myrepo->getDist().' ❯ '.$myrepo->getSection().'</span> ('.$myrepo->getType().')', 'success');
            }

            /**
             *  Si l'action est 'duplicate'
             */
            if ($action == 'duplicate') {
                $this->chk_param_targetName($operation_params['targetName']);
                $this->chk_param_description($operation_params['targetDescription']);
                if (!empty($operation_params['targetGroup'])) {
                    $this->chk_param_group($operation_params['targetGroup']);
                }
                /**
                 *  On vérifie qu'un repo du même nom n'existe pas déjà
                 */
                if ($this->repo->exists($operation_params['targetName']) === true) {
                    throw new Exception('Un repo <b>'.$operation_params['targetName'].' existe déjà');
                }

                if (OS_FAMILY == 'Redhat') History::set($_SESSION['username'], 'Lancement d\'une opération : duplication d\'un repo <span class="label-white">'.$myrepo->getName().'</span>'.Common::envtag($myrepo->getEnv()).' ➡ <span class="label-white">'.$operation_params['targetName'].'</span>', 'success');
                if (OS_FAMILY == 'Debian') History::set($_SESSION['username'], 'Lancement d\'une opération : duplication d\'une section de repo <span class="label-white">'.$myrepo->getName().' ❯ '.$myrepo->getDist().' ❯ '.$myrepo->getSection().'</span>'.Common::envtag($myrepo->getEnv()).' ➡ <span class="label-white">'.$operation_params['targetName'].' ❯ '.$myrepo->getDist().' ❯ '.$myrepo->getSection().'</span>', 'success');
            }

            /**
             *  Si l'action est 'delete'
             */
            if ($action == 'delete') {
                /**
                 *  On vérifie que le repo mentionné existe
                 */
                // if ($this->repo->existsId($operation_params['repoId'], $operation_params['repoStatus']) === false) {
                //     throw new Exception("Il n'existe aucun Id de repo ".$operation_params['repoId']);
                // }

                if ($operation_params['repoStatus'] == 'active') {
                    if (OS_FAMILY == 'Redhat') History::set($_SESSION['username'], 'Lancement d\'une opération : suppression du repo <span class="label-white">'.$myrepo->getName().'</span>⟶'.Common::envtag($myrepo->getEnv()).'⟶<span class="label-black">'.$myrepo->getDateFormatted().'</span>', 'success');
                    if (OS_FAMILY == 'Debian') History::set($_SESSION['username'], 'Lancement d\'une opération : suppression de la section de repo <span class="label-white">'.$myrepo->getName().' ❯ '.$myrepo->getDist().' ❯ '.$myrepo->getSection().'</span>⟶'.Common::envtag($myrepo->getEnv()).'⟶<span class="label-black">'.$myrepo->getDateFormatted().'</span>', 'success');
                }
                if ($operation_params['repoStatus'] == 'archived') {
                    if (OS_FAMILY == 'Redhat') History::set($_SESSION['username'], 'Lancement d\'une opération : suppression du repo archivé <span class="label-white">'.$myrepo->getName().'</span>⟶<span class="label-black">'.$myrepo->getDateFormatted().'</span>', 'success');
                    if (OS_FAMILY == 'Debian') History::set($_SESSION['username'], 'Lancement d\'une opération : suppression de la section de repo archivée <span class="label-white">'.$myrepo->getName().' ❯ '.$myrepo->getDist().' ❯ '.$myrepo->getSection().'</span>⟶<span class="label-black">'.$myrepo->getDateFormatted().'</span>', 'success');
                }
            }

            /**
             *  Si l'action est 'env'
             */
            if ($action == 'env') {
                $this->chk_param_env($operation_params['targetEnv']);
                $this->chk_param_description($operation_params['targetDescription']);

                if (OS_FAMILY == 'Redhat') History::set($_SESSION['username'], 'Lancement d\'une opération : nouvel environnement '.Common::envtag($operation_params['targetEnv']).'⟶'.Common::envtag($myrepo->getEnv()).'⟶<span class="label-black">'.$myrepo->getDateFormatted().'</span> pour le repo <span class="label-white">'.$myrepo->getName().'</span>', 'success');
                if (OS_FAMILY == 'Debian') History::set($_SESSION['username'], 'Lancement d\'une opération : nouvel environnement '.Common::envtag($operation_params['targetEnv']).'⟶'.Common::envtag($myrepo->getEnv()).'⟶<span class="label-black">'.$myrepo->getDateFormatted().'</span> pour la section de repo <span class="label-white">'.$myrepo->getName().' ❯ '.$myrepo->getDist().' ❯ '.$myrepo->getSection().'</span>', 'success');
            }

            /**
             *  Si l'action est 'reconstruct'
             */
            if ($action == 'reconstruct') {
                $this->chk_param_gpgResign($operation_params['targetGpgResign']);

                if (OS_FAMILY == 'Redhat') History::set($_SESSION['username'], 'Lancement d\'une opération : reconstruction des métadonnées du repo <span class="label-white">'.$myrepo->getName().'</span>'.Common::envtag($myrepo->getEnv()), 'success');
                if (OS_FAMILY == 'Debian') History::set($_SESSION['username'], 'Lancement d\'une opération : reconstruction des métadonnées de la section de repo <span class="label-white">'.$myrepo->getName().' ❯ '.$myrepo->getDist().' ❯ '.$myrepo->getSection().'</span>'.Common::envtag($myrepo->getEnv()), 'success');
            }

            /**
             *  Si l'action est 'restore'
             */
            if ($action == 'restore') {
                $this->chk_param_env($operation_params['targetEnv']);

                /**
                 *  On vérifie que le/la repo/section archivé mentionné existe
                 */
                if ($this->repo->existsId($operation_params['repoId'], 'archived') === false) {
                    throw new Exception("Il n'existe aucun Id de repo archivé ".$operation_params['repoId']);
                }

                if (OS_FAMILY == 'Redhat') History::set($_SESSION['username'], 'Lancement d\'une opération : restauration du repo archivé <span class="label-white">'.$myrepo->getName().'</span> sur'.Common::envtag($operation_params['targetEnv']), 'success');
                if (OS_FAMILY == 'Debian') History::set($_SESSION['username'], 'Lancement d\'une opération : restauration de la section de repo archivée <span class="label-white">'.$myrepo->getName().' ❯ '.$myrepo->getDist().' ❯ '.$myrepo->getSection().'</span> sur'.Common::envtag($operation_params['targetEnv']), 'success');
            }
        }
    }

    /**
     *  Exécution d'une opération dont les paramètres ont été validés par validateForm()
     */
    public function execute(array $operations_params)
    {
        /**
         *  Création d'un Id principal pour identifier l'opération asynchrone
         */
        while (true) {
            $operation_id = Common::generateRandom();

            /**
             *  On crée le fichier JSON et on sort de la boucle si le numéro est disponible
             */
            if (!file_exists(ROOT.'/operations/pool/'.$operation_id.'.json')) {
                touch(ROOT.'/operations/pool/'.$operation_id.'.json');
                break;
            }
        }

        /**
         *  Ajout du contenu de l'array dans un fichier au format JSON
         */
        file_put_contents(ROOT.'/operations/pool/'.$operation_id.'.json', json_encode($operations_params, JSON_PRETTY_PRINT));

        /**
         *  Lancement de execute.php qui va s'occuper de traiter le fichier JSON
         */
        exec("php ".ROOT."/operations/execute.php --id='$operation_id' >/dev/null 2>/dev/null &");

        return $operation_id;
    }
} ?>