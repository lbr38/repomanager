<?php

class Group extends Model {

    public $id; // Id en BDD
    public $name;

    public function __construct(string $type) {
        /**
         *  Cette class permet de manipuler des groupes de repos ou d'hôtes. 
         *  Selon ce qu'on souhaite traiter, la base de données n'est pas la même.
         *  Si on a renseigné une base de données au moment de l'instanciation d'un objet Group alors on utilise cette base
         *  Sinon par défaut on utilise la base principale de repomanager
         */

        if ($type != 'repo' AND $type != 'host') {
            throw new Exception("Le type de groupe est invalide");
        }

        if ($type == 'host') {
            $this->getConnection('hosts', 'rw');
        }
        if ($type == 'repo') {
            $this->getConnection('main', 'rw');
        }

        $this->type = $type;
    }

    public function setId(string $id)
    {
        $this->id = Common::validateData($id);
    }


    /**
     *  CREER UN GROUPE
     *  Permets de créer un groupe de repo ou un groupe d'hôtes
     *  @param name
     *  @param type
     */
    public function new(string $name)
    {
        $name = Common::validateData($name);

        /**
         *  1. On vérifie que le nom du groupe ne contient pas de caractères interdits
         */
        if (Common::is_alphanumdash($name) === false) {
            throw new Exception("Le groupe <b>$name</b> contient des caractères invalides");
        }

        /**
         *  2. On vérifie que le groupe n'existe pas déjà
         */
        if ($this->exists($name) === true) {
            throw new Exception("Le groupe <b>$name</b> existe déjà");
        }

        /**
         *  3. Insertion du nouveau groupe
         */
        try {
            $stmt = $this->db->prepare("INSERT INTO groups (Name) VALUES (:name)");
            $stmt->bindValue(':name', $name);
            $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

        History::set($_SESSION['username'], "Création d'un nouveau groupe $name (type : $this->type)", 'success');

        Common::clearCache();
    }

    /**
     *  RENOMMER UN GROUPE
     */
    public function rename(string $actualName, string $newName) {
        /**
         *  1. On vérifie que le nom du groupe ne contient pas des caractères interdits
         */
        if (Common::is_alphanumdash($actualName) === false) {
            throw new Exception("Le nom actuel du groupe <b>$actualName</b> contient des caractères invalides");
        }
        if (Common::is_alphanumdash($newName) === false) {
            throw new Exception("Le nouveau nom du groupe <b>$newName</b> contient des caractères invalides");
        }

        /**
         *  2. On vérifie que le nouveau nom de groupe n'existe pas déjà
         */
        try {
            $stmt = $this->db->prepare("SELECT * FROM groups WHERE Name = :newname");
            $stmt->bindValue(':newname', $newName);
            $result = $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

        /**
         *  Si le résultat n'est pas vide alors le groupe existe déjà
         */
        if (!$this->db->isempty($result)) {
            throw new Exception("Le groupe <b>$newName</b> existe déjà");
        }

        /**
         *  3. Renommage du groupe
         */
        try {
            $stmt = $this->db->prepare("UPDATE groups SET Name = :newname WHERE Name = :actualname");
            $stmt->bindValue(':newname', $newName);
            $stmt->bindValue(':actualname', $actualName);
            $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

        History::set($_SESSION['username'], "Renommage d'un groupe : $actualName en $newName (type : $this->type)", 'success');

        Common::clearCache();
    }

    /**
     *  SUPPRIMER UN GROUPE
     */
    public function delete(string $name) {
        /**
         *  1. On vérifie que le groupe existe
         */
        try {
            $stmt = $this->db->prepare("SELECT * FROM groups WHERE Name = :name");
            $stmt->bindValue(':name', $name);
            $result = $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

        /**
         *  Compte le nombre de lignes retournées, si il a 0 ligne alors le groupe n'existe pas
         */
        $count = 0;
        while ($row = $result->fetchArray()) {
            $count++;  
        }
    
        if ($count == 0) {
            throw new Exception("Le groupe <b>$name</b> n'existe pas");
        }

        /**
         *  2. Supprime toutes les entrées concernant ce groupe dans group_members afin que les repos repassent sur le groupe par défaut
         */
        try {
            $stmt = $this->db->prepare("DELETE FROM group_members WHERE Id_group IN (SELECT Id FROM groups WHERE Name = :name)");
            $stmt->bindValue(':name', $name);
            $result = $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

        /**
         *  3. Suppression du groupe
         */
        try {
            $stmt = $this->db->prepare("DELETE FROM groups WHERE Name = :name");
            $stmt->bindValue(':name', $name);
            $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

        History::set($_SESSION['username'], "Suppression du groupe $name (type : $this->type)", 'success');

        Common::clearCache();
    }

    /**
     *  LISTER TOUS LES REPOS D'UN GROUPE
     */
    public function listRepos(string $groupName) {
        /**
         *  Si le groupe est 'Default' (groupe fictif) alors on affiche tous les repos n'ayant pas de groupe 
         */
        if ($groupName == 'Default') {
            if (OS_FAMILY == "Redhat") {
                $reposInGroup = $this->db->query("SELECT * FROM repos
                WHERE Status = 'active' AND Id NOT IN (SELECT Id_repo FROM group_members)
                ORDER BY repos.Name ASC, repos.Env ASC");
            }
            if (OS_FAMILY == "Debian") {
                $reposInGroup = $this->db->query("SELECT * FROM repos
                WHERE Status = 'active' AND Id NOT IN (SELECT Id_repo FROM group_members)
                ORDER BY repos.Name ASC, repos.Dist ASC, repos.Section ASC, repos.Env ASC");
            }
            
        } else {
            if (OS_FAMILY == "Redhat") {
                // Note : ne pas utiliser SELECT *, comme il s'agit d'une jointure il faut bien préciser les données souhaitées
                $stmt = $this->db->prepare("SELECT repos.Id, repos.Name, repos.Source, repos.Env, repos.Date, repos.Time, repos.Description, repos.Type, repos.Signed
                FROM repos
                INNER JOIN group_members
                    ON repos.Id = group_members.Id_repo
                INNER JOIN groups
                    ON groups.Id = group_members.Id_group
                WHERE groups.Name=:groupname
                AND repos.Status = 'active'
                ORDER BY repos.Name ASC, repos.Env ASC");
                $stmt->bindValue(':groupname', $groupName);
                $reposInGroup = $stmt->execute();
            }
            if (OS_FAMILY == "Debian") {
                // Note : ne pas utiliser SELECT *, comme il s'agit d'une jointure il faut bien préciser les données souhaitées
                $stmt = $this->db->prepare("SELECT repos.Id, repos.Name, repos.Source, repos.Dist, repos.Section, repos.Env, repos.Date, repos.Time, repos.Description, repos.Type, repos.Signed
                FROM repos
                INNER JOIN group_members
                    ON repos.Id = group_members.Id_repo
                INNER JOIN groups
                    ON groups.Id = group_members.Id_group
                WHERE groups.Name=:groupname
                AND repos.Status = 'active'
                ORDER BY repos.Name ASC, repos.Dist ASC, repos.Section ASC, repos.Env ASC");
                $stmt->bindValue(':groupname', $groupName);
                $reposInGroup = $stmt->execute();
            }

            unset($stmt);
        }

        while ($datas = $reposInGroup->fetchArray(SQLITE3_ASSOC)) $reposIn[] = $datas;

        if (!empty($reposIn)) {
            return $reposIn;
        }
    }

    /**
     *  LISTER TOUS LES REPOS MEMBRES D'UN GROUPE EN FILTRANT PAR ENVIRONNEMENT
     */
    public function listReposMembers_byEnv(string $groupName, string $env) {
        if (OS_FAMILY == "Redhat") {
            $stmt = $this->db->prepare("SELECT DISTINCT repos.Id, repos.Name
            FROM repos
            INNER JOIN group_members
                ON repos.Id = group_members.Id_repo
            INNER JOIN groups
                ON groups.Id = group_members.Id_group
            WHERE groups.Name=:groupname
            AND repos.Env=:env
            AND repos.Status = 'active'
            ORDER BY repos.Name ASC");
        }
        if (OS_FAMILY == "Debian") {
            $stmt = $this->db->prepare("SELECT DISTINCT repos.Id, repos.Name, repos.Dist, repos.Section
            FROM repos
            INNER JOIN group_members
                ON repos.Id = group_members.Id_repo
            INNER JOIN groups
                ON groups.Id = group_members.Id_group
            WHERE groups.Name=:groupname
            AND repos.Env=:env
            AND repos.Status = 'active'
            ORDER BY repos.Name ASC, repos.Dist ASC");
        }
        $stmt->bindValue(':env', $env);
        $stmt->bindValue(':groupname', $groupName);
        $reposInGroup = $stmt->execute();
        unset($stmt);

        $reposIn = array();

        while ($datas = $reposInGroup->fetchArray(SQLITE3_ASSOC)) $reposIn[] = $datas;

        return $reposIn;
    }

    /**
     *  LISTER (Select) LES REPOS D'UN GROUPE
     *  On fait un DISTINCT ici car un repo peut avoir plusieurs environnements et donc apparaitre en double, ce qu'on ne veut pas
     */
    public function selectRepos(string $groupName) {
        if (OS_FAMILY == "Redhat") {
            $stmt = $this->db->prepare("SELECT DISTINCT repos.Name
            FROM repos
            INNER JOIN group_members
                ON repos.Id = group_members.Id_repo
            INNER JOIN groups
                ON groups.Id = group_members.Id_group
            WHERE groups.Name=:groupname
            AND repos.Status = 'active'");
            $stmt->bindValue(':groupname', $groupName);
            $reposInGroup = $stmt->execute();

            /*$reposNotInGroup = $this->db->query("SELECT DISTINCT repos.Name
            FROM repos
            INNER JOIN group_members
                ON repos.Id = group_members.Id_repo
            INNER JOIN groups
                ON groups.Id = group_members.Id_group
            WHERE groups.Name != '$groupName'");*/

            $reposNotInAnyGroup = $this->db->query("SELECT DISTINCT repos.Name
            FROM repos
            WHERE repos.Status = 'active' AND repos.Id NOT IN (SELECT Id_repo FROM group_members);");
        }
        if (OS_FAMILY == "Debian") {
            $stmt = $this->db->prepare("SELECT DISTINCT repos.Name, repos.Dist, repos.Section
            FROM repos
            INNER JOIN group_members
                ON repos.Id = group_members.Id_repo
            INNER JOIN groups
                ON groups.Id = group_members.Id_group
            WHERE groups.Name=:groupname
            AND repos.Status = 'active'");
            $stmt->bindValue(':groupname', $groupName);
            $reposInGroup = $stmt->execute();

            /*$reposNotInGroup = $this->db->query("SELECT DISTINCT repos.Name, repos.Dist, repos.Section
            FROM repos
            INNER JOIN group_members
                ON repos.Id = group_members.Id_repo
            INNER JOIN groups
                ON groups.Id = group_members.Id_group
            WHERE groups.Name != '$groupName'");*/

            $reposNotInAnyGroup = $this->db->query("SELECT DISTINCT repos.Name, repos.Dist, repos.Section
            FROM repos
            WHERE repos.Status = 'active' AND repos.Id NOT IN (SELECT Id_repo FROM group_members);");
        }

        unset($stmt);

        while ($datas = $reposInGroup->fetchArray(SQLITE3_ASSOC)) $reposIn[] = $datas;
        while ($datas = $reposNotInAnyGroup->fetchArray(SQLITE3_ASSOC)) $reposNotIn[] = $datas;
        
        echo '<select class="reposSelectList" groupname="'.$groupName.'" name="groupAddRepoName[]" multiple>';
        if (!empty($reposIn)) {
            foreach($reposIn as $repo) {
                $repoName = $repo['Name'];
                if (OS_FAMILY == "Debian") {
                    $repoDist = $repo['Dist'];
                    $repoSection = $repo['Section'];
                }
                if (OS_FAMILY == "Redhat") echo "<option value=\"$repoName\" selected>$repoName</option>";
                if (OS_FAMILY == "Debian") echo "<option value=\"$repoName|$repoDist|$repoSection\" selected>$repoName - $repoDist - $repoSection</option>";
            }
        }
        if (!empty($reposNotIn)) {
            foreach($reposNotIn as $repo) {
                $repoName = $repo['Name'];
                if (OS_FAMILY == "Debian") {
                    $repoDist = $repo['Dist'];
                    $repoSection = $repo['Section'];
                }
                if (OS_FAMILY == "Redhat") echo "<option value=\"$repoName\">$repoName</option>";
                if (OS_FAMILY == "Debian") echo "<option value=\"$repoName|$repoDist|$repoSection\">$repoName - $repoDist - $repoSection</option>";
            }
        }
        echo '</select>';  
        unset($reposInGroup, $reposNotInGroup, $datas, $reposIn, $reposNotIn);
        return;
    }

    /**
     *  AJOUTER / SUPPRIMER DES REPOS/SECTIONS D'UN GROUPE
     */
    public function addRepo(string $groupName, $repoNames) {
        /**
         *  1. Récupération des Id actuellement dans le groupe
         *  2. Suppression des Id actuellement dans le groupe qui ne sont pas dans l'array transmis $repoNames 
         *  3. Insertion des Id des repo transmis
         */

        /**
         *  1. Récupération de l'Id du groupe dans lequel on va ajouter les repos
         */
        $stmt = $this->db->prepare("SELECT Id FROM groups WHERE Name=:name");
        $stmt->bindValue(':name', $groupName);
        $result = $stmt->execute();
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $groupId = $row['Id'];
        }

        /**
         *  2. On traite chaque repo sélectionnés si il y en a
         */
        $reposId = array();

        if (!empty($repoNames)) {
            foreach ($repoNames as $repoName) {
                $repoName = Common::validateData($repoName);
                // Sur debian, $repoName contient le nom du repo, de la dist et de la section séparés par un |
                if (OS_FAMILY == "Debian") {
                    $repoNameExplode = explode('|', $repoName);
                    $repoName = $repoNameExplode[0];
                    $repoDist = $repoNameExplode[1];
                    $repoSection = $repoNameExplode[2];
                }
                /**
                 *  On vérifie que le nom du repo ne contient pas des caractères interdits
                 */
                if (Common::is_alphanumdash($repoName, array('.')) === false) {
                    throw new Exception("Le nom du repo <b>$repoName</b> contient des caractères invalides");
                }

                /**
                 *  Sur Debian on vérifie aussi que le nom de la dist et de la section ne contiennent pas de caractères interdits
                 *  On autorise le slash '/' dans le nom de la distribution
                 */
                if (OS_FAMILY == "Debian") {
                    if (Common::is_alphanumdash($repoDist, array('/')) === false) {
                        throw new Exception("Le nom de la distribution <b>$repoDist</b> contient des caractères invalides");
                    }
                    if (Common::is_alphanumdash($repoSection) === false) {
                        throw new Exception("Le nom de la section <b>$repoSection</b> contient des caractères invalides");
                    }
                }

                /**
                 *  Récupération à partir de la BDD de l'id du repo à ajouter. Il peut y avoir plusieurs Id si le repo a plusieurs environnements.
                 */
                if (OS_FAMILY == "Redhat") {
                    $stmt = $this->db->prepare("SELECT Id FROM repos WHERE Name=:reponame AND Status = 'active'");
                    $stmt->bindValue(':reponame', $repoName);
                    $result = $stmt->execute();
                }
                if (OS_FAMILY == "Debian") {
                    $stmt = $this->db->prepare("SELECT Id FROM repos WHERE Name=:reponame AND Dist=:repodist AND Section=:reposection AND Status = 'active'");
                    $stmt->bindValue(':reponame', $repoName);
                    $stmt->bindValue(':repodist', $repoDist);
                    $stmt->bindValue(':reposection', $repoSection);
                    $result = $stmt->execute();
                }
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    $repoId = $row['Id'];

                    /**
                     *  Insertion en BDD de l'ID du repo (il peut y avoir 1 ou plusieurs Id à insérer si le repo a plusieurs environnements)
                     *  Le format de cet INSERT est fait de sorte à ne pas insérer un Id_repo si celui-ci est déjà présent en BDD
                     */
                    $stmt = $this->db->prepare("INSERT INTO group_members (Id_repo, Id_group)
                    SELECT :idrepo, :idgroup WHERE not exists(SELECT * from group_members where Id_repo=:idrepo AND Id_group=:idgroup)");
                    $stmt->bindValue(':idrepo', $repoId);
                    $stmt->bindValue(':idgroup', $groupId);
                    $stmt->execute();

                    /**
                     *  On stocke dans reposId[] TOUS les Id des repos sélectionnés (tout environnements confondus) car on va en avoir besoin par la suite
                     */
                    $reposId[] = $repoId;
                }
            }
        }

        /**
         *  3. On récupère la liste des repos actuellement dans le groupe afin de supprimer ceux qui n'ont pas été sélectionnés
         */
        $stmt = $this->db->prepare("SELECT Id_repo FROM group_members WHERE Id_group=:idgroup");
        $stmt->bindValue(':idgroup', $groupId);
        $result = $stmt->execute();
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $actualReposId[] = $row['Id_repo'];
        }
    
        /**
         *  4. Suppression des repos qui n'ont pas été sélectionnés
         */
        foreach ($actualReposId as $actualRepoId) {
            if (!in_array($actualRepoId, $reposId)) {
                $stmt = $this->db->prepare("DELETE FROM group_members WHERE Id_repo=:idrepo AND Id_group=:idgroup");
                $stmt->bindValue(':idrepo', $actualRepoId);
                $stmt->bindValue(':idgroup', $groupId);
                $stmt->execute();
            }
        }

        History::set($_SESSION['username'], "Modifications des repos/sections membres du groupe $groupName", 'success');

        Common::clearCache();
    }

    /**
     *  LISTER TOUS LES SERVEURS D'UN GROUPE
     */
    public function listHosts() {
        /**
         *  Si le nom du groupe est 'Default' (groupe fictif) alors on affiche tous les hotes n'ayant pas de groupe 
         */
        if ($this->name == 'Default') {
            $hostsInGroup = $this->db->query("SELECT * FROM hosts
            WHERE Id NOT IN (SELECT Id_host FROM group_members)
            AND Status = 'active'
            ORDER BY hosts.Hostname ASC");
            
        } else {
            // Note : ne pas utiliser SELECT *, comme il s'agit d'une jointure il faut bien préciser les données souhaitées
            $stmt = $this->db->prepare("SELECT
            hosts.Id,
            hosts.Ip,
            hosts.Hostname,
            hosts.Os,
            hosts.Os_version,
            hosts.Profile,
            hosts.Env,
            hosts.Online_status,
            hosts.Online_status_date,
            hosts.Online_status_time,
            hosts.Status
            FROM hosts
            INNER JOIN group_members
                ON hosts.Id = group_members.Id_host
            INNER JOIN groups
                ON groups.Id = group_members.Id_group
            WHERE groups.Name=:groupname
            AND hosts.Status = 'active'
            ORDER BY hosts.Hostname ASC");
            $stmt->bindValue(':groupname', $this->name);
            $hostsInGroup = $stmt->execute();
            unset($stmt);
        }

        $hostsIn = array();
        while ($datas = $hostsInGroup->fetchArray(SQLITE3_ASSOC)) $hostsIn[] = $datas;

        return $hostsIn;
    }

    /**
     *  LISTER (Select) LES SERVEURS D'UN GROUPE
     */
    public function selectServers(string $groupName) {
        /**
         *  Liste des hotes actuellement dans le groupe $groupName
         */
        $stmt = $this->db->prepare("SELECT hosts.Id, hosts.Hostname, hosts.Ip
        FROM hosts
        INNER JOIN group_members
            ON hosts.Id = group_members.Id_host
        INNER JOIN groups
            ON groups.Id = group_members.Id_group
        WHERE groups.Name=:groupname");
        $stmt->bindValue(':groupname', $groupName);
        $hostsInGroup = $stmt->execute();
        unset($stmt);

        /**
         *  Liste des hotes n'appartenant pas à $groupName
         */
        $hostsNotInAnyGroup = $this->db->query("SELECT DISTINCT hosts.Id, hosts.Hostname, hosts.Ip
        FROM hosts
        WHERE hosts.Id NOT IN (SELECT Id_host FROM group_members);");    

        $hostsIn    = array();
        $hostsNotIn = array();

        while ($datas = $hostsInGroup->fetchArray(SQLITE3_ASSOC)) $hostsIn[] = $datas;
        while ($datas = $hostsNotInAnyGroup->fetchArray(SQLITE3_ASSOC)) $hostsNotIn[] = $datas;
        
        echo '<select class="hostsSelectList" groupname="'.$groupName.'" name="groupAddServerId[]" multiple>';
        if (!empty($hostsIn)) {
            foreach($hostsIn as $host) {
                $hostIp   = $host['Ip'];
                
                echo '<option value="'.$host['Id'].'" selected>'.$host['Hostname'].' ('.$host['Ip'].')</option>';
            }
        }
        if (!empty($hostsNotIn)) {
            foreach($hostsNotIn as $host) {
                $hostName = $host['Hostname'];
                $hostIp   = $host['Ip'];

                echo '<option value="'.$host['Id'].'">'.$host['Hostname'].' ('.$host['Ip'].')</option>';
            }
        }
        echo '</select>';  
        unset($hostsInGroup, $hostsNotInGroup, $datas, $hostsIn, $hostsNotIn);
        return;
    }

    /**
    *  AJOUTER / SUPPRIMER DES SERVEURS D'UN GROUPE
    */
    public function addHost(string $groupName, $hostsId) {
        /**
         *  1. Récupération des Id actuellement dans le groupe
         *  2. Suppression des Id actuellement dans le groupe qui ne sont pas dans l'array transmis $hostsList
         *  3. Insertion des Id des repo transmis
         */

        /**
         *  1. Récupération de l'Id du groupe dans lequel on va ajouter les repos
         */
        try {
            $stmt = $this->db->prepare("SELECT Id FROM groups WHERE Name = :name");
            $stmt->bindValue(':name', $groupName);
            $result = $stmt->execute();
        } catch (Exception $e) {
            Common::dbError($e);
        }

        if ($this->db->isempty($result)) {
            throw new Exception("Impossible de récupérer l'Id du groupe $groupName");
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) $groupId = $row['Id'];

        /**
         *  2. On traite chaque hote sélectionnés
         */
        if (!empty($hostsId)) {
            foreach ($hostsId as $hostId) {
                /**     
                 *  Si l'id n'est pas un chiffre alors on passe au suivant
                 */
                if (!is_numeric($hostId)) {
                    throw new Exception("L'Id de l'hôte est invalide");
                }

                /**
                 *  Insertion en BDD de l'ID de l'hote
                 *  Le format de cet INSERT est fait de sorte à ne pas insérer un Id_host si celui-ci est déjà présent en BDD
                 */
                try {
                    $stmt = $this->db->prepare("INSERT INTO group_members (Id_host, Id_group)
                    SELECT :idhost, :idgroup WHERE not exists(SELECT * from group_members where Id_host = :idhost AND Id_group = :idgroup)");
                    $stmt->bindValue(':idhost', $hostId);
                    $stmt->bindValue(':idgroup', $groupId);
                    $stmt->execute();
                } catch (Exception $e) {
                    Common::dbError($e);
                }
            }
        }

        /**
         *  3. On récupère la liste des hotes actuellement dans le groupe afin de supprimer ceux qui n'ont pas été sélectionnés
         */
        try {
            $stmt = $this->db->prepare("SELECT Id_host FROM group_members WHERE Id_group = :idgroup");
            $stmt->bindValue(':idgroup', $groupId);
            $result = $stmt->execute();
            //$actualHostsId = array();
        } catch (Exception $e) {
            Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) $actualHostsId[] = $row['Id_host'];

        /**
         *  4. Suppression des repos qui n'ont pas été sélectionnés
         */
        foreach ($actualHostsId as $actualHostId) {
            if (!in_array($actualHostId, $hostsId)) {
                try {
                    $stmt = $this->db->prepare("DELETE FROM group_members WHERE Id_host = :idhost AND Id_group = :idgroup");
                    $stmt->bindValue(':idhost', $actualHostId);
                    $stmt->bindValue(':idgroup', $groupId);
                    $stmt->execute();
                } catch (Exception $e) {
                    Common::dbError($e);
                }
            }
        }
    }

    /**
     *  Supprime dans les groupes les repos/sections qui n'existent plus
     */
    public function cleanRepos() {
        $this->db->exec("DELETE FROM group_members WHERE Id_repo NOT IN (SELECT Id FROM repos)");
    }

    /**
     *  Supprime dans les groupes les hotes qui n'existent plus
     */
    public function cleanServers() {
        $this->db->exec("DELETE FROM group_members WHERE Id_host NOT IN (SELECT Id FROM hosts)");
    }

    /**
     *  Recupère le nom du groupe à partir de son ID en BDD
     */
    public function db_getName() {
        $stmt = $this->db->prepare("SELECT Name from groups WHERE Id=:id");
        $stmt->bindValue(':id', $this->id);
        $result = $stmt->execute();
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $this->name = $row['Name'];
        }

        unset($stmt);
    }

    /**
     *  LISTER LES INFORMATIONS DE TOUS LES GROUPES
     *  Sauf le groupe par défaut
     */
    public function listAll() {
        $result = $this->db->query("SELECT * FROM groups");
        while ($datas = $result->fetchArray(SQLITE3_ASSOC)) $group[] = $datas;
        if (!empty($group)) {
            return $group;
        }
    }

    /**
     *  LISTER TOUS LES NOMS DE GROUPES
     *  Sauf le groupe par défaut
     */
    public function listAllName() {
        $query = $this->db->query("SELECT * FROM groups");
        while ($datas = $query->fetchArray(SQLITE3_ASSOC)) { 
            $group[] = $datas['Name'];
        }
        /**
         *  Retourne un array avec les noms des groupes
         */
        if (!empty($group)) {
            return $group;
        }
    }

    /**
     *  LISTER TOUS LES NOMS DE GROUPES
     *  Avec le groupe par défaut
     */
    public function listAllWithDefault() {
        $query = $this->db->query("SELECT * FROM groups");
        
        $groups = array();
        while ($datas = $query->fetchArray(SQLITE3_ASSOC)) $groups[] = $datas['Name'];

        // On ajoute le groupe par défaut (groupe fictif) à la suite
        $groups[] = 'Default';

        /**
         *  Retourne un array avec les noms des groupes
         */
        return $groups;
    }


/**
 *  VERIFICATIONS
 */
    /**
     *  Vérifie que l'Id du groupe existe en BDD
     *  Retourne true si existe
     *  Retourne false si n'existe pas
     */
    public function existsId() {
        $stmt = $this->db->prepare("SELECT * FROM groups WHERE Id=:id");
        $stmt->bindValue(':id', $this->id);
        $result = $stmt->execute();

        if ($this->db->isempty($result) === true)
            return false;
        else
            return true;
    }

    /**
     *  Vérifie si le groupe existe
     *  Si on passe un argument à cette fonction ($name) alors c'est cet argument qui est testé, sinon c'est $this->name
     */
    public function exists(string $name = '') {
        $stmt = $this->db->prepare("SELECT * FROM groups WHERE Name=:name");
        if (!empty($name)) {
            $stmt->bindValue(':name', $name);
        } else {
            $stmt->bindValue(':name', $this->name);
        }
        $result = $stmt->execute();

        if ($this->db->isempty($result) === true)
            return false;
        else
            return true;
    }
}
?>