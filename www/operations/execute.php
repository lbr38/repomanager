<?php
/**
 *  Import des variables nécessaires
 */
define("ROOT", dirname(__FILE__, 2));
require_once(ROOT."/models/Autoloader.php");
Autoloader::loadFromApi();

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
$getOptions = getopt(null, ["id:"]);

/**
 *  Récupération de l'Id de l'opération à traiter
 */
if (empty($getOptions['id'])) {
    throw new Exception("Erreur : l'Id d'opération non défini");
    exit(1);
}

$id = $getOptions['id'];

/**
 *  Récupération des détails de l'opération à traiter, sous forme d'array
 */
if (!file_exists(POOL."/${id}.json")) {
    throw new Exception("Erreur : impossible de récupérer les détails de l'opération (id $id) : le fichier est introuvable");
    exit(1);
}

$operation_params = json_decode(file_get_contents(POOL."/${id}.json"), true);

/**
 *  Traitement de chaque opération
 */
foreach ($operation_params as $operation) {
    $action     = $operation['action'];
    $repoStatus = $operation['repoStatus'];

    /**
     *  Un Id de repo a été renseigné seulement dans le cas où l'action n'est pas 'new'
     */
    if ($action !== 'new') {
        $repoId = $operation['repoId'];
    }

    /**
     *  Si aucun groupe et/ou description n'a été renseigné alors on set la valeur à 'nogroup' ou 'nodescription'
     */
    if (empty($operation['targetGroup'])) {
        $targetGroup = 'nogroup';
    } else {
        $targetGroup = $operation['targetGroup'];
    }
    if (empty($operation['targetDescription'])) {
        $targetDescription = 'nodescription';
    } else {
        $targetDescription = $operation['targetDescription'];
    }

    /**
     *  Si l'action est 'new'
     */
    if ($action == 'new') {
        /**
         *  Si le paramètre Type n'est pas défini, on quitte
         */
        if (empty($operation['type'])) {
            throw new Exception("Operation 'new' - Erreur : le paramètre Type n'est pas défini");
            continue;
        }
        $type = $operation['type'];


        if (OS_FAMILY == 'Debian') {
            /**
             *  Si le paramètre Dist n'est pas défini, on quitte
             */
            if (empty($operation['dist'])) {
                throw new Exception("Operation 'new' - Erreur : le paramètre Dist n'est pas défini");
                continue;
            }
            $dist = $operation['dist'];

            /**
             *  Si le paramètre Section n'est pas défini, on quitte
             */
            if (empty($operation['section'])) {
                throw new Exception("Operation 'new' - Erreur : le paramètre Section n'est pas défini");
                continue;
            }
            $section = $operation['section'];
        }

        /**
         *  Si le type est 'mirror' alors on vérifie des paramètres supplémentaires
         */
        if ($type === 'mirror') {
            /**
             *  Si le paramètre Source n'est pas défini, on quitte
             */
            if (empty($operation['source'])) {
                throw new Exception("Operation 'new' - Erreur : le paramètre Source n'est pas défini");
                continue;
            }
            $source = $operation['source'];

            /**
             *  Si le paramètre GPG Check n'est pas défini, on quitte
             */
            if (empty($operation['targetGpgCheck'])) {
                throw new Exception("Operation 'new' - Erreur : le paramètre GPG Check n'est pas défini");
                continue;
            }
            $targetGpgCheck = $operation['targetGpgCheck'];

            /**
             *  Si le paramètre GPG Resign n'est pas défini, on quitte
             */
            if (empty($operation['targetGpgResign'])) {
                throw new Exception("Operation 'new' - Erreur : le paramètre GPG Resign n'est pas défini");
                continue;
            }
            $targetGpgResign = $operation['targetGpgResign'];
        }

        /**
         *  Le paramètre Alias peut être vide dans le cas d'un type = 'mirror', si c'est le cas alors il pendra comme valeur 'source'
         *  Le paramètre Alias ne peut pas être vide dans le cas d'un type = 'local'
         */
        if ($type === 'mirror') {
            if (empty($operation['alias'])) {
                $alias = $source;
            } else {
                $alias = $operation['alias'];
            }
        }
        if ($type === 'local') {
            if (empty($operation['alias'])) {
                throw new Exception("Operation 'new' - Erreur : le paramètre Alias (Name) n'est pas défini");
                continue;
            } else {
                $alias = $operation['alias'];
            }        
        }

        /**
         *  Création d'une nouvelle opération
         */
        $op = new Operation();
        $op->setAction('new');
        $op->setType('manual');
        /**
         * 	Création d'un objet Repo avec les infos spécifiées par l'utilisateur
         */
        $op->repo = new Repo();
        $op->repo->setType($type);
        $op->repo->setName($alias);
        $op->repo->setTargetGroup($targetGroup);
        $op->repo->setTargetDescription($targetDescription);
        if (OS_FAMILY == 'Debian') {
            $op->repo->setDist($dist);
            $op->repo->setSection($section);
        }
        if ($type === 'mirror') {
            $op->repo->setSource($source);
            $op->repo->setTargetGpgCheck($targetGpgCheck);
            $op->repo->setTargetGpgResign($targetGpgResign);
        }
        /**
         * 	Exécution de l'opération
         */
        if ($type === 'mirror') {
            $op->exec_new();
        }
        if ($type === 'local') {
            $op->exec_newLocalRepo();
        }
    }
    /**
     *  Si l'action est 'update'
     */
    if ($action == 'update') {
        /**
         *  Si le paramètre GPG Check n'est pas défini on quitte
         */
        if (empty($operation['targetGpgCheck'])) {
            throw new Exception("Operation 'update' - Erreur : le paramètre GPG Check n'est pas défini");
            continue;
        }
        $targetGpgCheck = $operation['targetGpgCheck'];

        /**
         *  Si le paramètre GPG Resign n'est pas défini on quitte
         */
        if (empty($operation['targetGpgResign'])) {
            throw new Exception("Operation 'update' - Erreur : le paramètre GPG Resign n'est pas défini");
            continue;
        }
        $targetGpgResign = $operation['targetGpgResign'];

        /**
         *  Création d'une nouvelle opération
         */
        $op = new Operation();
        $op->setAction('update');
        $op->setType('manual');
        /**
         * 	Création d'un objet Repo avec les infos du repo source
         */
        $op->repo = new Repo();
        $op->repo->setId($repoId);
        /**
         *  On récupère toutes les infos du repo en base de données
         */
        $op->repo->db_getAllById('active');
        /**
         *  Set de GPG Check
         */
        $op->repo->setTargetGpgCheck($targetGpgCheck);
        /**
         *  Set de GPG Resign
         */
        $op->repo->setTargetGpgResign($targetGpgResign);
        /**
         * 	Exécution de l'opération
         */
        $op->exec_update();
    }
    /**
     *  Si l'action est 'duplicate'
     */
    if ($action == 'duplicate') {
        /**
         *  Si le nouveau nom n'est pas défini on quitte
         */
        if (empty($operation['targetName'])) {
            throw new Exception("Operation 'duplicate' - Erreur : le nouveau nom n'est pas défini");
            continue;
        }
        $targetName = $operation['targetName'];
        
        /**
         *  Création d'une nouvelle opération
         */
        $op = new Operation();
        $op->setAction('duplicate');
        $op->setType('manual');
        /**
         * 	Création d'un objet Repo avec les infos du repo à dupliquer
         */
        $op->repo = new Repo();
        $op->repo->setId($repoId);
        /**
         *  On récupère toutes les infos du repo en base de données
         */
        $op->repo->db_getAllById('active');
        /**
         *  Set du nouveau nom du repo cible
         */
        $op->repo->setTargetName($targetName);
        /**
         *  Set du groupe cible
         */
        $op->repo->setTargetGroup($targetGroup);
        /**
         *  Set de la description cible
         */
        $op->repo->setTargetDescription($targetDescription);
        /**
         * 	Exécution de l'opération
         */
        $op->exec_duplicate();
    }
    /**
     *  Si l'action est 'delete'
     */
    if ($action == 'delete') {
        /**
         *  Création d'une nouvelle opération
         */
        $op = new Operation();
        if ($repoStatus == 'active')   $op->setAction('delete');
        if ($repoStatus == 'archived') $op->setAction('deleteArchive');
        $op->setType('manual');
        /**
         * 	Exécution de l'opération
         */
        $op->exec_delete($repoId, $repoStatus);
    }
    /**
     *  Si l'action est 'env'
     */
    if ($action == 'env') {
        /**
         *  Si le l'environnement cible n'est pas défini on quitte
         */
        if (empty($operation['targetEnv'])) {
            throw new Exception("Operation 'env' - Erreur : l'env cible n'est pas défini");
            continue;
        }
        $targetEnv = $operation['targetEnv'];
        
        /**
         *  Création d'une nouvelle opération
         */
        $op = new Operation();
        $op->setAction('env');
        $op->setType('manual');
        /**
         * 	Création d'un objet Repo avec les infos du repo source
         */
        $op->repo = new Repo();
        $op->repo->setId($repoId);
        /**
         *  On récupère toutes les infos du repo en base de données
         */
        $op->repo->db_getAllById('active');
        /**
         *  Set de l'env cible
         */
        $op->repo->setTargetEnv($targetEnv);
        /**
         *  Set de la description cible
         */
        $op->repo->setTargetDescription($targetDescription);
        /**
         * 	Exécution de l'opération
         */
        $op->exec_env();
    }
    /**
     *  Si l'action est 'restore'
     */
    if ($action == 'restore') {
        /**
         *  Si le l'environnement cible n'est pas défini on quitte
         */
        if (empty($operation['targetEnv'])) {
            throw new Exception("Operation 'env' - Erreur : l'env cible n'est pas défini");
            continue;
        }
        $targetEnv = $operation['targetEnv'];
        
        /**
         *  Création d'une nouvelle opération
         */
        $op = new Operation();
        $op->setAction('restore');
        $op->setType('manual');
        /**
         * 	Création d'un objet Repo avec les infos du repo source
         */
        $op->repo = new Repo();
        $op->repo->setId($repoId);
        /**
         *  On récupère toutes les infos du repo en base de données
         */
        $op->repo->db_getAllById('archived');
        /**
         *  Set de l'env cible
         */
        $op->repo->setTargetEnv($targetEnv);
        /**
         * 	Exécution de l'opération
         */
        $op->exec_restore();
    }
    /**
     *  Si l'action est 'reconstruct'
     */
    if ($action == 'reconstruct') {
        /**
         *  Si le paramètre GPG Resign n'est pas défini on quitte
         */
        if (empty($operation['targetGpgResign'])) {
            throw new Exception("Operation 'reconstruct' - Erreur : le paramètre GPG Resign n'est pas défini");
            exit;
        }
        $targetGpgResign = $operation['targetGpgResign'];

        /**
         *  Création d'une nouvelle opération
         */
        $op = new Operation();
        $op->setAction('reconstruct');
        $op->setType('manual');
        /**
         * 	Création d'un objet Repo avec les infos du repo source
         */
        $op->repo = new Repo();
        $op->repo->setId($repoId);
        /**
         *  On récupère toutes les infos du repo en base de données
         */
        $op->repo->db_getAllById('active');
        /**
         *  Set de GPG Resign
         */
        $op->repo->setTargetGpgResign($targetGpgResign);
        /**
         * 	Exécution de l'opération
         */
        $op->exec_reconstruct();
    }
}
exit(0);
?>