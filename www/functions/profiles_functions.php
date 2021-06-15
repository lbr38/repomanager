<?php
// Création d'un nouveau profil
function newProfile($newProfile) {
  global $PROFILES_MAIN_DIR;

  // On vérifie qu'un profil du même nom n'existe pas déjà
  if (file_exists("${PROFILES_MAIN_DIR}/${newProfile}")) {
    printAlert("Erreur : un profil du même nom (<b>$newProfile</b>) existe déjà");
    return false;
  }

  // Si pas d'erreur alors on peut renommer le répertoire de profil
  // Créer le répertoire du profil :
  if (!is_dir("${PROFILES_MAIN_DIR}/${newProfile}")) { mkdir("${PROFILES_MAIN_DIR}/${newProfile}", 0775, true); }

  // Créer le fichier de config :
  if (!file_exists("${PROFILES_MAIN_DIR}/${newProfile}/config")) { touch("${PROFILES_MAIN_DIR}/${newProfile}/config"); }

  // Créer le fichier de config du profil avec des valeurs vides ou par défaut :
  file_put_contents("${PROFILES_MAIN_DIR}/${newProfile}/config", "EXCLUDE_MAJOR=\"\"\nEXCLUDE=\"\"\nNEED_RESTART=\"\"\nKEEP_CRON=\"no\"\nALLOW_OVERWRITE=\"yes\"\nALLOW_REPOSFILES_OVERWRITE=\"yes\"");
  
  // Affichage d'un message
  printAlert("Le profil <b>${newProfile}</b> a été créé");
}


// Ajout ou suppression de repos/sections d'un profil
function manageProfileRepos($profileName, $profileRepos) {
  global $PROFILES_MAIN_DIR;
  global $REPOS_PROFILES_CONF_DIR;
  global $REPO_CONF_FILES_PREFIX;
  global $OS_FAMILY;

  $repo = new Repo();

  //$profileRepos => validateData fait plus bas

  // D'abord on supprime tous les repos présents, avant de rajouter seulement ceux qui ont été sélectionnés dans la liste
  if (file_exists("${PROFILES_MAIN_DIR}/${profileName}/")) {
    if ($OS_FAMILY == "Redhat") {
      exec("rm ${PROFILES_MAIN_DIR}/${profileName}/*.repo -f");
    }
    if ($OS_FAMILY == "Debian") {
      exec("rm ${PROFILES_MAIN_DIR}/${profileName}/*.list -f");
    }
  }

  // Si l'array $profileRepos est vide alors on s'arrête là, le profil restera sans repo configuré. Sinon on continue.
  if (empty($profileRepos)) {
    return 0;
  }

  // On traite chaque repo sélectionné
  foreach ($profileRepos as $selectedOption) {
    $addProfileRepo = validateData($selectedOption);

    if ($OS_FAMILY == "Debian") {
      $addProfileRepoExplode = explode('|', $addProfileRepo);
      $addProfileRepo = $addProfileRepoExplode[0];
      $addProfileRepoDist = $addProfileRepoExplode[1];
      $addProfileRepoSection = $addProfileRepoExplode[2];
    }

    if ($OS_FAMILY == "Redhat") {
      // On vérifie que le repo existe :
      if ($repo->exists($addProfileRepo) === false) {
        printAlert("Le repo $addProfileRepo n'existe pas");
        continue;
      }
      exec("cd ${PROFILES_MAIN_DIR}/${profileName}/ && ln -s ${REPOS_PROFILES_CONF_DIR}/${REPO_CONF_FILES_PREFIX}${addProfileRepo}.repo");
    }

    if ($OS_FAMILY == "Debian" AND !empty($addProfileRepoDist) AND !empty($addProfileRepoSection)) {
      // On vérifie que la section repo existe :
      if ($repo->section_exists($addProfileRepo, $addProfileRepoDist, $addProfileRepoSection) === false) {
        printAlert("La section $addProfileRepoSection du repo $addProfileRepo n'existe pas");
        continue;
      }

      // Si le nom de la distribution contient un slash, c'est le cas par exemple avec debian-security (buster/updates), alors il faudra remplacer ce slash par [slash] dans le nom du fichier .list 
      $checkIfDistContainsSlash = exec("echo $addProfileRepoDist | grep '/'");
      if (!empty($checkIfDistContainsSlash)) {
        $addProfileRepoDist = str_replace("/", "[slash]","$addProfileRepoDist");
      }
      exec("cd ${PROFILES_MAIN_DIR}/${profileName}/ && ln -s ${REPOS_PROFILES_CONF_DIR}/${REPO_CONF_FILES_PREFIX}${addProfileRepo}_${addProfileRepoDist}_${addProfileRepoSection}.list");
    }
  }
}

// Suppression d'un profil
function deleteProfile($profileName) {
  global $PROFILES_MAIN_DIR;

  // Suppression du répertoire du profil
  exec("rm -fr ${PROFILES_MAIN_DIR}/${profileName}/", $output, $return);
  if ($return == 0) {
    // Affichage d'un message
    printAlert("Le profil <b>$profileName</b> a été supprimé");
  } else {
  // Si la suppression s'est mal passée
    printAlert("<span class=\"yellowtext\">Erreur lors de la suppression du profil <b>$profileName</b></span>");
  }
}

// Renommage d'un profil
function renameProfile($actualProfileName, $newProfileName) {
  global $PROFILES_MAIN_DIR;

  // On vérifie qu'un profil du même nom n'existe pas déjà. Si c'est le cas on affiche un message d'erreur
  if (is_dir("${PROFILES_MAIN_DIR}/${newProfileName}")) {
    printAlert("Erreur : un profil du même nom (<b>$newProfileName</b>) existe déjà");
    return false;
  }
  // Si pas d'erreur alors on peut renommer le répertoire de profil
  exec("mv ${PROFILES_MAIN_DIR}/${actualProfileName} ${PROFILES_MAIN_DIR}/${newProfileName}");
  // Affichage d'un message
  printAlert("Le profil <b>$actualProfileName</b> a été renommé en <b>$newProfileName</b>");
}
?>