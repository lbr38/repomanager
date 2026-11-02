<?php
use \Controllers\Layout\Table\Render as TableRender; ?>

<div class="reloadable-table" table="<?= $table ?>" offset="<?= $reloadableTableOffset ?>">
    <div class="flex justify-space-between align-flex-end margin-top-40 margin-bottom-15 margin-right-20">
        <div>
            <h6 class="margin-top-0">CURRENT SOURCE REPOSITORIES</h6>
            <p class="note">Source repositories used by curated repositories and mirrors.</p>
        </div>

        <?php
        if (!empty($reloadableTableContent)) : ?>
            <input type="checkbox" class="select-all-checkbox lowopacity" checkbox-id="source-repo" title="Select all source repositories" />
            <?php
        endif ?>
    </div>

    <?php
    if (empty($reloadableTableContent)) : ?>
        <div class="empty-state">
            <p class="empty-state-title">No source repository configured yet.</p>
            <p class="note">Import a predefined list to get started quickly, or add a source repository manually.</p>
            <div class="empty-state-actions">
                <button type="button" class="btn-medium-green get-panel-btn" panel="repos/sources/import">Import</button>
                <button type="button" class="btn-medium-tr get-panel-btn" panel="repos/sources/new">Manually add</button>
            </div>
        </div>
        <?php
    endif;

    if (!empty($reloadableTableContent)) :
        foreach ($reloadableTableContent as $item) :
            $distAndComponent = null;
            $releasevers = null;
            $sslCertificate = '';
            $sslPrivateKey = '';
            $sslCaCertificate = '';

            // Decode JSON definition
            try {
                $definition = json_decode($item['Definition'], true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                echo '<p>Error: could not decode JSON definition for source repository #' . $item['Id'] . '</p>';
                continue;
            }

            $name = $definition['name'];
            $url  = $definition['url'];
            $type = $definition['type'];
            $description = $definition['description'];

            // Case it is a deb source repository
            if ($type == 'deb') {
                $accent = 'red';

                if (isset($definition['distributions'])) {
                    $distAndComponent = $definition['distributions'];
                }
            }

            // Case it is a rpm source repository
            if ($type == 'rpm') {
                $accent = 'blue';

                if (isset($definition['releasever'])) {
                    $releasevers = $definition['releasever'];
                }
            }

            // Case it is an arch source repository
            if ($type == 'arch') {
                $accent = 'cyan';
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
            }

            // Basic authentication
            $basicAuthUsername = $definition['basic-authentication']['username'] ?? '';
            $basicAuthPassword = $definition['basic-authentication']['password'] ?? ''; ?>

            <div class="table-container-2 panel-list-item accent-<?= $accent ?> pointer source-repo-edit-param-btn" source-id="<?= $item['Id'] ?>">
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
                        <p class="note">The repository does not follow the standard Debian repository structure. <b><a href="https://docs.repomanager.net/configuration/source-repositories/#non-compliant-deb-source-repositories" target="_blank" rel="noopener noreferrer" class="font-size-13">Learn more.</a></b></p>
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
                                <div class="table-container grid-fr-4-1 bck-blue-alt source-repo-distribution-edit-btn pointer" source-id="<?= $item['Id'] ?>" distribution-id="<?= $distributionId ?>">
                                    <div>
                                        <div class="flex align-item-center column-gap-20">
                                            <div>
                                                <p><?= $distributionDetails['name'] ?></p>
                                                <p class="note"><?= $distributionDetails['description'] ?? '' ?></p>
                                            </div>

                                            <?php
                                            if (!empty($distributionDetails['eol']) and $distributionDetails['eol'] < DATE_YMD) {
                                                echo '<span class="label-yellow" title="This distribution has reached End of Life on ' . $distributionDetails['eol'] . '">EOL</span>';
                                            } ?>
                                        </div>

                                        <?php
                                        if (!empty($distributionDetails['components'])) : ?>
                                            <div class="flex column-gap-5 row-gap-5 margin-top-10">
                                                <?php
                                                foreach ($distributionDetails['components'] as $componentId => $componentDetails) {
                                                    echo '<p class="label-black">' . $componentDetails['name'] . '</p>';
                                                } ?>
                                            </div>
                                            <?php
                                        endif ?>
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
                                <!-- Release versions -->
                                <div class="table-container grid-fr-4-1 bck-blue-alt source-repo-releasever-edit-param-btn pointer" source-id="<?= $item['Id'] ?>" releasever-id="<?= $releaseverId ?>">
                                    <div class="flex align-item-center column-gap-20">
                                        <div>
                                            <p><?= $releaseverDefinition['name'] ?></p>
                                            <p class="note"><?= $releaseverDefinition['description'] ?></p>
                                        </div>

                                        <?php
                                        if (!empty($releaseverDefinition['eol']) and $releaseverDefinition['eol'] < DATE_YMD) {
                                            echo '<span class="label-yellow" title="This release version has reached End of Life on ' . $releaseverDefinition['eol'] . '">EOL</span>';
                                        } ?>
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
                    
                    <?php
                    if ($type == 'arch') :
                        // Repositories (e.g. core, extra) replace $repo in the URL, architectures replace $arch
                        $archItems = [
                            'repos' => ['title' => 'REPOSITORIES', 'note' => 'Repositories that can be mirrored, they replace <code>$repo</code> in the URL.', 'placeholder' => 'Add repository (e.g. core)'],
                            'archs' => ['title' => 'ARCHITECTURES', 'note' => 'Architectures that can be mirrored, they replace <code>$arch</code> in the URL.', 'placeholder' => 'Add architecture (e.g. x86_64)']
                        ];

                        foreach ($archItems as $field => $archItem) : ?>
                            <h6><?= $archItem['title'] ?></h6>
                            <p class="note"><?= $archItem['note'] ?></p>

                            <div class="flex flex-wrap column-gap-5 row-gap-5 margin-bottom-10">
                                <?php
                                foreach ($definition[$field] ?? [] as $itemId => $itemDefinition) : ?>
                                    <p class="label-black flex align-item-center column-gap-5" title="<?= htmlspecialchars($itemDefinition['description'] ?? '') ?>">
                                        <?= htmlspecialchars($itemDefinition['name']) ?>
                                        <img src="/assets/icons/close.svg" class="source-repo-arch-remove-btn icon-small pointer lowopacity" source-id="<?= $item['Id'] ?>" field="<?= $field ?>" item-id="<?= $itemId ?>" title="Remove <?= htmlspecialchars($itemDefinition['name']) ?>" />
                                    </p>
                                    <?php
                                endforeach ?>
                            </div>

                            <div class="flex align-item-center column-gap-5">
                                <input type="text" class="source-repo-arch-add-input" source-id="<?= $item['Id'] ?>" field="<?= $field ?>" placeholder="<?= $archItem['placeholder'] ?>">
                                <button type="button" class="source-repo-arch-add-btn btn-xxsmall-green" source-id="<?= $item['Id'] ?>" field="<?= $field ?>" title="Add">+</button>
                            </div>
                            <?php
                        endforeach;

                        if (!empty($definition['gpgkeys'])) : ?>
                            <h6>GPG KEYS</h6>
                            <p class="note">Imported in Repomanager keyring, used to verify the database signature when the source repository signs it.</p>
                            <div class="flex flex-wrap column-gap-5 row-gap-5">
                                <?php
                                foreach ($definition['gpgkeys'] as $gpgKey) {
                                    if (!empty($gpgKey['fingerprint'])) {
                                        echo '<p class="label-black copy">' . htmlspecialchars($gpgKey['fingerprint']) . '</p>';
                                    }
                                } ?>
                            </div>
                            <?php
                        endif;
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

                    <h6>BASIC AUTHENTICATION</h6>
                    <p class="note">Use a username and password to authenticate to the source repository. Leave the username empty to disable.</p>

                    <h6>USERNAME</h6>
                    <input type="text" class="source-param" param-name="basic-auth-username" value="<?= htmlspecialchars($basicAuthUsername) ?>" autocomplete="off" />

                    <h6>PASSWORD</h6>
                    <input type="password" class="source-param" param-name="basic-auth-password" value="<?= htmlspecialchars($basicAuthPassword) ?>" autocomplete="new-password" />

                    <br><br>
                    <button type="button" class="source-repo-form-submit-btn btn-medium-green" source-id="<?= $item['Id'] ?>" title="Save">Save</button>
                </form>
            </div>
            <?php
        endforeach; ?>
        
        <div class="flex justify-end margin-top-10">
            <?php TableRender::paginationBtn($reloadableTableCurrentPage, $reloadableTableTotalPages); ?>
        </div>

        <?php
    endif ?>
</div>
