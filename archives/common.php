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

    // activation du cache
    if (!empty($_POST['cache_repos_list'])) {
        $cache_repos_list = Common::validateData($_POST['cache_repos_list']);
        if ($cache_repos_list == "on") {
            $displayConfiguration['display']['cache_repos_list'] = 'yes';
        } else {
            $displayConfiguration['display']['cache_repos_list'] = 'no';
            Common::clearCache();
            if (is_link(WWW_CACHE)) unlink(WWW_CACHE);
            if (is_dir(WWW_CACHE))  rmdir(WWW_CACHE);
        }
    }

    // On écrit les modifications dans le fichier display.ini
    Common::write_ini_file(DISPLAY_CONF, $displayConfiguration);

    Common::clearCache();

    // Puis rechargement de la page pour appliquer les modifications d'affichage
    header('Location: '.__ACTUAL_URL__);
    exit;
}
?>