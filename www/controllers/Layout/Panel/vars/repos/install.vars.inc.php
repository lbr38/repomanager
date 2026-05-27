<?php
use Controllers\Repo\Repo;
use Controllers\Environment;

$environmentController = new Environment();

// Check that action and repos params have been sent
if (empty($item['repos'])) {
    throw new Exception('Task repositories required');
}

// Retrieve the repositories
try {
    $repos = json_decode($item['repos'], true, 512, JSON_THROW_ON_ERROR);
} catch (Exception $e) {
    throw new Exception('Could not decode the repositories');
}

// Prepare the commands output
ob_start();

foreach ($repos as $repo) {
    $repoController = new Repo();

    // Check that the Ids are numeric
    if (!is_numeric($repo['repo-id'])) {
        throw new Exception('Repository Id #' . $repo['repo-id'] . ' is invalid');
    }
    if (!is_numeric($repo['snap-id'])) {
        throw new Exception('Snapshot Id #' . $repo['snap-id'] . ' is invalid');
    }

    // Check that the Ids exist in the database
    if (!$repoController->existsId($repo['repo-id'])) {
        throw new Exception('Repository Id #' . $repo['repo-id'] . ' does not exist');
    }
    if (!$repoController->existsSnapId($repo['snap-id'])) {
        throw new Exception('Snapshot Id #' . $repo['snap-id'] . ' does not exist');
    }

    // Retrieve all repo data from the Ids
    $repoController->getAllById($repo['repo-id'], $repo['snap-id']);

    // Retrieve the package type of the repo
    $packageType = $repoController->getPackageType();
    $packagesTypes[] = $packageType; ?>

    <div class="form-block form-block-accent-<?= $packageType == 'deb' ? 'red' : 'blue' ?>">
    <div class="flex align-item-center justify-space-between">
        <div class="flex align-item-center">
            <?php
            if ($packageType == 'deb') {
                echo '<p class="label-white">' . $repoController->getName() . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection() . '</p>⸺';
                $labelColor = 'red';
            }
            if ($packageType == 'rpm') {
                echo '<p class="label-white">' . $repoController->getName() . ' ❯ ' . $repoController->getReleasever() . '</p>⸺';
                $labelColor = 'blue';
            } ?>

            <p class="label-white"><?= $repoController->getDateFormatted() ?></p>
            <p class="repository-install-env"></p>
        </div>
    </div>

    <h6>INSTALLATION</h6>
    <p class="note margin-bottom-5">Copy and paste the following commands in the shell of the target host.</p>

    <?php
    if ($packageType == 'deb') :
        // Get snapshot Id as a random string to use it as a unique identifier for the radio buttons
        $snapId = $repoController->getSnapId(); ?>

        <div class="switch-field margin-bottom-10">
            <input type="radio" id="<?= $snapId ?>-legacy" class="config-format" name="<?= $snapId ?>-config-format" snapshot-id="<?= $snapId ?>" value="legacy" checked />
            <label for="<?= $snapId ?>-legacy">Legacy format</label>
            <input type="radio" id="<?= $snapId ?>-deb822" class="config-format" name="<?= $snapId ?>-config-format" snapshot-id="<?= $snapId ?>" value="deb822" />
            <label for="<?= $snapId ?>-deb822">deb822 format</label>
        </div>

        <div class="legacy-format-container" snapshot-id="<?= $snapId ?>">
            <pre class="codeblock margin-top-10 margin-bottom-10 copy">curl -sS <?= WWW_REPOS_DIR_URL ?>/gpgkeys/<?= WWW_HOSTNAME ?>.pub | gpg --dearmor > /etc/apt/trusted.gpg.d/<?= WWW_HOSTNAME ?>.gpg</pre>
            <pre class="repository-install-commands codeblock margin-top-10 margin-bottom-10 copy" url="<?= WWW_REPOS_DIR_URL ?>" hostname="<?= WWW_HOSTNAME ?>" prefix="<?= REPO_CONF_FILES_PREFIX ?>" package-type="deb" name="<?= $repoController->getName() ?>" dist="<?= $repoController->getDist() ?>" component="<?= $repoController->getSection() ?>"></pre>
        </div>

        <div class="deb822-format-container hide" snapshot-id="<?= $snapId ?>">
            <pre class="codeblock margin-top-10 margin-bottom-10 copy">curl -sS <?= WWW_REPOS_DIR_URL ?>/gpgkeys/<?= WWW_HOSTNAME ?>.pub | gpg --dearmor > /etc/apt/keyrings/<?= WWW_HOSTNAME ?>.asc</pre>
            <pre class="repository-install-commands codeblock margin-top-10 margin-bottom-10 copy" url="<?= WWW_REPOS_DIR_URL ?>" hostname="<?= WWW_HOSTNAME ?>" prefix="<?= REPO_CONF_FILES_PREFIX ?>" package-type="deb-alt" name="<?= $repoController->getName() ?>" dist="<?= $repoController->getDist() ?>" component="<?= $repoController->getSection() ?>"></pre>
        </div>
        <?php
    endif;

    if ($packageType == 'rpm') : ?>
        <pre class="repository-install-commands codeblock margin-top-10 margin-bottom-10 copy" url="<?= WWW_REPOS_DIR_URL ?>" hostname="<?= WWW_HOSTNAME ?>" prefix="<?= REPO_CONF_FILES_PREFIX ?>" package-type="rpm" name="<?= $repoController->getName() ?>" releasever="<?= $repoController->getReleasever() ?>"></pre>
        <?php
    endif; ?>

    </div>
    <?php
}

$commands = ob_get_clean();