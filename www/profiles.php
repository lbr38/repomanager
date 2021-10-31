<!DOCTYPE html>
<html>
<?php include('includes/head.inc.php'); ?>

<?php
/**
 *  Import des variables et fonctions nécessaires
 */
require_once('functions/load_common_variables.php');
require_once('functions/load_display_variables.php');
require_once('functions/common-functions.php');
require_once('common.php');
require_once('class/Repo.php');
require_once('class/Profile.php');

// Créer le répertoire principal des profils si n'existe pas
if (!file_exists($PROFILES_MAIN_DIR)) mkdir($PROFILES_MAIN_DIR, 0775, true);

// Créer le répertoire qui accueille les fichiers de conf .list ou .repo si n'existe pas
if (!file_exists($REPOS_PROFILES_CONF_DIR)) mkdir($REPOS_PROFILES_CONF_DIR, 0775, true);

// Créer le répertoire qui accueille le fichier de conf du serveur de repo
if (!file_exists($REPOSERVER_PROFILES_CONF_DIR)) mkdir($REPOSERVER_PROFILES_CONF_DIR, 0775, true);

// Créer le fichier de conf du serveur n'existe pas on le crée
if (!file_exists($PROFILE_SERVER_CONF)) touch($PROFILE_SERVER_CONF);

/**
 *  Cas où on souhaite modifier la conf serveur
 */
if (!empty($_POST['action']) AND validateData($_POST['action']) === "applyServerConfiguration") {
    if (!empty($_POST['serverConf_manageClientsConf'])) { $serverConf_manageClientsConf = validateData($_POST['serverConf_manageClientsConf']); } else { $serverConf_manageClientsConf = 'no'; }
    if (!empty($_POST['serverConf_manageClients_reposConf'])) { $serverConf_manageClients_reposConf = validateData($_POST['serverConf_manageClients_reposConf']); } else { $serverConf_manageClients_reposConf = 'no'; }

    // On forge le bloc de conf qu'on va écrire dans le fichier
    $conf = "[REPOSERVER]\n";
    //$conf = "${conf}URL=\"https://${WWW_HOSTNAME}\"\n";
    $conf .= "URL=\"http://${WWW_HOSTNAME}\"\n";
    $conf .= "PROFILES_URL=\"${WWW_PROFILES_DIR_URL}\"\n";
    $conf .= "OS_FAMILY=\"${OS_FAMILY}\"\n";
    $conf .= "OS_NAME=\"${OS_NAME}\"\n";
    $conf .= "OS_VERSION=\"${OS_VERSION}\"\n";
    // Sur les systèmes CentOS il est possible de modifier la variable releasever, permettant de faire des miroirs de version de paquets différent de l'OS
    // Si c'est le cas, ($RELEASEVER différent de la version d'OS_VERSION alors il faut indiquer aux serveurs clients que ce serveur gère des paquets de version $RELEASEVER)
    if (!empty($RELEASEVER) AND $RELEASEVER !== $OS_VERSION) {
        $conf = "${conf}PACKAGES_OS_VERSION=\"${RELEASEVER}\"\n";
    }
    $conf = "${conf}MANAGE_CLIENTS_CONF=\"${serverConf_manageClientsConf}\"\n";
    $conf = "${conf}MANAGE_CLIENTS_REPOSCONF=\"${serverConf_manageClients_reposConf}\"\n";

    // Ajout de la conf au fichier de conf serveur
    file_put_contents("$PROFILE_SERVER_CONF", $conf);

    // Affichage d'un message
    printAlert("La configuration du serveur a été enregistrée", 'success');
}

/**
 *  Création d'un nouveau profil
 */
if (!empty($_POST['newProfile'])) {
    $myProfile = new Profile(array('profileName' => validateData($_POST['newProfile'])));
    $myProfile->new();
}

/**
 *  Cas où on souhaite supprimer un profil
 */
if (!empty($_GET['action']) AND (validateData($_GET['action']) == "deleteprofile") AND !empty($_GET['profileName'])) {
    $myProfile = new Profile(array('profileName' => validateData($_GET['profileName'])));
    $myProfile->delete();
}

/**
 *  Cas où on souhaite renommer un profil
 */
if (!empty($_POST['profileName']) AND !empty($_POST['actualProfileName'])) {
    $myProfile = new Profile(array('profileName' => validateData($_POST['actualProfileName']), 'newProfileName' => validateData($_POST['profileName'])));
    $myProfile->rename();
}

/**
 *  Cas où on modifie la configuration d'un profil (repos, exclusions...)
 */
if (!empty($_POST['action']) AND (validateData($_POST['action']) == "manageProfileConfiguration") AND !empty($_POST['profileName'])) {
    $myProfile = new Profile(array('profileName' => validateData($_POST['profileName'])));
    $myProfile->configure();
}

/**
 *  Duplication d'un profil et sa configuration
 */
if (!empty($_GET['action']) AND (validateData($_GET['action']) == "duplicateprofile") AND !empty($_GET['profileName'])) {
    $myProfile = new Profile(array('profileName' => validateData($_GET['profileName'])));
    $myProfile->duplicate();
}

/**
 *  Récupération de la conf dans le fichier de conf serveur
 */
$serverConf_manageClientsConf = exec("grep '^MANAGE_CLIENTS_CONF=' ${PROFILE_SERVER_CONF} | cut -d'=' -f2 | sed 's/\"//g'");
$serverConf_manageClients_reposConf = exec("grep '^MANAGE_CLIENTS_REPOSCONF=' ${PROFILE_SERVER_CONF} | cut -d'=' -f2 | sed 's/\"//g'");
?>

<body>
<?php include('includes/header.inc.php'); ?>

<article>
<section class="mainSectionLeft">
    <!-- REPOS ACTIFS -->
    <section class="left">
        <h3>PROFILS</h3>
        <p>Vous pouvez créer des profils de configuration pour vos serveurs clients utilisant <?php if ($OS_FAMILY == "Redhat") { echo "yum-update-auto"; } if ($OS_FAMILY == "Debian") { echo "apt-update-auto"; } ?>.<br>A chaque exécution d'une mise à jour, les clients récupèreront automatiquement leur configuration et leurs fichiers de repo depuis ce serveur de repo.</p>
        <br>
        <p>Créer un nouveau profil :</p>
        <form action="profiles.php" method="post" autocomplete="off">
            <input type="text" name="newProfile" class="input-medium" />
            <button type="submit" class="button-submit-xxsmall-blue" title="Ajouter">+</button>
        </form>
        <br>
        <?php
        $j = 0;

        /**
         *  Récupération de tous les noms de profils
         */
        $profilesNames = scandir($PROFILES_MAIN_DIR); // 

        /**
         *  Tri des profils afin de les afficher dans l'ordre alpha
         */
        sort($profilesNames);

        if (!empty($profilesNames)) {
            $repo = new Repo();

            echo '<p><b>PROFILS ACTIFS</b></p>';
            echo '<div class="profileDivContainer">';

            /**
             *  Affichage des profils et leur configuration
             */
            foreach($profilesNames as $profileName) {
                if (($profileName != "..") AND ($profileName != ".") AND ($profileName != "_configurations") AND ($profileName != "_reposerver") AND ($profileName != "${PROFILE_SERVER_CONF}")) {
                    echo '<div class="profileDiv">';
                        echo '<form action="profiles.php" method="post" autocomplete="off">';
                            echo '<table class="table-large">';
                                // On veut pouvoir renommer les profils, donc il faut transmettre le nom de profil actuel (actualProfileName),
                                echo "<input type=\"hidden\" name=\"actualProfileName\" value=\"${profileName}\" />";
                                // ainsi qu'afficher ce même profil actuel dans un input type=text qui permettra d'en renseigner un nouveau (profileName) :
                                echo '<tr>';
                                echo '<td>';
                                echo "<input type=\"text\" value=\"${profileName}\" name=\"profileName\" class=\"invisibleInput-blue\" />";
                                echo '</td>';
                                echo '<td class="td-fit">';
                                echo "<img id=\"profileConfigurationToggleButton-${profileName}\" title=\"Configuration de $profileName\" class=\"icon-mediumopacity\" src=\"icons/cog.png\" />";
                                echo "<a href=\"?action=duplicateprofile&profileName=${profileName}\" title=\"Créer un nouveau profil en dupliquant la configuration de $profileName\"><img class=\"icon-mediumopacity\" src=\"icons/duplicate.png\" /></a>";         
                                // Bouton supprimer le profil
                                echo "<img class=\"profileDeleteToggleButton-${profileName} icon-mediumopacity\" title=\"Supprimer le profil ${profileName}\" src=\"icons/bin.png\" />";
                                deleteConfirm("Etes-vous sûr de vouloir supprimer le profil <b>$profileName</b>", "?action=deleteprofile&profileName=${profileName}", "profileDeleteDiv-${profileName}", "profileDeleteToggleButton-${profileName}");
                                echo '</td>';
                                echo '</tr>';
                            echo '</table>';
                        echo '</form>';

                        // Configuration de ce profil dans un div caché, affichable en cliquant sur la roue crantée //
                        echo "<div id=\"profileConfigurationDiv-${profileName}\" class=\"hide profileDivConf\">";
                        echo '<form action="profiles.php" method="post" autocomplete="off">';
                        // Il faut transmettre le nom du profil dans le formulaire, donc on ajoute un input caché avec le nom du profil
                        echo "<input type=\"hidden\" name=\"profileName\" value=\"${profileName}\" />";
                        echo '<input type="hidden" name="action" value="manageProfileConfiguration" />';
                        if ($serverConf_manageClients_reposConf == "yes") {
                            if ($OS_FAMILY == "Redhat") echo '<p>Repos :</p>';
                            if ($OS_FAMILY == "Debian") echo '<p>Sections de repos :</p>';
                            echo '<table class="table-large">';
                                echo '<tr>';
                                    echo '<td colspan="100%">';
                                        echo '<select class="reposSelectList" name="profileRepos[]" multiple>';
                                            /**
                                             *  On récupère la liste des repos actifs
                                             *  Puis pour chaque repos, on regarde si celui-ci est déjà présent dans le profil, si c'est le cas il sera affiché sélectionné dans la liste déroulante, si ce n'est pas le cas il sera disponible dans la liste déroulante 
                                             */
                                            $reposList = $repo->listAll_distinct();
                                            foreach($reposList as $myrepo) {
                                                $repoName = $myrepo['Name'];
                                                if ($OS_FAMILY == "Debian") {
                                                    $repoDist = $myrepo['Dist'];
                                                    $repoSection = $myrepo['Section'];
                                                }
                                                if ($OS_FAMILY == "Redhat") {
                                                    // Si un fichier de repo existe dans ce profil, alors on génère une option "selected" pour indiquer que le repo est déjà présent dans ce profil
                                                    if (file_exists("${PROFILES_MAIN_DIR}/${profileName}/${REPO_CONF_FILES_PREFIX}${repoName}.repo")) {
                                                        echo "<option value=\"${repoName}\" selected>${repoName}</option>";
                                                    } else {
                                                        echo "<option value=\"${repoName}\">${repoName}</option>";
                                                    }
                                                }
                                                if ($OS_FAMILY == "Debian") {
                                                    /**
                                                     *  Si le nom de la distribution comporte un slash, alors on remplace '/' par '--slash--' car c'est comme cela qu'il sera écrit dans le nom du fichier
                                                     */
                                                    if (preg_match('#/#', $repoDist)) {
                                                        $repoDistFormatted = str_replace('/', '--slash--', $repoDist);

                                                        // Si un fichier de repo existe dans ce profil, alors on génère une option "selected" pour indiquer que le repo est déjà présent dans ce profil
                                                        if (file_exists("${PROFILES_MAIN_DIR}/${profileName}/${REPO_CONF_FILES_PREFIX}${repoName}_${repoDistFormatted}_${repoSection}.list")) {
                                                            echo "<option value=\"${repoName}|${repoDist}|${repoSection}\" selected>${repoName} - ${repoDist} - ${repoSection}</option>";
                                                        } else {
                                                            echo "<option value=\"${repoName}|${repoDist}|${repoSection}\">${repoName} - ${repoDist} - ${repoSection}</option>";
                                                        }
                                                    } else {
                                                        // Si un fichier de repo existe dans ce profil, alors on génère une option "selected" pour indiquer que le repo est déjà présent dans ce profil
                                                        if (file_exists("${PROFILES_MAIN_DIR}/${profileName}/${REPO_CONF_FILES_PREFIX}${repoName}_${repoDist}_${repoSection}.list")) {
                                                            echo "<option value=\"${repoName}|${repoDist}|${repoSection}\" selected>${repoName} - ${repoDist} - ${repoSection}</option>";
                                                        } else {
                                                            echo "<option value=\"${repoName}|${repoDist}|${repoSection}\">${repoName} - ${repoDist} - ${repoSection}</option>";
                                                        }
                                                    }
                                                }
                                            }
                                        echo '</select>';
                                    echo '</td>';
                                echo '</tr>';
                            echo '</table>';
                            echo '<br>';
                            echo '<hr>';
                            echo '<br>'; 
                        }

                        /**
                         *  Si le serveur est configuré pour gérer la conf des serveurs clients alors on affiche la configuration pour chaque profil
                         */
                        if ($serverConf_manageClientsConf == "yes") {
                            $myProfile = new Profile();

                            /**
                             *  On récupére la conf du profil contenue dans le fichier "config"
                             */
                            $profileConf_excludeMajor = exec("grep '^EXCLUDE_MAJOR=' ${PROFILES_MAIN_DIR}/${profileName}/config | cut -d'=' -f2 | sed 's/\"//g'");
                            $profileConf_exclude = exec("grep '^EXCLUDE=' ${PROFILES_MAIN_DIR}/${profileName}/config | cut -d'=' -f2 | sed 's/\"//g'");
                            $profileConf_needRestart = exec("grep '^NEED_RESTART=' ${PROFILES_MAIN_DIR}/${profileName}/config | cut -d'=' -f2 | sed 's/\"//g'");
                            $profileConf_keepCron = exec("grep '^KEEP_CRON=' ${PROFILES_MAIN_DIR}/${profileName}/config | cut -d'=' -f2 | sed 's/\"//g'");
                            $profileConf_allowOverwrite = exec("grep '^ALLOW_OVERWRITE=' ${PROFILES_MAIN_DIR}/${profileName}/config | cut -d'=' -f2 | sed 's/\"//g'");
                            $profileConf_allowReposFilesOverwrite = exec("grep '^ALLOW_REPOSFILES_OVERWRITE=' ${PROFILES_MAIN_DIR}/${profileName}/config | cut -d'=' -f2 | sed 's/\"//g'");

                            echo '<p>Paquets à exclure en cas de version majeure :</p>';
                            $profileConf_excludeMajor = explode(',', $profileConf_excludeMajor);
                            $profileConf_exclude = explode(',', $profileConf_exclude);
                            $profileConf_needRestart = explode(',', $profileConf_needRestart);

                            /**
                             *  Liste des paquets sélectionnables dans la liste des paquets à exclure
                             *  explode cette liste pour retourner un tableau, puis tri par ordre alpha
                             */
                            $listPackages = $myProfile->db_getPackages();
                            sort($listPackages);

                            /**
                             *  Pour chaque paquet de cette liste, si celui-ci apparait dans $profileConf_excludeMajor alors on l'affiche comme sélectionné "selected"
                             */
                            echo '<select class="excludeMajorSelectList" name="profileConf_excludeMajor[]" multiple>';
                            foreach($listPackages as $package) {
                                if (in_array($package, $profileConf_excludeMajor)) {
                                    echo "<option value=\"$package\" selected>${package}</option>";
                                } else {
                                    echo "<option value=\"$package\">${package}</option>";
                                }
                                /**
                                 *  On vérifie la même chose pour ce même paquet suivi d'un wildcard (ex: apache.*)
                                 */
                                if (in_array("${package}.*", $profileConf_excludeMajor)) {
                                    echo "<option value=\"${package}.*\" selected>${package}.*</option>";
                                } else {
                                    echo "<option value=\"${package}.*\">${package}.*</option>";
                                }
                            }
                            echo '</select>';
                            echo '<br>';
                            echo '<p>Paquets à exclure (toute version) :</p>';
                            echo '<select class="excludeSelectList" name="profileConf_exclude[]" multiple>';
                            foreach($listPackages as $package) {
                                if (in_array($package, $profileConf_exclude)) {
                                    echo "<option value=\"$package\" selected>${package}</option>";
                                } else {
                                    echo "<option value=\"$package\">${package}</option>";
                                }
                                /**
                                 *  On fait la même chose pour ce même paquet suivi d'un wildcard (ex: apache.*)
                                 */
                                if (in_array("${package}.*", $profileConf_exclude)) {
                                    echo "<option value=\"${package}.*\" selected>${package}.*</option>";
                                } else {
                                    echo "<option value=\"${package}.*\">${package}.*</option>";
                                }
                            }
                            echo '</select>';
                            echo '<br>';
                            echo '<p>Services à redémarrer en cas de mise à jour :</p>';
                            
                            /**
                             *  Liste des services sélectionnables dans la liste des services à redémarrer
                             *  explode cette liste pour retourner un tableau, puis tri par ordre alpha
                             */
                            $listServices = $myProfile->db_getServices();
                            sort($listServices);

                            echo '<select class="needRestartSelectList" name="profileConf_needRestart[]" multiple>';
                            foreach($listServices as $service) {
                                if (in_array($service, $profileConf_needRestart)) {
                                    echo "<option value=\"$service\" selected>${service}</option>";
                                } else {
                                    echo "<option value=\"$service\">${service}</option>";
                                }
                            }
                            echo '</select>';
                            echo '<br>';
                            echo '<table class="table-large">';
                                echo '<tr>';
                                    echo '<td class="td-fit" title="Conserver ou non la tâche cron après exécution de la mise à jour">Conserver la tâche cron</td>';
                                    echo '<td>';
                                    echo '<label class="onoff-switch-label">';
                                    echo '<input name="profileConf_keepCron" type="checkbox" class="onoff-switch-input" value="yes"'; if ($profileConf_keepCron == "yes") { echo 'checked'; } echo ' />';
                                    echo '<span class="onoff-switch-slider"></span>';
                                    echo '</label>';
                                    echo '</td>';
                                echo '</tr>';
                                echo '<tr>';
                                    echo '<td class="td-fit" title="Autoriser linux-autoupdate à récupérer et écraser sa conf à chaque exécution">Autoriser la mise à jour auto. de la configuration</td>';
                                    echo '<td>';
                                    echo '<label class="onoff-switch-label">';
                                    echo '<input name="profileConf_allowOverwrite" type="checkbox" class="onoff-switch-input" value="yes"'; if ($profileConf_allowOverwrite == "yes") { echo 'checked'; } echo ' />';
                                    echo '<span class="onoff-switch-slider"></span>';
                                    echo '</td>';
                                echo '</tr>';
                                echo '<tr>';
                                    echo '<td class="td-fit" title="Autoriser linux-autoupdate à récupérer automatiquement les fichiers .list ou .repo de son profil">Autoriser la mise à jour auto. des fichiers de repo</td>';
                                    echo '<td>';
                                    echo '<label class="onoff-switch-label">';
                                    echo '<input name="profileConf_allowReposFilesOverwrite" type="checkbox" class="onoff-switch-input" value="yes"'; if ($profileConf_allowReposFilesOverwrite == "yes") { echo 'checked'; } echo ' />';
                                    echo '<span class="onoff-switch-slider"></span>';
                                    echo '</td>';
                                echo '</tr>';
                            echo '</table>';
                        }
                        // On n'affiche pas le bouton Enregistrer si les 2 paramètres ci-dessous sont tous les 2 à no :
                        if ($serverConf_manageClients_reposConf == "yes" OR $serverConf_manageClientsConf == "yes") {
                            echo '<button type="submit" class="button-submit-large-green">Enregistrer</button>';
                        }
                        echo '</form>';
                        echo '</div>'; // Fermture de profileConfigurationDiv
                        // Afficher ou masquer la div 'profileConfigurationDiv' :
                        echo "<script>";
                        echo "$(document).ready(function(){";
                        echo "$(\"#profileConfigurationToggleButton-${profileName}\").click(function(){";
                        echo "$(\"div#profileConfigurationDiv-${profileName}\").slideToggle(150);";
                        echo '$(this).toggleClass("open");';
                        echo "});";
                        echo "});";
                        echo "</script>";
                    echo '</div>'; // Fermeture du profileDiv
                }
            }
            echo '</div>';
        }
        unset($j);
        ?>
    </section>
</section>

<section class="mainSectionRight">
    <section class="right">
        <h3>CONFIGURATION DE CE SERVEUR</h3>
        <form action="profiles.php" method="post" autocomplete="off">
            <input type="hidden" name="action" value="applyServerConfiguration" />
            <table class="table-large background-gray">
            <tr>
                <td><img src="icons/info.png" class="icon-verylowopacity" title="Permet aux serveurs clients de récupérer la configuration de leur profil avec http. Sous-répertoire du répertoire des repos. Non-modifiable." />URL d'accès aux profils</td>
                <td><input type="text" class="td-medium" value="<?php echo $WWW_PROFILES_DIR_URL;?>" readonly /></td>
            </tr>
            <tr>
                <td><img src="icons/info.png" class="icon-verylowopacity" title="Famille d'OS que ce client gère. Défini en fonction de l'OS de ce serveur (non-modifiable). Seuls des serveurs clients de la même famille pourront récupérer leur configuration auprès de ce serveur." />Famille d'OS</td>
                <td><input type="text" class="td-medium" value="<?php echo $OS_FAMILY;?>" readonly /></td>
            </tr>
            <tr>
                <td><img src="icons/info.png" class="icon-verylowopacity" title="OS de ce serveur. Les serveurs clients appartenant à la même famille que ce serveur mais pas au même OS pourront tout de même récupérer leur configuration auprès de ce serveur si les repos sont compatibles." />Nom de l'OS</td>
                <td><input type="text" class="td-medium" value="<?php echo $OS_NAME;?>" readonly /></td>
            </tr>
            <tr>
                <td><img src="icons/info.png" class="icon-verylowopacity" title="Version d'OS de ce serveur" />Version d'OS</td>
                <td><input type="text" class="td-medium" value="<?php echo $OS_VERSION;?>" readonly /></td>
            </tr>
            <?php
            if (!empty($RELEASEVER) AND $RELEASEVER !== $OS_VERSION) {
                echo '<tr>';
                echo '<td><img src="icons/info.png" class="icon-verylowopacity" title="Version d\'OS des paquets récupérés lors de la création de miroirs." />Version de paquets gérée</td>';
                echo "<td><input type=\"text\" class=\"td-medium\" value=\"$RELEASEVER\" readonly /></td>";
                echo '</tr>';
            }
            ?>
            <tr>
                <td><img src="icons/info.png" class="icon-verylowopacity" title="Si activé, ce serveur pourra choisir les paquets à exclure ou quels service redémarrer pour chaque profil de configuration. Cependant les clients qui téléchargeront la configuration de leur profil resteront en droit d'accepter ou non que ce serveur gère leur configuration." />Gérer la configuration des clients</td>
                <td class="td-medium">
                    <label class="onoff-switch-label">
                    <input name="serverConf_manageClientsConf" type="checkbox" class="onoff-switch-input" value="yes" <?php if ($serverConf_manageClientsConf == "yes") { echo 'checked'; }?> />
                    <span class="onoff-switch-slider"></span>
                    </label>
                </td>
                <?php if (empty($serverConf_manageClientsConf)) {
                echo '<td class="td-fit"><img src="icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" /></td>';
                } ?>
            </tr>
            <tr>
                <td><img src="icons/info.png" class="icon-verylowopacity" title="Si activé, ce serveur pourra choisir les repos à déployer pour chaque profil de configuration. Cependant les clients qui téléchargeront la configuration de leur profil resteront en droit d'accepter ou non que ce serveur gère leur configuration." />Gérer la configuration des repos clients</td>
                <td class="td-medium">
                    <label class="onoff-switch-label">
                    <input name="serverConf_manageClients_reposConf" type="checkbox" class="onoff-switch-input" value="yes" <?php if ($serverConf_manageClients_reposConf == "yes") { echo 'checked'; }?> />
                    <span class="onoff-switch-slider"></span>
                    </label>
                </td>
                <?php if (empty($serverConf_manageClients_reposConf)) {
                echo '<td class="td-fit"><img src="icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" /></td>';
                } ?>
            </tr>
            <tr>
                <td colspan="100%"><button type="submit" class="button-submit-large-green">Enregistrer</button></td>
            </tr>
            </table>
        </form>
    </section>
</section>
</article>

<?php include('includes/footer.inc.php'); ?>
</body>
<script>
// Scripts Select2 pour transformer un select multiple en liste déroulante
$('.reposSelectList').select2({
    closeOnSelect: false,
    placeholder: 'Ajouter un repo ✎'
});
$('.excludeMajorSelectList').select2({
    closeOnSelect: false,
    placeholder: 'Sélectionner un paquet ✎',
    tags: true
});
$('.excludeSelectList').select2({
    closeOnSelect: false,
    placeholder: 'Sélectionner un paquet ✎',
    tags: true
});
$('.needRestartSelectList').select2({
    closeOnSelect: false,
    placeholder: 'Sélectionner un service ✎',
    tags: true
});
</script>
</html>