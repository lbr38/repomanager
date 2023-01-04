<div id="newRepoDiv" class="param-slide-container">
    <div class="param-slide">
        <img id="newRepoCloseButton" title="Close" class="close-btn lowopacity float-right" src="resources/icons/close.svg" />

        <?php
        $mysource = new \Controllers\Source();

        /**
         *  Récupération de la liste de tous les groupes
         */
        $group = new \Controllers\Group('repo');
        $groupList = $group->listAllName(); ?>
        
        <h3>CREATE A NEW REPO</h3>

        <form class="operation-form-container" autocomplete="off">
            <div class="operation-form" repo-id="none" action="new">
                <table>
                    <tr>
                        <td>Package type</td>
                        <td>
                            <div class="switch-field">
                                <?php
                                /**
                                 *  Cas où le serveur gère plusieurs types de repo différents
                                 */
                                if (RPM_REPO == 'true' and DEB_REPO == 'true') : ?>
                                    <input type="radio" id="packageType_rpm" class="operation_param" param-name="packageType" name="packageType" value="rpm" checked />
                                    <label for="packageType_rpm">rpm</label>
                                    <input type="radio" id="packageType_deb" class="operation_param" param-name="packageType" name="packageType" value="deb" />
                                    <label for="packageType_deb">deb</label>
                                <?php elseif (RPM_REPO == 'true') : ?>
                                    <input type="radio" id="packageType_rpm" class="operation_param" param-name="packageType" name="packageType" value="rpm" checked />
                                    <label for="packageType_rpm">rpm</label>     
                                <?php elseif (DEB_REPO == 'true') : ?>
                                    <input type="radio" id="packageType_deb" class="operation_param" param-name="packageType" name="packageType" value="deb" checked />
                                    <label for="packageType_deb">deb</label> 
                                <?php endif ?>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td class="td-30">Repo type</td>
                        <td>
                            <div class="switch-field">
                                <input type="radio" id="repoType_mirror" class="operation_param" param-name="type" name="repoType" value="mirror" package-type="all" checked />
                                <label for="repoType_mirror">Mirror</label>
                                <input type="radio" id="repoType_local" class="operation_param" param-name="type" name="repoType" value="local" package-type="all" />
                                <label for="repoType_local">Local</label>
                            </div>
                        </td>
                    </tr>

                    <tr field-type="mirror rpm deb">
                        <td class="td-30">Source repo</td>
                        <td>
                            <?php if (RPM_REPO == true) : ?>
                                <select id="repoSourceSelect" class="operation_param" param-name="source" field-type="mirror rpm" package-type="rpm">
                                    <option value="">Select a source repo...</option>
                                    <?php
                                    $sourcesList = $mysource->listAll('rpm');

                                    if (!empty($sourcesList)) {
                                        foreach ($sourcesList as $source) {
                                            $sourceName = $source['Name'];
                                            $sourceUrl = $source['Url'];

                                            echo '<option value="' . $sourceName . '">' . $sourceName . '</option>';
                                        }
                                    } ?>
                                </select>
                            <?php endif;

                            if (DEB_REPO == 'true') : ?>
                                <select id="repoSourceSelect" class="operation_param" param-name="source" field-type="mirror deb" package-type="deb">
                                    <option value="">Select a source repo...</option>
                                    <?php
                                    $sourcesList = $mysource->listAll('deb');

                                    if (!empty($sourcesList)) {
                                        foreach ($sourcesList as $source) {
                                            $sourceName = $source['Name'];
                                            $sourceUrl = $source['Url'];

                                            echo '<option value="' . $sourceName . '">' . $sourceName . '</option>';
                                        }
                                    } ?>
                                </select>
                            <?php endif; ?>
                        </td>
                    </tr>
            
                    <tr>
                        <td class="td-30" field-type="mirror rpm deb">
                            <span>Custom repo name</span>
                            <span class="lowopacity">(optionnal)</span>
                        </td>
                        <td class="td-30" field-type="local rpm deb">Repo name</td>
                        <td>
                            <input type="text" class="operation_param" param-name="alias" package-type="all" />
                        </td>
                    </tr>

                    <tr field-type="mirror local deb">
                        <td class="td-30">Distribution</td>
                        <td>
                            <input type="text" class="operation_param" param-name="dist" package-type="deb" />
                        </td>
                    </tr>

                    <tr field-type="mirror local deb">
                        <td class="td-30">Section</td>
                        <td>
                            <input type="text" class="operation_param" param-name="section" package-type="deb" />
                        </td>
                    </tr>

                    <tr>
                        <td class="td-30">Point an environment</td>
                        <td>
                            <select id="new-repo-target-env-select" class="operation_param" param-name="targetEnv" package-type="all">
                                <option value=""></option>
                                <?php
                                foreach (ENVS as $env) {
                                    if ($env == DEFAULT_ENV) {
                                        echo '<option value="' . $env . '" selected>' . $env . '</option>';
                                    } else {
                                        echo '<option value="' . $env . '">' . $env . '</option>';
                                    }
                                } ?>
                            </select>
                        </td>
                    </tr>

                    <tr id="new-repo-target-description-tr">
                        <td class="td-30">
                            <span>Description</span>
                            <span class="lowopacity">(optionnal)</span>
                        </td>
                        <td><input type="text" class="operation_param" param-name="targetDescription" package-type="all" /></td>
                    </tr>

                    <tr field-type="mirror rpm deb">
                        <td class="td-30">Check GPG signatures</td>
                        <td>
                            <label class="onoff-switch-label">
                                <input name="repoGpgCheck" type="checkbox" class="onoff-switch-input operation_param" value="yes" param-name="targetGpgCheck" package-type="all" checked />
                                <span class="onoff-switch-slider"></span>
                            </label>
                        </td>
                    </tr>

                    <tr field-type="mirror rpm deb">
                        <td class="td-30">Sign with GPG</td>
                        <td>
                            <label class="onoff-switch-label" field-type="mirror rpm">
                                <input name="repoGpgResign" type="checkbox" class="onoff-switch-input operation_param type_rpm" value="yes" param-name="targetGpgResign" package-type="rpm" <?php echo (RPM_SIGN_PACKAGES == "true") ? 'checked' : ''; ?> />
                                <span class="onoff-switch-slider"></span>
                            </label>
                            <label class="onoff-switch-label" field-type="mirror deb">
                                <input name="repoGpgResign" type="checkbox" class="onoff-switch-input operation_param type_deb" value="yes" param-name="targetGpgResign" package-type="deb" <?php echo (DEB_SIGN_REPO == "true") ? 'checked' : ''; ?> />
                                <span class="onoff-switch-slider"></span>
                            </label>
                        </td>
                    </tr>

                    <?php
                    /**
                     *  Possibilité d'ajouter à un groupe, si il y en a
                     */
                    if (!empty($groupList)) : ?>
                        <tr>
                            <td class="td-30">
                                <span>Add to group</span>
                                <span class="lowopacity">(optionnal)</span>
                            </td>
                            <td>
                                <select class="operation_param" param-name="targetGroup" package-type="all" >
                                    <option value="">Select group...</option>
                                    <?php
                                    foreach ($groupList as $groupName) {
                                        echo '<option value="' . $groupName . '">' . $groupName . '</option>';
                                    } ?>
                                </select>
                            </td>
                        </tr>
                    <?php endif ?>

                    <tr field-type="mirror rpm deb">
                        <td colspan="100%"><b>Advanced parameters</b></td>
                    </tr>

                    <tr field-type="mirror local rpm deb">
                        <td class="td-30">Architecture</td>
                        <td field-type="mirror local rpm">
                            <select class="targetArchSelect operation_param" param-name="targetArch" package-type="rpm" multiple>
                                <option value="">Select architecture...</option>
                                <option value="x86_64" <?php echo (in_array('x86_64', RPM_DEFAULT_ARCH)) ? 'selected' : ''; ?>>x86_64</option>
                                <option value="i386" <?php echo (in_array('i386', RPM_DEFAULT_ARCH)) ? 'selected' : ''; ?>>i386</option>
                                <option value="noarch" <?php echo (in_array('noarch', RPM_DEFAULT_ARCH)) ? 'selected' : ''; ?>>noarch</option>
                                <option value="aarch64" <?php echo (in_array('aarch64', RPM_DEFAULT_ARCH)) ? 'selected' : ''; ?>>aarch64</option>
                                <option value="ppc64le" <?php echo (in_array('ppc64le', RPM_DEFAULT_ARCH)) ? 'selected' : ''; ?>>ppc64le</option>
                            </select>
                        </td>

                        <td field-type="mirror local deb">
                            <select class="targetArchSelect operation_param" param-name="targetArch" package-type="deb" multiple>
                                <option value="">Select architecture...</option>
                                <option value="i386" <?php echo (in_array('i386', DEB_DEFAULT_ARCH)) ? 'selected' : ''; ?>>i386</option>
                                <option value="amd64" <?php echo (in_array('amd64', DEB_DEFAULT_ARCH)) ? 'selected' : ''; ?>>amd64</option>
                                <option value="armhf" <?php echo (in_array('armhf', DEB_DEFAULT_ARCH)) ? 'selected' : ''; ?>>armhf</option>
                                <option value="arm64" <?php echo (in_array('arm64', DEB_DEFAULT_ARCH)) ? 'selected' : ''; ?>>arm64</option>
                                <option value="armel" <?php echo (in_array('armel', DEB_DEFAULT_ARCH)) ? 'selected' : ''; ?>>armel</option>
                                <option value="mips" <?php echo (in_array('mips', DEB_DEFAULT_ARCH)) ? 'selected' : ''; ?>>mips</option>
                                <option value="mipsel" <?php echo (in_array('mipsel', DEB_DEFAULT_ARCH)) ? 'selected' : ''; ?>>mipsel</option>
                                <option value="mips64el" <?php echo (in_array('mips64el', DEB_DEFAULT_ARCH)) ? 'selected' : ''; ?>>mips64el</option>
                                <option value="ppc64el" <?php echo (in_array('ppc64el', DEB_DEFAULT_ARCH)) ? 'selected' : ''; ?>>ppc64el</option>
                                <option value="s390x" <?php echo (in_array('s390x', DEB_DEFAULT_ARCH)) ? 'selected' : ''; ?>>s390x</option>
                            </select>
                        </td>
                    </tr>

                    <tr field-type="mirror rpm deb">
                        <td class="td-30">Include sources packages</td>
                        <td>
                            <label field-type="mirror rpm" class="onoff-switch-label">
                                <input name="repoIncludeSource" type="checkbox" class="onoff-switch-input operation_param" value="yes" param-name="targetSourcePackage" package-type="rpm" <?php echo (RPM_INCLUDE_SOURCE == 'true') ? 'checked' : ''; ?> />
                                <span class="onoff-switch-slider"></span>
                            </label>
                            <label field-type="mirror deb" class="onoff-switch-label">
                                <input field-type="mirror deb" name="repoIncludeSource" type="checkbox" class="onoff-switch-input operation_param" value="yes" param-name="targetSourcePackage" package-type="deb" <?php echo (DEB_INCLUDE_SOURCE == 'true') ? 'checked' : ''; ?> />
                                <span class="onoff-switch-slider"></span>
                            </label>
                        </td>
                    </tr>

                    <!-- <tr field-type="mirror deb">
                        <td class="td-30">Include translation</td>
                        <td>
                            <select id="targetPackageTranslationSelect" class="operation_param" param-name="targetPackageTranslation" package-type="deb" multiple>
                                <option value="">Select translation(s)...</option>
                                <option value="en" <?php //echo (in_array('en', DEB_DEFAULT_TRANSLATION)) ? 'selected' : ''; ?>>en (english)</option>
                                <option value="fr" <?php //echo (in_array('fr', DEB_DEFAULT_TRANSLATION)) ? 'selected' : ''; ?>>fr (french)</option>
                                <option value="de" <?php //echo (in_array('de', DEB_DEFAULT_TRANSLATION)) ? 'selected' : ''; ?>>de (deutsch)</option>
                                <option value="it" <?php //echo (in_array('it', DEB_DEFAULT_TRANSLATION)) ? 'selected' : ''; ?>>it (italian)</option>
                            </select>
                        </td>
                    </tr> -->
                </table>
            </div>
            
            <br>
            <button class="btn-large-red">Confirm and execute<img src="resources/icons/rocket.svg" class="icon" /></button>

        </form>
    </div>
</div>