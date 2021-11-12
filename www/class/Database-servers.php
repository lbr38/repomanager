<?php
/**
 *  Import des fonctions utiles
 */
require_once('Database-tools.php');

class Database_servers extends SQLite3 {

    public function __construct() {
        $WWW_DIR = dirname(__FILE__, 2);
        global $OS_FAMILY;

        /**
         *  Ouvre la base de données repomanager
         *  Si celle-ci n'existe pas elle est créée automatiquement
         */
        $this->open("${WWW_DIR}/db/repomanager-servers.db");
        $this->busyTimeout(60000);

        /**
         *  Crée la table servers si n'existe pas
         *  Online_status : online / unreachable
         *  Status : active / disabled / deleted
         *  Last_update_status : done / running / error
         */
        $this->exec("CREATE TABLE IF NOT EXISTS servers (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Ip VARCHAR(15) NOT NULL,
        Hostname VARCHAR(255) NOT NULL,
        Online_status VARCHAR(11) NOT NULL,
        Online_status_date DATE NOT NULL,
        Online_status_time TIME NOT NULL,
        Last_update_status VARCHAR(7) NOT NULL,
        Last_update_date DATE NOT NULL,
        Last_update_time TIME NOT NULL,
        Last_update_report VARCHAR(255),
        Available_packages_count INTEGER NOT NULL,
        Status VARCHAR(8))");

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
        Id_server INTEGER NOT NULL,
        Id_group INTEGER NOT NULL);");
    }

    /**
     *  Import de fonctions utiles
     */
    use database_tools;
}
?>