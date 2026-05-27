<?php
use \Controllers\Utils\Generate\Html\Label; ?>

<div id="task-details" class="div-generic-blue margin-bottom-15">
    <div class="grid grid-2 row-gap-10 column-gap-20">
        <div>
            <h6 class="margin-top-0">REPOSITORY</h6>
            <?php
            if ($repoController->getPackageType() == 'rpm') {
                echo Label::white($repoController->getName() . ' ❯ ' . $repoController->getReleasever());
            }
            if ($repoController->getPackageType() == 'deb') {
                echo Label::white($repoController->getName() . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection());
            } ?>
        </div>

        <div>
            <h6 class="margin-top-0">SNAPSHOT</h6>
            <?= Label::white($repoController->getDateFormatted()) ?>
        </div>
    </div>

    <div class="grid grid-2 row-gap-10 column-gap-20">
        <div>
            <h6>DUPLICATE TO</h6>
            <?php
            if ($repoController->getPackageType() == 'rpm') {
                echo Label::white($rawParams['name'] . ' ❯ ' . $repoController->getReleasever());
            }
            if ($repoController->getPackageType() == 'deb') {
                echo Label::white($rawParams['name'] . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection());
            } ?>
        </div>

        <?php
        if (!empty($rawParams['env'])) : ?>
            <div>
                <h6>POINT AN ENVIRONMENT</h6>
                <div class="flex column-gap-5 row-gap-5 flex-wrap">
                    <?php
                    foreach ($rawParams['env'] as $env) {
                        echo Label::envtag($env) . ' ';
                    } ?>
                </div>
            </div>
            <?php
        endif;

        if (!empty($rawParams['description'])) : ?>
            <div>
                <h6>DESCRIPTION</h6>
                <p><?= $rawParams['description'] ?></p>
            </div>
            <?php
        endif;

        if (!empty($rawParams['tags'])) : ?>
            <div>
                <h6>TAGS</h6>
                <div class="flex column-gap-5 row-gap-5 flex-wrap">
                    <?php
                    foreach ($rawParams['tags'] as $tag) {
                        echo Label::white($tag);
                    } ?>
                </div>
            </div>
            <?php
        endif;

        if (!empty($repoController->getGroup())) : ?>
            <div>
                <h6>ADD TO GROUP</h6>
                <p><?= $repoController->getGroup() ?></p>
            </div>
            <?php
        endif ?>
    </div>
</div>