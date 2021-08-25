<?php
trait op_createRepo {
/**
    *   Création des metadata du repo (Redhat) et des liens symboliques (environnements)
    */
    public function op_createRepo() {
        global $OS_FAMILY;
        global $DATE_DMY;
        global $REPOS_DIR;
        global $DEFAULT_ENV;

        ob_start();

        // Création des metadata du repo (Redhat/centos uniquement)
        if ($OS_FAMILY == "Redhat") {
            echo '<br>Création du dépôt (metadata) ';
            echo "<span class=\"createRepoLoading_{$this->log->pid}\">en cours<img src=\"images/loading.gif\" class=\"icon\" /></span><span class=\"createRepoOK_{$this->log->pid} greentext hide\">✔</span><span class=\"createRepoKO_{$this->log->pid} redtext hide\">✕</span>";
            echo '<div class="hide createRepoDiv"><pre>';

            $this->logcontent = ob_get_clean(); file_put_contents($this->log->steplog, $this->logcontent, FILE_APPEND); ob_start();

            exec("createrepo -v ${REPOS_DIR}/${DATE_DMY}_{$this->repo->name}/ >> {$this->log->steplog}", $output, $result);
            echo '</pre></div>';

            $this->logcontent = ob_get_clean(); file_put_contents($this->log->steplog, $this->logcontent, FILE_APPEND); ob_start();

            if ($result == 0) {
                echo '<style>';
                echo ".createRepoLoading_{$this->log->pid} { display: none; }";
                echo ".createRepoOK_{$this->log->pid} { display: inline-block; }";
                echo '</style>';
            } else {
                echo '<style>';
                echo ".createRepoLoading_{$this->log->pid} { display: none; }";
                echo ".createRepoKO_{$this->log->pid} { display: inline-block; }";
                echo '</style>';
                echo '<br><span class="redtext">Erreur : </span>la création du repo a échouée';
                echo '<br>Suppression de ce qui a été fait : ';
                exec("rm -rf '${REPOS_DIR}/${DATE_DMY}_{$this->repo->name}'");
                echo '<span class="greentext">OK</span>';
                $this->logcontent = ob_get_clean(); file_put_contents($this->log->steplog, $this->logcontent, FILE_APPEND); ob_start();
                throw new Exception();
            }
        }

        $this->logcontent = ob_get_clean(); file_put_contents($this->log->steplog, $this->logcontent, FILE_APPEND); ob_start();

        // Création du lien symbolique (environnement)
        if ($OS_FAMILY == "Redhat") {
            exec("cd ${REPOS_DIR}/ && ln -sfn ${DATE_DMY}_{$this->repo->name}/ {$this->repo->name}_${DEFAULT_ENV}", $output, $result);
        }
        if ($OS_FAMILY == "Debian") {
            exec("cd ${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/ && ln -sfn ${DATE_DMY}_{$this->repo->section}/ {$this->repo->section}_${DEFAULT_ENV}", $output, $result);
        }
        if ($result != 0) {
            throw new Exception('<p><span class="redtext">Erreur : </span>la finalisation du repo a échouée</p>');
        }
        return true;
    }
}
?>