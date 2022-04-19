<?php
trait cleanArchives {

    public function cleanArchives() {
        /**
         *  1. Conversion de RETENTION en nombre entier (base octale) => déjà fait par Autoloader
         */
        //$RETENTION = intval($RETENTION, 8);

        /**
         *  2. Si la suppression automatique des repos archivés n'est pas autorisée alors on quitte la fonction
         */
        if (ALLOW_AUTODELETE_ARCHIVED_REPOS != "yes") return;

        /**
         *  3. Si le paramètre retention est vide ou est invalide, alors on quitte la fonction
         */
        if (empty(RETENTION)) return;
        if (!is_int(RETENTION)) return;
        if (RETENTION < 0) return;

        // TRAITEMENT //
        
        /**
         *  4. On récupère la liste de tous les repos archivés dans le fichier (on récupère le champ Name uniquement)
         */
        if (OS_FAMILY == "Redhat") {
            $result = $this->db->query("SELECT DISTINCT Name FROM repos_archived WHERE Status = 'active'");
            if ($this->db->isempty($result) === true) {
                return;
            }
        }
        if (OS_FAMILY == "Debian") {
            $result = $this->db->query("SELECT DISTINCT Name, Dist, Section FROM repos_archived WHERE Status = 'active'");
            if ($this->db->isempty($result) === true) {
                return;
            }
        }

        /**
         *  5. Sinon on récupère leur nom
         */
        while ($data = $result->fetchArray(SQLITE3_ASSOC)) {
            $reposArchived[] = $data;
        }

        /**
         *  6. Avec cette liste, on va traiter chaque repo individuellement, en les triant par date puis en supprimant les plus vieux (on conserve X copie du repo, X étant défini par RETENTION)
         */
        foreach ($reposArchived as $repoArchived) {
            $dates = [];
            $repoName = $repoArchived['Name'];
            if (OS_FAMILY == "Debian") {
                $repoDist = $repoArchived['Dist'];
                $repoSection = $repoArchived['Section'];
            }

            /**
             *  7. Pour chaque repo, on récupère les dates qui vont au delà du paramètre de retention renseigné
             */
            if (!empty($repoName)) {
                if (OS_FAMILY == "Redhat") {
                    try {
                        $stmt = $this->db->prepare("SELECT Date FROM repos_archived WHERE Name=:name and Status = 'active' ORDER BY Date DESC LIMIT -1 OFFSET :retention");
                        $stmt->bindValue(':name', $repoName);
                        $stmt->bindValue(':retention', RETENTION);
                        $result = $stmt->execute();
                    } catch (Exception $e) {
                        Common::dbError($e);
                    }

                    if ($this->db->isempty($result)) {
                        continue;
                    }
                }
                if (OS_FAMILY == "Debian") {
                    try {
                        $stmt = $this->db->prepare("SELECT Date FROM repos_archived WHERE Name=:name and Dist=:dist and Section=:section and Status = 'active' ORDER BY Date DESC LIMIT -1 OFFSET :retention");
                        $stmt->bindValue(':name', $repoName);
                        $stmt->bindValue(':dist', $repoDist);
                        $stmt->bindValue(':section', $repoSection);
                        $stmt->bindValue(':retention', RETENTION);
                        $result = $stmt->execute();
                    } catch (Exception $e) {
                        Common::dbError($e);
                    }

                    if ($this->db->isempty($result)) {
                        continue;
                    }
                }

                /**
                 *  Récupération des dates trouvées
                 */
                while ($data = $result->fetchArray(SQLITE3_ASSOC)) $dates[] = $data;

                foreach ($dates as $date) {
                    $repoDate = $date['Date'];
                    $repoDateFormatted = DateTime::createFromFormat('Y-m-d', $date['Date'])->format('d-m-Y');
                    if (!empty($repoDateFormatted)) {
                        if (OS_FAMILY == "Redhat") echo "<p>Suppression du repo archivé <b>$repoName</b> en date du <b>$repoDateFormatted</b>.</p>";
                        if (OS_FAMILY == "Debian") echo "<p>Suppression de la section archivée <b>$repoSection</b> du repo <b>$repoName</b> (distribution <b>$repoDist</b>) en date du <b>$repoDateFormatted</b>.</p>";
                        
                        /**
                         *  8. Suppression du miroir
                         */
                        if (OS_FAMILY == "Redhat") exec("rm '".REPOS_DIR."/archived_${repoDateFormatted}_${repoName}' -rf", $output, $return);
                        if (OS_FAMILY == "Debian") exec("rm '".REPOS_DIR."/${repoName}/${repoDist}/archived_${repoDateFormatted}_${repoSection}' -rf", $output, $return);
                        if ($return != 0) {
                            echo "<p><span class=\"redtext\">Erreur lors de la suppression du repo <b>$repoName</b> en date du <b>$repoDateFormatted</b>.</span></p>";
                            continue; // On traite la date suivante
                        }

                        /**
                         *   9. Nettoyage de la BDD
                         */
                        try {
                            if (OS_FAMILY == "Redhat") $stmt = $this->db->prepare("UPDATE repos_archived SET Status = 'deleted' WHERE Name=:name and Date=:date");
                            if (OS_FAMILY == "Debian") $stmt = $this->db->prepare("UPDATE repos_archived SET Status = 'deleted' WHERE Name=:name and Dist=:dist and Section=:section and Date=:date");
                            $stmt->bindValue(':name', $repoName);
                            $stmt->bindValue(':date', $repoDate);
                            if (OS_FAMILY == "Debian") {
                                $stmt->bindValue(':dist', $repoDist);
                                $stmt->bindValue(':section', $repoSection);
                            }
                            $stmt->execute();
                        } catch (Exception $e) {
                            Common::dbError($e);
                        }
                    }
                }
            }
        }
    }
}