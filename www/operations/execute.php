<?php

define("ROOT", dirname(__FILE__, 2));
require_once(ROOT . "/controllers/Autoloader/Autoloader.php");
\Controllers\Autoloader\Autoloader::api();

ini_set('memory_limit', '256M');

$mylog = new \Controllers\Log\Log();

$validActions = ['new', 'update', 'duplicate', 'delete', 'env', 'reconstruct'];

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
    $mylog->log('error', 'Operation run', 'Operation Id is not defined.');
    echo "Error: operation Id is not defined." . PHP_EOL;
    exit(1);
}

$id = $getOptions['id'];

/**
 *  Récupération des détails de l'opération à traiter, sous forme d'array
 */
if (!file_exists(POOL . '/' . $id . '.json')) {
    $mylog->log('error', 'Operation run', 'Cannot get operation details (Id ' . $id . ') from pool file: file not found.');
    echo "Error: cannot get operation details (Id $id) from pool file: file not found." . PHP_EOL;
    exit(1);
}

$operation_params = json_decode(file_get_contents(POOL . '/' . $id . '.json'), true);

/**
 *  Traitement de chaque opération
 */
foreach ($operation_params as $operation) {
    if (empty($operation['action'])) {
        $mylog->log('error', 'Operation run', 'Action not specified.');
        echo "Action not specified." . PHP_EOL;
        $exitCode++;
        continue;
    }

    /**
     *  Default values
     */
    $targetGroup = 'nogroup';
    $targetDescription = 'nodescription';

    /**
     *  Getting action
     */
    $action = $operation['action'];

    /**
     *  Check that action is valid
     */
    //if ($action != 'new' and $action != 'update' and $action != 'duplicate' and $action != 'delete' and $action != 'env' and $action != 'reconstruct') {
    if (!in_array($action, $validActions)) {
        $mylog->log('error', 'Operation run', 'Invalid action: ' . $action);
        echo "Unknown operation: invalid action." . PHP_EOL;
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
            $mylog->log('error', 'Operation run', "New repo: parameter 'packageType' not defined.");
            echo "Operation 'new' - Error: parameter 'packageType' not defined." . PHP_EOL;
            $exitCode++;
            continue;
        }

        $packageType = $operation['packageType'];
    }

    /**
     *  Si un Id d'environnement a été spécifié
     */
    if (!empty($operation['envId'])) {
        $envId = $operation['envId'];
    }

    /**
     *  Si un environnement devra pointer sur le nouveau snapshot
     */
    if (!empty($operation['targetEnv'])) {
        $targetEnv = $operation['targetEnv'];
    }

    /**
     *  Getting target group and description
     */
    if (!empty($operation['targetGroup'])) {
        $targetGroup = $operation['targetGroup'];
    }

    if (!empty($operation['targetDescription'])) {
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
            $mylog->log('error', 'Operation run', "New repo: parameter 'Type' not defined.");
            echo "Operation 'new' - Error: parameter 'Type' not defined." . PHP_EOL;
            $exitCode++;
            continue;
        }
        $type = $operation['type'];

        if ($packageType == 'deb') {
            /**
             *  Si le paramètre Dist n'est pas défini, on quitte
             */
            if (empty($operation['dist'])) {
                $mylog->log('error', 'Operation run', "New repo: parameter 'Dist' not defined.");
                echo "Operation 'new' - Error: parameter 'Dist' not defined." . PHP_EOL;
                $exitCode++;
                continue;
            }
            $dist = $operation['dist'];

            /**
             *  Si le paramètre Section n'est pas défini, on quitte
             */
            if (empty($operation['section'])) {
                $mylog->log('error', 'Operation run', "New repo: parameter 'Section' not defined.");
                echo "Operation 'new' - Error: parameter 'Section' not defined." . PHP_EOL;
                $exitCode++;
                continue;
            }
            $section = $operation['section'];
        }

        /**
         *  Si le type est 'mirror' alors on vérifie des paramètres supplémentaires
         */
        if ($type == 'mirror') {
            /**
             *  Si le paramètre Source n'est pas défini, on quitte
             */
            if (empty($operation['source'])) {
                $mylog->log('error', 'Operation run', "New repo: parameter 'Source' not defined.");
                echo "Operation 'new' - Error: parameter 'Source' not defined." . PHP_EOL;
                $exitCode++;
                continue;
            }
            $source = $operation['source'];

            /**
             *  Le paramètre Alias peut être vide dans le cas d'un type = 'mirror', si c'est le cas alors il pendra comme valeur 'source'
             */
            if (empty($operation['alias'])) {
                $alias = $source;
            } else {
                $alias = $operation['alias'];
            }

            /**
             *  Si le paramètre GPG Check n'est pas défini, on quitte
             */
            if (empty($operation['targetGpgCheck'])) {
                $mylog->log('error', 'Operation run', "New repo: parameter 'GPG Check' not defined.");
                echo "Operation 'new' - Error: parameter 'GPG Check' not defined." . PHP_EOL;
                $exitCode++;
                continue;
            }
            $targetGpgCheck = $operation['targetGpgCheck'];

            /**
             *  Si le paramètre GPG Resign n'est pas défini, on quitte
             */
            if (empty($operation['targetGpgResign'])) {
                $mylog->log('error', 'Operation run', "New repo: parameter 'GPG Resign' not defined.");
                echo "Operation 'new' - Error: parameter 'GPG Resign' not defined." . PHP_EOL;
                $exitCode++;
                continue;
            }
            $targetGpgResign = $operation['targetGpgResign'];

            if (empty($operation['targetSourcePackage'])) {
                $mylog->log('error', 'Operation run', "New repo: parameter 'Include source packages' not defined.");
                echo "Operation 'new' - Error: parameter 'Include source packages' not defined." . PHP_EOL;
                $exitCode++;
                continue;
            }
            $targetSourcePackage = $operation['targetSourcePackage'];

            /**
             *  Paramètres supplémentaires si deb
             */
            if ($packageType == 'deb') {
                /**
                 *  Cas où on souhaite inclure des traductions de paquets
                 */
                if (!empty($operation['targetPackageTranslation'])) {
                    $targetPackageTranslation = $operation['targetPackageTranslation'];
                }
            }
        }
        if ($type == 'local') {
            /**
             *  Le paramètre Alias ne peut pas être vide dans le cas d'un type = 'local'
             */
            if (empty($operation['alias'])) {
                $mylog->log('error', 'Operation run', "New repo: parameter 'Alias' not defined.");
                echo "Operation 'new' - Error: parameter 'Alias' (Name) not defined." . PHP_EOL;
                $exitCode++;
                continue;
            }
            $alias = $operation['alias'];
        }

        if (empty($operation['targetArch'])) {
            $mylog->log('error', 'Operation run', "New repo: parameter 'Architecture' not defined.");
            echo "Operation 'new' - Error: parameter 'Arch' not defined." . PHP_EOL;
            $exitCode++;
            continue;
        }
        $targetArch = $operation['targetArch'];

        /**
         *  Création d'un objet Repo avec les infos spécifiées par l'utilisateur
         */
        $repo = new \Controllers\Repo();
        $repo->setPoolId($id);
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
        if ($type == 'mirror') {
            $repo->setSource($source);
            $repo->setTargetGpgCheck($targetGpgCheck);
            $repo->setTargetGpgResign($targetGpgResign);
            $repo->setTargetPackageSource($targetSourcePackage);

            if ($packageType == 'deb') {
                if (!empty($targetPackageTranslation)) {
                    $repo->setTargetPackageTranslation($targetPackageTranslation);
                }
            }
        }

        /**
         *  Set target package arch
         */
        $repo->setTargetArch($targetArch);

        /**
         *  Set target env if specified
         */
        if (!empty($targetEnv)) {
            $repo->setTargetEnv($targetEnv);
        }

        /**
         *  Exécution de l'opération
         */
        if ($type == 'mirror') {
            $repo->new();
        }
        if ($type == 'local') {
            $repo->newLocalRepo();
        }

        /**
         *  Si l'opération est en erreur alors on incrémente le code de sortie
         */
        if ($repo->getOpStatus() == 'error') {
            echo "Error while running operation: " . $repo->getOpError() . PHP_EOL;
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
            $mylog->log('error', 'Operation run', "Update repo: parameter 'GPG Check' not defined.");
            echo "Operation 'update' - Error: parameter 'GPG Check' not defined." . PHP_EOL;
            $exitCode++;
            continue;
        }
        $targetGpgCheck = $operation['targetGpgCheck'];

        /**
         *  Si le paramètre GPG Resign n'est pas défini on quitte
         */
        if (empty($operation['targetGpgResign'])) {
            $mylog->log('error', 'Operation run', "Update repo: parameter 'GPG Resign' not defined.");
            echo "Operation 'update' - Error: parameter 'GPG Resign' not defined." . PHP_EOL;
            $exitCode++;
            continue;
        }
        $targetGpgResign = $operation['targetGpgResign'];

        /**
         *  Paramètres avancés de la création d'un repo
         */
        if (!empty($operation['targetArch'])) {
            $targetArch = $operation['targetArch'];
        }

        if (!empty($operation['targetSourcePackage'])) {
            $targetSourcePackage = $operation['targetSourcePackage'];
        }

        if (!empty($operation['targetPackageTranslation'])) {
            $targetPackageTranslation = $operation['targetPackageTranslation'];
        }

        $onlySyncDifference = $operation['onlySyncDifference'];

        /**
         *  Création d'un objet Repo avec les infos du repo source
         */
        $repo = new \Controllers\Repo();
        $repo->setPoolId($id);
        $repo->setSnapId($snapId);

        /**
         *  On récupère toutes les infos du repo en base de données
         */
        $repo->getAllById('', $snapId, '');

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

        $repo->setTargetArch($targetArch);

        $repo->setTargetPackageSource($targetSourcePackage);

        if ($repo->getPackageType() == 'deb') {
            if (!empty($targetPackageTranslation)) {
                $repo->setTargetPackageTranslation($targetPackageTranslation);
            }
        }

        $repo->setOnlySyncDifference($onlySyncDifference);

        /**
         *  Exécution de l'opération
         */
        $repo->update();

        /**
         *  Si l'opération est en erreur alors on incrémente le code de sortie
         */
        if ($repo->getOpStatus() == 'error') {
            echo "Error while running operation: " . $repo->getOpError() . PHP_EOL;
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
            $mylog->log('error', 'Operation run', "Duplicate repo: parameter 'New repo name' not defined.");
            echo "Operation 'duplicate' - Error: new repo name not defined." . PHP_EOL;
            $exitCode++;
            continue;
        }
        $targetName = $operation['targetName'];

        /**
         *  Création d'un objet Repo avec les infos du repo à dupliquer
         */
        $repo = new \Controllers\Repo();
        $repo->setPoolId($id);
        $repo->setSnapId($snapId);

        /**
         *  On récupère toutes les infos du repo en base de données
         */
        $repo->getAllById('', $snapId, '');

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
         *  Get the source repo Arch
         */
        $repo->setTargetArch($repo->getArch());

        /**
         *  Get the source repo Package Source inclusion
         */
        $repo->setTargetPackageSource($repo->getPackageSource());

        /**
         *  Get the source repo Package Translation inclusion
         */
        if (!empty($repo->getPackageTranslation())) {
            $repo->setTargetPackageTranslation($repo->getPackageTranslation());
        }

        /**
         *  Exécution de l'opération
         */
        $repo->duplicate();

        /**
         *  Si l'opération est en erreur alors on incrémente le code de sortie
         */
        if ($repo->getOpStatus() == 'error') {
            echo "Error while running operation: " . $repo->getOpError() . PHP_EOL;
            $exitCode++;
        }
    }

    /**
     *  Si l'action est 'delete'
     */
    if ($action == 'delete') {
         /**
         *  Création d'un objet Repo avec les infos du snapshot à supprimer
         */
        $repo = new \Controllers\Repo();
        $repo->setPoolId($id);
        $repo->setSnapId($snapId);

        /**
         *  On récupère toutes les infos du repo en base de données
         */
        $repo->getAllById('', $snapId, '');

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
            $mylog->log('error', 'Operation run', "Repo env: parameter 'Target environment' not defined.");
            echo "Operation 'env' - Erreur: target environment not defined." . PHP_EOL;
            $exitCode++;
            continue;
        }
        $targetEnv = $operation['targetEnv'];

        /**
         *  Création d'un objet Repo avec les infos du repo source
         */
        $repo = new \Controllers\Repo();
        $repo->setPoolId($id);
        $repo->setSnapId($snapId);

        /**
         *  On récupère toutes les infos du repo en base de données
         */
        $repo->getAllById('', $snapId, '');

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
            echo "Error while running operation: " . $repo->getOpError() . PHP_EOL;
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
            $mylog->log('error', 'Operation run', "Reconstruct repo: parameter 'GPG Resign' not defined.");
            echo "Operation 'reconstruct' - Error: parameter 'GPG Resign' not defined." . PHP_EOL;
            $exitCode++;
            continue;
        }
        $targetGpgResign = $operation['targetGpgResign'];

        /**
         *  Création d'un objet Repo avec les infos du repo source
         */
        $repo = new \Controllers\Repo();
        $repo->setPoolId($id);
        $repo->setSnapId($snapId);

        /**
         *  On récupère toutes les infos du repo en base de données
         */
        $repo->getAllById('', $snapId, '');

        /**
         *  Get the actual repo Arch
         */
        $repo->setTargetArch($repo->getArch());

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
            echo "Error while running operation: " . $repo->getOpError() . PHP_EOL;
            $exitCode++;
        }
    }
}

exit($exitCode);
