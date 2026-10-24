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
                <input type="text" class="distribution-param" param-name="description" value="<?= $description ?>" placeholder="Description" />

                <h6>END OF LIFE DATE</h6>
                <p class="note">The end-of-life date for this distribution.</p>
                <input type="date" class="distribution-param" param-name="eol" value="<?= $eol ?>" placeholder="EOL Date" />

                <br><br>
                <button type="submit" class="btn-medium-green">Save</button>
            </form>

            <h6>COMPONENTS</h6>
            <p class="note">The components of the <?= $distribution ?> distribution.</p>

            <?php
            foreach ($components as $componentId => $componentDetails) : ?>
                <div class="table-container grid-2 bck-blue-alt source-repo-component-edit-btn pointer" source-id="<?= $sourceId ?>" distribution-id="<?= $distributionId ?>" component-id="<?= $componentId ?>">
                    <div>
                        <div>
                            <p><?= $componentDetails['name'] ?></p>
                            <p class="note"><?= $componentDetails['description'] ?? '' ?></p>
                        </div>

                        <?php
                        if (!empty($componentDetails['archs'])) : ?>
                            <div class="flex column-gap-5 row-gap-5 margin-top-10">
                                <?php
                                foreach ($componentDetails['archs'] as $arch) {
                                    echo '<p class="label-white">' . $arch . '</p>';
                                } ?>
                            </div>
                            <?php
                        endif ?>
                    </div>

                    <div class="flex justify-end">
                        <img src="/assets/icons/delete.svg" class="icon-lowopacity source-repo-remove-component-btn" source-id="<?= $sourceId ?>" distribution-id="<?= $distributionId ?>" component-id="<?= $componentId ?>" title="Delete <?= $componentDetails['name'] ?> component" />
                    </div>
                </div>
                <?php
            endforeach; ?>

            <div class="flex align-item-center column-gap-5">
                <input type="text" class="source-repo-edit-distribution-add-component-input" source-id="<?= $sourceId ?>" distribution-id="<?= $distributionId ?>" placeholder="Add component">
                <button type="button" class="source-repo-add-component-btn btn-xxsmall-green" source-id="<?= $sourceId ?>" distribution-id="<?= $distributionId ?>" title="Add component">+</button>
            </div>

            <h6>GPG KEYS</h6>
            <p class="note">The public key(s) used to verify the signature of the repository.</p>
            
            <?php
            foreach ($gpgKeys as $gpgKeyId => $gpgKeyDefinition) :
                $imported = false;
                $gpgKeyName = 'Unknown key, try to reimport it.';

                if (isset($gpgKeyDefinition['fingerprint'])) {
                    $gpgKey = $gpgKeyDefinition['fingerprint'];
                } ?>

                <div class="table-container grid-2 bck-blue-alt pointer" source-id="<?= $sourceId ?>" distribution-id="<?= $distributionId ?>">
                    <div>
                        <?php
                        /**
                         *  If the GPG key is in the trusted keyring, mark it as imported and retrieve its name
                         */
                        if (in_array($gpgKey, array_column($trustedGpgKeys, 'id'))) {
                            $imported = true;
                            $gpgKeyName = $trustedGpgKeys[array_search($gpgKey, array_column($trustedGpgKeys, 'id'))]['name'];
                        }

                        echo '<p>' . $gpgKeyName . '</p>';
                        echo '<p class="note">' . $gpgKey . '</p>'; ?>
                    </div>

                    <div class="flex justify-end column-gap-10">
                        <?php
                        if ($imported) {
                            echo '<img src="/assets/icons/check.svg" class="icon-np" title="Imported GPG key" />';
                        } else {
                            echo '<img src="/assets/icons/warning-red.svg" class="icon-np" title="Unknown GPG key" />';
                        } ?>

                        <img src="/assets/icons/delete.svg" class="icon-lowopacity source-repo-distribution-remove-gpgkey-btn" source-id="<?= $sourceId ?>" distribution-id="<?= $distributionId ?>" gpgkey-id="<?= $gpgKeyId ?>" title="Remove GPG key <?= $gpgKey ?>" />
                    </div>
                </div>
                <?php
            endforeach ?>

            <h6>IMPORT GPG KEY</h6>
            <p class="note">Import a GPG key and link it to the <?= $distribution ?> distribution.</p>

            <form class="source-repo-distribution-add-gpgkey" source-id="<?= $sourceId ?>" distribution-id="<?= $distributionId ?>">
                <h6>IMPORT FROM LINK</h6>
                <p class="note">URL to the key file.</p>
                <input type="text" name="gpgkey-url" placeholder="http://..." />

                <h6>IMPORT FROM FINGERPRINT</h6>
                <p class="note">Hexadecimal format.</p>
                <input type="text" name="gpgkey-fingerprint" placeholder="B8B80B5B623EAB6AD8775C45B7C5D7D6350947F8" />

                <h6>IMPORT FROM PLAIN TEXT</h6>
                <p class="note">ASCII armored format.</p>

                <textarea name="gpgkey-plaintext" class="textarea-100" placeholder="-----BEGIN PGP PUBLIC KEY BLOCK-----"></textarea>

                <br><br>
                <button type="submit" class="btn-medium-green">Import</button>
            </form>
        </div>
    </div>
</div>
