<?php
/**
 * Exemple simple qui étend la classe SQLite3 et change les paramètres
 * __construct, puis, utilise la méthode de connexion pour initialiser la
 * base de données.
 */
class Database extends SQLite3 {

    public function __construct() {
        $WWW_DIR = dirname(__FILE__, 2);
        global $OS_FAMILY;

        /**
         *  Ouvre la base de données repomanager
         *  Si celle-ci n'existe pas elle est créée automatiquement
         */
        $this->open("${WWW_DIR}/db/repomanager.db");
        $this->busyTimeout(60000);

        /**
         *  Crée la table repos si n'existe pas
         */
        if ($OS_FAMILY == "Redhat") {
            $this->exec("CREATE TABLE IF NOT EXISTS repos (
            Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            Name VARCHAR(255) NOT NULL,
            Source VARCHAR(255) NOT NULL,
            Env VARCHAR(255) NOT NULL,
            Date DATE NOT NULL,
            Time TIME NOT NULL,
            Description VARCHAR(255),
            Signed CHAR(3) NOT NULL,
            Type CHAR(6) NOT NULL,
            Status CHAR(8) NOT NULL);");
        }
        if ($OS_FAMILY == "Debian") {
            $this->exec("CREATE TABLE IF NOT EXISTS repos (
            Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            Name VARCHAR(255) NOT NULL,
            Source VARCHAR(255) NOT NULL,
            Dist VARCHAR(255) NOT NULL,
            Section VARCHAR(255) NOT NULL,
            Env VARCHAR(255) NOT NULL,
            Date DATE NOT NULL,
            Time TIME NOT NULL,
            Description VARCHAR(255),
            Signed CHAR(3) NOT NULL,
            Type CHAR(6) NOT NULL,
            Status CHAR(8) NOT NULL);");
        }

        /**
         *  Crée la table repos_archive si n'existe pas
         */
        if ($OS_FAMILY == "Redhat") {
            $this->exec("CREATE TABLE IF NOT EXISTS repos_archived (
            Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            Name VARCHAR(255) NOT NULL,
            Source VARCHAR(255) NOT NULL,
            Date DATE NOT NULL,
            Time TIME NOT NULL,
            Description VARCHAR(255),
            Signed CHAR(3) NOT NULL,
            Type CHAR(6) NOT NULL,
            Status CHAR(8) NOT NULL);");
        }

        if ($OS_FAMILY == "Debian") {
            $this->exec("CREATE TABLE IF NOT EXISTS repos_archived (
            Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            Name VARCHAR(255) NOT NULL,
            Source VARCHAR(255) NOT NULL,
            Dist VARCHAR(255) NOT NULL,
            Section VARCHAR(255) NOT NULL,
            Date DATE NOT NULL,
            Time TIME NOT NULL,
            Description VARCHAR(255),
            Signed CHAR(3) NOT NULL,
            Type CHAR(6) NOT NULL,
            Status CHAR(8) NOT NULL);");
        }

        /**
         *  Crée la table sources si n'existe pas
         */
        $this->exec("CREATE TABLE IF NOT EXISTS sources (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Name VARCHAR(255) NOT NULL,
        Url VARCHAR(255) NOT NULL,
        Gpgkey VARCHAR(255))");
            
        /** 
         *  Crée la table groups si n'existe pas
         */
        $this->exec("CREATE TABLE IF NOT EXISTS groups (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Name VARCHAR(255) UNIQUE NOT NULL)");

        /**
         *  Crée la table group_members si n'existe pas
         */
        $this->exec("CREATE TABLE IF NOT EXISTS group_members (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Id_repo INTEGER NOT NULL,
        Id_group INTEGER NOT NULL);");

        /**
         *  Crée la table operations si n'existe pas
         */
        $this->exec("CREATE TABLE IF NOT EXISTS operations (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Date DATE NOT NULL,
        Time TIME NOT NULL,
        Action VARCHAR(255) NOT NULL,  /* update, env->env */
        Type CHAR(6) NOT NULL,         /* manual, auto */
        Id_repo_source VARCHAR(255),
        Id_repo_target VARCHAR(255),
        Id_group INTEGER,
        Id_plan INTEGER,               /* si type = auto */
        GpgCheck CHAR(3),
        GpgResign CHAR(3),
        Pid INTEGER NOT NULL,
        Logfile VARCHAR(255) NOT NULL,
        Duration INTEGER,
        Status CHAR(7) NOT NULL)");    /* running, done, stopped */

        /**
         *  Crée la table planifications si n'existe pas
         */
        $this->exec("CREATE TABLE IF NOT EXISTS planifications (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Date DATE NOT NULL,
        Time TIME NOT NULL,
        Action VARCHAR(255) NOT NULL,
        Id_repo INTEGER,
        Id_group INTEGER,
        Gpgcheck CHAR(3),
        Gpgresign CHAR(3),
        Reminder VARCHAR(255),
        Status CHAR(7),
        Error VARCHAR(255),
        Logfile VARCHAR(255))");

        /**
         *  Crée la table profile_package si n'existe pas
         */
        $this->exec("CREATE TABLE IF NOT EXISTS profile_package (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Name VARCHAR(255) UNIQUE NOT NULL)");
        /**
         *  Si la table profile_package est vide (vient d'être créée) alors on la peuple
         */
        $result = $this->query("SELECT * FROM profile_package");
        if ($this->isempty($result)) $this->exec("INSERT INTO profile_package (Name) VALUES ('apache'), ('httpd'), ('php'), ('php-fpm'), ('mysql'), ('fail2ban'), ('nrpe'), ('munin-node'), ('node'), ('newrelic'), ('nginx'), ('haproxy'), ('netdata'), ('nfs'), ('rsnapshot'), ('kernel'), ('java'), ('redis'), ('varnish'), ('mongo'), ('rabbit'), ('clamav'), ('clam'), ('gpg'), ('gnupg')");

        /**
         *  Crée la table profile_service si n'existe pas
         */
        $this->exec("CREATE TABLE IF NOT EXISTS profile_service (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Name VARCHAR(255) UNIQUE NOT NULL)");
        /**
         *  Si la table profile_service est vide (vient d'être créée) alors on la peuple
         */
        $result = $this->query("SELECT * FROM profile_service");
        if ($this->isempty($result)) $this->exec("INSERT INTO profile_service (Name) VALUES ('apache'), ('httpd'), ('php-fpm'), ('mysqld'), ('fail2ban'), ('nrpe'), ('munin-node'), ('nginx'), ('haproxy'), ('netdata'), ('nfsd'), ('redis'), ('varnish'), ('mongod'), ('clamd')");
    }

    /**
     *  Fonction permettant de retourner le nombre de lignes résultant d'une requête
     */
    public function countRows(string $query) {
        $result = $this->query($query);

        /**
         *  Compte le nombre de lignes retournées par la requête
         */
        $count = 0;
        while ($row = $result->fetchArray()) {
            $count++;
        }

        /**
         *  Retourne le nombre de lignes
         */
        return $count;
    }

    /**
     *  Fonction permettant de compter le nombre de lignes résultant d'une requête.
     *  Destinée à remplacer countRows() petit à petit
     */
    public function countRows2($result) {
        /**
         *  Compte le nombre de lignes retournées par la requête
         */
        $count = 0;
        while ($row = $result->fetchArray()) {
            $count++;
        }

        /**
         *  Retourne le nombre de lignes
         */
        return $count;
    }

    /**
     *  Même fonction que ci-dessus mais retourne true si le résultat est vide et false si il est non-vide.
     */
    public function isempty($result) {
        /**
         *  Compte le nombre de lignes retournées par la requête
         */
        $count = 0;
        while ($row = $result->fetchArray()) $count++;

        /**
         *  Si le résultat est vide alors on retourne true
         */
        if ($count == 0) return true;

        /**
         *  Sinon on retourne false
         */
        return false;
    }

    /**
     *  Transforme un résultat de requête ($result = $stmt->execute()) en un array
     *  Valable pour les requêtes en bases renvoyant une seule ligne de résultat
     */
    public function fetch(object $result, string $option = '') {
        global $DEBUG_MODE;

        /**
         *  On vérifie d'abord que $result n'est pas vide, sauf si on a précisé l'option "ignore-null"
         */
        if ($option != "ignore-null") if ($this->isempty($result)) throw new Exception('Erreur : le résultat les données à traiter est vide');

        /**
         *  Fetch le résultat puis retourne l'array créé
         */
        while ($row = $result->fetchArray()) $datas = $row;

        if (!empty($datas)) return $datas;
    }

    /**
     *  Execute une requête retournant 1 seule ligne (LIMIT 1)
     */
    public function querySingleRow(string $query) {
        $result = $this->query("$query LIMIT 1");
        while ($row = $result->fetchArray()) {
            $data = $row;
        }
        if (!empty($data)) {
            return $data;
        }
        return; // Retourne une valeur vide sinon
    }

    /**
     *  Execute une requête et renvoi un array contenant les résultats
     */
    public function queryArray(string $query) {
        $result = $this->query($query);
        while ($row = $result->fetchArray()) {
            $datas = $row;
        }
        if (!empty($datas)) {
            return $datas;
        }
    }
}

class Database_stats extends SQLite3 {

    public function __construct() {
        $WWW_DIR = dirname(__FILE__, 2);
        global $OS_FAMILY;

        /**
         *  Ouvre la base de données repomanager
         *  Si celle-ci n'existe pas elle est créée automatiquement
         */
        $this->open("${WWW_DIR}/db/repomanager-stats.db");
        $this->busyTimeout(60000);

        /**
         *  Crée la table stats si n'existe pas
         */
        $this->exec("CREATE TABLE IF NOT EXISTS stats (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Date DATE NOT NULL,
        Time TIME NOT NULL,
        Id_repo INTEGER NOT NULL,
        Size INTEGER NOT NULL,
        Packages_count INTEGER NOT NULL)");

        /**
         *  Crée la table access si n'existe pas
         */
        $this->exec("CREATE TABLE IF NOT EXISTS access (
            Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            Date DATE NOT NULL,
            Time TIME NOT NULL,
            Source VARCHAR(255) NOT NULL,
            IP VARCHAR(16) NOT NULL,
            Request VARCHAR(255) NOT NULL,
            Request_result VARCHAR(8) NOT NULL)");
    }

    /**
     *  Retourne le détails des 50 dernières requêtes du repo/section spécifié
     */
    public function get_lastAccess(array $parameters = []) {
        global $OS_FAMILY;
        extract($parameters);

        $stmt = $this->prepare("SELECT * FROM access WHERE Request LIKE :likeRequest ORDER BY Date DESC LIMIT 50");

        if ($OS_FAMILY == "Redhat") $stmt->bindValue(':likeRequest', "%/${repo}_${env}/%");
        if ($OS_FAMILY == "Debian") $stmt->bindValue(':likeRequest', "%/$repo/$dist/${section}_${env}/%");

        $result = $stmt->execute();

        $datas = array();

        while ($row = $result->fetchArray()) $datas[] = $row;
        
        return $datas;
    }

    /**
     *  Retourne le détail des requêtes sur le repo/section spécifié, de la dernière minute passée
     */
    public function get_lastMinuteAccess(array $parameters = []) {
        global $OS_FAMILY;
        global $DATE_YMD;
        extract($parameters);
        $currentTime = date("H:i");

        $datas = array();

        $stmt = $this->prepare("SELECT * FROM access WHERE Date = '$DATE_YMD' AND Time LIKE '$currentTime:%' AND Request LIKE :likeRequest ORDER BY Date DESC LIMIT 50");
        if ($OS_FAMILY == "Redhat") $stmt->bindValue(':likeRequest', "%/${repo}_${env}/%");
        if ($OS_FAMILY == "Debian") $stmt->bindValue(':likeRequest', "%/$repo/$dist/${section}_${env}/%");
        $result = $stmt->execute();

        while ($row = $result->fetchArray()) $datas[] = $row;
        
        return $datas;
    }

    /**
     *  Retourne le nombre de requêtes du repo/section spécifié, de la dernière minute passée
     */
    public function get_lastMinuteAccess_count(array $parameters = []) {
        global $OS_FAMILY;
        global $DATE_YMD;
        extract($parameters);
        $currentTime = date("H:i");

        $stmt = $this->prepare("SELECT * FROM access WHERE Date = '$DATE_YMD' AND Time LIKE '$currentTime:%' AND Request LIKE :likeRequest ORDER BY Date DESC LIMIT 50");
        if ($OS_FAMILY == "Redhat") $stmt->bindValue(':likeRequest', "%/${repo}_${env}/%");
        if ($OS_FAMILY == "Debian") $stmt->bindValue(':likeRequest', "%/$repo/$dist/${section}_${env}/%");
        $result = $stmt->execute();

        /**
         *  Compte le nombre de lignes retournées par la requête
         */
        $count = 0;
        while ($row = $result->fetchArray()) $count++;

        /**
         *  Retourne le nombre de lignes
         */
        return $count;
    }

    /**
     *  Retourne le nombre de requêtes en temps réel (date et heure actuelles) sur le repo/section spécifié
     */
    public function get_realTimeAccess_count(array $parameters = []) {
        global $OS_FAMILY;
        global $DATE_YMD;
        extract($parameters);
        $currentTime = date("H:i:s");

        $stmt = $this->prepare("SELECT * FROM access WHERE Date = '$DATE_YMD' AND Time = '$currentTime' AND Request LIKE :likeRequest ORDER BY Date DESC");
        if ($OS_FAMILY == "Redhat") $stmt->bindValue(':likeRequest', "%/${repo}_${env}/%");
        if ($OS_FAMILY == "Debian") $stmt->bindValue(':likeRequest', "%/$repo/$dist/${section}_${env}/%");
        $result = $stmt->execute();

        /**
         *  Compte le nombre de lignes retournées par la requête
         */
        $count = 0;
        while ($row = $result->fetchArray()) $count++;

        /**
         *  Retourne le nombre de lignes
         */
        return $count;
    }

    /**
     *  Compte le nombre de requêtes d'accès au repo/section spécifié, sur une date donnée
     */
    public function get_dailyAccess_count(array $parameters = []) {
        global $OS_FAMILY;
        extract($parameters);

        $stmt = $this->prepare("SELECT * FROM access WHERE Date=:date AND Request LIKE :likeRequest");
        if ($OS_FAMILY == "Redhat") $stmt->bindValue(':likeRequest', "%/${repo}_${env}/%");
        if ($OS_FAMILY == "Debian") $stmt->bindValue(':likeRequest', "%/$repo/$dist/${section}_${env}/%");
        $stmt->bindValue(':date', $date);
        $result = $stmt->execute();

        /**
         *  Compte le nombre de lignes retournées par la requête
         */
        $count = 0;
        while ($row = $result->fetchArray()) $count++;

        /**
         *  Retourne le nombre de lignes
         */
        return $count;
    }
}
?>