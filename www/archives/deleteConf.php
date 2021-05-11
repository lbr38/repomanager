<?php
# Supprime les fichiers de conf repo .list ou .repo lors de la suppression d'un repo/section
# Ces fichiers sont utilisés pour les profils et sont téléchargés par les serveurs/postes clients

$error=0;

function checkError() {
  global $error;
  if ($error > 0) {
    echo "<tr><td colspan=\"100%\"><br><span class=\"redtext\">Erreur : </span>impossible de supprimer le fichier de conf repo sur le serveur, une variable nécéssaire à la suppression est vide</td></tr>";   
    return 1;
  } else {
    return 0;
  }
}

function deleteConf_rpm($repoName) {
  checkError();
  global $REPOS_PROFILES_CONF_DIR;
  global $REPO_CONF_FILES_PREFIX;
  global $PROFILES_MAIN_DIR;
  global $PROFILE_SERVER_CONF;

  // Suppression du fichier si existe
  if (file_exists("${REPOS_PROFILES_CONF_DIR}/${REPO_CONF_FILES_PREFIX}${repoName}.repo")) {
      unlink("${REPOS_PROFILES_CONF_DIR}/${REPO_CONF_FILES_PREFIX}${repoName}.repo");
  }

  // Suppression des liens symboliques pointant vers ce repo dans les répertoires de profils 
  $profilesNames = scandir($PROFILES_MAIN_DIR); // Récupération de tous les noms de profils
  foreach($profilesNames as $profileName) {
    if (($profileName != "..") AND ($profileName != ".") AND ($profileName != "_configurations") AND ($profileName != "_reposerver") AND ($profileName != "${PROFILE_SERVER_CONF}")) {
      if (is_link("${PROFILES_MAIN_DIR}/${profileName}/${REPO_CONF_FILES_PREFIX}${repoName}.repo")) {
        unlink("${PROFILES_MAIN_DIR}/${profileName}/${REPO_CONF_FILES_PREFIX}${repoName}.repo");
      }
    }
  }

  return 0;
}

function deleteConf_deb($repoName, $repoDist, $repoSection) {
  checkError();
  global $REPOS_PROFILES_CONF_DIR;
  global $REPO_CONF_FILES_PREFIX;
  global $PROFILES_MAIN_DIR;
  global $PROFILE_SERVER_CONF;

  // Si le nom de la distribution contient un slash, c'est le cas par exemple avec debian-security (buster/updates), alors il faudra remplacer ce slash par [slash] dans le nom du fichier .list 
  $checkIfDistContainsSlash = exec("echo $repoDist | grep '/'");
  if (!empty($checkIfDistContainsSlash)) {
    $repoDistFormatted = str_replace("/", "[slash]","$repoDist");
  } else {
      $repoDistFormatted = $repoDist;
  }

  // Suppression du fichier si existe
  if (file_exists("${REPOS_PROFILES_CONF_DIR}/${REPO_CONF_FILES_PREFIX}${repoName}_${repoDistFormatted}_${repoSection}.list")) {
      unlink("${REPOS_PROFILES_CONF_DIR}/${REPO_CONF_FILES_PREFIX}${repoName}_${repoDistFormatted}_${repoSection}.list");
  }
  
  // Suppression des liens symboliques pointant vers ce repo dans les répertoires de profils 
  $profilesNames = scandir($PROFILES_MAIN_DIR); // Récupération de tous les noms de profils
  foreach($profilesNames as $profileName) {
    if (($profileName != "..") AND ($profileName != ".") AND ($profileName != "_configurations") AND ($profileName != "_reposerver") AND ($profileName != "${PROFILE_SERVER_CONF}")) {
      if (is_link("${PROFILES_MAIN_DIR}/${profileName}/${REPO_CONF_FILES_PREFIX}${repoName}_${repoDistFormatted}_${repoSection}.list")) {
        unlink("${PROFILES_MAIN_DIR}/${profileName}/${REPO_CONF_FILES_PREFIX}${repoName}_${repoDistFormatted}_${repoSection}.list");
      }
    }
  }

  return 0;
}

// Si ce script php est appelé en externe (depuis un terminal avec bash par exemple), alors ces 4 variables ne seront pas set, il faut alors les récupérer
if (empty($WWW_DIR) OR empty($REPOS_PROFILES_CONF_DIR) OR empty($REPO_CONF_FILES_PREFIX) OR empty($WWW_HOSTNAME) OR empty($OS_FAMILY)) {
  $WWW_DIR = dirname(__FILE__, 2);
  // Import des variables nécessaires
  require "${WWW_DIR}/functions/load_common_variables.php";

  // Cas où ce script a été appelé en externe avec des arguments, on récupère ces arguments et on exécute directement la fonction de génération de conf
  if (!empty($argv)) {
    $repoName = $argv[1];
    if (empty($repoName)) { ++$error; }
    if ($OS_FAMILY == "Redhat") {
      deleteConf_rpm($repoName);
    }
    // Debian : on attends 2 autres arguments
    if ($OS_FAMILY == "Debian") {
      $repoDist = $argv[2];
      $repoSection = $argv[3];
      if (empty($repoDist)) { ++$error; }
      if (empty($repoSection)) { ++$error; }
      deleteConf_deb($repoName, $repoDist, $repoSection);
    }
  }
}
?>