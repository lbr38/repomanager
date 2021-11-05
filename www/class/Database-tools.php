<?php
trait database_tools {
    /**
     *  Retourne true si le résultat est vide et false si il est non-vide.
     */
    public function isempty($result) {
        /**
         *  Compte le nombre de lignes retournées par la requête
         */
        $count = 0;

        while ($row = $result->fetchArray()) $count++;

        if ($count == 0) return true;

        return false;
    }

    /**
     *  Fonction permettant de retourner le nombre de lignes résultant d'une requête
     */
    public function count(object $result) {
        $count = 0;

        while ($row = $result->fetchArray()) $count++;

        return $count;
    }

    /**
     *  Transforme un résultat de requête ($result = $stmt->execute()) en un array
     */
    public function fetch(object $result, string $option = '') {
        /**
         *  On vérifie d'abord que $result n'est pas vide, sauf si on a précisé l'option "ignore-null"
         */
        if ($option != "ignore-null") {
            if ($this->isempty($result) === true) {
                throw new Exception('Erreur : le résultat les données à traiter est vide');
            }
        }

        $datas = array();

        /**
         *  Fetch le résultat puis retourne l'array créé
         */
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $datas = $row;
        }

        return $datas;
    }

    /**
     *  Execute une requête et renvoi un array contenant les résultats
     */
    public function queryArray(string $query) {
        $result = $this->query($query);

        while ($row = $result->fetchArray()) $datas = $row;

        if (!empty($datas)) return $datas;
    }
}
?>    