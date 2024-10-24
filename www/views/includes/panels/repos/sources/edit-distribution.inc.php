<?php ob_start(); ?>

<div class="slide-panel-container" slide-panel="repos/sources/edit-distribution">
    <div class="slide-panel">
        <img src="/assets/icons/close.svg" class="slide-panel-close-btn float-right lowopacity" slide-panel="repos/sources/edit-distribution" title="Close" />

        <div class="slide-panel-reloadable-div" slide-panel="repos/sources/edit-distribution">
            <h3>EDIT <?= strtoupper($distribution) ?> DISTRIBUTION</h3>

            <form class="source-repo-edit-distribution" source-id="<?= $sourceId ?>" distribution-id="<?= $distributionId ?>">
                <h6>NAME</h6>
                <input type="text" class="distribution-param" param-name="name" value="<?= $distribution ?>" placeholder="Name" />

                <h6>DESCRIPTION</h6>
                <input type="text" class="distribution-param" param-name="description" value="<?= $description ?>" placeholder="Name" />

                <h6>SECTIONS / COMPONENTS</h6>
                <p class="note">The components of the <?= $distribution ?> distribution.</p>

                <?php
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

                <div class="flex align-item-center column-gap-5">
                    <input type="text" class="source-repo-edit-distribution-add-section-input" source-id="<?= $sourceId ?>" distribution-id="<?= $distributionId ?>" placeholder="Add section">
                    <button type="button" class="source-repo-edit-distribution-add-section-btn btn-xxsmall-green" source-id="<?= $sourceId ?>" distribution-id="<?= $distributionId ?>" title="Add section">+</button>
                </div>

                <h6>GPG KEYS</h6>
                <p class="note">The public key(s) used to verify the signature of the repository.</p>
                
                <?php
                foreach ($gpgKeys as $gpgKeyId => $gpgKeyDefinition) :
                    $imported = false;
                    $gpgKeyName = null;
                    $gpgType = null;

                    if (isset($gpgKeyDefinition['link'])) {
                        $gpgType = 'link';
                        $gpgKey = $gpgKeyDefinition['link'];
                    }
                    if (isset($gpgKeyDefinition['fingerprint'])) {
                        $gpgType = 'fingerprint';
                        $gpgKey = $gpgKeyDefinition['fingerprint'];
                    } ?>

                    <div class="table-container grid-2 bck-blue-alt pointer" source-id="<?= $sourceId ?>" distribution-id="<?= $distributionId ?>">
                        <div>
                            <?php
                            /**
                             *  If the GPG key is in the trusted keyring, mark it as imported and retrieve its name
                             */
                            if ($gpgType == 'fingerprint' and in_array($gpgKey, array_column($trustedGpgKeys, 'id'))) {
                                $imported = true;
                                $gpgKeyName = $trustedGpgKeys[array_search($gpgKey, array_column($trustedGpgKeys, 'id'))]['name'];
                            }

                            if (!empty($gpgKeyName)) {
                                echo '<p>' . $gpgKeyName . '</p>';
                                echo '<p class="note">' . $gpgKey . '</p>';
                            } else {
                                echo '<p>' . $gpgKey . '</p>';
                            } ?>
                        </div>

                        <div class="flex justify-end column-gap-10">
                            <?php
                            if ($imported) {
                                echo '<img src="/assets/icons/check.svg" class="icon-np" title="Imported GPG key" />';
                            } ?>

                            <img src="/assets/icons/delete.svg" class="icon-lowopacity source-repo-edit-distribution-remove-gpgkey-btn" source-id="<?= $sourceId ?>" distribution-id="<?= $distributionId ?>" gpgkey-id="<?= $gpgKeyId ?>" title="Remove GPG key <?= $gpgKey ?>" />
                        </div>
                    </div>
                    <?php
                endforeach ?>

                <div class="flex align-item-center column-gap-5">
                    <input type="text" class="source-repo-edit-distribution-add-gpgkey-input" source-id="<?= $sourceId ?>" distribution-id="<?= $distributionId ?>" placeholder="Add GPG key">
                    <button type="button" class="source-repo-edit-distribution-add-gpgkey-btn btn-xxsmall-green" source-id="<?= $sourceId ?>" distribution-id="<?= $distributionId ?>" title="Add GPG key">+</button>
                </div>
                <p class="note">HTTP link or fingerprint.</p>

                <br><br>
                <button type="submit" class="btn-medium-green">Save</button>
            </form>
        </div>
    </div>
</div>
