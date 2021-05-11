<?php
// Envoi d'un mail de rappel de planification
function sendMail($message, $EMAIL_DEST) {
  global $WWW_DIR;
  global $WWW_HOSTNAME;

  require("${WWW_DIR}/templates/plan_reminder_mail.inc.php");
  
  // Pour envoyer un mail HTML il faut inclure ces headers
  $headers[] = 'MIME-Version: 1.0';
  $headers[] = 'Content-type: text/html; charset=utf8';
  mail($EMAIL_DEST, "[RAPPEL] Planification(s) à venir sur $WWW_HOSTNAME", $template, implode("\r\n", $headers));
}


// Génération des messages de rappels de planifications
function generateReminders($planId) {
  global $PLANS_DIR;
  global $REPOS_LIST;
  global $OS_FAMILY;
  global $DEFAULT_ENV;

  $planFile = "plan-${planId}.conf";

  // Récupération des informations de la planification
  $planDate = str_replace(['Date=', '"'], '', exec("egrep '^Date=' $PLANS_DIR/$planFile"));
  $planTime = str_replace(['Time=', '"'], '', exec("egrep '^Time=' $PLANS_DIR/$planFile"));
  $planAction = str_replace(['Action=', '"'], '', exec("egrep '^Action=' $PLANS_DIR/$planFile"));
  $planGroup = str_replace(['Group=', '"'], '', exec("egrep '^Group=' $PLANS_DIR/$planFile"));
  $planRepo = str_replace(['Repo=', '"'], '', exec("egrep '^Repo=' $PLANS_DIR/$planFile"));
  if (!empty($planGroup) AND empty($planRepo)) {
    $planRepoOrGroup = $planGroup;
  }
  if (empty($planGroup) AND !empty($planRepo)) {
    $planRepoOrGroup = $planRepo;
    if ($OS_FAMILY == "Debian") {
      $planDist = str_replace(['Dist=', '"'], '', exec("egrep '^Dist=' $PLANS_DIR/$planFile"));
      $planSection = str_replace(['Section=', '"'], '', exec("egrep '^Section=' $PLANS_DIR/$planFile"));
    }
  }
  if ($planAction == "update") { // si planAction = 'update' alors il faut récupérer la valeur de GpgCheck
    $planGpgCheck = str_replace(['GpgCheck=', '"'], '', exec("egrep '^GpgCheck=' $PLANS_DIR/$planFile"));
    $planGpgResign = str_replace(['GpgResign=', '"'], '', exec("egrep '^GpgResign=' $PLANS_DIR/$planFile"));
  }
  $planReminder = str_replace(['Reminder=', '"'], '', exec("egrep '^Reminder=' $PLANS_DIR/$planFile"));


  // VERIFICATIONS //

  // Vérification de l'action
  try { checkAction($planId, $planAction); } catch(Exception $e) { return $e->getMessage(); }
  
  // Vérification si il s'agit d'un repo ou d'un groupe
  try { checkIfRepoOrGroup($planId, $planRepo, $planGroup); } catch(Exception $e) { return $e->getMessage(); }

  // Si on a renseigné un seul repo à traiter alors il faut vérifier qu'il existe bien (il a pu être supprimé depuis que la planification a été créée)
  // Puis il faut récupérer son vrai nom (Redhat) ou son hôte source (Debian)
  if (!empty($planRepo)) {
    // on vérifie qu'il existe
    if ($OS_FAMILY == "Redhat") {
      try { checkIfRepoExists($planId, $planRepo); } catch(Exception $e) { return $e->getMessage(); }
    }
    if ($OS_FAMILY == "Debian") { 
      $planRepoNameFull = "${planRepo}|${planDist}|${planSection}"; 
      try { checkIfRepoExists($planId, $planRepoNameFull); } catch(Exception $e) { return $e->getMessage(); }
    }
  }

  // Si on a renseigné un groupe (commence par @) plutôt qu'un seul repo à traiter, alors on vérifie que le groupe existe dans le fichier de groupe (il a pu être supprimé depuis que la planification a été créée)
  // Puis on récupère toute la liste du groupe
  if (!empty($planGroup)) {
    // on vérifie qu'il existe
    try { checkIfGroupExists($planId, $planGroup); } catch(Exception $e) { return $e->getMessage(); }
    // on récupère la liste des repos dans ce groupe
    try { $planGroupList = get_group_repo_list($planId, $planGroup); } catch(Exception $e) { return $e->getMessage(); }
  }


  // TRAITEMENT //
  
  // Cas où la planif à rappeler ne concerne qu'un seul repo/section
  if (!empty($planRepo)) {
    // Cas où l'action prévue est une mise à jour
    if ($planAction == "update") {
      if ($OS_FAMILY == "Redhat") {
        return "Mise à jour du repo $planRepo <span class=\"td-whitebackground\">${DEFAULT_ENV}</span>";
      }
      if ($OS_FAMILY == "Debian") {
        return "Mise à jour de la section $planSection du repo $planRepo (distribution $planDist) <span class=\"td-whitebackground\">${DEFAULT_ENV}</span>";
      }
    }

    // Cas où l'action prévue est une création d'env
    if (strpos($planAction, '->') !== false) {
      $planRepoEnv = exec("echo '$planAction' | awk -F '->' '{print $1}'");
      $planRepoNewEnv = exec("echo '$planAction' | awk -F '->' '{print $2}'");

      if (empty($planRepoEnv) AND empty($planRepoNewEnv)) {
        return "Erreur : l'environnement source ou de destination est inconnu";
      }

      if ($OS_FAMILY == "Redhat") {
        return "Changement d'environnement (${planRepoEnv} -> ${planRepoNewEnv}) du repo ${planRepo}";
      }
      if ($OS_FAMILY == "Debian") {
        return "Changement d'environnement (${planRepoEnv} -> ${planRepoNewEnv}) de la section ${planSection} du repo ${planRepo} (distribution ${planDist})";
      }
    }
  }

  // Cas où la planif à rappeler concerne un groupe de repo
  if (!empty($planGroup) AND !empty($planGroupList)) {
    $rows = explode("\n", $planGroupList);
    foreach($rows as $line) {
      if ($OS_FAMILY == "Redhat") {
        $groupRepoName = exec("echo $line | awk -F ',' '{print $1}' | cut -d'=' -f2 | sed 's/\"//g'");
        $groupRepoRealname = exec("grep '^Name=\"${groupRepoName}\"' $REPOS_LIST | awk -F ',' '{print $2}' | cut -d'=' -f2 | sed 's/\"//g'");
      }
      if ($OS_FAMILY == "Debian") {
        $groupRepoName = exec("echo $line | awk -F ',' '{print $1}' | cut -d'=' -f2 | sed 's/\"//g'");
        $groupRepoDist = exec("echo $line | awk -F ',' '{print $2}' | cut -d'=' -f2 | sed 's/\"//g'");
        $groupRepoSection = exec("echo $line | awk -F ',' '{print $3}' | cut -d'=' -f2 | sed 's/\"//g'");
      }

      // Cas où l'action prévue est une mise à jour
      if ($planAction == "update") {
        if ($OS_FAMILY == "Redhat") {
          return "Mise à jour des repos du groupe ${planGroup} (environnement ${DEFAULT_ENV})";
        }
        if ($OS_FAMILY == "Debian") {
          return "Mise à jour des sections de repos du groupe ${planGroup}";
        }
      }

      // Cas où l'action prévue est un changement d'env
      if (strpos($planAction, '->') !== false) {
        $planRepoEnv = exec("echo '$planAction' | awk -F '->' '{print $1}'");
        $planRepoNewEnv = exec("echo '$planAction' | awk -F '->' '{print $2}'");
        if (empty($planRepoEnv) AND empty($planRepoNewEnv)) {
          return "Erreur : l'environnement source ou de destination est inconnu";
        }
        if ($OS_FAMILY == "Redhat") {
          return "Changement d'environnement (${planRepoEnv} -> ${planRepoNewEnv}) du groupe ${planGroup}";
        }
        if ($OS_FAMILY == "Debian") {
          return "Changement d'environnement (${planRepoEnv} -> ${planRepoNewEnv}) du groupe ${planGroup}";
        }
      }
    }
  }
}
?>