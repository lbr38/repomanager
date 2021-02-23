<!DOCTYPE html>
<html>
<?php include('common-head.inc.php'); ?>

<?php
  // Import des variables et fonctions nécessaires, ne pas changer l'ordre des requires
  require 'functions/load_common_variables.php';
  require 'functions/load_display_variables.php';
  require 'functions/common-functions.php';
  require 'common.php';
  if ($DEBUG_MODE == "enabled") { echo 'Mode debug activé : ';	echo '<br>POST '; print_r($_POST); echo '<br>GET ';	print_r($_GET); }

// Cas où on ajoute une planification
if (!empty($_POST['addPlanDate']) AND !empty($_POST['addPlanTime']) AND !empty($_POST['addPlanAction'])) {
    $error = 0; // un peu de gestion d'erreur
    $addPlanDate = validateData($_POST['addPlanDate']);
    $addPlanTime = validateData($_POST['addPlanTime']);
    // on reformate l'heure afin de remplacer ':' par un 'h' (c'est plus parlant)
    $addPlanTime = str_replace(":", "h", $addPlanTime);
    // on récupère l'action à exécuter
    $addPlanAction = $_POST['addPlanAction']; // ne pas validateData() car ça transforme '->' en caractères échappés
    // et les rappels si il y en a
    if(!empty($_POST['addPlanReminder'])) {
      $addPlanReminder = '';
      // On sépare chaque jour de rappel par une virgule
      foreach ($_POST['addPlanReminder'] as $selectedOption) {
        $selectedOption = validateData($selectedOption);
        $addPlanReminder = "${addPlanReminder}${selectedOption},";
      }
      // Suppression de la dernière virgule
      $addPlanReminder = rtrim($addPlanReminder, ",");
    } 

    // si l'action sélectionnée dans le formulaire est 'update', alors on récupère les valeurs des boutons radio Gpg Check et Gpg resign
    if ($addPlanAction === "update") {
      if (empty($_POST['addPlanGpgCheck'])) { // Normalement ne peut pas être vide car un des deux boutons radio est forcément sélectionné, mais bon...
        $error++;
        printAlert("Vous devez indiquer une valeur pour GPG Check");
      } else {
        $addPlanGpgCheck = validateData($_POST['addPlanGpgCheck']);
      }
      // On récupère la valeur du bouton radio gpg resign
      if (empty($_POST['addPlanGpgResign'])) { // Normalement ne peut pas être vide car un des deux boutons radio est forcément sélectionné, mais bon...
        $error++;
        printAlert("Vous devez indiquer une valeur pour GPG Resign");
      } else {
        $addPlanGpgResign = validateData($_POST['addPlanGpgResign']);
      } 
    }

    // on récupère soit un seul repo, soit un groupe, selon ce qui a été envoyé via le formulaire
    // Cas où c'est un repo
    if(!empty($_POST['addPlanRepo'])) {
      $addPlanRepo = validateData($_POST['addPlanRepo']);
      // Pour Debian, la fonction reposSelectList() a renvoyé une valeur contenant le nom du repo, la dist et la section séparés par un | (voir fonction reposSelectList())
      // Du coup on explose $addPlanRepo pour en extraire les 3 valeurs
      if ($OS_FAMILY == "Debian") {
        $addPlanRepoExplode = explode('|', $addPlanRepo);
        $addPlanRepo = $addPlanRepoExplode[0];
        $addPlanDist = $addPlanRepoExplode[1];
        $addPlanSection = $addPlanRepoExplode[2];
      }
    }
    // Cas où c'est un groupe
    if(!empty($_POST['addPlanGroup'])) {
      $addPlanGroup = validateData($_POST['addPlanGroup']);
    }
    // si les deux on été renseignés, on affiche une erreur
    if (!empty($addPlanRepo) AND !empty($addPlanGroup)) {
      $error++;
      printAlert("Il faut renseigner soit un repo, soit un groupe mais pas les deux");
    }

    // on vérifie que le repo ou la section indiqué existe dans la liste des repos
    if (!empty($addPlanRepo)) {
      if ($OS_FAMILY == "Redhat") {
        $checkIfRepoExists = exec("grep '^Name=\"${addPlanRepo}\"' $REPOS_LIST");
        if (empty($checkIfRepoExists)) {
          $error++;
          printAlert("Le repo $addPlanRepo n'existe pas");
        }
      }
      if ($OS_FAMILY == "Debian") {
        $checkIfRepoExists = exec("grep '^Name=\"${addPlanRepo}\",Host=\".*\",Dist=\"${addPlanDist}\",Section=\"${addPlanSection}\"' $REPOS_LIST");
        if (empty($checkIfRepoExists)) {
          $error++;
          printAlert("La section $addPlanSection du repo $addPlanRepo (distribution ${addPlanDist}) n'existe pas");
        }
      }
    }

    // on vérifie que le groupe indiqué existe dans le fichier de groupes
    if (!empty($addPlanGroup)) {
      $checkIfGroupExists = exec("grep '\[${addPlanGroup}\]' $GROUPS_CONF");
      if (empty($checkIfGroupExists)) {
        $error++;
        printAlert("Le groupe $addPlanGroup n'existe pas");
      }
    }

    // On traite uniquement si il n'y a pas eu d'erreur précédemment
    if ($error === 0) {
      // Génération d'un ID de planification, basé sur la date transmise, l'heure transmise, puis d'un nombre aléatoire
      $addPlanId = DateTime::createFromFormat('Y-m-d', $addPlanDate)->format('Ymd').str_replace("h", "", $addPlanTime).mt_rand(100001, 999999);
      $planFileName = "plan-${addPlanId}.conf";

      // Ajout de la planification dans le fichier de conf et ajout d'une tâche at
      // Dans le cas où on ajoute une planif pour un groupe de repos (càd addPlanGroup a été envoyé) :
      if(isset($addPlanGroup)) {
        // on indique le nom du groupe et l'action à exécuter :
        if ($addPlanAction == "update") { // si l'action est update, on ajoute aussi les infomations concernant gpg (gpg check et gpg resign si rpm)
          exec("echo '[${addPlanId}]\nDate=\"${addPlanDate}\"\nTime=\"${addPlanTime}\"\nAction=\"${addPlanAction}\"\nGroup=\"${addPlanGroup}\"\nGpgCheck=\"${addPlanGpgCheck}\"\nGpgResign=\"${addPlanGpgResign}\"\nReminder=\"${addPlanReminder}\"' >> ${PLANS_DIR}/${planFileName}");
        } else {
          exec("echo '[${addPlanId}]\nDate=\"${addPlanDate}\"\nTime=\"${addPlanTime}\"\nAction=\"${addPlanAction}\"\nGroup=\"${addPlanGroup}\"\nReminder=\"${addPlanReminder}\"' >> ${PLANS_DIR}/${planFileName}");
        }
      }
      // Dans le cas où on ajoute une planif pour un seul repo :
      if(isset($addPlanRepo)) {
        // on indique le nom du repo :
        if ($OS_FAMILY == "Redhat") {
          if ($addPlanAction == "update") { // si l'action est update, on ajoute aussi les infomations concernant gpg (gpg check et gpg resign si rpm)
            exec("echo '[${addPlanId}]\nDate=\"${addPlanDate}\"\nTime=\"${addPlanTime}\"\nAction=\"${addPlanAction}\"\nRepo=\"${addPlanRepo}\"\nGpgCheck=\"${addPlanGpgCheck}\"\nGpgResign=\"${addPlanGpgResign}\"\nReminder=\"${addPlanReminder}\"' >> ${PLANS_DIR}/${planFileName}");
          } else {
            exec("echo '[${addPlanId}]\nDate=\"${addPlanDate}\"\nTime=\"${addPlanTime}\"\nAction=\"${addPlanAction}\"\nRepo=\"${addPlanRepo}\"\nReminder=\"${addPlanReminder}\"' >> ${PLANS_DIR}/${planFileName}");
          }
        }
        if ($OS_FAMILY == "Debian") { // Si c'est deb, on doit préciser la dist et la section
          if ($addPlanAction == "update") { // si l'action est update, on ajoute aussi les infomations concernant gpg (gpg check)
            exec("echo '[${addPlanId}]\nDate=\"${addPlanDate}\"\nTime=\"${addPlanTime}\"\nAction=\"${addPlanAction}\"\nRepo=\"${addPlanRepo}\"\nDist=\"${addPlanDist}\"\nSection=\"${addPlanSection}\"\nGpgCheck=\"${addPlanGpgCheck}\"\nGpgResign=\"${addPlanGpgResign}\"\nReminder=\"${addPlanReminder}\"' >> ${PLANS_DIR}/${planFileName}");
          } else {
            exec("echo '[${addPlanId}]\nDate=\"${addPlanDate}\"\nTime=\"${addPlanTime}\"\nAction=\"${addPlanAction}\"\nRepo=\"${addPlanRepo}\"\nDist=\"${addPlanDist}\"\nSection=\"${addPlanSection}\"\nReminder=\"${addPlanReminder}\"' >> ${PLANS_DIR}/${planFileName}");
          }
        }
      }
      // Dans tous les cas on crée une tâche at avec l'id de la planification :
      // export de SHELL /bin/bash pour patcher une erreur sur CentOS où les tâches at ne se lancent pas car nginx n'a pas de shell (/sbin/nologin)
      //exec("SHELL=/bin/bash; export SHELL; echo 'php ${WWW_DIR}/planifications/plan_exec.php ${addPlanId}' | at ${addPlanTime} ${addPlanDate}");   // ajout d'une tâche at
    }
}

// Cas où on souhaite supprimer une planification
if (!empty($_GET['action']) AND ($_GET['action'] == "deletePlan") AND !empty($_GET['planId'])) {
  $planId = validateData($_GET['planId']);
  $planDate = exec("egrep '^Date=' ${PLANS_DIR}/plan-${planId}.conf"); // récupération des infos de la planif
  $planDate = str_replace(['Date=', '"'], '', $planDate); // on récupère la date en retirant 'Date=""' de l'expression
  $planDate = preg_replace('/\s+/', '', $planDate); // on retire également les éventuelles tabs, espace ou fin de ligne
  $planTime = exec("egrep '^Time=' ${PLANS_DIR}/plan-${planId}.conf"); // récupération des infos de la planif
  $planTime = str_replace(['Time=', '"'], '', $planTime); // on récupère l'heure en retirant 'Time=""' de l'expression
  $planTime = preg_replace('/\s+/', '', $planTime); // on retire également les éventuelles tabs, espace ou fin de ligne
  $planTime = str_replace("h", ":", $planTime); // on remplace 'h' par ':' dans l'heure :
  // on converti la date dans le même format que at
  // pour les dates inférieures au 10 du mois, at laisse 2 espaces entre le mois et le jour :
  // Tue Dec  1 19:14:00 2020 a www-data
  // pour les dates supérieurs à 9, at laisse 1 seul espace entre le mois et le jour :
  // Mon Nov 30 19:14:00 2020 a www-data
  // du coup on récupère seulement le jour pour commencer puis on regarde si il est inférieur à 10. En fonction, on formate la date avec 1 ou 2 espaces :
  /*$dateCheck = DateTime::createFromFormat('Y-m-d', $planDate)->format('j');
  if ($dateCheck < 10) {
    $planDate = DateTime::createFromFormat('Y-m-d', $planDate)->format('D M  j');
  } else {
    $planDate = DateTime::createFromFormat('Y-m-d', $planDate)->format('D M j');
  }
  $atId = exec("atq | grep \"${planDate}.*${planTime}:\" | awk '{print $1}'");
  exec("atrm $atId"); // suppression de la tache at*/

  // enfin, on supprime le fichier de planification :
  unlink("${PLANS_DIR}/plan-${planId}.conf");
}
?>

<body>
<?php include('common-header.inc.php'); ?>
<!-- section 'conteneur' principal englobant toutes les sections de droite -->
<!-- On charge la section de droite avant celle de gauche car celle-ci peut mettre plus de temps à charger (si bcp de repos) -->
    <section class="mainSectionRight">
      <section class="right">
        <div id="planCronStatus">
        <h5>PLANIFICATIONS</h5>
        <?php
          // on commence par vérifier si une tache cron est déjà présente ou non :
          $cronStatus = checkCronReminder();
          if ($cronStatus == 'On') {
            echo '<img src="icons/green_circle.png" class="cronStatus pointer" title="La tâche cron pour l\'envoi des rappels est active"/>';
          }
          if ($cronStatus == 'Off') {
            echo '<img src="icons/red_circle.png" class="cronStatus pointer" title="Il n\'y a pas de tâche cron active pour l\'envoi des rappels"/>';
          }?>
        </div>

        <form action="planifications.php" method="post">
        <table class="table-large">
        <?php
        $i = 0;
        $planFiles = shell_exec("ls -A1 $PLANS_DIR/ | egrep '^plan-'");
        if(!empty($planFiles)) { // On affiche les planifs actives si il y en a (càd si $PLANIFICATIONS est non vide)
          echo '<p><b>Planifications actives</b></p>';
          echo '<tr>';
          echo '<td class="td-auto"><b>Date</b></td>';
          echo '<td class="td-auto"><b>Heure</b></td>';
          echo '<td class="td-auto"><b>Action</b></td>';
          echo '<td class="td-auto"><b>Repo ou @groupe</b></td>';
          if ($OS_FAMILY == "Debian") {
            echo '<td class="td-auto"><b>Dist</b></td>';
            echo '<td class="td-auto"><b>Section</b></td>';
          }
          echo '</tr>';

          $planFiles = preg_split('/\s+/', trim($planFiles));
          foreach($planFiles as $planFile) {
            $planId = str_replace(['[', ']'], '', exec("egrep '^\[' $PLANS_DIR/$planFile"));
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
            if (empty($planReminder)) {
              $planReminder = 'Aucun'; 
            } else {
              $planReminder = "$planReminder (jours avant)";
            }

            echo '<tr>';
            echo "<td class=\"td-auto\">${planDate}</td>";
            echo "<td class=\"td-auto\">${planTime}</td>";
            echo "<td class=\"td-auto\">${planAction}</td>";
            echo "<td class=\"td-auto\">${planRepoOrGroup}</td>";
            if ($OS_FAMILY == "Debian") {
              echo "<td class=\"td-auto\">${planDist}</td>";
              echo "<td class=\"td-auto\">${planSection}</td>";
            }
            echo "<td class=\"td-auto\">";
            echo "<img id=\"planDetailsToggle${i}\" class=\"icon-lowopacity\" title=\"Afficher les détails\" src=\"icons/search.png\" />";
            echo "<img class=\"planDeleteToggle${i} icon-lowopacity\" title=\"Supprimer la planification\" src=\"icons/bin.png\" />";
            echo "</td>";
            deleteConfirm("Êtes-vous sûr de vouloir supprimer la planification du $planDate à $planTime", "?action=deletePlan&planId=${planId}", "planDeleteDiv${i}", "planDeleteToggle${i}");
            echo '</tr>';
            echo "<tr id=\"planDetailsTr${i}\" class=\"hide background-gray\">";
            echo '<td colspan="100%">';
            if ($planAction == "update") {
              echo "<b>GPG Check : </b>${planGpgCheck}<br>";
              echo "<b>GPG Resign : </b>${planGpgResign}<br>";
            }
            echo "<b>Rappels : </b>${planReminder}<br>";
            echo '</td>';
            echo '</tr>';
            // Script JS pour afficher les détails cachés
            echo "<script>
            $(function() {
              $('#planDetailsToggle${i}').click(function() {
                  $('#planDetailsTr${i}').toggle('slow');
              });
            });
            </script>";
            ++$i;
          }
        } ?>
        </table>
        </form>
        <hr>

        <form action="planifications.php" method="post" autocomplete="off">
        <p><b>Ajouter une planification</b></p>
        <table class="table-large">
            <?php
            echo '<tr>';
            echo '<td class="td-fit">Date</td>';
            echo '<td colspan="100%"><input type="date" name="addPlanDate" required /></td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td class="td-fit">Heure</td>';
            echo '<td colspan="100%"><input type="time" name="addPlanTime" required /></td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td class="td-fit">Action</td>';
            echo '<td colspan="100%">';
            echo '<select name="addPlanAction" id="planSelect">';
            foreach ($ENVS as $env) {
              // on récupère l'env qui suit l'env actuel :
              $nextEnv = exec("grep -A1 '$env' $ENV_CONF | grep -v '$env'");
              if (!empty($nextEnv)) {
                echo "<option value='${env}->${nextEnv}'>Faire pointer un environnement ${nextEnv} -> ${env}</option>";
              }
            }
            if ($ENVS_TOTAL >= 1) {
              echo "<option value=\"update\" id=\"updateRepoSelect\">Mise à jour de l'environnement ${DEFAULT_ENV}</option>";
            }
            echo '</select>';
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td class="td-fit">Repo</td>';
            echo '<td>';
            echo '<select name="addPlanRepo">';
            reposSelectList_defaultEnv();
            echo '</select>';
            echo '</td>';
            echo '<td class="td-fit">ou Groupe</td>';
            echo '<td>';
            echo '<select name="addPlanGroup">';
            groupsSelectList(); // La fonction affiche les options de liste déroulante contenant la liste des groupes dans GROUPS_CONF
            echo '</select>';
            echo '</td>';
            echo '</tr>';
            echo '<tr class="hiddenGpgInput" class="tr-hide">';
            echo '<td class="td-fit">GPG check</td>';
            echo '<td colspan="2">';
            echo '<input type="radio" id="addPlanGpgCheck_yes" name="addPlanGpgCheck" value="yes" checked="yes">';
            echo '<label for="addPlanGpgCheck_yes">Yes</label>';
            echo '<input type="radio" id="addPlanGpgCheck_no" name="addPlanGpgCheck" value="no">';
            echo '<label for="addPlanGpgCheck_no">No</label>';
            echo '</td>';
            echo '</tr>';      
            echo '<tr class="hiddenGpgInput" class="tr-hide">';
            echo "<td>Re-signer avec GPG</td>";
            echo "<td colspan=\"2\">";
            if ($GPG_SIGN_PACKAGES == "yes") {
              echo '<input type="radio" id="addPlanGpgResign_yes" name="addPlanGpgResign" value="yes" checked="yes">';
              echo '<label for="addPlanGpgResign_yes">Yes</label>';
              echo '<input type="radio" id="addPlanGpgResign_no" name="addPlanGpgResign" value="no">';
              echo '<label for="addPlanGpgResign_no">No</label>';
            } else {
              echo '<input type="radio" id="addPlanGpgResign_yes" name="addPlanGpgResign" value="yes">';
              echo '<label for="addPlanGpgResign_yes">Yes</label>';
              echo '<input type="radio" id="addPlanGpgResign_no" name="addPlanGpgResign" value="no" checked="yes">';
              echo '<label for="addPlanGpgResign_no">No</label>';
            } 
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td class="td-fit">Rappels</td>';
            echo '<td colspan="100%">';
            echo '<select id="planReminderSelect" name="addPlanReminder[]" multiple>';
            echo '<option value="1">1 jour avant</option>';
            echo '<option value="2">2 jours avant</option>';
            echo '<option value="3" selected>3 jours avant</option>';
            echo '<option value="4">4 jours avant</option>';
            echo '<option value="5">5 jours avant</option>';
            echo '<option value="6">6 jours avant</option>';
            echo '<option value="7" selected>7 jours avant</option>';
            echo '<option value="8">8 jours avant</option>';
            echo '<option value="9">9 jours avant</option>';
            echo '<option value="10">10 jours avant</option>';
            echo '<option value="15">15 jours avant</option>';
            echo '<option value="20">20 jours avant</option>';
            echo '<option value="25">25 jours avant</option>';
            echo '<option value="30">30 jours avant</option>';
            echo '<option value="35">35 jours avant</option>';
            echo '<option value="40">40 jours avant</option>';
            echo '<option value="45">45 jours avant</option>';
            echo '<option value="50">50 jours avant</option>';
            echo '<option value="55">55 jours avant</option>';
            echo '<option value="60">60 jours avant</option>';
            echo '</select>';
            echo '</td>';
            //echo "<td class=\"td-large\" colspan=\"100%\"><input type=\"text\" name=\"addPlanReminder\" /></td>";
            echo "</tr>";?>
            <tr>
              <td colspan="100%"><button type="submit" class="button-submit-large-blue">Ajouter</button></td>
            </tr>
        </table>
        </form>

        <?php
          // Affichage des planifications précédemment exécutées si il y en a
          $planLogFiles = shell_exec("ls -A1 $PLAN_LOGS_DIR/ | egrep '^plan-'");
          if (!empty("$planLogFiles")) {
            echo '<p><b>Historique des planifications</b></p>';
            echo '<table class="table-large">';
            echo '<tr>';
            echo '<td class="td-fit"><b>Date</b></td>';
            echo '<td class="td-fit"><b>Heure</b></td>';
            echo '<td class="td-fit"><b>Action</b></td>';
            echo '<td class="td-fit"><b>Cible</b></td>';
            if ($OS_FAMILY == "Debian") {
              echo '<td class="td-fit"><b>Dist</b></td>';
              echo '<td class="td-fit"><b>Section</b></td>';
            }
            echo '<td class="td-fit"><b>Status</b></td>';
            echo '</tr>';

            $i = 0; // Initialisation d'une variable qui servira pour chaque div d'erreur de planification caché, et affiché par js
            $planLogFiles = preg_split('/\s+/', trim($planLogFiles));
            foreach($planLogFiles as $planLogFile) {
            //foreach($PLANIFICATIONS as $plan) {
              echo '<tr>';
              // On extrait l'ID de la planif
              $planId = str_replace(['[', ']'], '', exec("egrep '^\[' $PLAN_LOGS_DIR/$planLogFile"));

              // Récup de toutes les informations et l'état de cette planification en utilisant la fonction planLogExplode
              if ($OS_FAMILY == "Redhat") {
                list($planStatus, $planError, $planDate, $planTime, $planAction, $planGroup, $planRepo, $planGpgCheck, $planGpgResign, $planReminder, $planLogFile) = planLogExplode($planId);
              }
              if ($OS_FAMILY == "Debian") {
                list($planStatus, $planError, $planDate, $planTime, $planAction, $planGroup, $planRepo, $planDist, $planSection, $planGpgCheck, $planReminder, $planLogFile) = planLogExplode($planId);
              }
              if (empty($planDate)) { $planDate = '?'; }
              if (empty($planTime)) { $planTime = '?'; }
              if (empty($planAction)) { $planAction = '?'; }
              if ($OS_FAMILY == "Debian") {
                if (!empty($planGroup)) {
                  $planDist = '-';
                  $planSection = '-';
                }
                if (!empty($planRepo)) {
                  if (empty($planDist)) { $planDist = '?'; }
                  if (empty($planSection)) { $planSection = '?'; }
                }
              }
              if (empty($planGpgCheck)) { $planGpgCheck = '?'; }
              if (empty($planGpgResign)) { $planGpgResign = '?'; }
              if (empty($planReminder)) { $planReminder = '?'; }
              if (empty($planLogFile)) { $planLogFile = '?'; }
              if (empty($planStatus)) { $planStatus = '?'; }

              // Affichage de la date
              echo '<td class="td-fit">';
              echo "$planDate";
              echo '</td>';

              // Affichage de l'heure
              echo '<td class="td-fit">';
              echo "$planTime";
              echo '</td>';

              // Affichage de l'action
              echo '<td class="td-fit">';
              echo "$planAction";
              echo '</td>';

              // Affichage du repo ou du groupe
              echo '<td class="td-fit">';
              if (!empty($planGroup)) { echo "$planGroup"; }
              if (!empty($planRepo)) { echo "$planRepo"; }
              echo '</td>';

              // Dans le cas de Debian, on affiche la distribution et la section (ou des tirets '-' si la variable précédente était un groupe)
              if ($OS_FAMILY == "Debian") {
                // Affichage de la distribution
                echo '<td class="td-fit">';
                echo "$planDist";
                echo '</td>';
                // Affichage de la section
                echo '<td class="td-fit">';
                echo "$planSection";
                echo '</td>';
              }

              // Affichage du status
              echo '<td class="td-fit">';
              if ($planStatus === "Error") {
                echo "<span id=\"planErrorToggle${i}\" class=\"redtext\">${planStatus}</span>";
              } 
              elseif ($planStatus === "OK") {
                echo "<span class=\"greentext\">${planStatus}</span>";
              } else {
                echo '?';
              }
              echo '</td>';

              // Affichage des détails
              echo '<td class="td-fit">';
              echo "<img id=\"planStatusToggle${i}\" class=\"icon-lowopacity\" title=\"Afficher les détails\" src=\"icons/search.png\" />";
              echo '</td>';
              echo '</tr>';
              
              echo "<tr id=\"planStatusTr${i}\" class=\"hide background-gray\">";
              echo '<td colspan="100%">';
              if ($planStatus === "Error") {
                echo "$planError<br>";
              }
              echo "<b>GPG Check : </b>$planGpgCheck<br>";
              echo "<b>GPG Resign : </b>$planGpgResign<br>";
              echo "<b>Rappels : </b>$planReminder<br>";
              echo "<b><a href=\"run.php?logfile=${planLogFile}\">Log</a></b>";
              echo '</tr>';

              // On injecte alors du code js pour pouvoir déployer la ligne cachée par défaut
              echo "<script>
              $(function() {
                $('#planStatusToggle${i}').click(function() {
                    $('#planStatusTr${i}').toggle('slow');
                });
              });
              </script>";
              ++$i;
            }
            echo '</table>';
          } ?>
      </section>
    </section>

<!-- section 'conteneur' principal englobant toutes les sections de gauche -->
<!-- On charge la section de gauche après celle de droite car elle peut mettre plus de temps à charger (si bcp de repos) -->
<section class="mainSectionLeft">
    <section class="left">
        <!-- REPOS ACTIFS -->
        <?php include('common-repos-list.inc.php'); ?>
    </section>
    <section class="left">
        <!-- REPOS ARCHIVÉS-->
        <?php include('common-repos-archive-list.inc.php'); ?>
    </section>
</section>

<?php include('common-footer.inc.php'); ?>

</body>
<script>
// Afficher des boutons radio si l'option du select sélectionnée est '#updateRepoSelect' afin de choisir si on souhaite activer gpg check et resigner les paquets
$(function() {
  $("#planSelect").change(function() {
    if ($("#updateRepoSelect").is(":selected")) {
      $(".hiddenGpgInput").show();
    } else {
      $(".hiddenGpgInput").hide();
    }
  }).trigger('change');
});
</script>
<script>
// Script Select2 pour transformer un select multiple en liste déroulante
$('#planReminderSelect').select2({
  closeOnSelect: false,
  placeholder: 'Ajouter un rappel...'
});
</script>
</html>