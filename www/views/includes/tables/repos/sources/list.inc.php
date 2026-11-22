<?php
use \Controllers\Layout\Table\Render as TableRender; ?>

<div class="reloadable-table" table="<?= $table ?>" offset="<?= $reloadableTableOffset ?>">
    <div class="margin-top-40 margin-bottom-15">
        <h6 class="margin-top-0">CURRENT SOURCE REPOSITORIES</h6>
        <p class="note">Source repositories used by curated repositories and mirrors.</p>
    </div>

    <?php
    // One tab per source repository type (same navigation as the settings sections), and a search on the name, URL and description ?>
    <div class="flex flex-direction-column row-gap-10 margin-bottom-15">
        <div class="flex flex-wrap align-item-center column-gap-20 row-gap-10">
            <?php
            foreach ($sourceTypes as $sourceType => $sourceTypeTitle) :
                $sourceTypeCount = $sourceMatchesByType[$sourceType] ?? 0;
                $sourceTypeClass = '';

                // The active tab is fully visible with a blue count (as the repositories groups), the other tabs are faded, even more the ones without search results
                if ($sourceSearch !== '' and $sourceTypeCount == 0) {
                    $sourceTypeClass = ' mediumopacity opacity-30';
                } elseif ($sourceType != $sourceActiveType) {
                    $sourceTypeClass = ' mediumopacity panel-tab-inactive';
                } ?>

                <div class="flex align-item-center column-gap-5">
                    <div class="slide-btn panel-tab-btn source-repo-type-btn<?= $sourceTypeClass ?>" source-type="<?= $sourceType ?>" title="<?= $sourceTypeTitle ?> source repositories">
                        <span><?= $sourceTypeTitle ?></span>
                    </div>
                    <span class="<?= $sourceTypeClass === '' ? 'label-count' : 'label-tr' ?> label-medium" title="<?= $sourceTypeCount . ' ' . $sourceTypeTitle . ' source repositor' . ($sourceTypeCount > 1 ? 'ies' : 'y') . ($sourceSearch !== '' ? ' matching the search' : '') ?>"><?= $sourceTypeCount ?></span>
                </div>
                <?php
            endforeach ?>
        </div>

        <?php
        // No search without source repository. The 'select all' checkbox is next to the search, above the list checkboxes (as in the signing keys panel)
        if (!empty($sourceTotalByType)) : ?>
            <div class="flex align-item-center column-gap-10">
                <input id="source-repo-search" type="text" class="flex-1" maxlength="100" value="<?= htmlspecialchars($sourceSearch, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search by name, URL or description" title="Search source repositories by name, URL or description" autocomplete="off" />

                <?php
                if (!empty($reloadableTableContent)) : ?>
                    <input type="checkbox" class="select-all-checkbox lowopacity margin-left-5 margin-right-20" checkbox-id="source-repo" title="Select all source repositories" />
                    <?php
                endif ?>
            </div>
            <?php
        endif ?>
    </div>

    <?php
    // Active tab without source repository
    if (empty($sourceTotalByType[$sourceActiveType])) : ?>
        <div class="empty-state">
            <p class="empty-state-title">No <?= $sourceTypes[$sourceActiveType] ?> source repository configured yet.</p>
            <p class="note">Import a predefined list to get started quickly, or add a source repository manually.</p>
            <div class="empty-state-actions">
                <button type="button" class="btn-medium-green get-panel-btn" panel="repos/sources/import">Import</button>
                <button type="button" class="btn-medium-tr get-panel-btn" panel="repos/sources/new">Manually add</button>
            </div>
        </div>
        <?php
    elseif (empty($reloadableTableContent)) : ?>
        <p class="note">No source repository matches this search.</p>
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

            // Case it is an alpine source repository
            if ($type == 'alpine') {
                $accent = 'violet';
            }

            // Case it is an openbsd source repository
            if ($type == 'openbsd') {
                $accent = 'yellow';
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
                    if (in_array($type, ['arch', 'alpine', 'openbsd'])) :
                        if ($type == 'arch') {
                            // Repositories (e.g. core, extra) replace $repo in the URL, architectures replace $arch
                            $archItems = [
                                'repos' => ['title' => 'REPOSITORIES', 'note' => 'Repositories that can be mirrored, they replace <code>$repo</code> in the URL.', 'placeholder' => 'Add repository (e.g. core)'],
                                'archs' => ['title' => 'ARCHITECTURES', 'note' => 'Architectures that can be mirrored, they replace <code>$arch</code> in the URL.', 'placeholder' => 'Add architecture (e.g. x86_64)']
                            ];
                        }

                        if ($type == 'alpine') {
                            // Branches (e.g. v3.24) replace $branch in the URL, repositories (e.g. main) replace $repo, architectures are appended to the URL (as apk does)
                            $archItems = [
                                'branches' => ['title' => 'BRANCHES', 'note' => 'Branches that can be mirrored, they replace <code>$branch</code> in the URL (not needed if the URL does not contain it).', 'placeholder' => 'Add branch (e.g. v3.24)'],
                                'repos'    => ['title' => 'REPOSITORIES', 'note' => 'Repositories that can be mirrored, they replace <code>$repo</code> in the URL (not needed if the URL does not contain it).', 'placeholder' => 'Add repository (e.g. main)'],
                                'archs'    => ['title' => 'ARCHITECTURES', 'note' => 'Architectures that can be mirrored, they are appended to the URL, as apk does.', 'placeholder' => 'Add architecture (e.g. x86_64)']
                            ];
                        }

                        if ($type == 'openbsd') {
                            // Versions (e.g. 8.0) and platforms (e.g. amd64) of the OpenBSD tree, the URL is the root of an OpenBSD mirror
                            $archItems = [
                                'versions' => ['title' => 'VERSIONS', 'note' => 'OpenBSD versions that can be mirrored (e.g. 8.0). Snapshots are not supported.', 'placeholder' => 'Add version (e.g. 8.0)'],
                                'archs'    => ['title' => 'ARCHITECTURES', 'note' => 'Platforms that can be mirrored, as named by OpenBSD (e.g. amd64, arm64). Their packages directory is named after their architecture (e.g. aarch64 for arm64).', 'placeholder' => 'Add architecture (e.g. amd64)']
                            ];
                        }

                        foreach ($archItems as $field => $archItem) : ?>
                            <h6><?= $archItem['title'] ?></h6>
                            <p class="note"><?= $archItem['note'] ?></p>

                            <?php
                            foreach ($definition[$field] ?? [] as $itemId => $itemDefinition) : ?>
                                <div class="table-container grid-fr-4-1 bck-blue-alt source-repo-arch-edit-btn pointer" source-id="<?= $item['Id'] ?>" field="<?= $field ?>" item-id="<?= $itemId ?>">
                                    <div>
                                        <p><?= htmlspecialchars($itemDefinition['name']) ?></p>
                                        <p class="note"><?= htmlspecialchars($itemDefinition['description'] ?? '') ?></p>
                                    </div>

                                    <div class="flex justify-end">
                                        <img src="/assets/icons/delete.svg" class="source-repo-arch-remove-btn icon-lowopacity" source-id="<?= $item['Id'] ?>" field="<?= $field ?>" item-id="<?= $itemId ?>" title="Remove <?= htmlspecialchars($itemDefinition['name']) ?>" />
                                    </div>
                                </div>
                                <?php
                            endforeach ?>

                            <div class="flex align-item-center column-gap-5">
                                <input type="text" class="source-repo-arch-add-input" source-id="<?= $item['Id'] ?>" field="<?= $field ?>" placeholder="<?= $archItem['placeholder'] ?>">
                                <button type="button" class="source-repo-arch-add-btn btn-xxsmall-green" source-id="<?= $item['Id'] ?>" field="<?= $field ?>" title="Add">+</button>
                            </div>
                            <?php
                        endforeach;

                        // GPG keys of the arch source repository, as the GPG keys of a deb distribution or a rpm release version
                        if ($type == 'arch') : ?>
                            <h6>GPG KEYS</h6>
                            <p class="note">Imported in Repomanager keyring, used to verify the database signature when the source repository signs it.</p>

                            <?php
                            foreach ($definition['gpgkeys'] ?? [] as $gpgKeyId => $gpgKeyDefinition) :
                                $gpgKey = $gpgKeyDefinition['fingerprint'] ?? '';
                                $gpgKeyName = 'Unknown key, try to reimport it.';
                                $imported = false;

                                // If the GPG key is in the trusted keyring, mark it as imported and retrieve its name
                                if (in_array($gpgKey, array_column($trustedGpgKeys, 'id'))) {
                                    $imported = true;
                                    $gpgKeyName = $trustedGpgKeys[array_search($gpgKey, array_column($trustedGpgKeys, 'id'))]['name'];
                                } ?>

                                <div class="table-container grid-2 bck-blue-alt">
                                    <div>
                                        <p><?= htmlspecialchars($gpgKeyName) ?></p>
                                        <p class="note copy"><?= htmlspecialchars($gpgKey) ?></p>
                                    </div>

                                    <div class="flex justify-end column-gap-10">
                                        <?php
                                        if ($imported) {
                                            echo '<img src="/assets/icons/check.svg" class="icon-np" title="Imported GPG key" />';
                                        } else {
                                            echo '<img src="/assets/icons/warning-red.svg" class="icon-np" title="Unknown GPG key" />';
                                        } ?>

                                        <img src="/assets/icons/delete.svg" class="icon-lowopacity source-repo-remove-gpgkey-btn" source-id="<?= $item['Id'] ?>" gpgkey-id="<?= $gpgKeyId ?>" title="Remove GPG key <?= htmlspecialchars($gpgKey) ?>" />
                                    </div>
                                </div>
                                <?php
                            endforeach ?>

                            <!-- Same fields as the GPG keys import of a deb distribution or a rpm release version (not a form: it is in the source repository form) -->
                            <h6>IMPORT GPG KEY</h6>
                            <p class="note">Import a GPG key and link it to the <?= htmlspecialchars($name) ?> source repository.</p>

                            <div class="source-repo-import-gpgkey" source-id="<?= $item['Id'] ?>">
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
                                <button type="button" class="btn-medium-green source-repo-import-gpgkey-btn" source-id="<?= $item['Id'] ?>">Import</button>
                            </div>
                            <?php
                        endif;

                        if (in_array($type, ['alpine', 'openbsd'])) : ?>
                            <h6>SIGNING KEYS</h6>
                            <?php
                            if ($type == 'alpine') : ?>
                                <p class="note">RSA public keys used to verify the indexes signature. The name must be the name of the key file on clients (<code>/etc/apk/keys/&lt;name&gt;</code>).</p>
                                <?php
                            endif;

                            if ($type == 'openbsd') : ?>
                                <p class="note">Signify public keys used to verify the files lists signature (<code>SHA256.sig</code>): base, pkg and syspatch keys of each version (e.g. <code>openbsd-80-pkg.pub</code>), as installed in <code>/etc/signify/</code> on clients.</p>
                                <?php
                            endif;

                            foreach ($definition['keys'] ?? [] as $key) : ?>
                                <div class="table-container grid-fr-4-1 bck-blue-alt">
                                    <div>
                                        <p class="copy wordbreakall"><?= htmlspecialchars($key['name']) ?></p>
                                        <p class="note wordbreakall"><?= !empty($key['link']) ? htmlspecialchars($key['link']) : 'Imported from plain text' ?></p>
                                    </div>

                                    <div class="flex justify-end">
                                        <img src="/assets/icons/delete.svg" class="source-repo-remove-key-btn icon-lowopacity" source-id="<?= $item['Id'] ?>" key-name="<?= htmlspecialchars($key['name']) ?>" title="Remove <?= htmlspecialchars($key['name']) ?> key" />
                                    </div>
                                </div>
                                <?php
                            endforeach;

                            // Same fields as the GPG keys import of a deb distribution or a rpm release version, plus the key name
                            $keyType = $type == 'alpine' ? 'RSA' : 'signify';
                            $keyPlaceholder = $type == 'alpine' ? '-----BEGIN PUBLIC KEY-----' : 'untrusted comment: openbsd 8.0 pkg public key';
                            $keyNameExample = $type == 'alpine' ? 'alpine-devel@lists.alpinelinux.org-6165ee59.rsa.pub' : 'openbsd-80-pkg.pub'; ?>

                            <!-- Not a form: it is in the source repository form -->
                            <h6>IMPORT <?= strtoupper($keyType) ?> KEY</h6>
                            <p class="note">Import <?= $type == 'alpine' ? 'an' : 'a' ?> <?= $keyType ?> public key into the <?= htmlspecialchars($name) ?> source repository.</p>

                            <div class="source-repo-import-key" source-id="<?= $item['Id'] ?>">
                                <h6>IMPORT FROM LINK</h6>
                                <p class="note">URL to the key file.</p>
                                <input type="text" name="key-url" placeholder="http://..." />

                                <h6>IMPORT FROM PLAIN TEXT</h6>
                                <p class="note">Content of the key file.</p>
                                <textarea name="key-content" class="textarea-100" placeholder="<?= $keyPlaceholder ?>"></textarea>

                                <h6>KEY NAME</h6>
                                <p class="note">Name of the key file on clients. Required when importing from plain text, the name of the downloaded file by default when importing from link.</p>
                                <input type="text" name="key-name" placeholder="<?= $keyNameExample ?>" />

                                <br><br>
                                <button type="button" class="btn-medium-green source-repo-import-key-btn" source-id="<?= $item['Id'] ?>">Import</button>
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
