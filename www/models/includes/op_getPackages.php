<?php
trait op_getPackages {
    /**
     *   Récupération des paquets à partir d'un repo source
     *   $op_type = new ou update en fonction de si il s'agit d'un nouveau repo oiu d'une mise à jour
     */
    public function op_getPackages($op_type) {
        global $OS_FAMILY;
        global $DATE_DMY;
        global $DATE_YMD;
        global $OS_VERSION;
        global $REPOS_DIR;
        global $DEFAULT_ENV;
        global $GPGHOME;
        global $REPOMANAGER_YUM_DIR;
        global $PID_DIR;

        ob_start();

        $this->log->steplogInitialize('getPackages');
        $this->log->steplogTitle('RÉCUPÉRATION DES PAQUETS');
        $this->log->steplogLoading();
        
        /**
         *  Le type d'opération doit être renseigné pour cette fonction (soit "new" soit "update")
         */
        if (empty($op_type)) throw new Exception('<p><span class="redtext">Erreur : </span>type d\'opération inconnu (vide)</p>');
        if ($op_type != "new" AND $op_type != "update") throw new Exception('<p><span class="redtext">Erreur : </span>type d\'opération invalide</p>');

        //// VERIFICATIONS ////

        /**
         *  1 : Récupération du type du repo :
         *  Si il s'agit d'un repo de type 'local' alors on quitte à cette étape car on ne peut pas mettre à jour ce type de repo
         */
        $this->repo->db_getType();
        if ($this->repo->type == "local") {
            if ($OS_FAMILY == "Redhat") throw new Exception("Il n'est pas possible de mettre à jour un repo local");
            if ($OS_FAMILY == "Debian") throw new Exception("Il n'est pas possible de mettre à jour une section de repo local");
        }

        /**
         *  2 : Debian seulement : Si la section est un miroir alors il faut récupérer l'URL complète de sa source si ce n'est pas déjà fait
         */
        if ($OS_FAMILY == "Debian") $this->repo->getFullSource();

        /**
         *  2. Si il s'agit d'un nouveau repo/section, on vérifie quand même que le repo/section n'existe pas déjà.
         *     Si il s'agit d'une mise à jour de repo/section on vérifie qu'il/elle existe
         * 
         *  Cas nouveau :
         */
        if ($op_type == "new") {        
            if ($OS_FAMILY == "Redhat") {
                if ($this->repo->existsEnv($this->repo->name, $DEFAULT_ENV) === true) throw new Exception("le repo <b>{$this->repo->name}</b> existe déjà en ".envtag($DEFAULT_ENV));
            }
            if ($OS_FAMILY == "Debian") {
                if ($this->repo->section_existsEnv($this->repo->name, $this->repo->dist, $this->repo->section, $DEFAULT_ENV) === true) throw new Exception("la section <b>{$this->repo->section}</b> du repo <b>{$this->repo->name}</b> existe déjà en ".envtag($DEFAULT_ENV));
            }
        }
        /**
         *  Cas mise à jour :
         */
        if ($op_type == "update") {
            /**
             *  Vérifie si le repo qu'on souhaite mettre à jour existe bien en base de données
             */
            if ($OS_FAMILY == "Redhat") {
                if ($this->repo->existsEnv($this->repo->name, $DEFAULT_ENV) === false) throw new Exception("le repo <b>{$this->repo->name}</b> ".envtag($DEFAULT_ENV)." n'existe pas");
            }
            if ($OS_FAMILY == "Debian") {
                if ($this->repo->section_existsEnv($this->repo->name, $this->repo->dist, $this->repo->section, $DEFAULT_ENV) === false) throw new Exception("la section <b>{$this->repo->section}</b> ".envtag($DEFAULT_ENV)." du repo <b>{$this->repo->name}</b> n'existe pas</p>");
            }
            /**
             *  Vérifie si le repo à mettre à jour n'existe pas déjà à la date du jour
             */
            // Désactivé : on souhaite pouvoir re-mettre à jour un repo autant qu'on le souhaite (ex : planification récurrente)
            /*if ($OS_FAMILY == "Redhat") {
                if ($this->repo->existsDateEnv($this->repo->name, $DATE_YMD, $DEFAULT_ENV) === true) throw new Exception("la repo <b>{$this->repo->name}</b> existe déjà en ".envtag($DEFAULT_ENV)." au <b>{$this->repo->dateFormatted}</b>");
            }
            if ($OS_FAMILY == "Debian") {
                if ($this->repo->section_existsDateEnv($this->repo->name, $this->repo->dist, $this->repo->section, $DATE_YMD, $DEFAULT_ENV) === true) throw new Exception("la section <b>{$this->repo->section}</b> du repo <b>{$this->repo->name}</b> existe déjà en ".envtag($DEFAULT_ENV)." au <b>{$this->repo->dateFormatted}</b>");
            }*/
        }

        $this->log->steplogWrite();

        //// TRAITEMENT ////

        /**
         *  2. Création du répertoire du repo/section
         */
        if ($OS_FAMILY == "Redhat") {
            /**
             *  Si le répertoire existe déjà, on le supprime
             */
            if (is_dir("${REPOS_DIR}/${DATE_DMY}_{$this->repo->name}")) exec("rm -rf ${REPOS_DIR}/${DATE_DMY}_{$this->repo->name}");
            if (!mkdir("${REPOS_DIR}/${DATE_DMY}_{$this->repo->name}", 0770, true)) throw new Exception("la création du répertoire <b>${REPOS_DIR}/${DATE_DMY}_{$this->repo->name}</b> a échouée");
        }
        if ($OS_FAMILY == "Debian") {
            /**
             *  Si le répertoire existe déjà, on le supprime
             */
            if (is_dir("${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/${DATE_DMY}_{$this->repo->section}")) exec("rm -rf ${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/${DATE_DMY}_{$this->repo->section}");
            if (!mkdir("${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/${DATE_DMY}_{$this->repo->section}", 0770, true)) throw new Exception("la création du répertoire <b>${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/${DATE_DMY}_{$this->repo->section}</b> a échouée");
        }

        $this->log->steplogWrite();

        /**
         *  3. Récupération des paquets
         */
        echo '<div class="hide getPackagesDiv"><pre>';
        $this->log->steplogWrite();

        // File descriptors for each subprocess. http://phptutorial.info/?proc-open
        /* $descriptors = [
            0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
            1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
            2 => array("file", "{$this->log->steplog}", "a") // stderr is a file to write to
        ];*/
        // https://gist.github.com/swichers/027d5ae903350cbd4af8
        $descriptors = array(
            // Must use php://stdin(out) in order to allow display of command output
            // and the user to interact with the process.
            0 => array('file', 'php://stdin', 'r'),
            1 => array('file', 'php://stdout', 'w'),
            2 => array('pipe', 'w'),
        );

        if ($OS_FAMILY == "Redhat") {
            /**
             *  Note : pour reposync il faut impérativement rediriger la sortie standard vers la sortie d'erreur car c'est uniquement cette dernière qui est capturée par proc_open. On fait ça pour avoir non seulement les erreurs mais aussi tout le déroulé normal de reposync.
             */
            if ($this->repo->gpgCheck == "no") {
                if ($OS_VERSION == "7") $process = proc_open("exec reposync --config=${REPOMANAGER_YUM_DIR}/repomanager.conf -l --repoid={$this->repo->source} --norepopath --download_path='${REPOS_DIR}/${DATE_DMY}_{$this->repo->name}/' 1>&2", $descriptors, $pipes);
                if ($OS_VERSION == "8") $process = proc_open("exec reposync --config=${REPOMANAGER_YUM_DIR}/repomanager.conf --nogpgcheck --repoid={$this->repo->source} --download-path '${REPOS_DIR}/${DATE_DMY}_{$this->repo->name}/' 1>&2", $descriptors, $pipes);
            } else { // Dans tous les autres cas (même si rien n'a été précisé) on active gpgcheck
                if ($OS_VERSION == "7") $process = proc_open("exec reposync --config=${REPOMANAGER_YUM_DIR}/repomanager.conf --gpgcheck -l --repoid={$this->repo->source} --norepopath --download_path='${REPOS_DIR}/${DATE_DMY}_{$this->repo->name}/' 1>&2", $descriptors, $pipes);
                if ($OS_VERSION == "8") $process = proc_open("exec reposync --config=${REPOMANAGER_YUM_DIR}/repomanager.conf --repoid={$this->repo->source} --download-path '${REPOS_DIR}/${DATE_DMY}_{$this->repo->name}/' 1>&2", $descriptors, $pipes);
            }
        }

        if ($OS_FAMILY == "Debian") {
            /**
             *  Dans le cas où on a précisé de ne pas vérifier les signatures GPG
             */
            if ($this->repo->gpgCheck == "no") {           
                $process = proc_open("exec /usr/bin/debmirror --no-check-gpg --nosource --passive --method=http --rsync-extra=none --root={$this->repo->rootUrl} --dist={$this->repo->dist} --host={$this->repo->hostUrl} --section={$this->repo->section} --arch=amd64 ${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/${DATE_DMY}_{$this->repo->section} --getcontents --ignore-release-gpg --progress --i18n --include='Translation-fr.*\.bz2' --postcleanup", $descriptors, $pipes);
            
            /**
             *  Dans tous les autres cas (même si rien n'a été précisé)
             */
            } else {
                $process = proc_open("exec /usr/bin/debmirror --check-gpg --keyring=${GPGHOME}/trustedkeys.gpg --nosource --passive --method=http --rsync-extra=none --root={$this->repo->rootUrl} --dist={$this->repo->dist} --host={$this->repo->hostUrl} --section={$this->repo->section} --arch=amd64 ${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/${DATE_DMY}_{$this->repo->section} --getcontents --ignore-release-gpg --progress --i18n --include='Translation-fr.*\.bz2' --postcleanup", $descriptors, $pipes);
            }
        }

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
         *  Récupération du code d'erreur de reposync/debmirror
         */
        $return = $status['exitcode'];
       
        if ($return != 0) {
            /**
             *  Suppression de ce qui a été fait :
             */
            if ($OS_FAMILY == "Redhat") exec("rm -rf '${REPOS_DIR}/${DATE_DMY}_{$this->repo->name}'");
            if ($OS_FAMILY == "Debian") exec("rm -rf '${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/${DATE_DMY}_{$this->repo->section}'");

            throw new Exception('erreur lors de la récupération des paquets');
        }

        $this->log->steplogOK();

        return true;
    }
}
?>