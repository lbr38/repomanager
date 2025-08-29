<?php
$environmentController = new \Controllers\Environment();

/**
 *  Check that action and repos params have been sent
 */
if (empty($item['repos'])) {
    throw new Exception('Task repositories required');
}

/**
 *  Retrieve the repositories
 */
try {
    $repos = json_decode($item['repos'], true, 512, JSON_THROW_ON_ERROR);
} catch (Exception $e) {
    throw new Exception('Could not decode the repositories');
}

/**
 *  Prepare the commands output
 */
ob_start();

foreach ($repos as $repo) {
    $repoController = new \Controllers\Repo\Repo();

    /**
     *  Check that the Ids are numeric
     */
    if (!is_numeric($repo['repo-id'])) {
        throw new Exception('Repository Id #' . $repo['repo-id'] . ' is invalid');
    }
    if (!is_numeric($repo['snap-id'])) {
        throw new Exception('Snapshot Id #' . $repo['snap-id'] . ' is invalid');
    }

    /**
     *  Check that the Ids exist in the database
     */
    if (!$repoController->existsId($repo['repo-id'])) {
        throw new Exception('Repository Id #' . $repo['repo-id'] . ' does not exist');
    }
    if (!$repoController->existsSnapId($repo['snap-id'])) {
        throw new Exception('Snapshot Id #' . $repo['snap-id'] . ' does not exist');
    }

    /**
     *  Retrieve all repo data from the Ids
     */
    $repoController->getAllById($repo['repo-id'], $repo['snap-id']);

    /**
     *  Retrieve the package type of the repo
     */
    $packageType = $repoController->getPackageType();
    $packagesTypes[] = $packageType; ?>

    <br>
    <div class="flex align-item-center justify-space-between margin-left-5 margin-right-5">
        <div class="flex align-item-center">
            <?php
            if ($packageType == 'deb') {
                echo '<p class="label-white">' . $repoController->getName() . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection() . '</p>⸺';
            }
            if ($packageType == 'rpm') {
                echo '<p class="label-white">' . $repoController->getName() . ' ❯ ' . $repoController->getReleasever() . '</p>⸺';
            } ?>

            <p class="label-black"><?= $repoController->getDateFormatted() ?></p>
            <p class="repository-install-env"></p>

        </div>
        <span class="label-pkg-<?= $packageType ?>"><?= strtoupper($packageType) ?></span>
    </div>

    <pre class="repository-install-commands codeblock margin-top-10 margin-bottom-10 copy" url="<?= WWW_REPOS_DIR_URL ?>" hostname="<?= WWW_HOSTNAME ?>" prefix="<?= REPO_CONF_FILES_PREFIX ?>" package-type="<?= $packageType ?>" name="<?= $repoController->getName() ?>" dist="<?= $repoController->getDist() ?>" component="<?= $repoController->getSection() ?>" releasever="<?= $repoController->getReleasever() ?>"></pre>
    <?php
}

$commands = ob_get_clean();
