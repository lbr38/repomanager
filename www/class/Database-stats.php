<?php
/**
 *  Import des fonctions utiles
 */
require_once('Database-tools.php');

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
     *  Import de fonctions utiles
     */
    use database_tools;

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