<section class="section-main">
    <div id="title-button-div">
        <h3>PROFILES</h3>
        <?php
        if (IS_ADMIN) : ?>
            <div id="title-button-container">
                <div class="slide-btn slide-panel-btn" slide-panel="manage-profiles-server-settings" title="Edit server settings">
                    <img src="assets/icons/cog.svg" />
                    <span>Settings</span>
                </div>
            </div>
            <?php
        endif ?>
    </div>

    <p>
        You can create and manage configuration profiles for your hosts that use <a href="https://github.com/lbr38/linupdate"><b>linupdate</b></a>.<br>
        On every package update, hosts will automaticaly get their configuration from this reposerver.
    </p>

    <br><br>

    <div id="profilesDiv">
        <h5>CREATE A NEW PROFILE</h5>
        <form id="newProfileForm" autocomplete="off">
            <input id="newProfileInput" type="text" class="input-medium" />
            <button type="submit" class="btn-xxsmall-green" title="Add">+</button>
        </form>
        
        <br><br><br>

        <?php
        if (!empty($profiles)) : ?>
            <h5>CURRENT PROFILES</h5>
            
            <div class="profileDivContainer">
                <?php
                /**
                 *  Affichage des profils et leur configuration
                 */
                foreach ($profiles as $profile) :
                    /**
                     *  Récupération de la configuration du profil
                     */
                    $profileId = $profile['Id'];
                    $profileName = $profile['Name'];
                    $profileConfExclude = explode(',', $profile['Package_exclude']);
                    $profileConfExcludeMajor = explode(',', $profile['Package_exclude_major']);
                    $profileConfNeedRestart = explode(',', $profile['Service_restart']);
                    $linupdateGetPkgConf = $profile['Linupdate_get_pkg_conf'];
                    $linupdateGetReposConf = $profile['Linupdate_get_repos_conf'];
                    $profileNotes = $profile['Notes'];
                    $profileReposMembersIds = $myprofile->reposMembersIdList($profileId);

                    /**
                     *  On récupère le nombre d'hôtes utilisant ce profil, si il y en a, et si la gestion des hôtes est activée
                     */
                    if (MANAGE_HOSTS == 'true') {
                        /**
                         *  Ici on doit redéclarer à nouveau l'objet $myprofile, car lorsque la div '.profileDivContainer' est rechargée par jquery, l'objet $myprofile n'est alors pas défini et provoque une erreur 500.
                         */
                        $myhost = new \Controllers\Host();
                        $hostsCount = $myhost->countByProfile($profileName);
                        unset($myhost);
                    } ?>

                    <div class="profileDiv">
                        <form class="profileForm" profilename="<?=$profileName?>" autocomplete="off">
                            <table class="table-large">
                                <tr>
                                    <td>
                                        <input type="text" class="invisibleInput-blue profileFormInput" profilename="<?=$profileName?>" value="<?=$profileName?>" />
                                    </td>
                                    <td class="td-fit">
                                        <?php
                                        if (MANAGE_HOSTS == 'true' and $hostsCount > 0) {
                                            echo '<span class="hosts-count mediumopacity" title="' . $hostsCount . ' host(s) using this profile">' . $hostsCount . '<img src="assets/icons/server.svg" class="icon" /></span>';
                                        } ?>
                                        <span><img src="assets/icons/cog.svg" class="profileConfigurationBtn icon-mediumopacity" profilename="<?=$profileName?>" title="<?=$profileName?> configuration" /></span>
                                        <span><img src="assets/icons/duplicate.svg" class="duplicateProfileBtn icon-mediumopacity" profilename="<?=$profileName?>" title="Create a new profile from <?=$profileName?> configuration" /></span>
                                        <span><img src="assets/icons/delete.svg" class="deleteProfileBtn icon-mediumopacity" profilename="<?=$profileName?>" title="Delete <?=$profileName?> profile" /></span>
                                    </td>
                                </tr>
                            </table>
                        </form>
                
                        <div id="profileConfigurationDiv-<?=$profileName?>" class="hide profileDivConf">
                            <form class="profileConfigurationForm" profilename="<?=$profileName?>" autocomplete="off">
                                <h5>LINUPDATE CONFIGURATION</h5>
                                <br>
                                <?php
                                if ($serverManageClientRepos == "no" and $serverManageClientConf == "no") {
                                    echo "<p>This reposerver is not configured to manage hosts linupdate configuration.</p>";
                                }

                                if ($serverManageClientRepos == "yes") : ?>
                                    <h5>Grant access to following repositories</h5>
                                    <p>Specify what repositories the host(s) will have access to. Repos files will be retrieved by hosts on each linupdate execution.</p>
                                    <table class="table-large">
                                        <tr>
                                            <td colspan="100%">
                                                <select class="select-repos" profilename="<?= $profileName ?>" multiple>
                                                    <?php
                                                    /**
                                                     *  On récupère la liste des repos actifs
                                                     *  Puis pour chaque repos, on regarde si celui-ci est déjà présent dans le profil, si c'est le cas il sera affiché sélectionné dans la liste déroulante, si ce n'est pas le cas il sera disponible dans la liste déroulante
                                                     */
                                                    $repos = $myrepo->listNameOnly(true);

                                                    foreach ($repos as $repo) :
                                                        $repoId   = $repo['Id'];
                                                        $repoName = $repo['Name'];
                                                        $repoDist = $repo['Dist'];
                                                        $repoSection = $repo['Section'];
                                                        $repoPackageType = $repo['Package_type'];
                                                        if (in_array($repoId, $profileReposMembersIds)) {
                                                            if ($repoPackageType == 'rpm') {
                                                                echo '<option value="' . $repoId . '" selected>' . $repoName . '</option>';
                                                            }
                                                            if ($repoPackageType == 'deb') {
                                                                echo '<option value="' . $repoId . '" selected>' . $repoName . ' ❯ ' . $repoDist . ' ❯ ' . $repoSection . '</option>';
                                                            }
                                                        } else {
                                                            if ($repoPackageType == 'rpm') {
                                                                echo '<option value="' . $repoId . '">' . $repoName . '</option>';
                                                            }
                                                            if ($repoPackageType == 'deb') {
                                                                echo '<option value="' . $repoId . '">' . $repoName . ' ❯ ' . $repoDist . ' ❯ ' . $repoSection . '</option>';
                                                            }
                                                        }
                                                    endforeach ?>
                                                </select>
                                            </td>
                                        </tr>
                                    </table>

                                    <div class="flex align-item-center column-gap-4">
                                        <span>Linupdate should automatically get its repos files from this profile on each execution </span>
                                        <label class="onoff-switch-label">
                                        <input id="profile-linupdate-get-repos-conf" profilename="<?= $profileName ?>" type="checkbox" class="onoff-switch-input" <?php echo ($linupdateGetReposConf == 'true') ? 'checked' : ''; ?>>
                                            <span class="onoff-switch-slider"></span>
                                        </label>
                                    </div>
                                    
                                    <br>
                                    <hr>
                                    <br>

                                    <?php
                                endif;
                                /**
                                 *  Si le serveur est configuré pour gérer la conf des serveurs clients alors on affiche la configuration pour chaque profil
                                 */
                                if ($serverManageClientConf == "yes") :
                                    $listPackages = $myprofile->getPackages();
                                    /**
                                     *  Liste des paquets sélectionnables dans la liste des paquets à exclure
                                     *  explode cette liste pour retourner un tableau, puis tri par ordre alpha
                                     */
                                    sort($listPackages);

                                    /**
                                     *  Pour chaque paquet de cette liste, si celui-ci apparait dans $profileConfExcludeMajor alors on l'affiche comme sélectionné "selected"
                                     */ ?>
                                    <h5>Packages to exclude on major version update</h5>

                                    <select class="select-exclude-major" profilename="<?= $profileName ?>" name="profileConfExcludeMajor[]" multiple>
                                        <?php
                                        foreach ($listPackages as $package) {
                                            if (in_array($package, $profileConfExcludeMajor)) {
                                                echo '<option value="' . $package . '" selected>' . $package . '</option>';
                                            } else {
                                                echo '<option value="' . $package . '">' . $package . '</option>';
                                            }
                                            /**
                                             *  On vérifie la même chose pour ce même paquet suivi d'un wildcard (ex: apache.*)
                                             */
                                            if (in_array("${package}.*", $profileConfExcludeMajor)) {
                                                echo '<option value="' . $package . '.*" selected>' . $package . '.*</option>';
                                            } else {
                                                echo '<option value="' . $package . '.*">' . $package . '.*</option>';
                                            }
                                        } ?>
                                    </select>
                                    
                                    <br><br>

                                    <h5>Packages to exclude (no matter the version)</h5>

                                    <select class="select-exclude" profilename="<?= $profileName ?>" multiple>
                                        <?php
                                        foreach ($listPackages as $package) {
                                            if (in_array($package, $profileConfExclude)) {
                                                echo '<option value="' . $package . '" selected>' . $package . '</option>';
                                            } else {
                                                echo '<option value="' . $package . '">' . $package . '</option>';
                                            }

                                            /**
                                             *  On fait la même chose pour ce même paquet suivi d'un wildcard (ex: apache.*)
                                             */
                                            if (in_array("${package}.*", $profileConfExclude)) {
                                                echo '<option value="' . $package . '.*" selected>' . $package . '.*</option>';
                                            } else {
                                                echo '<option value="' . $package . '.*">' . $package . '.*</option>';
                                            }
                                        } ?>
                                    </select>

                                    <br><br>

                                    <h5>Services to restart after package update</h5>

                                    <?php
                                    /**
                                     *  Liste des services sélectionnables dans la liste des services à redémarrer
                                     *  explode cette liste pour retourner un tableau, puis tri par ordre alpha
                                     */
                                    $listServices = $myprofile->getServices();
                                    sort($listServices); ?>

                                    <select class="select-need-restart" profilename="<?= $profileName ?>" multiple>
                                        <?php
                                        foreach ($listServices as $service) {
                                            if (in_array($service, $profileConfNeedRestart)) {
                                                echo '<option value="' . $service . '" selected>' . $service . '</option>';
                                            } else {
                                                echo '<option value="' . $service . '">' . $service . '</option>';
                                            }
                                        } ?>
                                    </select>

                                    <br><br>

                                    <div class="flex align-item-center column-gap-4">
                                        <span>Linupdate should automatically get its configuration from this profile on each execution </span>
                                        <label class="onoff-switch-label">
                                            <input id="profile-linupdate-get-pkg-conf" profilename="<?= $profileName ?>" type="checkbox" class="onoff-switch-input" <?php echo ($linupdateGetPkgConf == 'true') ? 'checked' : ''; ?>>
                                            <span class="onoff-switch-slider"></span>
                                        </label>
                                    </div>

                                    <br><br>

                                    <h5>Notes</h5>

                                    <textarea class="profile-conf-notes" profilename="<?= $profileName ?>"><?= $profileNotes ?></textarea>
                                    <?php
                                endif;

                                /**
                                 *  On n'affiche pas le bouton Enregistrer si les 2 paramètres ci-dessous sont tous les 2 à no
                                 */
                                if ($serverManageClientRepos == "yes" or $serverManageClientConf == "yes") {
                                    echo '<button type="submit" class="btn-large-green">Save</button>';
                                } ?>
                            </form>
                        </div>
                    </div>
                    <?php
                endforeach ?>
            </div>
            <?php
        endif ?>
    </div>
</section>

<?php include_once(ROOT . '/views/includes/panels/manage-profiles-server-settings.inc.php'); ?>