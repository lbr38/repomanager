<?php
/////// GESTION DES FORMULAIRES ET REQUETES GET COMMUNS ////////
// Des formulaires peuvent être communs à plusieurs pages (on retrouve le même formulaire sur plusieurs pages, par exemple pour les groupes), 
// la récupération de leur valeur en POST et leur traitement est donc placé ici, pour éviter le code en doublon

// MODIFICATION DES INFORMATIONS DANS LA LISTE DES REPOS //
if (!empty($_POST['action']) AND validateData($_POST['action']) == "repoListEditRepo") {
    require_once("${WWW_DIR}/class/Repo.php");
    $repoId = validateData($_POST['repoId']);
    $repoDescription = validateData($_POST['repoDescription']);
    $myRepo = new Repo(compact('repoId', 'repoDescription'));
    $myRepo->edit();
}


// AFFICHAGE DANS LISTE DES REPOS //

if (!empty($_POST['action']) AND validateData($_POST['action']) == "configureDisplay") {
    // On récupère le contenu actuel de display.ini
    $displayConfiguration = parse_ini_file($DISPLAY_CONF, true);

    // Liste des repos : choisir d'afficher ou non la taille des repos
    if (!empty($_POST['printRepoSize'])) {
        $printRepoSize = validateData($_POST['printRepoSize']);
        if ($printRepoSize == "on") {
            $displayConfiguration['display']['printRepoSize'] = 'yes';
        } else {
            $displayConfiguration['display']['printRepoSize'] = 'no';
        }
    }

    // Liste des repos : choisir d'afficher ou non le type des repos
    if (!empty($_POST['printRepoType'])) {
        $printRepoType = validateData($_POST['printRepoType']);
        if ($printRepoType == "on") {
            $displayConfiguration['display']['printRepoType'] = 'yes';
        } else {
            $displayConfiguration['display']['printRepoType'] = 'no';
        }
    }

    // Liste des repos : choisir d'afficher ou non la signature gpg des repos
    if (!empty($_POST['printRepoSignature'])) {
        $printRepoSignature = validateData($_POST['printRepoSignature']);
        if ($printRepoSignature == "on") {
            $displayConfiguration['display']['printRepoSignature'] = 'yes';
        } else {
            $displayConfiguration['display']['printRepoSignature'] = 'no';
        }
    }

    // Liste des repos : choisir de filtrer ou non par groupe
    if (!empty($_POST['filterByGroups'])) {
        $filterByGroups = validateData($_POST['filterByGroups']);
        if ($filterByGroups == "on") {
            $displayConfiguration['display']['filterByGroups'] = 'yes';
        } else {
            $displayConfiguration['display']['filterByGroups'] = 'no';
        }
    }

    // Liste des repos : choisir ou non la vue simplifiée
    if (!empty($_POST['concatenateReposName'])) {
        $concatenateReposName = validateData($_POST['concatenateReposName']);
        if ($concatenateReposName == "on") {
            $displayConfiguration['display']['concatenateReposName'] = 'yes';
        } else {
            $displayConfiguration['display']['concatenateReposName'] = 'no';
        }
    }

    // Liste des repos : choisir d'afficher ou non une ligne séparatrice entre chaque nom de repo/section
    if (!empty($_POST['dividingLine'])) {
        $dividingLine = validateData($_POST['dividingLine']);
        if ($dividingLine == "on") {
            $displayConfiguration['display']['dividingLine'] = 'yes';
        } else {
            $displayConfiguration['display']['dividingLine'] = 'no';
        }
    }

    // Liste des repos : alterner ou non les couleurs dans la liste
    if (!empty($_POST['alternateColors'])) {
        $alternateColors = validateData($_POST['alternateColors']);
        if ($alternateColors == "on") {
            $displayConfiguration['display']['alternateColors'] = 'yes';
        } else {
            $displayConfiguration['display']['alternateColors'] = 'no';
        }
    }

    // Modification des couleurs, voir comment on peut améliorer car c'est très bricolage
    if (!empty($_POST['alternativeColor1'])) {
        $alternativeColor1 = validateData($_POST['alternativeColor1']);
        $displayConfiguration['display']['alternativeColor1'] = "$alternativeColor1";
    }

    if (!empty($_POST['alternativeColor2'])) {
        $alternativeColor2 = validateData($_POST['alternativeColor2']);
        $displayConfiguration['display']['alternativeColor2'] = "$alternativeColor2";
    }

    // On écrit les modifications dans le fichier display.ini
    write_ini_file("$DISPLAY_CONF", $displayConfiguration);

    clearCache($WWW_CACHE);

    // Puis rechargement de la page pour appliquer les modifications d'affichage
    header("Location: $actual_url");
}


// Debian : on a la possibilité d'ajouter de nouvelles url hotes depuis l'accueil
if ($OS_FAMILY == "Debian") {
    // Cas où on souhaite ajouter une nouvelle url hôte :
    if (!empty($_POST['newHostName']) AND !empty($_POST['newHostUrl'])) {
        $newHostName = validateData($_POST['newHostName']);
        $newHostUrl = validateData($_POST['newHostUrl']);
        if (!empty($_POST['newHostGpgKey'])) { // on importe la clé si elle a été transmise 
            $newHostGpgKey = validateData($_POST['newHostGpgKey']);
            $gpgTempFile = '/tmp/repomanager_newgpgkey.tmp'; // création d'un fichier temporaire
            file_put_contents($gpgTempFile, $newHostGpgKey, FILE_APPEND | LOCK_EX); // ajout de la clé gpg à l'intérieur d'un fichier temporaire, afin de l'importer
            $output=null; // un peu de gestion d'erreur
            $retval=null;
            exec("gpg --no-default-keyring --keyring ${GPGHOME}/trustedkeys.gpg --import $gpgTempFile", $output, $retval);
            if ($retval !== 0) {
                // Affichage d'un message et rechargement de la div
                printAlert("Erreur lors de l'import de la clé GPG");
                if ($DEBUG_MODE == "yes") { print_r($output); }
            } 
            unlink($gpgTempFile); // suppression du fichier temporaire
        }
        exec("echo 'Name=\"${newHostName}\",Url=\"${newHostUrl}\"' >> $HOSTS_CONF"); // import du nom et de l'url dans le fichier des hôtes
        // Affichage d'un message et rechargement de la div
        printAlert("L'hôte $newHostName a été ajouté. Vous pouvez créer des sections à partir de cet hôte");
        refreshdiv_class('divManageReposSources');
        showdiv_byclass('divManageReposSources');
    }

    // Cas où on souhaite supprimer un clé gpg du trousseau de repomanager :
    if (isset($_GET['action']) AND (validateData($_GET['action']) == "deleteGpgKey") AND !empty($_GET['gpgKeyID'])) {
        $gpgKeyID = validateData($_GET['gpgKeyID']);
        exec("gpg --no-default-keyring --keyring ${GPGHOME}/trustedkeys.gpg --no-greeting --delete-key --batch --yes $gpgKeyID");
        // Affichage d'un message et rechargement de la div
        printAlert("La clé GPG a été supprimée");
        refreshdiv_class('divManageReposSources');
        showdiv_byclass('divManageReposSources');
    }
}
?>