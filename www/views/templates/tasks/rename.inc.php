<?php
use \Controllers\Utils\Generate\Html\Label; ?>

<div id="task-details" class="div-generic-blue margin-bottom-15">
    <div class="grid grid-2 row-gap-10 column-gap-20">
        <div>
            <h6 class="margin-top-0">REPOSITORY</h6>
            <?php
            if ($repoController->getPackageType() == 'rpm') {
                echo Label::white($rawParams['old-name'] . ' ❯ ' . $repoController->getReleasever());
            }
            if ($repoController->getPackageType() == 'deb') {
                echo Label::white($rawParams['old-name'] . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection());
            } ?>
        </div>

        <div>
            <h6 class="margin-top-0">RENAME TO</h6>
            <?php
            if ($repoController->getPackageType() == 'rpm') {
                echo Label::white($rawParams['name'] . ' ❯ ' . $repoController->getReleasever());
            }
            if ($repoController->getPackageType() == 'deb') {
                echo Label::white($rawParams['name'] . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection());
            } ?>
        </div>
    </div>
</div>