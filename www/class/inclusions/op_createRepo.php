<?php
trait op_createRepo {
/**
    *   Création des metadata du repo (Redhat) et des liens symboliques (environnements)
    */
    public function op_createRepo() {
        global $OS_FAMILY;
        global $DATE_JMA;
        global $REPOS_DIR;
        global $DEFAULT_ENV;
        global $TEMP_DIR;

        ob_start();

        // Création des metadata du repo (Redhat/centos uniquement)
        if ($OS_FAMILY == "Redhat") {
            echo '<br>Création du dépôt (metadata) ';
            echo '<span class="createRepoLoading">en cours<img src="images/loading.gif" class="icon" /></span><span class="createRepoOK greentext hide">✔</span><span class="createRepoKO redtext hide">✕</span>';
            echo '<div class="hide createRepoDiv"><pre>';

            $this->logcontent = ob_get_clean(); file_put_contents($this->log->steplog, $this->logcontent, FILE_APPEND); ob_start();

            exec("createrepo -v ${REPOS_DIR}/${DATE_JMA}_{$this->name}/ >> {$this->log->steplog}", $output, $result);
            echo '</pre></div>';

            $this->logcontent = ob_get_clean(); file_put_contents($this->log->steplog, $this->logcontent, FILE_APPEND); ob_start();

            if ($result == 0) {
                echo '<style>';
                echo '.createRepoLoading { display: none; }';
                echo '.createRepoOK { display: inline-block; }';
                echo '</style>';
            } else {
                echo '<style>';
                echo '.createRepoLoading { display: none; }';
                echo '.createRepoKO { display: inline-block; }';
                echo '</style>';
                echo '<br><span class="redtext">Erreur : </span>la création du repo a échouée';
                echo '<br>Suppression de ce qui a été fait : ';
                exec("rm -rf '${REPOS_DIR}/${DATE_JMA}_{$this->name}'");
                echo '<span class="greentext">OK</span>';
                $this->logcontent = ob_get_clean(); file_put_contents($this->log->steplog, $this->logcontent, FILE_APPEND); ob_start();
                throw new Exception();
            }
        }

        $this->logcontent = ob_get_clean(); file_put_contents($this->log->steplog, $this->logcontent, FILE_APPEND); ob_start();

        // Création du lien symbolique (environnement)
        if ($OS_FAMILY == "Redhat") {
            exec("cd ${REPOS_DIR}/ && ln -sfn ${DATE_JMA}_{$this->name}/ {$this->name}_${DEFAULT_ENV}", $output, $result);
        }
        if ($OS_FAMILY == "Debian") {
            exec("cd ${REPOS_DIR}/{$this->name}/{$this->dist}/ && ln -sfn ${DATE_JMA}_{$this->section}/ {$this->section}_${DEFAULT_ENV}", $output, $result);
        }
        if ($result != 0) {
            throw new Exception('<p><span class="redtext">Erreur : </span>la finalisation du repo a échouée</p>');
        }
        return true;
    }
}
?>