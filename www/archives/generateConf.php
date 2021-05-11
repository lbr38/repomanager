<?php
# Génère les fichiers de conf repo .list ou .repo
# Ces fichiers sont utilisés pour les profils et sont téléchargés par les serveurs/postes clients
# L'environnement dans ces fichiers est générique (__ENV__), c'est le serveur/poste client qui le modifie à la volée lors du téléchargement et 
# en fonction de l'environnement qui est indiqué dans son fichier de conf

function generateConf_rpm($repoName, $destination) {
  global $REPOS_PROFILES_CONF_DIR;
  global $REPO_CONF_FILES_PREFIX;
  global $WWW_HOSTNAME;
  global $GPG_SIGN_PACKAGES;

  if (empty($repoName)) {
    return false;
  }

  // On peut préciser à la fonction le répertoire de destination des fichiers. Si on précise une valeur vide ou bien "default", alors les fichiers seront générés dans le répertoire par défaut
  if (empty($destination) OR $destination == "default") {
    $destination = $REPOS_PROFILES_CONF_DIR;
  }

  // Génération du fichier pour Redhat/Centos
  $content = "# Repo ${repoName} sur ${WWW_HOSTNAME}";
  $content = "${content}\n[${REPO_CONF_FILES_PREFIX}${repoName}___ENV__]";
  $content = "${content}\nname=Repo ${repoName} sur ${WWW_HOSTNAME}";
  $content = "${content}\ncomment=Repo ${repoName} sur ${WWW_HOSTNAME}";
  $content = "${content}\nbaseurl=https://${WWW_HOSTNAME}/repo/${repoName}___ENV__";
  $content = "${content}\nenabled=1";
  if ($GPG_SIGN_PACKAGES == "yes") {
    $content = "${content}\ngpgcheck=1";
    $content = "${content}\ngpgkey=https://${WWW_HOSTNAME}/repo/${WWW_HOSTNAME}_repos.pub";
  } else {
    $content = "${content}\ngpgcheck=0";
  }
  // Création du fichier si n'existe pas déjà
  if (!file_exists("${destination}/${REPO_CONF_FILES_PREFIX}${repoName}.repo")) {
    touch("${destination}/${REPO_CONF_FILES_PREFIX}${repoName}.repo");
  }
  // Ecriture du contenu dans le fichier
  file_put_contents("${destination}/${REPO_CONF_FILES_PREFIX}${repoName}.repo", $content);
  
  unset($content);
  return 0;
}

function generateConf_deb($repoName, $repoDist, $repoSection, $destination) {
  global $REPOS_PROFILES_CONF_DIR;
  global $REPO_CONF_FILES_PREFIX;
  global $WWW_HOSTNAME;

  if (empty($repoName) OR empty($repoDist) OR empty($repoSection)) {
    return false;
  }

  // On peut préciser à la fonction le répertoire de destination des fichiers. Si on précise une valeur vide ou bien "default", alors les fichiers seront générés dans le répertoire par défaut
  if (empty($destination) OR $destination == "default") {
    $destination = $REPOS_PROFILES_CONF_DIR;
  }

  // Génération du fichier pour Debian
  $content = "# Repo ${repoName}, distribution ${repoDist}, section ${repoSection} sur ${WWW_HOSTNAME}";
  $content = "${content}\ndeb https://${WWW_HOSTNAME}/repo/${repoName}/${repoDist}/${repoSection}___ENV__ ${repoDist} ${repoSection}";
    
  // Si le nom de la distribution contient un slash, c'est le cas par exemple avec debian-security (buster/updates), alors il faudra remplacer ce slash par [slash] dans le nom du fichier .list 
  $checkIfDistContainsSlash = exec("echo $repoDist | grep '/'");
  if (!empty($checkIfDistContainsSlash)) {
    $repoDistFormatted = str_replace("/", "[slash]","$repoDist");
  } else {
    $repoDistFormatted = $repoDist;
  }
  // Création du fichier si n'existe pas déjà
  if (!file_exists("${destination}/${REPO_CONF_FILES_PREFIX}${repoName}_${repoDistFormatted}_${repoSection}.list")) {
    touch("${destination}/${REPO_CONF_FILES_PREFIX}${repoName}_${repoDistFormatted}_${repoSection}.list");
  }
  // Ecriture du contenu dans le fichier
  file_put_contents("${destination}/${REPO_CONF_FILES_PREFIX}${repoName}_${repoDistFormatted}_${repoSection}.list", $content);
 
  unset($content);
  return 0;
}

// TRAITEMENT //

// Si ce script php est appelé en externe (depuis un terminal avec bash par exemple), alors ces variables ne seront pas set, il faut alors les récupérer
if (empty($WWW_DIR) OR empty($REPOS_PROFILES_CONF_DIR) OR empty($REPO_CONF_FILES_PREFIX) OR empty($WWW_HOSTNAME) OR empty($OS_FAMILY)) {
  $WWW_DIR = dirname(__FILE__, 2);
  // Import des variables nécessaires
  require "${WWW_DIR}/functions/load_common_variables.php";

  // Cas où ce script a été appelé en externe avec des arguments, on récupère ces argume  nts et on exécute directement la fonction de génération de conf
  if (!empty($argv)) {
    if ($OS_FAMILY == "Redhat") {
      if (!empty($argv[1])) { $repoName = $argv[1]; } else { throw new Exception("Erreur : nom du repo non défini"); }
      if (!empty($argv[4])) { $destination = $argv[4]; } else { $destination = 'default'; }
      generateConf_rpm($repoName, $destination);
    }

    // Debian : on attends 2 autres arguments (dist et section)
    if ($OS_FAMILY == "Debian") {
      if (!empty($argv[1])) { $repoName = $argv[1]; } else { throw new Exception("Erreur : nom du repo non défini"); }
      if (!empty($argv[2])) { $repoDist = $argv[2]; } else { throw new Exception("Erreur : nom de la distribution non défini"); }
      if (!empty($argv[3])) { $repoSection = $argv[3]; } else { throw new Exception("Erreur : nom de la section non défini"); }
      if (!empty($argv[4])) { $destination = $argv[4]; } else { $destination = 'default'; }
      generateConf_deb($repoName, $repoDist, $repoSection, $destination);
    }
  }
}
?>