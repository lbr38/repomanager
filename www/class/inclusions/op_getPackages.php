<?php
trait op_getPackages {
/**
     *   Récupération des paquets à partir d'un repo source
     *    $op_type = new ou update en fonction de si il s'agit d'un nouveau repo oiu d'une mise à jour
     */
    public function op_getPackages($op_type) {
        global $OS_FAMILY;
        global $DATE_JMA;
        global $OS_VERSION;
        global $REPOS_DIR;
        global $DEFAULT_ENV;
        global $GPGHOME;
        global $REPOMANAGER_YUM_DIR;
        global $TEMP_DIR;

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
                if ($this->existsEnv($this->name, $DEFAULT_ENV) === true) {
                    throw new Exception("<p><span class=\"redtext\">Erreur : </span>le repo <b>$this->name</b> existe déjà en <b>${DEFAULT_ENV}</b></p>");
                }
            }
            if ($OS_FAMILY == "Debian") {
                if ($this->section_existsEnv($this->name, $this->dist, $this->section, $DEFAULT_ENV) === true) {
                    throw new Exception("<p><span class=\"redtext\">Erreur : </span>la section <b>$this->section</b> du repo <b>$this->name</b> existe déjà en <b>${DEFAULT_ENV}</b></p>");
                }
            }
        }
        // Cas update
        if ($op_type == "update") {
            // Vérifie si le repo qu'on souhaite mettre à jour existe bien
            if ($OS_FAMILY == "Redhat") {
                if ($this->existsEnv($this->name, $DEFAULT_ENV) === false) {
                    throw new Exception("<p><span class=\"redtext\">Erreur : </span>le repo <b>$this->name</b> n'existe pas</p>");
                }
            }
            if ($OS_FAMILY == "Debian") {
                if ($this->section_existsEnv($this->name, $this->dist, $this->section, $DEFAULT_ENV) === false) {
                    throw new Exception("<p><span class=\"redtext\">Erreur : </span>la section <b>$this->section</b> du repo <b>$this->name</b> n'existe pas</p>");
                }
            }
            // Vérifie si le repo à mettre à jour n'existe pas déjà à la date du jour
            if ($OS_FAMILY == "Redhat") {
                if ($this->existsDateEnv($this->name, $this->date, $DEFAULT_ENV) === true) {
                    throw new Exception("<p><span class=\"redtext\">Erreur : </span>la section <b>$this->section</b> du repo <b>$this->name</b> existe déjà en <b>${DEFAULT_ENV}</b></p>");
                }
            }
            if ($OS_FAMILY == "Debian") {
                if ($this->section_existsDateEnv($this->name, $this->dist, $this->section, $this->date, $DEFAULT_ENV) === true) {
                    throw new Exception("<p><span class=\"redtext\">Erreur : </span>la section <b>$this->section</b> du repo <b>$this->name</b> existe déjà en <b>${DEFAULT_ENV}</b></p>");
                }
            }
        }

        $this->logcontent = ob_get_clean(); file_put_contents($this->log->steplog, $this->logcontent, FILE_APPEND); ob_start();

        //// TRAITEMENT ////

        // Création du répertoire du repo/section
        if ($OS_FAMILY == "Redhat") {
            if (is_dir("${REPOS_DIR}/${DATE_JMA}_{$this->name}")) {
                throw new Exception("<p><span class=\"redtext\">Erreur : </span>le répertoire <b>${REPOS_DIR}/${DATE_JMA}_{$this->name}</b> existe déjà</p>");
            }

            if (!mkdir("${REPOS_DIR}/${DATE_JMA}_{$this->name}", 0770, true)) {
                throw new Exception("<p><span class=\"redtext\">Erreur : </span>la création du répertoire <b>${REPOS_DIR}/${DATE_JMA}_{$this->name}</b> a échouée</p>");
            }
        }

        if ($OS_FAMILY == "Debian") {
            if (is_dir("${REPOS_DIR}/{$this->name}/{$this->dist}/${DATE_JMA}_{$this->section}")) {
                throw new Exception("<p><span class=\"redtext\">Erreur : </span>le répertoire <b>${REPOS_DIR}/{$this->name}/{$this->dist}/${DATE_JMA}_{$this->section}</b> existe déjà</p>");
            }

            if (!mkdir("${REPOS_DIR}/{$this->name}/{$this->dist}/${DATE_JMA}_{$this->section}", 0770, true)) {
                throw new Exception("<p><span class=\"redtext\">Erreur : </span>la création du répertoire <b>${REPOS_DIR}/{$this->name}/{$this->dist}/${DATE_JMA}_{$this->section}</b> a échouée</p>");
            }
        }

        $this->logcontent = ob_get_clean(); file_put_contents($this->log->steplog, $this->logcontent, FILE_APPEND); ob_start();

        // Récupération des paquets
        echo '<br>Récupération des paquets ';
        echo '<span class="getPackagesLoading">en cours<img src="images/loading.gif" class="icon" /></span><span class="getPackagesOK greentext hide">✔</span><span class="getPackagesKO redtext hide">✕</span>';
        echo '<div class="hide getPackagesDiv"><pre>';
        $this->logcontent = ob_get_clean(); file_put_contents($this->log->steplog, $this->logcontent, FILE_APPEND); ob_start();
        if ($OS_FAMILY == "Redhat") {
            if ($this->gpgCheck == "no") {
                if ($OS_VERSION == "7") {
                    exec("cd '${REPOS_DIR}/${DATE_JMA}_{$this->name}' && reposync --config=${REPOMANAGER_YUM_DIR}/repomanager.conf -l --repoid={$this->source} --norepopath --download_path='${REPOS_DIR}/${DATE_JMA}_{$this->name}/' >> {$this->log->steplog}", $output, $result);
                }
                if ($OS_VERSION == "8") {
                    exec("cd '${REPOS_DIR}/${DATE_JMA}_{$this->name}' && reposync --config=${REPOMANAGER_YUM_DIR}/repomanager.conf --nogpgcheck --repoid={$this->source} --download-path '${REPOS_DIR}/${DATE_JMA}_{$this->name}/' >> {$this->log->steplog}", $output, $result);
                }
            } else { // Dans tous les autres cas (même si rien n'a été précisé) on active gpgcheck
                if ($OS_VERSION == "7") {
                    exec("cd '${REPOS_DIR}/${DATE_JMA}_{$this->name}' && reposync --config=${REPOMANAGER_YUM_DIR}/repomanager.conf --gpgcheck -l --repoid={$this->source} --norepopath --download_path='${REPOS_DIR}/${DATE_JMA}_{$this->name}/' >> {$this->log->steplog}", $output, $result);
                }
                if ($OS_VERSION == "8") {
                    exec("cd '${REPOS_DIR}/${DATE_JMA}_{$this->name}' && reposync --config=${REPOMANAGER_YUM_DIR}/repomanager.conf --repoid={$this->source} --download-path '${REPOS_DIR}/${DATE_JMA}_{$this->name}/' >> {$this->log->steplog}", $output, $result);
                }
            }
            echo '</pre></div>';
            
            $this->logcontent = ob_get_clean(); file_put_contents($this->log->steplog, $this->logcontent, FILE_APPEND); ob_start();

            if ($result == 0) {
                echo '<style>';
                echo '.getPackagesLoading { display: none; }';
                echo '.getPackagesOK { display: inline-block; }';
                echo '</style>';
            } else {
                echo '<style>';
                echo '.getPackagesLoading { display: none; }';
                echo '.getPackagesKO { display: inline-block; }';
                echo '</style>';
                echo '<br><span class="redtext">Erreur : </span>reposync a rencontré un problème lors de la création du miroir';
                echo '<br>Suppression de ce qui a été fait : ';
                exec("rm -rf '${REPOS_DIR}/${DATE_JMA}_{$this->name}'");
                echo '<span class="greentext">OK</span>';
                $this->logcontent = ob_get_clean(); file_put_contents($this->log->steplog, $this->logcontent, FILE_APPEND); ob_start();
                throw new Exception();
            }
            $this->logcontent = ob_get_clean(); file_put_contents($this->log->steplog, $this->logcontent, FILE_APPEND); ob_start();
        }

        if ($OS_FAMILY == "Debian") {
            // Dans le cas où on a précisé de ne pas vérifier les signatures GPG :
            if ($this->gpgCheck == "no") {
                exec("/usr/bin/debmirror --no-check-gpg --nosource --passive --method=http --rsync-extra=none --root={$this->rootUrl} --dist={$this->dist} --host={$this->hostUrl} --section={$this->section} --arch=amd64 ${REPOS_DIR}/{$this->name}/{$this->dist}/${DATE_JMA}_{$this->section} --getcontents --ignore-release-gpg --progress --i18n --include='Translation-fr.*\.bz2' --postcleanup >> {$this->log->steplog}", $output, $result);
            } else { // Dans tous les autres cas (même si rien n'a été précisé)
                exec("/usr/bin/debmirror --check-gpg --keyring=${GPGHOME}/trustedkeys.gpg --nosource --passive --method=http --rsync-extra=none --root={$this->rootUrl} --dist={$this->dist} --host={$this->hostUrl} --section={$this->section} --arch=amd64 ${REPOS_DIR}/{$this->name}/{$this->dist}/${DATE_JMA}_{$this->section} --getcontents --ignore-release-gpg --progress --i18n --include='Translation-fr.*\.bz2' --postcleanup >> {$this->log->steplog}", $output, $result);
            }
            echo '</pre></div>';

            $this->logcontent = ob_get_clean(); file_put_contents($this->log->steplog, $this->logcontent, FILE_APPEND); ob_start();

            if ($result == 0) {
                echo '<style>';
                echo '.getPackagesLoading { display: none; }';
                echo '.getPackagesOK { display: inline-block; }';
                echo '</style>';
            } else {
                echo '<style>';
                echo '.getPackagesLoading { display: none; }';
                echo '.getPackagesKO { display: inline-block; }';
                echo '</style>';
                echo '<br><span class="redtext">Erreur : </span>debmirror a rencontré un problème lors de la création du miroir';
                echo '<br>Suppression de ce qui a été fait : ';
                exec("rm -rf '${REPOS_DIR}/{$this->name}/{$this->dist}/${DATE_JMA}_{$this->section}'");
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