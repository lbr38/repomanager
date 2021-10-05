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
         *  SQLite ne permet pas la création d'une colonne Group car il s'agit d'un mot clé SQL, 
         *  c'est la raison pour laquelle toutes les colonnes de cette table commencent par Plan_
         *  
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
         *  Crée la table sources si n'existe pas
         */
        $this->exec("CREATE TABLE IF NOT EXISTS sources (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Name VARCHAR(255) NOT NULL,
        Url VARCHAR(255) NOT NULL,
        Gpgkey VARCHAR(255))");
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
?>