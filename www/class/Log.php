<?php

class Log {
    public $type;       // Type de fichier de log (repomanager ou plan)
    public $date;
    public $time;
    public $name;       // Nom complet du fichier de log (repomanager_... ou plan_...)
    public $location;   // Emplacement du fichier de log
    public $title;
    public $pid;
    public $steplog;

    public function __construct(string $type) {
        global $MAIN_LOGS_DIR;
        global $PID_DIR;

        if (empty($type)) {
            throw new Error('Erreur : le type de fichier de log ne peut pas être vide');
        }

        /**
         *  Génération d'un PID
         */
        $PID = mt_rand(10001, 99999);
        while (file_exists("${PID_DIR}/${PID}.pid")) {
            // Re-génération d'un PID si celui-ci est déjà prit
            $PID = mt_rand(10001, 99999);
        }
        $this->pid = $PID;

        /**
         *  Seuls les types "main" ou "plan" sont valides
         */
        if ($type == "repomanager" OR $type == "plan") {
            $this->type = $type;
        } else {
            throw new Error('Erreur : le type de fichier de log est invalide');
        }

        $this->date = exec("date +%Y-%m-%d");
        $this->time = exec("date +%H-%M-%S");

        $this->name = "{$this->type}_{$this->pid}_{$this->date}_{$this->time}.log";
        $this->location = "$MAIN_LOGS_DIR/{$this->name}";

        /**
         *   Création du fichier PID
         */
        
        touch("${PID_DIR}/{$this->pid}.pid");
        file_put_contents("${PID_DIR}/{$this->pid}.pid", "PID=\"{$this->pid}\"LOG=\"$this->name\"");

        /**
         *  Génération du fichier de log
         */
        if (file_exists($this->location)) {
            throw new Error("Erreur : un fichier de log du même nom ({$this->location}) existe déjà");
        }
        if (!touch($this->location)) {
            throw new Error('Erreur : impossible de générer le fichier de log');
        }

        /**
         *  Modification du lien symbolique lastlog.log pour le faire pointer vers le nouveau fichier de log précédemment créé
         */
        if (file_exists("${MAIN_LOGS_DIR}/lastlog.log")) {
            unlink("${MAIN_LOGS_DIR}/lastlog.log");
        }
        exec("ln -sfn $this->location ${MAIN_LOGS_DIR}/lastlog.log");
    }

    public function write(string $content) {
        file_put_contents($this->location, $content);
    }

    public function close() {
        global $PID_DIR;
        /**
         *  Suppression du fichier PID
         */

        if (file_exists("${PID_DIR}/{$this->pid}.pid")) {
            unlink("${PID_DIR}/{$this->pid}.pid");
        }
    }

    public function steplog(int $number) {
        global $TEMP_DIR;

        /**
         *  Créé le répertoire accueillant le fichier de log si n'existe pas
         */
        if (!is_dir("${TEMP_DIR}/{$this->pid}/$number")) { mkdir("${TEMP_DIR}/{$this->pid}/$number", 0770, true); }

        /**
         *  Chemin complet vers le fichier de log d'étape
         */
        $this->steplog = "$TEMP_DIR/$this->pid/${number}/${number}.log";
    }

    public function closeStepOperation() {
        global $TEMP_DIR;

        /**
         *  Génère un fichier 'completed' dans le répertoire temporaire des étapes de l'opération, ceci afin que check_running.php s'arrête
         */
        touch("$TEMP_DIR/{$this->pid}/completed");

        /**
         *  Détruit le fichier PID
         */
        $this->close();
    }
}
?>