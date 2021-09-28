<?php
trait op_signPackages {
    /**
     *  Signature des paquets (Redhat) avec GPG
     *  Opération exclusive à Redhat car sous Debian c'est le fichier Release du repo qu'on signe
     */
    public function op_signPackages() {
        global $OS_FAMILY;
        global $REPOS_DIR;
        global $GPGHOME;
        global $GPG_KEYID;
        global $PASSPHRASE_FILE;
        global $PID_DIR;
        $warning = 0;

        ob_start();

        /**
         *  Signature des paquets du repo avec GPG
         *  Redhat seulement car sur Debian c'est le fichier Release qui est signé ors de la création du repo
         */
        if ($OS_FAMILY == "Redhat" AND ($this->repo->signed == "yes" OR $this->repo->gpgResign == "yes")) {

            $this->log->steplogInitialize('signPackages');
            $this->log->steplogTitle('SIGNATURE DES PAQUETS (GPG)');
            $this->log->steplogLoading();

            $descriptors = array(
                0 => array('file', 'php://stdin', 'r'),
                1 => array('file', 'php://stdout', 'w'),
                2 => array('pipe', 'w')
            );

            echo '<div class="hide signRepoDiv"><pre>';
            $this->log->steplogWrite();

            /**
             *  On se mets à la racine du repo
             *  Activation de globstar (**), cela permet à bash d'aller chercher des fichiers .rpm récursivement, peu importe le nb de sous-répertoires
             */
            if (file_exists("/usr/bin/rpmresign")) {
                //exec("shopt -s globstar && cd '${REPOS_DIR}/{$this->repo->dateFormatted}_{$this->name}' && /usr/bin/rpmresign --path '${GPGHOME}' --name '${GPG_KEYID}' --passwordfile '${PASSPHRASE_FILE}' **/*.rpm >> {$this->log->steplog} 2>&1", $output, $result);
                $process = proc_open("shopt -s globstar && cd '${REPOS_DIR}/{$this->repo->dateFormatted}_{$this->repo->name}' && /usr/bin/rpmresign --path '${GPGHOME}' --name '${GPG_KEYID}' --passwordfile '${PASSPHRASE_FILE}' **/*.rpm 1>&2", $descriptors, $pipes);
            } //else { utilisation de rpm-sign (ne fonctionne pas car affiche un prompt pour demander la passphrase)
            //exec("shopt -s globstar && cd '${REPOS_DIR}/{$this->repo->dateFormatted}_{$this->name}' && rpmsign --addsign **/*.rpm >> {$this->log->steplog} 2>&1", $output, $result);	// Sinon on utilise rpmsign et on demande le mdp à l'utilisateur (pas possible d'utiliser un fichier passphrase)

            /**
             *  Récupération du pid et du status du process lancé
             *  Puis écriture du pid de reposync/debmirror (lancé par proc_open) dans le fichier PID principal, ceci afin qu'il puisse être killé si l'utilisateur le souhaites
             */
            $proc_details = proc_get_status($process);
            file_put_contents("${PID_DIR}/{$this->log->pid}.pid", "SUBPID=\"".$proc_details['pid']."\"".PHP_EOL, FILE_APPEND);

            /**
             *  Tant que le process (lancé par proc_open) n'est pas terminé, on boucle afin de ne pas continuer les étapes suivantes
             */
            do {
                $status = proc_get_status($process);
                // If our stderr pipe has data, grab it for use later.
                if (!feof($pipes[2])) {
    
                    // We're acting like passthru would and displaying errors as they come in.
                    $error_line = fgets($pipes[2]);
                    file_put_contents($this->log->steplog, $error_line, FILE_APPEND);
                }
            } while ($status['running'] === true);

                /**
                 *  Clôture du process
                 */
                proc_close($process);
                echo '</pre></div>';

            $this->log->steplogWrite();

            /**
             *  Récupération du code d'erreur de rpmresign
             */
            $return = $status['exitcode'];

            /**
             *  Il y a un pb avec rpmresign, celui-ci renvoie systématiquement le code 0 même si il est en erreur. 
             *  Du coup on vérifie directement dans l'output du programme qu'il n'y a pas eu de message d'erreur et si c'est le cas alors on incrémente $return
             */
            if (preg_match('/gpg: signing failed/', file_get_contents($this->log->steplog))) ++$return;
            if (preg_match('/No secret key/', file_get_contents($this->log->steplog))) ++$return;
            if (preg_match('/error: gpg/', file_get_contents($this->log->steplog))) ++$return;
            if (preg_match("/Can't resign/", file_get_contents($this->log->steplog))) ++$return;

            /**
             *  Cas particulier, on affichera un warning si le message suivant a été détecté dans les logs
             */
            if (preg_match("/gpg: WARNING:/", file_get_contents($this->log->steplog))) ++$warning;

            if ($warning != 0) {
                $this->log-> steplogWarning();
            }

            if ($return != 0) {
                /**
                 *  Si l'action est reconstruct alors on ne supprime pas ce qui a été fait (sinon ça supprime le repo!)
                 */
                if ($this->action != "reconstruct") {
                    /**
                     *  Suppression de ce qui a été fait :
                     */
                    exec("rm -rf '${REPOS_DIR}/{$this->repo->dateFormatted}_{$this->repo->name}'");
                }

                throw new Exception('la signature des paquets a échouée');
            }

            $this->log->steplogOK();
        }

        return true;
    }
}
?>