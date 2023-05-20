<?php

namespace Controllers\Log;

use Exception;

class OperationLog
{
    public $type;     // Type de fichier de log (repomanager ou plan)
    public $name;     // Nom complet du fichier de log (repomanager_... ou plan_...)
    private $date;
    private $time;
    // public $action;
    private $location; // Emplacement du fichier de log
    private $pid;
    public $steplog;
    // public $stepName;

    public function getPid()
    {
        return $this->pid;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function getTime()
    {
        return $this->time;
    }

    public function getLocation()
    {
        return $this->location;
    }

    public function setType(string $type)
    {
        $this->type = $type;
    }

    public function initialize()
    {
        /**
         *  Generate a random PID
         */
        $pid = mt_rand(10001, 99999);

        while (file_exists(PID_DIR . '/' . $pid . '.pid')) {
            // If the PID already exists, generate a new one
            $pid = mt_rand(10001, 99999);
        }
        $this->pid = $pid;

        /**
         *  Get the current date and time
         */
        $this->date = date('Y-m-d');
        $this->time = date('H-i-s');

        /**
         *  Generate the name of the log file
         */
        $this->name = $this->date . '_' . $this->time . '_' . $this->type . '_' . $this->pid . '.log';
        $this->location = MAIN_LOGS_DIR . '/' . $this->name;

        /**
         *  Create the PID file
         */
        file_put_contents(PID_DIR . '/' . $this->pid . '.pid', 'PID="' . $this->pid . '"' . PHP_EOL . 'LOG="' . $this->name . '"' . PHP_EOL);

        /**
         *  Create the log file
         */
        if (file_exists($this->location)) {
            throw new Exception("Error: a log file with the same name ($this->location) already exists");
        }
        if (!touch($this->location)) {
            throw new Exception('Error: cannot create log file');
        }

        /**
         *  Update symbolic link lastlog.log to point to the newly created log file
         */
        if (file_exists(MAIN_LOGS_DIR . '/lastlog.log')) {
            unlink(MAIN_LOGS_DIR . '/lastlog.log');
        }

        exec("ln -sfn $this->location " . MAIN_LOGS_DIR . '/lastlog.log');
    }

    /**
     *  Ajout d'un subpid au fichier de PID principal
     */
    public function addsubpid(string $pid)
    {
        file_put_contents(PID_DIR . '/' . $this->pid . '.pid', 'SUBPID="' . $pid . '"' . PHP_EOL, FILE_APPEND);
    }

    /**
     *  Ecrire dans le fichier de log
     */
    public function write(string $content)
    {
        file_put_contents($this->location, $content);
    }

    public function close()
    {
        /**
         *  Suppression du fichier PID
         */
        if (file_exists(PID_DIR . '/' . $this->pid . '.pid')) {
            unlink(PID_DIR . '/' . $this->pid . '.pid');
        }
    }

    public function steplog(int $number)
    {
        /**
         *  Créé le répertoire accueillant le fichier de log d'étape si n'existe pas
         */
        if (!is_dir(TEMP_DIR . '/' . $this->pid . '/' . $number)) {
            mkdir(TEMP_DIR . '/' . $this->pid . '/' . $number, 0770, true);
        }

        /**
         *  Chemin complet vers le fichier de log d'étape
         */
        $this->steplog = TEMP_DIR . '/' . $this->pid . '/' . $number . '/' . $number . '.log';
    }

    /**
     *  Initialise le div principal de l'étape en cours
     *  Ce div contiendra le titre le l'étape, l'éventuel contenu renvoyé par l'exécution de l'opération, ainsi que les message d'erreurs de l'étape
     */
    public function steplogInitialize(string $stepId)
    {
        echo '<div class="' . $stepId . '-maindiv-' . $this->pid . ' op-step-div">';
    }

    /**
     *  Ecrit le contenu dans le fichier de log de l'étape en cours
     *  Puis relance la capture (ob_start)
     */
    public function steplogWrite(string $message = null)
    {
        if (!empty($message)) {
            file_put_contents($this->steplog, $message, FILE_APPEND);
        } else {
            file_put_contents($this->steplog, ob_get_clean(), FILE_APPEND);
            ob_start();
        }
    }

    /**
     *  Affiche le titre de l'étape
     */
    public function steplogName(string $name)
    {
        echo '<div class="op-step-title"><span>' . $name . '</span></div>';
        $this->steplogWrite();
    }

    /**
     *  Affiche un message Terminé dans le div de l'étape en cours et affiche un fond vert pour signaler que l'étape s'est déroulée sans erreur
     */
    public function steplogOK(string $stepId, string $duration, string $message = null)
    {
        /**
         *  On affiche l'éventuel message si spécifié, sinon on affiche 'Terminé'
         */
        if (!empty($message)) {
            echo '<div class="op-step-title-ok">' . $message . '</div>';
        } else {
            echo '<div class="op-step-title-ok">Completed</div>';
        }

        /**
         *  Affichage du temps d'exécution de l'étape
         */
        echo '<div class="' . $stepId . '-time op-step-time"></div>';

        /**
         *  Clôture de maindiv ouvert par steplogInitialize
         */
        echo '</div>';

        echo '<style>';
        echo ".${stepId}-loading-{$this->pid} { display: none; }";
        echo ".${stepId}-maindiv-{$this->pid} { background-color: #15bf7f; }";
        echo ".${stepId}-time:before { content: '" . $duration . "' }";
        echo '</style>';

        $this->steplogWrite();
    }

    /**
     *  Affiche un message d'erreur dans le div de l'étape en cours et affiche un fond rouge pour signaler que l'étape a rencontré des erreurs
     */
    public function steplogError(string $stepId, string $duration, string $error)
    {
        echo '<div class="op-step-title-error">' . $error . '</div>';

        /**
         *  Affichage du temps d'exécution de l'étape
         */
        echo '<div class="' . $stepId . '-time op-step-time"></div>';

        /**
         *  Clôture de maindiv ouvert par steplogInitialize
         */
        echo '</div>';

        echo '<style>';
        echo ".${stepId}-loading-{$this->pid} { display: none; }";
        echo ".${stepId}-maindiv-{$this->pid} { background-color: #ff0044; }";
        echo ".${stepId}-time:before { content: '" . $duration . "' }";
        echo '</style>';

        $this->steplogWrite();
    }

    /**
     *  Affiche la durée totale de l'opération dans le même format que les autres étapes
     */
    public function steplogDuration(string $stepId, string $duration)
    {
        echo '<div class="op-step-duration">' . $duration . '</div>';

        /**
         *  Clôture de maindiv ouvert par steplogInitialize
         */
        echo '</div>';

        echo '<style>';
        echo ".${stepId}-loading-{$this->pid} { display: none; }";
        echo ".${stepId}-maindiv-{$this->pid} { background-color: #182b3e; }";
        echo '</style>';

        $this->steplogWrite();
    }

    /**
     *  Affiche une animation 'en cours' dans le div de l'étape en cours
     */
    public function steplogLoading(string $stepId)
    {
        echo '<span class="' . $stepId . '-loading-' . $this->pid . ' op-step-loading">Running<img src="assets/images/loading.gif" class="icon" /></span>';
        $this->steplogWrite();
    }

    /**
     *  Affiche une icone 'Warning' dans le div de l'étape en cours
     */
    public function steplogWarning()
    {
        echo '<div class="op-step-title-warning"><img src="assets/icons/warning.png" class="icon" /></div>';
        $this->steplogWrite();
    }

    public function steplogBuild(int $steps)
    {
        $j = 0;

        /**
         *  On ajoute chaque log d'étape au fichier de log principal
         *  Exemple : ./temp/$PID/1/1.log est ajouté au fichier de log principal
         */
        while ($j != ($steps + 1)) { // On boucle sur tous les petits fichiers de log d'étapes jusqu'à atteindre le nombre d'étapes totales
            $stepLog = TEMP_DIR . '/' . $this->pid . '/' . $j . '/' . $j . '.log';

            if (file_exists($stepLog)) {
                file_put_contents($this->location, file_get_contents($stepLog), FILE_APPEND);
            }
            ++$j;
        }
    }

    /**
     *  Run logBuilder process in background
     */
    public function runLogBuilder(int $pid, string $location, int $steps)
    {
        $myprocess = new \Controllers\Process('php ' . LOGBUILDER . ' ' . $pid . ' ' . $location . ' ' . $steps . ' >/dev/null 2>/dev/null &');
        $myprocess->execute();
        $myprocess->close();
        unset($myprocess);
    }

    /**
     *  LogBuilder
     */
    public function logBuilder(int $pid, string $logFile, int $steps)
    {
        $mylayoutContainer = new \Controllers\Layout\ContainerState();
        $operationTempDir = TEMP_DIR . '/' . $pid;

        /**
         *  While the "completed" file doesn't exist in the temporary directory, we rewrite the main log file to make sure it's up to date
         */
        while (!file_exists($operationTempDir . '/completed')) {
            $this->writeStepLog($pid, $logFile, $steps);

            /**
             *  Make the following container refreshable by the client
             */
            $mylayoutContainer->update('operations/log');
            sleep(1);
        }

        /**
         *  When the operation is completed, we rewrite the main log file one last time to make sure we got all the step logs
         */
        $this->writeStepLog($pid, $logFile, $steps);

        /**
         *  Make the following container refreshable by the client
         */
        $mylayoutContainer->update('operations/log');
    }

    private function writeStepLog(int $pid, string $logFile, int $steps)
    {
        $j = 0;

        $operationTempDir = TEMP_DIR . '/' . $pid;

        /**
         *  Delete the log file before rebuilding it
         */
        if (file_exists($logFile)) {
            unlink($logFile);
        }
        touch($logFile);

        /**
         *  Adding each step log to the main log file
         *  Example: ./temp/$PID/1/1.log is added to the main log file
         */

        /**
         *  Looping on all the step logs until we reach the total number of steps
         */
        while ($j != ($steps + 1)) {
            $stepLog = $operationTempDir . '/' . $j . '/' . $j . '.log';

            if (file_exists($stepLog)) {
                file_put_contents($logFile, file_get_contents($stepLog), FILE_APPEND);
            }

            ++$j;
        }
    }
}
