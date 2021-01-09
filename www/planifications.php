<html>
<?php include('common-head.inc.php'); ?>

<?php
  // Import des variables nécessaires, ne pas changer l'ordre des require
  require 'common-vars.php';
  require 'common-functions.php';
  require 'common.php';
  require 'display.php';
  if ($debugMode == "enabled") { print_r($_POST); }

// Cas où on ajoute une planification
if (!empty($_POST['addPlanId']) AND !empty($_POST['addPlanDate']) AND !empty($_POST['addPlanTime']) AND !empty($_POST['addPlanAction'])) {
    $error = 0; // un peu de gestion d'erreur
    $addPlanId = validateData($_POST['addPlanId']);
    $addPlanDate = validateData($_POST['addPlanDate']);
    $addPlanTime = validateData($_POST['addPlanTime']);
    // on reformate l'heure afin de remplacer ':' par un 'h' (c'est plus parlant)
    $addPlanTime = str_replace(":", "h", $addPlanTime);

    // on récupère l'action à exécuter
    $addPlanAction = $_POST['addPlanAction']; // ne pas validateData() car ça transforme '->' en caractères échappés avant de les envoyer dans PLAN_CONF_FILE. Trouver une autre solution 
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
      if ($OS_TYPE == "rpm") {
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
      if ($OS_TYPE == "rpm") {
        $checkIfRepoExists = exec("grep '^Name=\"${addPlanRepo}\"' $REPO_FILE");
        if (empty($checkIfRepoExists)) {
          $error++;
          printAlert("Le repo $addPlanRepo n'existe pas");
        }
      }
      if ($OS_TYPE == "deb") {
        $checkIfRepoExists = exec("grep '^Name=\"${addPlanRepo}\",Host=\".*\",Dist=\"${addPlanDist}\",Section=\"${addPlanSection}\"' $REPO_FILE");
        if (empty($checkIfRepoExists)) {
          $error++;
          printAlert("La section $addPlanSection du repo $addPlanRepo (distribution ${addPlanDist}) n'existe pas");
        }
      }
    }

    // on vérifie que le groupe indiqué existe dans le fichier de groupes
    if (!empty($addPlanGroup)) {
      $checkIfGroupExists = exec("grep '\[${addPlanGroup}\]' $REPO_GROUPS_FILE");
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
          if ($OS_TYPE == "rpm") {
            exec("echo '\n[Plan-${addPlanId}]\nDate=\"${addPlanDate}\"\nTime=\"${addPlanTime}\"\nAction=\"${addPlanAction}\"\nGroup=\"${addPlanGroup}\"\nGpgCheck=\"${addPlanGpgCheck}\"\nGpgResign=\"${addPlanGpgResign}\"\nReminder=\"${addPlanReminder}\"' >> $PLAN_CONF_FILE");
          } else {
            exec("echo '\n[Plan-${addPlanId}]\nDate=\"${addPlanDate}\"\nTime=\"${addPlanTime}\"\nAction=\"${addPlanAction}\"\nGroup=\"${addPlanGroup}\"\nGpgCheck=\"${addPlanGpgCheck}\"\nReminder=\"${addPlanReminder}\"' >> $PLAN_CONF_FILE");
          }
        } else {
          exec("echo '\n[Plan-${addPlanId}]\nDate=\"${addPlanDate}\"\nTime=\"${addPlanTime}\"\nAction=\"${addPlanAction}\"\nGroup=\"${addPlanGroup}\"\nReminder=\"${addPlanReminder}\"' >> $PLAN_CONF_FILE");
        }
      }
      // Dans le cas où on ajoute une planif pour un seul repo :
      if(isset($addPlanRepo)) {
        // on indique le nom du repo :
        if ($OS_TYPE == "rpm") {
          if ($addPlanAction == "update") { // si l'action est update, on ajoute aussi les infomations concernant gpg (gpg check et gpg resign si rpm)
            exec("echo '\n[Plan-${addPlanId}]\nDate=\"${addPlanDate}\"\nTime=\"${addPlanTime}\"\nAction=\"${addPlanAction}\"\nRepo=\"${addPlanRepo}\"\nGpgCheck=\"${addPlanGpgCheck}\"\nGpgResign=\"${addPlanGpgResign}\"\nReminder=\"${addPlanReminder}\"' >> $PLAN_CONF_FILE");
          } else {
            exec("echo '\n[Plan-${addPlanId}]\nDate=\"${addPlanDate}\"\nTime=\"${addPlanTime}\"\nAction=\"${addPlanAction}\"\nRepo=\"${addPlanRepo}\"\nReminder=\"${addPlanReminder}\"' >> $PLAN_CONF_FILE");
          }
        }
        if ($OS_TYPE == "deb" ) { // Si c'est deb, on doit préciser la dist et la section
          if ($addPlanAction == "update") { // si l'action est update, on ajoute aussi les infomations concernant gpg (gpg check)
            exec("echo '\n[Plan-${addPlanId}]\nDate=\"${addPlanDate}\"\nTime=\"${addPlanTime}\"\nAction=\"${addPlanAction}\"\nRepo=\"${addPlanRepo}\"\nDist=\"${addPlanDist}\"\nSection=\"${addPlanSection}\"\nGpgCheck=\"${addPlanGpgCheck}\"\nReminder=\"${addPlanReminder}\"' >> $PLAN_CONF_FILE");
          } else {
            exec("echo '\n[Plan-${addPlanId}]\nDate=\"${addPlanDate}\"\nTime=\"${addPlanTime}\"\nAction=\"${addPlanAction}\"\nRepo=\"${addPlanRepo}\"\nDist=\"${addPlanDist}\"\nSection=\"${addPlanSection}\"\nReminder=\"${addPlanReminder}\"' >> $PLAN_CONF_FILE");
          }
        }
      }
      // Dans tous les cas on crée une tâche at avec l'id de la planification :
      exec("echo '${REPOMANAGER} --web --exec-plan ${addPlanId}' | at ${addPlanTime} ${addPlanDate}");   // ajout d'une tâche at
      // on formate un coup le fichier afin de supprimer les doubles saut de lignes si il y en a :
      exec('sed -i "/^$/N;/^\n$/D" '.$PLAN_CONF_FILE.''); // obligé d'utiliser de simples quotes et de concatenation sinon php évalue le \n et la commande sed ne fonctionne pas
    }
}

// Cas où on souhaite supprimer une planification
if (!empty($_GET['action']) AND ($_GET['action'] == "deletePlan") AND !empty($_GET['planId'])) {
    $planId = validateData($_GET['planId']);

    $planName = "Plan-${planId}";
    
    $planDate = shell_exec("sed -n -e '/\[${planName}\]/,/\[/p' $PLAN_CONF_FILE | sed '/^$/d' | grep -v '^\[' | grep '^Date='"); // récupération des infos de la planif
    $planDate = str_replace(['Date=', '"'], '', $planDate); // on récupère la date en retirant 'Date=""' de l'expression
    $planDate = preg_replace('/\s+/', '', $planDate); // on retire également les éventuelles tabs, espace ou fin de ligne

    $planTime = shell_exec("sed -n -e '/\[${planName}\]/,/\[/p' $PLAN_CONF_FILE | sed '/^$/d' | grep -v '^\[' | grep '^Time='"); // récupération des infos de la planif
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

    // ICI : trouver comment faire pour supprimer les taches at des rappels associées à cette planification

    // enfin, on supprime la ligne correspondante dans le fichier de conf :
    // supprime le nom du groupe entre [ ] ainsi que tout ce qui suit (ses repos) jusqu'à rencontrer une ligne vide (espace entre deux noms de groupes) :
    exec("sed -i '/^\[${planName}\]/,/^$/{d;}' $PLAN_CONF_FILE");
    // on formate un coup le fichier afin de supprimer les doubles saut de lignes si il y en a :
    exec('sed -i "/^$/N;/^\n$/D" '.$PLAN_CONF_FILE.''); // obligé d'utiliser de simples quotes et de concatenation sinon php évalue le \n et la commande sed ne fonctionne pas
}
?>

<body>
<?php include('common-header.inc.php'); ?>

  <article class='main'>
    <!-- REPOS ACTIFS -->
    <article class="left">
        <?php include('common-repos-list.inc.php'); ?>
    </article>

    <article class="right">
        
        <div id="planCronStatus">
        <h5>PLANIFICATIONS</h5>
        <?php
          // on commence par vérifier si une tache cron est déjà présente ou non :
          $actualCrontab = shell_exec("crontab -l"); // on récupère le contenu actuel de la crontab de $WWW_USER
          if (strpos($actualCrontab, "--web --reminders") === false || strpos($actualCrontab, "#") !== false) { // si le contenu actuel ne contient pas de tâche cron de rappel ou bien si la tâche est commentée (#), alors on affiche un cercle rouge
            echo "<a href=\"#\"><img src=\"icons/red_circle.png\" class=\"cronStatus\" title=\"Il n'y a pas de tâche cron active pour l'envoi des rappels\"/></a>";
          } else {
            echo "<a href=\"#\"><img src=\"icons/green_circle.png\" class=\"cronStatus\" title=\"La tâche cron pour l'envoi des rappels est active\"/></a>"; // sinon on affiche un cercle vert
          } ?>
        </div>

        <form action="planifications.php" method="post">
        <table class="table-large">
        <?php
        $pattern = "/Plan-/i"; // dans le fichier de conf, les planifications commencent par plan:
        $PLANIFICATIONS = preg_grep($pattern, file($PLAN_CONF_FILE)); // on récupère toutes les planifications actives si il y en a
        if(!empty($PLANIFICATIONS)) { // On affiche les planifs actives si il y en a (càd si $PLANIFICATIONS est non vide)
          echo "<tr><td colspan=\"100%\">Planifications actives :</td></tr>";
          echo "<tr>";
          echo "<td class=\"td-auto\"><b>Date</b></td>";
          echo "<td class=\"td-auto\"><b>Heure</b></td>";
          echo "<td class=\"td-auto\"><b>Action</b></td>";
          echo "<td class=\"td-auto\"><b>Repo ou @groupe</b></td>";
          if ($OS_TYPE == "deb") { 
            echo "<td class=\"td-auto\"><b>Dist</b></td>";
            echo "<td class=\"td-auto\"><b>Section</b></td>";
          }
          echo "<td class=\"td-auto\"><b>Rappels</b></td>";
          echo "</tr>";

          foreach($PLANIFICATIONS as $plan) {
            $planName = str_replace(['[', ']'], '', $plan); // on retire les [crochets] autour du nom de la planif
            $planName = preg_replace('/\s+/', '', $planName); // on retire également les éventuelles tabs, espace ou fin de ligne
            
            $plan = shell_exec("sed -n -e '/\[${planName}\]/,/\[/p' $PLAN_CONF_FILE | sed '/^$/d' | grep -v '^\['"); // récupération des infos de la planif
            $plan = preg_split('/\s+/', trim($plan)); // on éclate le résultat dans un tableau car tout a été récupéré sur une ligne

            $planId = str_replace("Plan-", "", $planName); // pour récupérer l'id, il suffit de retirer "Plan-" de $planName
            $planDate = str_replace(['Date=', '"'], '', $plan[0]); // on récupère la date en retirant 'Date=""' de l'expression
            $planTime = str_replace(['Time=', '"'], '', $plan[1]); // on récupère l'heure en retirant 'Time=""' de l'expression
            $planAction = str_replace(['Action=', '"'], '', $plan[2]); // on récupère l'action en retirant 'Action=""' de l'expression
            if(substr($plan[3], 0, 4) == "Repo") { // Si la 3ème ligne commence par Repo= alors c'est un repo, sinon c'est un groupe
              $planRepoOrGroup = str_replace(['Repo=', '"'], '', $plan[3]); // on récupère le repo ou le groupe en retirant 'Repo=""' de l'expression
              if ($OS_TYPE == "deb") { // Si Debian, alors on récupère la dist et la section aussi
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
              if ($OS_TYPE == "deb") { // Si Debian alors on n'affiche pas de distrib ni de section (on affiche un tiret "-" à la place)
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
            if ($OS_TYPE == "rpm") {
              if ($planAction == "update") { // si planAction = 'update' alors il faut récupérer la valeur de GpgCheck et GpgResign
                $planGpgCheck = str_replace(['GpgCheck=', '"'], '', $plan[4]);
                $planGpgResign = str_replace(['GpgResign=', '"'], '', $plan[5]);
                $planReminder = str_replace(['Reminder=', '"'], '', $plan[6]); // on récupère les rappels en retirant 'Reminder=""' de l'expression
              } else {
                $planReminder = str_replace(['Reminder=', '"'], '', $plan[4]); // on récupère les rappels en retirant 'Reminder=""' de l'expression
              }
            }

            echo "<tr>";
            echo "<td class=\"td-auto\">${planDate}</td>";
            echo "<td class=\"td-auto\">${planTime}</td>";
            echo "<td class=\"td-auto\">${planAction}</td>";
            echo "<td class=\"td-auto\">${planRepoOrGroup}</td>";
            if ($OS_TYPE == "deb") {
              echo "<td class=\"td-auto\">${planDist}</td>";
              echo "<td class=\"td-auto\">${planSection}</td>";
            }
            echo "<td class=\"td-auto\">${planReminder}</td>";
            echo "<td class=\"td-auto\"><a href=\"?action=deletePlan&planId=${planId}\"><img src=\"icons/bin.png\" class=\"icon-lowopacity\" /></a></td>";
            echo "</tr>";
            // après la boucle, on va incrémenter de +1 le numéro d'ID. Ce sera l'ID attribué pour la prochaine planification ajoutée.
            $planId++;
          }
        }
        ?>
        </table>
        </form>
        <hr>
        <form action="planifications.php" method="post">
        <input type="hidden" name="addPlanId" value="<?php if (empty($planId)) { echo "1"; /* initialise la numéro de planification à 1 si il n'y en a pas */ } else { echo $planId; }?>" />
        <table class="table-large">
            <tr>
              <td colspan="100%">Ajouter une planification :</td>
            </tr>
            <?php
            echo "<tr>";
            echo "<td class=\"td-auto\">Date</td>";
            echo "<td class=\"td-auto\" colspan=\"100%\"><input type=\"date\" name=\"addPlanDate\" autocomplete=\"off\" /></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td class=\"td-auto\">Heure</td>";
            echo "<td class=\"td-auto\" colspan=\"100%\"><input type=\"time\" name=\"addPlanTime\" autocomplete=\"off\" /></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td class=\"td-auto\">Action</td>";
            echo "<td class=\"td-auto\" colspan=\"100%\">";
            echo "<select name=\"addPlanAction\" id=\"planSelect\">";   //toto
            foreach ($REPO_ENVS as $env) {
              // on récupère l'env qui suit l'env actuel :
              $nextEnv = exec("grep -A1 '$env' $ENV_CONF_FILE | grep -v '$env'");
              if (!empty($nextEnv)) {
                echo "<option value='${env}->${nextEnv}'>Changement d'env : ${env} -> ${nextEnv}</option>";
              }
            }
            echo "<option value=\"update\" id=\"updateRepoSelect\">Mise à jour de l'environnement ${REPO_DEFAULT_ENV}</option>";
            echo "</select>";
            echo "</td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td class=\"td-auto\">Repo</td>";
            echo "<td class=\"td-auto\"><input type=\"text\" id=\"inputRepo\" name=\"addPlanRepo\" autocomplete=\"off\" /></td>";
            echo "<td class=\"td-auto\">ou Groupe</td>";
            echo "<td class=\"td-auto\"><input type=\"text\" name=\"addPlanGroup\" autocomplete=\"off\" placeholder=\"@\" /></td>";
            echo "</tr>";
            if ($OS_TYPE == "deb") { 
              echo "<tr class=\"tr-hide\" id=\"hiddenDebInput\">";
              echo "<td class=\"td-auto\">Dist</td>";
              echo "<td class=\"td-auto\"><input type=\"text\" name=\"addPlanDist\" autocomplete=\"off\" /></td>";
              echo "</tr>";
              echo "<tr class=\"tr-hide\">";
              echo "<td class=\"td-auto\">Section</td>";
              echo "<td class=\"td-auto\"><input type=\"text\" name=\"addPlanSection\" autocomplete=\"off\" /></td>";
              echo "</tr>";
            }
            echo "<tr class=\"tr-hide\">";
            echo "<td>GPG check</td>";
            echo "<td colspan=\"2\">";
            echo "<input type=\"radio\" id=\"addPlanGpgCheck_yes\" name=\"addPlanGpgCheck\" value=\"yes\" checked=\"yes\">";
            echo "<label for=\"addPlanGpgCheck_yes\">Yes</label>";
            echo "<input type=\"radio\" id=\"addPlanGpgCheck_no\" name=\"addPlanGpgCheck\" value=\"no\">";
            echo "<label for=\"addPlanGpgCheck_no\">No</label>";
            echo "</td>";
            echo "</tr>";
            if ($OS_TYPE == "rpm") { // si rpm, alors on propose de resigner les paquets ou non
              echo "<tr class=\"tr-hide\">";
              echo "<td>Re-signer avec GPG</td>";
              echo "<td colspan=\"2\">";
              if ( $GPG_SIGN_PACKAGES == "yes" ) {
                echo "<input type=\"radio\" id=\"addPlanGpgResign_yes\" name=\"addPlanGpgResign\" value=\"yes\" checked=\"yes\">";
                echo "<label for=\"addPlanGpgResign_yes\">Yes</label>";
                echo "<input type=\"radio\" id=\"addPlanGpgResign_no\" name=\"addPlanGpgResign\" value=\"no\">";
                echo "<label for=\"addPlanGpgResign_no\">No</label>";
              } else {
                echo "<input type=\"radio\" id=\"addPlanGpgResign_yes\" name=\"addPlanGpgResign\" value=\"yes\">";
                echo "<label for=\"addPlanGpgResign_yes\">Yes</label>";
                echo "<input type=\"radio\" id=\"addPlanGpgResign_no\" name=\"addPlanGpgResign\" value=\"no\" checked=\"yes\">";
                echo "<label for=\"addPlanGpgResign_no\">No</label>";
              } 
              echo "</td>";
              echo "</tr>";
            }
            echo "<tr>";
            echo "<td class=\"td-auto\">Rappels</td>";
            echo "<td class=\"td-auto\" colspan=\"100%\"><input type=\"text\" name=\"addPlanReminder\" autocomplete=\"off\" /></td>";
            echo "</tr>";?>
            <tr>
                <td colspan="100%"><button type="submit" class="button-submit-large-blue">Ajouter</button></td>
            </tr>
        </table>
        </form>
    </article>
  </article>
  
  <!-- divs cachées de base -->
  <!-- div des groupes de repos -->
  <?php include('common-groupslist.inc.php'); ?>

  <!-- div des hotes et fichers de repos -->
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
    $('#hiddenDebInput').hide();
  } else {
    // Otherwise show it
    $('#hiddenDebInput').show();
  }
}).keyup(); // Trigger the keyup event, thus running the handler on page load

// Afficher des boutons radio si l'option du select sélectionnée est '#updateRepoSelect' afin de choisir si on souhaite activer gpg check et resigner les paquets
$(function() {
  $("#planSelect").change(function() {
    if ($("#updateRepoSelect").is(":selected")) {
      $(".tr-hide").show();
    } else {
      $(".tr-hide").hide();
    }
  }).trigger('change');
});
</script>
</html>