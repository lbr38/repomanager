<section class="mainSectionLeft">
    
    <h3>MAIN CONFIGURATION</h3>

    <form action="/settings" method="post" autocomplete="off">
        <div class="div-generic-blue">
            <input type="hidden" name="action" value="applyConfiguration" />
            <table class="table-medium">
                <tr>
                    <td class="td-large">
                        <img src="resources/icons/info.svg" class="icon-verylowopacity" title="" /> OS family
                    </td>
                    <td>
                        <input type="text" value="<?= OS_FAMILY ?>" readonly />
                    </td>
                    <td class="td-fit">
                        <?php
                        if (empty(OS_FAMILY)) {
                            echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                        } ?>
                    </td>
                </tr>
                <tr>
                    <td class="td-large">
                        <img src="resources/icons/info.svg" class="icon-verylowopacity" title="" /> OS name
                    </td>
                    <td>
                        <input type="text" value="<?= OS_NAME ?>" readonly />
                    </td>
                    <td class="td-fit">
                        <?php
                        if (empty(OS_NAME)) {
                            echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                        } ?>
                    </td>
                </tr>
                <tr>
                    <td class="td-large">
                        <img src="resources/icons/info.svg" class="icon-verylowopacity" title="" /> OS version
                    </td>
                    <td>
                        <input type="text" value="<?= OS_VERSION ?>" readonly />
                    </td>
                    <td class="td-fit">
                        <?php
                        if (empty(OS_VERSION)) {
                            echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                        } ?>
                    </td>
                </tr>
                <tr>
                    <td class="td-large">
                        <img src="resources/icons/info.svg" class="icon-verylowopacity" title="If enabled, repomanager will automatically update to the new available release." /> Automatic update
                    </td>
                    <td>
                        <label class="onoff-switch-label">
                        <input name="updateAuto" type="checkbox" class="onoff-switch-input" value="yes" <?php echo (UPDATE_AUTO == "yes") ? 'checked' : ''; ?>>
                        <span class="onoff-switch-slider"></span>
                        </label>
                    </td>
                    <td class="td-fit">
                        <?php
                        if (empty(UPDATE_AUTO)) {
                            echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                        } ?>
                    </td>
                </tr>
                <tr>
                    <td class="td-large">
                        <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Specify from which target git branch updates must be applied (generally stable)." /> Update target branch
                    </td>
                    <td>
                        <select name="updateBranch">
                            <option value="stable" <?php echo (UPDATE_BRANCH == "stable") ? 'selected' : ''; ?>>stable</option>
                            <option value="dev" <?php echo (UPDATE_BRANCH == "dev") ? 'selected' : ''; ?>>dev</option>
                        </select>
                    </td>
                    <?php
                    if (UPDATE_AVAILABLE == "yes") : ?>
                        <td class="td-fit">
                            <input type="button" onclick="location.href='/settings?action=update'" class="btn-xxsmall-green" title="Update repomanager to: <?= GIT_VERSION ?>" value="↻">
                        </td>
                        <?php
                    endif;

                    if (empty(UPDATE_BRANCH)) {
                        echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                    } ?>
                </tr>
                    <?php
                    if (!empty($updateStatus)) : ?>
                        <tr>
                            <td></td>
                            <td colspan="2"><?= $updateStatus ?></td>
                        </tr>
                        <?php
                    endif ?>
                </tr>
                <tr>
                    <td class="td-large">
                        <img src="resources/icons/info.svg" class="icon-verylowopacity" title="If enabled, a backup of repomanager will be created before each update in specified directory." /> Backup before update
                    </td>
                    <td>
                        <label class="onoff-switch-label">
                            <input name="updateBackup" type="checkbox" class="onoff-switch-input" value="yes" <?php echo (UPDATE_BACKUP_ENABLED == "yes") ? 'checked' : ''; ?>>
                            <span class="onoff-switch-slider"></span>
                        </label>
                    </td>
                    <td class="td-fit">
                        <?php
                        if (empty(UPDATE_BACKUP_ENABLED)) {
                            echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                        } ?>
                    </td>
                </tr>
                <?php
                if (UPDATE_BACKUP_ENABLED == "yes") : ?>
                    <tr>
                        <td class="td-large">
                            <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Repomanager backup before update target directory." /> Backup before update target directory
                        </td>
                        <td>
                            <input type="text" name="updateBackupDir" autocomplete="off" value="<?= BACKUP_DIR ?>">
                        </td>
                        </td>
                        <td class="td-fit">
                            <?php
                            if (empty(BACKUP_DIR)) {
                                echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                            } ?>
                        </td>
                    </tr>
                    <?php
                endif ?>
                <tr>
                    <td class="td-large">
                        <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Specify email recipient(s) that will receive plan error/success notifications and plan reminder notifications. You can specify multiple recipients separated by a comma." /> Contact
                    </td>
                    <td>
                        <input type="text" name="emailDest" autocomplete="off" value="<?= EMAIL_DEST ?>">
                    </td>
                    <td class="td-fit">
                        <?php
                        if (empty(EMAIL_DEST)) {
                            echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                        } ?>
                    </td>
                </tr>
            </table>
        </div>

        <h3>REPOSITORIES</h3>

        <div class="div-generic-blue">
            <table class="table-medium">
                <tr>
                    <td class="td-large">
                        <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Specify storage directory for repositories created by repomanager." /> Repositories storage directory
                    </td>
                    <td>
                        <input type="text" autocomplete="off" name="reposDir" value="<?= REPOS_DIR ?>" />
                    </td>
                    <td class="td-fit">
                        <?php
                        if (empty(REPOS_DIR)) {
                            echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                        } ?>
                    </td>
                </tr>
                <tr>
                    <td class="td-large">
                        <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Enabled repositories access, size and packages statistics. Require a read-only access to webserver access logs (only nginx access logs supported) for <?= WWW_USER ?>." /> Enable statistics
                    </td>
                    <td>
                        <label class="onoff-switch-label">
                            <input name="cronStatsEnable" type="checkbox" class="onoff-switch-input" value="yes" <?php echo (STATS_ENABLED == "yes") ? 'checked' : ''; ?>>
                            <span class="onoff-switch-slider"></span>
                        </label>
                    </td>
                    <td class="td-fit">
                        <?php
                        if (empty(STATS_ENABLED)) {
                            echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                        } ?>
                    </td>
                </tr>
                <?php
                if (STATS_ENABLED == "yes") : ?>
                    <tr>
                        <td class="td-large">
                            <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Path to webserver access log (containing repomanager access logs). This file will be parsed to retieve repo access and generate statistics." /> Path to access log to scan for statistics
                        </td>
                        <td>
                            <input type="text" autocomplete="off" name="statsLogPath" value="<?= STATS_LOG_PATH ?>" />
                        </td>
                        <td class="td-fit">
                            <?php
                            if (empty(STATS_LOG_PATH)) {
                                echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endif ?>
            </table>

            <h5>RPM</h5>

            <table class="table-medium">
                <tr>
                    <td class="td-large">
                        <img src="resources/icons/info.svg" class="icon-verylowopacity" title="If enabled, this server will manage and serve .rpm packages repositories"> Enable RPM repositories
                    </td>
                    <td>
                        <label class="onoff-switch-label">
                            <input name="rpmRepo" type="checkbox" class="onoff-switch-input" value="enabled" <?php echo (RPM_REPO == "enabled") ? 'checked' : ''; ?>>
                            <span class="onoff-switch-slider"></span>
                        </label>
                    </td>
                </tr>
                <?php if (RPM_REPO == "enabled") : ?>
                    <tr>
                        <td class="td-large">
                            <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Sign RPM repos' packages with a GPG key."> Sign packages with GPG
                        </td>
                        <td>
                            <label class="onoff-switch-label">
                                <input name="rpmSignPackages" type="checkbox" class="onoff-switch-input" value="yes" <?php echo (RPM_SIGN_PACKAGES == "yes") ? 'checked' : ''; ?>>
                                <span class="onoff-switch-slider"></span>
                            </label>
                        </td>
                        <?php
                        if (empty(RPM_SIGN_PACKAGES)) {
                            echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                        } ?>
                    </tr>

                    <?php if (RPM_SIGN_PACKAGES == 'yes') : ?>
                        <tr>
                            <td class="td-large">
                                <img src="resources/icons/info.svg" class="icon-verylowopacity" title="GPG key email address identifier. Needed to sign packages."> GPG key email address identifier
                            </td>
                            <td>
                                <input type="email" name="rpmGpgKeyID" autocomplete="off" value="<?= RPM_SIGN_GPG_KEYID ?>">
                            </td>
                            <td>
                                <?php
                                if (empty(RPM_SIGN_GPG_KEYID)) {
                                    echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                                } ?>
                            </td>
                        </tr>

                        <tr>
                            <td class="td-large">
                                <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Specify which tool will be used to sign packages. (Pleas use rpmsign on recent systems. Use rpmresign on old RHEL (version 7)."> GPG signature method
                            </td>
                            <td>
                                <select name="rpmSignMethod">
                                    <option value="rpmsign" <?php echo (RPM_SIGN_METHOD == 'rpmsign' ? 'selected' : '') ?>>rpmsign</option>
                                    <option value="rpmresign" <?php echo (RPM_SIGN_METHOD == 'rpmresign' ? 'selected' : '') ?>>rpmresign (RPM4 perl module)</option>
                                </select>
                            </td>
                            <?php
                            if (empty(RPM_SIGN_METHOD)) {
                                echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                            } ?>
                        </tr>
                    <?php endif ?>

                    <tr>
                        <td class="td-large">
                            <img src="resources/icons/info.svg" class="icon-verylowopacity" title="This server will create and serve repos for RHEL/CentOS release <?= RELEASEVER ?>. Be careful, if modified, this value will globally affect yum and own local yum updates of this server (if this server is RHEL/CentOS)." /> Release version
                        </td>
                        <td>
                            <input type="text" name="releasever" autocomplete="off" value="<?= RELEASEVER ?>">
                        </td>
                        <td class="td-fit">
                            <?php
                            if (empty(RELEASEVER)) {
                                echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                            }?>
                        </td>
                    </tr>
                    <tr>
                        <td class="td-large">
                            <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Select default package architecture to use when creating rpm mirror."> Default package architecture
                        </td>
                        <td>
                            <select id="rpmArchitectureSelect" name="rpmDefaultArchitecture[]" multiple>
                                <option value="x86_64" <?php echo (in_array('x86_64', RPM_DEFAULT_ARCH)) ? 'selected' : ''; ?>>x86_64</option>
                                <option value="i386" <?php echo (in_array('i386', RPM_DEFAULT_ARCH)) ? 'selected' : ''; ?>>i386</option>
                                <option value="noarch" <?php echo (in_array('noarch', RPM_DEFAULT_ARCH)) ? 'selected' : ''; ?>>noarch</option>
                                <option value="aarch64" <?php echo (in_array('aarch64', RPM_DEFAULT_ARCH)) ? 'selected' : ''; ?>>aarch64</option>
                                <option value="ppc64le" <?php echo (in_array('ppc64le', RPM_DEFAULT_ARCH)) ? 'selected' : ''; ?>>ppc64le</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="td-large">
                            <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Retrieve and include packages sources when creating rpm mirror."> Include sources packages when creating rpm mirror
                        </td>
                        <td>
                            <label class="onoff-switch-label">
                                <input name="rpmIncludeSource" type="checkbox" class="onoff-switch-input" value="yes" <?php echo (RPM_INCLUDE_SOURCE == "yes") ? 'checked' : ''; ?>>
                                <span class="onoff-switch-slider"></span>
                            </label>
                        </td>
                    </tr>
                <?php endif ?>
            </table>

            <h5>DEB</h5>

            <table class="table-medium">
                <tr>
                    <td class="td-large">
                        <img src="resources/icons/info.svg" class="icon-verylowopacity" title="If enabled, this server will manage and serve .deb packages repositories"> Enable DEB repositories
                    </td>
                    <td>
                        <label class="onoff-switch-label">
                            <input name="debRepo" type="checkbox" class="onoff-switch-input" value="enabled" <?php echo (DEB_REPO == "enabled") ? 'checked' : ''; ?>>
                            <span class="onoff-switch-slider"></span>
                        </label>
                    </td>
                </tr>
            
                <?php if (DEB_REPO == "enabled") : ?>
                    <tr>
                        <td class="td-large">
                            <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Sign DEB repos with a GPG key."> Sign repos with GPG
                        </td>
                        <td>
                            <label class="onoff-switch-label">
                                <input name="debSignRepo" type="checkbox" class="onoff-switch-input" value="yes" <?php echo (DEB_SIGN_REPO == "yes") ? 'checked' : ''; ?>>
                                <span class="onoff-switch-slider"></span>
                            </label>
                        </td>
                        <?php
                        if (empty(DEB_SIGN_REPO)) {
                            echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                        } ?>
                    </tr>

                    <?php if (DEB_SIGN_REPO == 'yes') : ?>
                        <tr>
                            <td class="td-large">
                                <img src="resources/icons/info.svg" class="icon-verylowopacity" title="GPG key email address identifier. Needed to sign repositories."> GPG key email address identifier
                            </td>
                            <td>
                                <input type="text" name="debGpgKeyID" autocomplete="off" value="<?= DEB_SIGN_GPG_KEYID ?>">
                            </td>
                            <td>
                                <?php
                                if (empty(DEB_SIGN_GPG_KEYID)) {
                                    echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                                } ?>
                            </td>
                        </tr>
                    <?php endif ?>
                    <tr>
                        <td class="td-large">
                            <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Select default package architecture to use when creating deb mirror."> Default package architecture
                        </td>
                        <td>
                            <select id="debArchitectureSelect" name="debDefaultArchitecture[]" multiple>
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
                    <tr>
                        <td class="td-large">
                            <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Include sources packages when creating deb mirror."> Include sources packages when creating deb mirror
                        </td>
                        <td>
                            <label class="onoff-switch-label">
                                <input name="debIncludeSource" type="checkbox" class="onoff-switch-input" value="yes" <?php echo (DEB_INCLUDE_SOURCE == "yes") ? 'checked' : ''; ?>>
                                <span class="onoff-switch-slider"></span>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class="td-large">
                            <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Include packages specific translation when creating deb mirror."> Include translation(s) when creating deb mirror
                        </td>
                        <td>
                            <select id="debTranslationSelect" name="debDefaultTranslation[]" multiple>
                                <option value="en" <?php echo (in_array('en', DEB_DEFAULT_TRANSLATION)) ? 'selected' : ''; ?>>en (english)</option>
                                <option value="fr" <?php echo (in_array('fr', DEB_DEFAULT_TRANSLATION)) ? 'selected' : ''; ?>>fr (french)</option>
                                <option value="de" <?php echo (in_array('de', DEB_DEFAULT_TRANSLATION)) ? 'selected' : ''; ?>>de (deutsch)</option>
                                <option value="it" <?php echo (in_array('it', DEB_DEFAULT_TRANSLATION)) ? 'selected' : ''; ?>>it (italian)</option>
                            </select>
                        </td>
                    </tr>
                <?php endif ?>
            </table>
        </div>

        <h3>WEB CONFIGURATION</h3>

        <div class="div-generic-blue">
            <table class="table-medium">
                <tr>
                    <td class="td-large">
                        <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Specify Linux web dedied user that execute this web server. Usually www-data or nginx." /> Web user
                    </td>
                    <td>
                        <input type="text" name="wwwUser" autocomplete="off" value="<?= WWW_USER ?>">
                    </td>
                    <td class="td-fit">
                        <?php
                        if (empty(WWW_USER)) {
                            echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                        } ?>
                    </td>
                </tr>
                <tr>
                    <td class="td-large">
                        <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Repomanager dedied hostname." /> Hostname
                    </td>
                    <td>
                        <input type="text" name="wwwHostname" autocomplete="off" value="<?= WWW_HOSTNAME ?>">
                    </td>
                    <td class="td-fit">
                        <?php
                        if (empty(WWW_HOSTNAME)) {
                            echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                        } ?>
                    </td>
                </tr>
                <tr>
                    <td class="td-large">
                        <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Specify target URL for Repomanager's repos root directory. Usually http://.../repo" /> Repos URL
                    </td>
                    <td>
                        <input type="text" name="wwwReposDirUrl" autocomplete="off" value="<?= WWW_REPOS_DIR_URL ?>">
                    </td>
                    <td class="td-fit">
                        <?php
                        if (empty(WWW_REPOS_DIR_URL)) {
                            echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                        } ?>
                    </td>
                </tr>
            </table>
        </div>

        <h3>HOSTS MANAGEMENT</h3>

        <div class="div-generic-blue">
            <table class="table-medium">
                <tr>
                    <td class="td-large">
                        <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Enable hosts managing. For hosts using linupdate." /> Manage hosts
                    </td>
                    <td>
                        <label class="onoff-switch-label">
                            <input name="manageHosts" type="checkbox" class="onoff-switch-input" value="yes" <?php echo (MANAGE_HOSTS == "yes") ? 'checked' : ''; ?>>
                            <span class="onoff-switch-slider"></span>
                        </label>
                    </td>
                    <td class="td-fit">
                        <?php
                        if (empty(MANAGE_HOSTS)) {
                            echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                        } ?>
                    </td>
                </tr>
                <tr>
                    <td class="td-large">
                        <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Enable hosts profiles managing. For hosts using linupdate." /> Manage profiles
                    </td>
                    <td>
                        <label class="onoff-switch-label">
                            <input name="manageProfiles" type="checkbox" class="onoff-switch-input" value="yes" <?php echo (MANAGE_PROFILES == "yes") ? 'checked' : ''; ?>>
                            <span class="onoff-switch-slider"></span>
                        </label>
                    </td>
                    <td class="td-fit">
                        <?php
                        if (empty(MANAGE_PROFILES)) {
                            echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                        } ?>
                    </td>
                </tr>
                <tr>
                    <?php
                    if (MANAGE_PROFILES == "yes") : ?>
                        <td class="td-large">
                            <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Prefix that can be added to repo's configuration file name (e.g. 'myprefix-debian.list')." /> Repo file name prefix
                        </td>
                        <td>
                            <input type="text" name="repoConfPrefix" autocomplete="off" value="<?= REPO_CONF_FILES_PREFIX ?>">
                        </td>
                        <?php
                    endif ?>
                </tr>
            </table>
        </div>

        <h3>PLANIFICATIONS</h3>

        <div class="div-generic-blue">
            <table class="table-medium">
                <tr>
                    <td class="td-large">
                        <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Enable planifications" /> Enable plans
                    </td>
                    <td>
                        <label class="onoff-switch-label">
                            <input name="automatisationEnable" type="checkbox" class="onoff-switch-input" value="yes" <?php echo (PLANS_ENABLED == "yes") ? 'checked' : ''; ?>>
                            <span class="onoff-switch-slider"></span>
                        </label>
                    </td>
                    <td class="td-fit">
                        <?php
                        if (empty(PLANS_ENABLED)) {
                            echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                        } ?>
                    </td>
                </tr>
                <?php
                if (PLANS_ENABLED == "yes") : ?>
                    <tr>
                        <td class="td-large">
                            <img src="resources/icons/info.svg" class="icon-verylowopacity" title="If enabled, plans will be able to update repos by creating new repo snapshot on the planned day and time." /> Allow automatic repos updates by plans
                        </td>
                        <td>
                            <label class="onoff-switch-label">
                                <input name="allowAutoUpdateRepos" type="checkbox" class="onoff-switch-input" value="yes" <?php echo (ALLOW_AUTOUPDATE_REPOS == "yes") ? 'checked' : ''; ?>>
                                <span class="onoff-switch-slider"></span>
                            </label>
                        </td>
                        <td class="td-fit">
                            <?php
                            if (empty(ALLOW_AUTOUPDATE_REPOS)) {
                                echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                            } ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="td-large">
                            <img src="resources/icons/info.svg" class="icon-verylowopacity" title="If enabled, plans will be able to delete oldest repos snapshots, depending on the specified retention parameter." /> Allow automatic deletion of old repos snapshots
                        </td>
                        <td>
                            <label class="onoff-switch-label">
                                <input name="allowAutoDeleteArchivedRepos" type="checkbox" class="onoff-switch-input" value="yes" <?php echo (ALLOW_AUTODELETE_ARCHIVED_REPOS == "yes") ? 'checked' : ''; ?>>
                                <span class="onoff-switch-slider"></span>
                            </label>
                        </td>
                        <td class="td-fit">
                            <?php
                            if (empty(ALLOW_AUTODELETE_ARCHIVED_REPOS)) {
                                echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                            } ?>
                        </td>
                    </tr> 
                    <tr>
                        <td class="td-large">
                            <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Maximum number of snapshots to keep by repo, before deleting." /> Retention
                        </td>
                        <td>
                            <input type="number" name="retention" autocomplete="off" value="<?= RETENTION ?>">
                        </td>
                        <td class="td-fit">
                            <?php
                            if (empty(RETENTION)) {
                                echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                            } ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="td-large">
                            <img src="resources/icons/info.svg" class="icon-verylowopacity" title="If enabled, specified email recipients will receive reminder(s) for each planned tasks to come. A mail configuration must be setted on this server (e.g. sendmail)." /> Enable plan reminders
                        </td>
                        <td>
                            <label class="onoff-switch-label">
                                <input name="cronSendReminders" type="checkbox" class="onoff-switch-input" value="yes" <?php echo (PLAN_REMINDERS_ENABLED == "yes") ? 'checked' : ''; ?>>
                                <span class="onoff-switch-slider"></span>
                            </label>
                        </td>
                        <td class="td-fit">
                            <?php
                            if (empty(PLAN_REMINDERS_ENABLED)) {
                                echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                            } ?>
                        </td>
                    </tr> 
                    <?php
                endif ?>
            </table>
        </div>
        <button type="submit" class="btn-medium-green">Save</button>
    </form>
</section>

<section class="mainSectionRight">
    
    <h3>ENVIRONMENTS</h3>
    
    <div id="envDiv" class="div-generic-blue">
        <table class="table-medium">
            <form id="environmentForm" autocomplete="off">
                <?php
                /**
                 *  Affichage des environnements actuels
                 */
                $myenv = new \Controllers\Environment();
                $envs = $myenv->listAll();
                foreach ($envs as $envName) : ?>
                    <tr>
                        <td>
                            <input type="text" class="actual-env-input" value="<?= $envName ?>" />
                        </td>
                        <td class="td-fit center">
                            <img src="resources/icons/bin.svg" class="delete-env-btn icon-lowopacity" env-name="<?= $envName ?>" title="Delete environment <?= $envName ?>"/>
                        </td>
                        <td>
                            <?php
                            if ($envName == DEFAULT_ENV) {
                                echo '(default)';
                            } ?>
                        <td>
                    </tr>
                    <?php
                endforeach;
                unset($myenv, $envs);?>
                <input type="submit" class="hide" /> <!-- hidden button, to validate form with Enter -->
            </form>
            <form id="newEnvironmentForm" autocomplete="off">
                <tr>
                    <td>
                        <input id="new-env-input" type="text" placeholder="Add a new environment" />
                    </td>
                    <td class="td-fit">
                        <button type="submit" class="btn-xxsmall-green">+</button>
                    </td>
                    <td class="td-fit">
                        <?php
                        if (empty(ENVS)) {
                            echo '<img src="resources/icons/warning.png" class="icon" title="At least 1 environment must be configured." />';
                        } ?>
                    </td>
                    <td></td>
                </tr>
            </form>
        </table>
    </div>

    <h3>DATABASES</h3>

    <div class="div-generic-blue">
        <table class="table-large">
            <tr>
                <td class="td-50">
                    <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Main database. Repomanager cannot run if this database is on error." /> Main
                </td>
                <td>
                    <?php
                    /**
                     *  Vérification de la lisibilité du fichier de base de données
                     */
                    if (!is_readable(DB) or !is_writable(DB)) {
                        echo "Main database is not readable / writable.";
                    } else {
                        echo '<span title="OK">Access</span><img src="resources/icons/greencircle.png" class="icon-small" />';
                    } ?>
                </td>
                <td>
                    <?php
                    /**
                     *  Vérification de la présence des tables
                     */
                    $myconn = new \Models\Connection('main');

                    if (!$myconn->checkMainTables()) {
                        echo '<span title="One or more table are missing.">Tables state</span><img src="resources/icons/redcircle.png" class="icon-small" />';
                    } else {
                        echo '<span title="All tables are present.">Tables state</span><img src="resources/icons/greencircle.png" class="icon-small" />';
                    } ?>
                </td>
            </tr>

            <?php
            if (STATS_ENABLED == "yes") { ?>
            <tr>
                <td class="td-50">
                    <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Statistics database." /> Statistics
                </td>
                <td>
                    <?php
                    /**
                     *  Vérification de la lisibilité du fichier
                     */
                    if (!is_readable(STATS_DB) or !is_writable(STATS_DB)) {
                        echo "Stats database is not readable / writable.";
                    } else {
                        echo '<span title="OK">Access</span><img src="resources/icons/greencircle.png" class="icon-small" />';
                    } ?>
                </td>
                <td>
                    <?php
                    /**
                     *  Vérification de la présence des tables
                     */
                    $myconn = new \Models\Connection('stats');

                    if (!$myconn->checkStatsTables()) {
                        echo '<span title="One or more table are missing.">Tables state</span><img src="resources/icons/redcircle.png" class="icon-small" />';
                    } else {
                        echo '<span title="All tables are present.">Tables state</span><img src="resources/icons/greencircle.png" class="icon-small" />';
                    } ?>
                </td>
            </tr>
            <?php   }

            if (MANAGE_HOSTS == "yes") { ?>
            <tr>
                <td class="td-50">
                    <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Hosts database." /> Hosts
                </td>
                <td>
                    <?php
                    /**
                     *  Vérification de la lisibilité du fichier
                     */
                    if (!is_readable(HOSTS_DB) or !is_writable(HOSTS_DB)) {
                        echo "Hosts database is not readable / writable.";
                    } else {
                        echo '<span title="OK">Access</span><img src="resources/icons/greencircle.png" class="icon-small" />';
                    } ?>

                </td>
                <td>
                    <?php
                    /**
                     *  Vérification de la présence des tables
                     */
                    $myconn = new \Models\Connection('hosts');

                    if (!$myconn->checkHostsTables()) {
                        echo '<span title="One or more table are missing.">Tables state</span><img src="resources/icons/redcircle.png" class="icon-small" />';
                    } else {
                        echo '<span title="All tables are present.">Tables state</span><img src="resources/icons/greencircle.png" class="icon-small" />';
                    } ?>
                </td>
            </tr>
            <?php   } ?>
        </table>
    </div>

    <h3>SERVICE</h3>

    <div class="div-generic-blue">
        <form action="/settings" method="post">
            <input type="hidden" name="action" value="applyCronConfiguration" />
            <table class="table-large">
                <tr>
                    <td class="td-50">
                        <img src="resources/icons/info.svg" class="icon-verylowopacity" title="Systemd repomanager service is used to execute regular tasks such as applying permissions on repos dirs, executings plans (if enabled), sending plan reminders (if enabled)." />  Repomanager service state
                    </td>
                    <td>
                        <?php
                        if (SERVICE_RUNNING) {
                            echo '<span title="Service is running">Status <img src="resources/icons/greencircle.png" class="icon-small" /></span>';
                        } else {
                            echo '<span title="Service is not running">Status <img src="resources/icons/redcircle.png" class="icon-small" /></span>';
                        } ?>
                    </td>
                </tr>
            </table>
        </form>
    </div>

    <?php
    /**
     *  Cette section est accessible uniquement pour les utilisateurs dont le role est 'super-administrator'
     */
    if (IS_SUPERADMIN) : ?>
        <h3>USERS</h3>
        <div class="div-generic-blue">
            <form action="/settings" method="post" autocomplete="off">

                <input type="hidden" name="action" value="createUser" />
                <p>Create an user:</p>
                <input class="input-medium" type="text" name="username" placeholder="Username" />
                <select name="role" class="select-medium">
                    <option value="">Select role...</option>
                    <option value="usage">usage</option>
                    <option value="administrator">administrator</option>
                </select>
                <button class="btn-xxsmall-green">+</button>
            </form>
            <?php
            /**
             *  Cas où un nouveau mot de passe a été généré
             */
            if (!empty($newUserUsername) and !empty($newUserPassword)) {
                echo '<p class="greentext">Temporary password generated for <b>' . $newUserUsername . '</b>: ' . $newUserPassword . '</p>';
            }

            /**
             *  Cas où un mot de passe a été reset
             */
            if (!empty($newResetedPwdUsername) and !empty($newResetedPwdPassword)) {
                echo '<p class="greentext">A new password has been generated for <b>' . $newResetedPwdUsername . '</b>: ' . $newResetedPwdPassword . '</p>';
            }

            echo '<br>';

            /**
             *  Affichage des utilisateurs existants
             */
            $myuser = new \Controllers\Login();
            $users = $myuser->getUsers();

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
                                if ($user['Username'] != 'admin') {
                                    echo '<a href="?resetPassword&username=' . $user['Username'] . '" title="Reset password of user ' . $user['Username'] . '"><img src="resources/icons/update.svg" class="icon-lowopacity" /></a>';
                                    echo '<a href="?deleteUser&username=' . $user['Username'] . '" title="Delete user ' . $user['Username'] . '"><img src="resources/icons/bin.svg" class="icon-lowopacity" /></a>';
                                } ?>
                            </td>
                        </tr>
                        <?php
                    endforeach ?>
                </table>
                <?php
            endif ?>
        </div>
        <?php
    endif ?>
</section>