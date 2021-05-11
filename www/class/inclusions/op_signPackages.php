<?php
trait op_signPackages {
/**
    *   Signature des paquets (Redhat) ou du repo (Debian) avec GPG
    */
    public function op_signPackages() {
        global $OS_FAMILY;
        global $DATE_JMA;
        global $REPOS_DIR;
        global $GPGHOME;
        global $WWW_HOSTNAME;
        global $GPG_KEYID;
        global $PASSPHRASE_FILE;
        global $TEMP_DIR;

        ob_start();

        // Signature des paquets/du repo avec GPG
        // Si c'est Redhat/Centos on resigne les paquets
        // Si c'est Debian on signe le repo (Release.gpg)
        if ($this->signed == "yes" OR $this->gpgResign == "yes") {
            if ($OS_FAMILY == "Redhat") {
                echo '<br>Signature des paquets (GPG) ';
                echo '<span class="signPackagesLoading">en cours<img src="images/loading.gif" class="icon" /></span><span class="signPackagesOK greentext hide">✔</span><span class="signPackagesKO redtext hide">✕</span>';
                echo '<div class="hide signRepoDiv"><pre>';
                
                $this->logcontent = ob_get_clean(); file_put_contents($this->log->steplog, $this->logcontent, FILE_APPEND); ob_start();

                // On se mets à la racine du repo
                // Activation de globstar (**), cela permet à bash d'aller chercher des fichiers .rpm récursivement, peu importe le nb de sous-répertoires
                if (file_exists("/usr/bin/rpmresign")) {
                    exec("shopt -s globstar && cd '${REPOS_DIR}/${DATE_JMA}_{$this->name}' && /usr/bin/rpmresign --path '${GPGHOME}' --name '${GPG_KEYID}' --passwordfile '${PASSPHRASE_FILE}' **/*.rpm >> {$this->log->steplog} 2>&1", $output, $result);
                } else {
                    exec("shopt -s globstar && cd '${REPOS_DIR}/${DATE_JMA}_{$this->name}' && rpmsign --addsign **/*.rpm >> {$this->log->steplog} 2>&1", $output, $result);	// Sinon on utilise rpmsign et on demande le mdp à l'utilisateur (pas possible d'utiliser un fichier passphrase)
                }
                echo '</pre></div>';

                $this->logcontent = ob_get_clean(); file_put_contents($this->log->steplog, $this->logcontent, FILE_APPEND); ob_start();

                if ($result == 0) {
                    echo '<style>';
                    echo '.signPackagesLoading { display: none; }';
                    echo '.signPackagesOK { display: inline-block; }';
                    echo '</style>';
                } else {
                    echo '<style>';
                    echo '.signPackagesLoading { display: none; }';
                    echo '.signPackagesKO { display: inline-block; }';
                    echo '</style>';
                    echo "<span class=\"redtext\">Erreur : </span>la signature des paquets a échouée";
                    echo "<br>Suppression de ce qui a été fait : ";
                    exec ("rm -rf '${REPOS_DIR}/${DATE_JMA}_{$this->name}'");
                    echo '<span class="greentext">OK</span>';
                    $this->logcontent = ob_get_clean(); file_put_contents($this->log->steplog, $this->logcontent, FILE_APPEND); ob_start();
                    throw new Exception();
                }
                $this->logcontent = ob_get_clean(); file_put_contents($this->log->steplog, $this->logcontent, FILE_APPEND);
            }

            if ($OS_FAMILY == "Debian") {
                // On va utiliser un répertoire temporaire pour travailler
                $TMP_DIR = '/tmp/deb_packages';
                mkdir("$TMP_DIR", 0770, true);
                echo '<br>Signature du repo (GPG) ';
                echo '<span class="signPackagesLoading">en cours<img src="images/loading.gif" class="icon" /></span><span class="signPackagesOK greentext hide">✔</span><span class="signPackagesKO redtext hide">✕</span>';
                echo '<div class="hide signRepoDiv"><pre>';

                $this->logcontent = ob_get_clean(); file_put_contents($this->log->steplog, $this->logcontent, FILE_APPEND); ob_start();
                
                // On se mets à la racine de la section
                // On recherche tous les paquets .deb et on les déplace dans le répertoire temporaire
                exec("cd ${REPOS_DIR}/{$this->name}/{$this->dist}/${DATE_JMA}_{$this->section}/ && find . -name '*.deb' -exec mv '{}' $TMP_DIR \;");
                // Après avoir déplacé tous les paquets on peut supprimer tout le contenu de la section
                exec("rm -rf ${REPOS_DIR}/{$this->name}/{$this->dist}/${DATE_JMA}_{$this->section}/*");
                // Création du répertoire conf et des fichiers de conf du repo
                mkdir("${REPOS_DIR}/{$this->name}/{$this->dist}/${DATE_JMA}_{$this->section}/conf", 0770, true);
                // Création du fichier "distributions"
                // echo -e "Origin: Repo $this->name sur ${WWW_HOSTNAME}\nLabel: apt repository\nCodename: {$this->dist}\nArchitectures: i386 amd64\nComponents: {$this->section}\nDescription: Miroir du repo {$this->name}, distribution {$this->dist}, section {$this->section}\nSignWith: ${GPG_KEYID}\nPull: {$this->section}" > conf/distributions
                file_put_contents("${REPOS_DIR}/{$this->name}/{$this->dist}/${DATE_JMA}_{$this->section}/conf/distributions", "Origin: Repo $this->name sur ${WWW_HOSTNAME}\nLabel: apt repository\nCodename: {$this->dist}\nArchitectures: i386 amd64\nComponents: {$this->section}\nDescription: Miroir du repo {$this->name}, distribution {$this->dist}, section {$this->section}\nSignWith: ${GPG_KEYID}\nPull: {$this->section}".PHP_EOL);
                // Création du fichier "options"
                // echo -e "basedir ${REPOS_DIR}/{$this->name}/{$this->dist}/${DATE_JMA}_{$this->section}\nask-passphrase" > conf/options
                file_put_contents("${REPOS_DIR}/{$this->name}/{$this->dist}/${DATE_JMA}_{$this->section}/conf/options", "basedir ${REPOS_DIR}/{$this->name}/{$this->dist}/${DATE_JMA}_{$this->section}\nask-passphrase".PHP_EOL);
                // Création du repo en incluant les paquets deb du répertoire temporaire, et signature du fichier Release
                exec("cd ${REPOS_DIR}/{$this->name}/{$this->dist}/${DATE_JMA}_{$this->section}/ && /usr/bin/reprepro --gnupghome ${GPGHOME} includedeb {$this->dist} ${TMP_DIR}/*.deb >> {$this->log->steplog} 2>&1", $output, $result);
                echo '</pre></div>';
                
                $this->logcontent = ob_get_clean(); file_put_contents($this->log->steplog, $this->logcontent, FILE_APPEND); ob_start();

                // Suppression du répertoire temporaire
                exec("rm -rf '$TMP_DIR'");
                if ($result == 0) {
                    echo '<style>';
                    echo '.signPackagesLoading { display: none; }';
                    echo '.signPackagesOK { display: inline-block; }';
                    echo '</style>';
                } else {
                    echo '<style>';
                    echo '.signPackagesLoading { display: none; }';
                    echo '.signPackagesKO { display: inline-block; }';
                    echo '</style>';
                    echo "<br><span class=\"redtext\">Erreur : </span>la signature de la section <b>$this->section</b> du repo <b>$this->name</b> a échouée";
                    echo '<br>Suppression de ce qui a été fait : ';
                    exec("rm -rf '${REPOS_DIR}/{$this->name}/{$this->dist}/${DATE_JMA}_{$this->section}'");
                    echo '<span class="greentext">OK</span>';
                    $this->logcontent = ob_get_clean(); file_put_contents($this->log->steplog, $this->logcontent, FILE_APPEND); ob_start();
                    throw new Exception();
                }
                $this->logcontent = ob_get_clean(); file_put_contents($this->log->steplog, $this->logcontent, FILE_APPEND);
            }
        }
        return true;
    }
}
?>