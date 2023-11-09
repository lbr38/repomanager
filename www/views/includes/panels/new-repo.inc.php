<?php ob_start(); ?>
       
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
                            <?php
                        elseif (RPM_REPO == 'true') : ?>
                            <input type="radio" id="packageType_rpm" class="operation_param" param-name="packageType" name="packageType" value="rpm" checked />
                            <label for="packageType_rpm">rpm</label>     
                            <?php
                        elseif (DEB_REPO == 'true') : ?>
                            <input type="radio" id="packageType_deb" class="operation_param" param-name="packageType" name="packageType" value="deb" checked />
                            <label for="packageType_deb">deb</label> 
                            <?php
                        endif ?>
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
                    <?php
                    if (RPM_REPO == 'true') : ?>
                        <select id="repoSourceSelect" class="operation_param" param-name="source" field-type="mirror rpm" package-type="rpm">
                            <option value="">Select a source repo...</option>
                            <?php
                            if (!empty($newRepoRpmSourcesList)) {
                                foreach ($newRepoRpmSourcesList as $source) {
                                    echo '<option value="' . $source['Name'] . '">' . $source['Name'] . '</option>';
                                }
                            } ?>
                        </select>
                        <?php
                    endif;

                    if (DEB_REPO == 'true') : ?>
                        <select id="repoSourceSelect" class="operation_param" param-name="source" field-type="mirror deb" package-type="deb">
                            <option value="">Select a source repo...</option>
                            <?php
                            if (!empty($newRepoDebSourcesList)) {
                                foreach ($newRepoDebSourcesList as $source) {
                                    echo '<option value="' . $source['Name'] . '">' . $source['Name'] . '</option>';
                                }
                            } ?>
                        </select>
                        <?php
                    endif ?>
                </td>
            </tr>
            <tr>
                <td class="td-30" field-type="mirror rpm deb">
                    <span>Custom repo name</span>
                    <span class="lowopacity-cst">(optionnal)</span>
                </td>
                <td class="td-30" field-type="local rpm deb">Repo name</td>
                <td>
                    <input type="text" class="operation_param" param-name="alias" package-type="all" />
                </td>
            </tr>
            <tr field-type="mirror local rpm">
                <td class="td-30">Release version</td>
                <td>
                    <select class="operation_param" param-name="releasever" package-type="rpm" multiple>
                        <option value="7">7 (Redhat 7 and derivatives)</option>
                        <option value="8">8 (Redhat 8 and derivatives)</option>
                        <option value="9">9 (Redhat 9 and derivatives)</option>
                    </select>
                </td>
            </tr>
            <tr field-type="mirror local deb">
                <td class="td-30">Distribution</td>
                <td>
                    <select class="operation_param" param-name="dist" package-type="deb" multiple>
                        <optgroup label="Debian">
                            <option value="stretch">stretch (Debian 9)</option>
                            <option value="buster">buster (Debian 10)</option>
                            <option value="bullseye">bullseye (Debian 11)</option>
                            <option value="bookworm">bookworm (Debian 12)</option>
                        </optgroup>
                        <optgroup label="Ubuntu">
                            <option value="focal">focal (Ubuntu 20.04)</option>
                            <option value="hirsute">hirsute (Ubuntu 21.04)</option>
                            <option value="jammy">jammy (Ubuntu 22.04)</option>
                        </optgroup>
                    </select>
                </td>
            </tr>

            <tr field-type="mirror local deb">
                <td class="td-30">Section</td>
                <td>
                    <select class="operation_param" param-name="section" package-type="deb" multiple>
                        <option value="main">main</option>
                        <option value="contrib">contrib</option>
                        <option value="non-free">non-free</option>
                    </select>
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
                    <span class="lowopacity-cst">(optionnal)</span>
                </td>
                <td><input type="text" class="operation_param" param-name="targetDescription" package-type="all" /></td>
            </tr>

            <?php
            /**
             *  Possibility to add to a group, if there is at least one group
             */
            if (!empty($newRepoFormGroupList)) : ?>
                <tr>
                    <td class="td-30">
                        <span>Add to group</span>
                        <span class="lowopacity-cst">(optionnal)</span>
                    </td>
                    <td>
                        <select class="operation_param" param-name="targetGroup" package-type="all" >
                            <option value="">Select group...</option>
                            <?php
                            foreach ($newRepoFormGroupList as $groupName) {
                                echo '<option value="' . $groupName . '">' . $groupName . '</option>';
                            } ?>
                        </select>
                    </td>
                </tr>
                <?php
            endif ?>

            <tr field-type="mirror rpm deb">
                <td colspan="100%"><b>GPG parameters</b></td>
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

            <tr field-type="mirror rpm deb">
                <td colspan="100%"><b>Advanced parameters</b></td>
            </tr>

            <tr field-type="mirror local rpm deb">
                <td class="td-30">Architecture</td>
                <td field-type="mirror local rpm">
                    <select class="targetArchSelect operation_param" param-name="targetArch" package-type="rpm" multiple>
                        <?php
                        foreach (RPM_ARCHS as $arch) {
                            if (in_array($arch, RPM_DEFAULT_ARCH)) {
                                echo '<option value="' . $arch . '" selected>' . $arch . '</option>';
                            } else {
                                echo '<option value="' . $arch . '">' . $arch . '</option>';
                            }
                        } ?>
                    </select>
                </td>
                <td field-type="mirror local deb">
                    <select class="targetArchSelect operation_param" param-name="targetArch" package-type="deb" multiple>
                        <?php
                        foreach (DEB_ARCHS as $arch) {
                            if (in_array($arch, DEB_DEFAULT_ARCH)) {
                                echo '<option value="' . $arch . '" selected>' . $arch . '</option>';
                            } else {
                                echo '<option value="' . $arch . '">' . $arch . '</option>';
                            }
                        } ?>
                    </select>
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
    <button class="btn-large-red">Confirm and execute<img src="/assets/icons/rocket.svg" class="icon" /></button>
</form>

<?php
$content = ob_get_clean();
$slidePanelName = 'new-repo';
$slidePanelTitle = 'CREATE A NEW REPO';

include(ROOT . '/views/includes/slide-panel.inc.php');
