<section class="section-left reloadable-container" container="settings/settings">

    <h3>MAIN CONFIGURATION</h3>

    <div class="div-generic-blue">
        <form class="settings-form" autocomplete="off">
            <h6 class="margin-top-0">HOSTNAME</h6>
            <p class="note">Repomanager FQDN, defined during the creation of the Docker container.</p>
            <input type="text" value="<?= WWW_HOSTNAME ?>" readonly />
    
            <h6 class="required">TIMEZONE</h6>
            <p class="note">Specify your timezone. This is especially useful to ensure that scheduled tasks run at the specified time.</p>
            <select class="settings-param" param-name="timezone">
                <?php
                $tzlist = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
                foreach ($tzlist as $timezone) {
                    if ($timezone == TIMEZONE) {
                        echo '<option value="' . $timezone . '" selected>' . $timezone . '</option>';
                    } else {
                        echo '<option value="' . $timezone . '">' . $timezone . '</option>';
                    }
                } ?>
            </select>

            <h6>DEFAULT CONTACT</h6>
            <p class="note">Default contact for receiving emails. Currently, only scheduled tasks and their reminders are sending emails. You can specify multiple recipients.</p>
            <select id="emailRecipientSelect" class="settings-param" param-name="emailRecipient" multiple>
                <?php
                if (!empty(EMAIL_RECIPIENT)) {
                    foreach (EMAIL_RECIPIENT as $email) {
                        echo '<option value="' . $email . '" selected>' . $email . '</option>';
                    }
                }
                if (!empty($usersEmail)) {
                    foreach ($usersEmail as $email) {
                        if (!in_array($email, EMAIL_RECIPIENT)) {
                            echo '<option value="' . $email . '">' . $email . '</option>';
                        }
                    }
                } ?>
            </select>
            
            <?php
            if (!empty(EMAIL_RECIPIENT)) {
                echo '<button type="button" id="send-test-email-btn" class="btn-medium-blue margin-top-5" title="Send a test email">Send a test email</button>';
            } ?>

            <h6>SESSION TIMEOUT</h6>
            <p class="note">User session timeout in seconds.</p>
            <input class="settings-param" param-name="session-timeout" type="number" min="15" value="<?= SESSION_TIMEOUT ?>" placeholder="Default is 3600 (1 hour)">

            <h6>USE A PROXY</h6>
            <p class="note">Specify the proxy URL to use to access the internet. e.g. https://myproxy.com:8080</p>
            <input class="settings-param" param-name="proxy" type="text" value="<?= PROXY ?>" placeholder="https://">

            <hr class="margin-top-20 margin-bottom-20">

            <h5>TASK EXECUTION</h5>

            <h6>TASK QUEUING</h6>
            <p class="note">Enable or disable the task queuing.</p>
            <label class="onoff-switch-label">
                <input class="settings-param onoff-switch-input" param-name="task-queuing" type="checkbox" value="true" <?php echo (TASK_QUEUING == "true") ? 'checked' : ''; ?>>
                <span class="onoff-switch-slider"></span>
            </label>

            <?php
            if (TASK_QUEUING == 'true') : ?>
                <h6>MAXIMUM NUMBER OF SIMULTANEOUS TASKS</h6>
                <p class="note">Maximum number of tasks that can run simultaneously. The other tasks will be queued.</p>
                <input class="settings-param" param-name="task-queuing-max-simultaneous" type="number" min="1" value="<?= TASK_QUEUING_MAX_SIMULTANEOUS ?>" placeholder="Default is 3">
                <?php
            endif ?>

            <h6 class="required">TASK EXECUTION MEMORY LIMIT (in MB)</h6>
            <p class="note">Set PHP memory limit for task execution. It is recommended to set this value to a higher value when mirroring large repositories.</p>
            <input class="settings-param" param-name="task-execution-memory-limit" type="number" min="2" value="<?= TASK_EXECUTION_MEMORY_LIMIT ?>" placeholder="Default is 512">

            <br><br>
            <h5>TASK CLEANUP</h5>

            <h6 class="required">CLEAN TASKS OLDER THAN (in days)</h6>
            <p class="note">Tasks and their logs older than this value will be definitely deleted. This is useful to free up some space.</p>
            <input class="settings-param" param-name="task-clean-older-than" type="number" min="1" value="<?= TASK_CLEAN_OLDER_THAN ?>" placeholder="Default is 730 (2 years)">

            <button class="hide" type="submit"></button>
        </form>
    </div>

    <h3>REPOSITORIES</h3>

    <div class="div-generic-blue">
        <form class="settings-form" autocomplete="off">
            <h5>GLOBAL SETTINGS</h5>

            <h6>REPOSITORIES URL</h6>
            <p class="note">Root URL for accessing repositories. This URL is not browseable for security reasons. To explore the content of a repository snapshot, use the snapshot browsing system.</p>
            <input type="text" value="<?= WWW_REPOS_DIR_URL ?>" readonly />

            <h6 class="required">DEDUPLICATION</h6>
            <p class="note">Enable or disable deduplication of packages in snapshots. When enabled, identical packages in snapshots will be stored only once, saving disk space. Default: enabled.</p>
            <p class="note">Note: deduplication is using hard links, disable it if your repositories are stored on a filesystem that does not support hard links (e.g., S3 bucket).</p>
            <label class="onoff-switch-label">
                <input class="settings-param onoff-switch-input" param-name="repo-deduplication" type="checkbox" value="true" <?php echo (REPO_DEDUPLICATION) ? 'checked' : ''; ?>>
                <span class="onoff-switch-slider"></span>
            </label>

            <h6 class="required">RETENTION</h6>
            <p class="note">Maximum number of unused snapshots to keep per repository. Set to 0 to disable retention.</p>
            <input class="settings-param" param-name="retention" type="number" min="0" value="<?= RETENTION ?>">
            <?php
            if (RETENTION == 0) {
                echo '<p class="note yellowtext"><img src="/assets/icons/warning.svg" class="icon vertical-align-text-top" /> No retention is set. All unused snapshots will be deleted.</p>';
            } ?>

            <h6>REPOSITORY CONFIGURATION FILE NAME PREFIX</h6>
            <p class="note">Prefix added to repository configuration files when installing on client hosts. Leave empty if you want no prefix.</p>
            <input class="settings-param" param-name="repoConfFilesPrefix" type="text" value="<?= REPO_CONF_FILES_PREFIX ?>">

            <div class="flex column-gap-15 margin-top-5">
                <p class="note">e.g.</p>
                <div class="flex align-item-center column-gap-5">
                    <span class="label-pkg-rpm" title="This repository contains deb packages">RPM</span>
                    <code><?= REPO_CONF_FILES_PREFIX ?>almalinux-baseos.repo</code>
                </div>
                <div class="flex align-item-center column-gap-5">
                    <span class="label-pkg-deb" title="This repository contains deb packages">DEB</span>
                    <code><?= REPO_CONF_FILES_PREFIX ?>ubuntu-noble-main.list</code>
                </div>
            </div>

            <hr class="margin-top-20 margin-bottom-20">

            <h5>GLOBAL MIRRORING SETTINGS</h5>

            <h6 class="required">PACKAGE DOWNLOAD TIMEOUT (in seconds)</h6>
            <p class="note">Maximum time allowed to download a package during a mirroring process.</p>
            <input class="settings-param" param-name="mirrorPackageDownloadTimeout" min="1" type="number" value="<?= MIRRORING_PACKAGE_DOWNLOAD_TIMEOUT ?>" placeholder="Default is 300">

            <h6 class="required">PACKAGE CHECKSUM MATCH FAILURE</h6>
            <p class="note">If a package checksum does not match the expected checksum, this parameter allows you to choose what to do in this case.</p>
            <select class="settings-param" param-name="mirrorPackageChecksumFailure">
                <option value="error" <?php echo (MIRRORING_PACKAGE_CHECKSUM_FAILURE == 'error') ? 'selected' : '' ?>>End mirroring task with error</option>
                <option value="ignore" <?php echo (MIRRORING_PACKAGE_CHECKSUM_FAILURE == 'ignore') ? 'selected' : '' ?>>Ignore package (delete it) and continue</option>
                <option value="keep" <?php echo (MIRRORING_PACKAGE_CHECKSUM_FAILURE == 'keep') ? 'selected' : '' ?>>Keep the package anyway (not recommended)</option>
            </select>            

            <hr class="margin-top-20 margin-bottom-20">

            <h5>RPM</h5>

            <h6>ENABLE RPM REPOSITORIES</h6>
            <p class="note">Enable RPM package repositories.</p>        
            <label class="onoff-switch-label">
                <input class="settings-param onoff-switch-input" param-name="rpmRepo" type="checkbox" value="true" <?php echo (RPM_REPO == 'true') ? 'checked' : ''; ?>>
                <span class="onoff-switch-slider"></span>
            </label>

            <?php
            if (RPM_REPO == 'true') : ?>
                <h6>SIGN PACKAGES WITH GPG</h6>
                <p class="note">Enable the signing of RPM packages when creating a RPM package repository (mirror or local repository). Packages will be signed using the GPG signing key specified by the GPG KEY ID parameter.</p>
                <label class="onoff-switch-label">
                    <input class="settings-param onoff-switch-input" param-name="rpmSignPackages" type="checkbox" value="true" <?php echo (RPM_SIGN_PACKAGES == "true") ? 'checked' : ''; ?>>
                    <span class="onoff-switch-slider"></span>
                </label>
                <?php
                if (empty(RPM_SIGN_PACKAGES)) {
                    echo '<p class="note yellowtext"><img src="/assets/icons/warning.svg" class="icon vertical-align-text-top" /> This parameter must be specified.</p>';
                } ?>

                <h6 class="required">DEFAULT RELEASE VERSION</h6>
                <p class="note">Default release version to use when creating RPM repositories.</p>
                <select class="settings-param" param-name="releasever">
                    <option value="7" <?php echo (RELEASEVER == 7) ? 'selected' : '' ?>>7 (Redhat 7 and derivatives)</option>
                    <option value="8" <?php echo (RELEASEVER == 8) ? 'selected' : '' ?>>8 (Redhat 8 and derivatives)</option>
                    <option value="9" <?php echo (RELEASEVER == 9) ? 'selected' : '' ?>>9 (Redhat 9 and derivatives)</option>
                </select>
                <?php
                if (empty(RELEASEVER)) {
                    echo '<p class="note yellowtext"><img src="/assets/icons/warning.svg" class="icon vertical-align-text-top" /> This parameter must be specified.</p>';
                } ?>

                <h6 class="required">DEFAULT PACKAGE ARCHITECTURE</h6>
                <p class="note">Default package architecture to use when creating RPM repositories.</p>
                <select id="rpmArchitectureSelect" class="settings-param" param-name="rpmDefaultArch" multiple>
                    <?php
                    foreach (RPM_ARCHS as $arch) {
                        if (in_array($arch, RPM_DEFAULT_ARCH)) {
                            echo '<option value="' . $arch . '" selected>' . $arch . '</option>';
                        } else {
                            echo '<option value="' . $arch . '">' . $arch . '</option>';
                        }
                    } ?>
                </select>

                <hr class="margin-top-20 margin-bottom-20">

                <h5 title="RPM mirroring settings">RPM MIRRORING SETTINGS</h5>

                <h6 class="required">WHEN PACKAGE SIGNATURE IS MISSING</h6>
                <p class="note">Package retrieved from a remote repository may not be signed at all (for example, the publisher released the package forgetting to sign it). This parameter allows you to choose what to do in this case.</p>
                <select class="settings-param" param-name="rpm-missing-signature">
                    <option value="download" <?php echo (RPM_MISSING_SIGNATURE == 'download') ? 'selected' : '' ?>>Download package anyway</option>
                    <option value="ignore" <?php echo (RPM_MISSING_SIGNATURE == 'ignore') ? 'selected' : '' ?>>Ignore package (do not download)</option>
                    <option value="error" <?php echo (RPM_MISSING_SIGNATURE == 'error') ? 'selected' : '' ?>>End mirroring task with error</option>
                </select>

                <h6 class="required">WHEN PACKAGE SIGNATURE IS INVALID</h6>
                <p class="note">Package retrieved from a remote repository may have invalid signature (because the GPG key used to sign the package was not imported, or because the publisher signed the package with a different GPG key, or because the package's signature is corrupted or somehow broken). This parameter allows you to choose what to do in this case.</p>
                <select class="settings-param" param-name="rpm-invalid-signature">
                    <option value="download" <?php echo (RPM_INVALID_SIGNATURE == 'download') ? 'selected' : '' ?>>Download package anyway</option>
                    <option value="ignore" <?php echo (RPM_INVALID_SIGNATURE == 'ignore') ? 'selected' : '' ?>>Ignore package (do not download)</option>
                    <option value="error" <?php echo (RPM_INVALID_SIGNATURE == 'error') ? 'selected' : '' ?>>End mirroring task with error</option>
                </select>

                <h6 class="required">WHEN PACKAGE SIGNATURE FAILS</h6>
                <p class="note">Package retrieved from a remote repository may fail to be signed (for example, the package is not a valid RPM package or it is corrupted). This parameter allows you to choose what to do in this case.</p>
                <select class="settings-param" param-name="rpm-signature-fail">
                    <option value="keep" <?php echo (RPM_SIGNATURE_FAIL == 'keep') ? 'selected' : '' ?>>Keep the package anyway (not recommended)</option>
                    <option value="ignore" <?php echo (RPM_SIGNATURE_FAIL == 'ignore') ? 'selected' : '' ?>>Ignore package (delete it) and continue</option>
                    <option value="error" <?php echo (RPM_SIGNATURE_FAIL == 'error') ? 'selected' : '' ?>>End signing task with error</option>
                </select>
                <?php
            endif ?>

            <hr class="margin-top-20 margin-bottom-20">

            <h5>DEB</h5>

            <h6>ENABLE DEB REPOSITORIES</h6>
            <p class="note">Enable DEB package repositories.</p>
            <label class="onoff-switch-label">
                <input class="settings-param onoff-switch-input" param-name="debRepo" type="checkbox" value="true" <?php echo (DEB_REPO == 'true') ? 'checked' : ''; ?>>
                <span class="onoff-switch-slider"></span>
            </label>

            <?php
            if (DEB_REPO == 'true') : ?>
                <h6>SIGN REPOSITORIES WITH GPG</h6>
                <p class="note">Enable the signing of DEB repositories when creating a DEB package repository (mirror or local repository). The repository metadata will be signed using the GPG signing key specified by the GPG key Id parameter.</p>
                <label class="onoff-switch-label">
                    <input class="settings-param onoff-switch-input" param-name="debSignRepo" type="checkbox" value="true" <?php echo (DEB_SIGN_REPO == 'true') ? 'checked' : ''; ?>>
                    <span class="onoff-switch-slider"></span>
                </label>
                <?php
                if (empty(DEB_SIGN_REPO)) {
                    echo '<p class="note yellowtext"><img src="/assets/icons/warning.svg" class="icon vertical-align-text-top" /> This parameter must be specified.</p>';
                } ?>

                <h6 class="required">DEFAULT PACKAGE ARCHITECTURE</h6>
                <p class="note">Default package architecture to use when creating DEB repositories.</p>
                <select id="debArchitectureSelect" class="settings-param" param-name="debDefaultArch" multiple>
                    <?php
                    foreach (DEB_ARCHS as $arch) {
                        if (in_array($arch, DEB_DEFAULT_ARCH)) {
                            echo '<option value="' . $arch . '" selected>' . $arch . '</option>';
                        } else {
                            echo '<option value="' . $arch . '">' . $arch . '</option>';
                        }
                    } ?>
                </select>

                <hr class="margin-top-20 margin-bottom-20">

                <h5 title="DEB mirroring settings">DEB MIRRORING SETTINGS</h5>

                <h6 class="required">ALLOW SYNC OF EMPTY REPOSITORIES</h6>
                <p class="note">Allow the mirroring of empty repositories (repositories with empty <code>Packages</code> indices file).</p>
                <label class="onoff-switch-label">
                    <input class="settings-param onoff-switch-input" param-name="deb-allow-empty-repo" type="checkbox" value="true" <?php echo (DEB_ALLOW_EMPTY_REPO == 'true') ? 'checked' : ''; ?>>
                    <span class="onoff-switch-slider"></span>
                </label>

                <h6 class="required">WHEN RELEASE FILE SIGNATURE IS INVALID</h6>
                <p class="note">InRelease / Release file retrieved from a remote repository may have invalid signature (because the GPG key used to sign the file was not imported, or because the publisher signed the file with a different GPG key, or because the file's signature is corrupted or somehow broken). This parameter allows you to choose what to do in this case.</p>
                <select class="settings-param" param-name="deb-invalid-signature">
                    <option value="ignore" <?php echo (DEB_INVALID_SIGNATURE == 'ignore') ? 'selected' : '' ?>>Ignore and try another Release file if possible</option>
                    <option value="error" <?php echo (DEB_INVALID_SIGNATURE == 'error') ? 'selected' : '' ?>>End mirroring task with error</option>
                </select>
                <?php
            endif;

            if (RPM_SIGN_PACKAGES == 'true' or DEB_SIGN_REPO == 'true') : ?>
                <hr class="margin-top-20 margin-bottom-20">

                <h5>GPG SIGNING KEY</h5>

                <h6 class="required">GPG KEY ID</h6>
                <p class="note">GPG key for signing packages and repositories, identified by its email address. This key is randomly generated upon Repomanager's first startup (4096 bits RSA key).</p>
                <input class="settings-param" param-name="gpgKeyID" type="email" value="<?= GPG_SIGNING_KEYID ?>">
                <?php
            endif ?>

            <button class="hide" type="submit"></button>
        </form>

        <hr class="margin-top-20 margin-bottom-20">

        <h5>ENVIRONMENTS</h5>

        <h6>CURRENT ENVIRONMENTS</h6>
        <p class="note">You can edit environments names and colors. The default environment is the first one in the list, it will be pre-selected in tasks forms.</p>
        <p class="note">Renaming an environment here will not rename it in repositories, this to prevent breaking existing configurations.</p>

        <div id="envs-div">
            <div id="current-envs-div">
                <?php
                if (empty(ENVS)) {
                    echo '<p class="note yellowtext"><img src="/assets/icons/warning.svg" class="icon" /> At least 1 environment must be configured</p>';
                }

                if (!empty(ENVS)) :
                    foreach (ENVS as $env) :
                        if (empty($env['Color'])) {
                            $color = '#ffffff';
                        } else {
                            $color = $env['Color'];
                        } ?>

                        <div class="env-line flex align-item-center column-gap-5" env-id="<?= $env['Id'] ?>">
                            <div>
                                <div class="input-color-wrapper">
                                    <input type="color" name="env-color" value="<?= $color ?>" /> 
                                </div>
                            </div>

                            <input type="text" name="env-name" value="<?= $env['Name'] ?>" autocomplete="off" />
                            
                            <div>
                                <span class="round-btn-tr-to-red delete-env-btn" env-id="<?= $env['Id'] ?>" env-name="<?= $env['Name'] ?>" title="Delete <?= $env['Name'] ?> environment">
                                    <img src="/assets/icons/delete.svg" />
                                </span>
                            </div>
                        </div>
                        <?php
                    endforeach ?>
                    <br>
                    <button id="edit-env-btn" type="button" class="btn-small-green">Save</button>
                    <?php
                endif ?>
            </div>

            <h6>ADD NEW ENVIRONMENT</h6>
            <p class="note">Choose a color and a name for the new environment.</p>
            <div class="flex align-item-center column-gap-5">
                <div>
                    <div class="input-color-wrapper">
                        <input type="color" name="add-env-color" class="color-xsmall" value="#F32F63" /> 
                    </div>
                </div>
                
                <input type="text" name="add-env-name" placeholder="Name" autocomplete="off" />

                <div>
                    <button type="button" id="add-env-btn" class="btn-xxsmall-green">+</button>
                </div>
            </div>
        </div>

        <hr class="margin-top-20 margin-bottom-20">

        <h5>STATISTICS</h5>

        <form class="settings-form" autocomplete="off">
            <h6>ENABLE REPOSITORIES STATISTICS</h6>
            <p class="note">Enable logging and statistics on repositories access, repositories size and repositories packages count.</p>
            <label class="onoff-switch-label">
                <input class="settings-param onoff-switch-input" param-name="statsEnable" type="checkbox" value="true" <?php echo (STATS_ENABLED == "true") ? 'checked' : ''; ?>>
                <span class="onoff-switch-slider"></span>
            </label>
            <?php
            if (empty(STATS_ENABLED)) {
                echo '<p class="note yellowtext"><img src="/assets/icons/warning.svg" class="icon vertical-align-text-top" /> This parameter must be specified.</p>';
            } ?>

            <button class="hide" type="submit"></button>
        </form>
    </div>

    <h3>SCHEDULED TASKS</h3>

    <div class="div-generic-blue">
        <form class="settings-form" autocomplete="off">
            <h6 class="margin-top-0">ENABLE SCHEDULED TASKS REMINDERS</h6>
            <p class="note">Enable reminders for scheduled tasks. Reminders are sent via email to the recipients defined when adding a new scheduled task.</p>
            <label class="onoff-switch-label">
                <input class="settings-param onoff-switch-input" param-name="scheduled-tasks-reminders" type="checkbox" value="true" <?php echo (SCHEDULED_TASKS_REMINDERS == "true") ? 'checked' : ''; ?>>
                <span class="onoff-switch-slider"></span>
            </label>
            <?php
            if (empty(SCHEDULED_TASKS_REMINDERS)) {
                echo '<p class="note yellowtext"><img src="/assets/icons/warning.svg" class="icon vertical-align-text-top" /> This parameter must be specified.</p>';
            } ?>

            <button class="hide" type="submit"></button>
        </form>
    </div>

    <h3>HOSTS</h3>

    <div class="div-generic-blue">
        <form class="settings-form" autocomplete="off">
            <h6 class="margin-top-0">MANAGE HOSTS</h6>
            <p class="note">Enable the management of client hosts. These hosts can register to Repomanager using linupdate.</p>
            <label class="onoff-switch-label">
                <input class="settings-param onoff-switch-input" param-name="manageHosts" type="checkbox" value="true" <?php echo (MANAGE_HOSTS == "true") ? 'checked' : ''; ?>>
                <span class="onoff-switch-slider"></span>
            </label>
            <?php
            if (empty(MANAGE_HOSTS)) {
                echo '<p class="note yellowtext"><img src="/assets/icons/warning.svg" class="icon vertical-align-text-top" /> This parameter must be specified.</p>';
            } ?>

            <button class="hide" type="submit"></button>
        </form>
    </div>

    <!-- <h3>CVE</h3>

    <div class="div-generic-blue">
        <form class="settings-form" autocomplete="off">
            <h6 class="margin-top-0">IMPORT CVEs (beta)</h6>
            <p class="note">Enable the import of CVEs into Repomanager. The import uses feeds from https://nvd.nist.gov/ Eventually, the CVEs tab should be able to list client hosts imported into Repomanager that have vulnerable packages.</p>
            <label class="onoff-switch-label">
                <input class="settings-param onoff-switch-input" param-name="cveImport" type="checkbox" value="true" <?php echo (CVE_IMPORT == "true") ? 'checked' : ''; ?>>
                <span class="onoff-switch-slider"></span>
            </label>

            <?php
            if (CVE_IMPORT == 'true') : ?>
                <h6 class="required">IMPORT SCHEDULE TIME</h6>
                <input type="time" class="settings-param" param-name="cveImportTime" value="<?= CVE_IMPORT_TIME ?>">
                <p class="note">Every day time at which the import of CVEs runs.</p>
                <?php
            endif;

            // if (MANAGE_HOSTS == 'true' && CVE_IMPORT == 'true') :
                // <div>
                //     <div>
                //         <img src="/assets/icons/info.svg" class="icon-lowopacity" title="" />
                //     </div>
                //     <div>
                //         <p>Scan for CVEs affected hosts</p>
                //     </div>
                //     <div>
                //         <label class="onoff-switch-label">
                //             <input class="settings-param onoff-switch-input" param-name="cveScanHosts" type="checkbox" value="true" <?php echo (CVE_SCAN_HOSTS == "true") ? 'checked' : '';
                //             <span class="onoff-switch-slider"></span>
                //         </label>
                //     </div>
                //     <div></div>
                // </div>
            // endif

            if (CVE_IMPORT == 'true') : ?>
                <h6>ACCESS CVEs PAGE (beta)</h6>
                <p><a href="/cves" target="_blank" rel="noopener noreferrer">CVEs page (beta)<img src="/assets/icons/external-link.svg" class="icon-small margin-left-5" /></a></p>
                <?php
            endif ?>

            <button class="hide" type="submit"></button>
        </form>
    </div> -->

    <?php
    if (IS_ADMIN) : ?>
        <h3>SSO (OpenID Connect)</h3>

        <div class="div-generic-blue">
            <form class="settings-form" autocomplete="off">
                <h6 class="margin-top-0">ENABLE SSO</h6>
                <p class="note">Enable SSO login via OpenID Connect.</p>
                <label class="onoff-switch-label">
                    <input class="settings-param onoff-switch-input" param-name="oidcEnable" type="checkbox" value="true" <?php echo (OIDC_ENABLED == "true") ? 'checked' : ''; ?>>
                    <span class="onoff-switch-slider"></span>
                </label>

                <?php
                if (OIDC_ENABLED == 'true') : ?>
                    <h6>SSO ONLY</h6>
                    <p class="note">Only allow login via OpenID Connect. This will disable local login.</p>
                    <label class="onoff-switch-label">
                        <input class="settings-param onoff-switch-input" param-name="ssoOidcOnly" type="checkbox" value="true" <?php echo (SSO_OIDC_ONLY == "true") ? 'checked' : ''; ?>>
                        <span class="onoff-switch-slider"></span>
                    </label>

                    <h6>PROVIDER URL</h6>
                    <p class="note">Provider URL, used for Autodiscovery.</p>
                    <input class="settings-param" param-name="oidcProviderUrl" type="text" value="<?= OIDC_PROVIDER_URL ?>">

                    <h6>AUTHORIZATION ENDPOINT</h6>
                    <p class="note">Override Authorization Endpoint (leave empty for Autodiscovery).</p>
                    <input class="settings-param" param-name="oidcAuthorizationEndpoint" type="text" value="<?= OIDC_AUTHORIZATION_ENDPOINT ?>">

                    <h6>TOKEN ENDPOINT</h6>
                    <p class="note">Override Token Endpoint (leave empty for Autodiscovery).</p>
                    <input class="settings-param" param-name="oidcTokenEndpoint" type="text" value="<?= OIDC_TOKEN_ENDPOINT ?>">

                    <h6>USERINFO ENDPOINT</h6>
                    <p class="note">Override Userinfo Endpoint (leave empty for Autodiscovery).</p>
                    <input class="settings-param" param-name="oidcUserinfoEndpoint" type="text" value="<?= OIDC_USERINFO_ENDPOINT ?>">

                    <h6>SCOPES</h6>
                    <p class="note">Additional OIDC Scopes (openid already present).</p>
                    <input class="settings-param" param-name="oidcScopes" type="text" value="<?= OIDC_SCOPES ?>">

                    <h6>CLIENT ID</h6>
                    <p class="note">Client ID.</p>
                    <input class="settings-param" param-name="oidcClientId" type="text" value="<?= OIDC_CLIENT_ID ?>">

                    <h6>CLIENT SECRET</h6>
                    <p class="note">Client Secret.</p>
                    <input class="settings-param" param-name="oidcClientSecret" type="password" value="<?= OIDC_CLIENT_SECRET ?>">

                    <h6>USERNAME CLAIM</h6>
                    <p class="note">OIDC Claim for username.</p>
                    <input class="settings-param" param-name="oidcUsername" type="text" value="<?= OIDC_USERNAME ?>">

                    <h6>FIRST NAME</h6>
                    <p class="note">OIDC Claim for First Name.</p>
                    <input class="settings-param" param-name="oidcFirstName" type="text" value="<?= OIDC_FIRST_NAME ?>">

                    <h6>LAST NAME</h6>
                    <p class="note">OIDC Claim for Last Name.</p>
                    <input class="settings-param" param-name="oidcLastName" type="text" value="<?= OIDC_LAST_NAME ?>">

                    <h6>EMAIL</h6>
                    <p class="note">OIDC Claim for Email.</p>
                    <input class="settings-param" param-name="oidcEmail" type="text" value="<?= OIDC_EMAIL ?>">

                    <h6>GROUPS</h6>
                    <p class="note">OIDC Claim for Groups / Roles.</p>
                    <input class="settings-param" param-name="oidcGroups" type="text" value="<?= OIDC_GROUPS ?>">

                    <h6>GROUP ADMINISTRATOR</h6>
                    <p class="note">Groups value for Administrator.</p>
                    <input class="settings-param" param-name="oidcGroupAdministrator" type="text" value="<?= OIDC_GROUP_ADMINISTRATOR ?>">

                    <!-- <h6>GROUP SUPER ADMINISTRATOR</h6>
                    <p class="note">Groups value for Super Administrator.</p>
                    <input class="settings-param" param-name="oidcGroupSuperAdministrator" type="text" value="<?= OIDC_GROUP_SUPER_ADMINISTRATOR ?>"> -->

                    <h6>HTTP PROXY</h6>
                    <p class="note">HTTP proxy.</p>
                    <input class="settings-param" param-name="oidcHttpProxy" type="text" value="<?= OIDC_HTTP_PROXY ?>">

                    <h6>CERTIFICATE FILE</h6>
                    <p class="note">Path to certificate file. The certificate file must be stored under Repomanager's data directory to be valid.</p>
                    <input class="settings-param" param-name="oidcCertPath" type="text" value="<?= OIDC_CERT_PATH ?>">
                    <?php
                endif; ?>

                <button class="hide" type="submit"></button>
            </form>
        </div>
        <?php
    endif; ?>

    <form class="settings-form" autocomplete="off">
        <button type="submit" class="btn-medium-green">Save</button>
    </form>

    <script>
        $(document).ready(function(){
            myselect2.convert('#emailRecipientSelect', 'Select recipients...', true);
            myselect2.convert('#debArchitectureSelect', 'Select architectures...');
            myselect2.convert('#rpmArchitectureSelect', 'Select architectures...');
        });
    </script>
</section>
