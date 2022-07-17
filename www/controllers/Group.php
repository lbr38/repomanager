<?php

namespace Controllers;

use Exception;

class Group
{
    // public $id;
    private $id;
    private $name;
    private $type;
    private $model;

    public function __construct(string $type)
    {
        /**
         *  Cette class permet de manipuler des groupes de repos ou d'hôtes.
         *  Selon ce qu'on souhaite traiter, la base de données n'est pas la même.
         *  Si on a renseigné une base de données au moment de l'instanciation d'un objet Group alors on utilise cette base
         *  Sinon par défaut on utilise la base principale de repomanager
         */

        if ($type != 'repo' and $type != 'host') {
            throw new Exception("Le type de groupe est invalide");
        }

        $this->type = $type;

        $this->model = new \Models\Group($type);
    }

    public function setId(string $id)
    {
        $this->id = $id;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getType()
    {
        return $this->type;
    }

    /**
     *  Retourne l'Id du groupe à partir de son nom
     */
    public function getIdByName(string $name)
    {
        /**
         *  On vérifie que le groupe existe
         */
        if ($this->exists($name) === false) {
            throw new Exception("Le groupe <b>$name</b> n'existe pas");
        }

        return $this->model->getIdByName($name);
    }

    /**
     *  Retourne le nom du groupe à partir de son Id
     */
    public function getNameById(string $id)
    {
        /**
         *  On vérifie que le groupe existe
         */
        if ($this->existsId($id) === false) {
            throw new Exception("L'Id de groupe <b>$id</b> n'existe pas");
        }

        return $this->model->getNameById($id);
    }

    /**
     *  Retourne true si l'Id du groupe existe en base de données
     */
    public function existsId(string $groupId)
    {
        return $this->model->existsId($groupId);
    }

    /**
     *  Vérifie si le groupe existe en base de données, à partir de son nom
     */
    public function exists(string $name = '')
    {
        return $this->model->exists($name);
    }

    /**
     *  Créer un nouveau groupe
     *  @param name
     */
    public function new(string $name)
    {
        $name = \Controllers\Common::validateData($name);

        /**
         *  1. On vérifie que le nom du groupe ne contient pas de caractères interdits
         */
        if (\Controllers\Common::isAlphanumDash($name) === false) {
            throw new Exception("Le groupe <b>$name</b> contient des caractères invalides");
        }

        /**
         *  2. On vérifie que le groupe n'existe pas déjà
         */
        if ($this->exists($name) === true) {
            throw new Exception("Le groupe <b>$name</b> existe déjà");
        }

        /**
         *  3. Insertion du nouveau groupe
         */
        $this->model->add($name);

        \Models\History::set($_SESSION['username'], 'Création d\'un nouveau groupe <span class="label-white">' . $name . '</span> (type : ' . $this->type . ')', 'success');

        \Controllers\Common::clearCache();
    }

    /**
     *  Renommer un groupe
     *  @param actualName
     *  @param newName
     */
    public function rename(string $actualName, string $newName)
    {
        /**
         *  1. On vérifie que le nom du groupe ne contient pas des caractères interdits
         */
        if (\Controllers\Common::isAlphanumDash($actualName) === false) {
            throw new Exception("Le nom actuel du groupe <b>$actualName</b> contient des caractères invalides");
        }
        if (\Controllers\Common::isAlphanumDash($newName) === false) {
            throw new Exception("Le nouveau nom du groupe <b>$newName</b> contient des caractères invalides");
        }

        /**
         *  2. On vérifie que le nouveau nom de groupe n'existe pas déjà
         */
        if ($this->model->exists($newName) === true) {
            throw new Exception("Le groupe <b>$newName</b> existe déjà");
        }

        /**
         *  3. Renommage du groupe
         */
        $this->model->rename($actualName, $newName);

        \Models\History::set($_SESSION['username'], 'Renommage d\'un groupe : <span class="label-white">' . $actualName . '</span> en <span class="label-white">' . $newName . '</span> (type : ' . $this->type . ')', 'success');

        \Controllers\Common::clearCache();
    }

    /**
     *  Supprimer un groupe
     *  @param name
     */
    public function delete(string $name)
    {
        /**
         *  1. On vérifie que le groupe existe
         */
        if ($this->model->exists($name) === false) {
            throw new Exception("Le groupe <b>$name</b> n'existe pas");
        }

        /**
         *  2. Suppression du groupe en base de données
         */
        $this->model->delete($name);

        \Models\History::set($_SESSION['username'], 'Suppression du groupe <span class="label-white">' . $name . '</span> (type : '. $this->type . ')', 'success');

        \Controllers\Common::clearCache();
    }

    /**
     *  Retourne les informations de tous les groupes en base de données
     *  Sauf le groupe par défaut
     */
    public function listAll()
    {
        return $this->model->listAll();
    }

    /**
     *  Retourne tous les noms de groupes en bases de données
     *  Sauf le groupe par défaut
     */
    public function listAllName()
    {
        return $this->model->listAllName();
    }

    /**
     *  Returns the names of groups in database
     *  With the default group name
     */
    public function listAllWithDefault()
    {
        $groups = $this->model->listAllName();

        /**
         *  Sort by name
         */
        asort($groups);

        /**
         *  Then add default group 'Default' to the end of the list
         */
        $groups[] = 'Default';

        return $groups;
    }

    /**
     *  Supprime des groupes les repos qui n'existent plus
     */
    public function cleanRepos()
    {
        $this->model->cleanRepos();
    }
}
