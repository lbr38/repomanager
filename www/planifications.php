<!DOCTYPE html>
<html>
<?php include('includes/head.inc.php'); ?>

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

/**
 *  Cas où on ajoute une planification
 */
if (!empty($_POST['action']) AND validateData($_POST['action']) === "addnewplan" AND !empty($_POST['addPlanDate']) AND !empty($_POST['addPlanTime']) AND !empty($_POST['addPlanAction'])) {
    $error = 0; // un peu de gestion d'erreur
    /**
     *  On récupère les infos de la planification
     */
    $planDate = validateData($_POST['addPlanDate']);
    $planTime = validateData($_POST['addPlanTime']);
    $planAction = $_POST['addPlanAction']; // ne pas validateData() car ça transforme '->' en caractères échappés
    /**
     *  et les rappels si il y en a
     */
    if(!empty($_POST['addPlanReminder'])) {
        $planReminder = '';
        /**
         *  On sépare chaque jour de rappel par une virgule
         */
        foreach ($_POST['addPlanReminder'] as $selectedOption) {
            $selectedOption = validateData($selectedOption);
            $planReminder = "${planReminder}${selectedOption},";
        }
        /**
         *  Suppression de la dernière virgule
         */
        $planReminder = rtrim($planReminder, ",");
    } 

    /**
     *  Si l'action sélectionnée dans le formulaire est 'update', alors on récupère les valeurs des boutons on/off GpgCheck et Gpgresign
     */
    if ($planAction === "update") {
        if (!empty($_POST['addPlanGpgCheck'])) {
            $planGpgCheck = 'yes';
        } else {
            $planGpgCheck = 'no';
        }

        if (!empty($_POST['addPlanGpgResign'])) {
            $planGpgResign = 'yes';
        } else {
            $planGpgResign = 'no';
        }

        /**
         *  On instancie un nouvel objet plan avec les infos qu'on a déjà
         */
        $planToAdd = new Planification(compact('planDate', 'planTime', 'planAction', 'planGpgCheck', 'planGpgResign', 'planReminder'));
    } else {
        $planToAdd = new Planification(compact('planDate', 'planTime', 'planAction', 'planReminder'));
    }

    /**
     *  On récupère soit un seul repo, soit un groupe, selon ce qui a été envoyé via le formulaire
     */

    /**
     *  Cas où c'est un repo seul
     */
    if(!empty($_POST['addPlanRepoId'])) {
        $repoId = validateData($_POST['addPlanRepoId']);

        /**
         *  Instancie un nouvel objet repo dans l'objet plan
         */
        $planToAdd->repo = new Repo(compact('repoId'));

        /**
         *  On vérifie que l'ID du repo ou de la section renseigné existe en BDD
         */
        if ($planToAdd->repo->existsId() === false) {
            $error++;
            printAlert("Le repo renseigné n'existe pas");
        }
    }

    /**
     *  Cas où c'est un groupe
     */
    if(!empty($_POST['addPlanGroupId'])) {
        $groupId = validateData($_POST['addPlanGroupId']);
        $planToAdd->group = new Group(compact('groupId'));

        /**
         *  On vérifie que l'ID du groupe renseigné existe en BDD
         */
        if (!empty($planToAdd->group->id)) {
            if ($planToAdd->group->existsId() === false) {
                printAlert("Le groupe renseigné n'existe pas");
                $error++;
            }
        }
    }

    /**
     *  Si les deux on été renseignés (repo et groupe), alors on affiche une erreur
     */
    if (!empty($planToAdd->repo->id) AND !empty($planToAdd->group->id)) {
        printAlert("Il faut renseigner soit un repo, soit un groupe mais pas les deux");
        $error++;
    }

    /**
     *  On traite uniquement si il n'y a pas eu d'erreur précédemment
     */
    if ($error === 0) {
        $planToAdd->new();
    }
}

/**
 *  Cas où on souhaite supprimer une planification
 */
if (!empty($_GET['action']) AND (validateData($_GET['action']) == "deletePlan") AND !empty($_GET['planId'])) {
    $planId = validateData($_GET['planId']);
    $planToDelete = new Planification(compact('planId'));
    $planToDelete->delete();
}
?>

<body>
<?php include('includes/header.inc.php'); ?>

<article>
    <!-- section 'conteneur' principal englobant toutes les sections de droite -->
    <!-- On charge la section de droite avant celle de gauche car celle-ci peut mettre plus de temps à charger (si bcp de repos) -->
    <section class="mainSectionRight">

        <!-- div cachée, affichée par le bouton "Gérer les groupes" -->
        <!-- GERER LES GROUPES -->
        <section class="right" id="groupsDiv">
            <?php include('includes/manage-groups.inc.php'); ?>
        </section>

        <!-- div cachée, affichée par le bouton "Gérer les repos sources" -->
        <!-- GERER LES SOURCES -->
        <section class="right" id="sourcesDiv">
            <?php include('includes/manage-sources.inc.php'); ?>
        </section>

        <section class="right">
            <div class="div-flex">
                <h3>PLANIFICATIONS</h3>
                <div id="planCronStatus">
                <?php
                    // on commence par vérifier si une tache cron est déjà présente ou non :
                    if ($CRON_PLAN_REMINDERS_ENABLED == "yes") {
                        $cronStatus = checkCronReminder();
                        if ($cronStatus == 'On') {
                            echo '<span class="pointer" title="La tâche cron pour l\'envoi des rappels est active">Rappels <img src="icons/greencircle.png" /></span>';
                        }
                        if ($cronStatus == 'Off') {
                            echo '<span class="pointer" title="Il n\'y a aucune tâche cron active pour l\'envoi des rappels">Rappels <img src="icons/redcircle.png" /></span>';
                        }
                    } else {
                        echo '<span class="pointer" title="Les rappels de planifications sont désactivés">Rappels <img src="icons/redcircle.png" /></span>';
                    }
                    ?>
                </div>
            </div>

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

                foreach($planList as $plan) {
                    $planId     = $plan['Id'];
                    $planDate   = DateTime::createFromFormat('Y-m-d', $plan['Date'])->format('d-m-Y');
                    $planTime   = $plan['Time'];
                    $planAction = $plan['Action'];
                    $planGroupId  = $plan['Id_group'];
                    $planRepoId   = $plan['Id_repo'];
                    $planGpgCheck  = $plan['Gpgcheck'];
                    $planGpgResign = $plan['Gpgresign'];
                    $planReminder  = $plan['Reminder'];
                    $planStatus    = $plan['Status'];
                    $planLogfile   = $plan['Logfile'];

                    /**
                     *  On définit si la planification traite un repo seul ou un groupe en fonction de si les variables sont vides ou non
                     */
                    if (!empty($planGroupId) AND empty($planRepoId)) {
                        $planRepoIdOrGroup = $planGroupId;
                    }
                    if (empty($planGroupId) AND !empty($planRepoId)) {
                        $planRepoIdOrGroup = $planRepoId;
                    }

                    if (empty($planReminder)) {
                        $planReminder = 'Aucun'; 
                    } else {
                        $planReminder = "$planReminder (jours avant)";
                    }

                    echo '<div class="header-container">';
                        echo '<div class="header-blue">';
                            echo '<table>';
                            echo '<tr>';
                            echo '<td class="td-fit">';
                            if ($planAction == "update") {
                                echo "<img class=\"icon\" src=\"icons/update.png\" title=\"$planAction\" />";
                            } else {
                                echo "<img class=\"icon\" src=\"icons/link.png\" title=\"$planAction\" />";
                            }
                            echo '</td>';
                            echo "<td class=\"td-small\">Prévue le <b>$planDate</b> à <b>$planTime</b></td>";
                            /**
                             *  Affichage du repo ou du groupe
                             *  On possède l'ID du repo/groupe, il faut alors récupérer le nom en BDD
                             */
                            echo '<td>';
                            if (!empty($planGroupId)) {
                                $group = new Group(array('groupId' => $planGroupId));
                                $group->db_getName();
                                $planGroup = $group->name;
                                echo "Groupe $planGroup";
                                unset($group);
                            }
                            if (!empty($planRepoId)) {
                                $repo  = new Repo(array('repoId' => $planRepoId));
                                $repo->db_getAllById();     // Récupération de toutes les infos concernant le repo
                                $planName = $repo->name;
                                echo $planName;

                                /**
                                 *  Dans le cas de Debian, on affiche également la distribution et la section
                                 */
                                if ($OS_FAMILY == "Debian") {
                                    $planDist = $repo->dist;
                                    $planSection = $repo->section;
                                    echo " - $planDist";
                                    echo " - $planSection";
                                }
                                unset($repo);
                            }
                            echo '</td>';

                            /**
                             *  Affichage de l'icone 'loupe' pour afficher les détails de la planification
                             */
                            echo '<td class="td-fit">';
                            echo "<img id=\"planDetailsToggle${i}\" class=\"icon-lowopacity\" title=\"Afficher les détails\" src=\"icons/search.png\" />";
                            if ($planStatus == "queued") {
                                echo "<img class=\"planDeleteToggle${i} icon-lowopacity\" title=\"Supprimer la planification\" src=\"icons/bin.png\" />";
                            }
                            if ($planStatus == "running") {
                                echo 'en cours <img src="images/loading.gif" class="icon" title="en cours d\'exécution" />';
                            }
                            echo '</td>';
                            deleteConfirm("Êtes-vous sûr de vouloir supprimer la planification du <b>$planDate</b> à <b>$planTime</b>", "?action=deletePlan&planId=${planId}", "planDeleteDiv${i}", "planDeleteToggle${i}");
                            echo '</tr>';
                            echo '</table>';
                        echo '</div>';

                        /**
                         *  Div caché contenant les détails de la planification
                         */
                        echo "<div id=\"planDetailsDiv${i}\" class=\"hide detailsDiv\">";
                            echo '<table>';
                                echo '<tr>';
                                echo '<td colspan="100%">';
                                if ($planAction == "update") {
                                    if (!empty($planGroup)) {
                                        if ($OS_FAMILY == "Redhat") { echo "<p>Mise à jour des repos ".envtag($DEFAULT_ENV)." du groupe <b>$planGroup</b></p>"; }
                                        if ($OS_FAMILY == "Debian") { echo "<p>Mise à jour des sections de repos ".envtag($DEFAULT_ENV)." du groupe <b>$planGroup</b></p>"; }
                                    } else {
                                        if ($OS_FAMILY == "Redhat") { echo "<p>Mise à jour du repo <b>$planName</b> ".envtag($DEFAULT_ENV)."</p>"; }
                                        if ($OS_FAMILY == "Debian") { echo "<p>Mise à jour du repo <b>$planName</b>, distribution <b>$planDist</b>, section <b>$planSection</b> ".envtag($DEFAULT_ENV)."</p>"; }
                                    }
                                    echo "<tr><td><b>GPG Check</b></td><td>$planGpgCheck</td></tr>";
                                    echo "<tr><td><b>GPG Resign</b></td><td>$planGpgResign</td></tr>";
                                } else {
                                    $envs = explode('->', $planAction);
                                    $envTarget = $envs[0];
                                    $envSource = $envs[1];
                                    if (!empty($planGroup)) {
                                        if ($OS_FAMILY == "Redhat") { echo "<p>Pointage de l'environnement ".envtag($envSource)." vers ".envtag($envTarget)." pour les repos du groupe <b>$planGroup</b></p>"; }
                                        if ($OS_FAMILY == "Debian") { echo "<p>Pointage de l'environnement ".envtag($envSource)." vers ".envtag($envTarget)." pour les sections de repos du groupe <b>$planGroup</b></p>"; }
                                    } else {
                                        if ($OS_FAMILY == "Redhat") { echo "<p>Pointage de l'environnement ".envtag($envSource)." vers ".envtag($envTarget)." pour le repo <b>$planName</b></p>"; }
                                        if ($OS_FAMILY == "Debian") { echo "<p>Pointage de l'environnement ".envtag($envSource)." vers ".envtag($envTarget)." pour le repo <b>$planName</b>, distribution <b>$planDist</b>, section <b>$planSection</b></p>"; }
                                    }
                                }
                                echo '</td>';
                                echo '</tr>';
                                echo "<tr><td><b>Rappels</b></td><td>$planReminder</td></tr>";
                                if (!empty($planLogfile)) {
                                    echo "<tr><td><b>Log</b></td><td><a href=\"run.php?logfile=${planLogfile}\"><b>Voir</b></a></></td></tr>";
                                }
                            echo '</table>';
                        echo '</div>';
                    echo '</div>';

                    // Script JS pour afficher les détails cachés
                    echo "<script>
                    $(function() {
                        $('#planDetailsToggle${i}').click(function() {
                            $('#planDetailsDiv${i}').toggle();
                        });
                    });
                    </script>";
                    ++$i;
                }
                
                echo '<br><hr><br>';
            } ?>

            <form action="planifications.php" method="post" autocomplete="off">
                <input type="hidden" name="action" value="addnewplan" />
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
                    echo '<select name="addPlanRepoId">';
                    echo '<option value="">Sélectionnez un repo...</option>';

                    /**
                     *  Récupération de la liste des repos qui possèdent un environnement $DEFAULT_ENV
                     */
                    $repo = new Repo();
                    $reposList = $repo->listAll_distinct_byEnv($DEFAULT_ENV);
                    if (!empty($reposList)) {
                        foreach($reposList as $myrepo) {
                            $repoId = $myrepo['Id'];
                            $repoName = $myrepo['Name'];
                            if ($OS_FAMILY == "Debian") {
                                $repoDist = $myrepo['Dist'];
                                $repoSection = $myrepo['Section'];
                            }

                            /**
                             *  On génère une <option> pour chaque repo
                             */
                            if ($OS_FAMILY == "Redhat") {
                                echo "<option value=\"${repoId}\">${repoName}</option>";
                            }
                            if ($OS_FAMILY == "Debian") {
                                echo "<option value=\"${repoId}\">${repoName} - ${repoDist} - ${repoSection}</option>";
                            }
                        }
                    }
                    echo '</select>';
                    echo '</td>';
                    echo '<td class="td-fit">ou Groupe</td>';
                    echo '<td>';
                    echo '<select name="addPlanGroupId">';
                    echo '<option value="">Sélectionnez un groupe...</option>';
                    $group = new Group();
                    $groupsList = $group->listAll();
                    if (!empty($groupsList)) {
                        foreach($groupsList as $group) {
                            $groupId = $group['Id'];
                            $groupName = $group['Name'];
                            echo "<option value=\"${groupId}\">${groupName}</option>";
                        }
                    }
                    echo '</select>';
                    echo '</td>';
                    echo '</tr>';
                    echo '<tr class="hide hiddenGpgInput">';
                    echo '<td class="td-fit">GPG check</td>';
                    echo '<td>';
                    echo '<label class="onoff-switch-label">';
                    echo '<input name="addPlanGpgCheck" type="checkbox" class="onoff-switch-input" value="yes" checked />';
                    echo '<span class="onoff-switch-slider"></span>';
                    echo '</label>';
                    echo '</td>';
                    echo '</tr>';      
                    echo '<tr class="hide hiddenGpgInput">';
                    echo '<td class="td-fit">Signer avec GPG</td>';
                    echo '<td>';
                    echo '<label class="onoff-switch-label">';
                    echo '<input name="addPlanGpgResign" type="checkbox" class="onoff-switch-input" value="yes"'; if ($GPG_SIGN_PACKAGES == "yes") { echo 'checked'; } echo ' />';
                    echo '<span class="onoff-switch-slider"></span>';
                    echo '</label>';
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
            <script>
            /**
            *   Afficher des boutons radio si l'option du select sélectionnée est '#updateRepoSelect' afin de choisir si on souhaite activer gpg check et resigner les paquets
            */
            $(function() {
            $("#planSelect").change(function() {
                if ($("#updateRepoSelect").is(":selected")) {
                    $(".hiddenGpgInput").show();
                } else {
                    $(".hiddenGpgInput").hide();
                }
            }).trigger('change');
            });

            // Script Select2 pour transformer un select multiple en liste déroulante
            $('#planReminderSelect').select2({
                closeOnSelect: false,
                placeholder: 'Ajouter un rappel...'
            });
            </script>
        </section>

        <?php
        /**
         *  Affichage des planifications terminées si il y en a
         */
        $plansDone = $plans->listDone();
        if (!empty($plansDone)) {
            echo '<section class="right">';
                echo '<p><img src="icons/history.png" class="icon" /><b>Historique des planifications</b></p>';
                $i = 0; // Initialisation d'une variable qui servira pour chaque div d'erreur de planification caché, et affiché par js
                foreach($plansDone as $plan) {
                    $planId        = $plan['Id'];
                    $planDate      = DateTime::createFromFormat('Y-m-d', $plan['Date'])->format('d-m-Y');
                    $planTime      = $plan['Time'];
                    $planAction    = $plan['Action'];
                    $planGroupId   = $plan['Id_group'];
                    $planRepoId    = $plan['Id_repo'];
                    $planGpgCheck  = $plan['Gpgcheck'];
                    $planGpgResign = $plan['Gpgresign'];
                    $planReminder  = $plan['Reminder'];
                    $planStatus    = $plan['Status'];
                    $planError     = $plan['Error'];
                    $planLogfile   = $plan['Logfile'];
                    if (empty($planReminder)) {
                        $planReminder = 'Aucun'; 
                    } else {
                        $planReminder = "$planReminder (jours avant)";
                    }
                    if (empty($planDate))   { $planDate = '?'; }
                    if (empty($planTime))   { $planTime = '?'; }
                    if (empty($planAction)) { $planAction = '?'; }
                    if (empty($planGpgCheck))  { $planGpgCheck = '?'; }
                    if (empty($planGpgResign)) { $planGpgResign = '?'; }
                    if (empty($planReminder))  { $planReminder = '?'; }
                    if (empty($planStatus))    { $planStatus = '?'; }

                    echo '<div class="header-container">';
                        echo '<div class="header-blue">';
                            echo '<table>';
                            echo '<tr>';
                            echo '<td class="td-fit">';
                            if ($planAction == "update") {
                                echo "<img class=\"icon\" src=\"icons/update.png\" title=\"$planAction\" />";
                            } else {
                                echo "<img class=\"icon\" src=\"icons/link.png\" title=\"$planAction\" />";
                            }
                            echo '</td>';
                            echo "<td class=\"td-small\">Le <b>$planDate</b> à <b>$planTime</b></td>";

                            /**
                             *  Affichage du repo ou du groupe
                             */
                            echo '<td>';
                            if (!empty($planGroupId)) {
                                $group = new Group(array('groupId' => $planGroupId));
                                $group->db_getName();
                                $planGroup = $group->name;
                                echo "Groupe $planGroup";
                                unset($group);
                            }
                            if (!empty($planRepoId)) {
                                $repo = new Repo(array('repoId' => $planRepoId));
                                $repo->db_getAllById();     // Récupération de toutes les infos concernant le repo
                                $planName = $repo->name;
                                echo $planName;

                                /**
                                 *  Dans le cas de Debian, on affiche également la distribution et la section
                                 */
                                if ($OS_FAMILY == "Debian") {
                                    $planDist = $repo->dist;
                                    $planSection = $repo->section;
                                    echo " - $planDist";
                                    echo " - $planSection";
                                }
                                unset($repo);
                            }
                            echo '</td>';
                            echo '<td class="td-fit">';
                            /**
                             *  Affichage d'une pastille verte ou rouge en fonction du status de la planification
                             */
                            if ($planStatus == "done") {
                                echo '<img class="icon-small" src="icons/greencircle.png" title="Planification terminée" />';
                            } elseif ($planStatus == "error") {
                                echo '<img class="icon-small" src="icons/redcircle.png" title="Planification en erreur" />';
                            } elseif ($planStatus == "stopped") {
                                echo '<img class="icon-small" src="icons/redcircle.png" title="Planification stoppée par l\'utilisateur" />';
                            }
                            /**
                             *  Affichage de l'icone 'loupe' pour afficher les détails de la planification
                             */
                            echo "<img id=\"planStatusToggle${i}\" class=\"icon-lowopacity\" title=\"Afficher les détails\" src=\"icons/search.png\" />";
                            echo '</td>';
                            echo '</tr>';
                            echo '</table>';
                        echo '</div>';

                        /**
                         *  Div caché contenant les détails de la planification
                         */
                        echo "<div id=\"planStatusDiv${i}\" class=\"hide detailsDiv\">";
                            if ($planStatus === "error") {
                                echo "<p>$planError</p>";
                            }

                            echo '<table>';
                                if ($planAction == "update") {
                                    echo "<tr><td><b>GPG Check</b></td><td>$planGpgCheck</td></tr>";
                                    echo "<tr><td><b>GPG Resign</b></td><td>$planGpgResign</td></tr>";
                                }
                                echo "<tr><td><b>Rappels</b></td><td>$planReminder</td></tr>";
                                if (!empty($planLogfile)) {
                                    echo "<tr><td><b>Log</b></td><td><a href=\"run.php?logfile=${planLogfile}\"><b>Voir</b></a></></td></tr>";
                                }
                            echo '</table>';
                        echo '</div>';
                    echo '</div>';

                    // On injecte du code js pour pouvoir déployer le div contenant les détails de la planification
                    echo "<script>
                    $(function() {
                        $('#planStatusToggle${i}').click(function() {
                        $('#planStatusDiv${i}').toggle();
                        });
                    });
                    </script>";
                    ++$i;
                }
            echo '</section>';
        } ?>
    </section>

    <!-- section 'conteneur' principal englobant toutes les sections de gauche -->
    <!-- On charge la section de gauche après celle de droite car elle peut mettre plus de temps à charger (si bcp de repos) -->
    <section class="mainSectionLeft">
        <section class="left">
            <!-- REPOS ACTIFS -->
            <?php include('includes/repos-list.inc.php'); ?>
        </section>
        <section class="left">
            <!-- REPOS ARCHIVÉS-->
            <?php include('includes/repos-archive-list.inc.php'); ?>
        </section>
    </section>
</article>

<?php include('includes/footer.inc.php'); ?>
</body>
</html>