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
                <?php
                foreach ($gpgKeys as $gpgKeyId => $gpgKeyDefinition) :
                    if (isset($gpgKeyDefinition['link'])) {
                        $gpgKey = $gpgKeyDefinition['link'];
                    }
                    if (isset($gpgKeyDefinition['fingerprint'])) {
                        $gpgKey = $gpgKeyDefinition['fingerprint'];
                    } ?>

                    <div class="table-container grid-2 bck-blue-alt pointer" source-id="<?= $sourceId ?>" releasever-id="<?= $releaseverId ?>">
                        <div>
                            <p><?= $gpgKey ?></p>
                        </div>

                        <div class="flex justify-end">
                            <img src="/assets/icons/delete.svg" class="icon-lowopacity source-repo-edit-releasever-remove-gpgkey-btn" source-id="<?= $sourceId ?>" releasever-id="<?= $releaseverId ?>" gpgkey-id="<?= $gpgKeyId ?>" title="Remove GPG key <?= $gpgKey ?>" />
                        </div>
                    </div>
                    <?php
                endforeach ?>

                <div class="flex align-item-center column-gap-5">
                    <input type="text" class="source-repo-edit-releasever-add-gpgkey-input" source-id="<?= $sourceId ?>" releasever-id="<?= $releaseverId ?>" placeholder="Add GPG key">
                    <button type="button" class="source-repo-edit-releasever-add-gpgkey-btn btn-xxsmall-green" source-id="<?= $sourceId ?>" releasever-id="<?= $releaseverId ?>" title="Add GPG key">+</button>
                </div>
                <p class="note">http(s):// link or fingerprint.</p>

                <br><br>
                <button type="submit" class="btn-medium-green">Save</button>
            </form>
        </div>
    </div>
</div>
