<?php ob_start(); ?>

<div class="slide-panel-container" slide-panel="repos/sources/edit-distribution">
    <div class="slide-panel">
        <img src="/assets/icons/close.svg" class="slide-panel-close-btn float-right lowopacity" slide-panel="repos/sources/edit-distribution" title="Close" />

        <div class="slide-panel-reloadable-div" slide-panel="repos/sources/edit-distribution">
            <h3>EDIT DISTRIBUTION <?= strtoupper($distribution) ?></h3>

            <form class="source-repo-edit-distribution" source-id="<?= $sourceId ?>" distribution-id="<?= $distributionId ?>">
                <h6>NAME</h6>
                <input type="text" class="distribution-param" param-name="name" value="<?= $distribution ?>" placeholder="Name" />

                <h6>DESCRIPTION</h6>
                <input type="text" class="distribution-param" param-name="description" value="<?= $description ?>" placeholder="Name" />

                <h6>SECTIONS / COMPONENTS</h6>
                <?php
                if (empty($sections)) {
                    echo '<p class="note">No sections.</p>';
                }

                foreach ($sections as $sectionId => $sectionDefinition) : ?>
                    <div class="table-container grid-2 bck-blue-alt pointer" source-id="<?= $sourceId ?>" distribution-id="<?= $distributionId ?>">
                        <div>
                            <p><?= $sectionDefinition['name'] ?></p>
                        </div>

                        <div class="flex justify-end">
                            <img src="/assets/icons/delete.svg" class="icon-lowopacity source-repo-edit-distribution-remove-section-btn" source-id="<?= $sourceId ?>" distribution-id="<?= $distributionId ?>" section-id="<?= $sectionId ?>" title="Delete <?= $sectionDefinition['name'] ?> section" />
                        </div>
                    </div>
                    <?php
                endforeach; ?>

                <h6>GPG KEYS</h6>
                <?php
                if (empty($gpgKeys)) {
                    echo '<p class="note">No GPG keys.</p>';
                }

                foreach ($gpgKeys as $gpgKey) : ?>
                    <div class="table-container grid-2 bck-blue-alt pointer" source-id="<?= $sourceId ?>" distribution-id="<?= $distributionId ?>">
                        <div>
                            <p><?= $gpgKey ?></p>
                        </div>

                        <div class="flex justify-end">
                            <img src="/assets/icons/delete.svg" class="icon-lowopacity source-repo-edit-distribution-remove-gpgkey-btn" source-id="<?= $sourceId ?>" distribution-id="<?= $distributionId ?>" gpgkey="<?= $gpgKey ?>" title="Remove GPG key <?= $gpgKey ?>" />
                        </div>
                    </div>
                    <?php
                endforeach; ?>

                <br><br>
                <button type="submit" class="btn-medium-green">Save</button>
            </form>
        </div>
    </div>
</div>
