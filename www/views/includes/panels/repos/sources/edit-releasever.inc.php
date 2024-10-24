<?php ob_start(); ?>

<div class="slide-panel-container" slide-panel="repos/sources/edit-releasever">
    <div class="slide-panel">
        <img src="/assets/icons/close.svg" class="slide-panel-close-btn float-right lowopacity" slide-panel="repos/sources/edit-releasever" title="Close" />

        <div class="slide-panel-reloadable-div" slide-panel="repos/sources/edit-releasever">
            <h3>EDIT RELEASE VERSION <?= strtoupper($releasever) ?></h3>

            <form class="source-repo-edit-releasever" source-id="<?= $sourceId ?>" releasever-id="<?= $releaseverId ?>">
                <h6>NAME</h6>
                <input type="text" class="releasever-param" param-name="name" value="<?= $releasever ?>" placeholder="Name" />

                <h6>DESCRIPTION</h6>
                <input type="text" class="releasever-param" param-name="description" value="<?= $description ?>" placeholder="Name" />

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

                    <div class="table-container grid-2 bck-blue-alt pointer" source-id="<?= $sourceId ?>" releasever-id="<?= $releaseverId ?>">
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

                            <img src="/assets/icons/delete.svg" class="icon-lowopacity source-repo-edit-releasever-remove-gpgkey-btn" source-id="<?= $sourceId ?>" releasever-id="<?= $releaseverId ?>" gpgkey-id="<?= $gpgKeyId ?>" title="Remove GPG key <?= $gpgKey ?>" />
                        </div>
                    </div>
                    <?php
                endforeach ?>

                <div class="flex align-item-center column-gap-5">
                    <input type="text" class="source-repo-edit-releasever-add-gpgkey-input" source-id="<?= $sourceId ?>" releasever-id="<?= $releaseverId ?>" placeholder="Add GPG key">
                    <button type="button" class="source-repo-edit-releasever-add-gpgkey-btn btn-xxsmall-green" source-id="<?= $sourceId ?>" releasever-id="<?= $releaseverId ?>" title="Add GPG key">+</button>
                </div>
                <p class="note">HTTP link or fingerprint.</p>

                <br><br>
                <button type="submit" class="btn-medium-green">Save</button>
            </form>
        </div>
    </div>
</div>
