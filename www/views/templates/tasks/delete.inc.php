<?php
use \Controllers\Utils\Generate\Html\Label; ?>

<div id="task-details" class="div-generic-blue margin-bottom-15">
    <div class="grid grid-2 row-gap-10 column-gap-20">
        <div>
            <h6 class="margin-top-0">REPOSITORY</h6>
            <?php
            if ($repoController->getPackageType() == 'deb') {
                echo Label::white($repoController->getName() . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection());
            }
            if ($repoController->getPackageType() == 'rpm') {
                echo Label::white($repoController->getName() . ' ❯ ' . $repoController->getReleasever());
            } ?>
        </div>

        <div>
            <h6 class="margin-top-0">DATE</h6>
            <?= Label::white($repoController->getDateFormatted()) ?>
        </div>
    </div>
</div>
