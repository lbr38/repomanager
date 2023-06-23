<?php

namespace Controllers\Repo\Operation\Package;

use Exception;

trait Sync
{
    /**
     *  Sync packages from source repo
     */
    private function syncPackage()
    {
        ob_start();

        $this->log->step('SYNCING PACKAGES');

        //// CHECKS ////

        /**
         *  Operation type must be specified ('new' or 'update')
         */
        if (empty($this->operation->getAction())) {
            throw new Exception('Operation type unknow (empty)');
        }
        if ($this->operation->getAction() != 'new' and $this->operation->getAction() != 'update') {
            throw new Exception('Operation type is invalid');
        }

        /**
         *  Verify repo type (mirror or local)
         *  If it must be a local repo then quit because we can't update a local repo
         */
        if ($this->repo->getType() == 'local') {
            throw new Exception('Local repo snapshot cannot be updated');
        }

        /**
         *  2. Si il s'agit d'un nouveau repo, on vérifie qu'un repo du même nom avec un ou plusieurs snapshots actifs n'existe pas déjà.
         *  Un repo peut exister et n'avoir aucun snapshot / environnement rattachés (il sera invisible dans la liste) mais dans ce cas cela ne doit pas empêcher la création d'un nouveau repo
         *
         *  Cas nouveau snapshot de repo :
         */
        if ($this->operation->getAction() == 'new') {
            if ($this->repo->getPackageType() == 'rpm') {
                if ($this->repo->isActive($this->repo->getName()) === true) {
                    throw new Exception('Repo <span class="label-white">' . $this->repo->getName() . '</span> already exists');
                }
            }
            if ($this->repo->getPackageType() == 'deb') {
                if ($this->repo->isActive($this->repo->getName(), $this->repo->getDist(), $this->repo->getSection()) === true) {
                    throw new Exception('Repo <span class="label-white">' . $this->repo->getName() . ' ❯ ' . $this->repo->getDist() . ' ❯ ' . $this->repo->getSection() . '</span> already exists');
                }
            }
        }

        /**
         *  Target arch must be specified
         */
        if (empty($this->repo->getTargetArch())) {
            throw new Exception('Packages arch must be specified');
        }

        /**
         *  Si il s'agit d'une mise à jour de snapshot de repo on vérifie que l'id du snapshot existe en base de données
         */
        if ($this->operation->getAction() == 'update') {
            /**
             *  Vérifie si le snapshot qu'on souhaite mettre à jour existe bien en base de données
             */
            if ($this->repo->existsSnapId($this->repo->getSnapId()) === false) {
                throw new Exception('Specified repo snapshot does not exist');
            }

            /**
             *  On peut remettre à jour un snapshot dans la même journée, mais on ne peut pas mettre à jour un autre snapshot si un snapshot à la date du jour existe déjà
             *
             *  Du coup si la date du snapshot en cours de mise à jour == date du jour ($this->repo->getTargetDate()) alors on peut poursuivre l'opération
             *  Sinon on vérifie qu'un autre snapshot à la date du jour n'existe pas déjà, si c'est le cas on quitte
             */
            if ($this->repo->getSnapDateById($this->repo->getSnapId()) != $this->repo->getTargetDate()) {
                if ($this->repo->getPackageType() == 'rpm') {
                    if ($this->repo->existsRepoSnapDate($this->repo->getTargetDate(), $this->repo->getName()) === true) {
                        throw new Exception('A snapshot already exists on the <span class="label-black">' . $this->repo->getTargetDateFormatted() . '</span>');
                    }
                }
                if ($this->repo->getPackageType() == 'deb') {
                    if ($this->repo->existsRepoSnapDate($this->repo->getTargetDate(), $this->repo->getName(), $this->repo->getDist(), $this->repo->getSection()) === true) {
                        throw new Exception('A snapshot already exists on the <span class="label-black">' . $this->repo->getTargetDateFormatted() . '</span>');
                    }
                }
            }
        }

        $this->log->steplogWrite();

        //// TRAITEMENT ////

        /**
         *  2. Define final repo/section directory path
         */
        if ($this->repo->getPackageType() == 'rpm') {
            $repoPath = REPOS_DIR . '/' . DATE_DMY . '_' . $this->repo->getName();
            $this->repo->setWorkingDir(REPOS_DIR . '/download-mirror-' . $this->repo->getName() . '-' . time());
        }
        if ($this->repo->getPackageType() == 'deb') {
            $repoPath = REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . DATE_DMY . '_' . $this->repo->getSection();
            $this->repo->setWorkingDir(REPOS_DIR . '/download-mirror-' . $this->repo->getName() . '-' . $this->repo->getDist() . '-' . $this->repo->getSection()  . '-' . time());
        }

        /**
         *  If onlySyncDifference is true then copy source snapshot content to the working dir to avoid downloading packages that already exists, and only download the new packages.
         *  This parameter is used in the case of a snapshot update only (operation 'update').
         */
        if ($this->repo->getOnlySyncDifference() == 'yes') {
            /**
             *  Create working dir
             */
            if (!is_dir($this->repo->getWorkingDir())) {
                if (!mkdir($this->repo->getWorkingDir(), 0770, true)) {
                    throw new Exception('Cannot create temporary working directory ' . $this->repo->getWorkingDir());
                }
            }
            if (!is_dir($this->repo->getWorkingDir() . '/packages')) {
                if (!mkdir($this->repo->getWorkingDir() . '/packages', 0770, true)) {
                    throw new Exception('Cannot create temporary working directory ' . $this->repo->getWorkingDir() . '/packages');
                }
            }

            /**
             *  Get all source snapshot informations to retrieve snapshot directory path
             */
            $sourceSnapshot = new \Controllers\Repo\Repo();
            $sourceSnapshot->getAllById(null, $this->repo->getSnapId());

            /**
             *  Retrieve source snapshot directory from the informations
             */
            if ($this->repo->getPackageType() == 'rpm') {
                $sourceSnapshotDir = REPOS_DIR . '/' . $sourceSnapshot->getDateFormatted() . '_' . $sourceSnapshot->getName();
            }
            if ($this->repo->getPackageType() == 'deb') {
                $sourceSnapshotDir = REPOS_DIR . '/' . $sourceSnapshot->getName() . '/' . $sourceSnapshot->getDist() . '/' . $sourceSnapshot->getDateFormatted() . '_' . $sourceSnapshot->getSection();
            }

            /**
             *  Check that source snapshot directory exists
             */
            if (!is_dir($sourceSnapshotDir)) {
                throw new Exception('Source snapshot directory does not exist: ' . $sourceSnapshotDir);
            }

            /**
             *  Find source snapshot packages
             */
            if ($this->repo->getPackageType() == 'rpm') {
                $rpmPackages          = \Controllers\Common::findAndCopyRecursive($sourceSnapshotDir, $this->repo->getWorkingDir() . '/packages', 'rpm', true);
            }
            if ($this->repo->getPackageType() == 'deb') {
                $debPackages          = \Controllers\Common::findAndCopyRecursive($sourceSnapshotDir . '/pool', $this->repo->getWorkingDir() . '/packages', 'deb', true);
                $dscSourcesPackages   = \Controllers\Common::findAndCopyRecursive($sourceSnapshotDir . '/pool', $this->repo->getWorkingDir() . '/packages', 'dsc', true);
                $tarxzSourcesPackages = \Controllers\Common::findAndCopyRecursive($sourceSnapshotDir . '/pool', $this->repo->getWorkingDir() . '/packages', 'xz', true);
                $targzSourcesPackages = \Controllers\Common::findAndCopyRecursive($sourceSnapshotDir . '/pool', $this->repo->getWorkingDir() . '/packages', 'gz', true);
            }

            unset($sourceSnapshot);
        }

        /**
         *  3. Retrieving packages
         */
        echo '<div class="hide getPackagesDiv"><pre>';
        $this->log->steplogWrite();

        /**
         *  If syncing packages using embedded mirroring tool (beta)
         */
        if ($this->repo->getPackageType() == 'deb') {
            try {
                /**
                 *  Get source repo informations
                 */
                $mysource = new \Controllers\Source();
                $sourceDetails = $mysource->getAll('deb', $this->repo->getSource());

                /**
                 *  Check source repo informations
                 */
                if (empty($sourceDetails)) {
                    throw new Exception('Could not retrieve source repo informations. Does the source repo still exists?');
                }
                if (empty($sourceDetails['Url'])) {
                    throw new Exception('Could not retrieve source repo URL. Check source repo configuration.');
                }

                unset($mysource);

                $mymirror = new \Controllers\Mirror();
                $mymirror->setType('deb');
                $mymirror->setUrl($sourceDetails['Url']);
                $mymirror->setWorkingDir($this->repo->getWorkingDir());
                $mymirror->setDist($this->repo->getDist());
                $mymirror->setSection($this->repo->getSection());
                $mymirror->setArch($this->repo->getTargetArch());
                $mymirror->setSyncSource($this->repo->getTargetSourcePackage());
                $mymirror->setCheckSignature($this->repo->getTargetGpgCheck());
                $mymirror->setTranslation($this->repo->getTargetPackageTranslation());
                $mymirror->setOutputFile($this->log->getStepLog());
                $mymirror->outputToFile(true);
                if (!empty($sourceDetails['Ssl_certificate_path'])) {
                    $mymirror->setSslCustomCertificate($sourceDetails['Ssl_certificate_path']);
                }
                if (!empty($sourceDetails['Ssl_private_key_path'])) {
                    $mymirror->setSslCustomPrivateKey($sourceDetails['Ssl_private_key_path']);
                }
                $mymirror->mirror();

                /**
                 *  Create repo and dist directories if not exist
                 */
                if (!is_dir(REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist())) {
                    if (!mkdir(REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist(), 0770, true)) {
                        throw new Exception('Could not create directory: ' . REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist());
                    }
                }

                /**
                 *  Renaming working dir name to final name
                 *  First delete the target directory if it already exists
                 */
                if (is_dir($repoPath)) {
                    if (!\Controllers\Common::deleteRecursive($repoPath)) {
                        throw new Exception('Cannot delete existing directory: ' . $repoPath);
                    }
                }
                if (!rename($this->repo->getWorkingDir(), $repoPath)) {
                    throw new Exception('Could not rename working directory ' . $this->repo->getWorkingDir());
                }
            } catch (Exception $e) {
                echo '</pre></div>';
                throw new Exception($e->getMessage());
            }
        }

        if ($this->repo->getPackageType() == 'rpm') {
            try {
                /**
                 *  Get source repo informations
                 */
                $mysource = new \Controllers\Source();
                $sourceDetails = $mysource->getAll('rpm', $this->repo->getSource());

                /**
                 *  Check source repo informations
                 */
                if (empty($sourceDetails)) {
                    throw new Exception('Could not retrieve source repo informations. Does the source repo still exists?');
                }
                if (empty($sourceDetails['Url'])) {
                    throw new Exception('Could not retrieve source repo URL. Check source repo configuration.');
                }

                unset($mysource);

                $mymirror = new \Controllers\Mirror();
                $mymirror->setType('rpm');
                $mymirror->setUrl($sourceDetails['Url']);
                $mymirror->setWorkingDir($this->repo->getWorkingDir());
                $mymirror->setReleasever($this->repo->getReleasever());
                $mymirror->setArch($this->repo->getTargetArch());
                $mymirror->setSyncSource($this->repo->getTargetSourcePackage());
                $mymirror->setCheckSignature($this->repo->getTargetGpgCheck());
                $mymirror->setOutputFile($this->log->getStepLog());
                $mymirror->outputToFile(true);

                /**
                 *  If the source repo has a http:// GPG signature key, then it will be used to check for package signature
                 */
                if (!empty($sourceDetails['Gpgkey'])) {
                    $mymirror->setGpgKeyUrl($sourceDetails['Gpgkey']);
                }
                if (!empty($sourceDetails['Ssl_certificate_path'])) {
                    $mymirror->setSslCustomCertificate($sourceDetails['Ssl_certificate_path']);
                }
                if (!empty($sourceDetails['Ssl_private_key_path'])) {
                    $mymirror->setSslCustomPrivateKey($sourceDetails['Ssl_private_key_path']);
                }
                $mymirror->mirror();

                /**
                 *  Renaming working dir name to final name
                 *  First delete the target directory if it already exists
                 */
                if (is_dir($repoPath)) {
                    if (!\Controllers\Common::deleteRecursive($repoPath)) {
                        throw new Exception('Cannot delete existing directory: ' . $repoPath);
                    }
                }
                if (!rename($this->repo->getWorkingDir(), $repoPath)) {
                    throw new Exception('Could not rename working directory ' . $this->repo->getWorkingDir());
                }
            } catch (Exception $e) {
                echo '</pre></div>';
                throw new Exception($e->getMessage());
            }
        }

        echo '</pre></div>';

        $this->log->stepOK();

        return true;
    }
}
