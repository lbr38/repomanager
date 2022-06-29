<!DOCTYPE html>
<html>
<?php
require_once('../controllers/Autoloader.php');
\Controllers\Autoloader::load();
include_once('../includes/head.inc.php');

/**
 *  Seuls les admins ont accès à configuration.php
 */
if (!Models\Common::isadmin()) {
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
    <section id="profilesDiv" class="left">
        <h3>PROFILS</h3>
        <p>Vous pouvez créer des profils de configuration pour vos hôtes et serveurs clients utilisant <a href="https://github.com/lbr38/linupdate"><b>linupdate</b></a>.<br>A chaque exécution d'une mise à jour, les clients récupèreront automatiquement leur configuration et leurs fichiers de repo depuis ce serveur de repo.</p>
        <br>
        <p>Créer un nouveau profil :</p>
        <form id="newProfileForm" action="profiles.php" method="post" autocomplete="off">
            <input id="newProfileInput" type="text" class="input-medium" />
            <button type="submit" class="btn-xxsmall-blue" title="Ajouter">+</button>
        </form>
        <br>
        <?php

        /**
         *  Récupération de tous les noms de profils
         */

        $profiles = $myprofile->list();

        if (!empty($profiles)) {
            echo '<h5>PROFILS ACTIFS</h5>';
            echo '<div class="profileDivContainer">';

            /**
             *  Affichage des profils et leur configuration
             */
            foreach ($profiles as $profile) {
                /**
                 *  Récupération de la configuration du profil
                 */
                $profileId = $profile['Id'];
                $profileName = $profile['Name'];
                $profileConf_exclude = explode(',', $profile['Package_exclude']);
                $profileConf_excludeMajor = explode(',', $profile['Package_exclude_major']);
                $profileConf_needRestart = explode(',', $profile['Service_restart']);
                $profileConf_allowOverwrite = $profile['Allow_overwrite'];
                $profileConf_allowReposFilesOverwrite = $profile['Allow_repos_overwrite'];
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
                                        echo '<span class="hosts-count mediumopacity" title="' . $hostsCount . ' hôte(s) utilise(nt) ce profil">' . $hostsCount . '<img src="ressources/icons/server.png" class="icon" /></span>';
                                    } ?>
                                    <span><img src="ressources/icons/cog.png" class="profileConfigurationBtn icon-mediumopacity" profilename="<?=$profileName?>" title="Configuration de <?=$profileName?>" /></span>
                                    <span><img src="ressources/icons/duplicate.png" class="duplicateProfileBtn icon-mediumopacity" profilename="<?=$profileName?>" title="Créer un nouveau profil en dupliquant la configuration de <?=$profileName?>" /></span>
                                    <span><img src="ressources/icons/bin.png" class="deleteProfileBtn icon-mediumopacity" profilename="<?=$profileName?>" title="Supprimer le profil <?=$profileName?>" /></span>
                                </td>
                            </tr>
                        </table>
                    </form>
            
                    <div id="profileConfigurationDiv-<?=$profileName?>" class="hide profileDivConf">
                        <form class="profileConfigurationForm" profilename="<?=$profileName?>" autocomplete="off">
                            <?php
                            if ($serverManageClientRepos == "yes") : ?>
                                <h5>Repos :</h5>

                                <table class="table-large">
                                    <tr>
                                        <td colspan="100%">
                                            <select class="reposSelectList" profilename="<?=$profileName?>" name="profileRepos[]" multiple>
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
                                </table>
                                <br>
                                <hr>
                                <?php
                            endif;

                            /**
                             *  Si le serveur est configuré pour gérer la conf des serveurs clients alors on affiche la configuration pour chaque profil
                             */
                            if ($serverManageClientConf == "yes") {
                                echo '<h4>Paramétrage de linupdate</h4>';

                                $myprofile = new \Controllers\Profile();

                                echo '<h5>Paquets à exclure en cas de version majeure :</h5>';

                                /**
                                 *  Liste des paquets sélectionnables dans la liste des paquets à exclure
                                 *  explode cette liste pour retourner un tableau, puis tri par ordre alpha
                                 */
                                $listPackages = $myprofile->getPackages();
                                sort($listPackages);

                                /**
                                 *  Pour chaque paquet de cette liste, si celui-ci apparait dans $profileConf_excludeMajor alors on l'affiche comme sélectionné "selected"
                                 */ ?>
                                <select class="excludeMajorSelectList" profilename="<?=$profileName?>" name="profileConf_excludeMajor[]" multiple>

                                    <?php
                                    foreach ($listPackages as $package) {
                                        if (in_array($package, $profileConf_excludeMajor)) {
                                            echo '<option value="' . $package . '" selected>' . $package . '</option>';
                                        } else {
                                            echo '<option value="' . $package . '">' . $package . '</option>';
                                        }

                                        /**
                                         *  On vérifie la même chose pour ce même paquet suivi d'un wildcard (ex: apache.*)
                                         */
                                        if (in_array("${package}.*", $profileConf_excludeMajor)) {
                                            echo '<option value="' . $package . '.*" selected>' . $package . '.*</option>';
                                        } else {
                                            echo '<option value="' . $package . '.*">' . $package . '.*</option>';
                                        }
                                    } ?>
                                </select>
                                <br>
                                <h5>Paquets à exclure (toute version) :</h5>
                                <select class="excludeSelectList" profilename="<?php echo $profileName;?>" name="profileConf_exclude[]" multiple>

                                    <?php
                                    foreach ($listPackages as $package) {
                                        if (in_array($package, $profileConf_exclude)) {
                                            echo '<option value="' . $package . '" selected>' . $package . '</option>';
                                        } else {
                                            echo '<option value="' . $package . '">' . $package . '</option>';
                                        }

                                        /**
                                         *  On fait la même chose pour ce même paquet suivi d'un wildcard (ex: apache.*)
                                         */
                                        if (in_array("${package}.*", $profileConf_exclude)) {
                                            echo '<option value="' . $package . '.*" selected>' . $package . '.*</option>';
                                        } else {
                                            echo '<option value="' . $package . '.*">' . $package . '.*</option>';
                                        }
                                    } ?>
                                </select>
                                <br>

                                <h5>Services à redémarrer en cas de mise à jour :</h5>

                                <?php
                                /**
                                 *  Liste des services sélectionnables dans la liste des services à redémarrer
                                 *  explode cette liste pour retourner un tableau, puis tri par ordre alpha
                                 */
                                $listServices = $myprofile->getServices();
                                sort($listServices); ?>

                                <select class="needRestartSelectList" profilename="<?php echo $profileName;?>" name="profileConf_needRestart[]" multiple>
                                    
                                    <?php
                                    foreach ($listServices as $service) {
                                        if (in_array($service, $profileConf_needRestart)) {
                                            echo '<option value="' . $service . '" selected>' . $service . '</option>';
                                        } else {
                                            echo '<option value="' . $service . '">' . $service . '</option>';
                                        }
                                    } ?>
                                </select>
                                <br>

                                <table class="table-large">
                                    <tr>
                                        <td class="td-fit" title="Sur l'hôte client, autoriser linupdate à récupérer la configuration de ce profil à chaque exécution">Autoriser la mise à jour auto. de la configuration</td>
                                        <td>
                                            <label class="onoff-switch-label">
                                                <input id="profileConf_allowOverwrite" name="profileConf_allowOverwrite" profilename="<?php echo $profileName;?>" type="checkbox" class="onoff-switch-input" <?php echo ($profileConf_allowOverwrite == "yes") ? 'checked' : ''; ?>>
                                                <span class="onoff-switch-slider"></span>
                                            </label>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="td-fit" title="Sur l'hôte client, autoriser linupdate à récupérer la configuration des repos de ce profil à chaque exécution">Autoriser la mise à jour auto. des fichiers de repo</td>
                                        <td>
                                            <label class="onoff-switch-label">
                                                <input id="profileConf_allowReposFilesOverwrite" name="profileConf_allowReposFilesOverwrite" profilename="<?php echo $profileName;?>" type="checkbox" class="onoff-switch-input" <?php echo ($profileConf_allowReposFilesOverwrite == "yes") ? 'checked' : ''; ?>>
                                                <span class="onoff-switch-slider"></span>
                                            </label>
                                        </td>
                                    </tr>
                                </table>
                            <?php   }
                            /**
                             *  On n'affiche pas le bouton Enregistrer si les 2 paramètres ci-dessous sont tous les 2 à no
                             */
                            if ($serverManageClientRepos == "yes" or $serverManageClientConf == "yes") {
                                echo '<button type="submit" class="btn-large-green">Enregistrer</button>';
                            } ?>
                        </form>
                    </div>
                </div>
            <?php   }
            echo '</div>';
        } ?>
    </section>
</section>

<section class="mainSectionRight">
    <section class="right">
        <h3>CONFIGURATION</h3>

        <form id="applyServerConfigurationForm" class="operation-form-container" autocomplete="off">
            <?php
                /**
                 *  Si une des valeurs était vide alors on indique à l'utilisateur qu'il faut valider le formulaire au moins une fois pour valider et appliquer la configuration.
                 */
            if ($serverConfApplyNeeded > 0) {
                echo '<p><img src="ressources/icons/warning.png" class="icon" />Certains paramètres étaient vides et ont été générés automatiquement, vous devez valider ce formulaire pour appliquer la configuration.<br><br></p>';
            }
            ?>
            
            <h5>Configuration générale</h5>

            <div class="operation-form">
                <span>
                    <img src="ressources/icons/info.png" class="icon-verylowopacity" title="Type de paquets" />Type de paquets diffusés
                </span>
                <input type="text" id="serverPackageTypeInput" class="td-medium" value="<?=$serverPackageType?>" />

                <span>
                    <img src="ressources/icons/info.png" class="icon-verylowopacity" title="Si activé, ce serveur pourra choisir les paquets à exclure ou quels service redémarrer pour chaque profil de configuration. Cependant les clients qui téléchargeront la configuration de leur profil resteront en droit d'accepter ou non que ce serveur gère leur configuration." />Gérer la configuration des clients
                </span>

                <label class="onoff-switch-label">
                    <input id="serverManageClientConf" type="checkbox" class="onoff-switch-input" value="yes" <?php echo ($serverManageClientConf == "yes") ? 'checked' : ''; ?>>
                    <span class="onoff-switch-slider"></span>
                </label>

                <span>
                    <img src="ressources/icons/info.png" class="icon-verylowopacity" title="Si activé, ce serveur pourra choisir les repos à déployer pour chaque profil de configuration. Cependant les clients qui téléchargeront la configuration de leur profil resteront en droit d'accepter ou non que ce serveur gère leur configuration." />Gérer la configuration des repos clients
                </span>

                <label class="onoff-switch-label">
                    <input id="serverManageClientRepos" type="checkbox" class="onoff-switch-input" value="yes" <?php echo ($serverManageClientRepos == "yes") ? 'checked' : ''; ?>>
                    <span class="onoff-switch-slider"></span>
                </label>
            </div>
            <br>
            <button type="submit" class="btn-large-green">Enregistrer</button>
        </form>
    </section>
</section>
</article>

<?php include_once('../includes/footer.inc.php'); ?>
</body>
</html>