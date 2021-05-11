<?php
// Vérification si on traite un repo seul ou un groupe
function checkIfRepoOrGroup($planID, $planRepoName, $planGroup) {
  global $OS_FAMILY;
  global $plan_msg_error;

  if (empty($planRepoName) AND empty($planGroup)) {
    if (empty($planAction)) {
      throw new Exception("Erreur (CP06) : Aucun repo ou groupe spécifié");
    }
  }

  // On va traiter soit un repo soit un groupe de repo, ça ne peut pas être les deux, donc on vérifie que planRepo et planGroup ne sont pas tous les deux renseignés en même temps :
	if (!empty($planRepoName) AND !empty($planGroup)) {
		if ("$OS_FAMILY" == "Redhat") { throw new Exception("Erreur (CP07) : Il n'est pas possible de traiter à la fois un repo et un groupe de repos"); }
		if ("$OS_FAMILY" == "Debian") { throw new Exception("Erreur (CP07) : Il n'est pas possible de traiter à la fois une section et un groupe de sections"); }
	}
  return true;
}

// Vérification que le repo existe
function checkIfRepoExists($planID, $planRepoName) {
  global $OS_FAMILY;
  global $REPOS_LIST;

  if ("$OS_FAMILY" == "Redhat") {
    // Vérification que le repo existe
    $checkIfRepoExists = exec("grep '^Name=\"${planRepoName}\"' $REPOS_LIST");
    if (empty($checkIfRepoExists)) {
      throw new Exception("Erreur (CP08) : Le repo ${planRepoName} n'existe pas");
    }
  }

  if ("$OS_FAMILY" == "Debian") {
    $planRepoNameExplode = explode('|', $planRepoName);
    $planRepoName = $planRepoNameExplode[0];
    $planRepoDist = $planRepoNameExplode[1];
    $planRepoSection = $planRepoNameExplode[2];

    // On vérifie qu'on a bien renseigné la distribution et la section
    if (empty($planRepoDist)) {
      throw new Exception("Erreur (CP10) : Aucune distribution spécifiée");
    }
    if (empty($planRepoSection)) {
      throw new Exception("Erreur (CP11) : Aucune section spécifiée");
    }

    // Vérification que la section existe
    $checkIfSectionExists = exec("grep '^Name=\"${planRepoName}\",Host=\".*\",Dist=\"${planRepoDist}\",Section=\"${planRepoSection}\"' $REPOS_LIST");
    if (empty($checkIfSectionExists)) {
      throw new Exception("Erreur (CP12) : La section ${planRepoSection} du repo ${planRepoName} (distribution ${planRepoDist}) n'existe pas");
    }
  }
  return true;
}

function get_repo_source($planID, $planRepoName) {
  global $OS_FAMILY;
  global $REPOS_LIST;

  if ("$OS_FAMILY" == "Redhat") {
    // Récupération du repo source
    $planRepoRealname = exec("grep '^Name=\"${planRepoName}\"' $REPOS_LIST | awk -F ',' '{print $2}' | cut -d'=' -f2 | sed 's/\"//g'");
    if (empty($planRepoRealname)) {
      throw new Exception("Erreur (CP09) : Impossible de récupérer le nom du repo source");
    }
    return $planRepoRealname;
  }
  if ("$OS_FAMILY" == "Debian") {
    // Récupération de l'hote source
    $planRepoHostname = exec("grep '^Name=\"${planRepoName}\"' $REPOS_LIST | awk -F ',' '{print $2}' | cut -d'=' -f2 | sed 's/\"//g'");
    if (empty($planRepoHostname)) {
      throw new Exception("Erreur (CP13) : Impossible de récupérer le nom de l'hôte source");
    }
    return $planRepoHostname;
  }
}


// Vérification que le groupe existe
function checkIfGroupExists($planID, $planGroup) {
  global $GROUPS_CONF;

  $checkIfGroupExists = exec("grep '\[${planGroup}\]' $GROUPS_CONF");
  if (empty($checkIfGroupExists)) {
    throw new Exception("Erreur (CP14) : Le groupe ${planGroup} n'existe pas");
  }
  return true;
}

// Récupération de la liste des repo dans le groupe
function get_group_repo_list($planID, $planGroup) {
  global $GROUPS_CONF;
  global $REPOS_LIST;
  global $OS_FAMILY;

  // on récupère tous les repos du groupe
  $planGroupList = shell_exec("cat $GROUPS_CONF | sed -n '/${planGroup}/,/^\[@/p' | egrep '^Name=\".*\"'");
  if (empty($planGroupList)) {
    if ("$OS_FAMILY" == "Redhat") { throw new Exception("Erreur (CP13) : Il n'y a aucun repo renseigné dans le groupe ${planGroup}"); }
    if ("$OS_FAMILY" == "Debian") { throw new Exception("Erreur (CP13) : Il n'y a aucune section renseignée dans le groupe ${planGroup}"); }
  }

  // Pour chaque repo/section renseigné(e), on vérifie qu'il/elle existe
  $plan_msg_error = '';
  $rows = explode("\n", $planGroupList);
  foreach($rows as $LINE) {
    if (!empty($LINE)) {
      // Pour chaque ligne on récupère les infos du repo/section
      $groupRepoName = exec("echo $LINE | awk -F ',' '{print $1}' | cut -d'=' -f2 | sed 's/\"//g'");
      if ("$OS_FAMILY" == "Redhat") {
        $checkIfRepoExists = exec("grep '^Name=\"${groupRepoName}\"' $REPOS_LIST");
        if (empty($checkIfRepoExists)) {
          $plan_msg_error="${plan_msg_error}\nErreur (CP15) : Le repo ${groupRepoName} dans le groupe ${planGroup} n'existe pas/plus.";
        }
      }
      if ("$OS_FAMILY" == "Debian") {
        $groupRepoDist = exec("echo $LINE | awk -F ',' '{print $2}' | cut -d'=' -f2 | sed 's/\"//g'");
        $groupRepoSection = exec("echo $LINE | awk -F ',' '{print $3}' | cut -d'=' -f2 | sed 's/\"//g'");
        $checkIfSectionExists = exec("grep '^Name=\"${groupRepoName}\",Host=\".*\",Dist=\"${groupRepoDist}\",Section=\"${groupRepoSection}\"' $REPOS_LIST");
        if (empty($checkIfSectionExists)) {
          $plan_msg_error="${plan_msg_error}\nErreur (CP16) : La section ${groupRepoSection} du repo ${groupRepoName} (distribution ${groupRepoDist}) dans le groupe ${planGroup} n'existe pas/plus.";
        }
      }
    }
  }
  // Si des repos/sections n'existent plus alors on quitte
  if (!empty($plan_msg_error)) {
    throw new Exception("$plan_msg_error");
  }
  // Sinon on retourne la liste des repos/sections précédemment récupérée
  return $planGroupList;
}
?>