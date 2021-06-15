<!DOCTYPE html>
<html>
<?php include('common-head.inc.php'); ?>

<?php
/**
 *  Import des variables et fonctions nécessaires
 */
require_once('functions/load_common_variables.php');
require_once('functions/load_display_variables.php');
require_once('functions/common-functions.php');
require_once('common.php');
require_once('class/Repo.php');
require_once('class/Group.php');
require_once('class/Planification.php');
$repo  = new Repo();
$group = new Group();

if ($DEBUG_MODE == "enabled") { echo 'Mode debug activé : '; echo '<br>POST '; print_r($_POST); echo '<br>GET '; print_r($_GET); }

// Cas où on ajoute une planification
if (!empty($_POST['addPlanDate']) AND !empty($_POST['addPlanTime']) AND !empty($_POST['addPlanAction'])) {
    $error = 0; // un peu de gestion d'erreur
    $planDate = validateData($_POST['addPlanDate']);
    $planTime = validateData($_POST['addPlanTime']);
    // on reformate l'heure afin de remplacer ':' par un 'h' (c'est plus parlant)
    //$planTime = str_replace(":", "h", $planTime);
    // on récupère l'action à exécuter
    $planAction = $_POST['addPlanAction']; // ne pas validateData() car ça transforme '->' en caractères échappés
    // et les rappels si il y en a
    if(!empty($_POST['addPlanReminder'])) {
        $planReminder = '';
        // On sépare chaque jour de rappel par une virgule
        foreach ($_POST['addPlanReminder'] as $selectedOption) {
            $selectedOption = validateData($selectedOption);
            $planReminder = "${planReminder}${selectedOption},";
        }
        // Suppression de la dernière virgule
        $planReminder = rtrim($planReminder, ",");
    } 

    // si l'action sélectionnée dans le formulaire est 'update', alors on récupère les valeurs des boutons radio Gpg Check et Gpg resign
    if ($planAction === "update") {
        if (empty($_POST['addPlanGpgCheck'])) { // Normalement ne peut pas être vide car un des deux boutons radio est forcément sélectionné, mais bon...
            $error++;
            printAlert("Vous devez indiquer une valeur pour GPG Check");
        } else {
            $planGpgCheck = validateData($_POST['addPlanGpgCheck']);
        }
        // On récupère la valeur du bouton radio gpg resign
        if (empty($_POST['addPlanGpgResign'])) { // Normalement ne peut pas être vide car un des deux boutons radio est forcément sélectionné, mais bon...
            $error++;
            printAlert("Vous devez indiquer une valeur pour GPG Resign");
        } else {
            $planGpgResign = validateData($_POST['addPlanGpgResign']);
        }

        // On instancie un nouvel objet plan avec les infos qu'on a déjà
        $planToAdd = new Planification(compact('planDate', 'planTime', 'planAction', 'planGpgCheck', 'planGpgResign', 'planReminder'));
    } else {
        $planToAdd = new Planification(compact('planDate', 'planTime', 'planAction', 'planReminder'));
    }

    // On récupère soit un seul repo, soit un groupe, selon ce qui a été envoyé via le formulaire
    // Cas où c'est un repo
    if(!empty($_POST['addPlanRepo'])) {

        if ($OS_FAMILY == "Redhat") {
            $repoName = validateData($_POST['addPlanRepo']);
        }

        if ($OS_FAMILY == "Debian") {
            $addPlanRepoExplode = explode('|', validateData($_POST['addPlanRepo']));
            $repoName = $addPlanRepoExplode[0];
            $repoDist = $addPlanRepoExplode[1];
            $repoSection = $addPlanRepoExplode[2];
        }

        // Instancie un nouvel objet repo dans l'objet plan
        if ($OS_FAMILY == "Redhat") {
            $planToAdd->repo = new Repo(compact('repoName'));
        }
        if ($OS_FAMILY == "Debian") {
            $planToAdd->repo = new Repo(compact('repoName', 'repoDist', 'repoSection'));
        }
    }

    // Cas où c'est un groupe
    if(!empty($_POST['addPlanGroup'])) {
        $groupName = validateData($_POST['addPlanGroup']);
        $planToAdd->group = new Group(compact('groupName'));
    }

    // si les deux on été renseignés, on affiche une erreur
    if (!empty($planToAdd->repo->name) AND !empty($planToAdd->group->name)) {
        printAlert("Il faut renseigner soit un repo, soit un groupe mais pas les deux");
        $error++;
    }

    // on vérifie que le repo ou la section indiqué existe dans la liste des repos
    if (!empty($planToAdd->repo->name)) {
        if ($OS_FAMILY == "Redhat") {
            if ($planToAdd->repo->exists($planToAdd->repo->name) === false) {
                $error++;
                printAlert("Le repo {$planToAdd->repo->name} n'existe pas");
            }
        }
        if ($OS_FAMILY == "Debian") {
            if ($planToAdd->repo->section_exists($planToAdd->repo->name, $planToAdd->repo->dist, $planToAdd->repo->section) === false) {
                $error++;
                printAlert("La section {$planToAdd->repo->section} du repo {$planToAdd->repo->name} (distribution {$planToAdd->repo->dist}) n'existe pas");
            }
        }
    }

    // on vérifie que le groupe indiqué existe dans le fichier de groupes
    if (!empty($planToAdd->group->name)) {
        if ($planToAdd->group->exists() === false) {
            printAlert("Le groupe {$planToAdd->group->name} n'existe pas");
            $error++;
        }
    }

    // On traite uniquement si il n'y a pas eu d'erreur précédemment
    if ($error === 0) {
        $planToAdd->new();
    }
}

// Cas où on souhaite supprimer une planification
if (!empty($_GET['action']) AND (validateData($_GET['action']) == "deletePlan") AND !empty($_GET['planId'])) {
    $planId = validateData($_GET['planId']);

    $planToDelete = new Planification(compact('planId'));
    $planToDelete->delete();
}
?>

<body>
<?php include('common-header.inc.php'); ?>

    <!-- section 'conteneur' principal englobant toutes les sections de droite -->
    <!-- On charge la section de droite avant celle de gauche car celle-ci peut mettre plus de temps à charger (si bcp de repos) -->
    <section class="mainSectionRight">
      <!-- div cachée, affichée par le bouton "Gérer les groupes" -->
      <!-- GERER LES GROUPES -->
      <section class="right" id="groupsDiv">
        <?php include('common-groupslist.inc.php'); ?>
      </section>

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
        /**
         *  1. Récupération de la liste des planifications en liste d'attente ou en cours d'exécution
         */
        $plans = new Planification();
        $planList = $plans->listQueue();

        /**
         *  2. Affichage des planifications si il y en a
         */
        if(!empty($planList)) {
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

            foreach($planList as $plan) {
                $planId = $plan['Plan_id'];
                $planDate = $plan['Plan_date'];
                $planTime = $plan['Plan_time'];
                $planAction = $plan['Plan_action'];
                $planGroup = $plan['Plan_group'];
                $planRepo = $plan['Plan_repo'];
                if ($OS_FAMILY == "Debian") {
                    $planDist = $plan['Plan_dist'];
                    $planSection = $plan['Plan_section'];
                }
                $planGpgCheck = $plan['Plan_gpgCheck'];
                $planGpgResign = $plan['Plan_gpgResign'];
                $planReminder = $plan['Plan_reminder'];
                $planStatus = $plan['Plan_status'];
                $planLogfile = $plan['Plan_logfile'];

                if (!empty($planGroup) AND empty($planRepo)) {
                    $planRepoOrGroup = $planGroup;
                }
                if (empty($planGroup) AND !empty($planRepo)) {
                    $planRepoOrGroup = $planRepo;
                }
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
                    if (!empty($planDist)) {
                        echo "<td class=\"td-auto\">${planDist}</td>";
                    } else {
                        echo '<td class="td-auto">-</td>';
                    }
                    if (!empty($planSection)) {
                        echo "<td class=\"td-auto\">${planSection}</td>";
                    } else {
                        echo '<td class="td-auto">-</td>';
                    }
                }

                echo '<td class="td-auto">';
                echo "<img id=\"planDetailsToggle${i}\" class=\"icon-lowopacity\" title=\"Afficher les détails\" src=\"icons/search.png\" />";
                if ($planStatus == "queued") {
                    echo "<img class=\"planDeleteToggle${i} icon-lowopacity\" title=\"Supprimer la planification\" src=\"icons/bin.png\" />";
                }
                if ($planStatus == "running") {
                    //echo "<a href=\"run.php?logfile=${planLogfile}\"><img src=\"images/loading.gif\" class=\"icon\" title=\"en cours d'exécution\" /></a>";
                    echo "<img src=\"images/loading.gif\" class=\"icon\" title=\"en cours d'exécution\" />";
                }
                echo '</td>';
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
        <p><b><img src="icons/plus.png" class="icon" />Ajouter une planification</b></p>
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
            echo '<option value="">Sélectionnez un repo...</option>';

            /**
             *  Récupération de la liste des repos qui possèdent un environnement $DEFAULT_ENV
             */
            $reposList = $repo->listAll_distinct_byEnv($DEFAULT_ENV);
            if (!empty($reposList)) {
                foreach($reposList as $repo) {
                    $repoName = $repo['Name'];
                    if ($OS_FAMILY == "Debian") {
                        $repoDist = $repo['Dist'];
                        $repoSection = $repo['Section'];
                    }

                    // On génère une <option> pour chaque repo
                    if ($OS_FAMILY == "Redhat") {
                        echo "<option value=\"${repoName}\">${repoName}</option>";
                    }
                    if ($OS_FAMILY == "Debian") {
                        echo "<option value=\"${repoName}|${repoDist}|${repoSection}\">${repoName} - ${repoDist} - ${repoSection}</option>";
                    }
                }
            }
            echo '</select>';
            echo '</td>';
            echo '<td class="td-fit">ou Groupe</td>';
            echo '<td>';
            echo '<select name="addPlanGroup">';
            echo '<option value="">Sélectionnez un groupe...</option>';
            $groupsList = $group->listAll();
            if (!empty($groupsList)) {
                foreach($groupsList as $groupName) {
                    echo "<option value=\"${groupName}\">${groupName}</option>";
                }
            }
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
            echo '<td>Re-signer avec GPG</td>';
            echo '<td colspan="2">';
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
            echo "</tr>";
            ?>
            <tr>
              <td colspan="100%"><button type="submit" class="button-submit-large-blue">Ajouter</button></td>
            </tr>
        </table>
        </form>

        <?php
        /**
         *  Affichage des planifications terminées si il y en a
         */
        $plansDone = $plans->listDone();
        if (!empty($plansDone)) {
            echo '<p><img src="icons/history.png" class="icon" /><b>Historique des planifications</b></p>';
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
            foreach($plansDone as $plan) {
                $planId = $plan['Plan_id'];
                $planDate = $plan['Plan_date'];
                $planTime = $plan['Plan_time'];
                $planAction = $plan['Plan_action'];
                $planGroup = $plan['Plan_group'];
                $planRepo = $plan['Plan_repo'];
                if ($OS_FAMILY == "Debian") {
                    $planDist = $plan['Plan_dist'];
                    $planSection = $plan['Plan_section'];
                }
                $planGpgCheck = $plan['Plan_gpgCheck'];
                $planGpgResign = $plan['Plan_gpgResign'];
                $planReminder = $plan['Plan_reminder'];
                $planStatus = $plan['Plan_status'];
                $planError = $plan['Plan_error'];
                $planLogfile = $plan['Plan_logfile'];

                if (empty($planReminder)) {
                    $planReminder = 'Aucun'; 
                } else {
                    $planReminder = "$planReminder (jours avant)";
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
                if (empty($planStatus)) { $planStatus = '?'; }

                echo '<tr>';
                // Affichage de la date
                echo '<td class="td-fit">';
                echo $planDate;
                echo '</td>';

                // Affichage de l'heure
                echo '<td class="td-fit">';
                echo $planTime;
                echo '</td>';

                // Affichage de l'action
                echo '<td class="td-fit">';
                echo $planAction;
                echo '</td>';

                // Affichage du repo ou du groupe
                echo '<td class="td-fit">';
                if (!empty($planGroup)) { echo $planGroup; }
                if (!empty($planRepo)) { echo $planRepo; }
                echo '</td>';

                // Dans le cas de Debian, on affiche la distribution et la section (ou des tirets '-' si la variable précédente était un groupe)
                if ($OS_FAMILY == "Debian") {
                    // Affichage de la distribution
                    echo '<td class="td-fit">';
                    echo $planDist;
                    echo '</td>';
                    // Affichage de la section
                    echo '<td class="td-fit">';
                    echo $planSection;
                    echo '</td>';
                }

                // Affichage du status
                echo '<td class="td-fit">';
                if ($planStatus === "error") {
                    echo "<span id=\"planErrorToggle${i}\" class=\"redtext\">Error</span>";
                } 
                elseif ($planStatus === "done") {
                    echo '<span class="greentext">OK</span>';
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
                if ($planStatus === "error") {
                    echo "$planError<br>";
                }
                if ($planAction == "update") {
                    echo "<b>GPG Check : </b>$planGpgCheck<br>";
                    echo "<b>GPG Resign : </b>$planGpgResign<br>";
                }
                echo "<b>Rappels : </b>$planReminder<br>";
                if (!empty($planLogfile)) {
                    echo "<b><a href=\"run.php?logfile=${planLogfile}\">Log</a></b>";
                }
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