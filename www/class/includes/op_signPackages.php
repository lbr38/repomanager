<?php
trait op_signPackages {
    /**
    *   Signature des paquets (Redhat) ou du repo (Debian) avec GPG
    */
    public function op_signPackages() {
        global $OS_FAMILY;
        global $DATE_DMY;
        global $REPOS_DIR;
        global $GPGHOME;
        global $WWW_HOSTNAME;
        global $GPG_KEYID;
        global $PASSPHRASE_FILE;
        global $PID_DIR;

        ob_start();

        // Signature des paquets/du repo avec GPG
        // Si c'est Redhat/Centos on resigne les paquets
        // Si c'est Debian on signe le repo (Release.gpg)
        if ($this->repo->signed == "yes" OR $this->repo->gpgResign == "yes") {
            $descriptors = array(
                0 => array('file', 'php://stdin', 'r'),
                1 => array('file', 'php://stdout', 'w'),
                2 => array('pipe', 'w')
            );

            if ($OS_FAMILY == "Redhat") {
                echo '<br>Signature des paquets (GPG) ';
                echo "<span class=\"signPackagesLoading_{$this->log->pid}\">en cours<img src=\"images/loading.gif\" class=\"icon\" /></span><span class=\"signPackagesOK_{$this->log->pid} greentext hide\">✔</span><span class=\"signPackagesKO_{$this->log->pid} redtext hide\">✕</span>";
                echo '<div class="hide signRepoDiv"><pre>';
                
                $this->logcontent = ob_get_clean(); file_put_contents($this->log->steplog, $this->logcontent, FILE_APPEND); ob_start();

                // On se mets à la racine du repo
                // Activation de globstar (**), cela permet à bash d'aller chercher des fichiers .rpm récursivement, peu importe le nb de sous-répertoires
                if (file_exists("/usr/bin/rpmresign")) {
                    //exec("shopt -s globstar && cd '${REPOS_DIR}/${DATE_DMY}_{$this->name}' && /usr/bin/rpmresign --path '${GPGHOME}' --name '${GPG_KEYID}' --passwordfile '${PASSPHRASE_FILE}' **/*.rpm >> {$this->log->steplog} 2>&1", $output, $result);
                    $process = proc_open("shopt -s globstar && cd '${REPOS_DIR}/${DATE_DMY}_{$this->repo->name}' && /usr/bin/rpmresign --path '${GPGHOME}' --name '${GPG_KEYID}' --passwordfile '${PASSPHRASE_FILE}' **/*.rpm 1>&2", $descriptors, $pipes);
                } //else { utilisation de rpm-sign (ne fonctionne pas car affiche un prompt pour demander la passphrase)
                  //exec("shopt -s globstar && cd '${REPOS_DIR}/${DATE_DMY}_{$this->name}' && rpmsign --addsign **/*.rpm >> {$this->log->steplog} 2>&1", $output, $result);	// Sinon on utilise rpmsign et on demande le mdp à l'utilisateur (pas possible d'utiliser un fichier passphrase)
            }

            if ($OS_FAMILY == "Debian") {
                // On va utiliser un répertoire temporaire pour travailler
                $TMP_DIR = "/tmp/{$this->log->pid}_deb_packages";
                mkdir($TMP_DIR, 0770, true);
                echo '<br>Signature du repo (GPG) ';
                echo "<span class=\"signPackagesLoading_{$this->log->pid}\">en cours<img src=\"images/loading.gif\" class=\"icon\" /></span><span class=\"signPackagesOK_{$this->log->pid} greentext hide\">✔</span><span class=\"signPackagesKO_{$this->log->pid} redtext hide\">✕</span>";
                echo '<div class="hide signRepoDiv"><pre>';

                $this->logcontent = ob_get_clean(); file_put_contents($this->log->steplog, $this->logcontent, FILE_APPEND); ob_start();
                
                // On se mets à la racine de la section
                // On recherche tous les paquets .deb et on les déplace dans le répertoire temporaire
                exec("cd ${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/${DATE_DMY}_{$this->repo->section}/ && find . -name '*.deb' -exec mv '{}' $TMP_DIR \;");
                // Après avoir déplacé tous les paquets on peut supprimer tout le contenu de la section
                exec("rm -rf ${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/${DATE_DMY}_{$this->repo->section}/*");
                // Création du répertoire conf et des fichiers de conf du repo
                if (!mkdir("${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/${DATE_DMY}_{$this->repo->section}/conf", 0770, true)) {
                    throw new Exception("<p><span class=\"redtext\">Erreur : </span>impossible de créer le répertoire de configuration du repo (conf)</p>");
                }
                // Création du fichier "distributions"
                if (!file_put_contents("${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/${DATE_DMY}_{$this->repo->section}/conf/distributions", "Origin: Repo {$this->repo->name} sur ${WWW_HOSTNAME}\nLabel: apt repository\nCodename: {$this->repo->dist}\nArchitectures: i386 amd64\nComponents: {$this->repo->section}\nDescription: Miroir du repo {$this->repo->name}, distribution {$this->repo->dist}, section {$this->repo->section}\nSignWith: ${GPG_KEYID}\nPull: {$this->repo->section}".PHP_EOL)) {
                    throw new Exception('<p><span class="redtext">Erreur : </span>impossible de créer le fichier de configuration du repo (distributions)</p>');
                }
                // Création du fichier "options"
                if (!file_put_contents("${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/${DATE_DMY}_{$this->repo->section}/conf/options", "basedir ${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/${DATE_DMY}_{$this->repo->section}\nask-passphrase".PHP_EOL)) {
                    throw new Exception('<p><span class="redtext">Erreur : </span>impossible de créer le fichier de configuration du repo (options)</p>');
                }
                // Création du repo en incluant les paquets deb du répertoire temporaire, et signature du fichier Release
                //exec("cd ${REPOS_DIR}/{$this->name}/{$this->dist}/${DATE_DMY}_{$this->section}/ && /usr/bin/reprepro --gnupghome ${GPGHOME} includedeb {$this->dist} ${TMP_DIR}/*.deb >> {$this->log->steplog} 2>&1", $output, $result);
                //$process = proc_open("exec /usr/bin/reprepro --basedir ${REPOS_DIR}/{$this->name}/{$this->dist}/${DATE_DMY}_{$this->section}/ --gnupghome ${GPGHOME} includedeb {$this->dist} ${TMP_DIR}/*.deb 1>&2", $descriptors, $pipes);
                $process = proc_open("for DEB_PACKAGE in ${TMP_DIR}/*.deb; do /usr/bin/reprepro --basedir ${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/${DATE_DMY}_{$this->repo->section}/ --gnupghome ${GPGHOME} includedeb {$this->repo->dist} \$DEB_PACKAGE; rm \$DEB_PACKAGE -f;done 1>&2", $descriptors, $pipes);
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
             *  Suppression du répertoire temporaire
             */
            if ($OS_FAMILY == "Debian") {
                exec("rm -rf '$TMP_DIR'");
            }

            /**
             *  Récupération du code d'erreur de reposync/debmirror
             */
            $return = $status['exitcode'];

            if ($return == 0) {
                echo '<style>';
                echo ".signPackagesLoading_{$this->log->pid} { display: none; }";
                echo ".signPackagesOK_{$this->log->pid} { display: inline-block; }";
                echo '</style>';
            } else {
                echo '<style>';
                echo ".signPackagesLoading_{$this->log->pid} { display: none; }";
                echo ".signPackagesKO_{$this->log->pid} { display: inline-block; }";
                echo '</style>';
                if ($OS_FAMILY == "Redhat") { echo '<br><span class="redtext">Erreur : </span>la signature des paquets a échouée'; }
                if ($OS_FAMILY == "Debian") { echo "<br><span class=\"redtext\">Erreur : </span>la signature de la section <b>{$this->repo->section}</b> du repo <b>{$this->repo->name}</b> a échouée"; }
                echo '<br>Suppression de ce qui a été fait : ';
                if ($OS_FAMILY == "Redhat") { exec("rm -rf '${REPOS_DIR}/${DATE_DMY}_{$this->repo->name}'"); }
                if ($OS_FAMILY == "Debian") { exec("rm -rf '${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/${DATE_DMY}_{$this->repo->section}'"); }
                echo '<span class="greentext">OK</span>';
                $this->logcontent = ob_get_clean(); file_put_contents($this->log->steplog, $this->logcontent, FILE_APPEND); ob_start();
                throw new Exception();
            }

            $this->logcontent = ob_get_clean(); file_put_contents($this->log->steplog, $this->logcontent, FILE_APPEND); ob_start();

        }
        return true;
    }
}
?>