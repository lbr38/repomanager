<?php

namespace Controllers\Task\Form;

use Exception;

class Form
{
    private $validActions = array('new', 'create', 'update', 'env', 'duplicate', 'delete', 'removeEnv', 'rebuild');

    /**
     *  Return the task form to the user according to his selection
     */
    public function get(string $action, array $repos_array)
    {
        if (!in_array($action, $this->validActions)) {
            throw new Exception('Task action is invalid');
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
        if ($action == 'rebuild') {
            $title = '<h3>REBUILD REPO</h3>';
        }

        $content = $title . '<form class="task-form" autocomplete="off">';
        $totalReposArray = count($repos_array);

        foreach ($repos_array as $repo) {
            $repoId = \Controllers\Common::validateData($repo['repoId']);
            $snapId = \Controllers\Common::validateData($repo['snapId']);

            /**
             *  When no environment points to the snapshot (snapId), there is no envId transmitted.
             *  Set envId to null in this case
             */
            if (empty($repo['envId'])) {
                $envId = null;
            } else {
                $envId = \Controllers\Common::validateData($repo['envId']);
            }

            /**
             *  Check that the Ids are numeric
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
             *  Check that the Ids exist in the database
             */
            if (!$myrepo->existsId($repoId)) {
                throw new Exception("Repo Id does not exist");
            }
            if (!$myrepo->existsSnapId($snapId)) {
                throw new Exception("Snapshot Id does not exist");
            }

            /**
             *  Retrieve all repo data from the Ids
             */
            if (!empty($envId)) {
                $myrepo->getAllById($repoId, $snapId, $envId);
            } else {
                $myrepo->getAllById($repoId, $snapId, '');
            }

            /**
             *  Retrieve the package type of the repo
             */
            $packageType = $myrepo->getPackageType();

            /**
             *  Build the form from a template
             */
            ob_start();

            echo '<div class="task-form-params" snap-id="' . $snapId . '" env-id="' . $envId . '" action="' . $action . '">';
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
     *  Validate the task form filled by the user
     *  @param array $tasksParams
     */
    public function validate(array $tasksParams) : void
    {
        foreach ($tasksParams as $task) {
            /**
             *  Retrieve action
             */
            if (empty($task['action'])) {
                throw new Exception('No action has been specified');
            }

            /**
             *  Generate controller name
             */
            $controllerPath = '\Controllers\Task\Form\\' . ucfirst($task['action']);

            /**
             *  Check if class exists, otherwise the action might be invalid
             */
            if (!class_exists($controllerPath)) {
                throw new Exception('Invalid action: ' . $task['action']);
            }

            /**
             *  Validate form by calling the controller
             */
            $controller = new $controllerPath();
            $controller->validate($task);

            return;




            /**
             *  Check that the action is valid
             */
            if (!in_array($action, $this->validActions)) {
                throw new Exception("Specified action is invalid");
            }

            /**
             *  Retrieve repo and snapshot Ids, except when the action is 'new'
             */
            if ($action !== 'new') {
                Param\Snapshot::checkId($task['snapId']);
                $snapId = $task['snapId'];
            }

            /**
             *  When no environment points to the snapshot (snapId), there is no envId transmitted.
             */
            // if (!empty($task['envId'])) {
            //     Param\Environment::checkId($task['envId']);
            //     $envId = $task['envId'];
            // }

            // if ($action == 'new') {
            //     Param\PackageType::check($task['packageType']);
            //     $packageType = $task['packageType'];
            // }

            /**
             *  Retrieve all repo data from the Ids, except when the action is 'new'
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
                 *  Retrieve the package type of the repo
                 */
                $packageType = $myrepo->getPackageType();
            }

            /**
             *  Case the action is 'new'
             */
            // if ($action == 'new') {
            //     Param\Type::check($task['type']);

            //     if ($packageType == 'rpm') {
            //         Param\Releasever::check($task['releasever']);
            //     }

            //     if ($packageType == 'deb') {
            //         if (empty($task['dist'])) {
            //             throw new Exception('You must specify a distribution.');
            //         }
            //         if (empty($task['section'])) {
            //             throw new Exception('You must specify a section.');
            //         }

            //         foreach ($task['dist'] as $dist) {
            //             Param\Dist::check($dist);
            //         }
            //         foreach ($task['section'] as $section) {
            //             Param\Section::check($section);
            //         }
            //     }

            //     Param\Description::check($task['targetDescription']);
            //     Param\Arch::check($task['targetArch']);

            //     if (!empty($task['targetGroup'])) {
            //         Param\Group::check($task['targetGroup']);
            //     }

            //     /**
            //      *  If the selected repo type is 'local' then we check that a name has been provided (can be left empty in the case of a mirror)
            //      */
            //     if ($task['type'] == "local") {
            //         $targetName = $task['alias'];
            //         Param\Name::check($targetName);
            //     }

            //     /**
            //      *  If the selected repo type is 'mirror' then we check additional parameters
            //      */
            //     if ($task['type'] == "mirror") {
            //         /**
            //          *  If no alias has been given, we use the source name as alias
            //          */
            //         if (!empty($task['alias'])) {
            //             $targetName = $task['alias'];
            //         } else {
            //             $targetName = $task['source'];
            //         }

            //         Param\Source::check($task['source'], $packageType);
            //         Param\Name::check($targetName);
            //         Param\GpgCheck::check($task['targetGpgCheck']);
            //         Param\GpgResign::check($task['targetGpgResign']);

            //         if ($packageType == 'deb') {
            //             if (!empty($task['targetPackageTranslation'])) {
            //                 Param\TranslationInc::check($task['targetPackageTranslation']);
            //             }
            //         }
            //     }

            //     /**
            //      *  Check if a repo/section with the same name is not already active with snapshots
            //      */
            //     if ($packageType == 'rpm') {
            //         if ($myrepo->isActive($task['alias']) === true) {
            //             throw new Exception('<span class="label-white">' . $task['alias'] . '</span> repo already exists');
            //         }
            //     }
            //     if ($packageType == 'deb') {
            //         /**
            //          *  For deb repo, we check that no repo/dist/section with the same name is already active
            //          */
            //         foreach ($task['dist'] as $distribution) {
            //             foreach ($task['section'] as $section) {
            //                 if ($myrepo->isActive($task['alias'], $distribution, $section) === true) {
            //                     throw new Exception('<span class="label-white">' . $task['alias'] . ' ❯ ' . $distribution . ' ❯ ' . $section . '</span> repo already exists');
            //                 }
            //             }
            //         }
            //     }

            //     /**
            //      *  Check that the source repo exists
            //      */
            //     if ($task['type'] == 'mirror') {
            //         if ($mysource->exists($packageType, $task['source']) === false) {
            //             throw new Exception("There is no source repo named " . $task['source']);
            //         }
            //     }

            //     if ($packageType == 'rpm') {
            //         $myhistory->set($_SESSION['username'], 'Run operation: New repo <span class="label-white">' . $targetName . '</span> (' . $task['type'] . ')', 'success');
            //     }
            //     if ($packageType == 'deb') {
            //         foreach ($task['dist'] as $distribution) {
            //             foreach ($task['section'] as $section) {
            //                 $myhistory->set($_SESSION['username'], 'Run operation: New repo <span class="label-white">' . $targetName . ' ❯ ' . $distribution . ' ❯ ' . $section . '</span> (' . $task['type'] . ')', 'success');
            //             }
            //         }
            //     }
            // }

            /**
             *  Case the action is 'update'
             */
            // if ($action == 'update') {
            //     Param\GpgCheck::check($task['targetGpgCheck']);
            //     Param\GpgResign::check($task['targetGpgResign']);

            //     if ($packageType == 'rpm') {
            //         $myhistory->set($_SESSION['username'], 'Run operation: update repo <span class="label-white">' . $myrepo->getName() . '</span> (' . $myrepo->getType() . ')', 'success');
            //     }
            //     if ($packageType == 'deb') {
            //         $myhistory->set($_SESSION['username'], 'Run operation: update repo <span class="label-white">' . $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span> (' . $myrepo->getType() . ')', 'success');
            //     }

            //     Param\Arch::check($task['targetArch']);

            //     if ($packageType == 'deb') {
            //         if (!empty($task['targetPackageTranslation'])) {
            //             Param\TranslationInc::check($task['targetPackageTranslation']);
            //         }
            //     }
            // }

            /**
             *  Case the action is 'duplicate'
             */
            if ($action == 'duplicate') {
                Param\Name::check($task['targetName']);

                if (!empty($task['targetEnv'])) {
                    Param\Environment::check($task['targetEnv']);
                }

                if (!empty($task['targetDescription'])) {
                    Param\Description::check($task['targetDescription']);
                }

                if (!empty($task['targetGroup'])) {
                    Param\Group::check($task['targetGroup']);
                }

                /**
                 *  Check that a repo with the same name does not already exist
                 */
                if ($packageType == 'rpm') {
                    if ($myrepo->isActive($task['targetName']) === true) {
                        throw new Exception('<span class="label-white">' . $task['targetName'] . '</span> repo already exists');
                    }
                }
                if ($packageType == 'deb') {
                    if ($myrepo->isActive($task['targetName'], $myrepo->getDist(), $myrepo->getSection()) === true) {
                        throw new Exception('<span class="label-white">' . $task['targetName'] . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span> repo already exists');
                    }
                }

                if ($packageType == 'rpm') {
                    $myhistory->set($_SESSION['username'], 'Run operation: duplicate repo <span class="label-white">' . $myrepo->getName() . '</span>⟶<span class="label-black">' . $myrepo->getDateFormatted() . '</span> ➡ <span class="label-white">' . $task['targetName'] . '</span>', 'success');
                }
                if ($packageType == 'deb') {
                    $myhistory->set($_SESSION['username'], 'Run operation: duplicate repo  <span class="label-white">' . $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span>⟶<span class="label-black">' . $myrepo->getDateFormatted() . '</span> ➡ <span class="label-white">' . $task['targetName'] . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span>', 'success');
                }
            }

            /**
             *  Case the action is 'delete'
             */
            if ($action == 'delete') {
                /**
                 *  Check that the repo exists
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
             *  Case the action is 'env'
             */
            if ($action == 'env') {
                Param\Environment::check($task['targetEnv']);
                Param\Description::check($task['targetDescription']);

                if ($packageType == 'rpm') {
                    $myhistory->set($_SESSION['username'], 'Run operation: new repo environment <span class="label-white">' . $myrepo->getName() . '</span>⟶<span class="label-black">' . $myrepo->getDateFormatted() . '</span>⟵' . \Controllers\Common::envtag($task['targetEnv']), 'success');
                }
                if ($packageType == 'deb') {
                    $myhistory->set($_SESSION['username'], 'Run operation: new repo environment <span class="label-white">' . $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span>⟶<span class="label-black">' . $myrepo->getDateFormatted() . '</span>⟵' . \Controllers\Common::envtag($task['targetEnv']), 'success');
                }
            }

            /**
             *  Case the action is 'rebuild'
             */
            if ($action == 'rebuild') {
                Param\GpgResign::check($task['targetGpgResign']);

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
