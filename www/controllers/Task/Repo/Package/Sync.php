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
        $mysource = new \Controllers\Repo\Source\Source();

        $this->taskLogStepController->new('sync-packages', 'SYNCING PACKAGES');

        try {
            $this->taskLogSubStepController->new('initializing', 'INITIALIZING');

            /**
             *  If it is a new repo, check that a repo with the same name and active snapshots does not already exist.
             *  A repo can exist and have no snapshot / environment attached (it will be invisible in the list) but in this case it should not prevent the creation of a new repo
             */
            if ($this->task->getAction() == 'create') {
                if ($this->repo->getPackageType() == 'rpm') {
                    if ($this->rpmRepoController->isActive($this->repo->getName(), $this->repo->getReleasever())) {
                        throw new Exception('<span class="label-white">' . $this->repo->getName() . ' (release ver. ' . $this->repo->getReleasever() . ')</span> repository already exists');
                    }
                }
                if ($this->repo->getPackageType() == 'deb') {
                    if ($this->debRepoController->isActive($this->repo->getName(), $this->repo->getDist(), $this->repo->getSection())) {
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
                        if ($this->rpmRepoController->existsSnapDate($this->repo->getName(), $this->repo->getReleasever(), $this->repo->getDate())) {
                            throw new Exception('A snapshot already exists on the <span class="label-black">' . $this->repo->getDateFormatted() . '</span>');
                        }
                    }
                    if ($this->repo->getPackageType() == 'deb') {
                        if ($this->debRepoController->existsSnapDate($this->repo->getName(), $this->repo->getDist(), $this->repo->getSection(), $this->repo->getDate())) {
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

            /**
             *  Define temporary working directory
             */
            $workingDir = REPOS_DIR . '/temporary-task-' . $this->task->getId();

            /**
             *  Define snapshot parent directory
             */
            if ($this->repo->getPackageType() == 'rpm') {
                $parentDir = REPOS_DIR . '/rpm/' . $this->repo->getName() . '/' . $this->repo->getReleasever();
            }
            if ($this->repo->getPackageType() == 'deb') {
                $parentDir = REPOS_DIR . '/deb/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection();
            }

            /**
             *  Define snapshot path
             */
            $snapshotPath = $parentDir . '/' . $this->repo->getDate();

            /**
             *  If the task is an update, retrieve previous snapshot directory path
             */
            if ($this->task->getAction() == 'update') {
                if ($this->sourceRepo->getPackageType() == 'rpm') {
                    $previousSnapshotDir = REPOS_DIR . '/rpm/' . $this->sourceRepo->getName() . '/' . $this->sourceRepo->getReleasever() . '/' . $this->sourceRepo->getDate();
                }
                if ($this->sourceRepo->getPackageType() == 'deb') {
                    $previousSnapshotDir = REPOS_DIR . '/deb/' . $this->sourceRepo->getName() . '/' . $this->sourceRepo->getDist() . '/' . $this->sourceRepo->getSection() . '/' . $this->sourceRepo->getDate();
                }

                /**
                 *  Check that previous snapshot directory has been retrieved
                 */
                if (empty($previousSnapshotDir)) {
                    throw new Exception('Could not retrieve previous snapshot directory');
                }

                /**
                 *  Check that previous snapshot directory exists
                 */
                if (!is_dir($previousSnapshotDir)) {
                    throw new Exception('Previous snapshot directory does not exist: ' . $previousSnapshotDir);
                }
            }

            /**
             *  Get source repo informations
             */
            $source = $mysource->get($this->repo->getPackageType(), $this->repo->getSource());
            $sourceDefinition = $source['Definition'];

            /**
             *  Check that source repo informations have been retrieved
             */
            if (empty($sourceDefinition)) {
                throw new Exception('Could not retrieve source repository informations. Does the source repository still exists?');
            }

            /**
             *  Extract source repo JSON definiton
             */
            try {
                $sourceDefinition = json_decode($sourceDefinition, true);
                $sourceUrl = $sourceDefinition['url'];
            } catch (ValueError $e) {
                throw new Exception('Could not extract source repository definition: ' . $e->getMessage());
            }

            if (empty($sourceUrl)) {
                throw new Exception('Could not retrieve source repository URL. Check source repository configuration.');
            }

            /**
             *  Define mirroring params
             */
            if ($this->repo->getPackageType() == 'rpm') {
                $mymirror = new \Controllers\Repo\Mirror\Rpm($this->task->getId());
                $mymirror->setReleasever($this->repo->getReleasever());
            }
            if ($this->repo->getPackageType() == 'deb') {
                $mymirror = new \Controllers\Repo\Mirror\Deb($this->task->getId());
                $mymirror->setDist($this->repo->getDist());
                $mymirror->setSection($this->repo->getSection());
            }
            $mymirror->setUrl($sourceUrl);
            $mymirror->setWorkingDir($workingDir);
            $mymirror->setArch($this->repo->getArch());
            $mymirror->setCheckSignature($this->repo->getGpgCheck());
            $mymirror->setPackagesToInclude($this->repo->getPackagesToInclude());
            $mymirror->setPackagesToExclude($this->repo->getPackagesToExclude());

            /**
             *  If the task is an update, set the previous repo directory path
             *  Hard links will be created from the previous snapshot to the new snapshot
             */
            if ($this->task->getAction() == 'update' and !empty($previousSnapshotDir)) {
                $mymirror->setPreviousSnapshotDirPath($previousSnapshotDir);
            }

            /**
             *  If the source repo requires a SSL certificate, private key or CA certificate, then they will be used
             */
            if (!empty($sourceDefinition['ssl-authentication']['certificate'])) {
                /**
                 *  Create a temporary file with the certificate content
                 */
                $sslCertificate = tempnam(TEMP_DIR . '/' . $this->task->getId(), '');

                if (!$sslCertificate) {
                    throw new Exception('Could not create temporary file for SSL certificate');
                }

                /**
                 *  Write the certificate content to the temporary file
                 */
                if (!file_put_contents($sslCertificate, $sourceDefinition['ssl-authentication']['certificate'])) {
                    throw new Exception('Could not write SSL certificate to temporary file');
                }

                /**
                 *  Set the certificate file to use
                 */
                $mymirror->setSslCustomCertificate($sslCertificate);
            }
            if (!empty($sourceDefinition['ssl-authentication']['private-key'])) {
                /**
                 *  Create a temporary file with the private key content
                 */
                $sslPrivateKey = tempnam(TEMP_DIR . '/' . $this->task->getId(), '');

                if (!$sslPrivateKey) {
                    throw new Exception('Could not create temporary file for SSL private key');
                }

                /**
                 *  Write the private key content to the temporary file
                 */
                if (!file_put_contents($sslPrivateKey, $sourceDefinition['ssl-authentication']['private-key'])) {
                    throw new Exception('Could not write SSL private key to temporary file');
                }

                /**
                 *  Set the private key file to use
                 */
                $mymirror->setSslCustomPrivateKey($sslPrivateKey);
            }
            if (!empty($sourceDefinition['ssl-authentication']['ca-certificate'])) {
                /**
                 *  Create a temporary file with the CA certificate content
                 */
                $sslCaCertificate = tempnam(TEMP_DIR . '/' . $this->task->getId(), '');

                if (!$sslCaCertificate) {
                    throw new Exception('Could not create temporary file for SSL CA certificate');
                }

                /**
                 *  Write the CA certificate content to the temporary file
                 */
                if (!file_put_contents($sslCaCertificate, $sourceDefinition['ssl-authentication']['ca-certificate'])) {
                    throw new Exception('Could not write SSL CA certificate to temporary file');
                }

                /**
                 *  Set the CA certificate file to use
                 */
                $mymirror->setSslCustomCaCertificate($sslCaCertificate);
            }

            $this->taskLogSubStepController->completed();

            unset($mysource, $source);
        } catch (Exception $e) {
            /**
             *  Throw exception with mirror error message
             */
            throw new Exception($e->getMessage());
        }

        /**
         *  3. Retrieving packages
         */
        try {
            /**
             *  Start mirroring
             */
            $mymirror->mirror();

            if ($this->repo->getPackageType() == 'rpm') {
                /**
                 *  If the repo snapshot must be signed, then retrieve the list of packages to sign from the mirroring task
                 *  It will be used in the signing task (see Sign.php)
                 */
                if ($this->repo->getGpgSign() == 'true') {
                    $this->packagesToSign = $mymirror->getPackagesToSign();
                }
            }

            unset($mymirror);

            /**
             *  Delete the target snapshot directory if it already exists
             */
            if (is_dir($snapshotPath)) {
                if (!\Controllers\Filesystem\Directory::deleteRecursive($snapshotPath)) {
                    throw new Exception('Cannot delete existing directory: ' . $snapshotPath);
                }
            }

            /**
             *  Create parent directory if not exists
             */
            if (!is_dir($parentDir)) {
                if (!mkdir($parentDir, 0770, true)) {
                    throw new Exception('Could not create directory: ' . $parentDir);
                }
            }

            /**
             *  Rename temporary working directory to the final snapshot path
             */
            if (!rename($workingDir, $snapshotPath)) {
                throw new Exception('Could not rename working directory ' . $workingDir);
            }
        } catch (Exception $e) {
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

        $this->taskLogStepController->completed();
    }
}
