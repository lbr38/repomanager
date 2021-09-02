<?php
global $WWW_DIR;
require_once("${WWW_DIR}/class/Database.php");

class Group {
    private $db;
    public $id; // Id en BDD
    public $name;

    public function __construct(array $variables = []) {
        extract($variables);

        /**
         *  Instanciation d'une db car on peut avoir besoin de récupérer certaines infos en BDD
         */
        try {
            $this->db = new Database();
        } catch(Exception $e) {
            die('Erreur : '.$e->getMessage());
        }

        /* Id */
        if (!empty($groupId)) { $this->id = $groupId; }
        /* Nom */
        if (!empty($groupName)) { $this->name = $groupName; }
    }

/**
 *  CREER UN GROUPE
 */
    public function new(string $name) {
        /**
         *  1. On vérifie que le nom du groupe ne contient pas des caractères interdits
         */
        if (!is_alphanum($name)) {
            animatediv_byid('groupsDiv');
            return;
        }

        /**
         *  2. On vérifie que le groupe n'existe pas déjà
         */
        if ($this->exists($name) === true) {
            printAlert("Le groupe <b>${name}</b> existe déjà");
            animatediv_byid('groupsDiv');
            return;
        }

        /**
         *  3. Insertion du nouveau groupe
         */
        $stmt = $this->db->prepare("INSERT INTO groups (Name) VALUES (:name)");
        $stmt->bindValue(':name', $name);
        $stmt->execute();

        printAlert("Le groupe <b>${name}</b> a été créé");
        animatediv_byid('groupsDiv');

        unset($stmt, $result);
    }

/**
 *  RENOMMER UN GROUPE
 */
    public function rename(string $actualName, string $newName) {
        /**
         *  1. On vérifie que le nom du groupe ne contient pas des caractères interdits
         */
        if (!is_alphanum($actualName) OR !is_alphanum($newName)) {
            animatediv_byid('groupsDiv');
            return;
        }

        /**
         *  1. On traite seulement si le nouveau nom est différent de l'actuel
         */
        if ($newName == $actualName) {
            return;
        }

        /**
         *  2. On vérifie que le nouveau nom de groupe n'existe pas déjà
         */
        $stmt = $this->db->prepare("SELECT * FROM groups WHERE Name=:newname");
        $stmt->bindValue(':newname', $newName);
        $result = $stmt->execute();

        /**
         *  Compte le nombre de lignes retournées, si il a +0 ligne alors le groupe existe déjà
         */
        $count = 0;
        while ($row = $result->fetchArray()) {
            $count++;
        }
    
        if ($count > 0) {
            printAlert("Le groupe <b>$newName</b> existe déjà");
            animatediv_byid('groupsDiv');
            return;
        }

        /**
         *  3. Renommage du groupe
         */
        $stmt = $this->db->prepare("UPDATE groups SET Name=:newname WHERE Name=:actualname");
        $stmt->bindValue(':newname', $newName);
        $stmt->bindValue(':actualname', $actualName);
        $result = $stmt->execute();

        printAlert("Le groupe <b>$actualName</b> a été renommé en <b>$newName</b>");
        animatediv_byid('groupsDiv');

        unset($stmt, $result);
    }

/**
 *  SUPPRIMER UN GROUPE
 */
    public function delete(string $name) {
        /**
         *  1. On vérifie que le groupe existe
         */
        $stmt = $this->db->prepare("SELECT * FROM groups WHERE Name=:name");
        $stmt->bindValue(':name', $name);
        $result = $stmt->execute();

        /**
         *  Compte le nombre de lignes retournées, si il a 0 ligne alors le groupe n'existe pas
         */
        $count = 0;
        while ($row = $result->fetchArray()) {
            $count++;  
        }
    
        if ($count == 0) {
            printAlert("Le groupe <b>$name</b> n'existe pas");
            animatediv_byid('groupsDiv');
            return;
        }

        /**
         *  2. Supprime toutes les entrées concernant ce groupe dans group_members afin que les repos repassent sur le groupe par défaut
         */
        $stmt = $this->db->prepare("DELETE FROM group_members WHERE Id_group IN (SELECT Id FROM groups WHERE Name=:name)");
        $stmt->bindValue(':name', $name);
        $result = $stmt->execute();

        /**
         *  3. Suppression du groupe
         */
        $stmt = $this->db->prepare("DELETE FROM groups WHERE Name=:name");
        $stmt->bindValue(':name', $name);
        $result = $stmt->execute();

        printAlert("Le groupe <b>${name}</b> a été supprimé");
        animatediv_byid('groupsDiv');
    }

/**
 *  LISTER TOUS LES REPOS D'UN GROUPE
 */
    public function listRepos(string $groupName) {
        global $OS_FAMILY;

        /**
         *  Si le groupe est 'Default' (groupe fictif) alors on affiche tous les repos n'ayant pas de groupe 
         */
        if ($groupName == 'Default') {
            if ($OS_FAMILY == "Redhat") {
                $reposInGroup = $this->db->query("SELECT * FROM repos
                WHERE Status = 'active' AND Id NOT IN (SELECT Id_repo FROM group_members)
                ORDER BY repos.Name ASC, repos.Env ASC");
            }
            if ($OS_FAMILY == "Debian") {
                $reposInGroup = $this->db->query("SELECT * FROM repos
                WHERE Status = 'active' AND Id NOT IN (SELECT Id_repo FROM group_members)
                ORDER BY repos.Name ASC, repos.Dist ASC, repos.Section ASC, repos.Env ASC");
            }
            
        } else {
            if ($OS_FAMILY == "Redhat") {
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
            if ($OS_FAMILY == "Debian") {
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

        while ($datas = $reposInGroup->fetchArray()) { $reposIn[] = $datas; }

        if (!empty($reposIn)) {
            return $reposIn;
        }
    }

/**
 *  LISTER TOUS LES REPOS D'UN GROUPE (DISTINCT)
 *  Liste les noms des repos uniquement avec un DISTINCT, car un repo peut avoir plusieurs environnements et donc apparaitre en double, ce qu'on ne veut pas
 */
    public function listReposNamesDistinct(string $groupName) {
        global $OS_FAMILY;

        /**
         *  Si le groupe est 'Default' (groupe fictif) alors on affiche tous les repos n'ayant pas de groupe 
         */
        if ($groupName == 'Default') {
            if ($OS_FAMILY == "Redhat") {
                $reposInGroup = $this->db->query("SELECT DISTINCT repos.Name FROM repos
                WHERE Status = 'active' AND Id NOT IN (SELECT Id_repo FROM group_members)
                ORDER BY repos.Name ASC");
            }
            if ($OS_FAMILY == "Debian") {
                $reposInGroup = $this->db->query("SELECT DISTINCT repos.Name, repos.Dist, repos.Section FROM repos
                WHERE Status = 'active' AND Id NOT IN (SELECT Id_repo FROM group_members)
                ORDER BY repos.Name ASC, repos.Dist ASC");
            }            
        } else {
            if ($OS_FAMILY == "Redhat") {
                $stmt = $this->db->prepare("SELECT DISTINCT repos.Name
                FROM repos
                INNER JOIN group_members
                    ON repos.Id = group_members.Id_repo
                INNER JOIN groups
                    ON groups.Id = group_members.Id_group
                WHERE groups.Name=:groupname
                AND repos.Status = 'active'
                ORDER BY repos.Name ASC");
                $stmt->bindValue(':groupname', $groupName);
                $reposInGroup = $stmt->execute();
            }
                if ($OS_FAMILY == "Debian") {
                $stmt = $this->db->prepare("SELECT DISTINCT repos.Name, repos.Dist, repos.Section
                FROM repos
                INNER JOIN group_members
                    ON repos.Id = group_members.Id_repo
                INNER JOIN groups
                    ON groups.Id = group_members.Id_group
                WHERE groups.Name=:groupname
                AND repos.Status = 'active'
                ORDER BY repos.Name ASC, repos.Dist ASC");
                $stmt->bindValue(':groupname', $groupName);
                $reposInGroup = $stmt->execute();
            }

            unset($stmt);
        }

        while ($datas = $reposInGroup->fetchArray()) { $reposIn[] = $datas; }

        if (!empty($reposIn)) {
            return $reposIn;
        }
    }


/**
 *  LISTER (Select) LES REPOS D'UN GROUPE
 *  On fait un DISTINCT ici car un repo peut avoir plusieurs environnements et donc apparaitre en double, ce qu'on ne veut pas
 */
    public function selectRepos(string $groupName) {
        global $OS_FAMILY;

        if ($OS_FAMILY == "Redhat") {
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
        if ($OS_FAMILY == "Debian") {
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

        while ($datas = $reposInGroup->fetchArray()) { $reposIn[] = $datas; }
        //while ($datas = $reposNotInGroup->fetchArray()) { $reposNotIn[] = $datas; }
        while ($datas = $reposNotInAnyGroup->fetchArray()) { $reposNotIn[] = $datas; }
        
        echo '<select class="reposSelectList" name="groupAddRepoName[]" multiple>';
        if (!empty($reposIn)) {
            foreach($reposIn as $repo) {
                $repoName = $repo['Name'];
                if ($OS_FAMILY == "Debian") {
                    $repoDist = $repo['Dist'];
                    $repoSection = $repo['Section'];
                }
                if ($OS_FAMILY == "Redhat") { echo "<option value=\"$repoName\" selected>$repoName</option>"; }
                if ($OS_FAMILY == "Debian") { echo "<option value=\"$repoName|$repoDist|$repoSection\" selected>$repoName - $repoDist - $repoSection</option>"; }
            }
        }
        if (!empty($reposNotIn)) {
            foreach($reposNotIn as $repo) {
                $repoName = $repo['Name'];
                if ($OS_FAMILY == "Debian") {
                    $repoDist = $repo['Dist'];
                    $repoSection = $repo['Section'];
                }
                if ($OS_FAMILY == "Redhat") { echo "<option value=\"$repoName\">$repoName</option>"; }
                if ($OS_FAMILY == "Debian") { echo "<option value=\"$repoName|$repoDist|$repoSection\">$repoName - $repoDist - $repoSection</option>"; }
            }
        }
        echo '</select>';  
        unset($reposInGroup, $reposNotInGroup, $datas, $reposIn, $reposNotIn);
        return;
    }

/**
 *  AJOUTER / SUPPRIMER DES REPOS/SECTIONS D'UN GROUPE
 */
    public function addRepo(array $repoNames) {
        global $OS_FAMILY;

        /**
         *  1. Récupération des Id actuellement dans le groupe
         *  2. Suppression des Id actuellement dans le groupe qui ne sont pas dans l'array transmis $repoNames 
         *  3. Insertion des Id des repo transmis
         */

        /**
         *  1. Récupération de l'id du groupe dans lequel on va ajouter les repos
         */
        $stmt = $this->db->prepare("SELECT Id FROM groups WHERE Name=:name");
        $stmt->bindValue(':name', $this->name);
        $result = $stmt->execute();
        while ($row = $result->fetchArray()) {
            $groupId = $row['Id'];
        }

        /**
         *  2. On traite chaque repo sélectionnés
         */
        foreach ($repoNames as $repoName) {
            $repoName = validateData($repoName);
            // Sur debian, $repoName contient le nom du repo, de la dist et de la section séparés par un |
            if ($OS_FAMILY == "Debian") {
                $repoNameExplode = explode('|', $repoName);
                $repoName = $repoNameExplode[0];
                $repoDist = $repoNameExplode[1];
                $repoSection = $repoNameExplode[2];
            }
            /**
             *  On vérifie que le nom du repo ne contient pas des caractères interdits, sinon on passe au repo suivant
             */
            if (!is_alphanum($repoName)) {
                animatediv_byid('groupsDiv');
                continue;
            }
            if ($OS_FAMILY == "Debian") {
                if (!is_alphanum($repoDist) OR !is_alphanum($repoSection)) {
                    animatediv_byid('groupsDiv');
                    continue;
                }
            }

            /**
             *  Récupération à partir de la BDD de l'id du repo à ajouter. Il peut y avoir plusieurs Id si le repo a plusieurs environnements.
             */
            if ($OS_FAMILY == "Redhat") {
                $stmt = $this->db->prepare("SELECT Id FROM repos WHERE Name=:reponame AND Status = 'active'");
                $stmt->bindValue(':reponame', $repoName);
                $result = $stmt->execute();
            }
            if ($OS_FAMILY == "Debian") {
                $stmt = $this->db->prepare("SELECT Id FROM repos WHERE Name=:reponame AND Dist=:repodist AND Section=:reposection AND Status = 'active'");
                $stmt->bindValue(':reponame', $repoName);
                $stmt->bindValue(':repodist', $repoDist);
                $stmt->bindValue(':reposection', $repoSection);
                $result = $stmt->execute();
            }
            while ($row = $result->fetchArray()) {
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

        /**
         *  3. On récupère la liste des repos actuellement dans le groupe afin de supprimer ceux qui n'ont pas été sélectionnés
         */
        $stmt = $this->db->prepare("SELECT Id_repo FROM group_members WHERE Id_group=:idgroup");
        $stmt->bindValue(':idgroup', $groupId);
        $result = $stmt->execute();
        while ($row = $result->fetchArray()) {
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

        printAlert('Modifications prises en compte');
        animatediv_byid('groupsDiv');

        unset($stmt, $result);
    }

/**
 *  Supprime dans les groupes les repos/sections qui n'existent plus
 */
    public function clean() {
        $this->db->exec("DELETE FROM group_members WHERE Id_repo NOT IN (SELECT Id FROM repos)");
    }


/**
 *  Recupère le nom du groupe à partir de son ID en BDD
 */
    public function db_getName() {
        $stmt = $this->db->prepare("SELECT Name from groups WHERE Id=:id");
        $stmt->bindValue(':id', $this->id);
        $result = $stmt->execute();
        while ($row = $result->fetchArray()) {
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
        while ($datas = $result->fetchArray()) { $group[] = $datas; }
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
        while ($datas = $query->fetchArray()) { 
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
        while ($datas = $query->fetchArray()) {
            $group[] = $datas['Name'];
        }

        // On ajoute le groupe par défaut (groupe fictif) à la suite
        $group[] = 'Default';

        /**
         *  Retourne un array avec les noms des groupes
         */
        if (!empty($group)) {
            return $group;
        }
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

        if ($this->db->countRows2($result) == 0) {
            return false;
        } else {
            return true;
        }
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

        if ($this->db->countRows2($result) == 0) {
            return false;
        } else {
            return true;
        }
    }
}
?>