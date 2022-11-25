<!DOCTYPE html>
<html>
<?php
require_once('../controllers/Autoloader.php');
\Controllers\Autoloader::load();
include_once('../includes/head.inc.php');

/**
 *  Seuls les admins ont accès à configuration.php
 */
if (!Controllers\Common::isadmin()) {
    header('Location: index.php');
    exit;
}

/**
 *  Instanciation d'un objet Profile
 */
$myprofile = new \Controllers\Profile();

/**
 *  On tente de récupérer la configuration serveur en base de données
 */
$serverConfiguration = $myprofile->getServerConfiguration();

/**
 *  Si certaines valeurs sont vides alors on set des valeurs par défaut déterminées par l'autoloader, car tous les champs doivent être complétés.
 *  On indiquera à l'utilisateur qu'il faudra valider le formulaire pour appliquer la configuration.
 */
$serverConfApplyNeeded = 0;

if (!empty($serverConfiguration['Package_type'])) {
    $serverPackageType = $serverConfiguration['Package_type'];
} else {
    /**
     *  Si aucun type de paquets n'est spécifié alors on va déduire en fonction du type de système sur lequel repomanager est installé
     */
    if (OS_FAMILY == 'Redhat') {
        $serverPackageType = 'rpm';
    }
    if (OS_FAMILY == 'Debian') {
        $serverPackageType = 'deb';
    }
    $serverConfApplyNeeded++;
}

if (!empty($serverConfiguration['Manage_client_conf'])) {
    $serverManageClientConf = $serverConfiguration['Manage_client_conf'];
} else {
    $serverManageClientConf = 'no';
    $serverConfApplyNeeded++;
}

if (!empty($serverConfiguration['Manage_client_repos'])) {
    $serverManageClientRepos = $serverConfiguration['Manage_client_repos'];
} else {
    $serverManageClientRepos = 'no';
    $serverConfApplyNeeded++;
} ?>

<body>
<?php include_once('../includes/header.inc.php'); ?>

<article>
    <section class="mainSectionLeft">
        <div id="title-button-div">
            <h3>PROFILES</h3>

            <?php if (\Controllers\Common::isadmin()) : ?>
                <div id="title-button-container">
                    <div id="profileServerSettingsToggleButton" class="slide-btn" title="Edit server settings">
                        <img src="resources/icons/cog.svg" />
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

        <br>

        <div id="profilesDiv">
            <p>Create a new profile:</p>
            <form id="newProfileForm" action="profiles.php" method="post" autocomplete="off">
                <input id="newProfileInput" type="text" class="input-medium" />
                <button type="submit" class="btn-xxsmall-green" title="Add">+</button>
            </form>
            <br>

            <?php
            /**
             *  Récupération de tous les noms de profils
             */
            $profiles = $myprofile->list();

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
                        if (MANAGE_HOSTS == 'yes') {
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
                                            if (MANAGE_HOSTS == 'yes' and $hostsCount > 0) {
                                                echo '<span class="hosts-count mediumopacity" title="' . $hostsCount . ' host(s) using this profile">' . $hostsCount . '<img src="resources/icons/server.svg" class="icon" /></span>';
                                            } ?>
                                            <span><img src="resources/icons/cog.svg" class="profileConfigurationBtn icon-mediumopacity" profilename="<?=$profileName?>" title="<?=$profileName?> configuration" /></span>
                                            <span><img src="resources/icons/duplicate.svg" class="duplicateProfileBtn icon-mediumopacity" profilename="<?=$profileName?>" title="Create a new profile from <?=$profileName?> configuration" /></span>
                                            <span><img src="resources/icons/bin.svg" class="deleteProfileBtn icon-mediumopacity" profilename="<?=$profileName?>" title="Delete <?=$profileName?> profile" /></span>
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
                                        <h5>Access following repositories:</h5>
                                        <p>Specify what repositories the host(s) will have access to.<br>Repos files will be retrieved by hosts on each linupdate execution.</p>
                                        <table class="table-large">
                                            <tr>
                                                <td colspan="100%">
                                                    <select class="select-repos" profilename="<?= $profileName ?>" multiple>
                                                        <?php
                                                        /**
                                                         *  On récupère la liste des repos actifs
                                                         *  Puis pour chaque repos, on regarde si celui-ci est déjà présent dans le profil, si c'est le cas il sera affiché sélectionné dans la liste déroulante, si ce n'est pas le cas il sera disponible dans la liste déroulante
                                                         */
                                                        $myrepo = new \Controllers\Repo();
                                                        $repos = $myrepo->listNameOnly(true);

                                                        foreach ($repos as $repo) {
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
                                                        } ?>
                                                    </select>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="td-fit" title="Linupdate should automatically get its repos files from this profile on each execution">Linupdate should automatically get its repos files from this profile on each execution</td>
                                                <td>
                                                    <label class="onoff-switch-label">
                                                        <input id="profile-linupdate-get-repos-conf" profilename="<?= $profileName ?>" type="checkbox" class="onoff-switch-input" <?php echo ($linupdateGetReposConf == 'true') ? 'checked' : ''; ?>>
                                                        <span class="onoff-switch-slider"></span>
                                                    </label>
                                                </td>
                                            </tr>
                                        </table>
                                        <hr>
                                        <?php
                                    endif;

                                    /**
                                     *  Si le serveur est configuré pour gérer la conf des serveurs clients alors on affiche la configuration pour chaque profil
                                     */
                                    if ($serverManageClientConf == "yes") :
                                        $myprofile = new \Controllers\Profile();
                                        $listPackages = $myprofile->getPackages();
                                        /**
                                         *  Liste des paquets sélectionnables dans la liste des paquets à exclure
                                         *  explode cette liste pour retourner un tableau, puis tri par ordre alpha
                                         */
                                        sort($listPackages);

                                        /**
                                         *  Pour chaque paquet de cette liste, si celui-ci apparait dans $profileConfExcludeMajor alors on l'affiche comme sélectionné "selected"
                                         */ ?>

                                        <h5>Packages to exclude on major version update:</h5>

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
                                        <br>

                                        <h5>Packages to exclude (no matter the version):</h5>

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
                                        <br>

                                        <h5>Services to restart after package update:</h5>

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
                                        <br>

                                        <table class="table-large">
                                            <tr>
                                                <td class="td-fit" title="Linupdate should automatically get its configuration from this profile on each execution">Linupdate should automatically get its configuration from this profile on each execution</td>
                                                <td>
                                                    <label class="onoff-switch-label">
                                                        <input id="profile-linupdate-get-pkg-conf" profilename="<?= $profileName ?>" type="checkbox" class="onoff-switch-input" <?php echo ($linupdateGetPkgConf == 'true') ? 'checked' : ''; ?>>
                                                        <span class="onoff-switch-slider"></span>
                                                    </label>
                                                </td>
                                            </tr>
                                        </table>

                                        <h5>Notes:</h5>
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
</article>

<div id="profileServerSettingsDiv" class="param-slide-container">
    <div class="param-slide">
        <img id="profileServerSettingsDivCloseButton" title="Close" class="close-btn lowopacity float-right" src="resources/icons/close.svg" />
        <h3>SERVER SETTINGS</h3>
        <form id="applyServerConfigurationForm" autocomplete="off">
            <?php
            /**
             *  Si une des valeurs était vide alors on indique à l'utilisateur qu'il faut valider le formulaire au moins une fois pour valider et appliquer la configuration.
             */
            if ($serverConfApplyNeeded > 0) {
                echo '<p><img src="resources/icons/warning.png" class="icon" />Some parameters were empty and have been generated automatically. You must validate this form to apply configuration.<br><br></p>';
            } ?>

            <div class="operation-form">
                <input type="hidden" id="serverPackageTypeInput" class="td-medium" value="<?=$serverPackageType?>" />
                <span>
                    <img src="resources/icons/info.svg" class="icon-verylowopacity" title="If enabled, this server will be able to specify repos files for each profile." />Manage profiles repos configuration
                </span>
                <label class="onoff-switch-label">
                    <input id="serverManageClientRepos" type="checkbox" class="onoff-switch-input" value="yes" <?php echo ($serverManageClientRepos == "yes") ? 'checked' : ''; ?>>
                    <span class="onoff-switch-slider"></span>
                </label>
                <span>
                    <img src="resources/icons/info.svg" class="icon-verylowopacity" title="If enabled, this server will be able to specify which package(s) to exclude for each profile." />Manage profiles packages configuration
                </span>
                <label class="onoff-switch-label">
                    <input id="serverManageClientConf" type="checkbox" class="onoff-switch-input" value="yes" <?php echo ($serverManageClientConf == "yes") ? 'checked' : ''; ?>>
                    <span class="onoff-switch-slider"></span>
                </label>
            </div>
            <br>
            <button type="submit" class="btn-large-green">Save</button>
        </form>
    </div>
</div>

<?php include_once('../includes/footer.inc.php'); ?>

</body>
</html>