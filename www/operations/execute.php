<?php

/**
 *  Import des variables nécessaires
 */

define("ROOT", dirname(__FILE__, 2));
require_once(ROOT . "/controllers/Autoloader.php");
\Controllers\Autoloader::loadFromApi();

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
if (!file_exists(POOL . "/${id}.json")) {
    throw new Exception("Erreur : impossible de récupérer les détails de l'opération (id $id) : le fichier est introuvable");
    exit(1);
}

$operation_params = json_decode(file_get_contents(POOL . "/${id}.json"), true);

/**
 *  Traitement de chaque opération
 */
foreach ($operation_params as $operation) {
    $action = $operation['action'];

    /**
     *  Un Id de repo a été renseigné seulement dans le cas où l'action n'est pas 'new'
     */
    if ($action !== 'new') {
        $snapId = $operation['snapId'];
    }
    if ($action == 'new') {
        $packageType = $operation['packageType'];
    }

    /**
     *  Si un Id d'environnement a été spécifié
     */
    if (!empty($operation['envId'])) {
        $envId  = $operation['envId'];
    }

    /**
     *  Si un environnement devra pointer sur le nouveau snapshot
     */
    if (!empty($operation['targetEnv'])) {
        $targetEnv = $operation['targetEnv'];
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

        if ($packageType == 'deb') {
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
         *  Création d'un objet Repo avec les infos spécifiées par l'utilisateur
         */
        $repo = new \Controllers\Repo();
        $repo->setType($type);
        $repo->setName($alias);
        $repo->setTargetGroup($targetGroup);
        $repo->setTargetDescription($targetDescription);
        $repo->setPackageType($packageType);
        if (!empty($dist)) {
            $repo->setDist($dist);
        }
        if (!empty($section)) {
            $repo->setSection($section);
        }
        if ($type === 'mirror') {
            $repo->setSource($source);
            $repo->setTargetGpgCheck($targetGpgCheck);
            $repo->setTargetGpgResign($targetGpgResign);
        }
        if (!empty($targetEnv)) {
            $repo->setTargetEnv($targetEnv);
        }

        /**
         *  Exécution de l'opération
         */
        if ($type === 'mirror') {
            $repo->new();
        }
        if ($type === 'local') {
            $repo->newLocalRepo();
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
         *  Création d'un objet Repo avec les infos du repo source
         */
        $repo = new \Controllers\Repo();
        $repo->setSnapId($snapId);

        /**
         *  On récupère toutes les infos du repo en base de données
         */
        $repo->getAllById('', $snapId);

        /**
         *  Si un environnement devra pointer sur le nouveau snapshot
         */
        if (!empty($targetEnv)) {
            $repo->setTargetEnv($targetEnv);
        }

        /**
         *  Set de GPG Check
         */
        $repo->setTargetGpgCheck($targetGpgCheck);

        /**
         *  Set de GPG Resign
         */
        $repo->setTargetGpgResign($targetGpgResign);

        /**
         *  Exécution de l'opération
         */
        $repo->update();
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
         *  Création d'un objet Repo avec les infos du repo à dupliquer
         */
        $repo = new \Controllers\Repo();
        $repo->setSnapId($snapId);

        /**
         *  On récupère toutes les infos du repo en base de données
         */
        $repo->getAllById('', $snapId);

        /**
         *  Set du nouveau nom du repo cible
         */
        $repo->setTargetName($targetName);

        /**
         *  Set du groupe cible
         */
        $repo->setTargetGroup($targetGroup);

        /**
         *  Set de la description cible
         */
        if (!empty($targetDescription)) {
            $repo->setTargetDescription($targetDescription);
        }

        if (!empty($targetEnv)) {
            $repo->setTargetEnv($targetEnv);
        }

        /**
         *  Exécution de l'opération
         */
        $repo->duplicate();
    }
    /**
     *  Si l'action est 'delete'
     */
    if ($action == 'delete') {
         /**
         *  Création d'un objet Repo avec les infos du repo à dupliquer
         */
        $repo = new \Controllers\Repo();
        $repo->setSnapId($snapId);

        /**
         *  On récupère toutes les infos du repo en base de données
         */
        $repo->getAllById('', $snapId);

        /**
         *  Exécution de l'opération
         */
        $repo->delete();
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
         *  Création d'un objet Repo avec les infos du repo source
         */
        $repo = new \Controllers\Repo();
        $repo->setSnapId($snapId);

        /**
         *  On récupère toutes les infos du repo en base de données
         */
        $repo->getAllById('', $snapId);

        /**
         *  Set de l'env cible
         */
        $repo->setTargetEnv($targetEnv);

        /**
         *  Set de la description cible
         */
        $repo->setTargetDescription($targetDescription);

        /**
         *  Exécution de l'opération
         */
        $repo->env();
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
         *  Création d'un objet Repo avec les infos du repo source
         */
        $repo = new \Controllers\Repo();
        $repo->setSnapId($snapId);

        /**
         *  On récupère toutes les infos du repo en base de données
         */
        $repo->getAllById('', $snapId);

        /**
         *  Set de GPG Resign
         */
        $repo->setTargetGpgResign($targetGpgResign);

        /**
         *  Exécution de l'opération
         */
        $repo->reconstruct();
    }
}

exit(0);
