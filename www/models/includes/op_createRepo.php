<?php
trait op_createRepo {
    /**
     *  Création des metadata du repo (Redhat) et des liens symboliques (environnements)
     */
    public function op_createRepo() {

        ob_start();

        $this->log->steplogInitialize('createRepo');
        $this->log->steplogTitle('CRÉATION DU REPO');
        $this->log->steplogLoading();

        echo '<div class="hide createRepoDiv"><pre>';

        $this->log->steplogWrite();

        if (OS_FAMILY == "Redhat") {
            /**
             *  Si un répertoire my_uploaded_packages existe, alors on déplace ses éventuels packages
             */
            if (is_dir(REPOS_DIR."/{$this->repo->dateFormatted}_{$this->repo->name}/my_uploaded_packages/")) {
                /**
                 *  Création du répertoire my_integrated_packages qui intègrera les paquets intégrés au repo
                 */
                if (!is_dir(REPOS_DIR."/{$this->repo->dateFormatted}_{$this->repo->name}/my_integrated_packages/")) mkdir(REPOS_DIR."/{$this->repo->dateFormatted}_{$this->repo->name}/my_integrated_packages", 0770, true);

                /**
                 *  Déplacement des paquets dans my_uploaded_packages vers my_integrated_packages
                 */
                if (!Common::dir_is_empty(REPOS_DIR."/{$this->repo->dateFormatted}_{$this->repo->name}/my_uploaded_packages/")) {
                    exec("mv -f ".REPOS_DIR."/{$this->repo->dateFormatted}_{$this->repo->name}/my_uploaded_packages/*.rpm ".REPOS_DIR."/{$this->repo->dateFormatted}_{$this->repo->name}/my_integrated_packages/");
                }

                /**
                 *  Suppression de my_uploaded_packages
                 */
                rmdir(REPOS_DIR."/{$this->repo->dateFormatted}_{$this->repo->name}/my_uploaded_packages/");
            }

            exec("createrepo -v ".REPOS_DIR."/{$this->repo->dateFormatted}_{$this->repo->name}/ 1>&2 >> {$this->log->steplog}", $output, $return);
            echo '</pre></div>';

            $this->log->steplogWrite();
        }

        if (OS_FAMILY == "Debian") {
            $descriptors = array(
                0 => array('file', 'php://stdin', 'r'),
                1 => array('file', 'php://stdout', 'w'),
                2 => array('pipe', 'w')
            );

            /**
             *  On va créer et utiliser un répertoire temporaire pour travailler
             */
            $TMP_DIR = "/tmp/{$this->log->pid}_deb_packages";
            if (!mkdir($TMP_DIR, 0770, true)) throw new Exception("impossible de créer le répertoire temporaire /tmp/{$this->log->pid}_deb_packages");

            $this->log->steplogWrite();
            
            /**
             *  On se mets à la racine de la section
             *  On recherche tous les paquets .deb et on les déplace dans le répertoire temporaire
             */
            $sectionPath = REPOS_DIR."/{$this->repo->name}/{$this->repo->dist}/{$this->repo->dateFormatted}_{$this->repo->section}";
            if (!is_dir($sectionPath)) throw new Exception("le répertoire du repo n'existe pas");
            if (!is_dir($TMP_DIR)) throw new Exception("le répertoire temporaire n'existe pas");
            exec("find $sectionPath/ -name '*.deb' -exec mv '{}' ${TMP_DIR}/ \;");          

            /**
             *  Après avoir déplacé tous les paquets on peut supprimer tout le contenu de la section
             */
            exec("rm -rf $sectionPath/*");

            /**
             *  Création du répertoire 'conf' et des fichiers de conf du repo
             */
            if (!is_dir($sectionPath."/conf")) {
                if (!mkdir($sectionPath."/conf", 0770, true)) throw new Exception("impossible de créer le répertoire de configuration du repo (conf)");
            }

            /**
             *  Création du fichier "distributions"
             *  Son contenu sera différent suivant si on a choisi de chiffrer ou non le repo
             */
            if ($this->repo->signed == "yes" OR $this->repo->gpgResign == "yes")
                $file_distributions_content = "Origin: Repo {$this->repo->name} sur ".WWW_HOSTNAME."\nLabel: apt repository\nCodename: {$this->repo->dist}\nArchitectures: i386 amd64\nComponents: {$this->repo->section}\nDescription: Repo {$this->repo->name}, miroir du repo {$this->repo->source}, distribution {$this->repo->dist}, section {$this->repo->section}\nSignWith: ".GPG_KEYID."\nPull: {$this->repo->section}";
            else
                $file_distributions_content = "Origin: Repo {$this->repo->name} sur ".WWW_HOSTNAME."\nLabel: apt repository\nCodename: {$this->repo->dist}\nArchitectures: i386 amd64\nComponents: {$this->repo->section}\nDescription: Repo {$this->repo->name}, miroir du repo {$this->repo->source}, distribution {$this->repo->dist}, section {$this->repo->section}\nPull: {$this->repo->section}";

            if (!file_put_contents($sectionPath."/conf/distributions", "$file_distributions_content".PHP_EOL)) {
                throw new Exception('impossible de créer le fichier de configuration du repo (distributions)');
            }

            /**
             *  Création du fichier "options"
             *  Son contenu sera différent suivant si on a choisi de chiffrer ou non le repo
             */
            if ($this->repo->signed == "yes" OR $this->repo->gpgResign == "yes")
                $file_options_content = "basedir $sectionPath\nask-passphrase";
            else 
                $file_options_content = "basedir $sectionPath";

            if (!file_put_contents($sectionPath."/conf/options", "$file_options_content".PHP_EOL)) {
                throw new Exception('impossible de créer le fichier de configuration du repo (options)');
            }

            /**
             *  Si le répertoire temporaire ne contient aucun paquet (càd si le repo est vide) alors on ne traite pas et on incrémente $return afin d'afficher une erreur.
             */
            if (Common::dir_is_empty($TMP_DIR) === true) {
                echo "Il n'y a aucun paquets dans ce repo";
                echo '</pre></div>';

                $return = 1;

            /**
             *  Sinon on peut traiter
             */
            } else {

                /**
                 *  Création du repo en incluant les paquets deb du répertoire temporaire, et signature du fichier Release
                 */
                if ($this->repo->signed == "yes" OR $this->repo->gpgResign == "yes") {
                    $process = proc_open("for DEB_PACKAGE in ${TMP_DIR}/*.deb; do /usr/bin/reprepro --basedir $sectionPath/ --gnupghome ".GPGHOME." includedeb {$this->repo->dist} \$DEB_PACKAGE; rm \$DEB_PACKAGE -f;done 1>&2", $descriptors, $pipes);
                } else {
                    $process = proc_open("for DEB_PACKAGE in ${TMP_DIR}/*.deb; do /usr/bin/reprepro --basedir $sectionPath/ includedeb {$this->repo->dist} \$DEB_PACKAGE; rm \$DEB_PACKAGE -f;done 1>&2", $descriptors, $pipes);                
                }
            
                /**
                 *  Récupération du pid et du status du process lancé
                 *  Ecriture du pid de reposync/debmirror (lancé par proc_open) dans le fichier PID principal, ceci afin qu'il puisse être killé si l'utilisateur le souhaites
                 */
                $proc_details = proc_get_status($process);
                file_put_contents(PID_DIR."/{$this->log->pid}.pid", "SUBPID=\"".$proc_details['pid']."\"".PHP_EOL, FILE_APPEND);

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
                 *  Suppression du répertoire temporaire
                 */
                if (OS_FAMILY == "Debian" AND is_dir($TMP_DIR)) exec("rm -rf '$TMP_DIR'");

                /**
                 *  Récupération du code d'erreur de reprepro
                 */
                $return = $status['exitcode'];
            }
        }

        if ($return != 0) {
            /**
             *  Suppression de ce qui a été fait :
             */
            if ($this->action != "reconstruct") {
                if (OS_FAMILY == "Redhat") exec("rm -rf '".REPOS_DIR."/{$this->repo->dateFormatted}_{$this->repo->name}'");
                if (OS_FAMILY == "Debian") exec("rm -rf '".REPOS_DIR."/{$this->repo->name}/{$this->repo->dist}/{$this->repo->dateFormatted}_{$this->repo->section}'"); 
            }

            throw new Exception('la création du repo a échouée');
        }

        $this->log->steplogWrite();

        /**
         *  Création du lien symbolique (environnement)
         */
        if (OS_FAMILY == "Redhat") exec("cd ".REPOS_DIR."/ && ln -sfn {$this->repo->dateFormatted}_{$this->repo->name}/ {$this->repo->name}_".DEFAULT_ENV."", $output, $result);
        if (OS_FAMILY == "Debian") exec("cd ".REPOS_DIR."/{$this->repo->name}/{$this->repo->dist}/ && ln -sfn {$this->repo->dateFormatted}_{$this->repo->section}/ {$this->repo->section}_".DEFAULT_ENV."", $output, $result);
        if ($result != 0) throw new Exception('la finalisation du repo a échouée');

        $this->log->steplogOK();

        return true;
    }
}
?>