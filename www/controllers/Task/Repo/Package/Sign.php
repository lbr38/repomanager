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

        $warning = 0;

        ob_start();

        /**
         *  Signing packages with GPG
         */
        $this->taskLog->step('SIGNING PACKAGES (GPG)');

        echo '<div class="hide signRepoDiv"><pre>';
        $this->taskLog->steplogWrite();

        /**
         *  Retrieve all RPM files recursively
         */
        $rpmFiles = \Controllers\Common::findRecursive(REPOS_DIR . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getName(), 'rpm', true);
        $totalPackages = count($rpmFiles);
        $packageCounter = 1;
        $signError = 0;

        /**
         *  Sort files by name
         */
        asort($rpmFiles);

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
            echo '<span class="opacity-80-cst">(' . $packageCounter . '/' . $totalPackages . ')  ➙ <span class="copy">' . $rpmFile . '</span> ... </span>';

            $this->taskLog->steplogWrite();

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
                echo '<code class="bkg-red font-size-11">KO</code>' . PHP_EOL . 'Error while signing package <span class="copy"><code>' . $rpmFile . '</code></span>: ' . $output . PHP_EOL;
                $signError++;
                break;
            }

            $myprocess->close();

            echo '<code class="bkg-green font-size-11">OK</code>' . PHP_EOL;

            $packageCounter++;
        }
        echo '</pre></div>';

        $this->taskLog->steplogWrite();

        /**
         *  Specific case, we will display a warning if the following message has been detected in the logs
         */
        if (preg_match("/gpg: WARNING:/", file_get_contents($this->taskLog->getStepLog()))) {
            ++$warning;
        }
        if (preg_match("/warning:/", file_get_contents($this->taskLog->getStepLog()))) {
            ++$warning;
        }

        if ($warning != 0) {
            $this->taskLog->stepWarning();
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

        $this->taskLog->stepOK();
    }
}
