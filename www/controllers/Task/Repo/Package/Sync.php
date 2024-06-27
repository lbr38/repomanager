<?php

namespace Controllers\Task\Repo\Package;

use Exception;

trait Sync
{
    /**
     *  Sync packages from source repo
     */
    private function syncPackage()
    {
        ob_start();

        $this->taskLog->step('SYNCING PACKAGES');

        echo '<div class="hide getPackagesDiv"><pre>';
        $this->taskLog->steplogWrite();

        //// CHECKS ////

        try {
            /**
             *  Task action must be specified ('create' or 'update')
             */
            if (empty($this->task->getAction())) {
                throw new Exception('Task action unknow (empty)');
            }
            if ($this->task->getAction() != 'create' and $this->task->getAction() != 'update') {
                throw new Exception('Task action is invalid');
            }

            /**
             *  Verify repo type (mirror or local)
             *  If it must be a local repo then quit because we can't update a local repo
             */
            if ($this->repo->getType() == 'local') {
                throw new Exception('Local repo snapshot cannot be updated');
            }

            /**
             *  If it is a new repo, check that a repo with the same name and active snapshots does not already exist.
             *  A repo can exist and have no snapshot / environment attached (it will be invisible in the list) but in this case it should not prevent the creation of a new repo
             */
            if ($this->task->getAction() == 'create') {
                if ($this->repo->getPackageType() == 'rpm') {
                    if ($this->repo->isActive($this->repo->getName())) {
                        throw new Exception('<span class="label-white">' . $this->repo->getName() . '</span> repository already exists');
                    }
                }
                if ($this->repo->getPackageType() == 'deb') {
                    if ($this->repo->isActive($this->repo->getName(), $this->repo->getDist(), $this->repo->getSection())) {
                        throw new Exception('<span class="label-white">' . $this->repo->getName() . ' ❯ ' . $this->repo->getDist() . ' ❯ ' . $this->repo->getSection() . '</span> repository already exists');
                    }
                }
            }

            /**
             *  If it is a repo snapshot update, check that the snapshot id exists in the database
             */
            if ($this->task->getAction() == 'update') {
                /**
                 *  Check if a snapshot exists in the database
                 */
                if ($this->repo->existsSnapId($this->repo->getSnapId()) === false) {
                    throw new Exception('Specified repo snapshot does not exist');
                }

                /**
                 *  We can update a snapshot in the same day, but we can't update another snapshot if a snapshot at the current date already exists
                 *
                 *  So if the snapshot date being updated == today's date ($this->repo->getDate()) then the task can continue
                 *  Else we check that another snapshot at the current date does not already exist, if it does we quit
                 */
                if ($this->repo->getSnapDateById($this->repo->getSnapId()) != $this->repo->getDate()) {
                    if ($this->repo->getPackageType() == 'rpm') {
                        if ($this->repo->existsRepoSnapDate($this->repo->getDate(), $this->repo->getName()) === true) {
                            throw new Exception('A snapshot already exists on the <span class="label-black">' . $this->repo->getDateFormatted() . '</span>');
                        }
                    }
                    if ($this->repo->getPackageType() == 'deb') {
                        if ($this->repo->existsRepoSnapDate($this->repo->getDate(), $this->repo->getName(), $this->repo->getDist(), $this->repo->getSection()) === true) {
                            throw new Exception('A snapshot already exists on the <span class="label-black">' . $this->repo->getDateFormatted() . '</span>');
                        }
                    }
                }
            }

            /**
             *  Arch must be specified
             */
            if (empty($this->repo->getArch())) {
                throw new Exception('Packages arch must be specified');
            }

            $this->taskLog->steplogWrite();

            /**
             *  2. Define final repo/section directory path
             */
            if ($this->repo->getPackageType() == 'rpm') {
                $repoPath = REPOS_DIR . '/' . DATE_DMY . '_' . $this->repo->getName();
                $workingDir = REPOS_DIR . '/download-mirror-' . $this->repo->getName() . '-' . time();
            }
            if ($this->repo->getPackageType() == 'deb') {
                $repoPath = REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . DATE_DMY . '_' . $this->repo->getSection();
                $workingDir = REPOS_DIR . '/download-mirror-' . $this->repo->getName() . '-' . $this->repo->getDist() . '-' . $this->repo->getSection()  . '-' . time();
            }

            /**
             *  If onlySyncDifference is true then copy source snapshot content to the working dir to avoid downloading packages that already exists, and only download the new packages.
             *  This parameter is used in the case of a snapshot update only (task 'update').
             */
            if ($this->repo->getOnlySyncDifference() == 'true') {
                $this->taskLog->steplogWrite('Only sync the difference enabled: copying source snapshot packages to the new repository snapshot.' . PHP_EOL);

                /**
                 *  Create working dir
                 */
                if (!is_dir($workingDir)) {
                    if (!mkdir($workingDir, 0770, true)) {
                        throw new Exception('Cannot create temporary working directory ' . $workingDir);
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
                    $sourceSnapshotDir = REPOS_DIR . '/' . $sourceSnapshot->getName() . '/' . $sourceSnapshot->getDist() . '/' . $sourceSnapshot->getDateFormatted() . '_' . $sourceSnapshot->getSection() . '/pool/' . $sourceSnapshot->getSection();
                }

                /**
                 *  Check that source snapshot directory exists
                 */
                if (!is_dir($sourceSnapshotDir)) {
                    throw new Exception('Source snapshot directory does not exist: ' . $sourceSnapshotDir);
                }

                /**
                 *  RPM repo: copy source snapshot packages to the working dir packages directory
                 */
                if ($this->repo->getPackageType() == 'rpm') {
                    /**
                     *  First, create the packages directory
                     */
                    if (!is_dir($workingDir . '/packages')) {
                        if (!mkdir($workingDir . '/packages', 0770, true)) {
                            throw new Exception('Cannot create packages directory ' . $workingDir . '/packages');
                        }
                    }

                    \Controllers\Filesystem\Directory::copy($sourceSnapshotDir . '/packages', $workingDir . '/packages');
                }

                /**
                 *  DEB repo: copy source snapshot pool directory to the working dir pool directory
                 */
                if ($this->repo->getPackageType() == 'deb') {
                    \Controllers\Filesystem\Directory::copy($sourceSnapshotDir, $workingDir . '/pool/' . $sourceSnapshot->getSection());
                }

                unset($sourceSnapshot, $sourceSnapshotDir);
            }
        } catch (Exception $e) {
            echo '</pre></div>';

            /**
             *  Throw exception with mirror error message
             */
            throw new Exception($e->getMessage());
        }

        /**
         *  3. Retrieving packages
         */
        try {
            $mysource = new \Controllers\Source();

            if ($this->repo->getPackageType() == 'rpm') {
                /**
                 *  Get source repo informations
                 */
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

                $mymirror = new \Controllers\Repo\Mirror\Rpm();
                $mymirror->setUrl($sourceDetails['Url']);
                $mymirror->setWorkingDir($workingDir);
                $mymirror->setReleasever($this->repo->getReleasever());
                $mymirror->setArch($this->repo->getArch());
                $mymirror->setCheckSignature($this->repo->getGpgCheck());
                $mymirror->setOutputFile($this->taskLog->getStepLog());
                $mymirror->outputToFile(true);

                /**
                 *  If the source repo has a http:// GPG signing key, then it will be used to check for package signature
                 */
                if (!empty($sourceDetails['Gpgkey'])) {
                    $mymirror->setGpgKeyUrl($sourceDetails['Gpgkey']);
                }

                /**
                 *  If the source repo has a custom SSL certificate, private key or CA certificate, then they will be used
                 */
                if (!empty($sourceDetails['Ssl_certificate_path'])) {
                    $mymirror->setSslCustomCertificate($sourceDetails['Ssl_certificate_path']);
                }
                if (!empty($sourceDetails['Ssl_private_key_path'])) {
                    $mymirror->setSslCustomPrivateKey($sourceDetails['Ssl_private_key_path']);
                }
                if (!empty($sourceDetails['Ssl_ca_certificate_path'])) {
                    $mymirror->setSslCustomCaCertificate($sourceDetails['Ssl_ca_certificate_path']);
                }
                $mymirror->mirror();

                unset($mymirror);
            }

            if ($this->repo->getPackageType() == 'deb') {
                /**
                 *  Get source repo informations
                 */
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

                $mymirror = new \Controllers\Repo\Mirror\Deb();
                $mymirror->setUrl($sourceDetails['Url']);
                $mymirror->setWorkingDir($workingDir);
                $mymirror->setDist($this->repo->getDist());
                $mymirror->setSection($this->repo->getSection());
                $mymirror->setArch($this->repo->getArch());
                $mymirror->setCheckSignature($this->repo->getGpgCheck());
                // $mymirror->setTranslation($this->repo->getTargetPackageTranslation());
                $mymirror->setOutputFile($this->taskLog->getStepLog());
                $mymirror->outputToFile(true);
                if (!empty($sourceDetails['Ssl_certificate_path'])) {
                    $mymirror->setSslCustomCertificate($sourceDetails['Ssl_certificate_path']);
                }
                if (!empty($sourceDetails['Ssl_private_key_path'])) {
                    $mymirror->setSslCustomPrivateKey($sourceDetails['Ssl_private_key_path']);
                }
                $mymirror->mirror();

                unset($mymirror);

                /**
                 *  Create repo and dist directories if not exist
                 */
                if (!is_dir(REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist())) {
                    if (!mkdir(REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist(), 0770, true)) {
                        throw new Exception('Could not create directory: ' . REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist());
                    }
                }
            }

            /**
             *  Renaming working dir name to final name
             *  First delete the target directory if it already exists
             */
            if (is_dir($repoPath)) {
                if (!\Controllers\Filesystem\Directory::deleteRecursive($repoPath)) {
                    throw new Exception('Cannot delete existing directory: ' . $repoPath);
                }
            }

            /**
             *  Then rename
             */
            if (!rename($workingDir, $repoPath)) {
                throw new Exception('Could not rename working directory ' . $workingDir);
            }
        } catch (Exception $e) {
            echo '</pre></div>';

            /**
             *  If there was an error while mirroring, delete working dir if exists
             */
            if (is_dir($workingDir)) {
                \Controllers\Filesystem\Directory::deleteRecursive($workingDir);
            }

            /**
             *  Throw exception with mirror error message
             */
            throw new Exception($e->getMessage());
        }

        echo '</pre></div>';

        $this->taskLog->stepOK();

        return true;
    }
}
