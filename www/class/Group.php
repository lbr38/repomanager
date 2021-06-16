<?php
global $WWW_DIR;
require_once("${WWW_DIR}/class/Database.php");

class Group {
    public $db;
    public $name;

    public function __construct(array $variables = []) {
        extract($variables);

        /**
         *  Instanciation d'une db car on paut avoir besoin de récupérer certaines infos en BDD
         */
        try {
            $this->db = new databaseConnection();
        } catch(Exception $e) {
            die('Erreur : '.$e->getMessage());
        }

        /* Nom */
        if (!empty($groupName)) { $this->name = $groupName; }
    }

/**
 *  CREER UN GROUPE
 */
    public function new(string $name) {
        /**
         *  1. On vérifie que le groupe n'existe pas déjà
         */
        $result = $this->db->query("SELECT * FROM groups WHERE Name = '$name'");

        // Compte le nombre de lignes retournées, si il a +0 ligne alors le groupe existe déjà
        $count = 0;
        while ($row = $result->fetchArray()) {
            $count++;  
        }
    
        if ($count > 0) {
            printAlert("Le groupe <b>${name}</b> existe déjà");
            return;
        }

        /**
         *  2. Insertion du nouveau groupe
         */
        $this->db->exec("INSERT INTO groups (Name) VALUES ('$name')");

        printAlert("Le groupe <b>${name}</b> a été créé");
    }

/**
 *  RENOMMER UN GROUPE
 */
    public function rename(string $actualName, string $newName) {
        /**
         *  1. On traite seulement si le nouveau nom est différent de l'actuel
         */
        if ($newName == $actualName) {
            return;
        }

        /**
         *  2. On vérifie que le nouveau nom de groupe n'existe pas déjà
         */
        $result = $this->db->query("SELECT * FROM groups WHERE Name = '$newName'");

        // Compte le nombre de lignes retournées, si il a +0 ligne alors le groupe existe déjà
        $count = 0;
        while ($row = $result->fetchArray()) {
            $count++;
        }
    
        if ($count > 0) {
            printAlert("Le groupe <b>${newName}</b> existe déjà");
            return;
        }

        /**
         *  3. Renommage du groupe
         */
        $this->db->exec("UPDATE groups SET Name = '$newName' WHERE Name = '$actualName'");
        printAlert("Le groupe <b>${actualName}</b> a été renommé en <b>${newName}</b>");
    }

/**
 *  SUPPRIMER UN GROUPE
 */
    public function delete(string $name) {
        /**
         *  1. On vérifie que le groupe existe
         */
        $result = $this->db->query("SELECT * FROM groups WHERE Name = '$name'");

        // Compte le nombre de lignes retournées, si il a 0 ligne alors le groupe n'existe pas
        $count = 0;
        while ($row = $result->fetchArray()) {
            $count++;  
        }
    
        if ($count == 0) {
            printAlert("Le groupe <b>${name}</b> n'existe pas");
            return;
        }

        /**
         *  2. Supprime toutes les entrées concernant ce groupe dans group_members afin que les repos repassent sur le groupe par défaut
         */
        $this->db->exec("DELETE FROM group_members
        WHERE Id_group IN (SELECT Id FROM groups WHERE Name = '$name')");

        /**
         *  3. Suppression du groupe
         */
        $this->db->exec("DELETE FROM groups WHERE Name = '$name'");

        printAlert("Le groupe <b>${name}</b> a été supprimé");
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
            $reposInGroup = $this->db->query("SELECT * FROM repos
            WHERE Id NOT IN (SELECT Id_repo FROM group_members)
            ORDER BY repos.Name");
            
        } else {
            if ($OS_FAMILY == "Redhat") {
                // Note : ne pas utiliser SELECT *, comme il s'agit d'une jointure il faut bien préciser les données souhaitées
                $reposInGroup = $this->db->query("SELECT repos.Id, repos.Name, repos.Source, repos.Env, repos.Date, repos.Time, repos.Description, repos.Type, repos.Signed
                FROM repos
                INNER JOIN group_members
                    ON repos.Id = group_members.Id_repo
                INNER JOIN groups
                    ON groups.Id = group_members.Id_group
                WHERE groups.Name = '$groupName'
                ORDER BY repos.Name");
            }
                if ($OS_FAMILY == "Debian") {
                // Note : ne pas utiliser SELECT *, comme il s'agit d'une jointure il faut bien préciser les données souhaitées
                $reposInGroup = $this->db->query("SELECT repos.Id, repos.Name, repos.Source, repos.Dist, repos.Section, repos.Env, repos.Date, repos.Time, repos.Description, repos.Type, repos.Signed
                FROM repos
                INNER JOIN group_members
                    ON repos.Id = group_members.Id_repo
                INNER JOIN groups
                    ON groups.Id = group_members.Id_group
                WHERE groups.Name = '$groupName'
                ORDER BY repos.Name");
            }
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
                WHERE Id NOT IN (SELECT Id_repo FROM group_members)
                ORDER BY repos.Name");
            }
            if ($OS_FAMILY == "Debian") {
                $reposInGroup = $this->db->query("SELECT DISTINCT repos.Name, repos.Dist, repos.Section FROM repos
                WHERE Id NOT IN (SELECT Id_repo FROM group_members)
                ORDER BY repos.Name");
            }            
        } else {
            if ($OS_FAMILY == "Redhat") {
                $reposInGroup = $this->db->query("SELECT DISTINCT repos.Name
                FROM repos
                INNER JOIN group_members
                    ON repos.Id = group_members.Id_repo
                INNER JOIN groups
                    ON groups.Id = group_members.Id_group
                WHERE groups.Name = '$groupName'
                ORDER BY repos.Name");
            }
                if ($OS_FAMILY == "Debian") {
                $reposInGroup = $this->db->query("SELECT DISTINCT repos.Name, repos.Dist, repos.Section
                FROM repos
                INNER JOIN group_members
                    ON repos.Id = group_members.Id_repo
                INNER JOIN groups
                    ON groups.Id = group_members.Id_group
                WHERE groups.Name = '$groupName'
                ORDER BY repos.Name");
            }
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
            $reposInGroup = $this->db->query("SELECT DISTINCT repos.Name
            FROM repos
            INNER JOIN group_members
                ON repos.Id = group_members.Id_repo
            INNER JOIN groups
                ON groups.Id = group_members.Id_group
            WHERE groups.Name = '$groupName';");

            /*$reposNotInGroup = $this->db->query("SELECT DISTINCT repos.Name
            FROM repos
            INNER JOIN group_members
                ON repos.Id = group_members.Id_repo
            INNER JOIN groups
                ON groups.Id = group_members.Id_group
            WHERE groups.Name != '$groupName'");*/

            $reposNotInAnyGroup = $this->db->query("SELECT DISTINCT repos.Name
            FROM repos
            WHERE repos.Id NOT IN (SELECT Id_repo FROM group_members);");
        }
        if ($OS_FAMILY == "Debian") {
            $reposInGroup = $this->db->query("SELECT DISTINCT repos.Name, repos.Dist, repos.Section
            FROM repos
            INNER JOIN group_members
                ON repos.Id = group_members.Id_repo
            INNER JOIN groups
                ON groups.Id = group_members.Id_group
            WHERE groups.Name = '$groupName';");

            /*$reposNotInGroup = $this->db->query("SELECT DISTINCT repos.Name, repos.Dist, repos.Section
            FROM repos
            INNER JOIN group_members
                ON repos.Id = group_members.Id_repo
            INNER JOIN groups
                ON groups.Id = group_members.Id_group
            WHERE groups.Name != '$groupName'");*/

            $reposNotInAnyGroup = $this->db->query("SELECT DISTINCT repos.Name, repos.Dist, repos.Section
            FROM repos
            WHERE repos.Id NOT IN (SELECT Id_repo FROM group_members);");
        }

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

        $result = $this->db->querySingleRow("SELECT Id FROM groups WHERE Name = '$this->name'");
        $groupId = $result['Id'];

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
             *  Récupération à partir de la BDD de l'id du repo à ajouter. Il peut y avoir plusieurs Id si le repo a plusieurs environnements.
             */
            if ($OS_FAMILY == "Redhat") {
                $result = $this->db->query("SELECT Id FROM repos WHERE Name = '$repoName'");
            }
            if ($OS_FAMILY == "Debian") {
                $result = $this->db->query("SELECT Id FROM repos WHERE Name = '$repoName' AND Dist = '$repoDist' AND Section = '$repoSection'");
            }
            while ($row = $result->fetchArray()) {
                $repoId = $row['Id'];

                /**
                 *  Insertion en BDD de l'ID du repo (il peut y avoir 1 ou plusieurs Id à insérer si le repo a plusieurs environnements)
                 *  Le format de cet INSERT est fait de sorte à ne pas insérer un Id_repo si celui-ci est déjà présent en BDD
                 */
                $this->db->exec("INSERT INTO group_members (Id_repo, Id_group)
                Select $repoId, $groupId Where not exists(SELECT * from group_members where Id_repo = '$repoId' AND Id_group = '$groupId')");
                $reposId[] = $repoId; // On stocke dans reposId[] TOUS les Id des repos sélectionnés (tout environnements confondus) car on va en avoir besoin par la suite
            }
        }

        /**
         *  3. On récupère la liste des repos actuellement dans le groupe afin de supprimer ceux qui n'ont pas été sélectionnés
         */
        $result = $this->db->query("SELECT Id_repo FROM group_members WHERE Id_group = '$groupId'");
        while ($row = $result->fetchArray()) {
            $actualReposId[] = $row['Id_repo'];
        }
    
        /**
         *  4. Suppression des repos qui n'ont pas été sélectionnés
         */
        foreach ($actualReposId as $actualRepoId) {
            if (!in_array($actualRepoId, $reposId)) {
                $this->db->query("DELETE FROM group_members WHERE Id_repo = '$actualRepoId' AND Id_group = '$groupId'");
            }
        }
    }

/**
 *  Supprime dans les groupes les repos/sections qui n'existent plus
 */
    public function clean() {
        $this->db->exec("DELETE FROM group_members WHERE Id_repo NOT IN (SELECT Id FROM repos)");
    }

/**
 *  LISTER TOUS LES NOMS DE GROUPES
 *  Sauf le groupe par défaut
 */
    public function listAll() {
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
 *  Vérifie si le groupe existe
 */
    public function exists() {
        $result = $this->db->countRows("SELECT * FROM groups WHERE Name = '$this->name'");
        if ($result == 0) {
            return false;
        } else {
            return true;
        }
    }
}
?>