<?php
/**
 *  Import des fonctions utiles
 */
require_once('Database-tools.php');

class Database_users extends SQLite3 {

    public function __construct() {
        $WWW_DIR = dirname(__FILE__, 2);
        global $OS_FAMILY;

        /**
         *  Ouvre la base de données repomanager-users.db
         *  Si celle-ci n'existe pas elle est créée automatiquement
         */
        $this->open("${WWW_DIR}/db/repomanager-users.db");
        $this->busyTimeout(60000);

        /**
         *  Crée la table users si n'existe pas
         */
        $this->exec("CREATE TABLE IF NOT EXISTS users (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Name VARCHAR(255) NOT NULL,
        Password VARCHAR(255) NOT NULL)");
    }

    /**
     *  Import de fonctions utiles
     */
    use database_tools;
}
?>