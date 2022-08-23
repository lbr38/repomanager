<?php

namespace Models;

use Exception;

/**
 *  Historique des actions effectuées par les utilisateurs
 */

class History
{
    /**
     *  Récupérer l'historique complet
     */
    public static function getAll()
    {
        /**
         *  Ouverture d'une connexion à la base de données
         *  pas d'objet ici car il s'agit d'une classe static
         */
        $db = new Connection('main');

        try {
            $result = $db->query("SELECT history.Id, history.Date, history.Time, history.Action, history.State, users.First_name, users.Last_name, users.Username FROM history JOIN users ON history.Id_user = users.Id ORDER BY Date DESC, Time DESC");
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
            return;
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $datas[] = $row;
        }

        return $datas;
    }

    /**
     *  Récupérer l'historique complet d'un utilisateur
     */
    public static function getByUser(string $userId)
    {
        $userId = \Controllers\Common::validateData($userId);

        /**
         *  On vérifie que l'Id est valide
         */
        if (!is_numeric($userId)) {
            printAlert('User Id is invalid', 'error');
            return;
        }

        /**
         *  Ouverture d'une connexion à la base de données
         *  pas d'objet ici car il s'agit d'une classe static
         */
        $db = new Connection('main');

        try {
            $stmt = $db->prepare("SELECT history.Id, history.Date, history.Time, history.Action, history.State, users.First_name, users.Last_name, users.Username FROM history JOIN users ON history.Id_user = users.Id WHERE history.Id_user = :userid ORDER BY Date DESC, Time DESC");
            $stmt->bindValue(':userid', $userId);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
            return;
        }

        $datas = array();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $datas[] = $row;
        }

        return $datas;
    }

    /**
     *  Ajouter une nouvelle ligne d'historique en base de données
     */
    public static function set(string $username, string $action, string $state = null)
    {
        date_default_timezone_set('Europe/Paris');

        $username = \Controllers\Common::validateData($username);
        $action   = \Controllers\Common::validateData($action);
        $state    = \Controllers\Common::validateData($state);

        /**
         *  Ouverture d'une connexion à la base de données
         *  pas d'objet ici car il s'agit d'une classe static
         */
        $db = new Connection('main');

        /**
         *  Récupération de l'ID de l'utilisateur à partir de son username
         */
        try {
            $stmt = $db->prepare("SELECT Id FROM users WHERE Username = :username");
            $stmt->bindValue(':username', $username);
            $result = $stmt->execute();

            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $user_id = $row['Id'];
            }

            /**
             *  Si l'Id retourné est vide on lance une exception
             */
            if (empty($user_id)) {
                throw new Exception();
            }
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
            return;
        }

        try {
            $dateNow = date('Y-m-d');
            $timeNow = date('H:i:s');
            $stmt = $db->prepare("INSERT INTO history ('Date', 'Time', 'Id_user', 'Action', 'State') VALUES ('$dateNow', '$timeNow', :id_user, :action, :state)");
            $stmt->bindValue(':id_user', $user_id);
            $stmt->bindValue(':action', $action);
            $stmt->bindValue(':state', $state);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }
}
