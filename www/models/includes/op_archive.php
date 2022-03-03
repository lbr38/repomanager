<?php
trait op_archive {
    /**
     *  Archivage de l'ancien repo/section
     */
    public function op_archive($params) {
        extract($params);

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
        try {
            $stmt = $this->db->prepare("SELECT Date, Time, Source, Signed, Type, Description FROM repos WHERE Id = :id AND Status = 'active'");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();

        } catch(Exception $e) {
            Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) $datas = $row;
        $actual_date          = $datas['Date'];
        $actual_dateFormatted = DateTime::createFromFormat('Y-m-d', $actual_date)->format('d-m-Y');
        $actual_time          = $datas['Time'];
        $actual_source        = $datas['Source'];
        $actual_signed        = $datas['Signed'];
        $actual_type          = $datas['Type'];
        $actual_description   = $datas['Description'];

        if (empty($actual_date) OR empty($actual_dateFormatted)) {
            /**
             *  Si il y eu une erreur on fait appel directement à steplogError pour afficher un message,
             *  On n'utilise pas throw new Exception qui aurait pour effet de mettre fin à l'opération. Or ici on peut se permettre de continuer même si l'ancienne version n'a pas pû être supprimée
             */
            $this->log->steplogError("impossible de récupérer la date de l'ancien repo");
            return;
        }

        /**
         *  Dans le cas où on remet à jour un repo à la date actuelle (par exemple plusieurs fois dans la journée) alors on ignore l'archivage de l'ancien repo (ce qui pourrait avoir comme conséquence d'archiver le repo qu'on vient de mettre à jour)
         */
        if ($actual_date == $date) {
            $archive = 'no';

        /**
         *  Sinon on vérifie qu'on peut archiver l'ancien repo ou non
         */
        } else {
            /**
             *  A partir de la date récupérée, on regarde si d'autres environnements pointent sur le repo/section à cette date
             *  On exclu $env de la recherche car il apparaitra forcémment sinon.
             */
            try {            
                if (OS_FAMILY == "Redhat") $stmt = $this->db->prepare("SELECT * FROM repos WHERE Name = :name AND Date = :date AND Env != :env AND Status = 'active'");
                if (OS_FAMILY == "Debian") $stmt = $this->db->prepare("SELECT * FROM repos WHERE Name = :name AND Dist = :dist AND Section = :section AND Env != :env AND Date = :date AND Status = 'active'");
                $stmt->bindValue(':name', $name);
                $stmt->bindValue(':date', $actual_date);
                $stmt->bindValue(':env', $env);
                if (OS_FAMILY == "Debian") {
                    $stmt->bindValue(':dist', $dist);
                    $stmt->bindValue(':section', $section);
                }
                $result = $stmt->execute();

            } catch(Exception $e) {
                Common::dbError($e);
            }

            /**
             *  On compte le nombre de lignes obtenues
             *  Si 0 lignes, cela signifie que plus aucun env n'utilise la version, on va pouvoir l'archiver
             *  Si >0 lignes, cela signifie que la version est toujours utilisée par un env, on ne l'archive pas
             */
            if ($this->db->isempty($result)) {
                $archive = 'yes';
            } else {
                $archive = 'no';
            }
        }

        /**
         *  Mise à jour de la date, de l'heure et de la signature en BDD du repo qu'on vient de mettre à jour
         */
        try {
            $stmt = $this->db->prepare("UPDATE repos SET Date = :date, Time = :time, Signed = :signed WHERE Id = :id");
            $stmt->bindValue(':date', $date);
            $stmt->bindValue(':time', $time);
            $stmt->bindValue(':signed', $targetGpgResign);
            $stmt->bindValue(':id', $id);
            $stmt->execute();

        } catch(Exception $e) {
            Common::dbError($e);
        }

        /**
         *  Cas où on archive l'ancien repo/section
         */
        if ($archive == 'yes') {
            if (OS_FAMILY == "Redhat") {
                /**
                 *  Si un répertoire d'archive existe déjà alors on le supprime
                 */
                if (is_dir(REPOS_DIR."/archived_${actual_dateFormatted}_${name}")) exec("rm -rf '".REPOS_DIR."/archived_${actual_dateFormatted}_${name}'");
                if (!rename(REPOS_DIR."/${actual_dateFormatted}_${name}", REPOS_DIR."/archived_${actual_dateFormatted}_${name}")) {
                    $archiveError++;

                } else {
                    /**
                     *  Insertion en BDD du nouveau repo archivé
                     */
                    try {
                        $stmt = $this->db->prepare("INSERT INTO repos_archived (Name, Source, Date, Time, Description, Signed, Type, Status) VALUES (:name, :actual_source, :actual_date, :actual_time, :actual_description, :actual_signed, :actual_type, 'active')");
                        $stmt->bindValue(':name', $name);
                        $stmt->bindValue(':actual_source', $actual_source);
                        $stmt->bindValue(':actual_date', $actual_date);
                        $stmt->bindValue(':actual_time', $actual_time);
                        $stmt->bindValue(':actual_description', $actual_description);
                        $stmt->bindValue(':actual_signed', $actual_signed);
                        $stmt->bindValue(':actual_type', $actual_type);
                        $stmt->execute();
                    } catch(Exception $e) {
                        Common::dbError($e);
                    }
                }
            }

            if (OS_FAMILY == "Debian") {
                /**
                 *  Si un répertoire d'archive existe déjà alors on le supprime
                 */
                if (is_dir(REPOS_DIR."/${name}/${dist}/archived_${actual_dateFormatted}_${section}")) exec("rm -rf '".REPOS_DIR."/${name}/${dist}/archived_${actual_dateFormatted}_${section}'");
                if (!rename(REPOS_DIR."/${name}/${dist}/${actual_dateFormatted}_${section}", REPOS_DIR."/${name}/${dist}/archived_${actual_dateFormatted}_${section}")) {
                    $archiveError++;

                } else {
                    /**
                     *  Insertion en BDD du nouveau repo archivé
                     */
                    try {
                        $stmt = $this->db->prepare("INSERT INTO repos_archived (Name, Source, Dist, Section, Date, Time, Description, Signed, Type, Status) VALUES (:name, :actual_source, :dist, :section, :actual_date, :actual_time, :actual_description, :actual_signed, :actual_type, 'active')");
                        $stmt->bindValue(':name', $name);
                        $stmt->bindValue(':dist', $dist);
                        $stmt->bindValue(':section', $section);
                        $stmt->bindValue(':actual_source', $actual_source);
                        $stmt->bindValue(':actual_date', $actual_date);
                        $stmt->bindValue(':actual_time', $actual_time);
                        $stmt->bindValue(':actual_description', $actual_description);
                        $stmt->bindValue(':actual_signed', $actual_signed);
                        $stmt->bindValue(':actual_type', $actual_type);
                        $stmt->execute();
                    } catch(Exception $e) {
                        Common::dbError($e);
                    }
                }
            }

            if ($archiveError == 0) {
                $this->log->steplogOK("La version précédente du <b>$actual_dateFormatted</b> n'est pas utilisée par d'autres environnements, elle a donc été archivée");

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