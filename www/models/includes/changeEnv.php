<?php
trait changeEnv {
    
    public function exec_changeEnv() {
        global $OS_FAMILY;
        global $REPOS_DIR;
        global $DEFAULT_ENV;
        global $LAST_ENV;

        $case = 0;

        if ($this->repo->description == "nodescription") $this->repo->description = '';
        if ($this->repo->group == "nogroup") $this->repo->group = '';

        ob_start();

        /**
         *  1. Génération du tableau récapitulatif de l'opération
         */
        echo "<h3>NOUVEL ENVIRONNEMENT</h3>";
        echo "<table class=\"op-table\">
        <tr>
            <th>NOM DU REPO :</th>
            <td><b>{$this->repo->name}</b></td>
        </tr>";
        if ($OS_FAMILY == "Debian") {
            echo "<tr>
                <th>DISTRIBUTION :</th>
                <td><b>{$this->repo->dist}</b></td>
            </tr>
            <tr>
                <th>SECTION :</th>
	            <td><b>{$this->repo->section}</b></td>
	        </tr>";
        }
        echo '<tr>
            <th>ENVIRONNEMENT SOURCE :</th>
            <td><span>'.envtag($this->repo->env).'</span></td>
        </tr>';
        echo '<tr>
            <th>NOUVEL ENVIRONNEMENT :</th>
            <td><span>'.envtag($this->repo->newEnv).'</span></td>
        </tr>';
        if (!empty($this->repo->description)) {
            echo "<tr>
                <th>DESCRIPTION :</th>
                <td><b>{$this->repo->description}</b></td>
            </tr>";
        }
        echo '</table>';

        $this->log->steplog(1);
        $this->log->steplogInitialize('createEnv');
        $this->log->steplogTitle("CREATION DE L'ENVIRONNEMENT ".envtag($this->repo->newEnv)."");
        $this->log->steplogLoading();

        /**
         *  2. On vérifie si le repo (source) existe
         */
        if ($OS_FAMILY == "Redhat") {
            if ($this->repo->existsEnv($this->repo->name, $this->repo->env) === false) {
                throw new Exception('ce repo n\'existe pas en '.envtag($this->repo->env).'');
            }
        }
        if ($OS_FAMILY == "Debian") {
            if ($this->repo->section_existsEnv($this->repo->name, $this->repo->dist, $this->repo->section, $this->repo->env) === false) {
                throw new Exception('cette section n\'existe pas en '.envtag($this->repo->env).'');
            }
        }

        /**
         *  3. On vérifie qu'un repo cible de même env et de même date n'existe pas déjà
         */
        if ($OS_FAMILY == "Redhat") {
            if ($this->repo->existsDateEnv($this->repo->name, $this->repo->date, $this->repo->newEnv) === true) {
                throw new Exception("un repo ".envtag($this->repo->newEnv)." existe déjà au <b>".DateTime::createFromFormat('Y-m-d', $this->repo->date)->format('d-m-Y')."</b>");
            }
        }
        if ($OS_FAMILY == "Debian") {
            if ($this->repo->section_existsDateEnv($this->repo->name, $this->repo->dist, $this->repo->section, $this->repo->date, $this->repo->newEnv) === true) {
                throw new Exception("une section ".envtag($this->repo->newEnv)." existe déjà au <b>".DateTime::createFromFormat('Y-m-d', $this->repo->date)->format('d-m-Y')."</b>");
            }
        }

        /**
         *  4. Récupère la date vers laquelle on va faire pointer le nouvel env, la source du repo, son heure, son type et si il est signé ou non et son groupe (si il en a un)
         */
        $this->repo->db_getDate();

        if ($OS_FAMILY == "Redhat") {
            $stmt = $this->db->prepare("SELECT * FROM repos WHERE Name=:name AND Env=:env AND Status = 'active'");
            $stmt->bindValue(':name', $this->repo->name);
            $stmt->bindValue(':env', $this->repo->env);
            $result1 = $stmt->execute();

            $stmt2 = $this->db->prepare("SELECT Id_group FROM group_members INNER JOIN repos ON repos.Id = group_members.Id_repo WHERE repos.Name=:name AND repos.Env=:env AND repos.Status = 'active'");
            $stmt2->bindValue(':name', $this->repo->name);
            $stmt2->bindValue(':env', $this->repo->env);
            $result2 = $stmt2->execute();
        }
        if ($OS_FAMILY == "Debian") {
            $stmt = $this->db->prepare("SELECT * FROM repos WHERE Name=:name AND Dist=:dist AND Section=:section AND Env=:env AND Status = 'active'");
            $stmt->bindValue(':name', $this->repo->name);
            $stmt->bindValue(':dist', $this->repo->dist);
            $stmt->bindValue(':section', $this->repo->section);
            $stmt->bindValue(':env', $this->repo->env);
            $result1 = $stmt->execute();

            $stmt2 = $this->db->prepare("SELECT Id_group FROM group_members INNER JOIN repos ON repos.Id = group_members.Id_repo WHERE repos.Name=:name AND Dist=:dist AND Section=:section AND repos.Env=:env AND repos.Status = 'active'");
            $stmt2->bindValue(':name', $this->repo->name);
            $stmt2->bindValue(':dist', $this->repo->dist);
            $stmt2->bindValue(':section', $this->repo->section);
            $stmt2->bindValue(':env', $this->repo->env);
            $result2 = $stmt2->execute();
        }

        /**
         *  Vérifie que les deux résultats récupérés par les requêtes précédentes ne sont pas vides et fetch les données
         */
        $result1 = $this->repo->db->fetch($result1);
        $result2 = $this->repo->db->fetch($result2, 'ignore-null'); // Si le repo source n'est pas dans un groupe alors $result2 sera vide, on ignore si c'est le cas

        /**
         *  Récupération des données des résultats précédents
         */
        if (!empty($result1['Source']) AND !empty($result1['Time']) AND !empty($result1['Signed']) AND !empty($result1['Type'])) {
            $this->repo->source = $result1['Source'];
            $this->repo->time   = $result1['Time'];
            $this->repo->signed = $result1['Signed'];
            $this->repo->type   = $result1['Type'];
        } else {
            throw new Exception("certaines données concernant le repo source n'ont pas pu être récupérées");
        }

        /**
         *  Si le repo source appartient à un groupe alors le nouvel env. sera intégré au même groupe, sinon il ne sera intégré à aucun groupe
         */
        if (!empty($result2['Id_group']))
            $this->repo->group = $result2['Id_group'];
        else
            $this->repo->group = '';

        /**
         *  4. Si on n'a pas transmis de description, on va conserver celle actuellement en place sur $this->repo->newEnv si existe. Cependant si il n'y a pas de description ou qu'aucun repo n'existe actuellement dans l'env $this->repo->newEnv alors celle-ci restera vide
         */
        if (empty($this->repo->description)) {
            if ($OS_FAMILY == "Redhat") $stmt = $this->db->prepare("SELECT Description from repos WHERE Name=:name AND Env=:newenv AND Status = 'active'");
            if ($OS_FAMILY == "Debian") $stmt = $this->db->prepare("SELECT Description from repos WHERE Name=:name AND Dist=:dist AND Section=:section AND Env=:newenv AND Status = 'active'");
            $stmt->bindValue(':name', $this->repo->name);
            $stmt->bindValue(':newenv', $this->repo->newEnv);
            if ($OS_FAMILY == "Debian") {
                $stmt->bindValue(':dist', $this->repo->dist);
                $stmt->bindValue(':section', $this->repo->section);
            }
            $result = $stmt->execute();

            /**
             *  La description récupérée peut être vide, du coup on précise le paramètre 'ignore-null' afin que la fonction fetch() ne s'arrête pas si le résultat est vide
             */
            $result = $this->repo->db->fetch($result, 'ignore-null');

            if (!empty($result))
                $this->repo->description = $result['Description'];
            else
                $this->repo->description = '';

            unset($stmt, $result);
        }

        /**
         *  5. Dernière vérif : on vérifie que le repo n'est pas déjà dans l'environnement souhaité (par exemple fait par quelqu'un d'autre), dans ce cas on annule l'opération
         */
        if ($OS_FAMILY == "Redhat") {
            if ($this->repo->existsDateEnv($this->repo->name, $this->repo->date, $this->repo->newEnv) === true) {
                throw new Exception("ce repo est déjà en ".envtag($this->repo->newEnv)." au <b>{$this->repo->dateFormatted}</b>");
            }
        }
        if ($OS_FAMILY == "Debian") {
            if ($this->repo->section_existsDateEnv($this->repo->name, $this->repo->dist, $this->repo->section, $this->repo->date, $this->repo->newEnv) === true) {
                throw new Exception("cette section est déjà en ".envtag($this->repo->newEnv)." au <b>{$this->repo->dateFormatted}</b>");
            }
        }

        /**
         *  6. Traitement
         *  Deux cas possibles :
         *  - ce repo/section n'avait pas de version dans l'environnement cible, on crée simplement un lien symbo
         *  - ce repo/section avait déjà une version dans l'environnement cible, on modifie le lien symbo et on passe la version précédente en archive
         */
        if ($OS_FAMILY == "Redhat") {

            /**
             *  Cas 1 : pas de version déjà en $this->repo->newEnv
             */
            if ($this->repo->existsEnv($this->repo->name, $this->repo->newEnv) === false) {
                /**
                 *  On indique ne cas dans lequel on se trouve (sera utile plus bas pour l'ajout du repo à un groupe)
                 */
                $case = 1;

                /**
                 *  Suppression du lien symbolique (on sait jamais si il existe)
                 */
                if (file_exists("${REPOS_DIR}/{$this->repo->name}_{$this->repo->newEnv}")) unlink("${REPOS_DIR}/{$this->repo->name}_{$this->repo->newEnv}");
    
                /**
                 *  Création du lien symbolique
                 */
                exec("cd ${REPOS_DIR}/ && ln -sfn {$this->repo->dateFormatted}_{$this->repo->name}/ {$this->repo->name}_{$this->repo->newEnv}");
    
                /**
                 *  Mise à jour en BDD
                 */
                $stmt = $this->db->prepare("INSERT INTO repos (Name, Source, Env, Date, Time, Description, Signed, Type, Status) VALUES (:name, :source, :newenv, :date, :time, :description, :signed, :type, 'active')");
                $stmt->bindValue(':name', $this->repo->name);
                $stmt->bindValue(':source', $this->repo->source);
                $stmt->bindValue(':newenv', $this->repo->newEnv);
                $stmt->bindValue(':date', $this->repo->date);
                $stmt->bindValue(':time', $this->repo->time);
                $stmt->bindValue(':description', $this->repo->description);
                $stmt->bindValue(':signed', $this->repo->signed);
                $stmt->bindValue(':type', $this->repo->type);
                $stmt->execute();
                unset($stmt);

                /**
                 *  Récupération de l'ID du repos précédemment inséré car on va en avoir besoin pour l'ajouter au même groupe que le repo sur $this->repo->env
                 */
                $this->repo->id = $this->db->lastInsertRowID();

                /**
                 *  Clôture de l'étape en cours
                 */
                $this->log->steplogOK();

            /**
             *  Cas 2 : Il y a déjà une version en $this->repo->newEnv qui va donc passer en archive. Modif du lien symbo + passage de la version précédente en archive :
             */
            } else {

                /**
                 *  Suppression du lien symbolique
                 */
                if (file_exists("${REPOS_DIR}/{$this->repo->name}_{$this->repo->newEnv}")) unlink("${REPOS_DIR}/{$this->repo->name}_{$this->repo->newEnv}");

                /**
                 *  Création du lien symbolique
                 */
                exec("cd ${REPOS_DIR}/ && ln -sfn {$this->repo->dateFormatted}_{$this->repo->name}/ {$this->repo->name}_{$this->repo->newEnv}");

                /**
                 *  Clôture de l'étape en cours
                 */
                $this->log->steplogOK();

                /**
                 *  Passage de l'ancienne version de $this->repo->newEnv en archive
                 *  Pour cela on récupère la date et la description du repo qui va être archivé
                 */
                $stmt = $this->db->prepare("SELECT Date, Description FROM repos WHERE Name=:name AND Env=:newenv AND Status = 'active'");
                $stmt->bindValue(':name', $this->repo->name);
                $stmt->bindValue(':newenv', $this->repo->newEnv);
                $result = $stmt->execute();

                $result = $this->repo->db->fetch($result);
                $old_repoDate = $result['Date'];
                $old_repoDateFormatted = DateTime::createFromFormat('Y-m-d', $old_repoDate)->format('d-m-Y');
                $old_repoDescription = $result['Description'];

                /**
                 *  Création d'un nouveau div dans le log pour l'étape d'archivage
                 */
                $this->log->steplog(2);
                $this->log->steplogInitialize('archiveRepo');
                $this->log->steplogTitle("ARCHIVAGE DU MIROIR DU $old_repoDateFormatted");
                $this->log->steplogLoading();

                /**
                 *  Renommage du répertoire en archived_
                 *  Si un répertoire du même nom existe déjà alors on le supprime
                 */
                if (is_dir("${REPOS_DIR}/archived_${old_repoDateFormatted}_{$this->repo->name}")) exec("rm -rf '${REPOS_DIR}/archived_${old_repoDateFormatted}_{$this->repo->name}'");
                if (!rename("${REPOS_DIR}/${old_repoDateFormatted}_{$this->repo->name}", "${REPOS_DIR}/archived_${old_repoDateFormatted}_{$this->repo->name}")) {
                    throw new Exception("un problème est survenu lors du passage de l'ancienne version du <b>$old_repoDateFormatted</b> en archive");
                }

                /**
                 *  Mise à jour de la BDD
                 */
                $stmt = $this->db->prepare("UPDATE repos SET Date=:date, Time=:time, Description=:description, Signed=:signed WHERE Name=:name AND Env=:newenv AND Date=:old_date AND Status='active'");
                $stmt->bindValue(':date', $this->repo->date);
                $stmt->bindValue(':time', $this->repo->time);
                $stmt->bindValue(':description', $this->repo->description);
                $stmt->bindValue(':signed', $this->repo->signed);
                $stmt->bindValue(':name', $this->repo->name);
                $stmt->bindValue(':newenv', $this->repo->newEnv);
                $stmt->bindValue(':old_date', $old_repoDate);
                $stmt->execute();

                /**
                 *  Insertion du repo archivé dans repos_archived
                 */
                $stmt = $this->db->prepare("INSERT INTO repos_archived (Name, Source, Date, Time, Description, Signed, Type, Status) VALUES (:name, :source, :date, :time, :description, :signed, :type, 'active')");
                $stmt->bindValue(':name', $this->repo->name);
                $stmt->bindValue(':source', $this->repo->source);
                $stmt->bindValue(':date', $old_repoDate);
                $stmt->bindValue(':time', $this->repo->time);
                $stmt->bindValue(':decription', $old_repoDescription);
                $stmt->bindValue(':signed', $this->repo->signed);
                $stmt->bindValue(':type', $this->repo->type);
                $stmt->execute();

                /**
                 *  Application des droits sur la section archivée
                 */
                exec("find ${REPOS_DIR}/archived_${old_repoDateFormatted}_{$this->repo->name}/ -type f -exec chmod 0660 {} \;");
                exec("find ${REPOS_DIR}/archived_${old_repoDateFormatted}_{$this->repo->name}/ -type d -exec chmod 0770 {} \;");

                /**
                 *  Clôture de l'étape en cours
                 */
                $this->log->steplogOK();
            }
        }
    
        if ($OS_FAMILY == "Debian") {
            /**
             *  Cas 1 : pas de version déjà en $this->repo->newEnv
             */
            if ($this->repo->section_existsEnv($this->repo->name, $this->repo->dist, $this->repo->section, $this->repo->newEnv) === false) {
                /**
                 *  On indique ne cas dans lequel on se trouve (sera utile plus bas pour l'ajout du repo à un groupe)
                 */
                $case = 1;
                
                /**
                 *  Suppression du lien symbolique (on ne sait jamais si il existe)
                 */
                if (file_exists("${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/{$this->repo->section}_{$this->repo->newEnv}")) unlink("${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/{$this->repo->section}_{$this->repo->newEnv}");

                /**
                 *  Création du lien symbolique
                 */
                exec("cd ${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/ && ln -sfn {$this->repo->dateFormatted}_{$this->repo->section}/ {$this->repo->section}_{$this->repo->newEnv}");

                /**
                 *  Mise à jour en BDD
                 */
                $stmt = $this->db->prepare("INSERT INTO repos (Name, Source, Dist, Section, Env, Date, Time, Description, Signed, Type, Status) VALUES (:name, :source, :dist, :section, :newenv, :date, :time, :description, :signed, :type, 'active')");
                $stmt->bindValue(':name', $this->repo->name);
                $stmt->bindValue(':source', $this->repo->source);
                $stmt->bindValue(':dist', $this->repo->dist);
                $stmt->bindValue(':section', $this->repo->section);
                $stmt->bindValue(':newenv', $this->repo->newEnv);
                $stmt->bindValue(':date', $this->repo->date);
                $stmt->bindValue(':time', $this->repo->time);
                $stmt->bindValue(':description', $this->repo->description);
                $stmt->bindValue(':signed', $this->repo->signed);
                $stmt->bindValue(':type', $this->repo->type);
                $stmt->execute();

                /**
                 *  Récupération de l'ID du repos précédemment inséré car on va en avoir besoin pour l'ajouter au même groupe que le repo sur $this->repo->env
                 */
                $this->repo->id = $this->db->lastInsertRowID();

                /**
                 *  Clôture de l'étape en cours
                 */
                $this->log->steplogOK();
            
            /**
             *  Cas 2 : Il y a déjà une version en $this->repo->newEnv qui va donc passer en archive. Modif du lien symbo + passage de la version précédente en archive :
             */
            } else {

                /**
                 *  Suppression du lien symbolique
                 */
                if (file_exists("${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/{$this->repo->section}_{$this->repo->newEnv}")) unlink("${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/{$this->repo->section}_{$this->repo->newEnv}");

                /**
                 *  Création du lien symbolique
                 */
                exec("cd ${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/ && ln -sfn {$this->repo->dateFormatted}_{$this->repo->section}/ {$this->repo->section}_{$this->repo->newEnv}");

                /**
                 *  Clôture de l'étape en cours
                 */
                $this->log->steplogOK();

                /**
                 *  Passage de l'ancienne version de $this->repo->newEnv en archive
                 *  Pour cela on récupère la date et la description du repo qui va être archivé
                 */
                $stmt = $this->db->prepare("SELECT Date, Description FROM repos WHERE Name=:name AND Dist=:dist AND Section=:section AND Env=:newenv AND Status = 'active'");
                $stmt->bindValue(':name', $this->repo->name);
                $stmt->bindValue(':dist', $this->repo->dist);
                $stmt->bindValue(':section', $this->repo->section);
                $stmt->bindValue(':newenv', $this->repo->newEnv);
                $result = $stmt->execute();

                $result = $this->repo->db->fetch($result);
                $old_repoDate = $result['Date'];
                $old_repoDateFormatted = DateTime::createFromFormat('Y-m-d', $old_repoDate)->format('d-m-Y');
                $old_repoDescription = $result['Description'];

                /**
                 *  Création d'un nouveau div dans le log pour l'étape d'archivage
                 */
                $this->log->steplog(2);
                $this->log->steplogInitialize('archiveRepo');
                $this->log->steplogTitle("ARCHIVAGE DU MIROIR DU $old_repoDateFormatted");
                $this->log->steplogLoading();

                /**
                 *  Renommage du répertoire en archived_
                 *  Si un répertoire du même nom existe déjà alors on le supprime
                 */
                if (is_dir("${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/archived_${old_repoDateFormatted}_{$this->repo->section}")) exec("rm -rf '${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/archived_${old_repoDateFormatted}_{$this->repo->section}'");
                if (!rename("${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/${old_repoDateFormatted}_{$this->repo->section}", "${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/archived_${old_repoDateFormatted}_{$this->repo->section}")) {
                    throw new Exception("un problème est survenu lors du passage de l'ancienne version du <b>$old_repoDateFormatted</b> en archive");
                }

                /**
                 *  Mise à jour de la BDD
                 */
                $stmt = $this->db->prepare("UPDATE repos SET Date=:date, Time=:time, Description=:description, Signed=:signed WHERE Name=:name AND Dist=:dist AND Section=:section AND Env=:newenv AND Date=:old_date AND Status='active'");
                $stmt->bindValue(':date', $this->repo->date);
                $stmt->bindValue(':time', $this->repo->time);
                $stmt->bindValue(':description', $this->repo->description);
                $stmt->bindValue(':signed', $this->repo->signed);
                $stmt->bindValue(':name', $this->repo->name);
                $stmt->bindValue(':dist', $this->repo->dist);
                $stmt->bindValue(':section', $this->repo->section);
                $stmt->bindValue(':newenv', $this->repo->newEnv);
                $stmt->bindValue(':old_date', $old_repoDate);
                $stmt->execute();

                /**
                 *  Insertion du repo archivé dans repos_archived
                 */
                $stmt = $this->db->prepare("INSERT INTO repos_archived (Name, Source, Dist, Section, Date, Time, Description, Signed, Type, Status) VALUES (:name, :source, :dist, :section, :olddate, :time, :description, :signed, :type, 'active')");
                $stmt->bindValue(':name', $this->repo->name);
                $stmt->bindValue(':source', $this->repo->source);
                $stmt->bindValue(':dist', $this->repo->dist);
                $stmt->bindValue(':section', $this->repo->section);
                $stmt->bindValue(':olddate', $old_repoDate);
                $stmt->bindValue(':time', $this->repo->time);
                $stmt->bindValue(':description', $old_repoDescription);
                $stmt->bindValue(':signed', $this->repo->signed);
                $stmt->bindValue(':type', $this->repo->type);
                $stmt->execute();

                /**
                 *  Application des droits sur la section archivée
                 */
                exec("find ${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/archived_${old_repoDateFormatted}_{$this->repo->section}/ -type f -exec chmod 0660 {} \;");
                exec("find ${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/archived_${old_repoDateFormatted}_{$this->repo->section}/ -type d -exec chmod 0770 {} \;");

                /**
                 *  Clôture de l'étape en cours
                 */
                $this->log->steplogOK();
            }
        }

        $this->log->steplog(3);
        $this->log->steplogInitialize('finalizeRepo');
        $this->log->steplogTitle("FINALISATION");
        $this->log->steplogLoading();

        /**
         *  7. On ajoute le nouvel environnement de repo au même groupe que le repo sur $this->repo->env 
         *  On traite ce cas uniquement si on est passé par le Cas n°1
         */
        if (!empty($this->repo->group) AND $case == 1) {
            $stmt = $this->db->prepare("INSERT INTO group_members (Id_repo, Id_group) VALUES (:repoid, :groupid)");
            $stmt->bindValue(':repoid', $this->repo->id);
            $stmt->bindValue(':groupid', $this->repo->group);
            $stmt->execute();
        }

        /**
         *  8. Application des droits sur le repo/la section modifié
         */
        if ($OS_FAMILY == "Redhat") {
            exec("find ${REPOS_DIR}/{$this->repo->dateFormatted}_{$this->repo->name}/ -type f -exec chmod 0660 {} \;");
            exec("find ${REPOS_DIR}/{$this->repo->dateFormatted}_{$this->repo->name}/ -type d -exec chmod 0770 {} \;");
        }

        if ($OS_FAMILY == "Debian") {
            exec("find ${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/{$this->repo->dateFormatted}_{$this->repo->section}/ -type f -exec chmod 0660 {} \;");
            exec("find ${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/{$this->repo->dateFormatted}_{$this->repo->section}/ -type d -exec chmod 0770 {} \;");          
        }

        /**
         *  Clôture de l'étape en cours
         */
        $this->log->steplogOK();

        $this->repo->cleanArchives();
    }
}
?>