<?php

namespace Controllers\Operation;

use Exception;

class Form
{
    private $validActions = array('new', 'create', 'update', 'env', 'duplicate', 'delete', 'removeEnv', 'reconstruct');

    /**
     *  Return the operation form to the user according to his selection
     */
    public function getForm(string $action, array $repos_array)
    {
        if (!in_array($action, $this->validActions)) {
            throw new Exception('Operation action is invalid');
        }

        if ($action == 'update') {
            $title = '<h3>UPDATE</h3>';
        }
        if ($action == 'env') {
            $title = '<h3>NEW ENVIRONMENT</h3>';
        }
        if ($action == 'duplicate') {
            $title = '<h3>DUPLICATE</h3>';
        }
        if ($action == 'delete') {
            $title = '<h3>DELETE</h3>';
        }
        if ($action == 'reconstruct') {
            $title = '<h3>REBUILD REPO</h3>';
        }

        $content = $title . '<form class="operation-form-container" autocomplete="off">';
        $totalReposArray = count($repos_array);

        foreach ($repos_array as $repo) {
            $repoId = \Controllers\Common::validateData($repo['repoId']);
            $snapId = \Controllers\Common::validateData($repo['snapId']);

            /**
             *  Lorsque qu'aucun environnement ne pointe vers le snapshot (snapId), il n'y a aucun envId transmis.
             *  On set envId = null dans ce cas là
             */
            if (empty($repo['envId'])) {
                $envId = null;
            } else {
                $envId = \Controllers\Common::validateData($repo['envId']);
            }

            /**
             *  Vérification de l'id spécifié
             */
            if (!is_numeric($repoId)) {
                throw new Exception("Repo Id is invalid");
            }
            if (!is_numeric($snapId)) {
                throw new Exception("Snapshot Id is invalid");
            }
            if (!empty($envId)) {
                if (!is_numeric($envId)) {
                    throw new Exception("Environment Id is invalid");
                }
            }

            $myrepo = new \Controllers\Repo\Repo();
            $myrepo->setRepoId($repoId);
            $myrepo->setSnapId($snapId);
            if (!empty($envId)) {
                $myrepo->setEnvId($envId);
            }

            /**
             *  On vérifie que les Id spécifiés existent en base de données
             */
            if (!$myrepo->existsId($repoId)) {
                throw new Exception("Repo Id does not exist");
            }
            if (!$myrepo->existsSnapId($snapId)) {
                throw new Exception("Snapshot Id does not exist");
            }

            /**
             *  On récupère toutes les données du repo à partir des Id transmis
             */
            if (!empty($envId)) {
                $myrepo->getAllById($repoId, $snapId, $envId);
            } else {
                $myrepo->getAllById($repoId, $snapId, '');
            }

            /**
             *  Récupération du type ede paquets du repo
             */
            $packageType = $myrepo->getPackageType();

            /**
             *  Construction du formulaire à partir d'un template
             */
            ob_start();

            echo '<div class="operation-form" snap-id="' . $snapId . '" env-id="' . $envId . '" action="' . $action . '">';
            echo '<table>';

            /**
             *  Include form template
             */
            include(ROOT . '/templates/forms/op-form-' . $action . '.inc.php');

            echo '</table>';
            echo '</div>';

            /**
             *  Print a <hr> to separate when there are multiple repos to be processed
             */
            if ($totalReposArray > 1) {
                echo '<br><hr><br>';
            }
            $totalReposArray--;

            $content .= ob_get_clean();
        }

        $content .= '<br><button class="btn-large-red">Confirm and execute<img src="/assets/icons/rocket.svg" class="icon" /></button></form><br><br>';

        return $content;
    }

    /**
     *  Validate the operation form filled by the user
     */
    public function validateForm(array $operations_params)
    {
        $myrepo = new \Controllers\Repo\Repo();
        $mysource = new \Controllers\Source();
        $myhistory = new \Controllers\History();

        foreach ($operations_params as $operation_params) {
            /**
             *  Retrieve action
             */
            if (empty($operation_params['action'])) {
                throw new Exception("No action has been specified");
            }
            $action = $operation_params['action'];

            /**
             *  Check that the action is valid
             */
            if (!in_array($action, $this->validActions)) {
                throw new Exception("Specified action is invalid");
            }

            /**
             *  Récupération de l'id de repo et de snapshot, sauf quand l'action est 'new'
             */
            if ($action !== 'new') {
                Param\Snapshot::checkId($operation_params['snapId']);
                $snapId = $operation_params['snapId'];
            }

            /**
             *  Lorsque qu'aucun environnement ne pointe vers le snapshot (snapId), il n'y a aucun envId transmis.
             */
            // if (!empty($operation_params['envId'])) {
            //     Param\Environment::checkId($operation_params['envId']);
            //     $envId = $operation_params['envId'];
            // }

            if ($action == 'new') {
                Param\PackageType::check($operation_params['packageType']);
                $packageType = $operation_params['packageType'];
            }

            /**
             *  Récupération de toutes les informations du repo et du snapshot à traiter à partir de leur Id, sauf quand l'action est 'new'
             */
            if ($action !== 'new') {
                $myrepo->setSnapId($snapId);

                if (!empty($envId)) {
                    $myrepo->setEnvId($envId);
                    $myrepo->getAllById('', $snapId, $envId);
                } else {
                    $myrepo->getAllById('', $snapId, '');
                }

                /**
                 *  Récupération du type de paquet
                 */
                $packageType = $myrepo->getPackageType();
            }

            /**
             *  Si l'action est 'new'
             */
            if ($action == 'new') {
                Param\Type::check($operation_params['type']);

                if ($packageType == 'rpm') {
                    Param\Releasever::check($operation_params['releasever']);
                }

                if ($packageType == 'deb') {
                    if (empty($operation_params['dist'])) {
                        throw new Exception('You must specify a distribution.');
                    }
                    if (empty($operation_params['section'])) {
                        throw new Exception('You must specify a section.');
                    }

                    foreach ($operation_params['dist'] as $dist) {
                        Param\Dist::check($dist);
                    }
                    foreach ($operation_params['section'] as $section) {
                        Param\Section::check($section);
                    }
                }

                Param\Description::check($operation_params['targetDescription']);
                Param\Arch::check($operation_params['targetArch']);

                if (!empty($operation_params['targetGroup'])) {
                    Param\Group::check($operation_params['targetGroup']);
                }

                /**
                 *  Si le type de repo sélectionné est 'local' alors on vérifie qu'un nom a été fourni (peut rester vide dans le cas d'un miroir)
                 */
                if ($operation_params['type'] == "local") {
                    $targetName = $operation_params['alias'];
                    Param\Name::check($targetName);
                }

                /**
                 *  Si le type de repo sélectionné est 'mirror' alors on vérifie des paramètres supplémentaires
                 */
                if ($operation_params['type'] == "mirror") {
                    /**
                     *  If no alias has been given, we use the source name as alias
                     */
                    if (!empty($operation_params['alias'])) {
                        $targetName = $operation_params['alias'];
                    } else {
                        $targetName = $operation_params['source'];
                    }

                    Param\Source::check($operation_params['source'], $packageType);
                    Param\Name::check($targetName);
                    Param\GpgCheck::check($operation_params['targetGpgCheck']);
                    Param\GpgResign::check($operation_params['targetGpgResign']);
                    // Param\SourcePackageInc::check($operation_params['targetSourcePackage']);

                    if ($packageType == 'deb') {
                        if (!empty($operation_params['targetPackageTranslation'])) {
                            Param\TranslationInc::check($operation_params['targetPackageTranslation']);
                        }
                    }
                }

                /**
                 *  Check if a repo/section with the same name is not already active with snapshots
                 */
                if ($packageType == 'rpm') {
                    if ($myrepo->isActive($operation_params['alias']) === true) {
                        throw new Exception('<span class="label-white">' . $operation_params['alias'] . '</span> repo already exists');
                    }
                }
                if ($packageType == 'deb') {
                    /**
                     *  For deb repo, we check that no repo/dist/section with the same name is already active
                     */
                    foreach ($operation_params['dist'] as $distribution) {
                        foreach ($operation_params['section'] as $section) {
                            if ($myrepo->isActive($operation_params['alias'], $distribution, $section) === true) {
                                throw new Exception('<span class="label-white">' . $operation_params['alias'] . ' ❯ ' . $distribution . ' ❯ ' . $section . '</span> repo already exists');
                            }
                        }
                    }
                }

                /**
                 *  On vérifie que le repo source existe
                 */
                if ($operation_params['type'] == 'mirror') {
                    if ($mysource->exists($packageType, $operation_params['source']) === false) {
                        throw new Exception("There is no source repo named " . $operation_params['source']);
                    }
                }

                if ($packageType == 'rpm') {
                    $myhistory->set($_SESSION['username'], 'Run operation: New repo <span class="label-white">' . $targetName . '</span> (' . $operation_params['type'] . ')', 'success');
                }
                if ($packageType == 'deb') {
                    foreach ($operation_params['dist'] as $distribution) {
                        foreach ($operation_params['section'] as $section) {
                            $myhistory->set($_SESSION['username'], 'Run operation: New repo <span class="label-white">' . $targetName . ' ❯ ' . $distribution . ' ❯ ' . $section . '</span> (' . $operation_params['type'] . ')', 'success');
                        }
                    }
                }
            }

            /**
             *  Si l'action est 'update'
             */
            if ($action == 'update') {
                Param\GpgCheck::check($operation_params['targetGpgCheck']);
                Param\GpgResign::check($operation_params['targetGpgResign']);

                if ($packageType == 'rpm') {
                    $myhistory->set($_SESSION['username'], 'Run operation: update repo <span class="label-white">' . $myrepo->getName() . '</span> (' . $myrepo->getType() . ')', 'success');
                }
                if ($packageType == 'deb') {
                    $myhistory->set($_SESSION['username'], 'Run operation: update repo <span class="label-white">' . $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span> (' . $myrepo->getType() . ')', 'success');
                }

                Param\Arch::check($operation_params['targetArch']);
                // Param\SourcePackageInc::check($operation_params['targetSourcePackage']);

                if ($packageType == 'deb') {
                    if (!empty($operation_params['targetPackageTranslation'])) {
                        Param\TranslationInc::check($operation_params['targetPackageTranslation']);
                    }
                }
            }

            /**
             *  Si l'action est 'duplicate'
             */
            if ($action == 'duplicate') {
                Param\Name::check($operation_params['targetName']);

                if (!empty($operation_params['targetEnv'])) {
                    Param\Environment::check($operation_params['targetEnv']);
                }

                if (!empty($operation_params['targetDescription'])) {
                    Param\Description::check($operation_params['targetDescription']);
                }

                if (!empty($operation_params['targetGroup'])) {
                    Param\Group::check($operation_params['targetGroup']);
                }
                /**
                 *  On vérifie qu'un repo du même nom n'existe pas déjà
                 */
                if ($packageType == 'rpm') {
                    if ($myrepo->isActive($operation_params['targetName']) === true) {
                        throw new Exception('<span class="label-white">' . $operation_params['targetName'] . '</span> repo already exists');
                    }
                }
                if ($packageType == 'deb') {
                    if ($myrepo->isActive($operation_params['targetName'], $myrepo->getDist(), $myrepo->getSection()) === true) {
                        throw new Exception('<span class="label-white">' . $operation_params['targetName'] . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span> repo already exists');
                    }
                }

                if ($packageType == 'rpm') {
                    $myhistory->set($_SESSION['username'], 'Run operation: duplicate repo <span class="label-white">' . $myrepo->getName() . '</span>⟶<span class="label-black">' . $myrepo->getDateFormatted() . '</span> ➡ <span class="label-white">' . $operation_params['targetName'] . '</span>', 'success');
                }
                if ($packageType == 'deb') {
                    $myhistory->set($_SESSION['username'], 'Run operation: duplicate repo  <span class="label-white">' . $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span>⟶<span class="label-black">' . $myrepo->getDateFormatted() . '</span> ➡ <span class="label-white">' . $operation_params['targetName'] . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span>', 'success');
                }
            }

            /**
             *  Si l'action est 'delete'
             */
            if ($action == 'delete') {
                /**
                 *  On vérifie que le repo mentionné existe
                 */
                if ($myrepo->existsSnapId($snapId) === false) {
                    throw new Exception("Snapshot Id $snapId does not exist");
                }

                if ($packageType == 'rpm') {
                    $myhistory->set($_SESSION['username'], 'Run operation: delete repo snapshot <span class="label-white">' . $myrepo->getName() . '</span>⟶<span class="label-black">' . $myrepo->getDateFormatted() . '</span>', 'success');
                }
                if ($packageType == 'deb') {
                    $myhistory->set($_SESSION['username'], 'Run operation: delete repo snapshot <span class="label-white">' . $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span>⟶<span class="label-black">' . $myrepo->getDateFormatted() . '</span>', 'success');
                }
            }

            /**
             *  Si l'action est 'env'
             */
            if ($action == 'env') {
                Param\Environment::check($operation_params['targetEnv']);
                Param\Description::check($operation_params['targetDescription']);

                if ($packageType == 'rpm') {
                    $myhistory->set($_SESSION['username'], 'Run operation: new repo environment <span class="label-white">' . $myrepo->getName() . '</span>⟶<span class="label-black">' . $myrepo->getDateFormatted() . '</span>⟵' . \Controllers\Common::envtag($operation_params['targetEnv']), 'success');
                }
                if ($packageType == 'deb') {
                    $myhistory->set($_SESSION['username'], 'Run operation: new repo environment <span class="label-white">' . $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span>⟶<span class="label-black">' . $myrepo->getDateFormatted() . '</span>⟵' . \Controllers\Common::envtag($operation_params['targetEnv']), 'success');
                }
            }

            /**
             *  Si l'action est 'reconstruct'
             */
            if ($action == 'reconstruct') {
                Param\GpgResign::check($operation_params['targetGpgResign']);

                if ($packageType == 'rpm') {
                    $myhistory->set($_SESSION['username'], 'Run operation: rebuild repo metadata files of <span class="label-white">' . $myrepo->getName() . '</span>⟶<span class="label-black">' . $myrepo->getDateFormatted() . '</span>', 'success');
                }
                if ($packageType == 'deb') {
                    $myhistory->set($_SESSION['username'], 'Run operation: rebuild repo metadata files of <span class="label-white">' . $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span>⟶<span class="label-black">' . $myrepo->getDateFormatted() . '</span>', 'success');
                }
            }
        }
    }
}
