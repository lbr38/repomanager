<?php
trait changeEnv {
    
    public function changeEnv() {
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
            <td><b>{$this->name}</b></td>
        </tr>";
        if ($OS_FAMILY == "Debian") {
            echo "<tr>
                <td>Distribution :</td>
                <td><b>{$this->dist}</b></td>
            </tr>
            <tr>
                <td>Section :</td>
	            <td><b>{$this->section}</b></td>
	        </tr>";
        }
        echo '<tr>
        <td>Environnement source :</td>';
        if ($DEFAULT_ENV === $LAST_ENV) { // Cas où il n'y a qu'un seul env
            echo "<td class=\"td-redbackground\"><span>{$this->env}</span></td></tr>";
        } elseif ($this->env === $DEFAULT_ENV) { 
            echo "<td class=\"td-whitebackground\"><span>{$this->env}</span></td></tr>";
        } elseif ($this->env === $LAST_ENV) {
            echo "<td class=\"td-redbackground\"><span>{$this->env}</span></td></tr>";
        } else {
            echo "<td class=\"td-whitebackground\"><span>{$this->env}</span></td></tr>";
        }
        echo "<tr>
            <td>Nouvel environnement :</td>";
        if ($DEFAULT_ENV === $LAST_ENV) { // Cas où il n'y a qu'un seul env
            echo "<td class=\"td-redbackground\"><span>{$this->newEnv}</span></td></tr>";
        } elseif ($this->newEnv === $DEFAULT_ENV) { 
            echo "<td class=\"td-whitebackground\"><span>{$this->newEnv}</span></td></tr>";
        } elseif ($this->newEnv === $LAST_ENV) {
            echo "<td class=\"td-redbackground\"><span>{$this->newEnv}</span></td></tr>";
        } else {
            echo "<td class=\"td-whitebackground\"><span>{$this->newEnv}</span></td></tr>";
        }
        if (!empty($this->description)) {
            echo "<tr>
            <td>Description :</td>
            <td><b>{$this->description}</b></td>
            </tr>";
        }
        echo '</table>';

        /**
         *  2. On vérifie si le repo existe
         */
        if ($OS_FAMILY == "Redhat") {
            if ($this->existsEnv($this->name, $this->env) === false) {
                echo '<p><span class="redtext">Erreur :</span> ce repo n\'existe pas</p>';
                return false;
            }
        }
        if ($OS_FAMILY == "Debian") {
            if ($this->section_existsEnv($this->name, $this->dist, $this->section, $this->env) === false) {
                echo '<p><span class="redtext">Erreur :</span> cette section n\'existe pas</p>';
                return false;
            }
        }

        /**
         *  3. Récupère la date vers laquelle on va faire pointer le nouvel env, la source du repo, son heure, son type et si il est signé ou non et son groupe
         */
        $this->db_getDate();

        if ($OS_FAMILY == "Redhat") {
            $resultSource = $this->db->querySingleRow("SELECT Source from repos WHERE Name = '$this->name' AND Env = '$this->env'");
            $resultTime = $this->db->querySingleRow("SELECT Time from repos WHERE Name = '$this->name' AND Env = '$this->env'");
            $resultSigned = $this->db->querySingleRow("SELECT Signed from repos WHERE Name = '$this->name' AND Env = '$this->env'");
            $resultType = $this->db->querySingleRow("SELECT Type from repos WHERE Name = '$this->name' AND Env = '$this->env'");
            $resultGroupId = $this->db->querySingleRow("SELECT Id_group FROM group_members INNER JOIN repos ON repos.Id = group_members.Id_repo WHERE repos.Name = '$this->name' AND repos.Env = '$this->env'");
        }
        if ($OS_FAMILY == "Debian") {
            $resultSource = $this->db->querySingleRow("SELECT Source from repos WHERE Name = '$this->name' AND Dist = '$this->dist' AND Section = '$this->section' AND Env = '$this->env'");
            $resultTime = $this->db->querySingleRow("SELECT Time from repos WHERE Name = '$this->name' AND Dist = '$this->dist' AND Section = '$this->section' AND Env = '$this->env'");
            $resultSigned = $this->db->querySingleRow("SELECT Signed from repos WHERE Name = '$this->name' AND Dist = '$this->dist' AND Section = '$this->section' AND Env = '$this->env'");
            $resultType = $this->db->querySingleRow("SELECT Type from repos WHERE Name = '$this->name' AND Dist = '$this->dist' AND Section = '$this->section' AND Env = '$this->env'");
            $resultGroupId = $this->db->querySingleRow("SELECT Id_group FROM group_members INNER JOIN repos ON repos.Id = group_members.Id_repo WHERE repos.Name = '$this->name' AND Dist = '$this->dist' AND Section = '$this->section' AND repos.Env = '$this->env'");
        }
        $this->source = $resultSource['Source'];
        $this->time = $resultTime['Time'];
        $this->signed = $resultSigned['Signed'];
        $this->type = $resultType['Type'];
        if (!empty($resultGroupId['Id_group'])) {
            $this->group = $resultGroupId['Id_group'];
        }

        /**
         *  4. Si on n'a pas transmis de description, on va conserver celle actuellement en place sur $this->newEnv si existe. Cependant si il n'y a pas de description ou qu'aucun repo n'existe actuellement dans l'env $this->newEnv alors celle-ci restera vide
         */
        if (empty($this->description)) {
            if ($OS_FAMILY == "Redhat") {
                $result = $this->db->querySingleRow("SELECT Description from repos WHERE Name = '$this->name' AND Env = '$this->newEnv'");
            }
            if ($OS_FAMILY == "Debian") {
                $result = $this->db->querySingleRow("SELECT Description from repos WHERE Name = '$this->name' AND Dist = '$this->dist' AND Section = '$this->section' AND Env = '$this->newEnv'");
            }
            $this->description = $result['Description'];
        }

        /**
         *  5. Dernière vérif : on vérifie que le repo n'est pas déjà dans l'environnement souhaité (par exemple fait par quelqu'un d'autre), dans ce cas on annule l'opération
         */
        if ($OS_FAMILY == "Redhat") {
            if ($this->existsDateEnv($this->name, $this->date, $this->newEnv) === true) {
                echo "<p><span class=\"redtext\">Erreur :</span> ce repo est déjà en $this->newEnv au $this->date</p>";
                return false;
            }
        }
        if ($OS_FAMILY == "Debian") {
            if ($this->section_existsDateEnv($this->name, $this->dist, $this->section, $this->date, $this->newEnv, 'active') === true) {
                echo "<p><span class=\"redtext\">Erreur :</span> cette section est déjà en $this->newEnv au $this->date</p>";
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
             *  Cas 1 : pas de version déjà en $this->newEnv
             */
            if ($this->existsEnv($this->name, $this->newEnv) === false) {

                /**
                 *  Suppression du lien symbolique (on sait jamais si il existe)
                 */
                if (file_exists("${REPOS_DIR}/{$this->name}_{$this->newEnv}")) {
                    unlink("${REPOS_DIR}/{$this->name}_{$this->newEnv}");
                }
    
                /**
                 *  Création du lien symbolique
                 */
                exec("cd ${REPOS_DIR}/ && ln -sfn {$this->dateFormatted}_{$this->name}/ {$this->name}_{$this->newEnv}");
    
                /**
                 *  Mise à jour en BDD
                 */
                $this->db->exec("INSERT INTO repos (Name, Source, Env, Date, Time, Description, Signed, Type) VALUES ('$this->name', '$this->source', '$this->newEnv', '$this->date', '$this->time', '$this->description', '$this->signed', '$this->type')");

                /**
                 *  Récupération de l'ID du repos précédemment inséré car on va en avoir besoin pour l'ajouter au même groupe que le repo sur $this->env
                 */
                $this->id = $this->db->lastInsertRowID();

            /**
             *  Cas 2 : Il y a déjà une version en $this->newEnv qui va donc passer en archive. Modif du lien symbo + passage de la version précédente en archive :
             */
            } else {

                /**
                 *  Suppression du lien symbolique
                 */
                if (file_exists("${REPOS_DIR}/{$this->name}_{$this->newEnv}")) {
                    unlink("${REPOS_DIR}/{$this->name}_{$this->newEnv}");
                }

                /**
                 *  Création du lien symbolique
                 */
                exec("cd ${REPOS_DIR}/ && ln -sfn {$this->dateFormatted}_{$this->name}/ {$this->name}_{$this->newEnv}");

                /**
                 *  Passage de l'ancienne version de $this->newEnv en archive
                 *  Pour cela on récupère la date et la description du repo qui va être archivé
                 */
                $result = $this->db->querySingleRow("SELECT Date FROM repos WHERE Name = '$this->name' AND Env = '$this->newEnv'");
                $old_repoDate = $result['Date'];
                $old_repoDateFormatted = DateTime::createFromFormat('Y-m-d', $old_repoDate)->format('d-m-Y');
                $result = $this->db->querySingleRow("SELECT Description FROM repos WHERE Name = '$this->name' AND Env = '$this->newEnv'");
                $old_repoDescription = $result['Description'];
                
                /**
                 *  Renommage du répertoire en archived_
                 */
                if (!rename("${REPOS_DIR}/${old_repoDateFormatted}_{$this->name}", "${REPOS_DIR}/archived_${old_repoDateFormatted}_{$this->name}")) {
                    echo "<p><span class=\"redtext\">Erreur :</span> un problème est survenu lors du passage de l'ancienne version du $old_repoDateFormatted en archive</p>";
                    return false;
                }

                /**
                 *  Mise à jour de la BDD
                 */
                $this->db->exec("DELETE FROM repos WHERE Name = '$this->name' AND Env = '$this->newEnv' AND Date = '$old_repoDate'");
                $this->db->exec("INSERT INTO repos (Name, Source, Env, Date, Time, Description, Signed, Type) VALUES ('$this->name', '$this->source', '$this->newEnv', '$this->date', '$this->time', '$this->description', '$this->signed', '$this->type')");
                /**
                 *  Récupération de l'ID du repos précédemment inséré car on va en avoir besoin pour l'ajouter au même groupe que le repo sur $this->env
                 */
                $this->id = $this->db->lastInsertRowID();
                $this->db->exec("INSERT INTO repos_archived (Name, Source, Date, Time, Description, Signed, Type) VALUES ('$this->name', '$this->source', '$old_repoDate', '$this->time', '$old_repoDescription', '$this->signed', '$this->type')");

                /**
                 *  Application des droits sur la section archivée
                 */
                exec("find ${REPOS_DIR}/archived_${old_repoDateFormatted}_{$this->name}/ -type f -exec chmod 0660 {} \;");
                exec("find ${REPOS_DIR}/archived_${old_repoDateFormatted}_{$this->name}/ -type d -exec chmod 0770 {} \;");
            }
        }
    
        if ($OS_FAMILY == "Debian") {
            /**
             *  Cas 1 : pas de version déjà en $this->newEnv
             */
            if ($this->section_existsEnv($this->name, $this->dist, $this->section, $this->newEnv) === false) {
                
                /**
                 *  Suppression du lien symbolique (on sait jamais si il existe)
                 */
                if (file_exists("${REPOS_DIR}/{$this->name}/{$this->dist}/{$this->section}_{$this->newEnv}")) {
                    unlink("${REPOS_DIR}/{$this->name}/{$this->dist}/{$this->section}_{$this->newEnv}");
                }

                /**
                 *  Création du lien symbolique
                 */
                exec("cd ${REPOS_DIR}/{$this->name}/{$this->dist}/ && ln -sfn {$this->dateFormatted}_{$this->section}/ {$this->section}_{$this->newEnv}");

                /**
                 *  Mise à jour en BDD
                 */
                $this->db->exec("INSERT INTO repos (Name, Source, Dist, Section, Env, Date, Time, Description, Signed, Type) VALUES ('$this->name', '$this->source', '$this->dist', '$this->section', '$this->newEnv', '$this->date', '$this->time', '$this->description', '$this->signed', '$this->type')");
                
                /**
                 *  Récupération de l'ID du repos précédemment inséré car on va en avoir besoin pour l'ajouter au même groupe que le repo sur $this->env
                 */
                $this->id = $this->db->lastInsertRowID();
            
            /**
             *  Cas 2 : Il y a déjà une version en $this->newEnv qui va donc passer en archive. Modif du lien symbo + passage de la version précédente en archive :
             */
            } else {

                /**
                 *  Suppression du lien symbolique
                 */
                if (file_exists("${REPOS_DIR}/{$this->name}/{$this->dist}/{$this->section}_{$this->newEnv}")) {
                    unlink("${REPOS_DIR}/{$this->name}/{$this->dist}/{$this->section}_{$this->newEnv}");
                }

                /**
                 *  Création du lien symbolique
                 */
                exec("cd ${REPOS_DIR}/{$this->name}/{$this->dist}/ && ln -sfn {$this->dateFormatted}_{$this->section}/ {$this->section}_{$this->newEnv}");

                /**
                 *  Passage de l'ancienne version de $this->newEnv en archive
                 *  Pour cela on récupère la date et la description du repo qui va être archivé
                 */
                $result = $this->db->querySingleRow("SELECT Date FROM repos WHERE Name = '$this->name' AND Dist = '$this->dist' AND Section = '$this->section' AND Env = '$this->newEnv'");
                $old_repoDate = $result['Date'];
                $old_repoDateFormatted = DateTime::createFromFormat('Y-m-d', $old_repoDate)->format('d-m-Y');
                $result = $this->db->querySingleRow("SELECT Description FROM repos WHERE Name = '$this->name' AND Dist = '$this->dist' AND Section = '$this->section' AND Env = '$this->newEnv'");
                $old_repoDescription = $result['Description'];

                /**
                 *  Renommage du répertoire en archived_
                 */
                if (!rename("${REPOS_DIR}/{$this->name}/{$this->dist}/${old_repoDateFormatted}_{$this->section}", "${REPOS_DIR}/{$this->name}/{$this->dist}/archived_${old_repoDateFormatted}_{$this->section}")) {
                    echo "<p><span class=\"redtext\">Erreur :</span> un problème est survenu lors du passage de l'ancienne version du $old_repoDateFormatted en archive</p>";
                    return false;
                }

                /**
                 *  Mise à jour de la BDD
                 */
                $this->db->exec("DELETE FROM repos WHERE Name = '$this->name' AND Dist = '$this->dist' AND Section = '$this->section' AND Env = '$this->newEnv' AND Date = '$old_repoDate'");
                $this->db->exec("INSERT INTO repos (Name, Source, Dist, Section, Env, Date, Time, Description, Signed, Type) VALUES ('$this->name', '$this->source', '$this->dist', '$this->section', '$this->newEnv', '$this->date', '$this->time', '$this->description', '$this->signed', '$this->type')");
                /**
                 *  Récupération de l'ID du repos précédemment inséré car on va en avoir besoin pour l'ajouter au même groupe que le repo sur $this->env
                 */
                $this->id = $this->db->lastInsertRowID();
                $this->db->exec("INSERT INTO repos_archived (Name, Source, Dist, Section, Date, Time, Description, Signed, Type) VALUES ('$this->name', '$this->source', '$this->dist', '$this->section', '$old_repoDate', '$this->time', '$old_repoDescription', '$this->signed', '$this->type')");

                /**
                 *  Application des droits sur la section archivée
                 */
                exec("find ${REPOS_DIR}/{$this->name}/{$this->dist}/archived_${old_repoDateFormatted}_{$this->section}/ -type f -exec chmod 0660 {} \;");
                exec("find ${REPOS_DIR}/{$this->name}/{$this->dist}/archived_${old_repoDateFormatted}_{$this->section}/ -type d -exec chmod 0770 {} \;");
            }
        }

        /**
         *  7. On ajoute le nouvel environnement de repo au même groupe que le repo sur $this->env 
         */
        if (!empty($this->group)) {
            $this->db->exec("INSERT INTO group_members (Id_repo, Id_group) VALUES ('$this->id', '{$this->group}')");
        }

        /**
         *  8. Application des droits sur le repo/la section modifié
         */
        if ($OS_FAMILY == "Redhat") {
            exec("find ${REPOS_DIR}/{$this->dateFormatted}_{$this->name}/ -type f -exec chmod 0660 {} \;");
            exec("find ${REPOS_DIR}/{$this->dateFormatted}_{$this->name}/ -type d -exec chmod 0770 {} \;");
        }

        if ($OS_FAMILY == "Debian") {
            exec("find ${REPOS_DIR}/{$this->name}/{$this->dist}/{$this->dateFormatted}_{$this->section}/ -type f -exec chmod 0660 {} \;");
            exec("find ${REPOS_DIR}/{$this->name}/{$this->dist}/{$this->dateFormatted}_{$this->section}/ -type d -exec chmod 0770 {} \;");          
        }

        echo '<p>Terminé <span class="greentext">✔</span></p>';
    }
}
?>