<?php

namespace Models;

use Exception;

class Stat extends Model
{
    public function __construct()
    {
        /**
         *  Ouverture d'une connexion à la base de données
         */
        $this->getConnection('stats');
    }

    /**
     *  Ajoute de nouvelles statistiques à la table stats
     */
    public function add(string $date, string $time, string $repoSize, string $packagesCount, string $envId)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO stats (Date, Time, Size, Packages_count, Id_env) VALUES (:date, :time, :size, :packages_count, :envId)");
            $stmt->bindValue(':date', $date);
            $stmt->bindValue(':time', $time);
            $stmt->bindValue(':size', $repoSize);
            $stmt->bindValue(':packages_count', $packagesCount);
            $stmt->bindValue(':envId', $envId);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Retourne tout le contenu de la table stats
     */
    public function getAll(string $envId)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM stats WHERE Id_env = :envId");
            $stmt->bindValue('envId', $envId);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        $datas = array();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $datas[] = $row;
        }

        return $datas;
    }

    /**
     *  Retourne le détails des 50 dernières requêtes du repo/section spécifié
     */
    public function getLastAccess(string $name, string $dist = null, string $section = null, string $env)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM access WHERE Request LIKE :request ORDER BY Date DESC, Time DESC LIMIT 50");

            if (!empty($dist) and !empty($section)) {
                $stmt->bindValue(':request', "%/${name}/${dist}/${section}_${env}/%");
            } else {
                $stmt->bindValue(':request', "%/${name}_${env}/%");
            }
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        $datas = array();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $datas[] = $row;
        }

        return $datas;
    }

    /**
     *  Retourne le détail des requêtes sur le repo/section spécifié, des 5 dernières minutes
     */
    public function getLastMinutesAccess(string $name, string $dist = null, string $section = null, string $env)
    {
        $timeEnd   = date("H:i:s");
        $timeStart = date('H:i:s', strtotime('-5 minutes', strtotime($timeEnd)));

        try {
            $stmt = $this->db->prepare("SELECT * FROM access WHERE Date = '" . DATE_YMD . "' AND Time BETWEEN '$timeStart' AND '$timeEnd' AND Request LIKE :request ORDER BY Date DESC LIMIT 30");
            if (!empty($dist) and !empty($section)) {
                $stmt->bindValue(':request', "%/${name}/${dist}/${section}_${env}/%");
            } else {
                $stmt->bindValue(':request', "%/${name}_${env}/%");
            }
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        $datas = array();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $datas[] = $row;
        }

        return $datas;
    }

    /**
     *  Retourne le détail des requêtes en temps réel (date et heure actuelles +/- 5sec) sur le repo/section spécifié
     */
    public function getRealTimeAccess(string $name, string $dist = null, string $section = null, string $env)
    {
        $timeEnd   = date("H:i:s");
        $timeStart = date('H:i:s', strtotime('-5 seconds', strtotime($timeEnd)));

        try {
            $stmt = $this->db->prepare("SELECT * FROM access WHERE Date = '" . DATE_YMD . "' AND Time BETWEEN '$timeStart' AND '$timeEnd' AND Request LIKE :request ORDER BY Date DESC");
            if (!empty($dist) and !empty($section)) {
                $stmt->bindValue(':request', "%/${name}/${dist}/${section}_${env}/%");
            } else {
                $stmt->bindValue(':request', "%/${name}_${env}/%");
            }
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        $datas = array();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $datas[] = $row;
        }

        return $datas;
    }

    /**
     *  Compte le nombre de requêtes d'accès au repo/section spécifié, sur une date donnée
     */
    public function getDailyAccessCount(string $name, string $dist = null, string $section = null, string $env, string $date)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM access WHERE Date = :date AND Request LIKE :request");
            if (!empty($dist) and !empty($section)) {
                $stmt->bindValue(':request', "%/${name}/${dist}/${section}_${env}/%");
            } else {
                $stmt->bindValue(':request', "%/${name}_${env}/%");
            }
            $stmt->bindValue(':date', $date);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        /**
         *  Compte le nombre de lignes retournées par la requête
         */
        $count = $this->db->count($result);

        /**
         *  Retourne le nombre de lignes
         */
        return $count;
    }

    /**
     *  Fermeture de la connexion à la base de données
     */
    public function closeConnection()
    {
        $this->db->close();
    }
}
