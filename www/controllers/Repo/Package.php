<?php

namespace Controllers\Repo;

use Exception;

class Package
{
    /**
     *  Upload package to repo
     */
    public function upload(int $snapId, $packages)
    {
        /**
         *  If the user is not an administrator and does not have permission to upload packages, prevent access to this action.
         */
        if (!IS_ADMIN and !in_array('upload-package', USER_PERMISSIONS['repositories']['allowed-actions']['repos'])) {
            throw new Exception('You are not allowed to upload packages');
        }

        $myrepo = new \Controllers\Repo\Repo();

        /**
         *  Retrieve repo infos from DB
         */
        $repoDetails = $myrepo->getAllById('', $snapId, '');

        /**
         *  Retrieve current repo architectures
         *  We will need this to update the repo architectures list if new packages are uploaded
         */
        $currentArchs = $myrepo->getArch($snapId);

        /**
         *  Define snapshot path
         */
        if ($myrepo->getPackageType() == 'rpm') {
            $snapshotPath = REPOS_DIR .'/rpm/'. $myrepo->getName() . '/' . $myrepo->getReleasever() . '/' . $myrepo->getDate();
        }
        if ($myrepo->getPackageType() == 'deb') {
            $snapshotPath = REPOS_DIR .'/deb/'. $myrepo->getName() . '/'. $myrepo->getDist() . '/' . $myrepo->getSection() . '/' . $myrepo->getDate();
        }

        /**
         *  If the path does not exist on the server then we quit
         */
        if (!is_dir($snapshotPath)) {
            throw new Exception('Repository directory ' . $snapshotPath . ' does not exist');
        }

        /**
         *  Check each file size to make sure it is not empty
         */
        foreach ($packages as $package) {
            if ($package['size'] == 0) {
                throw new Exception('You must upload a file.');
            }
        }

        $packageExists = array();      // will contain the list of packages that already exist
        $packagesError = array();      // will contain the list of packages uploaded with an error
        $packageEmpty = array();       // will contain the list of empty packages
        $packageInvalidName = array(); // will contain the list of packages whose name is invalid
        $packageInvalid = array();     // will contain the list of packages whose format is invalid

        foreach ($packages as $package) {
            $uploadError    = 0;
            $packageName    = $package['name'];
            $packageSize    = $package['size'];
            $packageError   = $package['error'];
            $packageTmpName = $package['tmp_name'];
            $packageType    = mime_content_type($packageTmpName);

            /**
             *  Package name must not contain special characters
             *  Only allow dashes and underscores and one more character: the dot (because package names contain dots)
             */
            if (!\Controllers\Common::isAlphanumDash($packageName, array('.', '+', '~'))) {
                $uploadError++;
                $packageInvalidName[] = $packageName;
                continue;
            }

            /**
             *  If the package is a .deb package, check that it contains the architecture in its name
             */
            if ($myrepo->getPackageType() == 'deb') {
                if (!preg_match('/(amd64|arm64|armel|armhf|i386|mips|mips64el|mipsel|ppc64el|s390x|all).deb$/', $packageName)) {
                    throw new Exception('Package(s) name must contain the architecture in its name (e.g. package_amd64.deb).');
                }
            }

            /**
             *  If package is in error or not actually an uploaded file, then we ignore it and move on to the next one
             */
            if ($packageError != 0 || !is_uploaded_file($packageTmpName)) {
                $uploadError++;
                $packagesError[] = $packageName;
                continue;
            }

            /**
             *  If package size is equal to 0 then we ignore it and move on to the next one
             */
            if ($packageSize == 0) {
                $uploadError++;
                $packageEmpty[] = $packageName;
                continue;
            }

            /**
             *  For DEB, package will be uploaded to the pool/<section> directory
             */
            if ($myrepo->getPackageType() == 'deb') {
                $targetDir = $snapshotPath . '/pool/' . $myrepo->getSection();
            }

            /**
             *  For RPM, package will be uploaded to the correct architecture subfolder
             *  Try to determine package architecture to move it to the correct subfolder
             */
            if ($myrepo->getPackageType() == 'rpm') {
                foreach (RPM_ARCHS as $arch) {
                    if (preg_match("#\.$arch\.#", $packageName)) {
                        $targetDir = $snapshotPath . '/packages/' . $arch;

                        /**
                         *  If the architecture is not already in the list of architectures then we add it
                         */
                        if (!in_array($arch, $currentArchs)) {
                            $currentArchs[] = $arch;
                        }

                        break;
                    }
                }

                /**
                 *  If the package is a source package then move it to the SRPMS subfolder
                 */
                if (preg_match("#\.src\.#", $packageName)) {
                    $targetDir = $snapshotPath . '/packages/SRPMS';

                    /**
                     *  If the architecture is not already in the list of architectures then we add it
                     */
                    if (!in_array('SRPMS', $currentArchs)) {
                        $currentArchs[] = 'SRPMS';
                    }
                }

                /**
                 *  If no architecture has been found then we set it to 'noarch'
                 */
                if (empty($targetDir)) {
                    $targetDir = $snapshotPath . '/packages/noarch';

                    /**
                     *  If the architecture is not already in the list of architectures then we add it
                     */
                    if (!in_array('noarch', $currentArchs)) {
                        $currentArchs[] = 'noarch';
                    }
                }
            }

            /**
             *  Check that the package does not already exist, otherwise we ignore it and add it to a list of packages that already exist that we will display afterwards
             */
            if (file_exists($targetDir . '/' . $packageName)) {
                $uploadError++;
                $packageExists[] = $packageName;
                continue;
            }

            /**
             *  Check that the file has a valid mime type and said mime type matches the repo type
             */
            if (!($packageType == 'application/x-rpm' && $myrepo->getPackageType() == 'rpm') &&
                !($packageType == 'application/vnd.debian.binary-package' && $myrepo->getPackageType() == 'deb')) {
                $uploadError++;
                $packageInvalid[] = $packageName;
            }

            /**
             *  If there has been no error so far, then we can move the file to its final location
             */
            if ($uploadError == 0 and file_exists($packageTmpName)) {
                /**
                 *  Create the target dir
                 */
                if (!is_dir($targetDir)) {
                    if (!mkdir($targetDir, 0770, true)) {
                        throw new Exception('Error: cannot create upload directory <b>' . $targetDir . '</b>');
                    }
                }

                move_uploaded_file($packageTmpName, $targetDir . '/' . $packageName);
            }
        }

        /**
         *  If there was error during upload then we throw an exception
         */
        if ($uploadError != 0) {
            $errorMessage = '';
            if (!empty($packageInvalidName)) {
                $errorMessage .= '<br>Following packages have invalid name and have not been uploaded:';
                foreach ($packageInvalidName as $package) {
                    $errorMessage .= '<br><b>' . $package . '</b>';
                }
            }

            if (!empty($packageInvalid)) {
                $errorMessage .= '<br>Following files are not considered valid packages and have not been uploaded:';
                foreach ($packageInvalid as $package) {
                    $errorMessage .= '<br><b>' . $package . '</b>';
                }
            }

            if (!empty($packagesError)) {
                $errorMessage .= '<br>Following packages encountered error and have not been uploaded:';
                foreach ($packagesError as $package) {
                    $errorMessage .= '<br><b>' . $package . '</b>';
                }
            }

            if (!empty($packageEmpty)) {
                $errorMessage .= '<br>Following packages are empty and have not been uploaded:';
                foreach ($packageEmpty as $package) {
                    $errorMessage .= '<br><b>' . $package . '</b>';
                }
            }

            if (!empty($packageExists)) {
                $errorMessage .= '<br>Following packages already exist and have not been uploaded:';
                foreach ($packageExists as $package) {
                    $errorMessage .= '<br><b>' . $package . '</b>';
                }
            }

            throw new Exception($errorMessage);
        }

        /**
         *  Set repo rebuild status to 'needed'
         */
        $myrepo->snapSetRebuild($snapId, 'needed');

        /**
         *  Set new repo architectures
         */
        $myrepo->snapSetArch($snapId, $currentArchs);
    }

    /**
     *  Delete packages from repo
     */
    public function delete(int $snapId, array $packages)
    {
        /**
         *  If the user is not an administrator and does not have permission to delete packages, prevent access to this action.
         */
        if (!IS_ADMIN and !in_array('delete-package', USER_PERMISSIONS['repositories']['allowed-actions']['repos'])) {
            throw new Exception('You are not allowed to delete packages.');
        }

        $myrepo = new \Controllers\Repo\Repo();
        $deletedPackages = array();

        /**
         *  Retrieve repo infos from DB
         */
        $repoDetails = $myrepo->getAllById('', $snapId, '');

        /**
         *  Define snapshot path
         */
        if ($myrepo->getPackageType() == 'rpm') {
            $snapshotPath = REPOS_DIR .'/rpm/'. $myrepo->getName() . '/' . $myrepo->getReleasever() . '/' . $myrepo->getDate();
        }
        if ($myrepo->getPackageType() == 'deb') {
            $snapshotPath = REPOS_DIR .'/deb/'. $myrepo->getName() . '/'. $myrepo->getDist() . '/' . $myrepo->getSection() . '/' . $myrepo->getDate();
        }

        /**
         *  If the path does not exist on the server then we quit
         */
        if (!is_dir($snapshotPath)) {
            throw new Exception('Repo directory ' . $snapshotPath . ' does not exist.');
        }

        foreach ($packages as $package) {
            $name = \Controllers\Common::validateData($package);
            $path = REPOS_DIR . '/' . $name;

            /**
             *  Package name must not contain special characters
             *  Only allow dashes and underscores and one more character: the dot (because package names contain dots)
             *  Also allow slash because the path of the file also contains the subfolders to the package from the root of the repo
             */
            if (!\Controllers\Common::isAlphanumDash($name, array('.', '/', '+', '~'))) {
                continue;
            }

            /**
             *  Check that the file path starts with REPOS_DIR
             *  Prevents a malicious person from providing a path that has nothing to do with the repo directory (e.g. /etc/...)
             */
            if (!preg_match("#^" . REPOS_DIR . "#", realpath($path))) {
                throw new Exception('Invalid package path ' . $path);
            }

            /**
             *  Check that the file ends with .deb or .rpm otherwise we move on to the next one
             */
            if (!preg_match("#.deb$#", $path) and !preg_match("#.rpm$#", $path)) {
                continue;
            }

            /**
             *  If the file does not exist, we ignore it and move on to the next one
             */
            if (!file_exists($path)) {
                continue;
            }

            /**
             *  Delete package
             */
            if (!unlink($path)) {
                throw new Exception('Unable to delete package ' . $path);
            }

            $deletedPackages[] = str_replace($snapshotPath . '/', '', $path);

            /**
             *  Set repo rebuild status to 'needed'
             */
            $myrepo->snapSetRebuild($snapId, 'needed');
        }

        return $deletedPackages;
    }
}
