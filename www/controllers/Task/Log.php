<?php

namespace Controllers\Task;

use Exception;

class Log
{
    private $name;      // Full name
    private $date;
    private $time;
    private $location;  // Path to the log file
    private $pid;
    private $steplog;
    private $stepName;
    private $stepNumber = 0;
    private $stepTimeStart;

    public function __construct(int $taskId)
    {
        $this->taskId = $taskId;
    }

    public function generateLog()
    {
        /**
         *  Get the current date and time
         */
        $this->date = date('Y-m-d');
        $this->time = date('H-i-s');

        /**
         *  Generate the name of the log file
         */
        $this->name = $this->date . '_' . $this->time . '_task_' . $this->taskId . '.log';

        /**
         *  Path to the log file
         */
        $this->location = MAIN_LOGS_DIR . '/' . $this->name;

        /**
         *  Create the log file
         */
        if (!file_exists($this->location)) {
            if (!touch($this->location)) {
                throw new Exception('Error: cannot create log file');
            }
        }

        /**
         *  Update symbolic link 'latest' to point to the newly created log file
         */
        if (file_exists(MAIN_LOGS_DIR . '/latest')) {
            /**
             *  If first unlink fails, try another time with a random sleep time
             *  To fix error when multiple tasks are running at the same time and tries to update the symlink
             */
            if (!unlink(MAIN_LOGS_DIR . '/latest')) {
                usleep(rand(100000, 1500000));

                if (!unlink(MAIN_LOGS_DIR . '/latest')) {
                    throw new Exception('Error while generating task log: cannot remove symlink to the latest log file');
                }
            }
        }

        if (!symlink($this->location, MAIN_LOGS_DIR . '/latest')) {
            throw new Exception('Error while generating task log: cannot create symlink to the latest log file');
        }
    }

    public function getDate()
    {
        return $this->date;
    }

    public function getTime()
    {
        return $this->time;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getLocation()
    {
        return $this->location;
    }

    public function getStepLog()
    {
        return $this->steplog;
    }

    /**
     *  Ecrire dans le fichier de log
     */
    public function write(string $content)
    {
        file_put_contents($this->location, $content);
    }

    /**
     *  Création d'un nouvelle étape dans l'opération et donc un nouveau fichier de log pour cette étape
     */
    public function step(string $name = null, bool $printLoading = true)
    {
        /**
         *  Incrémentation du numéro d'étape
         */
        $this->stepNumber++;

        /**
         *  Initialisation de l'heure de démarrage de cette étape
         */
        $this->stepTimeStart = microtime(true);

        /**
         *  Création d'un fichier de log pour cette étape
         */

        /**
         *  Créé le répertoire accueillant le fichier de log d'étape si n'existe pas
         */
        if (!is_dir(TEMP_DIR . '/' . $this->taskId . '/' . $this->stepNumber)) {
            mkdir(TEMP_DIR . '/' . $this->taskId . '/' . $this->stepNumber, 0770, true);
        }

        /**
         *  Chemin complet vers le fichier de log d'étape
         */
        $this->steplog = TEMP_DIR . '/' . $this->taskId . '/' . $this->stepNumber . '/' . $this->stepNumber . '.log';

        /**
         *  If the step has a name (a title), then display it
         */
        if (!empty($name)) {
            $this->stepName = $name;
            $this->stepId = \Controllers\Common::randomString(24);

            /**
             *  Initialisation du fichier de configuration
             */
            $this->steplogInitialize($this->stepId);

            /**
             *  Affichage du titre de l'étape
             */
            $this->steplogName($this->stepName);

            /**
             *  Affichage d'une icone de chargement
             */
            if ($printLoading === true) {
                $this->steplogLoading($this->stepId);
            }
        }
    }

    /**
     *  Initialise le div principal de l'étape en cours
     *  Ce div contiendra le titre le l'étape, l'éventuel contenu renvoyé par l'exécution de l'opération, ainsi que les message d'erreurs de l'étape
     */
    public function steplogInitialize(string $stepId)
    {
        echo '<div class="' . $stepId . '-maindiv-' . $this->taskId . ' op-step-div">';
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
        echo '<div class="op-step-title-stopped">Task stopped by user</div>';
        $this->steplogWrite();
    }

    /**
     *  Affiche un message Terminé dans le div de l'étape en cours et affiche un fond vert pour signaler que l'étape s'est déroulée sans erreur
     */
    public function stepOK(string $message = null)
    {
        $duration = \Controllers\Common::convertMicrotime(microtime(true) - $this->stepTimeStart);

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
        echo '<div class="' . $this->stepId . '-time op-step-time"></div>';

        /**
         *  Clôture de maindiv ouvert par steplogInitialize
         */
        echo '</div>';

        echo '<style>';
        echo '.' . $this->stepId . '-loading-' . $this->taskId . ' { display: none; }';
        echo '.' . $this->stepId . '-maindiv-' . $this->taskId . ' { background-color: #15bf7f; }';
        echo '.' . $this->stepId . '-time:before { content: "' . $duration . '" }';
        echo '</style>';

        $this->steplogWrite();
    }

    /**
     *  Affiche un message d'erreur dans le div de l'étape en cours et affiche un fond rouge pour signaler que l'étape a rencontré des erreurs
     */
    public function stepError(string $error)
    {
        $duration = \Controllers\Common::convertMicrotime(microtime(true) - $this->stepTimeStart);

        echo '<div class="op-step-title-error">' . $error . '</div>';

        /**
         *  Affichage du temps d'exécution de l'étape
         */
        echo '<div class="' . $this->stepId . '-time op-step-time"></div>';

        /**
         *  Clôture de maindiv ouvert par steplogInitialize
         */
        echo '</div>';

        echo '<style>';
        echo '.' . $this->stepId . '-loading-' . $this->taskId . ' { display: none; }';
        echo '.' . $this->stepId . '-maindiv-' . $this->taskId . ' { background-color: #ff0044; }';
        echo '.' . $this->stepId . '-time:before { content: "' . $duration . '" }';
        echo '</style>';

        $this->steplogWrite();
    }

    /**
     *  Affichage d'une icône de warning pour l'étape en cours
     */
    public function stepWarning()
    {
        echo '<div class="op-step-title-warning"><img src="/assets/icons/warning.png" class="icon" /></div>';
        $this->steplogWrite();
    }

    /**
     *  Prints the total duration of the task in the same format as the other steps
     */
    public function stepDuration(string $duration)
    {
        $duration = \Controllers\Common::convertMicrotime($duration);
        if (empty($duration)) {
            $duration = '0s';
        }

        $this->step('TOTAL DURATION', false);

        echo '<div class="op-step-duration">' . $duration . '</div>';

        /**
         *  Closing maindiv opened by steplogInitialize
         */
        echo '</div>';

        echo '<style>';
        echo '.' . $this->stepId . '-loading-' . $this->taskId . ' { display: none; }';
        echo '.' . $this->stepId . '-maindiv-' . $this->taskId . ' { background-color: #182b3e; }';
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
        echo ".${stepId}-loading-{$this->taskId} { display: none; }";
        echo ".${stepId}-maindiv-{$this->taskId} { background-color: #182b3e; }";
        echo '</style>';

        $this->steplogWrite();
    }

    /**
     *  Affiche une animation 'en cours' dans le div de l'étape en cours
     */
    public function steplogLoading(string $stepId)
    {
        echo '<span class="' . $stepId . '-loading-' . $this->taskId . ' op-step-loading">Running<img src="/assets/images/loading.gif" class="icon" /></span>';
        $this->steplogWrite();
    }

    /**
     *  Run logBuilder process in background
     */
    public function runLogBuilder(int $taskId, string $location)
    {
        /**
         *  Clean temporary directory if exists
         */
        if (is_dir(TEMP_DIR . '/' . $taskId)) {
            \Controllers\Filesystem\Directory::deleteRecursive(TEMP_DIR . '/' . $taskId);
        }

        $myprocess = new \Controllers\Process('/usr/bin/php ' . LOGBUILDER . ' ' . $taskId . ' ' . $location . ' >/dev/null 2>/dev/null &');
        $myprocess->execute();
        $myprocess->close();

        unset($myprocess);
    }

    /**
     *  Log builder
     */
    public function logBuilder(int $taskId, string $logFile)
    {
        $mylayoutContainer = new \Controllers\Layout\ContainerState();

        /**
         *  While the "completed" file doesn't exist in the temporary directory, we rewrite the main log file to make sure it's up to date
         */
        while (!file_exists(TEMP_DIR . '/' . $taskId . '/completed')) {
            $this->writeStepLog($taskId, $logFile);

            /**
             *  Make the following container refreshable by the client
             */
            $mylayoutContainer->update('tasks/log');

            sleep(1);
        }

        /**
         *  When the task is completed, we rewrite the main log file one last time to make sure we got all the step logs
         */
        $this->writeStepLog($taskId, $logFile);

        /**
         *  Make the following container refreshable by the client
         */
        $mylayoutContainer->update('tasks/log');

        /**
         *  Clean temporary directory
         */
        \Controllers\Filesystem\Directory::deleteRecursive(TEMP_DIR . '/' . $taskId);
    }

    private function writeStepLog(int $taskId, string $logFile)
    {
        $j = 0;

        /**
         *  Delete the main log file before rebuilding it
         */
        if (file_exists($logFile)) {
            if (!unlink($logFile)) {
                throw new Exception('Cannot delete log file ' . $logFile);
            }
        }

        if (!touch($logFile)) {
            throw new Exception('Cannot create log file ' . $logFile);
        }

        /**
         *  Adding each step log to the main log file
         *  Example: ./temp/$taskId/1/1.log is added to the main log file
         */

        /**
         *  Looping on all the step logs until we reach the total number of steps
         */
        $steps = 10;

        while ($j != ($steps + 1)) {
            $stepLog = TEMP_DIR . '/' . $taskId . '/' . $j . '/' . $j . '.log';

            /**
             *  If step log exists, we append it to the main log file
             */
            if (file_exists($stepLog)) {
                if (!file_put_contents($logFile, file_get_contents($stepLog), FILE_APPEND)) {
                    throw new Exception('Cannot write step log to main log file');
                }
            }

            ++$j;
        }
    }
}
