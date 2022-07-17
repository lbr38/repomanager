<?php

define("ROOT", dirname(__FILE__, 2));
require_once(ROOT . "/controllers/Autoloader.php");
\Controllers\Autoloader::loadFromApi();

/**
 *  Ce script renvoie un code de sortie et un message d'erreur le cas écheant.
 *  Utile notamment pour la CI.
 */
$exitCode = 0;

/**
 *  1. Récupération de l'argument : id de l'opération à exécuter. Ne peut être vide.
 *
 *  Le premier paramètre passé à getopt est null : on ne souhaites pas travailler avec des options courtes.
 *  Plus d'infos sur getopt() : https://blog.pascal-martin.fr/post/php-5.3-getopt-parametres-ligne-de-commande/
 */
$getOptions = getopt(null, ["id:"]);

/**
 *  Récupération de l'Id de l'opération à traiter
 */
if (empty($getOptions['id'])) {
    echo "Erreur : l'Id d'opération n'est pas défini." . PHP_EOL;
    exit(1);
}

$id = $getOptions['id'];

/**
 *  Récupération des détails de l'opération à traiter, sous forme d'array
 */
if (!file_exists(POOL . '/' . $id . '.json')) {
    echo "Erreur : impossible de récupérer les détails de l'opération (Id $id) : le fichier est introuvable." . PHP_EOL;
    exit(1);
}

$operation_params = json_decode(file_get_contents(POOL . '/' . $id . '.json'), true);

/**
 *  Traitement de chaque opération
 */
foreach ($operation_params as $operation) {
    if (empty($operation['action'])) {
        echo "Operation inconnue : action non spécifiée." . PHP_EOL;
        $exitCode++;
        continue;
    }

    /**
     *  Récupération de l'action
     */
    $action = $operation['action'];

    if ($action != 'new'
        and $action != 'update'
        and $action != 'duplicate'
        and $action != 'delete'
        and $action != 'env'
        and $action != 'reconstruct') {
        echo "Operation inconnue : action invalide." . PHP_EOL;
        $exitCode++;
        continue;
    }

    /**
     *  Un Id de repo a été renseigné seulement dans le cas où l'action n'est pas 'new'
     */
    if ($action !== 'new') {
        $snapId = $operation['snapId'];
    }

    /**
     *  Si l'action est 'new' alors le type de paquet doit être spécifié
     */
    if ($action == 'new') {
        if (empty($operation['packageType'])) {
            echo "Operation 'new' - Erreur : le paramètre packageType n'est pas défini." . PHP_EOL;
            $exitCode++;
            continue;
        }
        
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
            echo "Operation 'new' - Erreur : le paramètre Type n'est pas défini." . PHP_EOL;
            $exitCode++;
            continue;
        }
        $type = $operation['type'];

        if ($packageType == 'deb') {
            /**
             *  Si le paramètre Dist n'est pas défini, on quitte
             */
            if (empty($operation['dist'])) {
                echo "Operation 'new' - Erreur : le paramètre Dist n'est pas défini." . PHP_EOL;
                $exitCode++;
                continue;
            }
            $dist = $operation['dist'];

            /**
             *  Si le paramètre Section n'est pas défini, on quitte
             */
            if (empty($operation['section'])) {
                echo "Operation 'new' - Erreur : le paramètre Section n'est pas défini." . PHP_EOL;
                $exitCode++;
                continue;
            }
            $section = $operation['section'];
        }

        /**
         *  Si le type est 'mirror' alors on vérifie des paramètres supplémentaires
         */
        if ($type === 'mirror') {
            /**
             *  Le paramètre Alias peut être vide dans le cas d'un type = 'mirror', si c'est le cas alors il pendra comme valeur 'source'
             */
            if (empty($operation['alias'])) {
                $alias = $source;
            } else {
                $alias = $operation['alias'];
            }

            /**
             *  Si le paramètre Source n'est pas défini, on quitte
             */
            if (empty($operation['source'])) {
                echo "Operation 'new' - Erreur : le paramètre Source n'est pas défini." . PHP_EOL;
                $exitCode++;
                continue;
            }
            $source = $operation['source'];

            /**
             *  Si le paramètre GPG Check n'est pas défini, on quitte
             */
            if (empty($operation['targetGpgCheck'])) {
                echo "Operation 'new' - Erreur : le paramètre GPG Check n'est pas défini." . PHP_EOL;
                $exitCode++;
                continue;
            }
            $targetGpgCheck = $operation['targetGpgCheck'];

            /**
             *  Si le paramètre GPG Resign n'est pas défini, on quitte
             */
            if (empty($operation['targetGpgResign'])) {
                echo "Operation 'new' - Erreur : le paramètre GPG Resign n'est pas défini." . PHP_EOL;
                $exitCode++;
                continue;
            }
            $targetGpgResign = $operation['targetGpgResign'];

            /**
             *  Paramètres avancés de la création d'un repo
             */
            $targetIncludeSource = $operation['targetIncludeSource'];

            /**
             *  Paramètres supplémentaires si deb
             */
            if ($packageType == 'deb') {
                /**
                 *  Cas où on souhaite inclure des traductions de paquets
                 */
                if (!empty($operation['targetIncludeTranslation'])) {
                    $targetIncludeTranslation = $operation['targetIncludeTranslation'];
                } else {
                    $targetIncludeTranslation = '';
                }
            }
        }
        if ($type === 'local') {
            /**
             *  Le paramètre Alias ne peut pas être vide dans le cas d'un type = 'local'
             */
            if (empty($operation['alias'])) {
                echo "Operation 'new' - Erreur : le paramètre Alias (Name) n'est pas défini." . PHP_EOL;
                $exitCode++;
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
        if (!empty($packageType)) {
            $repo->setPackageType($packageType);
        }
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
        $repo->setTargetIncludeSource($targetIncludeSource);
        if ($packageType == 'deb') {
            $repo->setTargetIncludeTranslation($targetIncludeTranslation);
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

        /**
         *  Si l'opération est en erreur alors on incrémente le code de sortie
         */
        if ($repo->getOpStatus() == 'error') {
            echo "Une erreur est survenue pendant l'opération : " . $repo->getOpError() . PHP_EOL;
            $exitCode++;
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
            echo "Operation 'update' - Erreur : le paramètre GPG Check n'est pas défini." . PHP_EOL;
            $exitCode++;
            continue;
        }
        $targetGpgCheck = $operation['targetGpgCheck'];

        /**
         *  Si le paramètre GPG Resign n'est pas défini on quitte
         */
        if (empty($operation['targetGpgResign'])) {
            echo "Operation 'update' - Erreur : le paramètre GPG Resign n'est pas défini." . PHP_EOL;
            $exitCode++;
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

        /**
         *  Si l'opération est en erreur alors on incrémente le code de sortie
         */
        if ($repo->getOpStatus() == 'error') {
            echo "Une erreur est survenue pendant l'opération : " . $repo->getOpError() . PHP_EOL;
            $exitCode++;
        }
    }
    /**
     *  Si l'action est 'duplicate'
     */
    if ($action == 'duplicate') {
        /**
         *  Si le nouveau nom n'est pas défini on quitte
         */
        if (empty($operation['targetName'])) {
            echo "Operation 'duplicate' - Erreur : le nouveau nom n'est pas défini." . PHP_EOL;
            $exitCode++;
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
         *  Set de la signature du repo/paquets
         *  Si le repo source est signé alors on signera le repo dupliqué
         */
        $repo->setTargetGpgResign($repo->getSigned());

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

        /**
         *  Si l'opération est en erreur alors on incrémente le code de sortie
         */
        if ($repo->getOpStatus() == 'error') {
            echo "Une erreur est survenue pendant l'opération : " . $repo->getOpError() . PHP_EOL;
            $exitCode++;
        }
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

        /**
         *  Si l'opération est en erreur alors on incrémente le code de sortie
         */
        if ($repo->getOpStatus() == 'error') {
            $exitCode++;
        }
    }
    /**
     *  Si l'action est 'env'
     */
    if ($action == 'env') {
        /**
         *  Si le l'environnement cible n'est pas défini on quitte
         */
        if (empty($operation['targetEnv'])) {
            echo "Operation 'env' - Erreur : l'env cible n'est pas défini." . PHP_EOL;
            $exitCode++;
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

        /**
         *  Si l'opération est en erreur alors on incrémente le code de sortie
         */
        if ($repo->getOpStatus() == 'error') {
            echo "Une erreur est survenue pendant l'opération : " . $repo->getOpError() . PHP_EOL;
            $exitCode++;
        }
    }
    /**
     *  Si l'action est 'reconstruct'
     */
    if ($action == 'reconstruct') {
        /**
         *  Si le paramètre GPG Resign n'est pas défini on quitte
         */
        if (empty($operation['targetGpgResign'])) {
            echo "Operation 'reconstruct' - Erreur : le paramètre GPG Resign n'est pas défini." . PHP_EOL;
            $exitCode++;
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
         *  Set de GPG Resign
         */
        $repo->setTargetGpgResign($targetGpgResign);

        /**
         *  Exécution de l'opération
         */
        $repo->reconstruct();

        /**
         *  Si l'opération est en erreur alors on incrémente le code de sortie
         */
        if ($repo->getOpStatus() == 'error') {
            echo "Une erreur est survenue pendant l'opération : " . $repo->getOpError() . PHP_EOL;
            $exitCode++;
        }
    }
}

exit($exitCode);
