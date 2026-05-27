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
                echo Label::white($repoController->getName());
            } ?>
        </div>

        <?php
        if ($repoController->getType() == 'mirror') : ?>
            <div>
                <h6 class="margin-top-0">SOURCE REPOSITORY</h6>
                <p class="copy"><?= Label::white($repoController->getSource()) ?></p>
            </div>
            <?php
        endif ?>
    </div>

    <?php
    if ($repoController->getPackageType() == 'rpm' and !empty($repoController->getReleasever())) : ?>
        <div class="grid grid-2 row-gap-10 column-gap-20">
            <div>
                <h6>RELEASE VERSION</h6>
                <p><?= $repoController->getReleasever() ?></p>
            </div>
        </div>
        <?php
    endif ?>

    <div class="grid grid-2 row-gap-10 column-gap-20">
        <?php
        if (!empty($repoController->getArch())) : ?>
            <div>
                <h6>ARCHITECTURE</h6>
                <div class="flex column-gap-5 row-gap-5 flex-wrap">
                    <?php
                    foreach ($repoController->getArch() as $arch) {
                        echo Label::white($arch);
                    } ?>
                </div>
            </div>

            <?php
        endif;

        if (!empty($rawParams['env'])) : ?>
            <div>
                <h6>ENVIRONMENT</h6>
                <div class="flex column-gap-5 row-gap-5 flex-wrap">
                    <?php
                    foreach ($rawParams['env'] as $env) {
                        echo Label::envtag($env);
                    } ?>
                </div>
            </div>
            <?php
        endif ?>
    </div>

    <div class="grid grid-2 row-gap-10 column-gap-20">
        <?php
        if ($repoController->getType() == 'mirror') :
            if (!empty($repoController->getAdvancedParams()['packages']['include'])) : ?>
                <div>
                    <h6>PACKAGES TO INCLUDE</h6>
                    <div class="flex column-gap-5 row-gap-5 flex-wrap">
                        <?php
                        foreach ($repoController->getAdvancedParams()['packages']['include'] as $package) {
                            echo Label::white($package);
                        } ?>
                    </div>
                </div>
                <?php
            endif;

            if (!empty($repoController->getAdvancedParams()['packages']['exclude'])) : ?>
                <div>
                    <h6>PACKAGES TO EXCLUDE</h6>
                    <div class="flex column-gap-5 row-gap-5 flex-wrap">
                        <?php
                        foreach ($repoController->getAdvancedParams()['packages']['exclude'] as $package) {
                            echo Label::white($package);
                        } ?>
                    </div>
                </div>
                <?php
            endif;
        endif ?>
    </div>

    <div class="grid grid-2 row-gap-10 column-gap-20">
        <?php
        if (!empty($rawParams['advanced-params']['packages']['include'])) : ?>
            <div>
                <h6>PACKAGES TO INCLUDE</h6>
                <div class="flex column-gap-5 row-gap-5 flex-wrap">
                    <?php
                    foreach ($rawParams['advanced-params']['packages']['include'] as $package) {
                        echo Label::white($package);
                    } ?>
                </div>
            </div>
            <?php
        endif;

        if (!empty($rawParams['advanced-params']['packages']['exclude'])) : ?>
            <div>
                <h6>PACKAGES TO EXCLUDE</h6>
                <div class="flex column-gap-5 row-gap-5 flex-wrap">
                    <?php
                    foreach ($rawParams['advanced-params']['packages']['exclude'] as $package) {
                        echo Label::white($package);
                    } ?>
                </div>
            </div>
            <?php
        endif ?>
    </div>

    <div class="grid grid-2 row-gap-10 column-gap-20">
        <?php
        if (!empty($rawParams['advanced-params']['packages']['keep-latest'])) : ?>
            <div>
                <h6>KEEP LATEST VERSIONS</h6>
                <p><?= $rawParams['advanced-params']['packages']['keep-latest'] ?></p>
            </div>
            <?php
        endif;

        // If there is at least one metadata file sync that is ignored (one false value in a key/value pair), we display the metadata sync section
        if (!empty($rawParams['advanced-params']['metadata-sync']) and in_array('false', $rawParams['advanced-params']['metadata-sync'])) : ?>
            <div>
                <h6>IGNORE METADATA FILES SYNC</h6>
                <div class="flex column-gap-5 row-gap-5 flex-wrap">
                    <?php
                    $metadataMap = [
                        'comps' => 'comps.xml',
                        'modules' => 'modules.yaml',
                        'updateinfo' => 'updateinfo.xml.gz'
                    ];

                    foreach ($rawParams['advanced-params']['metadata-sync'] as $metadata => $value) {
                        if ($value == 'true') {
                            continue;
                        }

                        $displayName = $metadataMap[$metadata] ?? $metadata;
                        echo Label::white($displayName);
                    } ?>
                </div>
            </div>
            <?php
        endif ?>
    </div>

    <div class="grid grid-2 row-gap-10 column-gap-20">
        <?php
        if ($repoController->getType() == 'mirror') :
            if (!empty($repoController->getGpgCheck())) : ?>
                <div>
                    <h6>CHECK GPG SIGNATURES</h6>
                    <?php
                    if ($repoController->getGpgCheck() == 'true') : ?>
                        <div class="flex column-gap-5">
                            <img src="/assets/icons/check.svg" class="icon" />
                            <span>Enabled</span>
                        </div>
                        <?php
                    endif;
                    if ($repoController->getGpgCheck() == 'false') : ?>
                        <div class="flex column-gap-5">
                            <img src="/assets/icons/error.svg" class="icon" />
                            <span>Disabled</span>
                        </div>
                        <?php
                    endif ?>
                </div>
                <?php
            endif;

            if (!empty($repoController->getGpgSign())) : ?>
                <div>
                    <h6>SIGN WITH GPG</h6>
                    <?php
                    if ($repoController->getGpgSign() == 'true') : ?>
                        <div class="flex column-gap-5">
                            <img src="/assets/icons/check.svg" class="icon" />
                            <span>Enabled</span>
                        </div>
                        <?php
                    endif;
                    if ($repoController->getGpgSign() == 'false') : ?>
                        <div class="flex column-gap-5">
                            <img src="/assets/icons/error.svg" class="icon" />
                            <span>Disabled</span>
                        </div>
                        <?php
                    endif ?>
                </div>
                <?php
            endif;
        endif ?>
    </div>

    <div class="grid grid-2 row-gap-10 column-gap-20">
        <?php
        if (!empty($repoController->getDescription())) : ?>
            <div>
                <h6>DESCRIPTION</h6>
                <p><?= $repoController->getDescription() ?></p>
            </div>
            <?php
        endif;

        if (!empty($repoController->getTags())) : ?>
            <div>
                <h6>TAGS</h6>
                <div class="flex column-gap-5 row-gap-5 flex-wrap">
                    <?php
                    foreach ($repoController->getTags() as $tag) {
                        echo Label::white($tag);
                    } ?>
                </div>
            </div>
            <?php
        endif ?>
    </div>

    <div class="grid grid-2 row-gap-10 column-gap-20">
        <?php
        if (!empty($repoController->getGroup())) : ?>
            <div>
                <h6>ADD TO GROUP</h6>
                <p><?= $repoController->getGroup() ?></p>
            </div>
            <?php
        endif ?>
    </div>
</div>
