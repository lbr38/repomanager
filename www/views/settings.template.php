<section class="mainSectionLeft">
    
    <h3>MAIN CONFIGURATION</h3>

    <div id="settingsDiv">
        <form id="settingsForm" autocomplete="off">
            <div class="div-generic-blue">
                <!-- <input type="hidden" name="action" value="applyConfiguration" /> -->

                <div class="settings-div">
                    <div>
                        <img src="resources/icons/info.svg" class="icon-verylowopacity" title="OS family of this Repomanager server" />
                    </div>
                    <div>
                        <p>OS family</p>
                    </div>
                    <div>
                        <p><?= OS_FAMILY ?></p>
                    </div>
                    <div>
                        <?php
                        if (empty(OS_FAMILY)) {
                            echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                        } ?>
                    </div>
                </div>

                <div class="settings-div">
                    <div>
                        <img src="resources/icons/info.svg" class="icon-verylowopacity" title="OS name and release version of this Repomanager server" />
                    </div>
                    <div>
                        <p>OS name</p>
                    </div>
                    <div>
                        <p><?= OS_NAME . ' ' . OS_VERSION ?></p>
                    </div>
                    <div>
                        <?php
                        if (empty(OS_NAME)) {
                            echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                        } ?>
                    </div>
                </div>

                <div class="settings-div">
                    <div>
                        <img src="resources/icons/info.svg" class="icon-verylowopacity" title="If enabled, repomanager will automatically update to the new available release." />
                    </div>
                    <div>
                        <p>Automatic update</p>
                    </div>
                    <div>
                        <label class="onoff-switch-label">
                        <input class="settings-param onoff-switch-input" param-name="updateAuto" type="checkbox" value="yes" <?php echo (UPDATE_AUTO == "true") ? 'checked' : ''; ?>>
                        <span class="onoff-switch-slider"></span>
                        </label>
                    </div>
                    <div>
                        <?php
                        if (empty(UPDATE_AUTO)) {
                            echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                        } ?>
                    </div>
                </div>

                <div class="settings-div">
                    <div>
                        <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Specify from which target git branch updates must be applied (generally stable)." />
                    </div>
                    <div>
                        <p>Update target branch</p>
                    </div>
                    <div>
                        <select class="settings-param" param-name="updateBranch">
                            <option value="stable" <?php echo (UPDATE_BRANCH == "stable") ? 'selected' : ''; ?>>stable</option>
                            <option value="dev" <?php echo (UPDATE_BRANCH == "devel") ? 'selected' : ''; ?>>devel</option>
                        </select>
                    </div>
                    <div>
                        <?php
                        if (empty(UPDATE_BRANCH)) {
                            echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                        } ?>
                    </div>
                    <div>
                        <?php
                        if (UPDATE_AVAILABLE == "true") : ?>
                            <span>New release available: </span>
                            <span id="update-repomanager-btn" release="<?= GIT_VERSION ?>" class="btn-medium-green" title="Update repomanager to: <?= GIT_VERSION ?>">Update now to <?= GIT_VERSION ?></span>
                            <?php
                        endif ?>
                    </div>
                </div>

                <div class="settings-div">
                    <div>
                        <img src="resources/icons/info.svg" class="icon-verylowopacity" title="If enabled, a backup of repomanager will be created before each update in specified directory." />
                    </div>
                    <div>
                        <p>Backup before update</p>
                    </div>
                    <div>
                        <label class="onoff-switch-label">
                            <input class="settings-param onoff-switch-input" param-name="updateBackup" type="checkbox" value="yes" <?php echo (UPDATE_BACKUP_ENABLED == "true") ? 'checked' : ''; ?>>
                            <span class="onoff-switch-slider"></span>
                        </label>
                    </div>
                    <div>
                        <?php
                        if (empty(UPDATE_BACKUP_ENABLED)) {
                            echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                        } ?>
                    </div>
                </div>

                <?php
                if (UPDATE_BACKUP_ENABLED == "true") : ?>
                    <div class="settings-div">
                        <div>
                            <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Repomanager backup before update target directory." />
                        </div>
                        <div>
                            <p>Backup before update target directory</p>
                        </div>
                        <div>
                            <input class="settings-param" param-name="updateBackupDir" type="text" value="<?= BACKUP_DIR ?>">
                        </div>
                        <div>
                            <?php
                            if (empty(BACKUP_DIR)) {
                                echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                            } ?>
                        </div>
                    </div>
                    <?php
                endif ?>

                <div class="settings-div">
                    <div>
                        <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Specify email recipient(s) that will receive plan error/success notifications and plan reminder notifications. You can specify multiple recipients separated by a comma." />
                    </div>
                    <div>
                        <p>Default contact</p>
                    </div>
                    <div>
                        <input class="settings-param" param-name="emailDest" type="text" value="<?= EMAIL_DEST ?>">
                    </div>
                    <div>
                        <?php
                        if (empty(EMAIL_DEST)) {
                            echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                        } ?>
                    </div>
                </div>
            </div>

            <h3>REPOSITORIES</h3>

            <div class="div-generic-blue">
                <div class="settings-div">
                    <div>
                        <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Specify storage directory for repositories created by repomanager." />
                    </div>
                    <div>
                        <p>Repositories storage directory</p>
                    </div>
                    <div>
                        <input class="settings-param" param-name="reposDir" type="text" value="<?= REPOS_DIR ?>" />
                    </div>
                    <div>
                        <?php
                        if (empty(REPOS_DIR)) {
                            echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                        } ?>
                    </div>
                </div>

                <div class="settings-div">
                    <div>
                        <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Enabled repositories access, size and packages statistics. Require a read-only access to webserver access logs (only nginx access logs supported) for <?= WWW_USER ?>." />
                    </div>
                    <div>
                        <p>Enable statistics</p>
                    </div>
                    <div>
                        <label class="onoff-switch-label">
                            <input class="settings-param onoff-switch-input" param-name="cronStatsEnable" type="checkbox" value="yes" <?php echo (STATS_ENABLED == "true") ? 'checked' : ''; ?>>
                            <span class="onoff-switch-slider"></span>
                        </label>
                    </div>
                    <div>
                        <?php
                        if (empty(STATS_ENABLED)) {
                            echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                        } ?>        
                    </div>
                </div>

                <?php
                if (STATS_ENABLED == "true") : ?>
                    <div class="settings-div">
                        <div>
                            <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Path to webserver access log (containing repomanager access logs). This file will be parsed to retieve repo access and generate statistics." />
                        </div>
                        <div>
                            <p>Path to access log to scan for statistics</p>
                        </div>
                        <div>
                            <input class="settings-param" param-name="statsLogPath" type="text" value="<?= STATS_LOG_PATH ?>" />
                        </div>
                        <div>
                            <?php
                            if (empty(STATS_LOG_PATH)) {
                                echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                            }
                            if (!file_exists(STATS_LOG_PATH)) {
                                echo '<img src="resources/icons/warning.png" class="icon" title="File not found." />';
                            }
                            if (!is_readable(STATS_LOG_PATH)) {
                                echo '<img src="resources/icons/warning.png" class="icon" title="File is not readable." />';
                            } ?>
                        </div>
                    </div>
                    <?php
                endif ?>

                <h5>RPM</h5>

                <div class="settings-div">
                    <div>
                        <img src="resources/icons/info.svg" class="icon-verylowopacity" title="If enabled, this server will manage and serve .rpm packages repositories">
                    </div>
                    <div>
                        <p>Enable RPM repositories</p>
                    </div>
                    <div>
                        <label class="onoff-switch-label">
                            <input class="settings-param onoff-switch-input" param-name="rpmRepo" type="checkbox" value="true" <?php echo (RPM_REPO == 'true') ? 'checked' : ''; ?>>
                            <span class="onoff-switch-slider"></span>
                        </label>
                    </div>
                </div>

                <?php
                if (RPM_REPO == 'true') : ?>
                    <div class="settings-div">
                        <div>
                            <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Sign RPM repos' packages with a GPG key.">
                        </div>
                        <div>
                            <p>Sign packages with GPG</p>
                        </div>
                        <div>
                            <label class="onoff-switch-label">
                                <input class="settings-param onoff-switch-input" param-name="rpmSignPackages" type="checkbox" value="yes" <?php echo (RPM_SIGN_PACKAGES == "true") ? 'checked' : ''; ?>>
                                <span class="onoff-switch-slider"></span>
                            </label>
                        </div>
                        <div>
                            <?php
                            if (empty(RPM_SIGN_PACKAGES)) {
                                echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                            } ?>
                        </div>
                    </div>

                    <?php
                    if (RPM_SIGN_PACKAGES == 'true') : ?>
                        <div class="settings-div">
                            <div>
                                <img src="resources/icons/info.svg" class="icon-verylowopacity" title="GPG key email address identifier. Needed to sign packages.">
                            </div>
                            <div>
                                <p>GPG key email address identifier</p>
                            </div>
                            <div>
                                <input class="settings-param" param-name="rpmGpgKeyID" type="email" value="<?= RPM_SIGN_GPG_KEYID ?>">
                            </div>
                            <div>
                                <?php
                                if (empty(RPM_SIGN_GPG_KEYID)) {
                                    echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                                } ?>
                            </div>
                        </div>

                        <div class="settings-div">
                            <div>
                                <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Specify which tool will be used to sign packages. (Pleas use rpmsign on recent systems. Use rpmresign on old RHEL (version 7).">
                            </div>
                            <div>
                                <p>GPG signature method</p>
                            </div>
                            <div>
                                <select class="settings-param" param-name="rpmSignMethod">
                                    <option value="rpmsign" <?php echo (RPM_SIGN_METHOD == 'rpmsign' ? 'selected' : '') ?>>rpmsign</option>
                                    <option value="rpmresign" <?php echo (RPM_SIGN_METHOD == 'rpmresign' ? 'selected' : '') ?>>rpmresign (RPM4 perl module)</option>
                                </select>
                            </div>
                            <div>
                                <?php
                                if (empty(RPM_SIGN_METHOD)) {
                                    echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                                } ?>
                            </div>
                        </div>
                        <?php
                    endif ?>

                    <div class="settings-div">
                        <div>
                            <img src="resources/icons/info.svg" class="icon-verylowopacity" title="This server will create and serve repos for RHEL/CentOS release <?= RELEASEVER ?>. Be careful, if modified, this value will globally affect yum and own local yum updates of this server (if this server is RHEL/CentOS)." />
                        </div>
                        <div>
                            <p>Release version</p>
                        </div>
                        <div>
                            <input class="settings-param" param-name="releasever" type="text" value="<?= RELEASEVER ?>">
                        </div>
                        <div>
                            <?php
                            if (empty(RELEASEVER)) {
                                echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                            } ?>
                        </div>
                    </div>

                    <div class="settings-div">
                        <div>
                            <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Select default package architecture to use when creating rpm mirror.">
                        </div>
                        <div>
                            <p>Default package architecture</p>
                        </div>
                        <div>
                            <select id="rpmArchitectureSelect" class="settings-param" param-name="rpmDefaultArchitecture" multiple>
                                <option value="x86_64" <?php echo (in_array('x86_64', RPM_DEFAULT_ARCH)) ? 'selected' : ''; ?>>x86_64</option>
                                <option value="i386" <?php echo (in_array('i386', RPM_DEFAULT_ARCH)) ? 'selected' : ''; ?>>i386</option>
                                <option value="noarch" <?php echo (in_array('noarch', RPM_DEFAULT_ARCH)) ? 'selected' : ''; ?>>noarch</option>
                                <option value="aarch64" <?php echo (in_array('aarch64', RPM_DEFAULT_ARCH)) ? 'selected' : ''; ?>>aarch64</option>
                                <option value="ppc64le" <?php echo (in_array('ppc64le', RPM_DEFAULT_ARCH)) ? 'selected' : ''; ?>>ppc64le</option>
                            </select>
                        </div>
                    </div>

                    <div class="settings-div">
                        <div>
                            <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Retrieve and include packages sources when creating rpm mirror.">
                        </div>
                        <div>
                            <p>Include sources packages when creating rpm mirror</p>
                        </div>
                        <div>
                            <label class="onoff-switch-label">
                                <input class="settings-param onoff-switch-input" param-name="rpmIncludeSource" type="checkbox" value="yes" <?php echo (RPM_INCLUDE_SOURCE == "true") ? 'checked' : ''; ?>>
                                <span class="onoff-switch-slider"></span>
                            </label>
                        </div>
                    </div>
                    <?php
                endif ?>

                <h5>DEB</h5>

                <div class="settings-div">
                    <div>
                        <img src="resources/icons/info.svg" class="icon-verylowopacity" title="If enabled, this server will manage and serve .deb packages repositories">
                    </div>
                    <div>
                        <p>Enable DEB repositories</p>
                    </div>
                    <div>
                        <label class="onoff-switch-label">
                            <input class="settings-param onoff-switch-input" param-name="debRepo" type="checkbox" value="true" <?php echo (DEB_REPO == 'true') ? 'checked' : ''; ?>>
                            <span class="onoff-switch-slider"></span>
                        </label>
                    </div>
                </div>

                <?php
                if (DEB_REPO == 'true') : ?>
                    <div class="settings-div">
                        <div>
                            <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Sign DEB repos with a GPG key.">
                        </div>
                        <div>
                            <p>Sign repos with GPG</p>
                        </div>
                        <div>
                            <label class="onoff-switch-label">
                                <input class="settings-param onoff-switch-input" param-name="debSignRepo" type="checkbox" value="yes" <?php echo (DEB_SIGN_REPO == "true") ? 'checked' : ''; ?>>
                                <span class="onoff-switch-slider"></span>
                            </label>
                        </div>
                        <div>
                            <?php
                            if (empty(DEB_SIGN_REPO)) {
                                echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                            } ?>
                        </div>
                    </div>

                    <?php
                    if (DEB_SIGN_REPO == 'true') : ?>
                        <div class="settings-div">
                            <div>
                                <img src="resources/icons/info.svg" class="icon-verylowopacity" title="GPG key email address identifier. Needed to sign repositories.">
                            </div>
                            <div>
                                <p>GPG key email address identifier</p>
                            </div>
                            <div>
                                <input class="settings-param" param-name="debGpgKeyID" type="text" value="<?= DEB_SIGN_GPG_KEYID ?>">
                            </div>
                            <div>
                                <?php
                                if (empty(DEB_SIGN_GPG_KEYID)) {
                                    echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                                } ?>
                            </div>
                        </div>
                        <?php
                    endif ?>

                    <div class="settings-div">
                        <div>
                            <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Select default package architecture to use when creating deb mirror.">
                        </div>
                        <div>
                            <p>Default package architecture</p>
                        </div>
                        <div>
                            <select id="debArchitectureSelect" class="settings-param" param-name="debDefaultArchitecture" multiple>
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
                        </div>
                    </div>

                    <div class="settings-div">
                        <div>
                            <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Include sources packages when creating deb mirror.">
                        </div>
                        <div>
                            <p>Include sources packages when creating deb mirror</p>
                        </div>
                        <div>
                            <label class="onoff-switch-label">
                                <input class="settings-param onoff-switch-input" param-name="debIncludeSource" type="checkbox" value="yes" <?php echo (DEB_INCLUDE_SOURCE == "true") ? 'checked' : ''; ?>>
                                <span class="onoff-switch-slider"></span>
                            </label>
                        </div>
                    </div>

                    <div class="settings-div">
                        <div>
                            <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Include packages specific translation when creating deb mirror.">
                        </div>
                        <div>
                            <p>Include translation(s) when creating deb mirror</p>
                        </div>
                        <div>
                            <select id="debTranslationSelect" class="settings-param" param-name="debDefaultTranslation" multiple>
                                <option value="en" <?php echo (in_array('en', DEB_DEFAULT_TRANSLATION)) ? 'selected' : ''; ?>>en (english)</option>
                                <option value="fr" <?php echo (in_array('fr', DEB_DEFAULT_TRANSLATION)) ? 'selected' : ''; ?>>fr (french)</option>
                                <option value="de" <?php echo (in_array('de', DEB_DEFAULT_TRANSLATION)) ? 'selected' : ''; ?>>de (deutsch)</option>
                                <option value="it" <?php echo (in_array('it', DEB_DEFAULT_TRANSLATION)) ? 'selected' : ''; ?>>it (italian)</option>
                            </select>
                        </div>
                    </div>
                    <?php
                endif ?>

                <h5>ENVIRONMENTS</h5>
                <p>Configure repository environments</p>

                <div id="envDiv">
                    <?php
                    if (!empty(ENVS)) :
                        foreach (ENVS as $envName) : ?>
                            <div class="settings-div">
                                <div>
                                </div>
                                <div>
                                    <?php
                                    if ($envName == DEFAULT_ENV) {
                                        echo '<p>Default environment</p>';
                                    } ?>
                                </div>
                                <div>
                                    <input class="env-input" type="text" value="<?= $envName ?>" />
                                </div>
                                <div>
                                    <img src="resources/icons/bin.svg" class="delete-env-btn icon-lowopacity" env-name="<?= $envName ?>" title="Delete environment <?= $envName ?>"/>
                                </div>
                            </div>
                            <?php
                        endforeach;
                    endif;

                    if (empty(ENVS)) {
                        echo '<img src="resources/icons/warning.png" class="icon" title="At least 1 environment must be configured." /> At least 1 environment must be configured.';
                    } ?>
                </div>

                <div class="settings-div">
                    <div></div>
                    <div></div>
                    <div>
                        <input class="env-input" type="text" placeholder="Add new environment" /> 
                    </div>
                    <div>
                        <button id="edit-env-btn" type="button" class="btn-xxsmall-green">+</button>
                    </div>
                </div>
            </div>

            <h3>WEB CONFIGURATION</h3>

            <div class="div-generic-blue">
                <div class="settings-div">
                    <div>
                        <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Specify Linux web dedicated user that execute this web server. Usually www-data or nginx." />
                    </div>
                    <div>
                        <p>Web user</p>
                    </div>
                    <div>
                        <input class="settings-param" param-name="wwwUser" type="text" value="<?= WWW_USER ?>">
                    </div>
                    <div>
                        <?php
                        if (empty(WWW_USER)) {
                            echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                        } ?>
                    </div>
                </div>

                <div class="settings-div">
                    <div>
                        <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Repomanager dedicated hostname." />
                    </div>
                    <div>
                        <p>Hostname</p>
                    </div>
                    <div>
                        <input class="settings-param" param-name="wwwHostname" type="text" value="<?= WWW_HOSTNAME ?>">
                    </div>
                    <div>
                        <?php
                        if (empty(WWW_HOSTNAME)) {
                            echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                        } ?>
                    </div>
                </div>

                <div class="settings-div">
                    <div>
                        <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Specify target URL for Repomanager's repos root directory. Usually http://.../repo" />
                    </div>
                    <div>
                        <p>Repos URL</p>
                    </div>
                    <div>
                        <input class="settings-param" param-name="wwwReposDirUrl" type="text" value="<?= WWW_REPOS_DIR_URL ?>">
                    </div>
                    <div>
                        <?php
                        if (empty(WWW_REPOS_DIR_URL)) {
                            echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                        } ?>
                    </div>
                </div>
            </div>

            <h3>HOSTS MANAGEMENT</h3>

            <div class="div-generic-blue">
                <div class="settings-div">
                    <div>
                        <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Enable hosts managing. For hosts using linupdate." />
                    </div>
                    <div>
                        <p>Manage hosts</p>
                    </div>
                    <div>
                        <label class="onoff-switch-label">
                            <input class="settings-param onoff-switch-input" param-name="manageHosts" type="checkbox" value="yes" <?php echo (MANAGE_HOSTS == "true") ? 'checked' : ''; ?>>
                            <span class="onoff-switch-slider"></span>
                        </label>
                    </div>
                    <div>
                        <?php
                        if (empty(MANAGE_HOSTS)) {
                            echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                        } ?>
                    </div>
                </div>

                <div class="settings-div">
                    <div>
                        <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Enable hosts profiles managing. For hosts using linupdate." />
                    </div>
                    <div>
                        <p>Manage profiles</p>
                    </div>
                    <div>
                        <label class="onoff-switch-label">
                            <input class="settings-param onoff-switch-input" param-name="manageProfiles" type="checkbox" value="yes" <?php echo (MANAGE_PROFILES == "true") ? 'checked' : ''; ?>>
                            <span class="onoff-switch-slider"></span>
                        </label>
                    </div>
                    <div>
                        <?php
                        if (empty(MANAGE_PROFILES)) {
                            echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                        } ?>
                    </div>
                </div>

                <?php
                if (MANAGE_PROFILES == "true") : ?>
                    <div class="settings-div">
                        <div>
                            <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Prefix that can be added to repo's configuration file name (e.g. 'myprefix-debian.list')." />
                        </div>
                        <div>
                            <p>Repo file name prefix</p>
                        </div>
                        <div>
                            <input class="settings-param" param-name="repoConfPrefix" type="text" value="<?= REPO_CONF_FILES_PREFIX ?>">
                        </div>
                    </div>
                    <?php
                endif ?>
            </div>

            <h3>PLANIFICATIONS</h3>

            <div class="div-generic-blue">
                <div class="settings-div">
                    <div>
                        <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Enable planifications" />
                    </div>
                    <div>
                        <p>Enable plans</p>
                    </div>
                    <div>
                        <label class="onoff-switch-label">
                            <input class="settings-param onoff-switch-input" param-name="automatisationEnable" type="checkbox" value="yes" <?php echo (PLANS_ENABLED == "true") ? 'checked' : ''; ?>>
                            <span class="onoff-switch-slider"></span>
                        </label>
                    </div>
                    <div>
                        <?php
                        if (empty(PLANS_ENABLED)) {
                            echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                        } ?>
                    </div>
                </div>

                <?php
                if (PLANS_ENABLED == "true") : ?>
                    <div class="settings-div">
                        <div>
                            <img src="resources/icons/info.svg" class="icon-verylowopacity" title="If enabled, plans will be able to update repos by creating new repo snapshot on the planned day and time." />
                        </div>
                        <div>
                            <p>Allow automatic repos updates by plans</p>
                        </div>
                        <div>
                            <label class="onoff-switch-label">
                                <input class="settings-param onoff-switch-input" param-name="allowAutoUpdateRepos" type="checkbox" value="yes" <?php echo (ALLOW_AUTOUPDATE_REPOS == "true") ? 'checked' : ''; ?>>
                                <span class="onoff-switch-slider"></span>
                            </label>
                        </div>
                        <div>
                            <?php
                            if (empty(ALLOW_AUTOUPDATE_REPOS)) {
                                echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                            } ?>
                        </div>
                    </div>

                    <div class="settings-div">
                        <div>
                            <img src="resources/icons/info.svg" class="icon-verylowopacity" title="If enabled, plans will be able to delete oldest repos snapshots, depending on the specified retention parameter." />
                        </div>
                        <div>
                            <p>Allow automatic deletion of old repos snapshots</p>
                        </div>
                        <div>
                            <label class="onoff-switch-label">
                                <input class="settings-param onoff-switch-input" param-name="allowAutoDeleteArchivedRepos" type="checkbox" value="yes" <?php echo (ALLOW_AUTODELETE_ARCHIVED_REPOS == "true") ? 'checked' : ''; ?>>
                                <span class="onoff-switch-slider"></span>
                            </label>
                        </div>
                        <div>
                            <?php
                            if (empty(ALLOW_AUTODELETE_ARCHIVED_REPOS)) {
                                echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                            } ?>
                        </div>
                    </div>

                    <div class="settings-div">
                        <div>
                            <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Maximum number of snapshots to keep by repo, before deleting." />
                        </div>
                        <div>
                            <p>Retention</p>
                        </div>
                        <div>
                            <input class="settings-param" param-name="retention" type="number" value="<?= RETENTION ?>">
                        </div>
                        <div>
                            <?php
                            if (empty(RETENTION)) {
                                echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                            } ?>
                        </div>
                    </div>

                    <div class="settings-div">
                        <div>
                            <img src="resources/icons/info.svg" class="icon-verylowopacity" title="If enabled, specified email recipients will receive reminder(s) for each planned tasks to come. A mail configuration must be setted on this server (e.g. sendmail)." />
                        </div>
                        <div>
                            <p>Enable plan reminders</p>
                        </div>
                        <div>
                            <label class="onoff-switch-label">
                                <input class="settings-param onoff-switch-input" param-name="cronSendReminders" type="checkbox" value="yes" <?php echo (PLAN_REMINDERS_ENABLED == "true") ? 'checked' : ''; ?>>
                                <span class="onoff-switch-slider"></span>
                            </label>
                        </div>
                        <div>
                            <?php
                            if (empty(PLAN_REMINDERS_ENABLED)) {
                                echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                            } ?>
                        </div>
                    </div>
                    <?php
                endif ?>
            </div>
            <button type="submit" class="btn-medium-green">Save</button>
        </form>
    </div>
</section>

<section class="mainSectionRight">
    
    <h3>HEALTH</h3>

    <div class="div-generic-blue">
        <h5>DATABASES</h5>

        <div class="health-div">
            <div>
                <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Main database. Repomanager cannot run if this database is on error." />
            </div>
            <div>
                <p>Main</p>
            </div>
            <div>
                <p>
                    <span>Status</span>
                    <span>
                        <?php
                        $statusError = 0;
                        $statusMsg = '';

                        /**
                         *  Checking that database is readable and writable
                         */
                        if (!is_readable(DB) or !is_writable(DB)) {
                            $statusError++;
                            $statusMsg = 'Main database is not readable / writable.';
                        } else {
                            /**
                             *  Checking that all tables are present
                             */
                            $myconn = new \Models\Connection('main');

                            if (!$myconn->checkMainTables()) {
                                $statusError++;
                                $statusMsg = 'One or more table are missing.';
                            }
                        }

                        if ($statusError == 0) {
                            echo '<img src="resources/icons/greencircle.png" class="icon-small" />';
                        } else {
                            echo '<img src="resources/icons/redcircle.png" class="icon-small" />' . $statusMsg;
                        } ?>
                    </span>
                </p>
            </div>
        </div>

        <?php
        if (STATS_ENABLED == "true") : ?>
            <div class="health-div">
                <div>
                    <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Stats database." />
                </div>
                <div>
                    <p>Stats</p>
                </div>
                <div>
                    <p>
                        <span>Status</span>
                        <span>
                            <?php
                            $statusError = 0;
                            $statusMsg = '';

                            /**
                             *  Checking that database is readable and writable
                             */
                            if (!is_readable(STATS_DB) or !is_writable(STATS_DB)) {
                                $statusError++;
                                $statusMsg = 'Stats database is not readable / writable.';
                            } else {
                                /**
                                 *  Checking that all tables are present
                                 */
                                $myconn = new \Models\Connection('stats');

                                if (!$myconn->checkMainTables()) {
                                    $statusError++;
                                    $statusMsg = 'One or more table are missing.';
                                }
                            }

                            if ($statusError == 0) {
                                echo '<img src="resources/icons/greencircle.png" class="icon-small" />';
                            } else {
                                echo '<img src="resources/icons/redcircle.png" class="icon-small" />' . $statusMsg;
                            } ?>
                        </span>
                    </p>
                </div>
            </div>
            <?php
        endif;

        if (MANAGE_HOSTS == "true") : ?>
            <div class="health-div">
                <div>
                    <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Hosts database." />
                </div>
                <div>
                    <p>Hosts</p>
                </div>
                <div>
                    <p>
                        <span>Status</span>
                        <span>
                            <?php
                            $statusError = 0;
                            $statusMsg = '';

                            /**
                             *  Checking that database is readable and writable
                             */
                            if (!is_readable(HOSTS_DB) or !is_writable(HOSTS_DB)) {
                                $statusError++;
                                $statusMsg = 'Hosts database is not readable / writable.';
                            } else {
                                /**
                                 *  Checking that all tables are present
                                 */
                                $myconn = new \Models\Connection('hosts');

                                if (!$myconn->checkMainTables()) {
                                    $statusError++;
                                    $statusMsg = 'One or more table are missing.';
                                }
                            }

                            if ($statusError == 0) {
                                echo '<img src="resources/icons/greencircle.png" class="icon-small" />';
                            } else {
                                echo '<img src="resources/icons/redcircle.png" class="icon-small" />' . $statusMsg;
                            } ?>
                        </span>
                    </p>
                </div>
            </div>
            <?php
        endif ?>

        <h5>SERVICE</h5>

        <div class="health-div">
            <div>
                <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Systemd repomanager service is used to execute regular tasks such as applying permissions on repos dirs, executings plans (if enabled), sending plan reminders (if enabled)." />
            </div>
            <div>
                <p>Repomanager service</p>
            </div>
            <div>
                <p>
                    <span>Status</span>
                    <span>
                        <?php
                        if (SERVICE_RUNNING) {
                            echo '<img src="resources/icons/greencircle.png" class="icon-small" />';
                        } else {
                            echo '<img src="resources/icons/redcircle.png" class="icon-small" />Service is not running';
                        } ?>
                    </span>
                </p>
            </div>
        </div>
    </div>

    <?php
    /**
     *  This section is only accessible to super-administrator user
     */
    if (IS_SUPERADMIN) : ?>
        <h3>USERS</h3>

        <div id="usersDiv" class="div-generic-blue">
            <form id="newUserForm" autocomplete="off">
                <p>Create an user:</p>

                <input class="input-medium" type="text" name="username" placeholder="Username" />

                <select name="role" class="select-medium" required>
                    <option value="">Select role...</option>
                    <option value="usage">usage</option>
                    <option value="administrator">administrator</option>
                </select>

                <button class="btn-xxsmall-green" type="submit">+</button>
            </form>

            <div id="generatedPassword"></div>
   
            <div id="currentUsers">
                <?php
                if (!empty($users)) : ?>
                    <table class="table-generic-blue">
                        <tr class="no-bkg">
                            <td>Username</td>
                            <td>Role</td>
                            <td>Account type</td>
                            <td class="td-fit"></td>
                        </tr>
                        <?php
                        foreach ($users as $user) : ?>
                            <tr>
                                <td>
                                    <?= $user['Username'] ?>
                                </td>
                                <td>
                                    <?= $user['Role_name'] ?>
                                </td>
                                <td>
                                    <?= $user['Type'] ?>
                                </td>
                                <td class="td-fit">
                                    <?php
                                    if ($user['Username'] != 'admin') : ?>
                                        <span class="reset-password-btn" user-id="<?= $user['Id'] ?>" username="<?= $user['Username'] ?>" title="Reset password of user <?= $user['Username'] ?>"><img src="resources/icons/update.svg" class="icon-lowopacity" /></span>
                                        <span class="delete-user-btn" user-id="<?= $user['Id'] ?>" username="<?= $user['Username'] ?>" title="Delete user <?= $user['Username'] ?>"><img src="resources/icons/bin.svg" class="icon-lowopacity" /></span>
                                        <?php
                                    endif ?>
                                </td>
                            </tr>
                            <?php
                        endforeach ?>
                    </table>
                    <?php
                endif ?>
            </div>
        </div>
        <?php
    endif ?>
</section>