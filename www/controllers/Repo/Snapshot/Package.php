<?php

namespace Controllers\Repo\Snapshot;

use Controllers\User\Permission\Repo as RepoPermission;
use Controllers\Repo\Snapshot\Snapshot;
use Controllers\Exception\AppException;
use Controllers\Filesystem\File;
use Controllers\Utils\Validate;
use Controllers\Utils\Convert;
use Controllers\Repo\Repo;
use Exception;

class Package
{
    private $snapId;
    private $rootUrl;
    private $snapshotPath;
    private $repoController;
    private $repoSnapshotController;

    public function __construct(int $snapId)
    {
        $this->repoController = new Repo();
        $this->repoSnapshotController = new Snapshot();

        $this->snapId = $snapId;

        // Check that snapshot exists
        if (!$this->repoSnapshotController->exists($this->snapId)) {
            throw new Exception('Unknow snapshot Id ' . $this->snapId);
        }

        // Get repository information
        $this->repoController->getAllById('', $this->snapId, '');

        // Define snapshot path
        if ($this->repoController->getPackageType() == 'rpm') {
            $this->snapshotPath = REPOS_DIR .'/rpm/'. $this->repoController->getName() . '/' . $this->repoController->getReleasever() . '/' . $this->repoController->getDate();
            $this->rootUrl = WWW_REPOS_DIR_URL . '/rpm/' . $this->repoController->getName() . '/' . $this->repoController->getReleasever() . '/' . $this->repoController->getDate();
        }
        if ($this->repoController->getPackageType() == 'deb') {
            $this->snapshotPath = REPOS_DIR .'/deb/'. $this->repoController->getName() . '/'. $this->repoController->getDist() . '/' . $this->repoController->getSection() . '/' . $this->repoController->getDate();
            $this->rootUrl = WWW_REPOS_DIR_URL . '/deb/' . $this->repoController->getName() . '/' . $this->repoController->getDist() . '/' . $this->repoController->getSection() . '/' . $this->repoController->getDate();
        }

        // Check that the snapshot path exists
        if (!is_dir($this->snapshotPath)) {
            throw new Exception('Repository root directory ' . $this->snapshotPath . ' does not exist.');
        }
    }

    /**
     *  List all packages of a snapshot
     */
    public function list(): array
    {
        $packages = [];

        // If the user does not have permission to list packages, prevent access to this action.
        if (!RepoPermission::allowedAction('browse')) {
            throw new Exception('You are not allowed to list packages (browse repository)');
        }

        // Scan snapshot directory recursively to find all packages
        $scan = File::findRecursive($this->snapshotPath, [], ['deb', 'rpm']);

        // Extract package name from path and add it to the list of packages
        foreach ($scan as $path) {
            $package = end(explode('/', $path));
            $size = filesize($path);

            $packages[] = [
                'name' => $package,
                'size' => $size,
                'size-human' => Convert::sizeToHuman($size),
                'relative-path' => str_replace($this->snapshotPath . '/', '', $path),
                'absolute-path' => $path,
                'download-url' => $this->rootUrl . '/' . str_replace($this->snapshotPath . '/', '', $path)
            ];
        }

        // Sort packages by name
        usort($packages, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return $packages;
    }

    /**
     *  Upload package to snapshot
     */
    public function upload(array $packages, bool $overwrite = false, bool $ignoreIfExists = false): array
    {
        $packageInvalidName = []; // will contain the list of packages whose name is invalid
        $packageInvalid     = []; // will contain the list of packages whose format is invalid
        $packageExists      = []; // will contain the list of packages that already exist
        $packageError       = []; // will contain the list of packages uploaded with an error
        $packageEmpty       = []; // will contain the list of empty packages
        $packageUploaded    = []; // will contain the list of packages uploaded successfully
        $packageIgnored     = []; // will contain the list of packages ignored because they already exist (ignoreIfExists)
        $return             = []; // will contain the list of messages to return to the user at the end of the upload process

        // If the user does not have permission to upload packages, prevent access to this action.
        if (!RepoPermission::allowedAction('upload-package')) {
            throw new Exception('You are not allowed to upload packages');
        }

        // Both overwrite and ignoreIfExists cannot be true at the same time
        if ($overwrite && $ignoreIfExists) {
            throw new Exception('Options overwrite and ignore-if-exists are mutually exclusive');
        }

        /**
         *  Retrieve current repo architectures
         *  We will need this to update the repo architectures list if new packages are uploaded
         */
        $currentArchs = $this->repoController->getArch();

        // Check each file size to make sure it is not empty
        foreach ($packages as $package) {
            if ($package['size'] == 0) {
                throw new Exception('You must upload a file.');
            }
        }

        foreach ($packages as $package) {
            $uploadError    = 0;
            $packageType    = mime_content_type($package['tmp_name']);

            /**
             *  Package name must not contain special characters
             *  Only allow dashes and underscores and one more character: the dot (because package names contain dots)
             */
            if (!Validate::alphaNumericHyphen($package['name'], ['.', '+', '~'])) {
                $uploadError++;
                $packageInvalidName[] = $package['name'];
                continue;
            }

            // If the package is a .deb package, check that it contains the architecture in its name
            if ($this->repoController->getPackageType() == 'deb') {
                if (!preg_match('/(amd64|arm64|armel|armhf|i386|mips|mips64el|mipsel|ppc64el|s390x|all).deb$/', $package['name'])) {
                    throw new Exception('Package(s) name must contain the architecture in its name (e.g. package_amd64.deb).');
                }
            }

            // If package is in error or not actually an uploaded file, then we ignore it and move on to the next one
            if ($package['error'] != 0 || !is_uploaded_file($package['tmp_name'])) {
                $uploadError++;
                $packageError[] = $package['name'];
                continue;
            }

            // If package size is equal to 0 then we ignore it and move on to the next one
            if ($package['size'] == 0) {
                $uploadError++;
                $packageEmpty[] = $package['name'];
                continue;
            }

            // For DEB, package will be uploaded to the pool/<section> directory
            if ($this->repoController->getPackageType() == 'deb') {
                $targetDir = $this->snapshotPath . '/pool/' . $this->repoController->getSection();
            }

            /**
             *  For RPM, package will be uploaded to the correct architecture subfolder
             *  Try to determine package architecture to move it to the correct subfolder
             */
            if ($this->repoController->getPackageType() == 'rpm') {
                foreach (RPM_ARCHS as $arch) {
                    if (preg_match("#\.$arch\.#", $package['name'])) {
                        $targetDir = $this->snapshotPath . '/packages/' . $arch;

                        // If the architecture is not already in the list of architectures then we add it
                        if (!in_array($arch, $currentArchs)) {
                            $currentArchs[] = $arch;
                        }

                        break;
                    }
                }

                // If the package is a source package then move it to the SRPMS subfolder
                if (preg_match("#\.src\.#", $package['name'])) {
                    $targetDir = $this->snapshotPath . '/packages/SRPMS';

                    // If the architecture is not already in the list of architectures then we add it
                    if (!in_array('SRPMS', $currentArchs)) {
                        $currentArchs[] = 'SRPMS';
                    }
                }

                // If no architecture has been found then we set it to 'noarch'
                if (empty($targetDir)) {
                    $targetDir = $this->snapshotPath . '/packages/noarch';

                    // If the architecture is not already in the list of architectures then we add it
                    if (!in_array('noarch', $currentArchs)) {
                        $currentArchs[] = 'noarch';
                    }
                }
            }

            // Check that the package does not already exist, otherwise we ignore it and add it to a list of packages that already exist that we will display afterwards
            if (file_exists($targetDir . '/' . $package['name'])) {
                // If ignoreIfExists is enabled, skip the package and track it
                if ($ignoreIfExists) {
                    $packageIgnored[] = $package['name'];
                    continue;
                }

                // If overwrite is disabled, skip the package and add it to the list of packages that already exist
                if (!$overwrite) {
                    $uploadError++;
                    $packageExists[] = $package['name'];
                    continue;
                }

                // If overwrite is allowed, delete the existing package
                unlink($targetDir . '/' . $package['name']);
            }

            // Check that the file has a valid mime type and said mime type matches the repo type
            if (!($packageType == 'application/x-rpm' && $this->repoController->getPackageType() == 'rpm') &&
                !($packageType == 'application/vnd.debian.binary-package' && $this->repoController->getPackageType() == 'deb')) {
                $uploadError++;
                $packageInvalid[] = $package['name'];
            }

            // If there has been no error so far, then we can move the file to its final location
            if ($uploadError == 0 and file_exists($package['tmp_name'])) {
                // Create the target dir
                if (!is_dir($targetDir)) {
                    if (!mkdir($targetDir, 0770, true)) {
                        throw new Exception('Error: cannot create upload directory ' . $targetDir);
                    }
                }

                if (!move_uploaded_file($package['tmp_name'], $targetDir . '/' . $package['name'])) {
                    $uploadError++;
                    $packageError[] = $package['name'];
                    continue;
                }

                // If the package has been moved successfully, we add it to the list of uploaded packages
                $packageUploaded[] = $package['name'];
            }
        }

        // If there was error during upload then we throw an exception
        if ($uploadError != 0) {
            $errorMessage = [];

            if (!empty($packageInvalidName)) {
                $errorMessage['Following packages have invalid name and have not been uploaded'] = $packageInvalidName;
            }

            if (!empty($packageInvalid)) {
                $errorMessage['Following files are not considered valid packages and have not been uploaded'] = $packageInvalid;
            }

            if (!empty($packageError)) {
                $errorMessage['Following packages encountered error and have not been uploaded'] = $packageError;
            }

            if (!empty($packageEmpty)) {
                $errorMessage['Following packages are empty and have not been uploaded'] = $packageEmpty;
            }

            if (!empty($packageExists)) {
                $errorMessage['Following packages already exist and have not been uploaded'] = $packageExists;
            }

            if (!empty($packageIgnored)) {
                $errorMessage['Following packages already exist and have been ignored'] = $packageIgnored;
            }

            throw new AppException($errorMessage);
        }

        // Set repo rebuild status to 'needed'
        $this->repoSnapshotController->updateRebuild($this->snapId, 'needed');

        // Set new repo architectures
        $this->repoSnapshotController->updateArch($this->snapId, $currentArchs);

        if (!empty($packageUploaded)) {
            $return['Following packages have been uploaded successfully'] = $packageUploaded;
        }
        if (!empty($packageIgnored)) {
            $return['Following packages already exist and have been ignored'] = $packageIgnored;
        }

        return $return;
    }

    /**
     *  Delete files from snapshot
     */
    public function delete(array $files): array
    {
        $deleted = [];

        // If the user does not have permission to delete packages, prevent access to this action.
        if (!RepoPermission::allowedAction('delete-package')) {
            throw new Exception('You are not allowed to delete packages.');
        }

        foreach ($files as $file) {
            // Filename must not contain special characters
            // Only allow dashes and underscores and one more character: the dot (because package names contain dots)
            // Also allow slash because the path of the file also contains the subfolders to the package from the root of the repo
            if (!Validate::alphaNumericHyphen($file, ['.', '/', '+', '~'])) {
                continue;
            }

            // Check that the file path starts with REPOS_DIR
            // Prevents a malicious person from providing a path that has nothing to do with the repo directory (e.g. /etc/...)
            if (!preg_match("#^" . REPOS_DIR . "#", realpath($file))) {
                throw new Exception('Invalid file path ' . $file);
            }

            // If the file does not exist, we ignore it and move on to the next one
            if (!file_exists($file)) {
                throw new Exception('File ' . $file . ' does not exist');
            }

            // Delete file
            if (!unlink($file)) {
                throw new Exception('Unable to delete file ' . $file);
            }

            $deleted[] = [
                'name' => end(explode('/', $file)),
                'path' => $file
            ];

            // Set repo rebuild status to 'needed'
            $this->repoSnapshotController->updateRebuild($this->snapId, 'needed');
        }

        if (empty($deleted)) {
            throw new Exception('Nothing to delete');
        }

        return $deleted;
    }

    /**
     *  Delete files from snapshot by their name
     */
    public function deleteByName(array $files): array
    {
        $toDelete = [];

        // Recursively scan snapshot directory to find the file with the provided name and add it to the list of files to delete
        $toDelete = File::findRecursive($this->snapshotPath, $files);

        return $this->delete($toDelete);
    }
}
