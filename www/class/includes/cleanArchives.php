<?php
trait cleanArchives {

    public function cleanArchives() {
        global $ALLOW_AUTODELETE_ARCHIVED_REPOS;
        global $OS_FAMILY;
        global $REPOS_DIR;
        global $RETENTION;

        /**
         *  1. Conversion de RETENTION en nombre entier (base octale)
         */
        $RETENTION = intval($RETENTION, 8);

        /**
         *  2. Si la suppression automatique des repos archivés n'est pas autorisée alors on quitte la fonction
         */
        if ($ALLOW_AUTODELETE_ARCHIVED_REPOS != "yes") {
            return;
        }

        /**
         *  3. Si le paramètre retention est vide, alors on quitte la fonction
         */
        if (empty($RETENTION)) {
            return;
        }

        if (!is_int($RETENTION)) {
            return;
        }
        if ($RETENTION < 0) {
            return;
        }

        // TRAITEMENT //
        
        /**
         *  4. On récupère la liste de tous les repos archivés dans le fichier (on récupère le champ Name uniquement)
         */
        if ($OS_FAMILY == "Redhat") {
            if ($this->db->countRows("SELECT DISTINCT Name FROM repos_archived") == 0) {
                return;
            }
            $result = $this->db->query("SELECT DISTINCT Name FROM repos_archived");
        }
        if ($OS_FAMILY == "Debian") {
            if ($this->db->countRows("SELECT DISTINCT Name, Dist, Section FROM repos_archived") == 0) {
                return;
            }
            $result = $this->db->query("SELECT DISTINCT Name, Dist, Section FROM repos_archived");
        }

        /**
         *  5. Sinon on récupère leur nom
         */
        while ($data = $result->fetchArray()) {
            $reposArchived[] = $data;
        }

        /**
         *  6. Avec cette liste, on va traiter chaque repo individuellement, en les triant par date puis en supprimant les plus vieux (on conserve X copie du repo, X étant défini par $RETENTION)
         */
        foreach($reposArchived as $repoArchived) {
            $dates = [];
            $repoName = $repoArchived['Name'];
            if ($OS_FAMILY == "Debian") {
                $repoDist = $repoArchived['Dist'];
                $repoSection = $repoArchived['Section'];
            }

            /**
             *  7. Pour chaque repo, on récupère les dates qui vont au delà du paramètre de retention renseigné
             */
            if (!empty($repoName)) {
                if ($OS_FAMILY == "Redhat") {
                    if ($this->db->countRows("SELECT Date FROM repos_archived WHERE Name = '$repoName' ORDER BY Date DESC LIMIT -1 OFFSET $RETENTION") == 0) {
                        continue;
                    } else {
                        $result = $this->db->query("SELECT Date FROM repos_archived WHERE Name = '$repoName' ORDER BY Date DESC LIMIT -1 OFFSET $RETENTION");        
                    }
                }
                if ($OS_FAMILY == "Debian") {
                    if ($this->db->countRows("SELECT Date FROM repos_archived WHERE Name = '$repoName' AND Dist = '$repoDist' AND Section = '$repoSection' ORDER BY Date DESC LIMIT -1 OFFSET $RETENTION") == 0) {
                        continue;
                    } else {
                        $result = $this->db->query("SELECT Date FROM repos_archived WHERE Name = '$repoName' AND Dist = '$repoDist' AND Section = '$repoSection' ORDER BY Date DESC LIMIT -1 OFFSET $RETENTION");        
                    }
                }
                while ($data = $result->fetchArray()) {
                    $dates[] = $data;
                }

                foreach($dates as $date) {
                    $repoDate = $date['Date'];
                    $repoDateFormatted = DateTime::createFromFormat('Y-m-d', $date['Date'])->format('d-m-Y');
                    if (!empty($repoDateFormatted)) {
                        if ($OS_FAMILY == "Redhat") { echo "<p>Suppression du repo archivé $repoName en date du $repoDateFormatted</p>"; }
                        if ($OS_FAMILY == "Debian") { echo "<p>Suppression de la section archivée $repoSection du repo $repoName (distribution $repoDist) en date du $repoDateFormatted</p>"; }
                        /**
                         *  8. Suppression du miroir
                         */
                        if ($OS_FAMILY == "Redhat") { exec("rm '${REPOS_DIR}/archived_${repoDateFormatted}_${repoName}' -rf", $output, $return); }
                        if ($OS_FAMILY == "Debian") { exec("rm '${REPOS_DIR}/${repoName}/${repoDist}/archived_${repoDateFormatted}_${repoSection}' -rf", $output, $return); }
                        if ($return != 0) {
                            echo "<p><span class=\"redtext\">Erreur lors de la suppression du repo $repoName en date du $repoDateFormatted</span></p>";
                            continue; // On traite la date suivante
                        }
                        /**
                         *   9. Nettoyage du fichier de liste
                         */
                        if ($OS_FAMILY == "Redhat") { $this->db->exec("DELETE FROM repos_archived WHERE Name = '$repoName' AND Date = '$repoDate'"); }
                        if ($OS_FAMILY == "Debian") { $this->db->exec("DELETE FROM repos_archived WHERE Name = '$repoName' AND Dist = '$repoDist' AND Section = '$repoSection' AND Date = '$repoDate'"); }
                    }
                }
            }
        }
    }
}