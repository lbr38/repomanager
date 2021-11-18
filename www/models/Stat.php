<?php

require_once("${WWW_DIR}/models/Model.php");

class Stat extends Model {

    /**
     *  Retourne le détails des 50 dernières requêtes du repo/section spécifié
     */
    public function get_lastAccess(array $parameters = [])
    {
        global $OS_FAMILY;
        extract($parameters);

        $stmt = $this->db->prepare("SELECT * FROM access WHERE Request LIKE :likeRequest ORDER BY Date DESC, Time DESC LIMIT 50");

        if ($OS_FAMILY == "Redhat") $stmt->bindValue(':likeRequest', "%/${repo}_${env}/%");
        if ($OS_FAMILY == "Debian") $stmt->bindValue(':likeRequest', "%/${repo}/${dist}/${section}_${env}/%");

        $result = $stmt->execute();

        $datas = array();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) $datas[] = $row;
        
        return $datas;
    }

    /**
     *  Retourne le détail des requêtes sur le repo/section spécifié, des 5 dernières minutes
     */
    public function get_lastMinutesAccess(array $parameters = [])
    {
        global $OS_FAMILY;
        global $DATE_YMD;
        extract($parameters);

        $timeEnd   = date("H:i:s");
        $timeStart = date('H:i:s',strtotime('-5 minutes',strtotime($timeEnd)));

        $datas = array();

        $stmt = $this->db->prepare("SELECT * FROM access WHERE Date = '$DATE_YMD' AND Time BETWEEN '$timeStart' AND '$timeEnd' AND Request LIKE :likeRequest ORDER BY Date DESC LIMIT 30");
        if ($OS_FAMILY == "Redhat") $stmt->bindValue(':likeRequest', "%/${repo}_${env}/%");
        if ($OS_FAMILY == "Debian") $stmt->bindValue(':likeRequest', "%/$repo/$dist/${section}_${env}/%");
        $result = $stmt->execute();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) $datas[] = $row;
        
        return $datas;
    }

    /**
     *  Retourne le détail des requêtes en temps réel (date et heure actuelles +/- 5sec) sur le repo/section spécifié
     */
    public function get_realTimeAccess(array $parameters = [])
    {
        global $OS_FAMILY;
        global $DATE_YMD;
        extract($parameters);

        $timeEnd   = date("H:i:s");
        $timeStart = date('H:i:s',strtotime('-5 seconds',strtotime($timeEnd)));

        $datas = array();
        
        $stmt = $this->db->prepare("SELECT * FROM access WHERE Date = '$DATE_YMD' AND Time BETWEEN '$timeStart' AND '$timeEnd' AND Request LIKE :likeRequest ORDER BY Date DESC");
        if ($OS_FAMILY == "Redhat") $stmt->bindValue(':likeRequest', "%/${repo}_${env}/%");
        if ($OS_FAMILY == "Debian") $stmt->bindValue(':likeRequest', "%/$repo/$dist/${section}_${env}/%");
        $result = $stmt->execute();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) $datas[] = $row;
        
        return $datas;
    }

    /**
     *  Compte le nombre de requêtes d'accès au repo/section spécifié, sur une date donnée
     */
    public function get_dailyAccess_count(array $parameters = [])
    {
        global $OS_FAMILY;
        extract($parameters);

        $stmt = $this->db->prepare("SELECT * FROM access WHERE Date=:date AND Request LIKE :likeRequest");
        if ($OS_FAMILY == "Redhat") $stmt->bindValue(':likeRequest', "%/${repo}_${env}/%");
        if ($OS_FAMILY == "Debian") $stmt->bindValue(':likeRequest', "%/$repo/$dist/${section}_${env}/%");
        $stmt->bindValue(':date', $date);
        $result = $stmt->execute();

        /**
         *  Compte le nombre de lignes retournées par la requête
         */
        $count = $this->db->count($result);

        /**
         *  Retourne le nombre de lignes
         */
        return $count;
    }
}
?>