<?php

namespace Controllers\Repo\Operation\Metadata;

use Exception;

trait Create
{
    /**
     *  Create repo metadata and symbolic links (environments)
     */
    private function createMetadata()
    {
        $createRepoErrors = 0;
        $repreproErrors = 0;

        ob_start();

        $this->log->step('CREATING REPO');

        echo '<div class="hide createRepoDiv"><pre>';

        $this->log->steplogWrite();

        if ($this->repo->getPackageType() == 'rpm') {
            $repoPath = REPOS_DIR . '/' . $this->repo->getTargetDateFormatted() . '_' . $this->repo->getName();
        }
        if ($this->repo->getPackageType() == 'deb') {
            $repoPath = REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getTargetDateFormatted() . '_' . $this->repo->getSection();
        }

        /**
         *  If a 'my_uploaded_packages' directory exists, move them packages into 'packages' directory
         */
        if (is_dir($repoPath . '/my_uploaded_packages/')) {
            /**
             *  Create 'packages' directory if not exist
             */
            if (!is_dir($repoPath . '/packages')) {
                if (!mkdir($repoPath . '/packages', 0770, true)) {
                    throw new Exception('Could not create ' . $repoPath . '/packages directory');
                }
            }

            /**
             *  Move packages to the 'packages' directory
             */
            if (!\Controllers\Common::dirIsEmpty($repoPath . '/my_uploaded_packages/')) {
                $myprocess = new \Controllers\Process('mv -f ' . $repoPath . '/my_uploaded_packages/* ' . $repoPath . '/packages/');
                $myprocess->execute();
                if ($myprocess->getExitCode() != 0) {
                    echo $myprocess->getOutput();
                    throw new Exception('Error while moving packages from ' . $repoPath . '/my_uploaded_packages/ to ' . $repoPath . '/packages/');
                }
            }

            /**
             *  Delete 'my_uploaded_packages' dir
             */
            if (!rmdir($repoPath . '/my_uploaded_packages')) {
                throw new Exception('Could not delete ' .$repoPath . '/my_uploaded_packages/ directory');
            }
        }

        if ($this->repo->getPackageType() == 'rpm') {
            /**
             *  Check which of createrepo or createrepo_c is present on the system
             */
            if (file_exists('/usr/bin/createrepo')) {
                $createrepo = '/usr/bin/createrepo';
            }
            if (file_exists('/usr/bin/createrepo_c')) {
                $createrepo = '/usr/bin/createrepo_c';
            }
            if (empty($createrepo)) {
                throw new Exception('Could not find createrepo on the system');
            }

            /**
             *  Instanciation d'un nouveau Process
             */
            $myprocess = new \Controllers\Process($createrepo . ' -v ' . $repoPath . '/');

            /**
             *  Exécution
             */
            $myprocess->setBackground(true);
            $myprocess->execute();

            /**
             *  Récupération du pid du process lancé
             *  Puis écriture du pid de createrepo (lancé par proc_open) dans le fichier PID principal, ceci afin qu'il puisse être killé si l'utilisateur le souhaite
             */
            $this->operation->addsubpid($myprocess->getPid());

            /**
             *  Affichage de l'output du process en continue dans un fichier
             */
            $myprocess->getOutput($this->log->getSteplog());

            if ($myprocess->getExitCode() != 0) {
                $createRepoErrors++;
            }

            $myprocess->close();

            echo '</pre></div>';

            $this->log->steplogWrite();
        }

        if ($this->repo->getPackageType() == 'deb') {
            $repreproArchs = '';
            $repreproGpgParams = '';

            if (!is_dir($repoPath)) {
                echo '</pre></div>';
                throw new Exception('Repo directory does not exist');
            }

            /**
             *  If this section already has a pool directory, then it means that it is an existing section that has been duplicated or that needs to be rebuilded.
             *  Packages and source packages in pool directory will be moved in dedicated directory as if it was a brand new repo.
             */
            if ($this->operation->getAction() == 'duplicate' or $this->operation->getAction() == 'reconstruct') {
                /**
                 *  Create packages and sources directory
                 */
                if (!is_dir($repoPath . '/packages')) {
                    if (!mkdir($repoPath . '/packages', 0770, true)) {
                        echo '</pre></div>';
                        throw new Exception('Error: could not create directory ' . $repoPath . '/packages');
                    }
                }
                if (!is_dir($repoPath . '/sources')) {
                    if (!mkdir($repoPath . '/sources', 0770, true)) {
                        echo '</pre></div>';
                        throw new Exception('Error: could not create directory ' . $repoPath . '/sources');
                    }
                }

                /**
                 *  Recursively find all packages and sources packages
                 */
                $debPackages          = \Controllers\Common::findRecursive($repoPath . '/pool', 'deb', true);
                $dscSourcesPackages   = \Controllers\Common::findRecursive($repoPath . '/pool', 'dsc', true);
                $tarxzSourcesPackages = \Controllers\Common::findRecursive($repoPath . '/pool', 'xz', true);
                $targzSourcesPackages = \Controllers\Common::findRecursive($repoPath . '/pool', 'gz', true);

                /**
                 *  Move packages to the packages directory
                 */
                if (!empty($debPackages)) {
                    foreach ($debPackages as $debPackage) {
                        $debPackageName = preg_split('#/#', $debPackage);
                        $debPackageName = end($debPackageName);

                        if (!rename($debPackage, $repoPath . '/packages/' . $debPackageName)) {
                            echo '</pre></div>';
                            throw new Exception('Error: could not move package ' . $debPackage . ' to the packages directory');
                        }
                    }
                }

                /**
                 *  Move source packages to the sources directory
                 */
                if (!empty($dscSourcesPackages)) {
                    foreach ($dscSourcesPackages as $dscSourcesPackage) {
                        $dscSourcesPackageName = preg_split('#/#', $dscSourcesPackage);
                        $dscSourcesPackageName = end($dscSourcesPackageName);

                        if (!rename($dscSourcesPackage, $repoPath . '/sources/' . $dscSourcesPackageName)) {
                            echo '</pre></div>';
                            throw new Exception('Error: could not move source package ' . $dscSourcesPackage . ' to the sources directory');
                        }
                    }
                }

                if (!empty($tarxzSourcesPackages)) {
                    foreach ($tarxzSourcesPackages as $tarxzSourcesPackage) {
                        $tarxzSourcesPackageName = preg_split('#/#', $tarxzSourcesPackage);
                        $tarxzSourcesPackageName = end($tarxzSourcesPackageName);

                        if (!preg_match('/.tar.xz/i', $tarxzSourcesPackageName)) {
                            continue;
                        }

                        if (!rename($tarxzSourcesPackage, $repoPath . '/sources/' . $tarxzSourcesPackageName)) {
                            echo '</pre></div>';
                            throw new Exception('Error: could not move source package ' . $tarxzSourcesPackage . ' to the sources directory');
                        }
                    }
                }

                if (!empty($targzSourcesPackages)) {
                    foreach ($targzSourcesPackages as $targzSourcesPackage) {
                        $targzSourcesPackageName = preg_split('#/#', $targzSourcesPackage);
                        $targzSourcesPackageName = end($targzSourcesPackageName);

                        if (!preg_match('/.tar.gz/i', $targzSourcesPackageName)) {
                            continue;
                        }

                        if (!rename($targzSourcesPackage, $repoPath . '/sources/' . $targzSourcesPackageName)) {
                            echo '</pre></div>';
                            throw new Exception('Error: could not move source package ' . $targzSourcesPackage . ' to the sources directory');
                        }
                    }
                }

                /**
                 *  Clean existing directories
                 */
                if (!\Controllers\Common::deleteRecursive($repoPath . '/conf')) {
                    echo '</pre></div>';
                    throw new Exception('Cannot delete existing directory: ' . $repoPath . '/conf');
                }
                if (!\Controllers\Common::deleteRecursive($repoPath . '/db')) {
                    echo '</pre></div>';
                    throw new Exception('Cannot delete existing directory: ' . $repoPath . '/db');
                }
                if (!\Controllers\Common::deleteRecursive($repoPath . '/dists')) {
                    echo '</pre></div>';
                    throw new Exception('Cannot delete existing directory: ' . $repoPath . '/dists');
                }
                if (!\Controllers\Common::deleteRecursive($repoPath . '/pool')) {
                    echo '</pre></div>';
                    throw new Exception('Cannot delete existing directory: ' . $repoPath . '/pool');
                }
            }

            /**
             *  Target arch must be specified
             */
            if (empty($this->repo->getTargetArch())) {
                echo '</pre></div>';
                throw new Exception('Packages arch must be specified');
            }

            $this->log->steplogWrite();

            /**
             *  Création du répertoire 'conf' et des fichiers de conf du repo
             */
            if (!is_dir($repoPath . '/conf')) {
                if (!mkdir($repoPath . '/conf', 0770, true)) {
                    echo '</pre></div>';
                    throw new Exception('Could not create repo configuration directory <b>' . $repoPath . '/conf</b>');
                }
            }

            /**
             *  Create "distributions" file
             *  Its content will depend on repo signature, architecture specified...
             */

            /**
             *  Define archs
             */
            foreach ($this->repo->getTargetArch() as $arch) {
                $repreproArchs .= ' ' . $arch;
            }

            /**
             *  If packages sources must be included, then add 'source' to the archs
             *
             *  For action like 'duplicate' or 'reconstruct', if the source repo has source packages included, then include them in the new repo
             */
            if ($this->operation->getAction() == 'duplicate' or $this->operation->getAction() == 'reconstruct') {
                if ($this->repo->getSourcePackage() == 'yes') {
                    $repreproArchs .= ' source';
                }
            /**
             *  For other action, include source packages or not, as defined by the user
             */
            } else {
                if ($this->repo->getTargetSourcePackage() == 'yes') {
                    $repreproArchs .= ' source';
                }
            }

            $distributionsFileContent = 'Origin: ' . $this->repo->getName() . ' repo on ' . WWW_HOSTNAME . PHP_EOL;
            $distributionsFileContent .= 'Label: apt repository' . PHP_EOL;
            $distributionsFileContent .= 'Codename: ' . $this->repo->getDist() . PHP_EOL;
            $distributionsFileContent .= 'Suite: stable' . PHP_EOL;
            $distributionsFileContent .= 'Architectures: ' . $repreproArchs . PHP_EOL;
            $distributionsFileContent .= 'Components: ' . $this->repo->getSection() . PHP_EOL;
            $distributionsFileContent .= 'Description: ' . $this->repo->getName() . ' repo, mirror of ' . $this->repo->getSource() . ' - ' . $this->repo->getDist() . ' - ' . $this->repo->getSection() . PHP_EOL;
            if ($this->repo->getTargetGpgResign() == "yes") {
                $distributionsFileContent .= 'SignWith: ' . GPG_SIGNING_KEYID . PHP_EOL;
            }
            $distributionsFileContent .= 'Pull: ' . $this->repo->getSection();

            if (!file_put_contents($repoPath . '/conf/distributions', $distributionsFileContent . PHP_EOL)) {
                throw new Exception('Could not create repo distributions file <b>' . $repoPath . '/conf/distributions</b>');
            }

            /**
             *  Create "options" file
             */
            $optionsFileContent = 'basedir ' . $repoPath . PHP_EOL;
            if ($this->repo->getTargetGpgResign() == "yes") {
                $optionsFileContent .= 'ask-passphrase';
            }

            if (!file_put_contents($repoPath . '/conf/options', $optionsFileContent . PHP_EOL)) {
                throw new Exception('Could not create repo options file <b>' . $repoPath . '/conf/options</b>');
            }

            /**
             *  Si le répertoire temporaire ne contient aucun paquet (càd si le repo est vide) alors on ne traite pas et on incrémente $return afin d'afficher une erreur.
             */
            if (\Controllers\Common::dirIsEmpty($repoPath . '/packages') === true) {
                echo 'Error: there is no package in this repo.';
                echo '</pre></div>';
                throw new Exception('No package found in this repo');

            /**
             *  Sinon on peut traiter
             */
            } else {
                /**
                 *  Get all .deb and .dsc files in working directory
                 */
                $debPackagesFiles = \Controllers\Common::findRecursive($repoPath . '/packages', 'deb', true);
                if (is_dir($repoPath . '/sources')) {
                    $dscPackagesFiles = \Controllers\Common::findRecursive($repoPath . '/sources', 'dsc', true);
                }

                /**
                 *  Merge all packages found into a single array
                 */
                if (!empty($debPackagesFiles) and !empty($dscPackagesFiles)) {
                    $packagesFiles = array_merge($debPackagesFiles, $dscPackagesFiles);
                } elseif (!empty($debPackagesFiles)) {
                    $packagesFiles = $debPackagesFiles;
                } elseif (!empty($dscPackagesFiles)) {
                    $packagesFiles = $dscPackagesFiles;
                }

                /**
                 *  Get all translations files if any
                 */
                if (is_dir($repoPath . '/translations/')) {
                    $translationsFiles = glob($repoPath . '/translations/*.bz2', GLOB_BRACE);
                }

                /**
                 *  To avoid 'too many argument list' error, reprepro will have to import .deb packages by lot of 100.
                 *  So we are creating arrays of deb packages paths by lot of 100.
                 */
                $debFilesGlobalArray = array();
                $debFilesArray = array();
                $i = 0;

                $dscFilesGlobalArray = array();

                foreach ($packagesFiles as $packageFile) {
                    if (preg_match('/.deb$/', $packageFile)) {
                        /**
                         *  Add deb file path to the array and increment package counter
                         */
                        $debFilesArray[] = $packageFile;
                        $i++;

                        /**
                         *  If 100 packages paths have been collected, then push the array in the global array and create a new array
                         */
                        if ($i == '100') {
                            $debFilesGlobalArray[] = $debFilesArray;
                            $debFilesArray = array();

                            /**
                             *  Reset packages counter
                             */
                            $i = 0;
                        }
                    }

                    if (preg_match('/.dsc$/', $packageFile)) {
                        /**
                         *  Add deb file path to the array and increment package counter
                         */
                        $dscFilesGlobalArray[] = $packageFile;
                    }
                }

                /**
                 *  Add the last generated array, even if has not reached 100 packages, and if not empty
                 */
                if (!empty($debFilesArray)) {
                    $debFilesGlobalArray[] = $debFilesArray;
                }

                /**
                 *  Case repo GPG signature is enabled
                 */
                if ($this->repo->getTargetGpgResign() == 'yes') {
                    $repreproGpgParams = '--gnupghome ' . GPGHOME;
                }

                /**
                 *  Process each lot arrays to generate a one-liner path to packages. The paths to deb files are concatened and separated by a space.
                 *  It the only way to import multiple packages with reprepro (using * wildcard coult end in 'too many argument' error)
                 */
                if (!empty($debFilesGlobalArray)) {
                    foreach ($debFilesGlobalArray as $lotArray) {
                        /**
                         *  Convert each array of 100 packages to a string
                         *
                         *  e.g:
                         *
                         *  Array(
                         *      [0] => /home/repo/.../package1.deb
                         *      [1] => /home/repo/.../package2.deb
                         *      [2] => /home/repo/.../package3.deb
                         *      ...
                         *  )
                         *
                         *  is being converted to a oneliner string:
                         *
                         *  '/home/repo/.../package1.deb /home/repo/.../package2.deb /home/repo/.../package3.deb'
                         */
                        $debFilesConcatenatePaths = trim(implode(' ', $lotArray));

                        /**
                         *  Then build the includeb command from the string generated
                         */
                        $repreproIncludeParams = 'includedeb ' . $this->repo->getDist() . ' ' . $debFilesConcatenatePaths;

                        /**
                         *  Proceed to import those 100 deb packages into the repo
                         */
                        $myprocess = new \Controllers\Process('/usr/bin/reprepro --keepunusednewfiles -P optionnal --basedir ' . $repoPath . '/ ' . $repreproGpgParams . ' ' . $repreproIncludeParams);

                        /**
                         *  Execute
                         */
                        $myprocess->setBackground(true);
                        $myprocess->execute();

                        /**
                         *  Récupération du pid du process lancé
                         *  Puis écriture du pid de reprepro (lancé par proc_open) dans le fichier PID principal, ceci afin qu'il puisse être killé si l'utilisateur le souhaite
                         */
                        $this->operation->addsubpid($myprocess->getPid());

                        /**
                         *  Affichage de l'output du process en continue dans un fichier
                         */
                        $myprocess->getOutput($this->log->getSteplog());

                        /**
                         *  Si la signature du paquet en cours s'est mal terminée, on incrémente $repreproErrors pour
                         *  indiquer une erreur et on sort de la boucle pour ne pas traiter le paquet suivant
                         */
                        if ($myprocess->getExitCode() != 0) {
                            $repreproErrors++;
                            break;
                        }

                        $myprocess->close();
                    }
                }

                /**
                 *  Case sources packages must be included in the repo too
                 */
                if (!empty($dscFilesGlobalArray)) {
                    /**
                     *  Reprepro can't deal with multiple .dsc files at the same time, so we have to proceed each file one by one
                     *  Known issue https://bugs.launchpad.net/ubuntu/+source/reprepro/+bug/1479148
                     */
                    foreach ($dscFilesGlobalArray as $dscFile) {
                        $repreproIncludeParams = '-S ' . $this->repo->getSection() . ' includedsc ' . $this->repo->getDist() . ' ' . $dscFile;

                        /**
                         *  Proceed to import those 100 deb packages into the repo
                         *  Instanciate a new Process
                         */
                        $myprocess = new \Controllers\Process('/usr/bin/reprepro --keepunusednewfiles -P optionnal -V --basedir ' . $repoPath . '/ ' . $repreproGpgParams . ' ' . $repreproIncludeParams);

                        /**
                         *  Execute
                         */
                        $myprocess->setBackground(true);
                        $myprocess->execute();

                        /**
                         *  Récupération du pid du process lancé
                         *  Puis écriture du pid de reprepro (lancé par proc_open) dans le fichier PID principal, ceci afin qu'il puisse être killé si l'utilisateur le souhaite
                         */
                        $this->operation->addsubpid($myprocess->getPid());

                        /**
                         *  Affichage de l'output du process en continue dans un fichier
                         */
                        $myprocess->getOutput($this->log->getSteplog());

                        /**
                         *  Si la signature du paquet en cours s'est mal terminée, on incrémente $repreproErrors pour
                         *  indiquer une erreur et on sort de la boucle pour ne pas traiter le paquet suivant
                         */
                        if ($myprocess->getExitCode() != 0) {
                            $repreproErrors++;
                            break;
                        }

                        $myprocess->close();
                    }
                }

                echo '</pre></div>';

                $this->log->steplogWrite();

                /**
                 *  Delete temporary directories
                 */
                if ($this->repo->getPackageType() == 'deb') {
                    if (is_dir($repoPath . '/packages')) {
                        if (!\Controllers\Common::deleteRecursive($repoPath . '/packages')) {
                            throw new Exception('Cannot delete temporary directory: ' . $repoPath . '/packages');
                        }
                    }
                    if (is_dir($repoPath . '/sources')) {
                        if (!\Controllers\Common::deleteRecursive($repoPath . '/sources')) {
                            throw new Exception('Cannot delete temporary directory: ' . $repoPath . '/sources');
                        }
                    }
                    if (is_dir($repoPath . '/translations')) {
                        if (!\Controllers\Common::deleteRecursive($repoPath . '/translations')) {
                            throw new Exception('Cannot delete temporary directory: ' . $repoPath . '/translations');
                        }
                    }
                }
            }
        }

        /**
         *  If there was error with createrepo or reprepro
         */
        if ($createRepoErrors != 0 or $repreproErrors != 0) {
            /**
             *  Delete everything to make sure the operation can be relaunched (except if action is 'reconstruct')
             */
            if ($this->operation->getAction() != "reconstruct") {
                if ($this->repo->getPackageType() == 'rpm') {
                    if (!\Controllers\Common::deleteRecursive($repoPath)) {
                        throw new Exception('Repo creation has failed and directory cannot be cleaned: ' . $repoPath);
                    }
                }
                if ($this->repo->getPackageType() == 'deb') {
                    if (!\Controllers\Common::deleteRecursive($repoPath)) {
                        throw new Exception('Repo creation has failed and directory cannot be cleaned: ' . $repoPath);
                    }
                }
            }

            throw new Exception('Repo creation has failed');
        }

        $this->log->steplogWrite();

        /**
         *  Création du lien symbolique (environnement)
         *  Uniquement si l'utilisateur a spécifié de faire pointer un environnement sur le snapshot créé
         */
        if ($this->operation->getAction() == "new" or $this->operation->getAction() == "update") {
            if (!empty($this->repo->getTargetEnv())) {
                if ($this->repo->getPackageType() == 'rpm') {
                    exec('cd ' . REPOS_DIR . '/ && ln -sfn ' . $this->repo->getTargetDateFormatted() . '_' . $this->repo->getName() . ' ' . $this->repo->getName() . '_' . $this->repo->getTargetEnv(), $output, $result);
                }
                if ($this->repo->getPackageType() == 'deb') {
                    exec('cd ' . REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/ && ln -sfn ' . $this->repo->getTargetDateFormatted() . '_' . $this->repo->getSection() . ' ' . $this->repo->getSection() . '_' . $this->repo->getTargetEnv(), $output, $result);
                }
                if ($result != 0) {
                    throw new Exception('Repo finalization has failed');
                }
            }
        }

        $this->log->stepOK();

        return true;
    }
}
