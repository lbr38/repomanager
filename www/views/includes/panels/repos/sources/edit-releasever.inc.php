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
                <input type="text" class="releasever-param" param-name="description" value="<?= $description ?>" placeholder="Description" />

                <br><br>
                <button type="submit" class="btn-medium-green">Save</button>
            </form>

            <h6>GPG KEYS</h6>
            <p class="note">The public key(s) used to verify the signature of the repository.</p>

            <?php
            foreach ($gpgKeys as $gpgKeyId => $gpgKeyDefinition) :
                $imported = false;
                $gpgKeyName = 'Unknown key, try to reimport it.';

                if (isset($gpgKeyDefinition['fingerprint'])) {
                    $gpgKey = $gpgKeyDefinition['fingerprint'];
                } ?>

                <div class="table-container grid-2 bck-blue-alt pointer" source-id="<?= $sourceId ?>" releasever-id="<?= $releaseverId ?>">
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

                        <img src="/assets/icons/delete.svg" class="icon-lowopacity source-repo-edit-releasever-remove-gpgkey-btn" source-id="<?= $sourceId ?>" releasever-id="<?= $releaseverId ?>" gpgkey-id="<?= $gpgKeyId ?>" title="Remove GPG key <?= $gpgKey ?>" />
                    </div>
                </div>
                <?php
            endforeach ?>

            <h6>IMPORT GPG KEY</h6>
            <p class="note">Import a GPG key and link it to the <?= $releasever ?> release version.</p>

            <form class="source-repo-edit-releasever-add-gpgkey-form" source-id="<?= $sourceId ?>" releasever-id="<?= $releaseverId ?>">
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
