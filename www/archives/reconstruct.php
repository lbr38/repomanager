<?php
/**
 * Import des variables nécessaires
 */
$WWW_DIR = dirname(__FILE__, 2);
require_once("${WWW_DIR}/functions/load_common_variables.php");
require_once("${WWW_DIR}/functions/common-functions.php");
require_once("${WWW_DIR}/class/Operation.php");
require_once("${WWW_DIR}/class/Repo.php");
 
/**
 * 	Cas où ce script a été appelé avec des arguments, on récupère ces arguments et on exécute directement la fonction de génération de conf
 */
if (!empty($argv)) {
	if (!empty($argv[1])) { $repoId = $argv[1]; } else { throw new Exception("Erreur : Id du repo non défini"); }
	if (!empty($argv[2])) { $repoGpgResign = $argv[2]; } else { throw new Exception("Erreur : gpg resign non défini"); }
}

/**
 * 	Traitement
 */

/**
 *	Création d'une nouvelle opération
 */
$op = new Operation(array('op_action' => 'reconstruct', 'op_type' => 'manual'));

/**
 * 	Création d'un objet Repo avec les infos du repo à reconstruire
 */
$op->repo = new Repo(compact('repoId', 'repoGpgResign'));

/**
 * 	On vérifie que l'ID passé en paramètre existe en BDD
 */
if ($op->repo->existsId() === false) throw new Exception("Erreur : l'ID du repo renseigné n'existe pas");

/**
 * 	On récupère toutes les infos du repo en BDD
 */
$op->repo->db_getAllById();

/**
 * 	On écrase la propriété $op->repo->gpgResign et $op->repo->signed (set par db_getAllById juste au dessus) par la valeur de $repoGpgResign transmise, pour éviter par exemple de signer le repo alors qu'on a transmis $repoGpgResign = no
 */
$op->repo->gpgResign = $repoGpgResign;
$op->repo->signed = $repoGpgResign;

/**
 * 	Exécution de la fonction
 */
$op->exec_reconstruct();

exit(0);
?>