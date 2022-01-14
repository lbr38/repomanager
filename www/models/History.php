<?php

class History {

    static function set(string $username, string $action, string $state = null)
    {
        $username = validateData($username);
        $action   = validateData($action);
        $state    = validateData($state);

        /**
         *  Ouverture d'une connexion à la base de données
         *  pas d'objet ici car il s'agit d'une classe static
         */
        $db = new Connection('main', 'rw');

        /**
         *  Récupération de l'ID de l'utilisateur à partir de son username
         */
        try {
            $stmt = $db->prepare("SELECT Id FROM users WHERE Username = :username");
            $stmt->bindValue(':username', $username);
            $result = $stmt->execute();

            while ($row = $result->fetchArray(SQLITE3_ASSOC)) $user_id = $row['Id'];

            /**
             *  Si l'Id retourné est vide on lance une exception
             */
            if (empty($user_id)) throw new Exception();

        } catch(Exception $e) {
            printAlert('Une erreur est survenue lors de l\'exécution de la requête en base de données (Err. CH.01)', 'error');
        }

        try {
            $dateNow = date('Y-m-d');
            $timeNow = date('H:i:s');
            $stmt = $db->prepare("INSERT INTO history ('Date', 'Time', 'Id_user', 'Action', 'State') VALUES ('$dateNow', '$timeNow', :id_user, :action, :state)");
            $stmt->bindValue(':id_user', $user_id);
            $stmt->bindValue(':action', $action);
            $stmt->bindValue(':state', $state);
            $stmt->execute();
        } catch(Exception $e) {
            printAlert('Une erreur est survenue lors de l\'exécution de la requête en base de données (Err. CH.02)', 'error');
        }
    }
}
?>