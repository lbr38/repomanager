<?php
include_once(ROOT."/models/includes/cleanArchives.php");

class Repo extends Model {
    public $id; // l'id en BDD du repo
    public $name;
    public $source;
    public $dist;
    public $section;
    public $date;
    public $dateFormatted;
    public $time;
    public $env;
    public $description;
    public $signed; // yes ou no
    public $type; // miroir ou local
    public $status;

    // Variable supplémentaires utilisées lors d'opérations sur le repo
    public $group;
    public $newName;
    public $newEnv;
    public $sourceFullUrl;
    public $hostUrl;
    public $rootUrl;
    public $gpgCheck;
    public $gpgResign;
    public $log;

    /**
     *  Import des traits nécessaires pour les opérations sur les repos/sections
     */
    use cleanArchives;

    public function __construct(array $variables = []) {

        extract($variables);

        /**
         *  Ouverture d'une connexion à la base de données
         */
        $this->getConnection('main');
        
        /* Id */
        if (!empty($repoId)) $this->id = $repoId;
        /* Type */
        if (!empty($repoType)) $this->type = $repoType;
        /* Nom */
        if (!empty($repoName)) $this->name = $repoName;
        /* Nouveau nom */
        if (!empty($repoNewName)) $this->newName = $repoNewName;
        /* Distribution (Debian) */
        if (!empty($repoDist)) $this->dist = $repoDist;
        /* Section (Debian) */
        if (!empty($repoSection)) $this->section = $repoSection;
        /* Env */
        if (empty($repoEnv)) { $this->env = DEFAULT_ENV; } else { $this->env = $repoEnv; }
        /* New env */
        if (!empty($repoNewEnv)) $this->newEnv = $repoNewEnv;
        /* Groupe */
        if (!empty($repoGroup)) { 
            if ($repoGroup == 'nogroup') {
                $this->group = ''; 
            } else {
                $this->group = $repoGroup;
            }
        } else { 
            $this->group = '';
        }
        /* Description */
        if (!empty($repoDescription)) {
            if ($repoDescription == "nodescription") {
                $this->description = '';
            } else {
                $this->description = $repoDescription;
            }
        } else {
            $this->description = '';
        }
        /* Date */
        if (empty($repoDate)) {
            // Si aucune date n'a été transmise alors on prend la date du jour
            $this->date = DATE_YMD;
            $this->dateFormatted = DATE_DMY;
        } else {
            /**
             *  A TESTER : nouvelle façon d'initialiser la date si elle a été transmise
             *  Pas encore eu l'occasion de tester ce cas
             */
            /**
             *  Si la date transmise est au format Y-m-d
             */
            $d = DateTime::createFromFormat('Y-m-d', $repoDate);
            if ($d === $repoDate) {
                $this->date = $repoDate;
                $this->dateFormatted = DateTime::createFromFormat('Y-m-d', $repoDate)->format('d-m-Y');
            }

            /**
             *  Si la date transmise est au format d-m-Y
             */
            $d = DateTime::createFromFormat('d-m-Y', $repoDate);
            if ($d === $repoDate) {
                $this->date = DateTime::createFromFormat('d-m-Y', $repoDate)->format('Y-m-d');
                $this->dateFormatted = $repoDate;
            }

            unset($d);
        }

        /* Time */
        if (empty($repoTime)) {
            //$this->time = exec("date +%H:%M");
            $this->time = date("H:i:s");
        } else {
            $this->time = $repoTime;
        }

        /* Source */
        if (!empty($repoSource)) {
            $this->source = $repoSource;

            /**
             *  On récupère au passage l'url source complète
             */
            if (OS_FAMILY == "Debian" AND $this->type == "mirror") $this->getFullSource();
        }
        /* Signed */
        if (!empty($repoSigned)) $this->signed = $repoSigned;
        /* Gpg resign */
        if (!empty($repoGpgResign)) {
            $this->signed    = $repoGpgResign;
            $this->gpgResign = $repoGpgResign;
        }
        /* gpg check */
        if (!empty($repoGpgCheck)) $this->gpgCheck = $repoGpgCheck;
        /* status */
        if (!empty($repoStatus)) $this->status = $repoStatus;
    }

    public function setId(string $id)
    {
        $this->id = Common::validateData($id);
    }

    public function setStatus(string $status)
    {
        $status = Common::validateData($status);
        
        /**
         *  Le status ne peut qu'être 'active' ou 'archived'
         */
        if ($status != "active" AND $status != "archived") {
            throw new Exception("Le status renseigné est invalide : $status");
        }

        $this->status = $status;
    }

    public function setDescription(string $description)
    {
        if ($description == 'nodescription') $description = '';

        $this->description = Common::validateData($description);
    }

    public function setGpgResign(string $gpgResign)
    {
        if ($gpgResign != 'yes' AND $gpgResign != 'no') {
            throw new Exception('Erreur : le paramètre gpgResign doit être égal à yes ou à no');
        }

        $this->gpgResign = Common::validateData($gpgResign);
        $this->signed = Common::validateData($gpgResign);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }


/**
 *  LISTAGE
 */

/**
 *  Retourne un array de tous les repos/sections
 */
    public function listAll() {
        if (OS_FAMILY == "Redhat") {
            $result = $this->db->query("SELECT * FROM repos WHERE Status = 'active' ORDER BY Name ASC, Env ASC");
        }
        if (OS_FAMILY == "Debian") {
            $result = $this->db->query("SELECT * FROM repos WHERE Status = 'active' ORDER BY Name ASC, Dist ASC, Section ASC, Env ASC");
        }
        while ($datas = $result->fetchArray(SQLITE3_ASSOC)) $repos[] = $datas;
        
        if (!empty($repos)) return $repos;
    }

/**
 *  Retourne un array de tous les repos/sections archivé(e)s
 */
    public function listAll_archived() {
        if (OS_FAMILY == "Redhat") {
            $result = $this->db->query("SELECT * FROM repos_archived WHERE Status = 'active' ORDER BY Name ASC");
        }
        if (OS_FAMILY == "Debian") {
            $result = $this->db->query("SELECT * FROM repos_archived WHERE Status = 'active' ORDER BY Name ASC, Dist ASC, Section ASC");
        }
        while ($datas = $result->fetchArray(SQLITE3_ASSOC)) $repos[] = $datas;
        
        if (!empty($repos)) return $repos;
    }

/**
 *  Retourne un array de tous les repos/sections (nom seulement)
 */
    public function listAll_distinct() {
        if (OS_FAMILY == "Redhat") { $result = $this->db->query("SELECT DISTINCT Name FROM repos WHERE Status = 'active' ORDER BY Name ASC"); }
        if (OS_FAMILY == "Debian") { $result = $this->db->query("SELECT DISTINCT Name, Dist, Section FROM repos WHERE Status = 'active' ORDER BY Name ASC, Dist ASC, Section ASC"); }
        while ($datas = $result->fetchArray(SQLITE3_ASSOC)) $repos[] = $datas;
        
        if (!empty($repos)) return $repos;
    }

/**
 *  Retourne un array de tous les repos/sections (nom seulement), sur un environnement en particulier
 */
    public function listAll_distinct_byEnv(string $env) {
        try {
            if (OS_FAMILY == "Redhat") $stmt = $this->db->prepare("SELECT DISTINCT Id, Name FROM repos WHERE Env=:env AND Status = 'active' ORDER BY Name ASC");
            if (OS_FAMILY == "Debian") $stmt = $this->db->prepare("SELECT DISTINCT Id, Name, Dist, Section FROM repos WHERE Env=:env AND Status = 'active' ORDER BY Name ASC, Dist ASC, Section ASC");
            $stmt->bindValue(':env', $env);
            $result = $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

        while ($datas = $result->fetchArray(SQLITE3_ASSOC)) $repos[] = $datas;
        
        if (!empty($repos)) return $repos;
    }

/**
 *  Compter le nombre total de repos/sections
 */
    public function countActive() {
        if (OS_FAMILY == "Redhat") $result = $this->db->query("SELECT DISTINCT Name FROM repos WHERE Status = 'active'");
        if (OS_FAMILY == "Debian") $result = $this->db->query("SELECT DISTINCT Name, Dist, Section FROM repos WHERE Status = 'active'");

        $count = $this->db->count($result);

        return $count;
    }

/**
 *  Compter le nombre total de repos/sections archivé(e)s
 */
    public function countArchived() {
        if (OS_FAMILY == "Redhat") $result = $this->db->query("SELECT DISTINCT Name FROM repos_archived WHERE Status = 'active'");
        if (OS_FAMILY == "Debian") $result = $this->db->query("SELECT DISTINCT Name, Dist, Section FROM repos_archived WHERE Status = 'active'");

        $count = $this->db->count($result);

        return $count;
    }


/**
 *  VERIFICATIONS
 */

    /**
     *  Vérifie que l'Id du repo existe en BDD
     *  Retourne true si existe
     *  Retourne false si n'existe pas
     */
    public function existsId(string $state = '') {
        /**
         *  Si on a renseigné $state (active ou archived) alors on interroge soit la table repos soit la table repos_archived
         */
        if (!empty($state)) {
            if ($state == "active") {
                try {
                    $stmt = $this->db-prepare("SELECT * FROM repos WHERE Id=:id AND Status = 'active'");
                    $stmt->bindValue(':id', $this->id);
                    $result = $stmt->execute();
                } catch(Exception $e) {
                    Common::dbError($e);
                }

                if ($this->db->isempty($result) === true)
                    return false;
                else
                    return true;

            } elseif ($state == "archived") {
                try {
                    $stmt = $this->db-prepare("SELECT * FROM repos_archived WHERE Id=:id AND Status = 'active'");
                    $stmt->bindValue(':id', $this->id);
                    $result = $stmt->execute();
                } catch(Exception $e) {
                    Common::dbError($e);
                }

                if ($this->db->isempty($result) === true)
                    return false;
                else
                    return true;

            } else {
                return false;
            }
        }

        /**
         *  Si on n'a pas renseigné $state alors on interroge par défaut la table repos
         */
        try {
            $stmt = $this->db->prepare("SELECT * FROM repos WHERE Id=:id AND Status = 'active'");
            $stmt->bindValue(':id', $this->id);
            $result = $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

        if ($this->db->isempty($result) === true)
            return false;
        else
            return true;        
    }

/**
 *  Vérifie que le repo existe
 *  Retourne true si existe
 *  Retourne false si n'existe pas
 */
    public function exists(string $name) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM repos WHERE Name=:name AND Status = 'active'");
            $stmt->bindValue(':name', $name);
            $result = $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

        if ($this->db->isempty($result) === true) return false;

        return true;
    }

/**
 *  Vérifie que le repo existe, sur un environnement en particulier
 *  Retourne true si existe
 *  Retourne false si n'existe pas
 */
    public function existsEnv(string $name, string $env) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM repos WHERE Name=:name AND Env=:env AND Status = 'active'");
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':env', $env);
            $result = $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

        if ($this->db->isempty($result) === true) return false;
        
        return true;
    }

/**
 *  Vérifie que le repo existe, à une date en particulier
 *  Retourne true si existe
 *  Retourne false si n'existe pas
 */
    public function existsDate(string $name, string $date, string $status) {
        /**
         *  Recherche dans la table repos
         */
        if ($status == 'active') {
            try {
                $stmt = $this->db->prepare("SELECT * FROM repos WHERE Name=:name AND Date=:date AND Status = 'active'");
                $stmt->bindValue(':name', $name);
                $stmt->bindValue(':date', $date);
                $result = $stmt->execute();
            } catch(Exception $e) {
                Common::dbError($e);
            }

            if ($this->db->isempty($result) === true) return false;
            
            return true;
        }
        /**
         *  Recherche dans la table repos_archived
         */
        if ($status == 'archived') {
            try {
                $stmt = $this->db->prepare("SELECT * FROM repos_archived WHERE Name = '$name' AND Date = '$date' AND Status = 'active'");
                $stmt->bindValue(':name', $name);
                $stmt->bindValue(':date', $date);
                $result = $stmt->execute();
            } catch(Exception $e) {
                Common::dbError($e);
            }

            if ($this->db->isempty($result) === true) return false;

            return true;
        }
    }

/**
 *  Vérifie que le repo existe, à une date en particulier et à un environnement en particulier
 *  Retourne true si existe
 *  Retourne false si n'existe pas
 */
    public function existsDateEnv(string $name, string $date, string $env) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM repos WHERE Name=:name AND Date=:date AND Env=:env AND Status = 'active'");
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':date', $date);
            $stmt->bindValue(':env', $env);
            $result = $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

        if ($this->db->isempty($result) === true) return false;

        return true;
    }

/**
 *  Vérifie que la distribution existe
 *  Retourne true si existe
 *  Retourne false si n'existe pas
 */
    public function dist_exists(string $name, string $dist) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM repos WHERE Name=:name AND Dist=:dist AND Status = 'active'");
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':dist', $dist);
            $result = $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

        if ($this->db->isempty($result) === true) return false;
        
        return true;
    }

/**
 *  Vérifie que la section existe
 *  Retourne true si existe
 *  Retourne false si n'existe pas
 */
    public function section_exists(string $name, string $dist, string $section) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM repos WHERE Name=:name AND Dist=:dist AND Section=:section AND Status = 'active'");
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':dist', $dist);
            $stmt->bindValue(':section', $section);
            $result = $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

        if ($this->db->isempty($result) === true) return false;

        return true;
    }

/**
 *  Vérifie que la section existe, sur un environnement en particulier
 *  Retourne true si existe
 *  Retourne false si n'existe pas
 */
    public function section_existsEnv(string $name, string $dist, string $section, string $env) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM repos WHERE Name=:name AND Dist=:dist AND Section=:section AND Env=:env AND Status = 'active'");
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':dist', $dist);
            $stmt->bindValue(':section', $section);
            $stmt->bindValue(':env', $env);
            $result = $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

        if ($this->db->isempty($result) === true) return false;

        return true;
    }

/**
 *  Vérifie que la section existe, à une date en particulier
 *  Retourne true si existe
 *  Retourne false si n'existe pas
 */
    public function section_existsDate(string $name, string $dist, string $section, string $date, string $status) {
        /**
         *  Recherche dans la table repos
         */
        if ($status == 'active') {
            try {
                $stmt = $this->db->prepare("SELECT * FROM repos WHERE Name=:name AND Dist=:dist AND Section=:section AND Date=:date AND Status = 'active'");
                $stmt->bindValue(':name', $name);
                $stmt->bindValue(':dist', $dist);
                $stmt->bindValue(':section', $section);
                $stmt->bindValue(':date', $date);
                $result = $stmt->execute();
            } catch(Exception $e) {
                Common::dbError($e);
            }

            if ($this->db->isempty($result) === true) return false;
            
            return true;
        }
        /**
         *  Recherche dans la table repos_archived
         */
        if ($status == 'archived') {
            try {
                $stmt = $this->db->prepare("SELECT * FROM repos_archived WHERE Name=:name AND Dist=:dist AND Section=:section AND Date=:date AND Status = 'active'");
                $stmt->bindValue(':name', $name);
                $stmt->bindValue(':dist', $dist);
                $stmt->bindValue(':section', $section);
                $stmt->bindValue(':date', $date);
                $result = $stmt->execute();
            } catch(Exception $e) {
                Common::dbError($e);
            }

            if ($this->db->isempty($result) === true) return false;

            return true;
        }        
    }

/**
 *  Vérifie que la section existe, à une date en particulier et à un environnement en particulier
 *  Retourne true si existe
 *  Retourne false si n'existe pas
 */
    public function section_existsDateEnv(string $name, string $dist, string $section, string $date, string $env) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM repos WHERE Name=:name AND Dist=:dist AND Section=:section AND Date=:date AND Env=:env AND Status = 'active'");
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':dist', $dist);
            $stmt->bindValue(':section', $section);
            $stmt->bindValue(':date', $date);
            $stmt->bindValue(':env', $env);
            $result = $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

        if ($this->db->isempty($result) === true) return false;

        return true;
    }

/**
 *  RECUPERATION D'INFOS EN BDD
 */

/**
 *  Récupère l'ID du repo/de la section en BDD
 */
    public function db_getId() {
        try {
            if (OS_FAMILY == "Redhat") $stmt = $this->db->prepare("SELECT Id from repos WHERE Name=:name AND Env =:env AND Status = 'active'");
            if (OS_FAMILY == "Debian") $stmt = $this->db->prepare("SELECT Id from repos WHERE Name=:name AND Dist=:dist AND Section=:section AND Env=:env AND Status = 'active'");
            $stmt->bindValue(':name', $this->name);
            if (OS_FAMILY == "Debian") {
                $stmt->bindValue(':dist', $this->dist);
                $stmt->bindValue(':section', $this->section);
            }
            $stmt->bindValue(':env', $this->env);
            $result = $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

        while ($row = $result->fetchArray()) {
            $this->id = $row['Id'];
        }

        unset($stmt, $result);
    }

    /**
     *  Comme au dessus mais pour un repo/section archivé
     */
    public function db_getId_archived() {
        try {
            if (OS_FAMILY == "Redhat") $stmt = $this->db->prepare("SELECT Id from repos_archived WHERE Name=:name AND Date=:date AND Status = 'active'");
            if (OS_FAMILY == "Debian") $stmt = $this->db->prepare("SELECT Id from repos_archived WHERE Name=:name AND Dist=:dist AND Section=:section AND Date=:date AND Status = 'active'");
            $stmt->bindValue(':name', $this->name);
            if (OS_FAMILY == "Debian") {
                $stmt->bindValue(':dist', $this->dist);
                $stmt->bindValue(':section', $this->section);
            }
            $stmt->bindValue(':date', $this->date);
            $result = $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

        while ($row = $result->fetchArray()) {
            $this->id = $row['Id'];
        }

        unset($stmt, $result);
    }

/**
 *  Recupère toutes les information du repo/de la section en BDD à partir de son ID et de son état (active ou archived)
 */
    public function db_getAllById(string $state = '') {
        /**
         *  Si on a précisé un state en argument et qu'il est égal à 'archived' alors on interroge la table des repos archivé
         *  Sinon dans tous les autres cas on interroge la table par défaut càd les repos actifs
         */
        try {
            if (!empty($state) AND $state == 'archived') {
                $stmt = $this->db->prepare("SELECT * from repos_archived WHERE Id=:id");
            } else {
                $stmt = $this->db->prepare("SELECT * from repos WHERE Id=:id");
            }
            $stmt->bindValue(':id', $this->id);
            $result = $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

        /**
         *  Si rien n'a été trouvé en BDD avec l'ID fourni alors on quitte
         */
        if ($this->db->isempty($result) === true) throw new Exception("Erreur : aucun repo portant l'ID $this->id n'a été trouvé en BDD");

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $this->name = $row['Name'];
            if (OS_FAMILY == 'Debian') {
                $this->dist = $row['Dist'];
                $this->section = $row['Section'];
            }
            $this->source = $row['Source'];
            if (OS_FAMILY == "Debian" AND $this->type == "mirror") {
                $this->getFullSource();
            }
            $this->date = $row['Date'];
            $this->dateFormatted = DateTime::createFromFormat('Y-m-d', $row['Date'])->format('d-m-Y');
            if (!empty($row['Env'])) $this->env = $row['Env']; // Dans le cas où on a précisé $state == 'archived' il n'y a pas d'env pour les repo archivés, d'où la condition
            $this->type = $row['Type'];
            $this->signed = $row['Signed']; $this->gpgResign = $this->signed;
            $this->description = $row['Description'];
        }

        unset($stmt, $result);
    }

/**
 *  Recupère toutes les information du repo/de la section en BDD à partir de son nom et son env
 */
    public function db_getAll() {
        try {
            if (OS_FAMILY == "Redhat") $stmt = $this->db->prepare("SELECT * from repos WHERE Name=:name AND Env=:env AND Status = 'active'");
            if (OS_FAMILY == "Debian") $stmt = $this->db->prepare("SELECT * from repos WHERE Name=:name AND Dist=:dist AND Section=:section AND Env=:env AND Status = 'active'");
            $stmt->bindValue(':name', $this->name);
            if (OS_FAMILY == "Debian") {
                $stmt->bindValue(':dist', $this->dist);
                $stmt->bindValue(':section', $this->section);
            }
            $stmt->bindValue(':env', $this->env);
            $result = $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $this->id = $row['Id'];
            $this->source = $row['Source'];
            $this->date = $row['Date'];
            $this->dateFormatted = DateTime::createFromFormat('Y-m-d', $row['Date'])->format('d-m-Y');
            $this->type = $row['Type'];
            $this->signed = $row['Signed'];
            $this->description = $row['Description'];
        }
    }
/**
 *  Récupère la date du repo/section en BDD
 */
    public function db_getDate() {
        try {
            if (OS_FAMILY == "Redhat") $stmt = $this->db->prepare("SELECT Date FROM repos WHERE Name=:name AND Env=:env AND Status = 'active'");
            if (OS_FAMILY == "Debian") $stmt = $this->db->prepare("SELECT Date FROM repos WHERE Name=:name AND Dist=:dist AND Section=:section AND Env=:env AND Status = 'active'");
            $stmt->bindValue(':name', $this->name);
            $stmt->bindValue(':env', $this->env);
            if (OS_FAMILY == "Debian") {
                $stmt->bindValue(':dist', $this->dist);
                $stmt->bindValue(':section', $this->section);
            }
            $result = $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) $datas = $row;
        $this->date = $datas['Date'];
        $this->dateFormatted = DateTime::createFromFormat('Y-m-d', $this->date)->format('d-m-Y');
    }

/**
 *  Récupère le type du repo/section en BDD
 */
    public function db_getType() {
        try {
            $stmt = $this->db->prepare("SELECT Type FROM repos WHERE Id=:id AND Status = 'active'");
            $stmt->bindValue(':id', $this->id);
            $result = $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

        while ($row = $result->fetchArray()) $this->type = $row['Type'];
        
        unset($stmt, $result);
    }

/**
 *  Recupère la source du repo/section en BDD
 */
    public function db_getSource() {
        try {
            $stmt = $this->db->prepare("SELECT Source FROM repos WHERE Id=:id AND Status = 'active'");
            $stmt->bindValue(':id', $this->id);
            $result = $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

        while ($row = $result->fetchArray()) $this->source = $row['Source'];

        if (empty($this->source)) throw new Exception("<br><span class=\"redtext\">Erreur : </span>impossible de déterminer la source de du repo <b>$this->name</b>");
    }

/**
 *  Récupère l'url source complete avec la racine du dépot (Debian uniquement)
 */
    public function getFullSource() {
        /**
         *  Récupère l'url complète
         */
        try {
            $stmt = $this->db->prepare("SELECT Url FROM sources WHERE Name=:name");
            $stmt->bindValue(':name', $this->source);
            $result = $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

        while ($row = $result->fetchArray()) {
            $this->sourceFullUrl = $row['Url'];
        }
        unset($stmt);

        /**
         *  On retire http:// ou https:// du début de l'URL
         */
        $this->sourceFullUrl = str_replace(array("http://", "https://"), '', $this->sourceFullUrl);

        if (empty($this->sourceFullUrl)) {
            throw new Exception('<br><span class="redtext">Erreur : </span>impossible de déterminer l\'URL du repo source');
        }

        $this->hostUrl = exec("echo '$this->sourceFullUrl' | cut -d'/' -f1");
        
        /**
         *  Extraction de la racine de l'hôte (ex pour : ftp.fr.debian.org/debian ici la racine sera debian)
         */
        $this->rootUrl = str_replace($this->hostUrl, '', $this->sourceFullUrl);

        if (empty($this->hostUrl)) {
            throw new Exception('<br><span class="redtext">Erreur : </span>impossible de déterminer l\'adresse du repo source');
        }
        if (empty($this->rootUrl)) {
            throw new Exception('<br><span class="redtext">Erreur : </span>impossible de déterminer la racine de l\'URL du repo source');
        }
    }

/**
 *  ECRITURE EN BDD
 */

 /**
  *  Modification de la description
  */
    public function db_setDescription(string $description) {
        /**
         *  On accepte de modifier la description à certaines conditions
         *  Il faut avoir transmis si le repo est actif ou archivé
         *  Il faut que la description ne comporte pas de caractères interdits, on accepte certains caractères spéciaux (voir array ci-dessous)
         */
        if ($this->status != 'active' AND $this->status != 'archived') {
            throw new Exception('Le type de repo est invalide');
        }

        /**
         *  Vérification des caractères de la description
         */
        if (Common::is_alphanumdash($description, array(' ', '(', ')', '@', ',', '.', '\'', 'é', 'è', 'ê', 'à', 'ç', 'ù', 'ô', 'ï', '"')) === false) {
            throw new Exception("La description contient des caractères invalides");
        }

        try {
            if ($this->status == 'active')   $stmt = $this->db->prepare("UPDATE repos SET Description = :description WHERE Id = :id");
            if ($this->status == 'archived') $stmt = $this->db->prepare("UPDATE repos_archived SET Description = :description WHERE Id = :id");
            $stmt->bindValue(':description', Common::validateData($description));
            $stmt->bindValue(':id', $this->id);
            $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }
        unset($stmt);

        Common::clearCache();
    }

/**
 *  Modification de l'état de signature GPG
 */
    public function db_setsigned() {
        /**
         *  $this->signed ne peut que être 'yes' ou 'no'
         */
        if ($this->signed != "yes" AND $this->signed != "no") return;

        try {
            $stmt = $this->db->prepare("UPDATE repos SET Signed=:signed WHERE Id=:id");
            $stmt->bindValue(':signed', $this->signed);
            $stmt->bindValue(':id', $this->id);
            $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }
        unset($stmt);

        Common::clearCache();
    }


/**
 *  GENERATION DE CONF
 */
    public function generateConf(string $destination) {
        // On peut préciser à la fonction le répertoire de destination des fichiers. Si on précise une valeur vide ou bien "default", alors les fichiers seront générés dans le répertoire par défaut
        if (empty($destination) OR $destination == "default") {
            $destination = REPOS_PROFILES_CONF_DIR;
        }

        // Génération du fichier pour Redhat/Centos
        if (OS_FAMILY == "Redhat") {
            $content = "# Repo {$this->name} sur ".WWW_HOSTNAME;
            $content = "${content}\n[".REPO_CONF_FILES_PREFIX."{$this->name}___ENV__]";
            $content = "${content}\nname=Repo {$this->name} sur ".WWW_HOSTNAME;
            $content = "${content}\ncomment=Repo {$this->name} sur ".WWW_HOSTNAME;
            $content = "${content}\nbaseurl=https://".WWW_HOSTNAME."/repo/{$this->name}___ENV__";
            $content = "${content}\nenabled=1";
            if (GPG_SIGN_PACKAGES == "yes") {
            $content = "${content}\ngpgcheck=1";
            $content = "${content}\ngpgkey=https://".WWW_HOSTNAME."/repo/".WWW_HOSTNAME.".pub";
            } else {
            $content = "${content}\ngpgcheck=0";
            }
            // Création du fichier si n'existe pas déjà
            if (!file_exists("${destination}/".REPO_CONF_FILES_PREFIX."{$this->name}.repo")) {
                touch("${destination}/".REPO_CONF_FILES_PREFIX."{$this->name}.repo");
            }
            // Ecriture du contenu dans le fichier
            file_put_contents("${destination}/".REPO_CONF_FILES_PREFIX."{$this->name}.repo", $content);
        }
        // Génération du fichier pour Debian
        if (OS_FAMILY == "Debian") {
            $content = "# Repo {$this->name}, distribution {$this->dist}, section {$this->section} sur ".WWW_HOSTNAME;
            $content = "${content}\ndeb https://".WWW_HOSTNAME."/repo/{$this->name}/{$this->dist}/{$this->section}___ENV__ {$this->dist} {$this->section}";
            
            // Si le nom de la distribution contient un slash, c'est le cas par exemple avec debian-security (buster/updates), alors il faudra remplacer ce slash par --slash-- dans le nom du fichier .list 
            //$checkIfDistContainsSlash = exec("echo $this->dist | grep '/'");
            //if (!empty($checkIfDistContainsSlash)) {
            if (preg_match('#/#', $this->dist)) {
                $repoDistFormatted = str_replace("/", "--slash--", $this->dist);
            } else {
                $repoDistFormatted = $this->dist;
            }
            // Création du fichier si n'existe pas déjà
            if (!file_exists("${destination}/".REPO_CONF_FILES_PREFIX."{$this->name}_${repoDistFormatted}_{$this->section}.list")) {
                touch("${destination}/".REPO_CONF_FILES_PREFIX."{$this->name}_${repoDistFormatted}_{$this->section}.list");
            }
            // Ecriture du contenu dans le fichier
            file_put_contents("${destination}/".REPO_CONF_FILES_PREFIX."{$this->name}_${repoDistFormatted}_{$this->section}.list", $content);
        }

        unset($content);
        return 0;
    }

/**
 *  SUPPRESSION DE CONF
 */
    public function deleteConf() {
        if (OS_FAMILY == "Redhat") {
            // Suppression du fichier si existe
            if (file_exists(REPOS_PROFILES_CONF_DIR."/".REPO_CONF_FILES_PREFIX."{$this->name}.repo")) {
                unlink(REPOS_PROFILES_CONF_DIR."/".REPO_CONF_FILES_PREFIX."{$this->name}.repo");
            }

            // Suppression des liens symboliques pointant vers ce repo dans les répertoires de profils 
            $profilesNames = scandir(PROFILES_MAIN_DIR); // Récupération de tous les noms de profils
            foreach($profilesNames as $profileName) {
                if (($profileName != "..") AND ($profileName != ".") AND ($profileName != "_configurations") AND ($profileName != "_reposerver") AND ($profileName != PROFILE_SERVER_CONF)) {
                    if (is_link(PROFILES_MAIN_DIR."/${profileName}/".REPO_CONF_FILES_PREFIX."{$this->name}.repo")) {
                        unlink(PROFILES_MAIN_DIR."/${profileName}/".REPO_CONF_FILES_PREFIX."{$this->name}.repo");
                    }
                }
            }
        }

        if (OS_FAMILY == "Debian") {
            // Si le nom de la distribution contient un slash, c'est le cas par exemple avec debian-security (buster/updates), alors il faudra remplacer ce slash par --slash-- dans le nom du fichier .list 
            $checkIfDistContainsSlash = exec("echo $this->dist | grep '/'");
            if (!empty($checkIfDistContainsSlash)) {
                $repoDistFormatted = str_replace("/", "--slash--", $this->dist);
            } else {
                $repoDistFormatted = $this->dist;
            }

            // Suppression du fichier si existe
            if (file_exists(REPOS_PROFILES_CONF_DIR."/".REPO_CONF_FILES_PREFIX."{$this->name}_${repoDistFormatted}_{$this->section}.list")) {
                unlink(REPOS_PROFILES_CONF_DIR."/".REPO_CONF_FILES_PREFIX."{$this->name}_${repoDistFormatted}_{$this->section}.list");
            }
            
            // Suppression des liens symboliques pointant vers ce repo dans les répertoires de profils 
            $profilesNames = scandir(PROFILES_MAIN_DIR); // Récupération de tous les noms de profils
            foreach($profilesNames as $profileName) {
                if (($profileName != "..") AND ($profileName != ".") AND ($profileName != "_configurations") AND ($profileName != "_reposerver") AND ($profileName != PROFILE_SERVER_CONF)) {
                    if (is_link(PROFILES_MAIN_DIR."/$profileName/".REPO_CONF_FILES_PREFIX."{$this->name}_${repoDistFormatted}_{$this->section}.list")) {
                        unlink(PROFILES_MAIN_DIR."/$profileName/".REPO_CONF_FILES_PREFIX."{$this->name}_${repoDistFormatted}_{$this->section}.list");
                    }
                }
            }
        }
    }
}
?>