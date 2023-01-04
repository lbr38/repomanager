<div id="sourcesDiv" class="param-slide-container">
    <div class="param-slide">

        <?php $source = new \Controllers\Source(); ?>

        <img id="source-repo-close-btn" title="Close" class="close-btn lowopacity float-right" src="resources/icons/close.svg" />
        <h3>SOURCE REPOSITORIES</h3>

        <p>To create mirrors, you must configure sources repositories.</p>
        <br>

        <h4><b>New source repository</b></h4>

        <form id="addSourceForm" autocomplete="off">
            <table>
                <tr>
                    <td class="td-30">Repo type</td>
                    <td colspan="100%">
                        <div class="switch-field">
                            <?php
                            if (RPM_REPO == 'true' and DEB_REPO == 'true') : ?>
                                <input type="radio" id="repoType_rpm" name="addSourceRepoType" value="rpm" checked />
                                <label for="repoType_rpm">rpm</label>
                                <input type="radio" id="repoType_deb" name="addSourceRepoType" value="deb" />
                                <label for="repoType_deb">deb</label>
                                <?php
                            elseif (RPM_REPO == 'true') : ?>
                                <input type="radio" id="repoType_rpm" name="addSourceRepoType" value="rpm" checked />
                                <label for="repoType_rpm">rpm</label>     
                                <?php
                            elseif (DEB_REPO == 'true') : ?>
                                <input type="radio" id="repoType_deb" name="addSourceRepoType" value="deb" checked />
                                <label for="repoType_deb">deb</label> 
                                <?php
                            endif ?>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>Name</td>
                    <td colspan="100%">
                        <input type="text" name="addSourceName" required />
                    </td>
                </tr>
                <tr>
                    <td>URL</td>
                    <td>
                        <input type="text" name="addSourceUrl" required />
                    </td>
                </tr>
                <tr>
                    <td colspan="2"><br>GPG signing key <span class="lowopacity">(optionnal)</span></td>
                </tr>
                <tr>
                    <td colspan="100%">
                        <div>
                            <br>
                            <p>Specify URL to the GPG key or import a plain ASCII GPG key.</p>
                            <br>

                            <p>URL to the GPG key:</p>
                            <input type="text" name="gpgKeyURL" placeholder="https://...">
                            
                            <br><br>
                            <p>Import a GPG key:</p>
                            <textarea id="gpgKeyText" class="textarea-100" placeholder="-----BEGIN PGP PUBLIC KEY BLOCK-----"></textarea>
                        </div>
                    </td>
                </tr>
            </table>
            <br>
            <button type="submit" class="btn-large-green" title="Add">Add source</button>
        </form>

        <?php
        /**
         *  Get all source repos
         */
        $sources = $source->listAll();

        /**
         *  Print source repos if there are
         */
        if (!empty($sources)) : ?>
            <br><br>
            <h4><b>Current source repositories</b></h4>

            <?php
            if (!empty($sources)) :
                foreach ($sources as $source) :
                    $sourceId = $source['Id'];
                    $sourceName = $source['Name'];
                    $sourceUrl = $source['Url'];
                    $sourceType = $source['Type']; ?>

                    <table class="table-generic-blue">
                        <tr>
                            <td class="td-10">
                                <?php
                                if ($sourceType == 'rpm') {
                                    echo ' <span class="label-pkg-rpm">rpm</span>';
                                }
                                if ($sourceType == 'deb') {
                                    echo ' <span class="label-pkg-deb">deb</span>';
                                } ?>
                            </td>
                            <td>
                                <input class="source-input-name input-medium invisibleInput-blue" type="text" source-name="<?= $sourceName ?>" source-type="<?= $sourceType ?>" value="<?= $sourceName ?>" />
                            </td>
                            <td>
                                <input class="source-input-url input-medium invisibleInput-blue" type="text" source-name="<?= $sourceName ?>" source-type="<?= $sourceType ?>" value="<?= $sourceUrl ?>" />
                            </td>
                            <td class="td-fit">
                                <img src="resources/icons/cog.svg" class="icon-lowopacity source-repo-edit-param-btn" source-id="<?= $sourceId ?>" title="Configure repository" />
                                <img src="resources/icons/bin.svg" class="source-repo-delete-btn icon-lowopacity" source-id="<?= $sourceId ?>" source-name="<?= $sourceName ?>" title="Delete <?= $sourceName ?> source repo" />
                            </td>
                        </tr>
                    </table>

                    <div class="header-container hide source-repo-param-div" source-id="<?= $sourceId ?>">
                        <div class="header-light-blue-min"> 
                            <table>
                                <tr>
                                    <td>
                                        <img src="resources/icons/key.svg" class="icon" />
                                    </td>
                                    <td>
                                        GPG signature key URL
                                    </td>
                                    <td colspan="3">
                                        <input class="invisibleInput-light-blue source-repo-gpgkey-input" source-id="<?= $sourceId ?>" type="text" value="<?= $source['Gpgkey'] ?>" placeholder="http://..." />
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        <img src="resources/icons/file.svg" class="icon" />
                                    </td>
                                    <td>
                                        SSL certificate file path
                                    </td>
                                    <td colspan="3">
                                        <input class="invisibleInput-light-blue source-repo-crt-input" source-id="<?= $sourceId ?>" type="text" value="<?= $source['Ssl_certificate_path'] ?>" placeholder="e.g. /etc/ssl/certificate.crt" />
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        <img src="resources/icons/file.svg" class="icon" />
                                    </td>
                                    <td>
                                        SSL private key file path
                                    </td>
                                    <td colspan="3">
                                        <input class="invisibleInput-light-blue source-repo-key-input" source-id="<?= $sourceId ?>" type="text" value="<?= $source['Ssl_private_key_path'] ?>" placeholder="e.g. /etc/ssl/private.key" />
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <?php
                endforeach;
            endif;
        endif; ?>      

        <?php
        /**
         *  Get imported GPG signing keys
         */
        $knownPublicKeys = \Controllers\Common::getGpgTrustedKeys(); ?>

        <br><br>
        <h4><b>GPG signing keys</b></h4>

        <p>Import a GPG key:</p>

        <form id="source-repo-add-key-form" autocomplete="off">
            <div class="flex align-content-center">
                <textarea id="source-repo-add-key-textarea" class="textarea-100" placeholder="-----BEGIN PGP PUBLIC KEY BLOCK-----"></textarea>
                <button class="btn-xxsmall-green" title="Import">+</button>
            </div>
        </form>

        <?php
        if (!empty($knownPublicKeys)) : ?>
            <br>
            <p>Imported GPG keys:</p>
            <table class="table-generic-blue">
                <?php
                foreach ($knownPublicKeys as $knownPublicKey) : ?>
                    <tr>
                        <td title="GPG key name <?= $knownPublicKey['name'] ?> with Id <?= $knownPublicKey['id'] ?>">
                            <?= $knownPublicKey['name'] ?>
                        </td>
                        <td class="td-fit">
                            <img src="resources/icons/bin.svg" class="gpgKeyDeleteBtn icon-lowopacity" gpgkey-id="<?= $knownPublicKey['id'] ?>" gpgkey-name="<?= $knownPublicKey['name'] ?>" title="Delete GPG key <?= $knownPublicKey['name'] ?>" />
                        </td>
                    </tr>
                    <?php
                endforeach ?>
            </table>
            <?php
        endif; ?>
        <br><br>
    </div>
</div>