<?php
trait op_archive {
    /**
     *  Archivage de l'ancien repo/section
     */
    public function op_archive() {
        global $OS_FAMILY;
        global $REPOS_DIR;
        global $DATE_YMD;

        $archiveError = 0;

        ob_start();

        $this->log->steplogInitialize('archiveRepo');
        $this->log->steplogTitle("ARCHIVAGE");
        $this->log->steplogLoading();

        /**
         *  1. Avant d'archiver un repo/section, il faut vérifier qu'il n'est plus utilisé par aucun environnement
         *  Pour cela on a besoin de récupérer la date du repo/section qui va être potentiellement archivé.
         *  Puis on affiche toutes les occurences de ce repo/section en BDD en filtrant sur la date récupérée.
         *  Si il y a 1 ou plusieurs occurences alors on ne peut pas archiver le repo/section à la date indiquée car il est toujours utilisé
         */
        $stmt = $this->db->prepare("SELECT * FROM repos WHERE Id=:id AND Status = 'active'");
        $stmt->bindValue(':id', $this->repo->id);
        $result = $stmt->execute();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) $datas = $row;
        $oldRepoDate = $datas['Date'];
        $oldRepoDateFormatted = DateTime::createFromFormat('Y-m-d', $oldRepoDate)->format('d-m-Y');
        $this->repo->description = $datas['Description'];
        $this->repo->type = $datas['Type'];
        $oldRepoTime = $datas['Time'];

        if (empty($oldRepoDate) OR empty($oldRepoDateFormatted)) {
            /**
             *  Si il y eu une erreur on fait appel directement à steplogError pour afficher un message,
             *  On n'utilise pas throw new Exception qui aurait pour effet de mettre fin à l'opération. Or ici on peut se permettre de continuer même si l'ancienne version n'a pas pû être supprimée
             */
            $this->log->steplogError("impossible de récupérer la date de l'ancien repo");
            return;
        }

        /**
         *  A partir de la date récupérée, on regarde si d'autres environnements pointent sur le repo/section à cette date
         *  On exclu $this->env de la recherche car il apparaitra forcémment sinon.
         */
        if ($OS_FAMILY == "Redhat") $stmt = $this->db->prepare("SELECT * FROM repos WHERE Name=:name AND Date=:date AND Status = 'active'");
        if ($OS_FAMILY == "Debian") $stmt = $this->db->prepare("SELECT * FROM repos WHERE Name=:name AND Dist=:dist AND Section=:section AND Date=:date AND Status = 'active'");
        $stmt->bindValue(':name', $this->repo->name);
        $stmt->bindValue(':date', $oldRepoDate);
        if ($OS_FAMILY == "Debian") {
            $stmt->bindValue(':dist', $this->repo->dist);
            $stmt->bindValue(':section', $this->repo->section);
        }
        $result = $stmt->execute();

        /**
         *  On compte le nombre de lignes obtenues
         *  Si 0 lignes, cela signifie que plus aucun env n'utilise la version, on va pouvoir l'archiver
         *  Si >0 lignes, cela signifie que la version est toujours utilisée par un env, on ne l'archive pas
         */
        $count = $this->db->count($result);

        /**
         *  Mise à jour de la date, de l'heure et de la signature en BDD du repo qu'on vient de mettre à jour
         */
        $stmt = $this->db->prepare("UPDATE repos SET Date=:date, Time=:time, Signed=:signed WHERE Id=:id");
        $stmt->bindValue(':date', $this->repo->date);
        $stmt->bindValue(':time', $this->repo->time);
        $stmt->bindValue(':signed', $this->repo->gpgResign);
        $stmt->bindValue(':id', $this->repo->id);
        $stmt->execute();

        /**
         *  Cas où on archive l'ancien repo/section
         */
        if ($count == 0) {
            if ($OS_FAMILY == "Redhat") {
                /**
                 *  Si un répertoire d'archive existe déjà alors on le supprime
                 */
                if (is_dir("${REPOS_DIR}/archived_${oldRepoDateFormatted}_{$this->repo->name}")) exec("rm -rf '${REPOS_DIR}/archived_${oldRepoDateFormatted}_{$this->repo->name}'");
                if (!rename("${REPOS_DIR}/${oldRepoDateFormatted}_{$this->repo->name}", "${REPOS_DIR}/archived_${oldRepoDateFormatted}_{$this->repo->name}")) {
                    $archiveError++;

                } else {
                    /**
                     *  Insertion en BDD du nouveau repo archivé
                     */
                    $stmt = $this->db->prepare("INSERT INTO repos_archived (Name, Source, Date, Time, Description, Signed, Type, Status) VALUES (:name, :source, :olddate, :oldtime, :description, :signed, :type, 'active')");
                    $stmt->bindValue(':name', $this->repo->name);
                    $stmt->bindValue(':source', $this->repo->source);
                    $stmt->bindValue(':olddate', $oldRepoDate);
                    $stmt->bindValue(':oldtime', $oldRepoTime);
                    $stmt->bindValue(':description', $this->repo->description);
                    $stmt->bindValue(':signed', $this->repo->signed);
                    $stmt->bindValue(':type', $this->repo->type);
                    $stmt->execute();
                }
            }

            if ($OS_FAMILY == "Debian") {
                /**
                 *  Si un répertoire d'archive existe déjà alors on le supprime
                 */
                if (is_dir("${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/archived_${oldRepoDateFormatted}_{$this->repo->section}")) exec("rm -rf '${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/archived_${oldRepoDateFormatted}_{$this->repo->section}'");
                if (!rename("${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/${oldRepoDateFormatted}_{$this->repo->section}", "${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/archived_${oldRepoDateFormatted}_{$this->repo->section}")) {
                    $archiveError++;

                } else {
                    /**
                     *  Insertion en BDD du nouveau repo archivé
                     */
                    $stmt = $this->db->prepare("INSERT INTO repos_archived (Name, Source, Dist, Section, Date, Time, Description, Signed, Type, Status) VALUES (:name, :source, :dist, :section, :olddate, :oldtime, :description, :signed, :type, 'active')");
                    $stmt->bindValue(':name', $this->repo->name);
                    $stmt->bindValue(':dist', $this->repo->dist);
                    $stmt->bindValue(':section', $this->repo->section);
                    $stmt->bindValue(':source', $this->repo->source);
                    $stmt->bindValue(':olddate', $oldRepoDate);
                    $stmt->bindValue(':oldtime', $oldRepoTime);
                    $stmt->bindValue(':description', $this->repo->description);
                    $stmt->bindValue(':signed', $this->repo->signed);
                    $stmt->bindValue(':type', $this->repo->type);
                    $stmt->execute();
                }
            }

            if ($archiveError == 0) {
                $this->log->steplogOK("La version précédente du <b>$oldRepoDateFormatted</b> n'est pas utilisée par d'autres environnements, elle a donc été archivée");

            } else {
                /**
                 *  Si il y eu une erreur on fait appel directement à steplogError pour afficher un message,
                 *  On n'utilise pas throw new Exception qui aurait pour effet de mettre fin à l'opération. Or ici on peut se permettre de continuer même si l'ancienne version n'a pas pû être supprimée
                 */
                $this->log->steplogError("impossible d'archiver l'ancienne version");
            }

        } else {
            /**
             *  Cas où on n'archive pas : on ne fait rien
             */
            $this->log->steplogOK('Rien à archiver');
        }

        $this->log->steplogWrite();

        return true;
    }
}
?>