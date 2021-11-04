<?php
/**
 *  Import des variables nécessaires
 */
$WWW_DIR = dirname(__FILE__, 2);
require_once("${WWW_DIR}/functions/load_common_variables.php");
require_once("${WWW_DIR}/functions/common-functions.php");
require_once("${WWW_DIR}/class/Operation.php");
require_once("${WWW_DIR}/class/Repo.php");
 
/**
 *  1. Récupération de l'argument : type d'opération à exécuter
 *  Ne peut être vide.
 *  Valeurs possibles :
 *      new
 *      update
 *      duplicate
 *      reconstruct
 * 
 *  Le premier paramètre passé à getopt est null : on ne souhaites pas travailler avec des options courtes.
 *  Plus d'infos sur getopt() : https://blog.pascal-martin.fr/post/php-5.3-getopt-parametres-ligne-de-commande/
 */
$getOptions = getopt(null, ["action:"]);
$opAction = $getOptions['action'];

if (empty($opAction)) throw new Exception("Erreur : type d'opération non défini");
if ($opAction != "new" AND $opAction != "update" AND $opAction != "reconstruct" AND $opAction != "duplicate") throw new Exception("Erreur : type d'opération invalide");

/**
 *  2. Récupération des arguments suivants, différents en fonction du type d'opération précédemment récupéré.
 *  Puis exécution de l'opération
 */
if ($opAction == "new") {
    if ($OS_FAMILY == "Redhat") $options = getopt(null, ["name:", "source:", "gpgCheck:", "gpgResign:", "group:", "description:", "type:"]);
    if ($OS_FAMILY == "Debian") $options = getopt(null, ["name:", "dist:", "section:", "source:", "gpgCheck:", "gpgResign:", "group:", "description:", "type:"]);

    /**
     *  Vérification que les paramètres obligatoires ne sont pas vides
     */
    if (empty($options['name']))        throw new Exception("Erreur : nom du repo non défini");
    if (empty($options['source']))      throw new Exception("Erreur : repo source non défini");
    if (empty($options['gpgCheck']))    throw new Exception("Erreur : gpg check non défini");
    if (empty($options['gpgResign']))   throw new Exception("Erreur : gpg resign non défini");
    if (empty($options['group']))       throw new Exception("Erreur : groupe non défini");
    if (empty($options['description'])) throw new Exception("Erreur : description non définie");
    if (empty($options['type']))        throw new Exception("Erreur : type de repo non défini");
    /**
     *  Sur Debian on attends 2 paramètres supplémentaires
     */
    if ($OS_FAMILY == "Debian") {
        if (empty($options['dist']))        throw new Exception("Erreur : nom de la distribution non défini");
        if (empty($options['section']))     throw new Exception("Erreur : nom de la section non défini");
    }

    /**
     * Création d'une nouvelle opération
     */
    $op = new Operation(array('op_action' => 'new', 'op_type' => 'manual'));

    /**
     * 	Création d'un objet Repo avec les infos du repo à créer
     */
    if ($OS_FAMILY == "Redhat") $op->repo = new Repo(array(
        'repoName'          => $options['name'],
        'repoSource'        => $options['source'],
        'repoGroup'         => $options['group'],
        'repoDescription'   => $options['description'],
        'repoGpgCheck'      => $options['gpgCheck'],
        'repoGpgResign'     => $options['gpgResign'],
        'repoType'          => $options['type']
    ));
    if ($OS_FAMILY == "Debian") $op->repo = new Repo(array(
        'repoName'          => $options['name'],
        'repoSource'        => $options['source'],
        'repoDist'          => $options['dist'],
        'repoSection'       => $options['section'],
        'repoGroup'         => $options['group'],
        'repoDescription'   => $options['description'],
        'repoGpgCheck'      => $options['gpgCheck'],
        'repoGpgResign'     => $options['gpgResign'],
        'repoType'          => $options['type']
    ));

    /**
     * 	Exécution de l'opération "nouveau repo"
     */
    $op->exec_new();
}

if ($opAction == "update") {
    $options = getopt(null, ["id:", "gpgCheck:", "gpgResign:"]);

    /**
     *  Vérification que les paramètres obligatoires ne sont pas vides
     */
    if (empty($options['id']))          throw new Exception("Erreur : id du repo non défini");
    if (empty($options['gpgCheck']))    throw new Exception("Erreur : gpg check non défini");
    if (empty($options['gpgResign']))   throw new Exception("Erreur : gpg resign non défini");

    /**
     * Création d'une nouvelle opération
     */
    $op = new Operation(array('op_action' => 'update', 'op_type' => 'manual'));

    /**
     * 	Création d'un objet Repo avec les infos du repo à mettre à jour
     */
    $op->repo = new Repo(array('repoId' => $options['id']));

    /**
     *  Les paramètres GPG Check et GPG Resign sont conservées de côté et seront pris en compte au début de l'exécution de exec_update()
     */
    $op->gpgCheck  = $options['gpgCheck'];
    $op->gpgResign = $options['gpgResign'];

    /**
     * 	Exécution de l'opération "mise à jour du repo"
     */
    $op->exec_update();
}

if ($opAction == "duplicate") {
    if ($OS_FAMILY == "Redhat") $options = getopt(null, ["name:", "newname:", "env:", "group:", "description:", "type:"]);
    if ($OS_FAMILY == "Debian") $options = getopt(null, ["name:", "dist:", "section:", "newname:", "env:", "group:", "description:", "type:"]);

    /**
     *  Vérification que les paramètres obligatoires ne sont pas vides
     */
    if (empty($options['name']))        throw new Exception("Erreur : nom du repo non défini");
    if (empty($options['newname']))     throw new Exception("Erreur : nom du nouveau repo non défini");
    if (empty($options['group']))       throw new Exception("Erreur : groupe non défini");
    if (empty($options['description'])) throw new Exception("Erreur : description non définie");
    if (empty($options['env']))         throw new Exception("Erreur : environnement du repo source non défini");
    if (empty($options['type']))        throw new Exception("Erreur : type de repo non défini");
    /**
     *  Sur Debian on attends 2 paramètres supplémentaires
     */
    if ($OS_FAMILY == "Debian") {
        if (empty($options['dist']))        throw new Exception("Erreur : nom de la distribution non défini");
        if (empty($options['section']))     throw new Exception("Erreur : nom de la section non défini");
    }

    /**
     * Création d'une nouvelle opération
     */
    $op = new Operation(array('op_action' => 'duplicate', 'op_type' => 'manual'));

    /**
     * 	Création d'un objet Repo avec les infos du repo à dupliquer
     */
    if ($OS_FAMILY == "Redhat") $op->repo = new Repo(array(
        'repoName'          => $options['name'],
        'repoNewName'       => $options['newname'],
        'repoEnv'           => $options['env'],
        'repoGroup'         => $options['group'],
        'repoDescription'   => $options['description'],
        'repoType'          => $options['type'] 
    ));
    if ($OS_FAMILY == "Debian") $op->repo = new Repo(array(
        'repoName'          => $options['name'],
        'repoNewName'       => $options['newname'],
        'repoDist'          => $options['dist'],
        'repoSection'       => $options['section'],
        'repoEnv'           => $options['env'],
        'repoGroup'         => $options['group'],
        'repoDescription'   => $options['description'],
        'repoType'          => $options['type']        
    ));

    /**
     * 	Exécution de l'opération "mise à jour du repo"
     */
    $op->exec_duplicate();
}

if ($opAction == "reconstruct") {
    
    $options = getopt(null, ["id:", "gpgResign:"]);

    /**
     *  Vérification que les paramètres obligatoires ne sont pas vides
     */
    if (empty($options['id']))          throw new Exception("Erreur : id du repo non défini");
    if (empty($options['gpgResign']))   throw new Exception("Erreur : gpg resign non défini");

    /**
     * Création d'une nouvelle opération
     */
    $op = new Operation(array('op_action' => 'reconstruct', 'op_type' => 'manual'));

    /**
     * 	Création d'un objet Repo avec les infos du repo à reconstruire
     *  On n'inclut pas gpgResign à la construction de l'objet car sa valeur va être écrasée par db_getAllById() puis rectifiée plus bas avant d'exécuter l'opération
     */
    $op->repo = new Repo(array('repoId' => $options['id']));

    /**
     * 	On vérifie que l'ID passé en paramètre existe en BDD
     */
    if ($op->repo->existsId() === false) throw new Exception("Erreur : l'id du repo renseigné n'existe pas");

    /**
     * 	On récupère toutes les infos du repo en BDD
     */
    $op->repo->db_getAllById();

    /**
     * 	On écrase la propriété $op->repo->gpgResign et $op->repo->signed (set par db_getAllById juste au dessus) par la valeur de $options['gpgResign'] transmise, pour éviter par exemple de signer le repo alors qu'on a transmis $options['gpgResign'] = no
     */
    $op->repo->gpgResign = $options['gpgResign'];
    $op->repo->signed = $options['gpgResign'];

    /**
     * 	Exécution de la fonction
     */
    $op->exec_reconstruct();
}

exit(0);

?>