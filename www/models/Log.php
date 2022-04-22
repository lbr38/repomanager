<?php

class Log {
    public $type;       // Type de fichier de log (repomanager ou plan)
    public $date;
    public $time;
    public $name;       // Nom complet du fichier de log (repomanager_... ou plan_...)
    public $location;   // Emplacement du fichier de log
    public $action;
    public $pid;
    public $steplog;
    public $stepName;
    public $title;

    public function __construct(string $type) {
        if (empty($type)) throw new Error('Erreur : le type de fichier de log ne peut pas être vide');

        if (!empty($action)) { $this->action = $action; }

        /**
         *  Génération d'un PID
         */
        $PID = mt_rand(10001, 99999);
        while (file_exists(PID_DIR."/${PID}.pid")) {
            // Re-génération d'un PID si celui-ci est déjà prit
            $PID = mt_rand(10001, 99999);
        }
        $this->pid = $PID;

        /**
         *  Seuls les types "main" ou "plan" sont valides
         */
        if ($type == "repomanager" or $type == "plan") {
            $this->type = $type;
        } else {
            throw new Error('Erreur : le type de fichier de log est invalide');
        }

        $this->date = date("Y-m-d");
        $this->time = date("H-i-s");

        $this->name = "{$this->type}_{$this->pid}_{$this->date}_{$this->time}.log";
        $this->location = MAIN_LOGS_DIR."/{$this->name}";

        /**
         *   Création du fichier PID
         */
        file_put_contents(PID_DIR."/{$this->pid}.pid", "PID=\"{$this->pid}\"\nLOG=\"$this->name\"".PHP_EOL);
        if (!empty($this->action)) {
            file_put_contents(PID_DIR."/{$this->pid}.pid", "ACTION=\"{$this->action}\"".PHP_EOL, FILE_APPEND);
        }

        /**
         *  Génération du fichier de log
         */
        if (file_exists($this->location)) throw new Error("Erreur : un fichier de log du même nom ({$this->location}) existe déjà");
        if (!touch($this->location)) throw new Error('Erreur : impossible de générer le fichier de log');

        /**
         *  Modification du lien symbolique lastlog.log pour le faire pointer vers le nouveau fichier de log précédemment créé
         */
        if (file_exists(MAIN_LOGS_DIR."/lastlog.log")) unlink(MAIN_LOGS_DIR."/lastlog.log");
        exec("ln -sfn $this->location ".MAIN_LOGS_DIR."/lastlog.log");
    }

    /**
     *  Ajout d'un subpid au fichier de PID principal
     */
    public function addsubpid(string $pid) {
        file_put_contents(PID_DIR."/{$this->pid}.pid", "SUBPID=\"${pid}\"".PHP_EOL, FILE_APPEND);
    }

    public function write(string $content) {
        file_put_contents($this->location, $content);
    }

    public function close() {
        /**
         *  Suppression du fichier PID
         */
        if (file_exists(PID_DIR."/{$this->pid}.pid")) {
            unlink(PID_DIR."/{$this->pid}.pid");
        }
    }

    public function steplog(int $number) {
        /**
         *  Créé le répertoire accueillant le fichier de log d'étape si n'existe pas
         */
        if (!is_dir(TEMP_DIR."/{$this->pid}/$number")) mkdir(TEMP_DIR."/{$this->pid}/$number", 0770, true);

        /**
         *  Chemin complet vers le fichier de log d'étape
         */
        $this->steplog = TEMP_DIR."/$this->pid/${number}/${number}.log";
    }

    /**
     *  Initialise le div principal de l'étape en cours
     *  Ce div contiendra le titre le l'étape, l'éventuel contenu renvoyé par l'exécution de l'opération, ainsi que les message d'erreurs de l'étape
     */
    public function steplogInitialize(string $stepName) {
        $this->stepName = $stepName;
        echo "<div class=\"{$this->stepName}-maindiv-{$this->pid} op-step-div\">";
    }

    /**
     *  Ecrit le contenu dans le fichier de log de l'étape en cours
     *  Puis relance la capture (ob_start)
     */
    public function steplogWrite() {
        file_put_contents($this->steplog, ob_get_clean(), FILE_APPEND);
        ob_start();
    }

    /**
     *  Affiche le titre de l'étape
     */
    public function steplogTitle(string $title) {
        echo "<div class=\"op-step-title\"><span>${title} </span></div>";
        $this->steplogWrite();
    }

    /**
     *  Affiche un message Terminé dans le div de l'étape en cours et affiche un fond vert pour signaler que l'étape s'est déroulée sans erreur
     */
    public function steplogOK(string $message = '') {
        /**
         *  On affiche l'éventuel message si renseigné, sinon on affiche 'Terminé'
         */
        if (!empty($message)) {
            echo "<div class=\"op-step-title-ok\">$message</div>";
        } else {
            echo "<div class=\"op-step-title-ok\">Terminé</div>";
        }
        echo '</div>'; // cloture de maindiv ouvert par steplogInitialize

        echo '<style>';
        echo ".{$this->stepName}-loading-{$this->pid} { display: none; }";
        echo ".{$this->stepName}-maindiv-{$this->pid} { background-color: #489f4d; }";
        echo '</style>';

        $this->steplogWrite();
    }

    /**
     *  Affiche une icone 'Warning' dans le div de l'étape en cours 
     */
    public function steplogWarning() {
        echo "<div class=\"op-step-title-warning\"><img src=\"ressources/icons/warning.png\" class=\"icon\" /></div>";
        $this->steplogWrite();
    }

    /**
     *  Affiche un message d'erreur dans le div de l'étape en cours et affiche un fond rouge pour signaler que l'étape a rencontré des erreurs
     */
    public function steplogError(string $error) {
        echo "<div class=\"op-step-title-error\">$error</div>";
        echo '</div>'; // cloture de maindiv ouvert par steplogInitialize

        echo '<style>';
        echo ".{$this->stepName}-loading-{$this->pid} { display: none; }";
        echo ".{$this->stepName}-maindiv-{$this->pid} { background-color: #d9534f; }";
        echo '</style>';

        $this->steplogWrite();
    }

    /**
     *  Affiche une animation 'en cours' dans le div de l'étape en cours
     */
    public function steplogLoading() {
        echo "<span class=\"{$this->stepName}-loading-{$this->pid} op-step-loading\">En cours<img src=\"ressources/images/loading.gif\" class=\"icon\" /></span>";
        $this->steplogWrite();
    }

    public function steplogBuild(int $steps) {
        $j = 0;

        /**
         *  On ajoute chaque log d'étape au fichier de log principal
         *  Exemple : ./temp/$PID/1/1.log est ajouté au fichier de log principal
         */
        while ($j != ($steps + 1)) { // On boucle sur tous les petits fichiers de log d'étapes jusqu'à atteindre le nombre d'étapes totales
            $stepLog = TEMP_DIR."/{$this->pid}/${j}/${j}.log";
            if (file_exists($stepLog)) file_put_contents($this->location, file_get_contents($stepLog), FILE_APPEND);
            ++$j;
        }
    }

    /**
     *  Clotûre l'opération en étapes
     */
    public function closeStepOperation() {
        /**
         *  Génère un fichier 'completed' dans le répertoire temporaire des étapes de l'opération, ceci afin que logbuilder.php s'arrête
         */
        touch(TEMP_DIR."/{$this->pid}/completed");

        /**
         *  Détruit le fichier PID
         */
        $this->close();
    }
}
?>