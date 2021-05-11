<?php
global $WWW_DIR;
require_once("${WWW_DIR}/class/Database.php");
require_once("${WWW_DIR}/class/Log.php");
require_once("${WWW_DIR}/class/Repo.php");
require_once("${WWW_DIR}/class/Group.php");

class Planification {
    private $log;       // pour instancier un objet Log
    public  $repo;      // pour instancier un objet Repo
    public  $group;     // pour instancier un objet Group

    private $db;
    private $logList = array();
    private $groupList;

    public  $id;
    public  $date;
    public  $time;
    private $action;    // contiendra 'update' ou 'env->newEnv'
    private $gpgCheck;
    private $gpgResign;
    private $status;
    private $error;
    private $logfile;

    public function __construct(array $variables = []) {
        extract($variables);

        /**
         *  Instanciation d'une db car on peut avoir besoin de récupérer certaines infos en BDD
         */
        try {
            $this->db = new databaseConnection();
        } catch(Exception $e) {
            die('Erreur : '.$e->getMessage());
        }

        // Id
        if (!empty($planId)) { $this->id = $planId; }
        // Date
        if (!empty($planDate)) { $this->date = $planDate; }
        // Time
        if (!empty($planTime)) { $this->time = $planTime; }
        // Action
        if (!empty($planAction)) { $this->action = $planAction; }
        // Reminder
        if (!empty($planReminder)) { $this->reminder = $planReminder; }
        // GpgCheck
        if (!empty($planGpgCheck)) { $this->gpgCheck = $planGpgCheck; }
        // GpgResign
        if (!empty($planGpgResign)) { $this->gpgResign = $planGpgResign; }
    }

/**
 *  Ajout d'une nouvelle planification en BDD
 */
public function new() {
    global $OS_FAMILY;

    if (empty($this->date) OR empty($this->time) OR empty($this->action) or (empty($this->repo->name) AND empty($this->group->name))) {
        printAlert("Erreur : paramètres de planification incomplets");
        return;
    }

    if ($OS_FAMILY == "Redhat") {
        // Cas où on ajoute un repo seul
        if (!empty($this->repo->name)) {
            // Cas où l'action est "update"
            if ($this->action == "update") {
                $this->db->exec("INSERT INTO Planifications ('Plan_date', 'Plan_time', 'Plan_action', 'Plan_repo', 'Plan_gpgCheck', 'Plan_gpgResign', 'Plan_reminder', 'Plan_status') 
                VALUES ('$this->date', '$this->time', '$this->action', '{$this->repo->name}', '{$this->gpgCheck}', '{$this->gpgResign}', '$this->reminder', 'queued')"); 
            } else {
                $this->db->exec("INSERT INTO Planifications ('Plan_date', 'Plan_time', 'Plan_action', 'Plan_repo', 'Plan_reminder', 'Plan_status') 
                VALUES ('$this->date', '$this->time', '$this->action', '{$this->repo->name}', '$this->reminder', 'queued')");    
            }
        }
        // Cas où on ajoute un groupe
        if (!empty($this->group->name)) {
            // Cas où l'action est "update"
            if ($this->action == "update") {
                $this->db->exec("INSERT INTO Planifications ('Plan_date', 'Plan_time', 'Plan_action', 'Plan_group', 'Plan_gpgCheck', 'Plan_gpgResign', 'Plan_reminder', 'Plan_status') 
                VALUES ('$this->date', '$this->time', '$this->action', '{$this->group->name}', '{$this->gpgCheck}', '{$this->gpgResign}', '$this->reminder', 'queued')"); 
            } else {
                $this->db->exec("INSERT INTO Planifications ('Plan_date', 'Plan_time', 'Plan_action', 'Plan_group', 'Plan_reminder', 'Plan_status') 
                VALUES ('$this->date', '$this->time', '$this->action', '{$this->group->name}', '$this->reminder', 'queued')");
            }
        }
    }

    if ($OS_FAMILY == "Debian") {
        // Cas où on ajoute un repo seul
        if (!empty($this->repo->name) AND !empty($this->repo->dist) AND !empty($this->repo->section)) {
            // Cas où l'action est "update"
            if ($this->action == "update") {
                $this->db->exec("INSERT INTO Planifications ('Plan_date', 'Plan_time', 'Plan_action', 'Plan_repo', 'Plan_dist', 'Plan_section', 'Plan_gpgCheck', 'Plan_gpgResign', 'Plan_reminder', 'Plan_status') 
                VALUES ('$this->date', '$this->time', '$this->action', '{$this->repo->name}', '{$this->repo->dist}', '{$this->repo->section}', '{$this->gpgCheck}', '{$this->gpgResign}', '$this->reminder', 'queued')");    
            } else {
                $this->db->exec("INSERT INTO Planifications ('Plan_date', 'Plan_time', 'Plan_action', 'Plan_repo', 'Plan_dist', 'Plan_section', 'Plan_reminder', 'Plan_status') 
                VALUES ('$this->date', '$this->time', '$this->action', '{$this->repo->name}', '{$this->repo->dist}', '{$this->repo->section}', '$this->reminder', 'queued')");    
            }
        }
        // Cas où on ajoute un groupe
        if (!empty($this->group->name)) {
            // Cas où l'action est "update"
            if ($this->action == "update") {
                $this->db->exec("INSERT INTO Planifications ('Plan_date', 'Plan_time', 'Plan_action', 'Plan_group', 'Plan_gpgCheck', 'Plan_gpgResign', 'Plan_reminder', 'Plan_status') 
                VALUES ('$this->date', '$this->time', '$this->action', '{$this->group->name}', '{$this->gpgCheck}', '{$this->gpgResign}', '$this->reminder', 'queued')"); 
            } else {
                $this->db->exec("INSERT INTO Planifications ('Plan_date', 'Plan_time', 'Plan_action', 'Plan_group', 'Plan_reminder', 'Plan_status') 
                VALUES ('$this->date', '$this->time', '$this->action', '{$this->group->name}', '$this->reminder', 'queued')");
            }
        }
    }
    printAlert("Planification créée");
}

/**
 *  Suppression d'une planification
 */
    public function delete() {
        if (empty($this->id)) {
            printAlert('<span class="redtext">Erreur : ID de planification non renseigné</span>');
            return;
        }

        $this->db->exec("DELETE FROM planifications WHERE Plan_id = '$this->id'");
        printAlert('Planification supprimée');
    }

    
/**
 *  Exécution d'une planification
 */
    public function exec() {
        global $WWW_DIR;
        global $PLANS_DIR;
        global $OS_FAMILY;
        global $AUTOMATISATION_ENABLED;
        global $ALLOW_AUTOUPDATE_REPOS;
        global $ALLOW_AUTOUPDATE_REPOS_ENV;

        $this->log = new Log('plan');

        /**
         *  Passe le status de la planification à "Running"
         */
        $this->db->exec("UPDATE planifications SET Plan_status = 'running', Plan_logfile = '{$this->log->name}' WHERE Plan_id = '$this->id'");

        /**
         *  0. Démarre l'enregistrement de la planification
         */
        ob_start();

        try {
            // VERIFICATIONS //
            
            /**
             *  1. Si les planifications ne sont pas activées, on quitte
             */
            if ($AUTOMATISATION_ENABLED != "yes") {
                throw new Exception("Erreur (EP01) : Les planifications ne sont pas activées. Vous pouvez modifier ce paramètre depuis l'onglet Paramètres.");
            }

            /**
             *  2. On instancie des objets Repo et Group
             */
            $this->repo = new Repo();
            $this->group = new Group();

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
            if ($this->action == "update") {
                $this->checkAction_update_allowed();
                $this->checkAction_update_gpgCheck();
                $this->checkAction_update_gpgResign();
            }

            /**
             *  6. Si l'action est '->' alors on vérifie que cette action est autorisée
             */
            if (strpos($this->action, '->') !== false) {
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
            if (!empty($this->repo->name)) {
                $this->checkIfRepoExists();
                // On récupère le repo/hote source
                $this->repo->db_getSource();
            }

            /**
             *  9. Si on a renseigné un groupe plutôt qu'un seul repo à traiter, alors on vérifie que le groupe existe dans le fichier de groupe (il a pu être supprimé depuis que la planification a été créée)
             *  Puis on récupère toute la liste du groupe
             */
            if (!empty($this->group->name)) {
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
            if (!empty($this->repo->name) AND empty($this->group->name)) {
                // Si $this->action = update alors on met à jour le repo
                if ($this->action == "update") {
                    if ($this->repo->update() === false) {
                        $this->close(2, 'Une erreur est survenue pendant le traitement, voir les logs');
                    }
                }

                // Si $this->action contient '->' alors il s'agit d'un changement d'env
                if (strpos($this->action, '->') !== false) {
                    // Récupération de l'environnement source et de l'environnement cible
                    $this->repo->env = exec("echo '$this->action' | awk -F '->' '{print $1}'");
                    $this->repo->newEnv = exec("echo '$this->action' | awk -F '->' '{print $2}'");
                    if (empty($this->repo->env) OR empty($this->repo->newEnv)) {
                        $this->close(1, 'Erreur (EP04) : Environnement(s) non défini(s)'); // On sort avec 1 car on considère que c'est une erreur de type vérification
                    }

                    // Traitement
                    if ($OS_FAMILY == "Redhat") { $this->log->title = 'NOUVEL ENVIRONNEMENT DE REPO'; }
                    if ($OS_FAMILY == "Debian") { $this->log->title = 'NOUVEL ENVIRONNEMENT DE SECTION'; }
                    if ($this->repo->changeEnv() === false) {
                        $this->close(2, 'Une erreur est survenue pendant le traitement, voir les logs');
                    }
                }
            }
        

        /**
         *  2. Cas où on traite un groupe de repos/sections
         */
        if (!empty($this->group->name) AND !empty($this->groupList)) {
            // Comme on boucle pour traiter plusieurs repos/sections, on ne peut pas tout quitter en cas d'erreur tant qu'on a pas bouclé sur tous les repos.
            // Du coup on initialise une variable qu'on incrémentera en cas d'erreur.
            // A la fin si cette variable > 0 alors on pourra quitter ce script en erreur ($this->close 1)
            $plan_error = 0;

            // On traite chaque ligne de groupList
            foreach($this->groupList as $repo) {

                // Pour chaque ligne on récupère les infos du repo/section
                $this->repo->name = $repo['Name'];
                
                if ($OS_FAMILY == "Debian") {
                    $this->repo->dist = $repo['Dist'];
                    $this->repo->section = $repo['Section'];
                }
                // on récupère aussi la source du repo
                $this->repo->db_getSource();

                // Si $this->action = update alors on met à jour le repo
                if ($this->action == "update") {
                    if ($this->repo->update() === false) {
                        $plan_error++;
                    }
                    $this->logList[] = $this->repo->log->location;
                }

                // Si $this->action contient -> alors il s'agit d'un changement d'env
                if (strpos($this->action, '->') !== false) {
                    try {
                        $this->repo->env = exec("echo '$this->action' | awk -F '->' '{print $1}'");
                        $this->repo->newEnv = exec("echo '$this->action' | awk -F '->' '{print $2}'");
                        if (empty($this->repo->env) OR empty($this->repo->newEnv)) {
                            if (empty($this->repo->env) OR empty($this->repo->newEnv)) {
                                throw new Exception('Erreur (EP04) : Environnement(s) non défini(s)');
                            }
                        }
                    } catch(Exception $e) {
                        $this->close(2, $e->getMessage());
                    }
        
                    if ($OS_FAMILY == "Redhat") { $this->log->title = 'NOUVEL ENVIRONNEMENT DE REPO'; }
                    if ($OS_FAMILY == "Debian") { $this->log->title = 'NOUVEL ENVIRONNEMENT DE SECTION'; }
                    if ($this->repo->changeEnv() === false) {                
                        $plan_error++;
                    }
                }
            }

            // Si on a rencontré des erreurs dans la boucle, alors on quitte le script
            if ($plan_error > 0) {
                $this->close(2, 'Une erreur est survenue pendant le traitement, voir les logs');
            }
        }

        // Si on est arrivé jusqu'ici alors on peut quitter sans erreur
        $this->close(0, '');
    }

/**
 *  Générer les messages de rappels
 *  Retourne le message approprié
 */
    public function generateReminders() {
        global $PLANS_DIR;
        global $REPOS_LIST;
        global $OS_FAMILY;
        global $DEFAULT_ENV;
      
        $this->repo = new Repo();
        $this->group = new Group();

        //$planFile = "plan-{$this->id}.conf";
              
        /**
         *  1. Récupération des informations de la planification toto
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
            if (!empty($this->repo->name)) {
                $this->checkIfRepoExists();
            }
        
            /**
             *  5. Si on a renseigné un groupe (commence par @) plutôt qu'un seul repo à traiter, alors on vérifie que le groupe existe dans le fichier de groupe (il a pu être supprimé depuis que la planification a été créée)
             *  Puis on récupère toute la liste du groupe
             */
            if (!empty($this->group->name)) {
                // On vérifie que le groupe existe
                $this->checkIfGroupExists();
                // On récupère la liste des repos dans ce groupe
                $this->getGroupRepoList();
            }
        /**
         *  Cloture du try/catch pour la partie Vérifications
         */
        } catch(Exception $e) {
            return $e->getMessage();
        }
      
      
        // TRAITEMENT //
        
        // Cas où la planif à rappeler ne concerne qu'un seul repo/section
        if (!empty($this->repo->name)) {

            // Cas où l'action prévue est une mise à jour
            if ($this->action == "update") {
                if ($OS_FAMILY == "Redhat") {
                    return "Mise à jour du repo {$this->repo->name} <span class=\"td-whitebackground\">${DEFAULT_ENV}</span>";
                }
                if ($OS_FAMILY == "Debian") {
                    return "Mise à jour de la section {$this->repo->section} du repo {$this->repo->name} (distribution {$this->repo->dist}) <span class=\"td-whitebackground\">${DEFAULT_ENV}</span>";
                }
            }
      
            // Cas où l'action prévue est une création d'env
            if (strpos($this->action, '->') !== false) {
                $this->repo->env = exec("echo '$this->action' | awk -F '->' '{print $1}'");
                $this->repo->newEnv = exec("echo '$this->action' | awk -F '->' '{print $2}'");
        
                if (empty($this->repo->env) AND empty($this->repo->newEnv)) {
                    return "Erreur : l'environnement source ou de destination est inconnu";
                }
        
                if ($OS_FAMILY == "Redhat") {
                    return "Changement d'environnement ({$this->repo->env} -> {$this->repo->newEnv}) du repo {$this->repo->name}";
                }
                if ($OS_FAMILY == "Debian") {
                    return "Changement d'environnement ({$this->repo->env} -> {$this->repo->newEnv}) de la section {$this->repo->section} du repo {$this->repo->name} (distribution {$this->repo->dist})";
                }
            }
        }
      
        // Cas où la planif à rappeler concerne un groupe de repo
        if (!empty($this->group->name) AND !empty($this->groupList)) {

            foreach($this->groupList as $line) {
                // Pour chaque ligne on récupère les infos du repo/section
                $this->repo->name = $line['Name'];
                
                if ($OS_FAMILY == "Debian") {
                    $this->repo->dist = $line['Dist'];
                    $this->repo->section = $line['Section'];
                }

                // Cas où l'action prévue est une mise à jour
                if ($this->action == "update") {
                    if ($OS_FAMILY == "Redhat") {
                        return "Mise à jour des repos du groupe {$this->group->name} (environnement ${DEFAULT_ENV})";
                    }
                    if ($OS_FAMILY == "Debian") {
                        return "Mise à jour des sections de repos du groupe {$this->group->name}";
                    }
                }
      
                // Cas où l'action prévue est un changement d'env
                if (strpos($this->action, '->') !== false) {
                    $this->repo->env = exec("echo '$this->action' | awk -F '->' '{print $1}'");
                    $this->repo->newEnv = exec("echo '$this->action' | awk -F '->' '{print $2}'");
                    if (empty($this->repo->env) AND empty($this->repo->newEnv)) {
                        return "Erreur : l'environnement source ou de destination est inconnu";
                    }
                    if ($OS_FAMILY == "Redhat") {
                        return "Changement d'environnement ({$this->repo->env} -> {$this->repo->newEnv}) des repos du groupe {$this->group->name}";
                    }
                    if ($OS_FAMILY == "Debian") {
                        return "Changement d'environnement ({$this->repo->env} -> {$this->repo->newEnv}) des sections de repos du groupe {$this->group->name}";
                    }
                }
            }
        }
    }

/**
 *  Envoi d'un mail d'erreur ou de rappel de planification
 *  A partir d'une variable $template contenant le corps HTML du mail à envoyer
 */
    public function sendMail($title, $template) {
        global $WWW_DIR;
        global $WWW_HOSTNAME;
        global $EMAIL_DEST;
      
        //require("${WWW_DIR}/templates/plan_reminder_mail.inc.php");
        
        // Pour envoyer un mail HTML il faut inclure ces headers
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=utf8';
        mail($EMAIL_DEST, $title, $template, implode("\r\n", $headers));
    }

/**
 *  Clotûre d'une planification exécutée
 *  Génère le récapitulatif, le fichier de log et envoi un mail d'erreur si il y a eu une erreur
 */
    public function close($planError, $plan_msg_error) {
        global $PLANS_DIR;
        global $PLAN_LOGS_DIR;
        global $EMAIL_DEST;
        global $WWW_DIR;
        global $WWW_HOSTNAME;

        /**
         *  Suppression des lignes vides dans le message d'erreur si il y en a
         */
        if ($planError != "0") {
            $plan_msg_error = exec("echo \"$plan_msg_error\" | sed '/^$/d'");
        }

        /**
         *  Mise à jour du status de la planification en BDD
         */
        if ($planError == "0") {
            $this->db->exec("UPDATE planifications SET Plan_status = 'done', Plan_logfile = '{$this->log->name}' WHERE Plan_id = '$this->id'");
        } else {
            $this->db->exec("UPDATE planifications SET Plan_status = 'error', Plan_error = '$plan_msg_error', Plan_logfile = '{$this->log->name}' WHERE Plan_id = '$this->id'");
        }

        // Si l'erreur est de type 1 (erreur lors des vérifications de l'opération), on affiche les erreurs avec echo, elles seront capturées par ob_get_clean()
        // On ajoute également les données connues de la planification, le tableau récapitulatif n'ayant pas pu être généré par l'opération puisqu'on a rencontré une erreur avant qu'elle ne se lance.
        if ($planError == 1) {
            echo "<span class='redtext'>${plan_msg_error}</span>";
            echo '<p><b>Détails de la planification :</b></p>';
            echo '<table>';
            echo "<tr><td><b>Action : </b></td><td>{$this->action}</td></tr>";
            if (!empty($this->group->name)) { echo "<tr><td><b>Groupe : </b></td><td>{$this->group->name}</td></tr>"; }
            if (!empty($this->repo->name)) { echo "<tr><td><b>Repo : </b></td><td>{$this->repo->name}</td></tr>"; }
            if (!empty($this->repo->dist)) { echo "<tr><td><b>Dist : </b></td><td>{$this->repo->dist}</td></tr>"; }
            if (!empty($this->repo->section)) { echo "<tr><td><b>Section : </b></td><td>{$this->repo->section}</td></tr>"; }
            echo '</table>';
        }

// Contenu du fichier de log de la planification //

        // Cas où on traite un groupe de repo et que l'action = update
        // Dans ce cas, chaque repo mis à jour crée son propre fichier de log. On a récupéré le chemin de ces fichiers de log au cours de l'opération et on l'a placé dans un array $this->logList
        // On parcourt donc cet array pour récupérer le contenu de chaque sous-fichier de log afin de créer un fichier de log global de la planification
        if (!empty($this->group->name)) {
            if ($this->action == "update" AND $planError != 1) {
                // On laisse 3 secondes au script check_running.php pour finir de forger les sous-fichiers de log, sinon on prend le risque de récupérer des contenu vide car on va trop vite
                sleep(3); 
                $content = '';
                // Si l'array $this->logList contient des sous-fichier de log alors on récupère leur contenu en le placant dans $content
                if (!empty($this->logList)) {
                    foreach ($this->logList as $log) {
                        $content = $content . file_get_contents($log);
                        // On supprime le sous-fichier de log puisque son contenu vient d'ere récupéré et qu'il sera intégré au fichier de log de la planification
                        unlink($log);
                    }
                }
            // Cas où on traite un groupe de repo mais que l'action n'est pas update (ex : env->env), dans ce cas on récupère le contenu à partir de ob_get_clean();
            } else {
                $content = ob_get_clean();
            }
        }
        
        // Cas où on traite un repo seulement
        if (!empty($this->repo->name) AND empty($this->group->name)) {
            // Si l'action est 'update', un sous-fichier de log sera créé par la fonction $repo->update(). Ce fichier de log existera uniquement si la fonction a pu se lancer (donc pas d'erreur lors des vérifications). On récupère donc le contenu de ce fichier uniquement si il n'y a pas eu d'erreur lors des vérifications ($planError != 1).
            if ($this->action == "update" AND $planError != 1) {
                // On laisse 3 secondes au script check_running.php pour finir de forger le sous-fichier de log, sinon on prend le risque de récupérer un contenu vide car on va trop vite
                sleep(3);
                // On récupère le contenu du fichier de log généré par la fonction $repo->update() si celui-ci existe
                // Ce contenu sera ensuite injecté dans le fichier de log de la planification
                if (!empty($this->repo->log->location)) {
                    $content = file_get_contents($this->repo->log->location);
                } else {
                    $content = '';
                }
                // Du coup on supprime ensuite le fichier de log généré par $repo->update() car on ne souhaite que conserver celui généré par la planification
                if (file_exists("{$this->repo->log->location}")) { unlink("{$this->repo->log->location}"); }
            // Cas où une erreur est survenue lors des vérifications ou bien que l'action n'est pas 'update'
            } else {
                $content = ob_get_clean();
            }
        }

        // Génération du fichier de log final à partir d'un template, le contenu précédemment récupéré est alors inclu dans le template
        include_once("${WWW_DIR}/templates/planification_log.inc.php");
        $this->log->write($logContent);
        $this->log->close();

        // Envoi d'un mail si erreur
        if ($planError != 0) {
            // template HTML du mail, inclu une variable $template contenant le corps du mail avec $plan_msg_error
            include("${WWW_DIR}/templates/plan_error_mail.inc.php");
            $this->sendMail("[ERREUR] Planification Plan-{$this->id} sur $WWW_HOSTNAME", $template);
        }
        exit();
    }

/**
 *  VERIFICATIONS
 */
    private function checkAction() {
        if (empty($this->action)) {
          throw new Exception("Erreur (CP01) : Aucune action n'est spécifiée dans cette planification");
        }
    }

    private function checkAction_update_allowed() {
        global $ALLOW_AUTOUPDATE_REPOS;

        // Si la mise à jour des repos n'est pas autorisée, on quitte
        if ($ALLOW_AUTOUPDATE_REPOS != "yes") {
            throw new Exception("Erreur (CP02) : La mise à jour des miroirs par planification n'est pas autorisée. Vous pouvez modifier ce paramètre depuis l'onglet Paramètres");
        }
    }

    private function checkAction_update_gpgCheck() {
        if (empty($this->repo->gpgCheck)) {
            throw new Exception("Erreur (CP03) : Vérification des signatures GPG non spécifié dans cette planification");
        }
    }

    private function checkAction_update_gpgResign() {
        if (empty($this->repo->gpgResign)) {
            throw new Exception("Erreur (CP04) : Signature des paquets avec GPG non spécifié dans cette planification");
        }
    }

    private function checkAction_env_allowed() {
        global $ALLOW_AUTOUPDATE_REPOS_ENV;

        // Si le changement d'environnement n'est pas autorisé, on quitte
        if ($ALLOW_AUTOUPDATE_REPOS_ENV != "yes") {
            throw new Exception("Erreur (CP05) : Le changement d'environnement par planification n'est pas autorisé. Vous pouvez modifier ce paramètre depuis l'onglet Paramètres.");
        }
    }

    // Vérification si on traite un repo seul ou un groupe
    private function checkIfRepoOrGroup() {
        global $OS_FAMILY;

        if (empty($this->repo->name) AND empty($this->group->name)) {
            throw new Exception("Erreur (CP06) : Aucun repo ou groupe spécifié");
        }
    
        // On va traiter soit un repo soit un groupe de repo, ça ne peut pas être les deux, donc on vérifie que planRepo et planGroup ne sont pas tous les deux renseignés en même temps :
        if (!empty($this->repo->name) AND !empty($this->group->name)) {
            if ($OS_FAMILY == "Redhat") { throw new Exception("Erreur (CP07) : Il n'est pas possible de traiter à la fois un repo et un groupe de repos"); }
            if ($OS_FAMILY == "Debian") { throw new Exception("Erreur (CP07) : Il n'est pas possible de traiter à la fois une section et un groupe de sections"); }
        }
    }
  
    // Vérification que le repo existe
    private function checkIfRepoExists() {
        global $OS_FAMILY;
        global $REPOS_LIST;
    
        if ($OS_FAMILY == "Redhat") {
            // Vérification que le repo existe
            if ($this->repo->exists($this->repo->name) === false) {
                throw new Exception("Erreur (CP08) : Le repo {$this->repo->name} n'existe pas");
            }
        }
    
        if ($OS_FAMILY == "Debian") {       
            // On vérifie qu'on a bien renseigné la distribution et la section
            if (empty($this->repo->dist)) {
                throw new Exception("Erreur (CP10) : Aucune distribution spécifiée");
            }
            if (empty($this->repo->section)) {
                throw new Exception("Erreur (CP11) : Aucune section spécifiée");
            }
        
            // Vérification que la section existe
            if ($this->repo->section_exists($this->repo->name, $this->repo->dist, $this->repo->section) === false) {
                throw new Exception("Erreur (CP12) : La section {$this->repo->section} du repo {$this->repo->name} (distribution {$this->repo->dist}) n'existe pas");
            }
        }
    }  
  
    // Vérification que le groupe existe
    private function checkIfGroupExists() {

        if ($this->repo->db->countRows("SELECT * FROM groups WHERE Name = '{$this->group->name}'") == 0) {
            throw new Exception("Erreur (CP14) : Le groupe {$this->group->name} n'existe pas");
        }
    }
  
    // Récupération de la liste des repo dans le groupe
    private function getGroupRepoList() {
        global $GROUPS_CONF;
        global $REPOS_LIST;
        global $OS_FAMILY;

        // on récupère tous les repos du groupe
        $this->groupList = $this->group->listReposNamesDistinct($this->group->name);
    
        if (empty($this->groupList)) {
            if ($OS_FAMILY == "Redhat") { throw new Exception("Erreur (CP13) : Il n'y a aucun repo renseigné dans le groupe {$this->group->name}"); }
            if ($OS_FAMILY == "Debian") { throw new Exception("Erreur (CP13) : Il n'y a aucune section renseignée dans le groupe {$this->group->name}"); }
        }
    
        // Pour chaque repo/section renseigné(e), on vérifie qu'il/elle existe
        $msg_error = '';
        foreach($this->groupList as $repo) {
            $repoName = $repo['Name'];
            if ($OS_FAMILY == "Debian") { // si Debian on récupère aussi la distrib et la section
                $repoDist = $repo['Dist'];
                $repoSection = $repo['Section'];
            }

            if ($OS_FAMILY == "Redhat") {
                if ($this->repo->exists($repoName) === false) {
                    $msg_error="${msg_error}\nErreur (CP15) : Le repo $repoName dans le groupe {$this->group->name} n'existe pas/plus.";
                }
            }
            
            if ($OS_FAMILY == "Debian") {
                if ($this->repo->section_exists($repoName, $repoDist, $repoSection) === false) {
                    $msg_error="${msg_error}\nErreur (CP16) : La section $repoSection du repo $repoName (distribution $repoDist) dans le groupe {$this->group->name} n'existe pas/plus.";
                }
            }
        }
        // Si des repos/sections n'existent plus alors on quitte
        if (!empty($msg_error)) {
            throw new Exception($msg_error);
        }
    }

    /**
     *  Liste des planifications en attente ou en cours d'exécution
     */
    public function listQueue() {
        $query = $this->db->query("SELECT * FROM planifications WHERE Plan_status = 'queued' OR Plan_status = 'running'");
        while ($datas = $query->fetchArray()) { 
            $plan[] = $datas;
        }
        /**
         *  Retourne un array avec les planifications
         */
        if (!empty($plan)) {
            return $plan;
        }
    }

    /**
    *  Liste les planifications terminées (tout status compris)
    */
    public function listDone() {
        $query = $this->db->query("SELECT * FROM planifications WHERE Plan_status = 'done' OR Plan_status = 'error'");
        while ($datas = $query->fetchArray()) { 
            $plan[] = $datas;
        }
        /**
         *  Retourne un array avec les planifications
         */
        if (!empty($plan)) {
            return $plan;
        }
    }

    /**
     *  Liste la dernière planification exécutée
     */
    public function last() {
        $result = $this->db->queryArray("SELECT Plan_date, Plan_time FROM planifications WHERE Plan_status = 'done' OR Plan_status = 'error' ORDER BY Plan_date DESC, Plan_time DESC LIMIT 1");
        return $result;
    }

    /**
     *  Liste la prochaine planification exécutée
     */
    public function next() {
        $result = $this->db->queryArray("SELECT Plan_date, Plan_time FROM planifications WHERE Plan_status = 'done' OR Plan_status = 'error' ORDER BY Plan_date ASC, Plan_time ASC LIMIT 1");
        return $result;
    }

    /**
    *  Récupère toutes les infos d'un planification
    */
    private function getInfo() {
        if (empty($this->id)) {
            throw new Exception("Erreur (EP02) Impossible de récupérer les informations de la planification car son ID est vide");
        }

        $result = $this->db->querySingleRow("SELECT * FROM planifications WHERE Plan_id = '$this->id'");
        $this->action          = $result['Plan_action'];
        $this->repo->name      = $result['Plan_repo'];
        $this->repo->dist      = $result['Plan_dist'];
        $this->repo->section   = $result['Plan_section'];
        $this->repo->gpgCheck  = $result['Plan_gpgCheck'];
        $this->repo->gpgResign = $result['Plan_gpgResign'];
        $this->group->name     = $result['Plan_group'];
        $this->reminder        = $result['Plan_reminder'];
    }
}
?>