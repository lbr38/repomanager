<?php

namespace Controllers\Repo\Package;

use \Controllers\Utils\Generate\Html\Label;
use \Controllers\Filesystem\Directory;
use \Controllers\Repo\Source\Source;
use \Controllers\Repo\Mirror\Rpm;
use \Controllers\Repo\Mirror\Deb;
use \Controllers\App\DebugMode;
use Exception;
use JsonException;

trait Sync
{
    /**
     *  Sync packages from source repo
     */
    private function syncPackage()
    {
        $mysource = new Source();

        try {
            $this->taskLogStepController->new('sync-packages', 'SYNCING PACKAGES');
            $this->taskLogSubStepController->new('initializing', 'INITIALIZING');

            /**
             *  If it is a new repo, check that a repo with the same name and active snapshots does not already exist.
             *  A repo can exist and have no snapshot / environment attached (it will be invisible in the list) but in this case it should not prevent the creation of a new repo
             */
            if ($this->action == 'create') {
                if ($this->repoController->getPackageType() == 'rpm') {
                    if ($this->rpmRepoController->isActive($this->repoController->getName(), $this->repoController->getReleasever())) {
                        throw new Exception(Label::white($this->repoController->getName() . ' ❯ ' . $this->repoController->getReleasever()) . ' repository already exists');
                    }
                }
                if ($this->repoController->getPackageType() == 'deb') {
                    if ($this->debRepoController->isActive($this->repoController->getName(), $this->repoController->getDist(), $this->repoController->getSection())) {
                        throw new Exception(Label::white($this->repoController->getName() . ' ❯ ' . $this->repoController->getDist() . ' ❯ ' . $this->repoController->getSection()) . ' repository already exists');
                    }
                }
            }

            /**
             *  If it is a repo snapshot update, check that the snapshot id exists in the database
             */
            if ($this->action == 'update') {
                /**
                 *  Check if a snapshot exists in the database
                 */
                if ($this->repoController->existsSnapId($this->repoController->getSnapId()) === false) {
                    throw new Exception('Specified repo snapshot does not exist');
                }

                /**
                 *  We can update a snapshot in the same day, but we can't update another snapshot if a snapshot at the current date already exists
                 *
                 *  So if the snapshot date being updated == today's date ($this->repoController->getDate()) then the task can continue
                 *  Else we check that another snapshot at the current date does not already exist, if it does we quit
                 */
                if ($this->repoController->getSnapDateById($this->repoController->getSnapId()) != $this->repoController->getDate()) {
                    if ($this->repoController->getPackageType() == 'rpm') {
                        if ($this->rpmRepoController->existsSnapDate($this->repoController->getName(), $this->repoController->getReleasever(), $this->repoController->getDate())) {
                            throw new Exception('A snapshot already exists on the ' . Label::black($this->repoController->getDateFormatted()));
                        }
                    }
                    if ($this->repoController->getPackageType() == 'deb') {
                        if ($this->debRepoController->existsSnapDate($this->repoController->getName(), $this->repoController->getDist(), $this->repoController->getSection(), $this->repoController->getDate())) {
                            throw new Exception('A snapshot already exists on the ' . Label::black($this->repoController->getDateFormatted()));
                        }
                    }
                }
            }

            /**
             *  Arch must be specified
             */
            if (empty($this->repoController->getArch())) {
                throw new Exception('Packages arch must be specified');
            }

            /**
             *  Define temporary working directory
             */
            $workingDir = REPOS_DIR . '/temporary-task-' . $this->taskId;

            /**
             *  Define snapshot parent directory
             */
            if ($this->repoController->getPackageType() == 'rpm') {
                $parentDir = REPOS_DIR . '/rpm/' . $this->repoController->getName() . '/' . $this->repoController->getReleasever();
            }
            if ($this->repoController->getPackageType() == 'deb') {
                $parentDir = REPOS_DIR . '/deb/' . $this->repoController->getName() . '/' . $this->repoController->getDist() . '/' . $this->repoController->getSection();
            }

            /**
             *  Define snapshot path
             */
            $snapshotPath = $parentDir . '/' . $this->repoController->getDate();

            /**
             *  If the task is an update, retrieve previous snapshot directory path
             */
            if ($this->action == 'update') {
                if ($this->sourceRepoController->getPackageType() == 'rpm') {
                    $previousSnapshotDir = REPOS_DIR . '/rpm/' . $this->sourceRepoController->getName() . '/' . $this->sourceRepoController->getReleasever() . '/' . $this->sourceRepoController->getDate();
                }
                if ($this->sourceRepoController->getPackageType() == 'deb') {
                    $previousSnapshotDir = REPOS_DIR . '/deb/' . $this->sourceRepoController->getName() . '/' . $this->sourceRepoController->getDist() . '/' . $this->sourceRepoController->getSection() . '/' . $this->sourceRepoController->getDate();
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
            $source = $mysource->get($this->repoController->getPackageType(), $this->repoController->getSource());
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
                $nonCompliantSource = $sourceDefinition['non-compliant'] ?? 'false';
            } catch (JsonException $e) {
                throw new Exception('Could not extract source repository definition: ' . $e->getMessage());
            }

            if (empty($sourceUrl)) {
                throw new Exception('Could not retrieve source repository URL. Check source repository configuration.');
            }

            /**
             *  Define mirroring params
             */
            if ($this->repoController->getPackageType() == 'rpm') {
                $mymirror = new Rpm($this->taskId);
                $mymirror->setReleasever($this->repoController->getReleasever());
            }
            if ($this->repoController->getPackageType() == 'deb') {
                $mymirror = new Deb($this->taskId);
                $mymirror->setNonCompliantSource($nonCompliantSource);
                $mymirror->setDist($this->repoController->getDist());
                $mymirror->setSection($this->repoController->getSection());
            }
            $mymirror->setUrl($sourceUrl);
            $mymirror->setArch($this->repoController->getArch());
            $mymirror->setCheckSignature($this->repoController->getGpgCheck());
            $mymirror->setPackagesToInclude($this->repoController->getPackagesToInclude());
            $mymirror->setPackagesToExclude($this->repoController->getPackagesToExclude());

            /**
             *  If the task is an update, set the previous repo directory path
             *  Hard links will be created from the previous snapshot to the new snapshot
             */
            if ($this->action == 'update' and !empty($previousSnapshotDir)) {
                $mymirror->setPreviousSnapshotDirPath($previousSnapshotDir);
            }

            /**
             *  If the source repo requires a SSL certificate, private key or CA certificate, then they will be used
             */
            if (!empty($sourceDefinition['ssl-authentication']['certificate'])) {
                /**
                 *  Create a temporary file with the certificate content
                 */
                $sslCertificate = tempnam(TEMP_DIR . '/' . $this->taskId, '');

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
                $sslPrivateKey = tempnam(TEMP_DIR . '/' . $this->taskId, '');

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
                $sslCaCertificate = tempnam(TEMP_DIR . '/' . $this->taskId, '');

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

            if ($this->repoController->getPackageType() == 'rpm') {
                /**
                 *  If the repo snapshot must be signed, then retrieve the list of packages to sign from the mirroring task
                 *  It will be used in the signing task (see Sign.php)
                 */
                if ($this->repoController->getGpgSign() == 'true') {
                    $this->packagesToSign = $mymirror->getPackagesToSign();
                }
            }

            unset($mymirror);

            /**
             *  Delete the target snapshot directory if it already exists
             */
            // if (is_dir($snapshotPath)) {
            //     if (!Directory::deleteRecursive($snapshotPath)) {
            //         throw new Exception('Cannot delete existing directory: ' . $snapshotPath);
            //     }
            // }

            // /**
            //  *  Create parent directory if not exists
            //  */
            // if (!is_dir($parentDir)) {
            //     if (!mkdir($parentDir, 0770, true)) {
            //         throw new Exception('Could not create directory: ' . $parentDir);
            //     }
            // }

            /**
             *  Rename temporary working directory to the final snapshot path
             */
            // if (!rename($workingDir, $snapshotPath)) {
            //     throw new Exception('Could not rename working directory ' . $workingDir);
            // }
        } catch (Exception $e) {
            /**
             *  If there was an error while mirroring, delete working dir if exists
             */
            // if (is_dir($workingDir) and !DebugMode::enabled()) {
            //     Directory::deleteRecursive($workingDir);
            // }

            /**
             *  Throw exception with mirror error message
             */
            throw new Exception($e->getMessage());
        }

        $this->taskLogStepController->completed();
    }
}
