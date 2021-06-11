<?php
global $WWW_DIR;

require_once("${WWW_DIR}/class/Database.php");
require_once("${WWW_DIR}/class/Log.php");
require_once("${WWW_DIR}/class/Group.php");
include_once("${WWW_DIR}/class/inclusions/op_printDetails.php");
include_once("${WWW_DIR}/class/inclusions/op_getPackages.php");
include_once("${WWW_DIR}/class/inclusions/op_signPackages.php");
include_once("${WWW_DIR}/class/inclusions/op_createRepo.php");
include_once("${WWW_DIR}/class/inclusions/op_archive.php");
include_once("${WWW_DIR}/class/inclusions/op_finalize.php");
include_once("${WWW_DIR}/class/inclusions/changeEnv.php");
include_once("${WWW_DIR}/class/inclusions/delete.php");
include_once("${WWW_DIR}/class/inclusions/deleteDist.php");
include_once("${WWW_DIR}/class/inclusions/deleteSection.php");
include_once("${WWW_DIR}/class/inclusions/duplicate.php");
include_once("${WWW_DIR}/class/inclusions/deleteArchive.php");
include_once("${WWW_DIR}/class/inclusions/restore.php");

class Repo {
    public $db;
    public $id;
    public $name;
    public $source;
    public $dist;
    public $section;
    public $date;
    public $time;
    public $env;
    public $description;
    public $signed; // yes ou no
    public $type; // miroir ou local

    // Variable supplémentaires utilisées lors d'opérations sur le repo
    public $newName;
    public $dateFormatted;
    public $newEnv;
    public $sourceFullUrl;
    public $hostUrl;
    public $rootUrl;
    public $gpgCheck;
    public $log;

    /**
     *  Import des traits nécessaires pour les opérations sur les repos/sections
     */
    use op_printDetails, op_getPackages, op_signPackages, op_createRepo, op_archive, op_finalize;
    use changeEnv, duplicate, delete, deleteDist, deleteSection, deleteArchive, restore;

    public function __construct(array $variables = []) {
        global $OS_FAMILY;
        global $HOSTS_CONF;
        global $DEFAULT_ENV;
        extract($variables);

        /**
         *  Instanciation d'une db car on peut avoir besoin de récupérer certaines infos en BDD
         */
        try {
            $this->db = new databaseConnection();
        } catch(Exception $e) {
            die('Erreur : '.$e->getMessage());
        }
        
        /* Id */
        if (!empty($repoId)) { $this->id = $repoId; }
        /* Nom */
        if (!empty($repoName)) { $this->name = $repoName; }
        /* Nouveau nom */
        if (!empty($repoNewName)) { $this->newName = $repoNewName; }
        /* Distribution (Debian) */
        if (!empty($repoDist)) { $this->dist = $repoDist; }
        /* Section (Debian) */
        if (!empty($repoSection)) { $this->section = $repoSection; }
        /* Env */
        if (empty($repoEnv)) { $this->env = $DEFAULT_ENV; } else { $this->env = $repoEnv; }
        /* New env */
        if (!empty($repoNewEnv)) { $this->newEnv = $repoNewEnv; }
        /* Groupe */
        if (!empty($repoGroup)) { 
            if ($repoGroup == 'nogroup') {
                $this->group = ''; 
            } else { 
                $this->group = $repoGroup; }
            } else { 
                $this->group = '';
            }
        /* Description */
        if (!empty($repoDescription)) {
            if ($repoDescription == 'nodescription') {
                $this->description = '';
            } else {
                $this->description = $repoDescription;
            }
        } else {
            $this->description = '';
        }
        /* Date */
        if (empty($repoDate)) { 
            $this->date = exec("date +%Y-%m-%d"); 
        } else { 
            // $repoDate est généralement au format d-m-Y, on le convertit en format DATETIME pour qu'il puisse être facilement inséré en BDD
            $this->date = DateTime::createFromFormat('d-m-Y', $repoDate)->format('Y-m-d');
            $this->dateFormatted = $repoDate;
        }
        /* Time */
        if (empty($repoTime)) { $this->time = exec("date +%H:%M"); } else { $this->time = $repoTime; }

        /* Source */
        if (!empty($repoSource)) {
            $this->source = $repoSource;

            /**
             *  On récupère au passage l'url source complète
             */
            if ($OS_FAMILY == "Debian") {
                $this->getFullSource();
            }
        }
        /* Signed */
        if (!empty($repoSigned)) { $this->signed = $repoSigned; }
        /* Gpg resign */
        if (!empty($repoGpgResign)) {
            $this->signed    = $repoGpgResign;
            $this->gpgResign = $repoGpgResign;
        }
        /* gpg check */
        if (!empty($repoGpgCheck)) { $this->gpgCheck = $repoGpgCheck; }
        /* Type */
        if (!empty($repoType)) { $this->type = $repoType; }
    }

/**
 *  CREER UN NOUVEAU REPO/SECTION
 */
    public function new() {
        global $TEMP_DIR;
        global $OS_FAMILY;
        global $WWW_DIR;

        /**
         *  Création d'un fichier de log principal + un fichier PID
         */
        $this->log = new Log('repomanager');

        /**
         *  Lancement du script externe qui va construire le fichier de log principal à partir des petits fichiers de log de chaque étape
         */
        $steps = 5;
        exec("php ${WWW_DIR}/operations/check_running.php {$this->log->location} $TEMP_DIR/{$this->log->pid} $steps >/dev/null 2>/dev/null &");

        try {
            /**
             *  Etape 0 : Afficher le tritre de l'opération
             */
            $this->log->steplog(0);
            if ($OS_FAMILY == "Redhat") { file_put_contents($this->log->steplog, "<h5>CREATION D'UN NOUVEAU REPO</h5>"); }
            if ($OS_FAMILY == "Debian") { file_put_contents($this->log->steplog, "<h5>CREATION D'UNE NOUVELLE SECTION DE REPO</h5>"); }
            /**
             *  Etape 1 : Afficher les détails de l'opération
             */
            $this->log->steplog(1);
            $this->op_printDetails();
            /**
            *   Etape 2 => en commun avec updateRepo sauf la partie // On vérifie quand même que le repo n'existe pas déjà 
            */
            $this->log->steplog(2);
            $this->op_getPackages('new');
            /**
            *   Etape 3 => en commun avec updateRepo
            */
            $this->log->steplog(3);
            $this->op_signPackages();
            /**
            *   Etape 4 : Création du repo et liens symboliques => commun avec updateRepo
            */
            $this->log->steplog(4);
            $this->op_createRepo();
            /**
            *   Etape 5 : Finalisation du repo (ajout en BDD et application des droits)
            */
            $this->log->steplog(5);
            $this->op_finalize('new');

        } catch(Exception $e) {
            file_put_contents($this->log->steplog, $e->getMessage(), FILE_APPEND);
        }
        /**
         *  Cloture de l'opération
         */
        $this->log->closeStepOperation();
    }


/**
 *  METTRE A JOUR UN REPO/SECTION
 */
    public function update() {
        global $TEMP_DIR;
        global $OS_FAMILY;
        global $WWW_DIR;

        /**
         *  Création d'un fichier de log principal + un fichier PID
         */
        $this->log = new Log('repomanager');

        /**
         *  Lancement du script externe qui va construire le fichier de log principal à partir des petits fichiers de log de chaque étape
         */
        $steps = 6;
        exec("php ${WWW_DIR}/operations/check_running.php {$this->log->location} ${TEMP_DIR}/{$this->log->pid} $steps >/dev/null 2>/dev/null &");

        try {
            /**
             *  Etape 0 : Afficher le titre de l'opération
             */
            $this->log->steplog(0);
            if ($OS_FAMILY == "Redhat") { file_put_contents($this->log->steplog, "<h5>MISE A JOUR D'UN REPO</h5>"); }
            if ($OS_FAMILY == "Debian") { file_put_contents($this->log->steplog, "<h5>MISE A JOUR D'UNE SECTION DE REPO</h5>"); }
            /**
             *  Etape 1 : Afficher les détails de l'opération
             */
            $this->log->steplog(1);
            $this->op_printDetails();
            /**
            *   Etape 2 => en commun avec updateRepo sauf la partie // On vérifie quand même que le repo n'existe pas déjà 
            */
            $this->log->steplog(2);
            $this->op_getPackages('update');
            /**
            *   Etape 3 => en commun avec updateRepo
            */
            $this->log->steplog(3);
            $this->op_signPackages();
            /**
            *   Etape 4 : Création du repo et liens symboliques => commun avec updateRepo
            */
            $this->log->steplog(4);
            $this->op_createRepo();
            /**
             *  Etape 5 : Archivage de l'ancien repo/section
             */
            $this->log->steplog(5);
            $this->op_archive();
            /**
            *   Etape 6 : Finalisation du repo (ajout en BDD et application des droits)
            */
            $this->log->steplog(6);
            $this->op_finalize('update');

        } catch(Exception $e) {
            file_put_contents($this->log->steplog, $e->getMessage(), FILE_APPEND);
            /**
             *  Cloture de l'opération
             */
            $this->log->closeStepOperation();
            /**
             *  Cas où cette fonction est lancée par une planification : la planif attend un retour, on lui renvoie false pour lui indiquer qu'il y a eu une erreur
             */
            return false;
        }
        /**
         *  Cloture de l'opération
         */
        $this->log->closeStepOperation();
    }


/**
 *  LISTAGE
 */

/**
 *  Retourne un array de tous les repos/sections
 */
    public function listAll() {
        global $OS_FAMILY;

        if ($OS_FAMILY == "Redhat") {
            $result = $this->db->query("SELECT * FROM repos ORDER BY Name ASC");
        }
        if ($OS_FAMILY == "Debian") {
            $result = $this->db->query("SELECT * FROM repos ORDER BY Name ASC, Dist ASC, Section ASC");
        }
        while ($datas = $result->fetchArray()) { $repos[] = $datas; }
        if (!empty($repos)) {
            return $repos;
        }
    }

/**
 *  Retourne un array de tous les repos/sections archivé(e)s
 */
    public function listAll_archived() {
        global $OS_FAMILY;

        $result = $this->db->query("SELECT * FROM repos_archived ORDER BY Name ASC");
        while ($datas = $result->fetchArray()) { $repos[] = $datas; }
        if (!empty($repos)) {
            return $repos;
        }
    }

/**
 *  Retourne un array de tous les repos/sections (nom seulement)
 */
    public function listAll_distinct() {
        global $OS_FAMILY;
        if ($OS_FAMILY == "Redhat") { $result = $this->db->query("SELECT DISTINCT Name FROM repos"); }
        if ($OS_FAMILY == "Debian") { $result = $this->db->query("SELECT DISTINCT Name, Dist, Section FROM repos"); }
        while ($datas = $result->fetchArray()) { $repos[] = $datas; }
        if (!empty($repos)) {
            return $repos;
        }
    }

/**
 *  Retourne un array de tous les repos/sections (nom seulement), sur un environnement en particulier
 */
    public function listAll_distinct_byEnv(string $env) {
        global $OS_FAMILY;
        if ($OS_FAMILY == "Redhat") { $result = $this->db->query("SELECT DISTINCT Name FROM repos WHERE Env = '$env'"); }
        if ($OS_FAMILY == "Debian") { $result = $this->db->query("SELECT DISTINCT Name, Dist, Section FROM repos WHERE Env = '$env'"); }
        while ($datas = $result->fetchArray()) { $repos[] = $datas; }
        if (!empty($repos)) {
            return $repos;
        }
    }

/**
 *  Compter le nombre total de repos/sections
 */
    public function countActive() {
        global $OS_FAMILY;
        if ($OS_FAMILY == "Redhat") { $result = $this->db->countRows("SELECT DISTINCT Name FROM repos"); }
        if ($OS_FAMILY == "Debian") { $result = $this->db->countRows("SELECT DISTINCT Name, Dist, Section FROM repos"); }
        return $result;
    }

/**
 *  Compter le nombre total de repos/sections archivé(e)s
 */
    public function countArchived() {
        global $OS_FAMILY;
        if ($OS_FAMILY == "Redhat") { $result = $this->db->countRows("SELECT DISTINCT Name FROM repos_archived"); }
        if ($OS_FAMILY == "Debian") { $result = $this->db->countRows("SELECT DISTINCT Name, Dist, Section FROM repos_archived"); }
        return $result;
    }


/**
 *  VERIFICATIONS
 */

/**
 *  Vérifie que le repo existe
 *  Retourne true si existe
 *  Retourne false si n'existe pas
 */
    public function exists(string $name) {
        if ($this->db->countRows("SELECT * FROM repos WHERE Name = '$name'") == 0) {
            return false;
        } else {
            return true;
        }
    }

/**
 *  Vérifie que le repo existe, sur un environnement en particulier
 *  Retourne true si existe
 *  Retourne false si n'existe pas
 */
    public function existsEnv(string $name, string $env) {
        if ($this->db->countRows("SELECT * FROM repos WHERE Name = '$name' AND Env = '$env'") == 0) {
            return false;
        } else {
            return true;
        }
    }

/**
 *  Vérifie que le repo existe, à une date en particulier
 *  Retourne true si existe
 *  Retourne false si n'existe pas
 */
    public function existsDate(string $name, string $date, string $status) {
        // Recherche dans la table repos
        if ($status == 'active') {
            if ($this->db->countRows("SELECT * FROM repos WHERE Name = '$name' AND Date = '$date'") == 0) {
                return false;
            } else {
                return true;
            }
        }
        // Recherche dans la table repos_archived
        if ($status == 'archived') {
            if ($this->db->countRows("SELECT * FROM repos_archived WHERE Name = '$name' AND Date = '$date'") == 0) {
                return false;
            } else {
                return true;
            }
        }
    }

/**
 *  Vérifie que le repo existe, à une date en particulier et à un environnement en particulier
 *  Retourne true si existe
 *  Retourne false si n'existe pas
 */
    public function existsDateEnv(string $name, string $date, string $env) {
    if ($this->db->countRows("SELECT * FROM repos WHERE Name = '$name' AND Date = '$date' AND Env = '$env'") == 0) {
        return false;
    } else {
        return true;
    }
}

/**
 *  Vérifie que la distribution existe
 *  Retourne true si existe
 *  Retourne false si n'existe pas
 */
    public function dist_exists(string $name, string $dist) {
        if ($this->db->countRows("SELECT * FROM repos WHERE Name = '$name' AND Dist = '$dist'") == 0) {
            return false;
        } else {
            return true;
        }
    }

/**
 *  Vérifie que la section existe
 *  Retourne true si existe
 *  Retourne false si n'existe pas
 */
    public function section_exists(string $name, string $dist, string $section) {
        if ($this->db->countRows("SELECT * FROM repos WHERE Name = '$name' AND Dist = '$dist' AND Section = '$section'") == 0) {
            return false;
        } else {
            return true;
        }
    }

/**
 *  Vérifie que la section existe, sur un environnement en particulier
 *  Retourne true si existe
 *  Retourne false si n'existe pas
 */
    public function section_existsEnv(string $name, string $dist, string $section, string $env) {
        if ($this->db->countRows("SELECT * FROM repos WHERE Name = '$name' AND Dist = '$dist' AND Section = '$section' AND Env = '$env'") == 0) {
            return false;
        } else {
            return true;
        }
    }

/**
 *  Vérifie que la section existe, à une date en particulier
 *  Retourne true si existe
 *  Retourne false si n'existe pas
 */
    public function section_existsDate(string $name, string $dist, string $section, string $date, string $status) {
        // Recherche dans la table repos
        if ($status == 'active') {
            if ($this->db->countRows("SELECT * FROM repos WHERE Name = '$name' AND Dist = '$dist' AND Section = '$section' AND Date = '$date'") == 0) {
                return false;
            } else {
                return true;
            }
        }
        // Recherche dans la table repos_archived
        if ($status == 'archived') {
            if ($this->db->countRows("SELECT * FROM repos_archived WHERE Name = '$name' AND Dist = '$dist' AND Section = '$section' AND Date = '$date'") == 0) {
                return false;
            } else {
                return true;
            }
        }        
    }

/**
 *  Vérifie que la section existe, à une date en particulier et à un environnement en particulier
 *  Retourne true si existe
 *  Retourne false si n'existe pas
 */
    public function section_existsDateEnv(string $name, string $dist, string $section, string $date, string $env) {
        if ($this->db->countRows("SELECT * FROM repos WHERE Name = '$name' AND Dist = '$dist' AND Section = '$section' AND Date = '$date' AND Env = '$env'") == 0) {
            return false;
        } else {
            return true;
        }
    }

/**
 *  Recupère toutes les information du repo/de la section en BDD
 */
    public function db_getAll() {
        global $OS_FAMILY;

        if ($OS_FAMILY == "Redhat") {
            $result = $this->db->query("SELECT * from repos WHERE Name = '$this->name' AND Env = '$this->env'");
        }

        if ($OS_FAMILY == "Debian") {
            $result = $this->db->query("SELECT * from repos WHERE Name = '$this->name' AND Dist = '$this->dist' AND Section = '$this->section' AND Env = '$this->env'");
        }
        while ($row = $result->fetchArray()) {
            $this->source = $row['Source'];
            $this->date = $row['Date'];
            $this->type = $row['Type'];
            $this->signed = $row['Signed'];
            $this->description = $row['Description'];
        }
    }
/**
 *  Récupère la date du repo/section en BDD
 */
    public function db_getDate() {
        global $OS_FAMILY;

        if ($OS_FAMILY == "Redhat") {
            $result = $this->db->querySingleRow("SELECT Date from repos WHERE Name = '$this->name' AND Env = '$this->env'");
        }

        if ($OS_FAMILY == "Debian") {
            $result = $this->db->querySingleRow("SELECT Date from repos WHERE Name = '$this->name' AND Dist = '$this->dist' AND Section = '$this->section' AND Env = '$this->env'");
        }
        $this->date = $result['Date'];
        $this->dateFormatted = DateTime::createFromFormat('Y-m-d', $result['Date'])->format('d-m-Y');
    }

/**
 *  Recupère la source du repo/section en BDD
 */
    public function db_getSource() {
        global $OS_FAMILY;

        if ($OS_FAMILY == "Redhat") {
            $result = $this->db->querySingleRow("SELECT Source from repos WHERE Name = '$this->name'");
        }

        if ($OS_FAMILY == "Debian") {
            $result = $this->db->querySingleRow("SELECT Source from repos WHERE Name = '$this->name' AND Dist = '$this->dist' AND Section = '$this->section'");
        }
        $this->source = $result['Source'];

        /**
         *  On récupère au passage l'url source complète
         */
        if ($OS_FAMILY == "Debian") {
            $this->getFullSource();
        }
    }

/**
 *  Récupère l'url source complete avec la racine du dépot (Debian uniquement)
 */
    private function getFullSource() {
        global $HOSTS_CONF;

        // Récupère l'url complète dans hosts.conf
        $this->sourceFullUrl = exec("grep '^Name=\"{$this->source}\",Url=' $HOSTS_CONF | awk -F ',' '{print $2}' | cut -d'=' -f2 | sed 's/\"//g'");
        $this->hostUrl = exec("echo '$this->sourceFullUrl' | cut -d'/' -f1");
        // Extraction de la racine de l'hôte (ex pour : ftp.fr.debian.org/debian ici la racine sera debian
        $this->rootUrl = exec("echo '$this->sourceFullUrl' | sed 's/$this->hostUrl//g'");
        if (empty($this->hostUrl)) {
            throw new Exception('<br><span class="redtext">Erreur : </span>impossible de déterminer l\'adresse de l\'hôte source');
        }
        if (empty($this->rootUrl)) {
            throw new Exception('<br><span class="redtext">Erreur : </span>impossible de déterminer la racine de l\'URL hôte');
        }
    }

/**
 *  MODIFICATION DES INFORMATIONS DU REPO
 */
    public function edit() {
        $this->db->exec("UPDATE repos SET Description = '$this->description' WHERE Id = '$this->id'");
        printAlert('Modifications prises en compte');
    }


/**
 *  GENERATION DE CONF
 */
    public function generateConf(string $destination) {
        global $REPOS_PROFILES_CONF_DIR;
        global $REPO_CONF_FILES_PREFIX;
        global $WWW_HOSTNAME;
        global $GPG_SIGN_PACKAGES;
        global $OS_FAMILY;

        // On peut préciser à la fonction le répertoire de destination des fichiers. Si on précise une valeur vide ou bien "default", alors les fichiers seront générés dans le répertoire par défaut
        if (empty($destination) OR $destination == "default") {
            $destination = $REPOS_PROFILES_CONF_DIR;
        }

        // Génération du fichier pour Redhat/Centos
        if ($OS_FAMILY == "Redhat") {
            $content = "# Repo {$this->name} sur ${WWW_HOSTNAME}";
            $content = "${content}\n[${REPO_CONF_FILES_PREFIX}{$this->name}___ENV__]";
            $content = "${content}\nname=Repo {$this->name} sur ${WWW_HOSTNAME}";
            $content = "${content}\ncomment=Repo {$this->name} sur ${WWW_HOSTNAME}";
            $content = "${content}\nbaseurl=https://${WWW_HOSTNAME}/repo/{$this->name}___ENV__";
            $content = "${content}\nenabled=1";
            if ($GPG_SIGN_PACKAGES == "yes") {
            $content = "${content}\ngpgcheck=1";
            $content = "${content}\ngpgkey=https://${WWW_HOSTNAME}/repo/${WWW_HOSTNAME}.pub";
            } else {
            $content = "${content}\ngpgcheck=0";
            }
            // Création du fichier si n'existe pas déjà
            if (!file_exists("${destination}/${REPO_CONF_FILES_PREFIX}{$this->name}.repo")) {
            touch("${destination}/${REPO_CONF_FILES_PREFIX}{$this->name}.repo");
            }
            // Ecriture du contenu dans le fichier
            file_put_contents("${destination}/${REPO_CONF_FILES_PREFIX}{$this->name}.repo", $content);
        }
        // Génération du fichier pour Debian
        if ($OS_FAMILY == "Debian") {
            $content = "# Repo {$this->name}, distribution {$this->dist}, section {$this->section} sur ${WWW_HOSTNAME}";
            $content = "${content}\ndeb https://${WWW_HOSTNAME}/repo/{$this->name}/{$this->dist}/{$this->section}___ENV__ {$this->dist} {$this->section}";
            
            // Si le nom de la distribution contient un slash, c'est le cas par exemple avec debian-security (buster/updates), alors il faudra remplacer ce slash par [slash] dans le nom du fichier .list 
            $checkIfDistContainsSlash = exec("echo $this->dist | grep '/'");
            if (!empty($checkIfDistContainsSlash)) {
            $repoDistFormatted = str_replace("/", "[slash]","$this->dist");
            } else {
            $repoDistFormatted = $this->dist;
            }
            // Création du fichier si n'existe pas déjà
            if (!file_exists("${destination}/${REPO_CONF_FILES_PREFIX}{$this->name}_${repoDistFormatted}_{$this->section}.list")) {
            touch("${destination}/${REPO_CONF_FILES_PREFIX}{$this->name}_${repoDistFormatted}_{$this->section}.list");
            }
            // Ecriture du contenu dans le fichier
            file_put_contents("${destination}/${REPO_CONF_FILES_PREFIX}{$this->name}_${repoDistFormatted}_{$this->section}.list", $content);
        }

        unset($content);
        return 0;
    }

/**
 *  SUPPRESSION DE CONF
 */
    public function deleteConf() {
        global $REPOS_PROFILES_CONF_DIR;
        global $REPO_CONF_FILES_PREFIX;
        global $PROFILES_MAIN_DIR;
        global $PROFILE_SERVER_CONF;
        global $OS_FAMILY;

        if ($OS_FAMILY == "Redhat") {
            // Suppression du fichier si existe
            if (file_exists("${REPOS_PROFILES_CONF_DIR}/${REPO_CONF_FILES_PREFIX}{$this->name}.repo")) {
                unlink("${REPOS_PROFILES_CONF_DIR}/${REPO_CONF_FILES_PREFIX}{$this->name}.repo");
            }

            // Suppression des liens symboliques pointant vers ce repo dans les répertoires de profils 
            $profilesNames = scandir($PROFILES_MAIN_DIR); // Récupération de tous les noms de profils
            foreach($profilesNames as $profileName) {
                if (($profileName != "..") AND ($profileName != ".") AND ($profileName != "_configurations") AND ($profileName != "_reposerver") AND ($profileName != "${PROFILE_SERVER_CONF}")) {
                    if (is_link("${PROFILES_MAIN_DIR}/${profileName}/${REPO_CONF_FILES_PREFIX}{$this->name}.repo")) {
                    unlink("${PROFILES_MAIN_DIR}/${profileName}/${REPO_CONF_FILES_PREFIX}{$this->name}.repo");
                    }
                }
            }
        }

        if ($OS_FAMILY == "Debian") {
            // Si le nom de la distribution contient un slash, c'est le cas par exemple avec debian-security (buster/updates), alors il faudra remplacer ce slash par [slash] dans le nom du fichier .list 
            $checkIfDistContainsSlash = exec("echo $this->dist | grep '/'");
            if (!empty($checkIfDistContainsSlash)) {
                $repoDistFormatted = str_replace("/", "[slash]", $this->dist);
            } else {
                $repoDistFormatted = $this->dist;
            }

            // Suppression du fichier si existe
            if (file_exists("${REPOS_PROFILES_CONF_DIR}/${REPO_CONF_FILES_PREFIX}{$this->name}_${repoDistFormatted}_{$this->section}.list")) {
                unlink("${REPOS_PROFILES_CONF_DIR}/${REPO_CONF_FILES_PREFIX}{$this->name}_${repoDistFormatted}_{$this->section}.list");
            }
            
            // Suppression des liens symboliques pointant vers ce repo dans les répertoires de profils 
            $profilesNames = scandir($PROFILES_MAIN_DIR); // Récupération de tous les noms de profils
            foreach($profilesNames as $profileName) {
                if (($profileName != "..") AND ($profileName != ".") AND ($profileName != "_configurations") AND ($profileName != "_reposerver") AND ($profileName != "${PROFILE_SERVER_CONF}")) {
                    if (is_link("${PROFILES_MAIN_DIR}/${profileName}/${REPO_CONF_FILES_PREFIX}{$this->name}_${repoDistFormatted}_{$this->section}.list")) {
                        unlink("${PROFILES_MAIN_DIR}/${profileName}/${REPO_CONF_FILES_PREFIX}{$this->name}_${repoDistFormatted}_{$this->section}.list");
                    }
                }
            }
        }
    }
}
?>