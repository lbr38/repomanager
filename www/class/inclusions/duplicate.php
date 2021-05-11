<?php
trait duplicate {
    /**
     *  DUPLIQUER UN REPO/SECTION
     */
    public function duplicate() {
        global $REPOS_DIR;
        global $DEFAULT_ENV;
        global $WWW_USER;
        global $OS_FAMILY;

        /** 
         *  1. On vérifie que le nouveau nom du repo n'est pas vide
         */
        if (empty($this->newName)) {
            echo "Erreur : le nouveau nom du repo ne peut être vide";
            return;
        }

        /**
         *  2. Génération du tableau récapitulatif de l'opération
         */
        echo '<table>';
        if ($OS_FAMILY == "Redhat") {
            echo "<tr>
                <td>Nom du repo :</td>
                <td><b>$this->name</b></td>
            </tr>
            <tr>
                <td>Nouveau nom du repo :</td>
                <td><b>$this->newName</b></td>
            </tr>";
        }
        if ($OS_FAMILY == "Debian") {
            echo "<tr>
                <td>Section de repo :</td>
                <td><b>$this->section ($this->section)</b></td>
            </tr>
            <tr>
                <td>Nom du repo :</td>
                <td><b>$this->name</b></td>
            </tr>
            <tr>
                <td>Nouveau nom du repo :</td>
                <td><b>$this->newName</b></td>
            </tr>
            <tr>
                <td>Distribution :</td>
                <td><b>$this->dist</b></td>
            </tr>
            <tr>
                <td>Section :</td>
                <td><b>$this->section</b></td>
            </tr>";
        }
        if (!empty($this->description)) {
        echo "<tr>
        <td>Description :</td>
        <td><b>$this->description</b></td>
        </tr>";
        }
        if (!empty($this->group)) {
        echo "<tr>
        <td>Ajout à un groupe :</td>
        <td><b>$this->group</b></td>
        </tr>";
        }
        echo '</table>';

        /**
         *  3. Vérifications : 
         *  On vérifie que le repo source (celui qui sera dupliqué) existe bien
         *  On vérifie que le nouveau nom du repo n'existe pas déjà
         */
        if ($this->exists($this->name) === false) {
            echo '<br><span class="redtext">Erreur : le repo à dupliquer n\'existe pas</span>';
            return;
        }
        if ($this->exists($this->newName) === true) {
            echo "<br><span class=\"redtext\">Erreur : un repo $this->newName existe déjà</span>";
            return;
        }

        /**
         *  4. On récupère la date et la source du repo qu'on va dupliquer
         */
        // Date
        $this->db_getDate();
        if ($OS_FAMILY == "Redhat") {
            // Source
            $resultSource = $this->db->querySingleRow("SELECT Source FROM repos WHERE Name = '$this->name' AND Env = '$this->env'");
            // Signature 
            $resultSigned = $this->db->querySingleRow("SELECT Signed FROM repos WHERE Name = '$this->name' AND Env = '$this->env'");
        }
        if ($OS_FAMILY == "Debian") {
            // Source
            $resultSource = $this->db->querySingleRow("SELECT Source FROM repos WHERE Name = '$this->name' AND Dist = '$this->dist' AND Section = '$this->section' AND Env = '$this->env'");
            // Signature
            $resultSigned = $this->db->querySingleRow("SELECT Signed FROM repos WHERE Name = '$this->name' AND Dist = '$this->dist' AND Section = '$this->section' AND Env = '$this->env'");
        }
        $this->source = $resultSource['Source'];
        $this->signed = $resultSigned['Signed'];

        /**
         *  4. Création du nouveau répertoire avec le nouveau nom du repo :
         */
        if ($OS_FAMILY == "Redhat") {
            if (!file_exists("${REPOS_DIR}/{$this->dateFormatted}_{$this->newName}")) {
                if (!mkdir("${REPOS_DIR}/{$this->dateFormatted}_{$this->newName}", 0770, true)) {
                echo "<br><span class=\"redtext\">Erreur : impossible de créer le répertoire ${REPOS_DIR}/{$this->newName}</span>";
                return;
                }
            }
        }
        if ($OS_FAMILY == "Debian") {
            if (!file_exists("${REPOS_DIR}/{$this->newName}/{$this->dist}")) {
                if (!mkdir("${REPOS_DIR}/{$this->newName}/{$this->dist}", 0770, true)) {
                    echo "<br><span class=\"redtext\">Erreur : impossible de créer le répertoire ${REPOS_DIR}/{$this->newName}/{$this->dist}</span>";
                    return;
                }
            }
        }

        /**
         *  5. Copie du contenu du repo/de la section
         *  Anti-slash devant la commande cp pour forcer l'écrasement
         */
        if ($OS_FAMILY == "Redhat") {
            exec("\cp -r ${REPOS_DIR}/{$this->dateFormatted}_{$this->name} ${REPOS_DIR}/{$this->dateFormatted}_{$this->newName}", $output, $result);
        }
        if ($OS_FAMILY == "Debian") {
            exec("\cp -r ${REPOS_DIR}/{$this->name}/{$this->dist}/{$this->dateFormatted}_{$this->section} ${REPOS_DIR}/{$this->newName}/{$this->dist}/{$this->dateFormatted}_{$this->section}", $output, $result);
        }
        if ($result != 0) {
            echo '<br><span class="redtext">Erreur lors de la copie du répertoire</span>';
            return;
        }

        /**
         *   6. Création du lien symbolique
         */
        if ($OS_FAMILY == "Redhat") {
            exec("cd ${REPOS_DIR}/ && ln -s {$this->dateFormatted}_{$this->newName}/ {$this->newName}_${DEFAULT_ENV}", $output, $result);            
        }
        if ($OS_FAMILY == "Debian") {
            exec("cd ${REPOS_DIR}/{$this->newName}/{$this->dist}/ && ln -s {$this->dateFormatted}_{$this->section}/ {$this->section}_${DEFAULT_ENV}", $output, $result);
        }
        if ($result != 0) {
            echo '<br><span class="redtext">Erreur lors de la création du lien symbolique</span>';
            return;
        }

        /**
         *  7. Insertion en BDD du nouveau repo
         */
        if ($OS_FAMILY == "Redhat") {
            $this->db->exec("INSERT INTO repos (Name, Source, Env, Date, Time, Description, Signed, Type) VALUES ('$this->newName', '$this->source', '$this->env', '$this->date', '$this->time', '$this->description', '$this->signed', 'mirror')");
        }
        if ($OS_FAMILY == "Debian") {
            $this->db->exec("INSERT INTO repos (Name, Source, Dist, Section, Env, Date, Time, Description, Signed, Type) VALUES ('$this->newName', '$this->source', '$this->dist', '$this->section', '$this->env', '$this->date', '$this->time', '$this->description', '$this->signed', 'mirror')");
        }

        /**
         *  8. Application des droits sur le nouveau repo créé
         */
        exec("find ${REPOS_DIR}/{$this->newName}/ -type f -exec chmod 0660 {} \;");
        exec("find ${REPOS_DIR}/{$this->newName}/ -type d -exec chmod 0770 {} \;");
        exec("chown -R ${WWW_USER}:repomanager ${REPOS_DIR}/{$this->newName}/");

        /**
         *  9. Ajout de la section à un groupe si un groupe a été renseigné
         */
        if (!empty($this->group)) {
            if ($OS_FAMILY == "Redhat") {
                $result = $this->db->query("SELECT repos.Id AS repoId, groups.Id AS groupId FROM repos, groups WHERE repos.Name = '$this->newName' AND groups.Name = '$this->group'");
            }
            if ($OS_FAMILY == "Debian") {
                $result = $this->db->query("SELECT repos.Id AS repoId, groups.Id AS groupId FROM repos, groups WHERE repos.Name = '$this->newName' AND repos.Dist = '$this->dist' AND repos.Section = '$this->section' AND groups.Name = '$this->group'");
            }

            while ($data = $result->fetchArray()) {
                $repoId = $data['repoId'];
                $groupId = $data['groupId'];
            }

            if (empty($repoId)){
                echo "<br><span class=\"redtext\">Erreur : impossible de récupérer l'Id du repo $this->newName</span>";
                return;
            }

            if (empty($groupId)) {
                echo "<br><span class=\"redtext\">Erreur : impossible de récupérer l'Id du groupe $this->group</span>";
                return;
            }
            $this->db->exec("INSERT INTO group_members (Id_repo, Id_group) VALUES ('$repoId', '$groupId')");
        }

        /**
         *  10. Génération du fichier de conf repo en local (ces fichiers sont utilisés pour les profils)
         *  Pour les besoins de la fonction, on set $this->name = $this->newName (sinon ça va générer un fichier pour le repo source, ce qu'on ne veut pas)
         */
        $this->name = $this->newName;
        $this->generateConf('default');

        echo '<p>Dupliqué <span class="greentext">✔</span></p>';
    }
}
?>