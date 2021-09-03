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

        if (empty($op_type)) {
            throw new Exception('<p><span class="redtext">Erreur : </span>type d\'opération inconnu (vide)</p>');
        }
        if ($op_type != "new" AND $op_type != "update") {
            throw new Exception('<p><span class="redtext">Erreur : </span>type d\'opération invalide</p>');
        }

        //// VERIFICATIONS ////

        /**
         *  1. Si il s'agit d'un nouveau repo/section, on vérifie quand même que le repo/section n'existe pas déjà.
         *     Si il s'agit d'une mise à jour de repo/section on vérifie qu'il/elle existe
         */
        // Cas new
        if ($op_type == "new") {        
            if ($OS_FAMILY == "Redhat") {
                if ($this->repo->existsEnv($this->repo->name, $DEFAULT_ENV) === true) {
                    throw new Exception("<p><span class=\"redtext\">Erreur : </span>le repo <b>{$this->repo->name}</b> existe déjà en ".entag($DEFAULT_ENV)."</p>");
                }
            }
            if ($OS_FAMILY == "Debian") {
                if ($this->repo->section_existsEnv($this->repo->name, $this->repo->dist, $this->repo->section, $DEFAULT_ENV) === true) {
                    throw new Exception("<p><span class=\"redtext\">Erreur : </span>la section <b>{$this->repo->section}</b> du repo <b>{$this->repo->name}</b> existe déjà en ".entag($DEFAULT_ENV)."</p>");
                }
            }
        }
        // Cas update
        if ($op_type == "update") {
            /**
             *  Vérifie si le repo qu'on souhaite mettre à jour existe bien
             */
            if ($OS_FAMILY == "Redhat") {
                if ($this->repo->existsEnv($this->repo->name, $DEFAULT_ENV) === false) {
                    throw new Exception("<p><span class=\"redtext\">Erreur : </span>le repo <b>{$this->repo->name}</b> ".entag($DEFAULT_ENV)." n'existe pas</p>");
                }
            }
            if ($OS_FAMILY == "Debian") {
                if ($this->repo->section_existsEnv($this->repo->name, $this->repo->dist, $this->repo->section, $DEFAULT_ENV) === false) {
                    throw new Exception("<p><span class=\"redtext\">Erreur : </span>la section <b>{$this->repo->section}</b> ".entag($DEFAULT_ENV)." du repo <b>{$this->repo->name}</b> n'existe pas</p>");
                }
            }
            /**
             *  Vérifie si le repo à mettre à jour n'existe pas déjà à la date du jour
             */
            if ($OS_FAMILY == "Redhat") {
                if ($this->repo->existsDateEnv($this->repo->name, $DATE_YMD, $DEFAULT_ENV) === true) {
                    throw new Exception("<p><span class=\"redtext\">Erreur : </span>la repo <b>{$this->repo->name}</b> existe déjà en ".entag($DEFAULT_ENV)." au <b>{$this->repo->dateFormatted}</b></p>");
                }
            }
            if ($OS_FAMILY == "Debian") {
                if ($this->repo->section_existsDateEnv($this->repo->name, $this->repo->dist, $this->repo->section, $DATE_YMD, $DEFAULT_ENV) === true) {
                    throw new Exception("<p><span class=\"redtext\">Erreur : </span>la section <b>{$this->repo->section}</b> du repo <b>{$this->repo->name}</b> existe déjà en ".entag($DEFAULT_ENV)." au <b>{$this->repo->dateFormatted}</b></p>");
                }
            }
        }

        $this->logcontent = ob_get_clean(); file_put_contents($this->log->steplog, $this->logcontent, FILE_APPEND); ob_start();

        //// TRAITEMENT ////

        /**
         *  2. Création du répertoire du repo/section
         */
        if ($OS_FAMILY == "Redhat") {
            // Si le répertoire existe déjà, on le supprime
            if (is_dir("${REPOS_DIR}/${DATE_DMY}_{$this->repo->name}")) {
                exec("rm -rf ${REPOS_DIR}/${DATE_DMY}_{$this->repo->name}");
            }

            if (!mkdir("${REPOS_DIR}/${DATE_DMY}_{$this->repo->name}", 0770, true)) {
                throw new Exception("<p><span class=\"redtext\">Erreur : </span>la création du répertoire <b>${REPOS_DIR}/${DATE_DMY}_{$this->repo->name}</b> a échouée</p>");
            }
        }
        if ($OS_FAMILY == "Debian") {
            // Si le répertoire existe déjà, on le supprime
            if (is_dir("${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/${DATE_DMY}_{$this->repo->section}")) {
                exec("rm -rf ${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/${DATE_DMY}_{$this->repo->section}");
            }

            if (!mkdir("${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/${DATE_DMY}_{$this->repo->section}", 0770, true)) {
                throw new Exception("<p><span class=\"redtext\">Erreur : </span>la création du répertoire <b>${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/${DATE_DMY}_{$this->repo->section}</b> a échouée</p>");
            }
        }

        $this->logcontent = ob_get_clean(); file_put_contents($this->log->steplog, $this->logcontent, FILE_APPEND); ob_start();

        /**
         *  3. Récupération des paquets
         */
        echo '<br>Récupération des paquets ';
        echo "<span class=\"getPackagesLoading_{$this->log->pid} baseline\">en cours<img src=\"images/loading.gif\" class=\"icon\" /></span><span class=\"getPackagesOK_{$this->log->pid} greentext baseline hide\">✔</span><span class=\"getPackagesKO_{$this->log->pid} redtext baseline hide\">✕</span>";
        echo '<div class="hide getPackagesDiv"><pre>';
        $this->logcontent = ob_get_clean(); file_put_contents($this->log->steplog, $this->logcontent, FILE_APPEND); ob_start();

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
                if ($OS_VERSION == "7") {
                    $process = proc_open("exec reposync --config=${REPOMANAGER_YUM_DIR}/repomanager.conf -l --repoid={$this->repo->source} --norepopath --download_path='${REPOS_DIR}/${DATE_DMY}_{$this->repo->name}/' 1>&2", $descriptors, $pipes);
                }
                if ($OS_VERSION == "8") {
                    $process = proc_open("exec reposync --config=${REPOMANAGER_YUM_DIR}/repomanager.conf --nogpgcheck --repoid={$this->repo->source} --download-path '${REPOS_DIR}/${DATE_DMY}_{$this->repo->name}/' 1>&2", $descriptors, $pipes);
                }
            } else { // Dans tous les autres cas (même si rien n'a été précisé) on active gpgcheck
                if ($OS_VERSION == "7") {
                    $process = proc_open("exec reposync --config=${REPOMANAGER_YUM_DIR}/repomanager.conf --gpgcheck -l --repoid={$this->repo->source} --norepopath --download_path='${REPOS_DIR}/${DATE_DMY}_{$this->repo->name}/' 1>&2", $descriptors, $pipes);
                }
                if ($OS_VERSION == "8") {
                    $process = proc_open("exec reposync --config=${REPOMANAGER_YUM_DIR}/repomanager.conf --repoid={$this->repo->source} --download-path '${REPOS_DIR}/${DATE_DMY}_{$this->repo->name}/' 1>&2", $descriptors, $pipes);
                }
            }
        }

        if ($OS_FAMILY == "Debian") {
            // Dans le cas où on a précisé de ne pas vérifier les signatures GPG :
            if ($this->repo->gpgCheck == "no") {
                // à conserver (ancienne méthode) :
                //exec("/usr/bin/debmirror --no-check-gpg --nosource --passive --method=http --rsync-extra=none --root={$this->rootUrl} --dist={$this->dist} --host={$this->hostUrl} --section={$this->section} --arch=amd64 ${REPOS_DIR}/{$this->name}/{$this->dist}/${DATE_DMY}_{$this->section} --getcontents --ignore-release-gpg --progress --i18n --include='Translation-fr.*\.bz2' --postcleanup >> {$this->log->steplog}", $output, $result);
                $process = proc_open("exec /usr/bin/debmirror --no-check-gpg --nosource --passive --method=http --rsync-extra=none --root={$this->repo->rootUrl} --dist={$this->repo->dist} --host={$this->repo->hostUrl} --section={$this->repo->section} --arch=amd64 ${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/${DATE_DMY}_{$this->repo->section} --getcontents --ignore-release-gpg --progress --i18n --include='Translation-fr.*\.bz2' --postcleanup", $descriptors, $pipes);
            } else { // Dans tous les autres cas (même si rien n'a été précisé)
                // à conserver (ancienne méthode) :
                //exec("/usr/bin/debmirror --check-gpg --keyring=${GPGHOME}/trustedkeys.gpg --nosource --passive --method=http --rsync-extra=none --root={$this->rootUrl} --dist={$this->dist} --host={$this->hostUrl} --section={$this->section} --arch=amd64 ${REPOS_DIR}/{$this->name}/{$this->dist}/${DATE_DMY}_{$this->section} --getcontents --ignore-release-gpg --progress --i18n --include='Translation-fr.*\.bz2' --postcleanup >> {$this->log->steplog}", $output, $result);
                $process = proc_open("exec /usr/bin/debmirror --check-gpg --keyring=${GPGHOME}/trustedkeys.gpg --nosource --passive --method=http --rsync-extra=none --root={$this->repo->rootUrl} --dist={$this->repo->dist} --host={$this->repo->hostUrl} --section={$this->repo->section} --arch=amd64 ${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/${DATE_DMY}_{$this->repo->section} --getcontents --ignore-release-gpg --progress --i18n --include='Translation-fr.*\.bz2' --postcleanup", $descriptors, $pipes);
            }
        }

        // Récupération du pid et du status du process lancé
        $proc_details = proc_get_status($process);
        // Ecriture du pid de reposync/debmirror (lancé par proc_open) dans le fichier PID principal, ceci afin qu'il puisse être killé si l'utilisateur le souhaites
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
        $this->logcontent = ob_get_clean(); file_put_contents($this->log->steplog, $this->logcontent, FILE_APPEND); ob_start();
        
        /**
         *  Récupération du code d'erreur de reposync/debmirror
         */
        $return = $status['exitcode'];

        if ($return == 0) {
            echo '<style>';
            echo ".getPackagesLoading_{$this->log->pid} { display: none; }";
            echo ".getPackagesOK_{$this->log->pid} { display: inline-block; }";
            echo '</style>';
        } else {
            echo '<style>';
            echo ".getPackagesLoading_{$this->log->pid} { display: none; }";
            echo ".getPackagesKO_{$this->log->pid} { display: inline-block; }";
            echo '</style>';
            echo '<br><span class="redtext">Erreur : </span>pendant la création du miroir';
            echo '<br>Suppression de ce qui a été fait : ';
            if ($OS_FAMILY == "Redhat") { exec("rm -rf '${REPOS_DIR}/${DATE_DMY}_{$this->name}'"); }
            if ($OS_FAMILY == "Debian") { exec("rm -rf '${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/${DATE_DMY}_{$this->repo->section}'"); }
            echo '<span class="greentext">OK</span>';
            $this->logcontent = ob_get_clean(); file_put_contents($this->log->steplog, $this->logcontent, FILE_APPEND); ob_start();
            throw new Exception();
        }
        $this->logcontent = ob_get_clean(); file_put_contents($this->log->steplog, $this->logcontent, FILE_APPEND); ob_start();

        return true;
    }
}
?>