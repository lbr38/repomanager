<?php
global $WWW_DIR;
require_once("${WWW_DIR}/class/Database.php");

class Environnement {
    public $db;
    public $name;

    public function __construct(array $variables = []) {
        extract($variables);

        /**
         *  Instanciation d'une db car on peut avoir besoin de récupérer certaines infos en BDD
         */
        try {
            $this->db = new Database();
        } catch(Exception $e) {
            die('Erreur : '.$e->getMessage());
        }


    }

}
?>