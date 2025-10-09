<div class="reloadable-table" table="<?= $table ?>" offset="<?= $reloadableTableOffset ?>">   
    <h6>CURRENT SOURCE REPOSITORIES</h6>
    <p class="note">Source repositories to be mirrored.</p>

    <?php
    if (empty($reloadableTableContent)) {
        echo '<p class="note">Nothing for now!</p>';
    } ?>

    <div class="flex column-gap-10 margin-top-10 margin-bottom-15">
        <button type="button" class="btn-medium-blue get-panel-btn" panel="repos/sources/new">Manually add</button>
        <button type="button" class="btn-medium-blue get-panel-btn" panel="repos/sources/import">Import</button>
    </div>

    <?php
    if (!empty($reloadableTableContent)) : ?>
        <div class="flex justify-end margin-bottom-10 margin-right-15">
            <input type="checkbox" class="select-all-checkbox lowopacity" checkbox-id="source-repo" title="Select all source repositories" />
        </div>

        <?php
        foreach ($reloadableTableContent as $item) :
            $distAndComponent = null;
            $releasevers = null;
            $sslCertificate = '';
            $sslPrivateKey = '';
            $sslCaCertificate = '';

            // Decode JSON definition
            try {
                $definition = json_decode($item['Definition'], true);
            } catch (Exception $e) {
                echo '<p>Error: could not decode JSON definition for source repository #' . $item['Id'] . '</p>';
                continue;
            }

            $name = $definition['name'];
            $url  = $definition['url'];
            $type = $definition['type'];
            $description = $definition['description'];

            // Case it is a deb source repository
            if ($type == 'deb') {
                if (isset($definition['distributions'])) {
                    $distAndComponent = $definition['distributions'];
                }
            }

            // Case it is a rpm source repository
            if ($type == 'rpm') {
                if (isset($definition['releasever'])) {
                    $releasevers = $definition['releasever'];
                }
            }

            // SSL authentication
            if (isset($definition['ssl-authentication']['certificate'])) {
                $sslCertificate = $definition['ssl-authentication']['certificate'];
            }
            if (isset($definition['ssl-authentication']['private-key'])) {
                $sslPrivateKey = $definition['ssl-authentication']['private-key'];
            }
            if (isset($definition['ssl-authentication']['ca-certificate'])) {
                $sslCaCertificate = $definition['ssl-authentication']['ca-certificate'];
            } ?>

            <div class="table-container-3 bck-blue-alt pointer source-repo-edit-param-btn" source-id="<?= $item['Id'] ?>">
                <div>
                    <?php
                    if ($type == 'rpm') {
                        echo ' <span class="label-pkg-rpm">rpm</span>';
                    }
                    if ($type == 'deb') {
                        echo ' <span class="label-pkg-deb">deb</span>';
                    } ?>
                </div>

                <div>
                    <p><?= $name ?></p>
                    <?php
                    if (!empty($description)) {
                        echo '<p class="lowopacity-cst wordbreakall">' . $description . '</p>';
                    } else {
                        echo '<p class="lowopacity-cst wordbreakall">' . $url . '</p>';
                    } ?>
                </div>

                <div class="flex justify-end">
                    <div class="flex alig-item-center column-gap-10">
                        <?php
                        if ($item['Method'] == 'import-github') {
                            echo '<img src="/assets/icons/github.svg" class="icon-np" title="Imported from predefined list" />';
                        }
                        if ($item['Method'] == 'import-custom') {
                            echo '<img src="/assets/icons/user.svg" class="icon-np" title="Imported from user custom list" />';
                        }
                        if ($item['Method'] == 'import-api') {
                            echo '<img src="/assets/icons/user.svg" class="icon-np" title="Imported from user custom list (via API)" />';
                        }  ?>

                        <input type="checkbox" class="child-checkbox lowopacity" checkbox-id="source-repo" checkbox-data-attribute="source-id" source-id="<?= $item['Id'] ?>" title="Select source repository" />
                    </div>
                </div>
            </div>

            <div class="hide source-repo-param-div details-div margin-bottom-5" source-id="<?= $item['Id'] ?>">
                <form class="source-repo-form" source-id="<?= $item['Id'] ?>" autocomplete="off">
                    <input type="hidden" class="source-param" param-name="type" value="<?= $type ?>" />

                    <h6 class="required margin-top-0">NAME</h6>
                    <input type="text" class="source-param" param-name="name" value="<?= $name ?>" />

                    <h6 class="required">URL</h6>
                    <input type="text" class="source-param" param-name="url" value="<?= $url ?>" />

                    <?php
                    if ($type == 'deb') : ?>
                        <h6 class="required">NON-COMPLIANT REPOSITORY</h6>
                        <p class="note">The repository does not follow the standard Debian repository structure. <b><a href="https://github.com/lbr38/repomanager/wiki/05.-Manage-sources-repositories#non-compliant-deb-source-repositories" target="_blank" rel="noopener noreferrer" class="font-size-13">Learn more.</a></b></p>
                        <label class="onoff-switch-label">
                            <input type="checkbox" class="onoff-switch-input source-param" value="true" param-name="non-compliant" <?= isset($definition['non-compliant']) && $definition['non-compliant'] == 'true' ? 'checked' : '' ?> />
                            <span class="onoff-switch-slider"></span>
                        </label>
                        <?php
                    endif ?>

                    <h6>DESCRIPTION</h6>
                    <input type="text" class="source-param" param-name="description" value="<?= $description ?>" />

                    <?php
                    if ($type == 'deb') :
                        echo '<h6>DISTRIBUTIONS</h6>';
                        echo '<p class="note">Embedded distributions.</p>';

                        if (!empty($distAndComponent)) :
                            foreach ($distAndComponent as $distributionId => $distributionDetails) : ?>
                                <!-- Distributions -->
                                <div class="table-container grid-fr-4-1 bck-blue-alt source-repo-distribution-edit-param-btn pointer" source-id="<?= $item['Id'] ?>" distribution-id="<?= $distributionId ?>">
                                    <div>
                                        <p><?= $distributionDetails['name'] ?></p>
                                        <p class="note"><?= $distributionDetails['description'] ?></p>

                                        <div class="flex column-gap-5">
                                            <?php
                                            foreach ($distributionDetails['components'] as $componentId => $componentDetails) {
                                                echo '<p class="label-black">' . $componentDetails['name'] . '</p>';
                                            } ?>
                                        </div>
                                    </div>

                                    <div class="flex justify-end">
                                        <img src="/assets/icons/delete.svg" class="source-repo-remove-distribution-btn icon-lowopacity" source-id="<?= $item['Id'] ?>" distribution-id="<?= $distributionId ?>" title="Remove <?= $distributionDetails['name'] ?> distribution" />
                                    </div>
                                </div>
                                <?php
                            endforeach;
                        endif ?>

                        <div class="flex align-item-center column-gap-5">
                            <input type="text" class="source-repo-add-distribution-input" source-id="<?= $item['Id'] ?>" placeholder="Add distribution">
                            <button type="button" class="source-repo-add-distribution-btn btn-xxsmall-green" source-id="<?= $item['Id'] ?>" title="Add distribution">+</button>
                        </div>
                        <?php
                    endif;

                    if ($type == 'rpm') :
                        echo '<h6>RELEASE VERSIONS</h6>';
                        echo '<p class="note">Embedded release versions.</p>';

                        if (!empty($releasevers)) :
                            foreach ($releasevers as $releaseverId => $releaseverDefinition) : ?>
                                <!-- Distributions -->
                                <div class="table-container grid-fr-4-1 bck-blue-alt source-repo-releasever-edit-param-btn pointer" source-id="<?= $item['Id'] ?>" releasever-id="<?= $releaseverId ?>">
                                    <div>
                                        <p><?= $releaseverDefinition['name'] ?></p>
                                        <p class="note"><?= $releaseverDefinition['description'] ?></p>
                                    </div>

                                    <div class="flex justify-end">
                                        <img src="/assets/icons/delete.svg" class="source-repo-remove-releasever-btn icon-lowopacity" source-id="<?= $item['Id'] ?>" releasever-id="<?= $releaseverId ?>" title="Remove <?= $releaseverDefinition['name'] ?> release version" />
                                    </div>
                                </div>
                                <?php
                            endforeach;
                        endif ?>

                        <div class="flex align-item-center column-gap-5">
                            <input type="text" class="source-repo-add-releasever-input" source-id="<?= $item['Id'] ?>" placeholder="Add release version">
                            <button type="button" class="source-repo-add-releasever-btn btn-xxsmall-green" source-id="<?= $item['Id'] ?>" title="Add release version">+</button>
                        </div>
                        <?php
                    endif ?>
                    
                    <h6>SSL AUTHENTICATION</h6>
                    <p class="note">Use a SSL certificate and private key to authenticate to the source repository.</p>

                    <h6>SSL CERTIFICATE</h6>
                    <p class="note">Plain text format.</p>
                    <textarea class="source-param textarea-100 resize-disabled" param-name="ssl-certificate" placeholder="-----BEGIN CERTIFICATE-----"><?= $sslCertificate ?></textarea>
    
                    <h6>SSL PRIVATE KEY</h6>
                    <p class="note">Plain text format.</p>
                    <textarea class="source-param textarea-100 resize-disabled" param-name="ssl-private-key" placeholder="-----BEGIN PRIVATE KEY-----"><?= $sslPrivateKey ?></textarea>

                    <h6>SSL CA CERTIFICATE</h6>
                    <p class="note">Plain text format.</p>
                    <textarea class="source-param textarea-100 resize-disabled" param-name="ssl-ca-certificate" placeholder="-----BEGIN CERTIFICATE-----"><?= $sslCaCertificate ?></textarea>

                    <br><br>
                    <button type="button" class="source-repo-form-submit-btn btn-medium-green" source-id="<?= $item['Id'] ?>" title="Save">Save</button>
                </form>
            </div>
            <?php
        endforeach; ?>
        
        <div class="flex justify-end margin-top-10">
            <?php \Controllers\Layout\Table\Render::paginationBtn($reloadableTableCurrentPage, $reloadableTableTotalPages); ?>
        </div>

        <?php
    endif ?>
</div>
