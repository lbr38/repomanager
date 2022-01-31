<?php
/////// GESTION DES FORMULAIRES ET REQUETES GET COMMUNS ////////
// Des formulaires peuvent être communs à plusieurs pages (on retrouve le même formulaire sur plusieurs pages, par exemple pour les groupes), 
// la récupération de leur valeur en POST et leur traitement est donc placé ici, pour éviter le code en doublon

// AFFICHAGE DANS LISTE DES REPOS //

if (!empty($_POST['action']) AND Common::validateData($_POST['action']) == "configureDisplay") {
    // On récupère le contenu actuel de display.ini
    $displayConfiguration = parse_ini_file(DISPLAY_CONF, true);

    // Liste des repos : choisir d'afficher ou non la taille des repos
    if (!empty($_POST['printRepoSize'])) {
        $printRepoSize = Common::validateData($_POST['printRepoSize']);
        if ($printRepoSize == "on") {
            $displayConfiguration['display']['printRepoSize'] = 'yes';
        } else {
            $displayConfiguration['display']['printRepoSize'] = 'no';
        }
    }

    // Liste des repos : choisir d'afficher ou non le type des repos
    if (!empty($_POST['printRepoType'])) {
        $printRepoType = Common::validateData($_POST['printRepoType']);
        if ($printRepoType == "on") {
            $displayConfiguration['display']['printRepoType'] = 'yes';
        } else {
            $displayConfiguration['display']['printRepoType'] = 'no';
        }
    }

    // Liste des repos : choisir d'afficher ou non la signature gpg des repos
    if (!empty($_POST['printRepoSignature'])) {
        $printRepoSignature = Common::validateData($_POST['printRepoSignature']);
        if ($printRepoSignature == "on") {
            $displayConfiguration['display']['printRepoSignature'] = 'yes';
        } else {
            $displayConfiguration['display']['printRepoSignature'] = 'no';
        }
    }

    // Liste des repos : choisir de filtrer ou non par groupe
    if (!empty($_POST['filterByGroups'])) {
        $filterByGroups = Common::validateData($_POST['filterByGroups']);
        if ($filterByGroups == "on") {
            $displayConfiguration['display']['filterByGroups'] = 'yes';
        } else {
            $displayConfiguration['display']['filterByGroups'] = 'no';
        }
    }

    // Liste des repos : choisir ou non la vue simplifiée
    if (!empty($_POST['concatenateReposName'])) {
        $concatenateReposName = Common::validateData($_POST['concatenateReposName']);
        if ($concatenateReposName == "on") {
            $displayConfiguration['display']['concatenateReposName'] = 'yes';
        } else {
            $displayConfiguration['display']['concatenateReposName'] = 'no';
        }
    }

    // Liste des repos : choisir d'afficher ou non une ligne séparatrice entre chaque nom de repo/section
    if (!empty($_POST['dividingLine'])) {
        $dividingLine = Common::validateData($_POST['dividingLine']);
        if ($dividingLine == "on") {
            $displayConfiguration['display']['dividingLine'] = 'yes';
        } else {
            $displayConfiguration['display']['dividingLine'] = 'no';
        }
    }

    // Liste des repos : alterner ou non les couleurs dans la liste
    if (!empty($_POST['alternateColors'])) {
        $alternateColors = Common::validateData($_POST['alternateColors']);
        if ($alternateColors == "on") {
            $displayConfiguration['display']['alternateColors'] = 'yes';
        } else {
            $displayConfiguration['display']['alternateColors'] = 'no';
        }
    }

    // Modification des couleurs
    if (!empty($_POST['alternativeColor1'])) {
        $alternativeColor1 = Common::validateData($_POST['alternativeColor1']);
        $displayConfiguration['display']['alternativeColor1'] = "$alternativeColor1";
    }

    if (!empty($_POST['alternativeColor2'])) {
        $alternativeColor2 = Common::validateData($_POST['alternativeColor2']);
        $displayConfiguration['display']['alternativeColor2'] = "$alternativeColor2";
    }

    // activation du cache
    if (!empty($_POST['cache_repos_list'])) {
        $cache_repos_list = Common::validateData($_POST['cache_repos_list']);
        if ($cache_repos_list == "on") {
            $displayConfiguration['display']['cache_repos_list'] = 'yes';
        } else {
            $displayConfiguration['display']['cache_repos_list'] = 'no';
            clearCache();
            if (is_link(WWW_CACHE)) unlink(WWW_CACHE);
            if (is_dir(WWW_CACHE))  rmdir(WWW_CACHE);
        }
    }

    // On écrit les modifications dans le fichier display.ini
    write_ini_file(DISPLAY_CONF, $displayConfiguration);

    clearCache();

    // Puis rechargement de la page pour appliquer les modifications d'affichage
    header('Location: '.__ACTUAL_URL__);
    exit;
}
?>