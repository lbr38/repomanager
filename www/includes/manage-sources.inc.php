<div id="sourcesDiv" class="param-slide-container">
    <div class="param-slide">
        
        <?php $source = new \Controllers\Source(); ?>

        <img id="source-repo-close-btn" title="Close" class="close-btn lowopacity float-right" src="resources/icons/close.svg" />
        <h3>SOURCE REPOSITORIES</h3>

        <p>To create mirrors, you must configure sources repositories.</p>
        <br>

        <h4>Add a new source repository</h4>

        <form id="addSourceForm" autocomplete="off">
            <table>
                <tr>
                    <td class="td-30">Repo type</td>
                    <td colspan="100%">
                        <div class="switch-field">
                            <?php
                            if (RPM_REPO == 'enabled' and DEB_REPO == 'enabled') : ?>
                                <input type="radio" id="repoType_rpm" name="addSourceRepoType" value="rpm" checked />
                                <label for="repoType_rpm">rpm</label>
                                <input type="radio" id="repoType_deb" name="addSourceRepoType" value="deb" />
                                <label for="repoType_deb">deb</label>
                                <?php
                            elseif (RPM_REPO == 'enabled') : ?>
                                <input type="radio" id="repoType_rpm" name="addSourceRepoType" value="rpm" checked />
                                <label for="repoType_rpm">rpm</label>     
                                <?php
                            elseif (DEB_REPO == 'enabled') : ?>
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
                <tr field-type="rpm">
                    <td>This source repo has a GPG pub key</td>
                    <td class="td-10">
                        <select id="newRepoGpgSelect" class="select-small">
                            <option id="newRepoGpgSelect_no">No</option>
                            <option id="newRepoGpgSelect_yes">Yes</option>
                        </select>
                    </td>
                </tr>
                <tr field-type="rpm">
                    <td field-type="rpm" colspan="100%">
                        <div class="sourceGpgDiv hide">
                            <br>
                            <span>You can either specify an URL to the GPG key or import a plan ASCII text GPG key.</span><br><br>

                            <p>URL to the GPG key:</p>
                            <input type="text" name="gpgKeyURL" placeholder="https://...">
                            
                            <br>
                            <p>Import a GPG key:</p>
                            <textarea id="rpmGpgKeyText" class="textarea-100" placeholder="ASCII format"></textarea>
                        </div>
                    </td>
                </tr>
                <tr field-type="deb">
                    <td field-type="deb" colspan="100%">
                        <p>
                            <br>
                            <span>Import a GPG key</span>
                            <span class="lowopacity">(optionnal)</span>
                        </p>
                        <textarea id="debGpgKeyText" class="textarea-100" placeholder="ASCII format"></textarea>
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
            <br>
            <h4>Current source repositories</h4>

            <?php
            if (!empty($sources)) :
                foreach ($sources as $source) :
                    $sourceId = $source['Id'];
                    $sourceName = $source['Name'];
                    $sourceUrl = $source['Url'];
                    $sourceType = $source['Type']; ?>

                    <div class="header-container">
                        <div class="header-blue-min"> 
                            <table id="sourceDivs">
                                <tr>
                                    <td class="td-10">
                                        <?php
                                        if ($sourceType == 'rpm') {
                                            echo '<img src="resources/icons/products/centos.png" class="icon" />';
                                        }
                                        if ($sourceType == 'deb') {
                                            echo '<img src="resources/icons/products/debian.png" class="icon" />';
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
                                    </td>
                                    <td class="td-fit">
                                        <img src="resources/icons/bin.svg" class="source-repo-delete-btn icon-lowopacity" source-id="<?= $sourceId ?>" source-name="<?= $sourceName ?>" title="Delete <?= $sourceName ?> source repo" />
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

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
        endif;

        /**
         *  Get imported GPG signing keys
         */
        $knownPublicKeys = \Controllers\Common::getGpgTrustedKeys(); ?>

        <br>
        <h4>Imported GPG signing keys</h4>

        <?php
        if (!empty($knownPublicKeys)) : ?>
            <p class="lowopacity">These are the public GPG signing keys you have imported.</p>

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

        <h5>Import a GPG key:</h5>

        <form id="source-repo-add-key-form" autocomplete="off">
            <div class="flex flex-align-cnt-center">
                <textarea id="source-repo-add-key-textarea" class="textarea-100" placeholder="ASCII format"></textarea>
                <button class="btn-xxsmall-green" title="Import">+</button>
            </div>
        </form>
        <br>
    </div>
</div>