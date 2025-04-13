<?php

namespace Controllers\Task\Repo\Package;

use Exception;

trait Sign
{
    /**
     *  Sign the packages with GPG (RPM only)
     *  Exclusive to rpm packages because with deb it's the Release file that is signed
     */
    private function signPackage()
    {
        $signError = 0;

        /**
         *  Skip if not rpm
         */
        if ($this->repo->getPackageType() != 'rpm') {
            return;
        }

        /**
         *  Skip if package signing is not enabled
         */
        if ($this->repo->getGpgSign() != 'true') {
            return;
        }

        /**
         *  Signing packages with GPG
         */
        $this->taskLogStepController->new('sign-packages', 'SIGNING PACKAGES');
        $this->taskLogSubStepController->new('sign-packages', 'SIGNING PACKAGES WITH GPG');

        try {
            /**
             *  In case of a new repository or a repository rebuild, all packages must be signed
             *  Force all packages to be signed
             */
            if ($this->task->getAction() == 'create' or $this->task->getAction() == 'rebuild') {
                $this->packagesToSign = 'all';
            }

            /**
             *  In case of a repository update, we need to check if the previous repo snapshot was signed
             *  If the previous repo snapshot was not signed at all and the new one has to be signed, then force all packages to be signed
             */
            if ($this->task->getAction() == 'update') {
                if ($this->sourceRepo->getSigned() == 'false' and $this->repo->getGpgSign() == 'true') {
                    $this->packagesToSign = 'all';
                }
            }

            /**
             *  If all packages must be signed, retrieve all RPM files recursively
             */
            if (!is_array($this->packagesToSign) and $this->packagesToSign == 'all') {
                $rpmFiles = \Controllers\Filesystem\File::findRecursive(REPOS_DIR . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getName(), ['rpm'], true);
            }

            /**
             *  Else, if only specific packages must be signed ($this->packagesToSign is an array with a list of packages), retrieve the list of packages to sign
             *  This list was generated by the rpm mirroring task (see Rpm.php)
             *  The list is a list of relative paths to the packages, so we need to add the full path to the packages
             */
            if (is_array($this->packagesToSign)) {
                foreach ($this->packagesToSign as $relativePackagePath) {
                    $rpmFiles[] = REPOS_DIR . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getName() . '/' . $relativePackagePath;
                }
            }

            /**
             *  If no packages are found or no packages were marked for signing, print a message and nothing will be signed
             */
            if (empty($rpmFiles)) {
                $this->taskLogSubStepController->output('No packages marked for signing.');
            }

            /**
             *  Otherwise, sort all files by name
             */
            if (!empty($rpmFiles)) {
                asort($rpmFiles);

                /**
                 *  Count total packages to sign and initialize counter
                 */
                $totalPackages = count($rpmFiles);
                $packageCounter = 1;

                /**
                 *  Print if all packages are to be signed or if specific packages are to be signed
                 */
                if (!is_array($this->packagesToSign) and $this->packagesToSign == 'all') {
                    $this->taskLogSubStepController->output('Signing all packages');
                }
                if (is_array($this->packagesToSign)) {
                    $this->taskLogSubStepController->output('Signing ' . $totalPackages . ' package(s)');
                }

                /**
                 *  Process each found file
                 */
                foreach ($rpmFiles as $rpmFile) {
                    /**
                     *  We need a gpg macros file, we sign only if the macros file is present, otherwise we return an error
                     */
                    if (!file_exists(MACROS_FILE)) {
                        throw new Exception('GPG macros file for rpm does not exist.');
                    }

                    if (!file_exists($rpmFile)) {
                        throw new Exception('RPM file <code>' . $rpmFile . '</code> not found (deleted?).');
                    }

                    /**
                     *  Print package counter
                     */
                    $this->taskLogSubStepController->new('signing-package-' . $packageCounter, 'SIGNING PACKAGE (' . $packageCounter . '/' . $totalPackages . ')', basename($rpmFile));

                    /**
                     *  Sign package
                     */
                    $myprocess = new \Controllers\Process('/usr/bin/rpmsign --macros=' . MACROS_FILE . ' --addsign ' . $rpmFile, array('GPG_TTY' => '$(tty)'));

                    /**
                     *  Execution
                     */
                    $myprocess->execute();

                    /**
                     *  Retrieve output from process
                     */
                    $output = $myprocess->getOutput();

                    /**
                     *  If the signature of the current package failed, we increment $signError to indicate an error and we exit the loop to not process the next package
                     */
                    if ($myprocess->getExitCode() != 0) {
                        throw new Exception('Error while signing package:<br><pre class="codeblock margin-top-10">' . $output . '</pre>');
                    }

                    $myprocess->close();

                    /**
                     *  Specific case, we will display a warning if "warning:" has been detected in the logs
                     */
                    if (preg_match("/warning:/i", $output)) {
                        // If the warning is about an identical signature, we skip the package and mark the substep as completed
                        if (preg_match("/already contains identical signature, skipping/", $output)) {
                            $this->taskLogSubStepController->completed('Identical signature already present, skipping');

                        // Otherwise, we display the warning and mark the substep as completed
                        } else {
                            $this->taskLogSubStepController->warning('Warning detected during package signing');
                            $this->taskLogSubStepController->output($output, 'pre');
                        }
                    } else {
                        $this->taskLogSubStepController->completed();
                    }

                    $packageCounter++;
                }
            }
        } catch (Exception $e) {
            /**
             *  Throw exception with error message
             */
            throw new Exception($e->getMessage());
        }

        /**
         *  If there was an error, delete what has been done
         */
        if ($signError != 0) {
            /**
             *  If the action is 'rebuild' then we do not delete what has been done (otherwise it deletes the repo!)
             */
            if ($this->task->getAction() != 'rebuild') {
                /**
                 *  Delete what has been done
                 */
                \Controllers\Filesystem\Directory::deleteRecursive(REPOS_DIR . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getName());
            }

            throw new Exception('Packages signature has failed');
        }

        // Set the main substep as completed
        $this->taskLogSubStepController->completed('', 'sign-packages');

        $this->taskLogStepController->completed();
    }
}
