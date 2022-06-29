<?php

namespace Controllers;

class Process
{
    private $workingDir = ROOT . '/.temp'; // Répertoire de travail pour php
    private $command;
    private $env;
    private $process;
    private $pipes;
    private $pid;
    private $returnCode = null;

    public function __construct(string $command, array $env = null)
    {
        $this->command = $command;

        /**
         *  Définition des variables d'environnement minimales pour ce process
         */
        $this->env = array('HOME' => ROOT);

        /**
         *  Si d'autres variables ont été spécifiées alors on les ajoute.
         */
        if (!empty($env)) {
            $this->env = array_merge($this->env, $env);
        }
    }

    /**
     *  Créer et exécute un nouveau processus selon la commande spécifiée
     */
    public function exec()
    {
        $descriptors = array(
            0 => array("pipe", "r"), // stdin is a pipe that the child will read from
            1 => array("pipe", "w"), // stdout is a pipe that the child will write to
            2 => array("pipe", "w")  // stderr is a pipe that the child will write to
        );

        /**
         *  Execution
         */
        $this->process = proc_open($this->command, $descriptors, $this->pipes, $this->workingDir, $this->env);

        /**
         *  Empêche les pipes de bloquer la suite de l'exécution
         */
        stream_set_blocking($this->pipes[1], 0);
        stream_set_blocking($this->pipes[2], 0);

        /**
         *  On récupère le PID du process en cours
         */
        $this->pid = $this->getPid();
    }

    /**
     *  Clôture du processus
     */
    public function close()
    {
        /**
         *  Clôture des pipes
         */
        fclose($this->pipes[0]);
        fclose($this->pipes[1]);
        fclose($this->pipes[2]);

        proc_close($this->process);
    }

    /**
     *  Retourne le status actuel du process
     */
    public function getStatus()
    {
        $this->status = proc_get_status($this->process);

        /**
         *  On en profite pour récupérer le code d'erreur si spécifié
         */
        if (!empty($this->status['exitcode']) and $this->status['exitcode'] != "-1") {
            $this->returnCode = $this->status['exitcode'];
        }

        return $this->status;
    }

    /**
     *  Retourne le code de retour / code d'erreur du process
     *  Le code de retour est présent dans le dernier accès au status du process, avant sa fin.
     */
    public function getReturnCode()
    {
        return $this->returnCode;
    }

    /**
     *  Affiche la sortie du process en temps réel
     *  La sortie peut être ajouté à la suite d'un fichier si spécifié
     */
    public function getOutput(string $filePath = null)
    {
        if (is_resource($this->process)) {
            while ($this->isRunning()) {

                /**
                 *  Si un fichier a été spécifié, on ajoute le contenu de la sortie dans le fichier
                 */
                if (!empty($filePath)) {
                    file_put_contents($filePath, stream_get_contents($this->pipes[1]), FILE_APPEND); // stdout
                    file_put_contents($filePath, stream_get_contents($this->pipes[2]), FILE_APPEND); // stderr

                /**
                 *  Sinon on affiche directement la sortie
                 */
                } else {
                    echo stream_get_contents($this->pipes[1]); // stdout
                    echo stream_get_contents($this->pipes[2]); // stderr
                }

                $this->getStatus();
            }

            $this->getStatus();

            $this->close();
        }
    }

    /**
     *  Retourne le PID du process
     */
    public function getPid()
    {
        $procInfo = proc_get_status($this->process);

        if (!empty($procInfo['pid'])) {
            return $procInfo['pid'];
        }

        return null;
    }

    /**
     *  Retourne true si le process est en cours
     */
    public function isRunning()
    {
        $status = $this->getStatus();

        if ($status['running'] === true) {
            return true;
        }

        return false;
    }
}
