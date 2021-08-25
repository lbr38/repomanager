<?php
trait changeEnv {
    
    public function exec_changeEnv() {
        global $OS_FAMILY;
        global $REPOS_DIR;
        global $DEFAULT_ENV;
        global $LAST_ENV;

        /**
         *  1. Génération du tableau récapitulatif de l'opération
         */
        echo "<table>
        <tr>
            <td>Nom du repo :</td>
            <td><b>{$this->repo->name}</b></td>
        </tr>";
        if ($OS_FAMILY == "Debian") {
            echo "<tr>
                <td>Distribution :</td>
                <td><b>{$this->repo->dist}</b></td>
            </tr>
            <tr>
                <td>Section :</td>
	            <td><b>{$this->repo->section}</b></td>
	        </tr>";
        }
        echo '<tr>
        <td>Environnement source :</td>';
        if ($DEFAULT_ENV === $LAST_ENV) { // Cas où il n'y a qu'un seul env
            echo "<td class=\"td-redbackground\"><span>{$this->repo->env}</span></td></tr>";
        } elseif ($this->repo->env === $DEFAULT_ENV) { 
            echo "<td class=\"td-whitebackground\"><span>{$this->repo->env}</span></td></tr>";
        } elseif ($this->repo->env === $LAST_ENV) {
            echo "<td class=\"td-redbackground\"><span>{$this->repo->env}</span></td></tr>";
        } else {
            echo "<td class=\"td-whitebackground\"><span>{$this->repo->env}</span></td></tr>";
        }
        echo "<tr>
            <td>Nouvel environnement :</td>";
        if ($DEFAULT_ENV === $LAST_ENV) { // Cas où il n'y a qu'un seul env
            echo "<td class=\"td-redbackground\"><span>{$this->repo->newEnv}</span></td></tr>";
        } elseif ($this->repo->newEnv === $DEFAULT_ENV) { 
            echo "<td class=\"td-whitebackground\"><span>{$this->repo->newEnv}</span></td></tr>";
        } elseif ($this->repo->newEnv === $LAST_ENV) {
            echo "<td class=\"td-redbackground\"><span>{$this->repo->newEnv}</span></td></tr>";
        } else {
            echo "<td class=\"td-whitebackground\"><span>{$this->repo->newEnv}</span></td></tr>";
        }
        if (!empty($this->repo->description)) {
            echo "<tr>
            <td>Description :</td>
            <td><b>{$this->repo->description}</b></td>
            </tr>";
        }
        echo '</table>';

        /**
         *  2. On vérifie si le repo existe
         */
        if ($OS_FAMILY == "Redhat") {
            if ($this->repo->existsEnv($this->repo->name, $this->repo->env) === false) {
                echo '<p><span class="redtext">Erreur :</span> ce repo n\'existe pas</p>';
                return false;
            }
        }
        if ($OS_FAMILY == "Debian") {
            if ($this->repo->section_existsEnv($this->repo->name, $this->repo->dist, $this->repo->section, $this->repo->env) === false) {
                echo '<p><span class="redtext">Erreur :</span> cette section n\'existe pas</p>';
                return false;
            }
        }

        /**
         *  3. Récupère la date vers laquelle on va faire pointer le nouvel env, la source du repo, son heure, son type et si il est signé ou non et son groupe
         */
        $this->repo->db_getDate();

        if ($OS_FAMILY == "Redhat") {
            $resultSource = $this->db->querySingleRow("SELECT Source from repos WHERE Name = '{$this->repo->name}' AND Env = '{$this->repo->env}' AND Status = 'active'");
            $resultTime = $this->db->querySingleRow("SELECT Time from repos WHERE Name = '{$this->repo->name}' AND Env = '{$this->repo->env}' AND Status = 'active'");
            $resultSigned = $this->db->querySingleRow("SELECT Signed from repos WHERE Name = '{$this->repo->name}' AND Env = '{$this->repo->env}' AND Status = 'active'");
            $resultType = $this->db->querySingleRow("SELECT Type from repos WHERE Name = '{$this->repo->name}' AND Env = '{$this->repo->env}' AND Status = 'active'");
            $resultGroupId = $this->db->querySingleRow("SELECT Id_group FROM group_members INNER JOIN repos ON repos.Id = group_members.Id_repo WHERE repos.Name = '{$this->repo->name}' AND repos.Env = '{$this->repo->env}' AND repos.Status = 'active'");
        }
        if ($OS_FAMILY == "Debian") {
            $resultSource = $this->db->querySingleRow("SELECT Source from repos WHERE Name = '{$this->repo->name}' AND Dist = '{$this->repo->dist}' AND Section = '{$this->repo->section}' AND Env = '{$this->repo->env}' AND Status = 'active'");
            $resultTime = $this->db->querySingleRow("SELECT Time from repos WHERE Name = '{$this->repo->name}' AND Dist = '{$this->repo->dist}' AND Section = '{$this->repo->section}' AND Env = '{$this->repo->env}' AND Status = 'active'");
            $resultSigned = $this->db->querySingleRow("SELECT Signed from repos WHERE Name = '{$this->repo->name}' AND Dist = '{$this->repo->dist}' AND Section = '{$this->repo->section}' AND Env = '{$this->repo->env}' AND Status = 'active'");
            $resultType = $this->db->querySingleRow("SELECT Type from repos WHERE Name = '{$this->repo->name}' AND Dist = '{$this->repo->dist}' AND Section = '{$this->repo->section}' AND Env = '{$this->repo->env}' AND Status = 'active'");
            $resultGroupId = $this->db->querySingleRow("SELECT Id_group FROM group_members INNER JOIN repos ON repos.Id = group_members.Id_repo WHERE repos.Name = '{$this->repo->name}' AND Dist = '{$this->repo->dist}' AND Section = '{$this->repo->section}' AND repos.Env = '{$this->repo->env}' AND repos.Status = 'active'");
        }
        $this->repo->source = $resultSource['Source'];
        $this->repo->time = $resultTime['Time'];
        $this->repo->signed = $resultSigned['Signed'];
        $this->repo->type = $resultType['Type'];
        if (!empty($resultGroupId['Id_group'])) {
            $this->repo->group = $resultGroupId['Id_group'];
        }

        /**
         *  4. Si on n'a pas transmis de description, on va conserver celle actuellement en place sur $this->repo->newEnv si existe. Cependant si il n'y a pas de description ou qu'aucun repo n'existe actuellement dans l'env $this->repo->newEnv alors celle-ci restera vide
         */
        if (empty($this->repo->description)) {
            if ($OS_FAMILY == "Redhat") {
                $result = $this->db->querySingleRow("SELECT Description from repos WHERE Name = '{$this->repo->name}' AND Env = '{$this->repo->newEnv}' AND Status = 'active'");
            }
            if ($OS_FAMILY == "Debian") {
                $result = $this->db->querySingleRow("SELECT Description from repos WHERE Name = '{$this->repo->name}' AND Dist = '{$this->repo->dist}' AND Section = '{$this->repo->section}' AND Env = '{$this->repo->newEnv}' AND Status = 'active'");
            }
            if (!empty($result)) {
                $this->repo->description = $result['Description'];
            } else {
                $this->repo->description = '';
            }
        }

        /**
         *  5. Dernière vérif : on vérifie que le repo n'est pas déjà dans l'environnement souhaité (par exemple fait par quelqu'un d'autre), dans ce cas on annule l'opération
         */
        if ($OS_FAMILY == "Redhat") {
            if ($this->repo->existsDateEnv($this->repo->name, $this->repo->date, $this->repo->newEnv) === true) {
                echo "<p><span class=\"redtext\">Erreur :</span> ce repo est déjà en {$this->repo->newEnv} au {$this->repo->date}</p>";
                return false;
            }
        }
        if ($OS_FAMILY == "Debian") {
            if ($this->repo->section_existsDateEnv($this->repo->name, $this->repo->dist, $this->repo->section, $this->repo->date, $this->repo->newEnv) === true) {
                echo "<p><span class=\"redtext\">Erreur :</span> cette section est déjà en {$this->repo->newEnv} au {$this->repo->date}</p>";
                return false;
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
                 *  Suppression du lien symbolique (on sait jamais si il existe)
                 */
                if (file_exists("${REPOS_DIR}/{$this->repo->name}_{$this->repo->newEnv}")) {
                    unlink("${REPOS_DIR}/{$this->repo->name}_{$this->repo->newEnv}");
                }
    
                /**
                 *  Création du lien symbolique
                 */
                exec("cd ${REPOS_DIR}/ && ln -sfn {$this->repo->dateFormatted}_{$this->repo->name}/ {$this->repo->name}_{$this->repo->newEnv}");
    
                /**
                 *  Mise à jour en BDD
                 */
                $this->db->exec("INSERT INTO repos (Name, Source, Env, Date, Time, Description, Signed, Type, Status) VALUES ('{$this->repo->name}', '{$this->repo->source}', '{$this->repo->newEnv}', '{$this->repo->date}', '{$this->repo->time}', '{$this->repo->description}', '{$this->repo->signed}', '{$this->repo->type}', 'active')");

                /**
                 *  Récupération de l'ID du repos précédemment inséré car on va en avoir besoin pour l'ajouter au même groupe que le repo sur $this->repo->env
                 */
                $this->repo->id = $this->db->lastInsertRowID();

            /**
             *  Cas 2 : Il y a déjà une version en $this->repo->newEnv qui va donc passer en archive. Modif du lien symbo + passage de la version précédente en archive :
             */
            } else {

                /**
                 *  Suppression du lien symbolique
                 */
                if (file_exists("${REPOS_DIR}/{$this->repo->name}_{$this->repo->newEnv}")) {
                    unlink("${REPOS_DIR}/{$this->repo->name}_{$this->repo->newEnv}");
                }

                /**
                 *  Création du lien symbolique
                 */
                exec("cd ${REPOS_DIR}/ && ln -sfn {$this->repo->dateFormatted}_{$this->repo->name}/ {$this->repo->name}_{$this->repo->newEnv}");

                /**
                 *  Passage de l'ancienne version de $this->repo->newEnv en archive
                 *  Pour cela on récupère la date et la description du repo qui va être archivé
                 */
                $result = $this->db->querySingleRow("SELECT Date FROM repos WHERE Name = '{$this->repo->name}' AND Env = '{$this->repo->newEnv}' AND Status = 'active'");
                $old_repoDate = $result['Date'];
                $old_repoDateFormatted = DateTime::createFromFormat('Y-m-d', $old_repoDate)->format('d-m-Y');
                $result = $this->db->querySingleRow("SELECT Description FROM repos WHERE Name = '{$this->repo->name}' AND Env = '{$this->repo->newEnv}' AND Status = 'active'");
                $old_repoDescription = $result['Description'];
                
                /**
                 *  Renommage du répertoire en archived_
                 *  Si un répertoire du même nom existe déjà alors on le supprime
                 */
                if (is_dir("${REPOS_DIR}/archived_${old_repoDateFormatted}_{$this->repo->name}")) {
                    exec("rm -rf '${REPOS_DIR}/archived_${old_repoDateFormatted}_{$this->repo->name}'");
                }
                if (!rename("${REPOS_DIR}/${old_repoDateFormatted}_{$this->repo->name}", "${REPOS_DIR}/archived_${old_repoDateFormatted}_{$this->repo->name}")) {
                    echo "<p><span class=\"redtext\">Erreur :</span> un problème est survenu lors du passage de l'ancienne version du $old_repoDateFormatted en archive</p>";
                    return false;
                }

                /**
                 *  Mise à jour de la BDD
                 */
                $this->db->exec("DELETE FROM repos WHERE Name = '{$this->repo->name}' AND Env = '{$this->repo->newEnv}' AND Date = '$old_repoDate' AND Status = 'active'");
                $this->db->exec("INSERT INTO repos (Name, Source, Env, Date, Time, Description, Signed, Type, Status) VALUES ('{$this->repo->name}', '{$this->repo->source}', '{$this->repo->newEnv}', '{$this->repo->date}', '{$this->repo->time}', '{$this->repo->description}', '{$this->repo->signed}', '{$this->repo->type}', 'active')");
                /**
                 *  Récupération de l'ID du repos précédemment inséré car on va en avoir besoin pour l'ajouter au même groupe que le repo sur $this->repo->env
                 */
                $this->repo->id = $this->db->lastInsertRowID();
                $this->db->exec("INSERT INTO repos_archived (Name, Source, Date, Time, Description, Signed, Type, Status) VALUES ('{$this->repo->name}', '{$this->repo->source}', '$old_repoDate', '{$this->repo->time}', '$old_repoDescription', '{$this->repo->signed}', '{$this->repo->type}', 'active')");

                /**
                 *  Application des droits sur la section archivée
                 */
                exec("find ${REPOS_DIR}/archived_${old_repoDateFormatted}_{$this->repo->name}/ -type f -exec chmod 0660 {} \;");
                exec("find ${REPOS_DIR}/archived_${old_repoDateFormatted}_{$this->repo->name}/ -type d -exec chmod 0770 {} \;");
            }
        }
    
        if ($OS_FAMILY == "Debian") {
            /**
             *  Cas 1 : pas de version déjà en $this->repo->newEnv
             */
            if ($this->repo->section_existsEnv($this->repo->name, $this->repo->dist, $this->repo->section, $this->repo->newEnv) === false) {
                
                /**
                 *  Suppression du lien symbolique (on sait jamais si il existe)
                 */
                if (file_exists("${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/{$this->repo->section}_{$this->repo->newEnv}")) {
                    unlink("${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/{$this->repo->section}_{$this->repo->newEnv}");
                }

                /**
                 *  Création du lien symbolique
                 */
                exec("cd ${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/ && ln -sfn {$this->repo->dateFormatted}_{$this->repo->section}/ {$this->repo->section}_{$this->repo->newEnv}");

                /**
                 *  Mise à jour en BDD
                 */
                $this->db->exec("INSERT INTO repos (Name, Source, Dist, Section, Env, Date, Time, Description, Signed, Type, Status) VALUES ('{$this->repo->name}', '{$this->repo->source}', '{$this->repo->dist}', '{$this->repo->section}', '{$this->repo->newEnv}', '{$this->repo->date}', '{$this->repo->time}', '{$this->repo->description}', '{$this->repo->signed}', '{$this->repo->type}', 'active')");
                
                /**
                 *  Récupération de l'ID du repos précédemment inséré car on va en avoir besoin pour l'ajouter au même groupe que le repo sur $this->repo->env
                 */
                $this->repo->id = $this->db->lastInsertRowID();
            
            /**
             *  Cas 2 : Il y a déjà une version en $this->repo->newEnv qui va donc passer en archive. Modif du lien symbo + passage de la version précédente en archive :
             */
            } else {

                /**
                 *  Suppression du lien symbolique
                 */
                if (file_exists("${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/{$this->repo->section}_{$this->repo->newEnv}")) {
                    unlink("${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/{$this->repo->section}_{$this->repo->newEnv}");
                }

                /**
                 *  Création du lien symbolique
                 */
                exec("cd ${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/ && ln -sfn {$this->repo->dateFormatted}_{$this->repo->section}/ {$this->repo->section}_{$this->repo->newEnv}");

                /**
                 *  Passage de l'ancienne version de $this->repo->newEnv en archive
                 *  Pour cela on récupère la date et la description du repo qui va être archivé
                 */
                $result = $this->db->querySingleRow("SELECT Date FROM repos WHERE Name = '{$this->repo->name}' AND Dist = '{$this->repo->dist}' AND Section = '{$this->repo->section}' AND Env = '{$this->repo->newEnv}' AND Status = 'active'");
                $old_repoDate = $result['Date'];
                $old_repoDateFormatted = DateTime::createFromFormat('Y-m-d', $old_repoDate)->format('d-m-Y');
                $result = $this->db->querySingleRow("SELECT Description FROM repos WHERE Name = '{$this->repo->name}' AND Dist = '{$this->repo->dist}' AND Section = '{$this->repo->section}' AND Env = '{$this->repo->newEnv}' AND Status = 'active'");
                $old_repoDescription = $result['Description'];

                /**
                 *  Renommage du répertoire en archived_
                 *  Si un répertoire du même nom existe déjà alors on le supprime
                 */
                if (is_dir("${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/archived_${old_repoDateFormatted}_{$this->repo->section}")) {
                    exec("rm -rf '${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/archived_${old_repoDateFormatted}_{$this->repo->section}'");
                }
                if (!rename("${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/${old_repoDateFormatted}_{$this->repo->section}", "${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/archived_${old_repoDateFormatted}_{$this->repo->section}")) {
                    echo "<p><span class=\"redtext\">Erreur :</span> un problème est survenu lors du passage de l'ancienne version du $old_repoDateFormatted en archive</p>";
                    return false;
                }

                /**
                 *  Mise à jour de la BDD
                 */
                $this->db->exec("DELETE FROM repos WHERE Name = '{$this->repo->name}' AND Dist = '{$this->repo->dist}' AND Section = '{$this->repo->section}' AND Env = '{$this->repo->newEnv}' AND Date = '$old_repoDate' AND Status = 'active'");
                $this->db->exec("INSERT INTO repos (Name, Source, Dist, Section, Env, Date, Time, Description, Signed, Type, Status) VALUES ('{$this->repo->name}', '{$this->repo->source}', '{$this->repo->dist}', '{$this->repo->section}', '{$this->repo->newEnv}', '{$this->repo->date}', '{$this->repo->time}', '{$this->repo->description}', '{$this->repo->signed}', '{$this->repo->type}', 'active')");
                /**
                 *  Récupération de l'ID du repos précédemment inséré car on va en avoir besoin pour l'ajouter au même groupe que le repo sur $this->repo->env
                 */
                $this->repo->id = $this->db->lastInsertRowID();
                $this->db->exec("INSERT INTO repos_archived (Name, Source, Dist, Section, Date, Time, Description, Signed, Type, Status) VALUES ('{$this->repo->name}', '{$this->repo->source}', '{$this->repo->dist}', '{$this->repo->section}', '$old_repoDate', '{$this->repo->time}', '$old_repoDescription', '{$this->repo->signed}', '{$this->repo->type}', 'active')");

                /**
                 *  Application des droits sur la section archivée
                 */
                exec("find ${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/archived_${old_repoDateFormatted}_{$this->repo->section}/ -type f -exec chmod 0660 {} \;");
                exec("find ${REPOS_DIR}/{$this->repo->name}/{$this->repo->dist}/archived_${old_repoDateFormatted}_{$this->repo->section}/ -type d -exec chmod 0770 {} \;");
            }
        }

        /**
         *  7. On ajoute le nouvel environnement de repo au même groupe que le repo sur $this->repo->env 
         */
        if (!empty($this->repo->group)) {
            $this->db->exec("INSERT INTO group_members (Id_repo, Id_group) VALUES ('{$this->repo->id}', '{$this->repo->group}')");
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

        echo '<p>Terminé <span class="greentext">✔</span></p>';

        $this->repo->cleanArchives();
    }
}
?>