<?php
/**
 *  Import des fonctions utiles
 */
require_once('Database-tools.php');

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
        $this->exec("CREATE TABLE IF NOT EXISTS env (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Name VARCHAR(255) NOT NULL)");

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
        if ($this->isempty($result) === true) $this->exec("INSERT INTO profile_package (Name) VALUES ('apache'), ('httpd'), ('php'), ('php-fpm'), ('mysql'), ('fail2ban'), ('nrpe'), ('munin-node'), ('node'), ('newrelic'), ('nginx'), ('haproxy'), ('netdata'), ('nfs'), ('rsnapshot'), ('kernel'), ('java'), ('redis'), ('varnish'), ('mongo'), ('rabbit'), ('clamav'), ('clam'), ('gpg'), ('gnupg')");

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
        if ($this->isempty($result) === true) $this->exec("INSERT INTO profile_service (Name) VALUES ('apache'), ('httpd'), ('php-fpm'), ('mysqld'), ('fail2ban'), ('nrpe'), ('munin-node'), ('nginx'), ('haproxy'), ('netdata'), ('nfsd'), ('redis'), ('varnish'), ('mongod'), ('clamd')");
    }

    /**
     *  Import de fonctions utiles
     */
    use database_tools;

    /**
     *  Fonction permettant de retourner le nombre de lignes résultant d'une requête
     */
    /*public function count(object $result) {
        $count = 0;

        while ($row = $result->fetchArray()) $count++;

        return $count;
    }*/

    /**
     *  Retourne true si le résultat est vide et false si il est non-vide.
     */
    /*public function isempty($result) {
        /**
         *  Compte le nombre de lignes retournées par la requête
         */
    /*    $count = 0;

        while ($row = $result->fetchArray()) $count++;

        if ($count == 0) return true;

        return false;
    }*/

    /**
     *  Transforme un résultat de requête ($result = $stmt->execute()) en un array
     */
  /*  public function fetch(object $result, string $option = '') {
        /**
         *  On vérifie d'abord que $result n'est pas vide, sauf si on a précisé l'option "ignore-null"
         */
    /*    if ($option != "ignore-null") {
            if ($this->isempty($result) === true) {
                throw new Exception('Erreur : le résultat les données à traiter est vide');
            }
        }

        $datas = array();

        /**
         *  Fetch le résultat puis retourne l'array créé
         */
     /*   while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $datas = $row;
        }

        return $datas;
    }*/

    /**
     *  Execute une requête et renvoi un array contenant les résultats
     */
    /*public function queryArray(string $query) {
        $result = $this->query($query);

        while ($row = $result->fetchArray()) $datas = $row;

        if (!empty($datas)) return $datas;
    }*/
}
?>