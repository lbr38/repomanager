<?php
include_once(ROOT."/models/includes/cleanArchives.php");

class Repo extends Model {
    // public $id; // l'id en BDD du repo
    // public $name;
    // public $source;
    // public $dist;
    // public $section;
    // public $date;
    // public $dateFormatted;
    // public $time;
    // public $env;
    // public $description;
    // public $signed; // yes ou no
    // public $type; // miroir ou local
    // public $status;

    private $id; // l'id en BDD du repo
    private $name;
    private $source;
    private $dist;
    private $section;
    private $date;
    private $dateFormatted;
    private $time;
    private $env;
    private $description;
    private $signed; // yes ou no
    private $type; // miroir ou local
    private $status;


    // Variable supplémentaires utilisées lors d'opérations sur le repo
    public $group;
    public $log;
    // public $newName;
    // public $newEnv;
    private $sourceFullUrl;
    private $hostUrl;
    private $rootUrl;
    private $gpgCheck;
    private $gpgResign;

    private $targetName;
    private $targetEnv;
    private $targetGroup;
    private $targetDescription;
    private $targetGpgCheck;
    private $targetGpgResign;

    /**
     *  Import des traits nécessaires pour les opérations sur les repos/sections
     */
    use cleanArchives;

    public function __construct(array $variables = []) {

        // extract($variables);

        /**
         *  Ouverture d'une connexion à la base de données
         */
        $this->getConnection('main');
        
        // /* Id */
        // if (!empty($repoId)) $this->id = $repoId;
        // /* Type */
        // if (!empty($repoType)) $this->type = $repoType;
        // /* Nom */
        // if (!empty($repoName)) $this->name = $repoName;
        // /* Nouveau nom */
        // if (!empty($repoNewName)) $this->newName = $repoNewName;
        // /* Distribution (Debian) */
        // if (!empty($repoDist)) $this->dist = $repoDist;
        // /* Section (Debian) */
        // if (!empty($repoSection)) $this->section = $repoSection;
        // /* Env */
        // if (empty($repoEnv)) { $this->env = DEFAULT_ENV; } else { $this->env = $repoEnv; }
        // /* New env */
        // if (!empty($repoNewEnv)) $this->newEnv = $repoNewEnv;
        // /* Groupe */
        // if (!empty($repoGroup)) { 
        //     if ($repoGroup == 'nogroup') {
        //         $this->group = ''; 
        //     } else {
        //         $this->group = $repoGroup;
        //     }
        // } else { 
        //     $this->group = '';
        // }
        // /* Description */
        // if (!empty($repoDescription)) {
        //     if ($repoDescription == "nodescription") {
        //         $this->description = '';
        //     } else {
        //         $this->description = $repoDescription;
        //     }
        // } else {
        //     $this->description = '';
        // }
        // /* Date */
        // if (empty($repoDate)) {
        //     // Si aucune date n'a été transmise alors on prend la date du jour
        //     $this->date = DATE_YMD;
        //     $this->dateFormatted = DATE_DMY;
        // } else {
        //     /**
        //      *  A TESTER : nouvelle façon d'initialiser la date si elle a été transmise
        //      *  Pas encore eu l'occasion de tester ce cas
        //      */
        //     /**
        //      *  Si la date transmise est au format Y-m-d
        //      */
        //     $d = DateTime::createFromFormat('Y-m-d', $repoDate);
        //     if ($d === $repoDate) {
        //         $this->date = $repoDate;
        //         $this->dateFormatted = DateTime::createFromFormat('Y-m-d', $repoDate)->format('d-m-Y');
        //     }

        //     /**
        //      *  Si la date transmise est au format d-m-Y
        //      */
        //     $d = DateTime::createFromFormat('d-m-Y', $repoDate);
        //     if ($d === $repoDate) {
        //         $this->date = DateTime::createFromFormat('d-m-Y', $repoDate)->format('Y-m-d');
        //         $this->dateFormatted = $repoDate;
        //     }

        //     unset($d);
        // }

        // /* Time */
        // if (empty($repoTime)) {
        //     //$this->time = exec("date +%H:%M");
        //     $this->time = date("H:i:s");
        // } else {
        //     $this->time = $repoTime;
        // }

        // /* Source */
        // if (!empty($repoSource)) {
        //     $this->source = $repoSource;

        //     /**
        //      *  On récupère au passage l'url source complète
        //      */
        //     if (OS_FAMILY == "Debian" AND $this->type == "mirror") $this->getFullSource();
        // }
        // /* Signed */
        // if (!empty($repoSigned)) $this->signed = $repoSigned;
        // /* Gpg resign */
        // if (!empty($repoGpgResign)) {
        //     $this->signed    = $repoGpgResign;
        //     $this->gpgResign = $repoGpgResign;
        // }
        // /* gpg check */
        // if (!empty($repoGpgCheck)) $this->gpgCheck = $repoGpgCheck;
        // /* status */
        // if (!empty($repoStatus)) $this->status = $repoStatus;
    }

    public function setId(string $id)
    {
        $this->id = Common::validateData($id);
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function setDist(string $dist)
    {
        $this->dist = $dist;
    }

    public function setSection(string $section)
    {
        $this->section = $section;
    }

    public function setEnv(string $env)
    {
        $this->env = $env;
    }

    public function setDate(string $date)
    {
        $this->date = $date;
    }

    public function setDateFormatted(string $date)
    {
        $this->dateFormatted = DateTime::createFromFormat('Y-m-d', $date)->format('d-m-Y');
    }

    public function setTime(string $time)
    {
        $this->time = $time;
    }

    public function setType(string $type)
    {
        $this->type = $type;
    }

    public function setSigned(string $signed)
    {
        $this->signed = $signed;
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

    public function setDescription($description = '')
    {
        if ($description == 'nodescription') $description = '';

        $this->description = Common::validateData($description);
    }

    public function setSource(string $source)
    {
        $this->source = $source;
    }

    public function setSourceFullUrl(string $fullUrl)
    {
        $this->sourceFullUrl = $fullUrl;
    }

    public function setSourceHostUrl($hostUrl)
    {
        $this->hostUrl = $hostUrl;
    }

    public function setSourceRoot($root)
    {
        $this->rootUrl = $root;
    }

    // public function setGpgResign(string $gpgResign)
    // {
    //     if ($gpgResign != 'yes' AND $gpgResign != 'no') {
    //         throw new Exception('Erreur : le paramètre gpgResign doit être égal à yes ou à no');
    //     }

    //     $this->gpgResign = Common::validateData($gpgResign);
    //     $this->signed = Common::validateData($gpgResign);
    // }

    public function setTargetName(string $name)
    {
        $this->targetName = $name;    
    }

    public function setTargetEnv(string $env)
    {
        $this->targetEnv = $env;
    }

    public function setTargetGroup(string $group)
    {
        if ($group == 'nogroup') {
            $this->targetGroup = '';
        } else {
            $this->targetGroup = $group;
        }
    }

    public function setTargetDescription(string $description)
    {
        if ($description == 'nodescription') {
            $this->targetDescription = '';
        } else {
            $this->targetDescription = $description;
        }
    }

    public function setTargetGpgCheck(string $gpgCheck)
    {
        $this->targetGpgCheck = $gpgCheck;
    }

    public function setTargetGpgResign(string $gpgResign)
    {
        $this->targetGpgResign = $gpgResign;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDist()
    {
        return $this->dist;
    }

    public function getSection()
    {
        return $this->section;
    }

    public function getEnv()
    {
        return $this->env;
    }

    public function getTargetEnv()
    {
        return $this->targetEnv;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function getDateFormatted()
    {
        return DateTime::createFromFormat('Y-m-d', $this->date)->format('d-m-Y');
    }

    public function getTime()
    {
        return $this->time;
    }

    public function getSource()
    {
        return $this->source;
    }

    public function getHostUrl()
    {
        return $this->hostUrl;
    }

    public function getRootUrl()
    {
        return $this->rootUrl;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getSigned()
    {
        return $this->signed;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getTargetName()
    {
        return $this->targetName;
    }

    public function getTargetGroup()
    {
        return $this->targetGroup;
    }

    public function getTargetDescription()
    {
        return $this->targetDescription;
    }

    public function getTargetGpgCheck()
    {
        return $this->targetGpgCheck;
    }

    public function getTargetGpgResign()
    {
        return $this->targetGpgResign;
    }


/**
 *  LISTAGE
 */

    /**
     *  Retourne un array de tous les repos/sections
     */
    public function listAll()
    {
        try {
            if (OS_FAMILY == "Redhat") $result = $this->db->query("SELECT * FROM repos WHERE Status = 'active' ORDER BY Name ASC, Env ASC");
            if (OS_FAMILY == "Debian") $result = $this->db->query("SELECT * FROM repos WHERE Status = 'active' ORDER BY Name ASC, Dist ASC, Section ASC, Env ASC");
        } catch(Exception $e) {
            Common::dbError($e);
        }

        $repos = array();

        while ($datas = $result->fetchArray(SQLITE3_ASSOC)) $repos[] = $datas;
        
        return $repos;
    }

    /**
     *  Retourne un array de tous les repos/sections archivé(e)s
     */
    public function listAll_archived()
    {    
        try {
            if (OS_FAMILY == "Redhat") $result = $this->db->query("SELECT * FROM repos_archived WHERE Status = 'active' ORDER BY Name ASC");
            if (OS_FAMILY == "Debian") $result = $this->db->query("SELECT * FROM repos_archived WHERE Status = 'active' ORDER BY Name ASC, Dist ASC, Section ASC");
        } catch(Exception $e) {
            Common::dbError($e);
        }

        $repos = array();

        while ($datas = $result->fetchArray(SQLITE3_ASSOC)) $repos[] = $datas;
        
        return $repos;
    }

    /**
     *  Retourne un array de tous les repos/sections (nom seulement)
     */
    public function listAll_distinct() {
        try {
            if (OS_FAMILY == "Redhat") $result = $this->db->query("SELECT DISTINCT Name FROM repos WHERE Status = 'active' ORDER BY Name ASC");
            if (OS_FAMILY == "Debian") $result = $this->db->query("SELECT DISTINCT Name, Dist, Section FROM repos WHERE Status = 'active' ORDER BY Name ASC, Dist ASC, Section ASC");
        } catch(Exception $e) {
            Common::dbError($e);
        }

        $repos = array();
        
        while ($datas = $result->fetchArray(SQLITE3_ASSOC)) $repos[] = $datas;
        
        return $repos;
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
        
        $repos = array();

        while ($datas = $result->fetchArray(SQLITE3_ASSOC)) $repos[] = $datas;
        
        return $repos;
    }

    /**
     *  Compter le nombre total de repos/sections
     */
    public function countActive()
    {
        if (OS_FAMILY == "Redhat") $result = $this->db->query("SELECT DISTINCT Name FROM repos WHERE Status = 'active'");
        if (OS_FAMILY == "Debian") $result = $this->db->query("SELECT DISTINCT Name, Dist, Section FROM repos WHERE Status = 'active'");

        return $this->db->count($result);
    }

    /**
     *  Compter le nombre total de repos/sections archivé(e)s
     */
    public function countArchived()
    {
        if (OS_FAMILY == "Redhat") $result = $this->db->query("SELECT DISTINCT Name FROM repos_archived WHERE Status = 'active'");
        if (OS_FAMILY == "Debian") $result = $this->db->query("SELECT DISTINCT Name, Dist, Section FROM repos_archived WHERE Status = 'active'");

        return $this->db->count($result);
    }


/**
 *  VERIFICATIONS
 */

    /**
     *  Vérifie que l'Id du repo existe en BDD
     *  Retourne true si existe
     *  Retourne false si n'existe pas
     */
    public function existsId(string $id, string $status = null)
    {
        /** 
         *  Si on a renseigné $table (active ou archived) alors on interroge soit la table repos soit la table repos_archived
         *  Sinon on interroge la table par défaut 'repos'
         */
        if (empty($status)) {
            $table = 'repos';
        } else {
            if ($status != 'active' AND $status != 'archived') {
                return false;
            }

            if ($status == 'active') {
                $table = 'repos';
            }
            if ($status == 'archived') {
                $table = 'repos_archived';
            }
        }

        try {
            $stmt = $this->db->prepare("SELECT Id FROM $table WHERE Id = :id AND Status = 'active'");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();

        } catch(Exception $e) {
            Common::dbError($e);
        }
        
        if ($this->db->isempty($result) === true) {
            return false;
        }
        
        return true;      
    }

    /**
     *  Vérifie que le repo existe à partir de son nom
     *  Retourne true si existe
     *  Retourne false si n'existe pas
     */
    public function exists(string $name)
    {
        try {
            $stmt = $this->db->prepare("SELECT Id FROM repos WHERE Name = :name AND Status = 'active'");
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
    public function existsEnv(string $name, string $env)
    {
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
    public function existsDate(string $name, string $date, string $status)
    {
        if ($status == 'active') {
            $table = 'repos';
        }
        if ($status == 'archived') {
            $table = 'repos_archived';
        }

        try {
            $stmt = $this->db->prepare("SELECT * FROM $table WHERE Name = :name AND Date = :date AND Status = 'active'");
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
     *  Vérifie que le repo existe, à une date et à un environnement en particulier
     *  Retourne true si existe
     *  Retourne false si n'existe pas
     */
    public function existsDateEnv(string $name, string $date, string $env)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM repos WHERE Name = :name AND Date = :date AND Env = :env AND Status = 'active'");
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
     *  Vérifie que la section existe
     *  Retourne true si existe
     *  Retourne false si n'existe pas
     */
    public function section_exists(string $name, string $dist, string $section)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM repos WHERE Name = :name AND Dist = :dist AND Section = :section AND Status = 'active'");
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
    public function section_existsEnv(string $name, string $dist, string $section, string $env)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM repos WHERE Name = :name AND Dist = :dist AND Section = :section AND Env = :env AND Status = 'active'");
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
    public function section_existsDate(string $name, string $dist, string $section, string $date, string $status)
    {
        if ($status == 'active') {
            $table = 'repos';
        }
        if ($status == 'archived') {
            $table = 'repos_archived';
        }

        try {
            $stmt = $this->db->prepare("SELECT * FROM $table WHERE Name = :name AND Dist = :dist AND Section = :section AND Date = :date AND Status = 'active'");
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
     *  Vérifie que la section existe, à une date et à un environnement en particulier
     *  Retourne true si existe
     *  Retourne false si n'existe pas
     */
    public function section_existsDateEnv(string $name, string $dist, string $section, string $date, string $env)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM repos WHERE Name = :name AND Dist = :dist AND Section = :section AND Date = :date AND Env = :env AND Status = 'active'");
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
    public function db_getId()
    {
        try {
            if (OS_FAMILY == "Redhat") $stmt = $this->db->prepare("SELECT Id from repos WHERE Name = :name AND Env = :env AND Status = 'active'");
            if (OS_FAMILY == "Debian") $stmt = $this->db->prepare("SELECT Id from repos WHERE Name = :name AND Dist = :dist AND Section = :section AND Env = :env AND Status = 'active'");
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

        while ($row = $result->fetchArray()) $this->id = $row['Id'];

        unset($stmt, $result);
    }

    /**
     *  Recupère toutes les information du repo/de la section en BDD à partir de son ID et de son état (active ou archived)
     */
    public function db_getAllById(string $state = 'active')
    {
        /**
         *  Si on a précisé un state en argument et qu'il est égal à 'archived' alors on interroge la table des repos archivé
         *  Sinon dans tous les autres cas on interroge la table par défaut càd les repos actifs
         */
        try {
            if ($state == 'active') {
                $stmt = $this->db->prepare("SELECT * from repos WHERE Id = :id");
            }
            if ($state == 'archived') {
                $stmt = $this->db->prepare("SELECT * from repos_archived WHERE Id = :id");
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
            $this->setName($row['Name']);
            if (OS_FAMILY == 'Debian') {
                $this->setDist($row['Dist']);
                $this->setSection($row['Section']);
            }
            $this->setSource($row['Source']);
            if (OS_FAMILY == "Debian" AND $this->type == "mirror") {
                $this->getFullSource();
            }
            $this->setDate($row['Date']);
            $this->setDateFormatted($row['Date']);
            $this->setTime($row['Time']);
            /**
             *  Dans le cas où on a précisé $state == 'archived' il n'y a pas d'env pour les repo archivés, d'où la condition
             */
            if (!empty($row['Env'])) {
                $this->setEnv($row['Env']);
            }
            $this->setType($row['Type']);
            $this->setSigned($row['Signed']);
            $this->setDescription($row['Description']);
        }
    }

    /**
     *  Récupère l'url source complete avec la racine du dépot (Debian uniquement)
     */
    public function getFullSource()
    {
        /**
         *  Récupère l'url complète
         */
        try {
            $stmt = $this->db->prepare("SELECT Url FROM sources WHERE Name = :name");
            $stmt->bindValue(':name', $this->source);
            $result = $stmt->execute();

        } catch(Exception $e) {
            Common::dbError($e);
        }

        while ($row = $result->fetchArray()) $fullUrl = $row['Url'];

        /**
         *  On retire http:// ou https:// du début de l'URL
         */
        $fullUrl = str_replace(array("http://", "https://"), '', $fullUrl);

        if (empty($fullUrl)) {
            throw new Exception('impossible de déterminer l\'URL du repo source');
        }

        /**
         *  Extraction de l'adresse de l'hôte (server.domain.net) à partir de l'url http
         */
        $hostUrl = exec("echo '$fullUrl' | cut -d'/' -f1");
        
        /**
         *  Extraction de la racine de l'hôte (ex pour : ftp.fr.debian.org/debian ici la racine sera debian)
         */
        $root = str_replace($hostUrl, '', $fullUrl);

        if (empty($hostUrl)) {
            throw new Exception('impossible de déterminer l\'adresse du repo source');
        }
        if (empty($root)) {
            throw new Exception('impossible de déterminer la racine de l\'URL du repo source');
        }

        $this->setSourceFullUrl($fullUrl);
        $this->setSourceHostUrl($hostUrl);
        $this->setSourceRoot($root);
    }

    /**
     *  Récupère l'Id de groupe dont est membre un repo (si il fait partie d'un groupe)
     */
    public function db_getGroup(string $repoId)
    {
        try {
            $stmt = $this->db->prepare("SELECT Id_group FROM group_members WHERE Id_repo = :idrepo");
            $stmt->bindValue(':idrepo', $repoId);
            $result = $stmt->execute();

        } catch(Exception $e) {
            Common::dbError($e);
        }

        /**
         *  Si le repo n'est membre d'aucun groupe on retourne une valeur vide
         */
        if ($this->db->isempty($result)) return '';

        while ($row = $result->fetchArray()) $groupId = $row['Id_group'];

        return $groupId;
    }

/**
 *  ECRITURE EN BDD
 */

    /**
     *  Modification de la description
    */
    public function db_setDescription(string $description)
    {
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
    public function db_setsigned(string $id, string $signed)
    {
        /**
         *  signed peut être égal à 'yes' ou 'no'
         */
        if ($signed != "yes" AND $signed != "no") return;

        try {
            $stmt = $this->db->prepare("UPDATE repos SET Signed = :signed WHERE Id = :id");
            $stmt->bindValue(':signed', $signed);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

        Common::clearCache();
    }


    /**
     *  Génération d'un fichier de configuration de repo (.repo ou .list)
     */
    public function generateConf(string $destination) {
        /**
         *  On vérifie que le nom a été spécifié
         */
        if (empty($this->name)) {
            return false;
        }
        /**
         *  Sur Debian on vérifie également qu'on a bien fourni le nom de la distribution et de la section
         */
        if (OS_FAMILY == 'Debian') {
            if (empty($this->dist)) {
                return false;
            }

            if (empty($this->section)) {
                return false;
            }
        }

        /**
         *  On peut préciser à la fonction le répertoire de destination des fichiers. Si on précise une valeur vide ou bien "default", alors les fichiers seront générés dans le répertoire par défaut
         */
        if (empty($destination) OR $destination == "default") {
            $destination = REPOS_PROFILES_CONF_DIR;
        }

        /**
         *  Génération du fichier pour Redhat/Centos
         */
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

            /**
             *  Création du fichier si n'existe pas déjà
             */
            if (!file_exists("${destination}/".REPO_CONF_FILES_PREFIX."{$this->name}.repo")) {
                touch("${destination}/".REPO_CONF_FILES_PREFIX."{$this->name}.repo");
            }

            /**
             *  Ecriture du contenu dans le fichier
             */
            file_put_contents("${destination}/".REPO_CONF_FILES_PREFIX."{$this->name}.repo", $content);
        }

        /**
         *  Génération du fichier pour Debian
         */
        if (OS_FAMILY == "Debian") {
            $content = "# Repo {$this->name}, distribution {$this->dist}, section {$this->section} sur ".WWW_HOSTNAME;
            $content = "${content}\ndeb https://".WWW_HOSTNAME."/repo/{$this->name}/{$this->dist}/{$this->section}___ENV__ {$this->dist} {$this->section}";
            
            /**
             *  Si le nom de la distribution contient un slash, c'est le cas par exemple avec debian-security (buster/updates), alors il faudra remplacer ce slash par --slash-- dans le nom du fichier .list 
             */
            if (preg_match('#/#', $this->dist)) {
                $repoDistFormatted = str_replace("/", "--slash--", $this->dist);
            } else {
                $repoDistFormatted = $this->dist;
            }

            /**
             *  Création du fichier si n'existe pas déjà
             */
            if (!file_exists("${destination}/".REPO_CONF_FILES_PREFIX."{$this->name}_${repoDistFormatted}_{$this->section}.list")) {
                touch("${destination}/".REPO_CONF_FILES_PREFIX."{$this->name}_${repoDistFormatted}_{$this->section}.list");
            }

            /**
             *  Ecriture du contenu dans le fichier
             */
            file_put_contents("${destination}/".REPO_CONF_FILES_PREFIX."{$this->name}_${repoDistFormatted}_{$this->section}.list", $content);
        }

        unset($content);
        
        return true;
    }

    /**
     *  Suppression d'un fichier de configuration de repo (.repo ou .list)
     */
    public function deleteConf() {
        /**
         *  On vérifie que le nom a été spécifié
         */
        if (empty($this->name)) {
            return false;
        }
        /**
         *  Sur Debian on vérifie également qu'on a bien fourni le nom de la distribution et de la section
         */
        if (OS_FAMILY == 'Debian') {
            if (empty($this->dist)) {
                return false;
            }

            if (empty($this->section)) {
                return false;
            }
        }

        if (OS_FAMILY == "Redhat") {
            /**
             *  Suppression du fichier si existe
             */
            if (file_exists(REPOS_PROFILES_CONF_DIR."/".REPO_CONF_FILES_PREFIX."{$this->name}.repo")) {
                unlink(REPOS_PROFILES_CONF_DIR."/".REPO_CONF_FILES_PREFIX."{$this->name}.repo");
            }

            /**
             *  Suppression des liens symboliques pointant vers ce repo dans les répertoires de profils
             */ 
            $profilesNames = scandir(PROFILES_MAIN_DIR);

            foreach($profilesNames as $profileName) {
                if (($profileName != "..") AND ($profileName != ".") AND ($profileName != "_configurations") AND ($profileName != "_reposerver") AND ($profileName != PROFILE_SERVER_CONF)) {
                    if (is_link(PROFILES_MAIN_DIR."/${profileName}/".REPO_CONF_FILES_PREFIX."{$this->name}.repo")) {
                        unlink(PROFILES_MAIN_DIR."/${profileName}/".REPO_CONF_FILES_PREFIX."{$this->name}.repo");
                    }
                }
            }
        }

        if (OS_FAMILY == "Debian") {
            /**
             *  Si le nom de la distribution contient un slash, c'est le cas par exemple avec debian-security (buster/updates), alors il faudra remplacer ce slash par --slash-- dans le nom du fichier .list 
             */
            if (preg_match('#/#', $this->dist)) {
                $repoDistFormatted = str_replace("/", "--slash--", $this->dist);
            } else {
                $repoDistFormatted = $this->dist;
            }

            /**
             *  Suppression du fichier si existe
             */
            if (file_exists(REPOS_PROFILES_CONF_DIR."/".REPO_CONF_FILES_PREFIX."{$this->name}_${repoDistFormatted}_{$this->section}.list")) {
                unlink(REPOS_PROFILES_CONF_DIR."/".REPO_CONF_FILES_PREFIX."{$this->name}_${repoDistFormatted}_{$this->section}.list");
            }
            
            /**
             *  Suppression des liens symboliques pointant vers ce repo dans les répertoires de profils
             */ 
            $profilesNames = scandir(PROFILES_MAIN_DIR);

            foreach($profilesNames as $profileName) {
                if (($profileName != "..") AND ($profileName != ".") AND ($profileName != "_configurations") AND ($profileName != "_reposerver") AND ($profileName != PROFILE_SERVER_CONF)) {
                    if (is_link(PROFILES_MAIN_DIR."/$profileName/".REPO_CONF_FILES_PREFIX."{$this->name}_${repoDistFormatted}_{$this->section}.list")) {
                        unlink(PROFILES_MAIN_DIR."/$profileName/".REPO_CONF_FILES_PREFIX."{$this->name}_${repoDistFormatted}_{$this->section}.list");
                    }
                }
            }
        }
    }

    /**
     *  Ajout du repo au groupe spécifié
     */
    public function addToGroup(string $id, string $group)
    {
        /**
         *  On vérifie que le groupe existe et on récupère son Id
         */
        $mygroup = new Group('repo');
        $mygroup->db_getId($group);

        /**
         *  Insertion du repo dans le groupe
         */
        $mygroup->addRepoById($id, $mygroup->getId());
    }
}
?>