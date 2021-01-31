<html>
<?php include('common-head.inc.php'); ?>

<?php
  // Import des variables et fonctions nécessaires, ne pas changer l'ordre des requires
  require_once 'vars/common.vars';
  require_once 'common-functions.php';
  require_once 'common.php';
  require_once 'vars/display.vars';
  if ($debugMode == "enabled") { echo "Mode debug activé : "; print_r($_POST); }

// Cas où on ajoute une planification
if (!empty($_POST['addPlanId']) AND !empty($_POST['addPlanDate']) AND !empty($_POST['addPlanTime']) AND !empty($_POST['addPlanAction'])) {
    $error = 0; // un peu de gestion d'erreur
    $addPlanId = validateData($_POST['addPlanId']);
    $addPlanDate = validateData($_POST['addPlanDate']);
    $addPlanTime = validateData($_POST['addPlanTime']);
    // on reformate l'heure afin de remplacer ':' par un 'h' (c'est plus parlant)
    $addPlanTime = str_replace(":", "h", $addPlanTime);

    // on récupère l'action à exécuter
    $addPlanAction = $_POST['addPlanAction']; // ne pas validateData() car ça transforme '->' en caractères échappés avant de les envoyer dans PLAN_CONF. Trouver une autre solution 
    if(!empty($_POST['addPlanReminder'])) { $addPlanReminder = validateData($_POST['addPlanReminder']); } // et les rappels si il y en a

    // si l'action sélectionnée dans le formulaire est 'update', alors on récupère les valeurs des boutons radio Gpg Check et Gpg resign
    if ($addPlanAction === "update") {
      if (empty($_POST['addPlanGpgCheck'])) { // Normalement ne peut pas être vide car un des deux boutons radio est forcément sélectionné, mais bon...
        $error++;
        printAlert("Vous devez indiquer une valeur pour GPG Check");
      } else {
        $addPlanGpgCheck = validateData($_POST['addPlanGpgCheck']);
      }
      // Si rpm, on récupère la valeur du bouton radio gpg resign
      if ($OS_FAMILY == "Redhat") {
        if (empty($_POST['addPlanGpgResign'])) { // Normalement ne peut pas être vide car un des deux boutons radio est forcément sélectionné, mais bon...
          $error++;
          printAlert("Vous devez indiquer une valeur pour GPG Resign");
        } else {
          $addPlanGpgResign = validateData($_POST['addPlanGpgResign']);
        }
      }
    }

    // on récupère soit un seul repo, soit un groupe, selon ce qui a été envoyé via le formulaire
    if(!empty($_POST['addPlanRepo'])) { $addPlanRepo = validateData($_POST['addPlanRepo']); }
    if(!empty($_POST['addPlanGroup'])) { $addPlanGroup = validateData($_POST['addPlanGroup']); }
    // si les deux on été renseignés, on affiche une erreur
    if (!empty($addPlanRepo) AND !empty($addPlanGroup)) {
      $error++;
      printAlert("Il faut renseigner soit un repo, soit un groupe mais pas les deux");
    } 
    
    // Si on est sur Debian, on récupère aussi la distrib et la section (dans le cas où la planif n'est pas pour un groupe)
    if(!empty($_POST['addPlanDist'])) { $addPlanDist = validateData($_POST['addPlanDist']); }
    if(!empty($_POST['addPlanSection'])) { $addPlanSection = validateData($_POST['addPlanSection']); } 

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
      // Ajout de la planification dans le fichier de conf et ajout d'une tâche at
      // Dans le cas où on ajoute une planif pour un groupe de repos (càd addPlanGroup a été envoyé) :
      if(isset($addPlanGroup)) {
        // on indique le nom du groupe et l'action à exécuter :
        if ($addPlanAction == "update") { // si l'action est update, on ajoute aussi les infomations concernant gpg (gpg check et gpg resign si rpm)
          if ($OS_FAMILY == "Redhat") {
            exec("echo '\n[Plan-${addPlanId}]\nDate=\"${addPlanDate}\"\nTime=\"${addPlanTime}\"\nAction=\"${addPlanAction}\"\nGroup=\"${addPlanGroup}\"\nGpgCheck=\"${addPlanGpgCheck}\"\nGpgResign=\"${addPlanGpgResign}\"\nReminder=\"${addPlanReminder}\"' >> $PLAN_CONF");
          } else {
            exec("echo '\n[Plan-${addPlanId}]\nDate=\"${addPlanDate}\"\nTime=\"${addPlanTime}\"\nAction=\"${addPlanAction}\"\nGroup=\"${addPlanGroup}\"\nGpgCheck=\"${addPlanGpgCheck}\"\nReminder=\"${addPlanReminder}\"' >> $PLAN_CONF");
          }
        } else {
          exec("echo '\n[Plan-${addPlanId}]\nDate=\"${addPlanDate}\"\nTime=\"${addPlanTime}\"\nAction=\"${addPlanAction}\"\nGroup=\"${addPlanGroup}\"\nReminder=\"${addPlanReminder}\"' >> $PLAN_CONF");
        }
      }
      // Dans le cas où on ajoute une planif pour un seul repo :
      if(isset($addPlanRepo)) {
        // on indique le nom du repo :
        if ($OS_FAMILY == "Redhat") {
          if ($addPlanAction == "update") { // si l'action est update, on ajoute aussi les infomations concernant gpg (gpg check et gpg resign si rpm)
            exec("echo '\n[Plan-${addPlanId}]\nDate=\"${addPlanDate}\"\nTime=\"${addPlanTime}\"\nAction=\"${addPlanAction}\"\nRepo=\"${addPlanRepo}\"\nGpgCheck=\"${addPlanGpgCheck}\"\nGpgResign=\"${addPlanGpgResign}\"\nReminder=\"${addPlanReminder}\"' >> $PLAN_CONF");
          } else {
            exec("echo '\n[Plan-${addPlanId}]\nDate=\"${addPlanDate}\"\nTime=\"${addPlanTime}\"\nAction=\"${addPlanAction}\"\nRepo=\"${addPlanRepo}\"\nReminder=\"${addPlanReminder}\"' >> $PLAN_CONF");
          }
        }
        if ($OS_FAMILY == "Debian" ) { // Si c'est deb, on doit préciser la dist et la section
          if ($addPlanAction == "update") { // si l'action est update, on ajoute aussi les infomations concernant gpg (gpg check)
            exec("echo '\n[Plan-${addPlanId}]\nDate=\"${addPlanDate}\"\nTime=\"${addPlanTime}\"\nAction=\"${addPlanAction}\"\nRepo=\"${addPlanRepo}\"\nDist=\"${addPlanDist}\"\nSection=\"${addPlanSection}\"\nGpgCheck=\"${addPlanGpgCheck}\"\nReminder=\"${addPlanReminder}\"' >> $PLAN_CONF");
          } else {
            exec("echo '\n[Plan-${addPlanId}]\nDate=\"${addPlanDate}\"\nTime=\"${addPlanTime}\"\nAction=\"${addPlanAction}\"\nRepo=\"${addPlanRepo}\"\nDist=\"${addPlanDist}\"\nSection=\"${addPlanSection}\"\nReminder=\"${addPlanReminder}\"' >> $PLAN_CONF");
          }
        }
      }
      // Dans tous les cas on crée une tâche at avec l'id de la planification :
      // export de SHELL /bin/bash pour patcher une erreur sur CentOS où les tâches at ne se lancent pas car nginx n'a pas de shell (/sbin/nologin)
      exec("SHELL=/bin/bash; export SHELL; echo '${REPOMANAGER} --exec-plan ${addPlanId}' | at ${addPlanTime} ${addPlanDate}");   // ajout d'une tâche at
      // on formate un coup le fichier afin de supprimer les doubles saut de lignes si il y en a :
      exec('sed -i "/^$/N;/^\n$/D" '.$PLAN_CONF.''); // obligé d'utiliser de simples quotes et de concatenation sinon php évalue le \n et la commande sed ne fonctionne pas
    }
}

// Cas où on souhaite supprimer une planification
if (!empty($_GET['action']) AND ($_GET['action'] == "deletePlan") AND !empty($_GET['planId'])) {
    $planId = validateData($_GET['planId']);

    $planName = "Plan-${planId}";
    
    $planDate = shell_exec("sed -n -e '/\[${planName}\]/,/\[/p' $PLAN_CONF | sed '/^$/d' | grep -v '^\[' | grep '^Date='"); // récupération des infos de la planif
    $planDate = str_replace(['Date=', '"'], '', $planDate); // on récupère la date en retirant 'Date=""' de l'expression
    $planDate = preg_replace('/\s+/', '', $planDate); // on retire également les éventuelles tabs, espace ou fin de ligne

    $planTime = shell_exec("sed -n -e '/\[${planName}\]/,/\[/p' $PLAN_CONF | sed '/^$/d' | grep -v '^\[' | grep '^Time='"); // récupération des infos de la planif
    $planTime = str_replace(['Time=', '"'], '', $planTime); // on récupère l'heure en retirant 'Time=""' de l'expression
    $planTime = preg_replace('/\s+/', '', $planTime); // on retire également les éventuelles tabs, espace ou fin de ligne
    $planTime = str_replace("h", ":", $planTime); // on remplace 'h' par ':' dans l'heure :

    // on converti la date dans le même format que at
    // pour les dates inférieures au 10 du mois, at laisse 2 espaces entre le mois et le jour :
    // Tue Dec  1 19:14:00 2020 a www-data
    // pour les dates supérieurs à 9, at laisse 1 seul espace entre le mois et le jour :
    // Mon Nov 30 19:14:00 2020 a www-data
    // du coup on récupère seulement le jour pour commencer puis on regarde si il est inférieur à 10. En fonction, on formate la date avec 1 ou 2 espaces :
    $dateCheck = DateTime::createFromFormat('Y-m-d', $planDate)->format('j');
    if ($dateCheck < 10) {
      $planDate = DateTime::createFromFormat('Y-m-d', $planDate)->format('D M  j');
    } else {
      $planDate = DateTime::createFromFormat('Y-m-d', $planDate)->format('D M j');
    }

    $atId = exec("atq | grep \"${planDate}.*${planTime}:\" | awk '{print $1}'");
    exec("atrm $atId"); // suppression de la tache at

    // enfin, on supprime la ligne correspondante dans le fichier de conf :
    // supprime le nom du groupe entre [ ] ainsi que tout ce qui suit (ses repos) jusqu'à rencontrer une ligne vide (espace entre deux noms de groupes) :
    exec("sed -i '/^\[${planName}\]/,/^$/{d;}' $PLAN_CONF");
    // on formate un coup le fichier afin de supprimer les doubles saut de lignes si il y en a :
    exec('sed -i "/^$/N;/^\n$/D" '.$PLAN_CONF.''); // obligé d'utiliser de simples quotes et de concatenation sinon php évalue le \n et la commande sed ne fonctionne pas
}
?>

<body>
<?php include('common-header.inc.php'); ?>

  
    <!-- REPOS ACTIFS -->
    <section class="mainSectionLeft">
      <section class="left">
          <!-- REPOS ACTIFS -->
          <?php include('common-repos-list.inc.php'); ?>
      </section>
    </section>

    <section class="mainSectionRight">
      <section class="right">
        <div id="planCronStatus">
        <h5>PLANIFICATIONS</h5>
        <?php
          // on commence par vérifier si une tache cron est déjà présente ou non :
          $actualCrontab = shell_exec("crontab -l"); // on récupère le contenu actuel de la crontab de $WWW_USER
          if (strpos($actualCrontab, "--planReminders") === false || strpos($actualCrontab, "#") !== false) { // si le contenu actuel ne contient pas de tâche cron de rappel ou bien si la tâche est commentée (#), alors on affiche un cercle rouge
            echo '<a href="#"><img src="icons/red_circle.png" class="cronStatus" title="Il n\'y a pas de tâche cron active pour l\'envoi des rappels"/></a>';
          } else {
            echo '<a href="#"><img src="icons/green_circle.png" class="cronStatus" title="La tâche cron pour l\'envoi des rappels est active"/></a>'; // sinon on affiche un cercle vert
          } ?>
        </div>

        <form action="planifications.php" method="post">
        <table class="table-large">
        <?php
        $pattern = "/Plan-/i"; // dans le fichier de conf, les planifications commencent par plan:
        $PLANIFICATIONS = preg_grep($pattern, file($PLAN_CONF)); // on récupère toutes les planifications actives si il y en a
        if(!empty($PLANIFICATIONS)) { // On affiche les planifs actives si il y en a (càd si $PLANIFICATIONS est non vide)
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
          echo '<td class="td-auto"><b>Rappels</b></td>';
          echo '</tr>';

          foreach($PLANIFICATIONS as $plan) {
            $planName = str_replace(['[', ']'], '', $plan); // on retire les [crochets] autour du nom de la planif
            $planName = preg_replace('/\s+/', '', $planName); // on retire également les éventuelles tabs, espace ou fin de ligne
            
            $plan = shell_exec("sed -n -e '/\[${planName}\]/,/\[/p' $PLAN_CONF | sed '/^$/d' | grep -v '^\['"); // récupération des infos de la planif
            $plan = preg_split('/\s+/', trim($plan)); // on éclate le résultat dans un tableau car tout a été récupéré sur une ligne

            $planId = str_replace("Plan-", "", $planName); // pour récupérer l'id, il suffit de retirer "Plan-" de $planName
            $planDate = str_replace(['Date=', '"'], '', $plan[0]); // on récupère la date en retirant 'Date=""' de l'expression
            $planTime = str_replace(['Time=', '"'], '', $plan[1]); // on récupère l'heure en retirant 'Time=""' de l'expression
            $planAction = str_replace(['Action=', '"'], '', $plan[2]); // on récupère l'action en retirant 'Action=""' de l'expression
            if(substr($plan[3], 0, 4) == "Repo") { // Si la 3ème ligne commence par Repo= alors c'est un repo, sinon c'est un groupe
              $planRepoOrGroup = str_replace(['Repo=', '"'], '', $plan[3]); // on récupère le repo ou le groupe en retirant 'Repo=""' de l'expression
              if ($OS_FAMILY == "Debian") { // Si Debian, alors on récupère la dist et la section aussi
                $planDist = str_replace(['Dist=', '"'], '', $plan[4]); // on récupère la distribution en retirant 'Dist=""' de l'expression
                $planSection = str_replace(['Section=', '"'], '', $plan[5]); // on récupère la section en retirant 'Section=""' de l'expression
                if ($planAction == "update") { // si planAction = 'update' alors il faut récupérer la valeur de GpgCheck
                  $planGpgCheck = str_replace(['GpgCheck=', '"'], '', $plan[6]);
                  $planReminder = str_replace(['Reminder=', '"'], '', $plan[7]); // on récupère les rappels en retirant 'Reminder=""' de l'expression
                } else {
                  $planReminder = str_replace(['Reminder=', '"'], '', $plan[6]); // on récupère les rappels en retirant 'Reminder=""' de l'expression
                }
              }
            } else if(substr($plan[3], 0, 5) == "Group") { // sinon si la 3ème ligne commence par Group
              $planRepoOrGroup = str_replace(['Group=', '"'], '', $plan[3]); // on récupère le repo ou le groupe en retirant 'Repo=""' de l'expression
              if ($OS_FAMILY == "Debian") { // Si Debian alors on n'affiche pas de distrib ni de section (on affiche un tiret "-" à la place)
                $planDist = "-";
                $planSection = "-";
                if ($planAction == "update") { // si planAction = 'update' alors il faut récupérer la valeur de GpgCheck
                  $planGpgCheck = str_replace(['GpgCheck=', '"'], '', $plan[4]);
                  $planReminder = str_replace(['Reminder=', '"'], '', $plan[5]); // on récupère les rappels en retirant 'Reminder=""' de l'expression
                } else {
                  $planReminder = str_replace(['Reminder=', '"'], '', $plan[4]); // on récupère les rappels en retirant 'Reminder=""' de l'expression
                }
              }
            }
            if ($OS_FAMILY == "Redhat") {
              if ($planAction == "update") { // si planAction = 'update' alors il faut récupérer la valeur de GpgCheck et GpgResign
                $planGpgCheck = str_replace(['GpgCheck=', '"'], '', $plan[4]);
                $planGpgResign = str_replace(['GpgResign=', '"'], '', $plan[5]);
                $planReminder = str_replace(['Reminder=', '"'], '', $plan[6]); // on récupère les rappels en retirant 'Reminder=""' de l'expression
              } else {
                $planReminder = str_replace(['Reminder=', '"'], '', $plan[4]); // on récupère les rappels en retirant 'Reminder=""' de l'expression
              }
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
            echo "<td class=\"td-auto\">${planReminder}</td>";
            echo "<td class=\"td-auto\"><a href=\"?action=deletePlan&planId=${planId}\"><img src=\"icons/bin.png\" class=\"icon-lowopacity\" /></a></td>";
            echo '</tr>';
            // après la boucle, on va incrémenter de +1 le numéro d'ID. Ce sera l'ID attribué pour la prochaine planification ajoutée.
            $planId++;
          }
        }
        ?>
        </table>
        </form>
        <hr>

        <form action="planifications.php" method="post" autocomplete="off">
        <input type="hidden" name="addPlanId" value="<?php if (empty($planId)) { echo "1"; /* initialise la numéro de planification à 1 si il n'y en a pas */ } else { echo $planId; }?>" />
        <p><b>Ajouter une planification</b></p>
        <table class="table-large">
            <?php
            echo '<tr>';
            echo '<td class="td-fit">Date</td>';
            echo '<td class="td-large" colspan="100%"><input type="date" name="addPlanDate" /></td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td class="td-fit">Heure</td>';
            echo '<td class="td-large" colspan="100%"><input type="time" name="addPlanTime" /></td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td class="td-fit">Action</td>';
            echo '<td class="td-large" colspan="100%">';
            echo '<select name="addPlanAction" id="planSelect">';
            foreach ($ENVS as $env) {
              // on récupère l'env qui suit l'env actuel :
              $nextEnv = exec("grep -A1 '$env' $ENV_CONF | grep -v '$env'");
              if (!empty($nextEnv)) {
                echo "<option value='${env}->${nextEnv}'>Changement d'env : ${env} -> ${nextEnv}</option>";
              }
            }
            echo "<option value=\"update\" id=\"updateRepoSelect\">Mise à jour de l'environnement ${DEFAULT_ENV}</option>";
            echo '</select>';
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td class="td-fit">Repo</td>';
            echo '<td class="td-large"><input type="text" id="inputRepo" name="addPlanRepo" /></td>';
            echo '<td class="td-fit">ou Groupe</td>';
            echo '<td class="td-large"><input type="text" name="addPlanGroup" placeholder="@" /></td>';
            echo '</tr>';
            if ($OS_FAMILY == "Debian") { 
              echo '<tr class="hiddenDebInput" class="tr-hide">';
              echo '<td class="td-fit">Dist</td>';
              echo '<td class="td-large"><input type="text" name="addPlanDist" /></td>';
              echo '</tr>';
              echo '<tr class="hiddenDebInput" class="tr-hide">';
              echo '<td class="td-fit">Section</td>';
              echo '<td class="td-large"><input type="text" name="addPlanSection" /></td>';
              echo '</tr>';
            }
            echo '<tr class="hiddenGpgInput" class="tr-hide">';
            echo '<td class="td-fit">GPG check</td>';
            echo '<td colspan="2">';
            echo '<input type="radio" id="addPlanGpgCheck_yes" name="addPlanGpgCheck" value="yes" checked="yes">';
            echo '<label for="addPlanGpgCheck_yes">Yes</label>';
            echo '<input type="radio" id="addPlanGpgCheck_no" name="addPlanGpgCheck" value="no">';
            echo '<label for="addPlanGpgCheck_no">No</label>';
            echo '</td>';
            echo '</tr>';
            if ($OS_FAMILY == "Redhat") { // si rpm, alors on propose de resigner les paquets ou non
              echo "<tr class=\"hiddenGpgInput\" class=\"tr-hide\">";
              echo "<td>Re-signer avec GPG</td>";
              echo "<td colspan=\"2\">";
              if ( $GPG_SIGN_PACKAGES == "yes" ) {
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
            }
            echo "<tr>";
            echo "<td class=\"td-fit\">Rappels</td>";
            echo "<td class=\"td-large\" colspan=\"100%\"><input type=\"text\" name=\"addPlanReminder\" /></td>";
            echo "</tr>";?>
            <tr>
                <td colspan="100%"><button type="submit" class="button-submit-large-blue">Ajouter</button></td>
            </tr>
        </table>
        </form>

        <?php
          // Affichage des planifications précédemment exécutées si il y en a
          if (file_exists("$PLAN_LOG")) {
            $pattern = "/Plan-/i"; // dans le fichier de log, les planifications commencent par Plan-
            $PLANIFICATIONS = preg_grep($pattern, file($PLAN_LOG)); // on récupère toutes les planifications passées, dans le fichier de log
            if(!empty($PLANIFICATIONS)) { // On affiche les planifs si il y en a (càd si $PLANIFICATIONS est non vide)
              echo '<a href="#" id="lastPlans"><p><b>Planifications précédentes</b></p></a>';
              echo '<div id="lastPlansDiv" class="hide">';
              echo '<table class="table-large">';
              echo '<tr>';
              echo '<td class="td-fit"><b>Date</b></td>';
              echo '<td class="td-fit"><b>Heure</b></td>';
              echo '<td class="td-fit"><b>Action</b></td>';
              echo '<td class="td-fit"><b>Repo ou @groupe</b></td>';
              if ($OS_FAMILY == "Debian") {
                echo '<td class="td-fit"><b>Dist</b></td>';
                echo '<td class="td-fit"><b>Section</b></td>';
              }
              echo '<td class="td-fit"><b>GPG Chk</b></td>';
              if ($OS_FAMILY == "Redhat") {
                echo '<td class="td-fit"><b>GPG Resign</b></td>';
              }
              echo '<td class="td-fit"><b>Rappels</b></td>';
              echo '<td class="td-fit"><b>Status</b></td>';
              echo '<td class="td-fit"><b>Log</b></td>';
              echo '</tr>';

              $i = '0'; // Initialisation d'une variable qui servira pour chaque div d'erreur de planification caché, et affiché par js
              foreach($PLANIFICATIONS as $plan) {
                echo '<tr>';
                // On extrait l'ID de la planif
                $planId = str_replace(['[Plan-', ']'], '', $plan); // on récupère uniquement l'ID
                $planId = trim($planId);
                // Récup de toutes les informations et l'état de cette planification en utilisant la fonction planLogExplode
                $plan = planLogExplode($planId, $PLAN_LOG, $OS_FAMILY); // Le tout est retourné dans un tableau et placé dans $plan

                // L'array renvoyé par la fonction est sous la forme suivante. Il renvoie toutes les valeurs existantes dans les planifications en settant à null celle qui ne concernent pas la planification en cours de traitement
                // Les valeurs renvoyées par cet array seront donc toujours à la même position avec le même nom.
                // array($planStatus, $planError, $planDate, $planTime, $planAction, $planRepoOrGroup, $planGroup, $planRepo, $planDist, $planSection, $planGpgCheck, $planGpgResign, $planReminder);
                // On récupère toutes les valeurs comme ça c'est fait, et ce sera plus clair pour la suite
                $planStatus = $plan[0];
                $planError = $plan[1];
                $planDate = $plan[2];
                $planTime = $plan[3];
                $planAction = $plan[4];
                $planRepoOrGroup = $plan[5];
                $planGroup = $plan[6];
                $planRepo = $plan[7];
                $planDist = $plan[8];
                $planSection = $plan[9];
                $planGpgCheck = $plan[10];
                $planGpgResign = $plan[11];
                $planReminder = $plan[12];
                $planLogFile = $plan[13];

                // Si une date a été retournée, on l'affiche
                echo '<td class="td-fit">';
                if (!empty($planDate)) {
                  echo "${planDate}";
                } else {
                  echo '?';
                }
                echo '</td>';

                // Si une heure a été retournée, on l'affiche
                echo '<td class="td-fit">';
                if (!empty($planTime)) {
                  echo "${planTime}";
                } else {
                  echo '?';
                }
                echo '</td>';

                // Si une action a été retournée, on l'affiche
                echo '<td class="td-fit">';
                if (!empty($planAction)) {
                  echo "${planAction}";
                } else {
                  echo '?';
                }
                echo '</td>';

                if ($planRepoOrGroup === "Group") {
                  // Si un groupe a été retourné, on l'affiche
                  echo '<td class="td-fit">';
                  if (!empty($planGroup)) {
                    echo "${planGroup}";
                  } else {
                    echo '?';
                  }
                  echo '</td>';
                }

                if ($planRepoOrGroup === "Repo") {
                  // Si un repo a été retourné, on l'affiche
                  echo '<td class="td-fit">';
                  if (!empty($planRepo)) {
                    echo "${planRepo}";
                  } else {
                    echo '?';
                  }
                  echo '</td>';
                }

                // Dans le cas de Debian, on affiche la distribution et la section (ou des tirets '-' si la variable précédente était un groupe)
                if ($OS_FAMILY == "Debian") {
                  // Dist
                  echo '<td class="td-fit">';
                  if (!empty($planDist)) {
                    echo "${planDist}";
                  } else {
                    echo '?';
                  }
                  echo '</td>';
                  // Section
                  echo '<td class="td-fit">';
                  if (!empty($planSection)) {
                    echo "${planSection}";
                  } else {
                    echo '?';
                  }
                  echo '</td>';
                }

                // GPG Check
                echo '<td class="td-fit">';
                if (!empty($planGpgCheck)) {
                  echo "${planGpgCheck}";
                } else {
                  echo '?';
                }
                echo '</td>';

                // Dans le cas de Redhat/Centos, on affiche aussi la valeur de GpgResign
                if ($OS_FAMILY == "Redhat") {
                  echo '<td class="td-fit">';
                  if (!empty($planGpgResign)) {
                    echo "${planGpgResign}";
                  } else {
                    echo '?';
                  }
                  echo '</td>';
                }
                
                // Rappels
                echo '<td class="td-fit">';
                if (!empty($planReminder)) {
                  echo "${planReminder}";
                } else {
                  echo '?';
                }
                echo '</td>';

                // Status
                echo '<td class="td-fit">';
                if (!empty($planStatus) AND ($planStatus === "Error")) {
                  echo "<a href=\"#\" id=\"lastPlansError${i}\" class=\"redtext\">${planStatus}</a>";
                } 
                elseif ($planStatus === "OK") {
                  echo "<span class=\"greentext\">${planStatus}</span>";
                } else {
                  echo '?';
                }
                echo '</td>';

                // Fichier de log
                echo '<td class="td-fit">';
                if (!empty($planLogFile)) {
                  echo "<a href=\"viewlog.php?logfile=${planLogFile}\">Log</a>";
                } else {
                  echo '?';
                }
                echo '</td>';


                echo '</tr>';
                // Si le status était Error, alors on affiche une ligne (cachée) contenant le message d'erreur. 
                if ($planStatus === "Error") {
                  echo "<tr id=\"lastPlansErrorTr${i}\" class=\"tr-hide\">";
                  echo '<td colspan="100%">';
                  echo "$planError";
                  echo '</td>';
                  echo '</tr>';
                  // On injecte alors du code js pour pouvoir déployer la ligne cachée par défaut
                  echo "
                  <script>
                  $(document).ready(function(){
                    $(\"a#lastPlansError${i}\").click(function(){
                      $(\"tr#lastPlansErrorTr${i}\").slideToggle(50);
                      $(this).toggleClass(\"open\");
                    });
                  });
                  </script>";
                  $i++;
                }
              }
              echo '</table>';
              echo '</div>';
            }
          }
          ?>
      </section>
    </section>

<!-- divs cachées de base -->
<!-- GERER LES GROUPES -->
<?php include('common-groupslist.inc.php'); ?>

<!-- REPOS/HOTES SOURCES -->
<?php include('common-repos-sources.inc.php'); ?>

<?php include('common-footer.inc.php'); ?>
</body>

<script>
// Afficher des inputs supplémentaires si quelque chose est tapé au clavier dans le input 'Repo'
  // Bind keyup event on the input
  $('#inputRepo').keyup(function() {
    // If value is not empty
    if ($(this).val().length == 0) {
      // Hide the element
      $('.hiddenDebInput').hide();
    } else {
      // Otherwise show it
      $('.hiddenDebInput').show();
    }
  }).keyup(); // Trigger the keyup event, thus running the handler on page load

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

// Afficher les planifications précédentes
  $(document).ready(function(){
    $("a#lastPlans").click(function(){
      $("div#lastPlansDiv").slideToggle(250);
      $(this).toggleClass("open");
    });
  });
</script>
</html>