<?php
/* Codes d'erreurs
PE = Planification Execution
PE01
PE02

PR = Planification Reminder
PR01
PR02 */

// Ce script php sera appelé en externe (depuis une tâche at), alors ces 4 variables ne seront pas set, il faut alors les récupérer

function plan_exec($planID) {

  $WWW_DIR = dirname(__FILE__, 2);

  // Import des variables et fonctions nécessaires, ne pas changer l'ordre des requires
  require("${WWW_DIR}/functions/load_common_variables.php");
  require("${WWW_DIR}/functions/cleanArchives.php");
  require("${WWW_DIR}/operations/changeEnv_rpm.php");
  require("${WWW_DIR}/operations/changeEnv_deb.php");
  

  $plan_msg_error = '';
  // Récupération de la date et l'heure actuelle à laquelle la planification est exécutée
  $planDate = date("Y-m-d");
  $planTime = date("H\hi");


  // VERIFICATIONS //

  // Création d'un fichier de log et récupération du PID et du nom du fichier de log
  list($PID, $LOGNAME) = createPlanLog($planID);

  // Si les planifications ne sont pas activées, on quitte
  if ("$AUTOMATISATION_ENABLED" != "yes") {
    $plan_msg_error = "${plan_msg_error}\nErreur (EP01) : Les planifications ne sont pas activées. Vous pouvez modifier ce paramètre depuis l'onglet Paramètres.";
    plan_exit($planID, 1, $LOGNAME, $plan_msg_error);
  }

  // On vérifie qu'une planification possèdant l'ID ${planID} existe vraiment :
  if (!file_exists("${PLANS_DIR}/plan-${planID}.conf")) {
    $plan_msg_error = "${plan_msg_error}\nErreur (EP01) : Il n'existe aucune planification portant l'ID '${planID}'";
    plan_exit($planID, 1, $LOGNAME, $plan_msg_error);
  }

  // Récupération des détails de la planification actuelle dans le fichier de conf, afin de savoir quels repos sont impliqués et quelle action effectuer
  $planAction =      exec("grep '^Action=' ${PLANS_DIR}/plan-${planID}.conf | cut -d'=' -f2 | sed 's/\"//g'");
  $planRepoName =    exec("grep '^Repo=' ${PLANS_DIR}/plan-${planID}.conf | cut -d'=' -f2 | sed 's/\"//g'");
  $planRepoDist =    exec("grep '^Dist=' ${PLANS_DIR}/plan-${planID}.conf | cut -d'=' -f2 | sed 's/\"//g'");
  $planRepoSection = exec("grep '^Section=' ${PLANS_DIR}/plan-${planID}.conf | cut -d'=' -f2 | sed 's/\"//g'");
  $planGroup =       exec("grep '^Group=' ${PLANS_DIR}/plan-${planID}.conf | cut -d'=' -f2 | sed 's/\"//g'");
  $planGpgCheck =    exec("grep '^GpgCheck=' ${PLANS_DIR}/plan-${planID}.conf | cut -d'=' -f2 | sed 's/\"//g'");
  $planGpgResign =   exec("grep '^GpgResign=' ${PLANS_DIR}/plan-${planID}.conf | cut -d'=' -f2 | sed 's/\"//g'");
  // Initialisation de variables supplémentaires
  $planGroupList = '';
  $planRepoRealname = '';
  $planRepoHostname = '';

  // Vérification de l'action
  try { checkAction($planID, $planAction); } catch(Exception $e) { plan_exit($planID, 1, $LOGNAME, $e->getMessage()); }

  // Si l'action est 'update' alors on doit avoir renseigné planGpgCheck et planGpgResign
  if ("$planAction" == "update") {
    try { checkAction_update_allowed($planID, $ALLOW_AUTOUPDATE_REPOS); } catch(Exception $e) { plan_exit($planID, 1, $LOGNAME, $e->getMessage()); }
    try { checkAction_update_gpgCheck($planID, $planGpgCheck); } catch(Exception $e) { plan_exit($planID, 1, $LOGNAME, $e->getMessage()); }
    try { checkAction_update_gpgResign($planID, $planGpgResign); } catch(Exception $e) { plan_exit($planID, 1, $LOGNAME, $e->getMessage()); }
  }
  // Si l'action est '->'
  if (strpos($planAction, '->') !== false) {
    try { checkAction_env_allowed($planID, $ALLOW_AUTOUPDATE_REPOS_ENV); } catch(Exception $e) { plan_exit($planID, 1, $LOGNAME, $e->getMessage()); }
  }

  // Vérification si il s'agit d'un repo ou d'un groupe
  try { checkIfRepoOrGroup($planID, $planRepoName, $planGroup); } catch(Exception $e) { plan_exit($planID, 1, $LOGNAME, $e->getMessage()); }

  // Si on a renseigné un seul repo à traiter alors il faut vérifier qu'il existe bien (il a pu être supprimé depuis que la planification a été créée)
  // Puis il faut récupérer son vrai nom (Redhat) ou son hôte source (Debian)
  if (!empty($planRepoName)) {
    // on vérifie qu'il existe
    if ($OS_FAMILY == "Redhat") {
      try { checkIfRepoExists($planID, $planRepoName); } catch(Exception $e) { plan_exit($planID, 1, $LOGNAME, $e->getMessage()); }
    }
    if ($OS_FAMILY == "Debian") { 
      $planRepoNameFull = "${planRepoName}|${planRepoDist}|${planRepoSection}"; 
      try { checkIfRepoExists($planID, $planRepoNameFull); } catch(Exception $e) { plan_exit($planID, 1, $LOGNAME, $e->getMessage()); }
    }

    // on récupère le repo/hote source
    if ($OS_FAMILY == "Redhat") { try { $planRepoRealname = get_repo_source($planID,$planRepoName); } catch(Exception $e) { plan_exit($planID, 1, $LOGNAME, $e->getMessage()); } }
    if ($OS_FAMILY == "Debian") { try { $planRepoHostname = get_repo_source($planID,$planRepoName); } catch(Exception $e) { plan_exit($planID, 1, $LOGNAME, $e->getMessage()); } }
  }

  // Si on a renseigné un groupe (commence par @) plutôt qu'un seul repo à traiter, alors on vérifie que le groupe existe dans le fichier de groupe (il a pu être supprimé depuis que la planification a été créée)
  // Puis on récupère toute la liste du groupe
  if (!empty($planGroup)) {
    // on vérifie qu'il existe
    try { checkIfGroupExists($planID, $planGroup); } catch(Exception $e) { plan_exit($planID, 1, $LOGNAME, $e->getMessage()); }
    // on récupère la liste des repos dans ce groupe
    try { $planGroupList = get_group_repo_list($planID, $planGroup); } catch(Exception $e) { plan_exit($planID, 1, $LOGNAME, $e->getMessage()); }
  }

  // TRAITEMENT //

  // Cas où on traite 1 repo seulement :
  if (!empty($planRepoName)) {
    // Si $planAction = update alors on met à jour le repo
    if ("$planAction" == "update") {

      if ("$OS_FAMILY" == "Redhat") {
        $result = exec("php ${WWW_DIR}/operations/updateRepo.php $PID $LOGNAME $repoName $repoSource $repoGpgCheck $repoGpgResign");
        if ($result == 1) {
          plan_exit($planID, 1, $LOGNAME, "Une erreur est survenue pendant le traitement, voir les logs");
        }
      }
      if ("$OS_FAMILY" == "Debian") {
        $result = exec("php ${WWW_DIR}/operations/updateRepo.php $PID $LOGNAME $planRepoName $planRepoDist $planRepoSection $planRepoHostname $planGpgCheck $planGpgResign");
        if ($result == 1) {
          plan_exit($planID, 1, $LOGNAME, "Une erreur est survenue pendant le traitement, voir les logs");
        }
      }
    }

    // Si $planAction contient '->' alors il s'agit d'un changement d'env
    if (strpos($planAction, '->') !== false) {
      $planRepoEnv = exec("echo '$planAction' | awk -F '->' '{print $1}'");
      $planRepoNextEnv = exec("echo '$planAction' | awk -F '->' '{print $2}'");
      if (empty($planRepoEnv) OR empty($planRepoNextEnv)) {
        plan_exit($planID, 1, $LOGNAME, "Erreur (EP04) : Environnement(s) non défini(s)");
      }

      if ("$OS_FAMILY" == "Redhat") {
        if (changeEnv_rpm($planRepoName, $planRepoEnv, $planRepoNextEnv, 'nodescription') === false) {
          plan_exit($planID, 1, $LOGNAME, "Une erreur est survenue pendant le traitement, voir les logs");
        }
      }
      if ("$OS_FAMILY" == "Debian") {
        if (changeEnv_deb($planRepoName, $planRepoDist, $planRepoSection, $planRepoEnv, $planRepoNextEnv, 'nodescription') === false) {
          plan_exit($planID, 1, $LOGNAME, "Une erreur est survenue pendant le traitement, voir les logs");
        }
      }
    }
  }

  // Cas où on traite un groupe de repos/sections :
  if (!empty($planGroup) AND !empty($planGroupList)) {
    // Comme on boucle pour traiter plusieurs repos/sections, on ne peut pas tout quitter en cas d'erreur tant qu'on a pas bouclé sur tous les repos.
    // Du coup on initialise un variable qu'on incrémentera en cas d'erreur.
    // A la fin si cette variable > 0 alors on pourra quitter ce script en erreur (plan_exit 1)
    $plan_error = 0;

    // Récupération de la liste des repos/section du groupe
    $rows = preg_split('/\s+/', trim($planGroupList));
    //$rows = explode("\n", $planGroupList);
    foreach($rows as $LINE) {
      // Pour chaque ligne on récupère les infos du repo/section
      if ("$OS_FAMILY" == "Redhat") {
        $groupRepoName = exec("echo $LINE | awk -F ',' '{print $1}' | cut -d'=' -f2 | sed 's/\"//g'");
        $groupRepoRealname = exec("grep '^Name=\"${groupRepoName}\"' $REPOS_LIST | awk -F ',' '{print $2}' | cut -d'=' -f2 | sed 's/\"//g'");
      }
      if ("$OS_FAMILY" == "Debian") {
        $groupRepoName = exec("echo $LINE | awk -F ',' '{print $1}' | cut -d'=' -f2 | sed 's/\"//g'");
        $groupRepoDist = exec("echo $LINE | awk -F ',' '{print $2}' | cut -d'=' -f2 | sed 's/\"//g'");
        $groupRepoSection = exec("echo $LINE | awk -F ',' '{print $3}' | cut -d'=' -f2 | sed 's/\"//g'");
        $groupRepoHostname = exec("grep '^Name=\"${groupRepoName}\"' $REPOS_LIST | awk -F ',' '{print $2}' | cut -d'=' -f2 | sed 's/\"//g'");
      }
      // Si $planAction = update alors on met à jour les repos du groupe
      if ("$planAction" == "update") {
        // Exécution
        if ("$OS_FAMILY" == "Redhat") {
          $result = exec("php ${WWW_DIR}/operations/updateRepo.php $PID $LOGNAME $groupRepoName $groupRepoRealname $planGpgCheck $planGpgResign");
          if ($result == 1) {
            $plan_error++;
          }
        }
        if ("$OS_FAMILY" == "Debian") {
          $result = exec("php ${WWW_DIR}/operations/updateRepo.php $PID $LOGNAME $groupRepoName $groupRepoDist $groupRepoSection $groupRepoHostname $planGpgCheck $planGpgResign");
          if ($result == 1) {
            $plan_error++;
          }
        }
      }

      # Si $planAction contient -> alors il s'agit d'un changement d'env
      if (strpos($planAction, '->') !== false) {
        $planRepoEnv = exec("echo '$planAction' | awk -F '->' '{print $1}'");
        $planRepoNextEnv = exec("echo '$planAction' | awk -F '->' '{print $2}'");
        if (empty($planRepoEnv) OR empty($planRepoNextEnv)) {
          plan_exit($planID, 1, $LOGNAME, "Erreur (EP04) : Environnement(s) non défini(s)");
        }

        if ("$OS_FAMILY" == "Redhat") {
          if (changeEnv_rpm($groupRepoName, $planRepoEnv, $planRepoNextEnv, 'nodescription') === false) {
            $plan_error++;
          }
        }

        if ("$OS_FAMILY" == "Debian") {
          if (changeEnv_deb($groupRepoName, $groupRepoDist, $groupRepoSection, $planRepoEnv, $planRepoNextEnv, 'nodescription') === false) {
            $plan_error++;
          }
        }
      }
    }

    // Si on a rencontré des erreurs dans la boucle, alors on quitte le script
    if ($plan_error > 0) {
      plan_exit($planID, 1, $LOGNAME, "Une erreur est survenue pendant le traitement, voir les logs");
    }
  }

  # Si on est arrivé jusqu'ici alors on peut quitter sans erreur
  plan_exit($planID, 0, $LOGNAME, '');
}
?>