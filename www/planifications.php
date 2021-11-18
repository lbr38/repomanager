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
require_once('functions/repo.functions.php');
require_once('common.php');
require_once('models/Repo.php');
require_once('models/Group.php');
require_once('models/Planification.php');

/**
 *  Cas où on ajoute une planification
 */
if (!empty($_POST['action']) AND validateData($_POST['action']) === "newPlan" AND !empty($_POST['addPlanAction'])) {

    /**
     *  On récupère les paramètres de la planification
     */
    $myplan = new Planification();

    $myplan->setAction($_POST['addPlanAction']);
    if (!empty($_POST['addPlanDate'])) $myplan->setDate($_POST['addPlanDate']);
    if (!empty($_POST['addPlanTime'])) $myplan->setTime($_POST['addPlanTime']);
    if (!empty($_POST['addPlanType'])) $myplan->setType($_POST['addPlanType']);
    if (!empty($_POST['addPlanFrequency'])) $myplan->setFrequency($_POST['addPlanFrequency']);
    if (!empty($_POST['addPlanMailRecipient'])) $myplan->setMailRecipient($_POST['addPlanMailRecipient']);
    if (!empty($_POST['addPlanReminder'])) $myplan->setReminder($_POST['addPlanReminder']);
    if (!empty($_POST['addPlanNotificationOnError'])) {
        $myplan->setNotification('on-error', 'yes');
    } else {
        $myplan->setNotification('on-error', 'no');
    }
    if (!empty($_POST['addPlanNotificationOnSuccess'])) {
        $myplan->setNotification('on-success', 'yes');
    } else {
        $myplan->setNotification('on-success', 'no');
    }

    /**
     *  Si l'action est 'update' alors on récupère les paramètres concernant GPG
     */
    if ($_POST['addPlanAction'] == 'update') {
        if (!empty($_POST['addPlanGpgCheck'])) {
            $myplan->setGpgCheck('yes');
        } else {
            $myplan->setGpgCheck('no');
        }

        if (!empty($_POST['addPlanGpgResign'])) {
            $myplan->setGpgResign('yes');
        } else {
            $myplan->setGpgResign('no');
        }
    }
    
    /**
     *  Cas où c'est un repo seul
     */
    if(!empty($_POST['addPlanRepoId'])) $myplan->setRepoId($_POST['addPlanRepoId']);

    /**
     *  Cas où c'est un groupe
     */
    if(!empty($_POST['addPlanGroupId'])) $myplan->setGroupId($_POST['addPlanGroupId']);

    /**
     *  Création de la planification
     */
    $myplan->new();
}

/**
 *  Cas où on souhaite supprimer une planification
 */
if (!empty($_GET['action']) AND (validateData($_GET['action']) == "deletePlan") AND !empty($_GET['planId'])) {
    $myplan = new Planification();
    $myplan->setId($_GET['planId']);
    $myplan->remove();
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
                        if ($cronStatus == 'On') echo '<span class="pointer" title="La tâche cron pour l\'envoi des rappels est active">Rappels <img src="icons/greencircle.png" /></span>';
                        if ($cronStatus == 'Off') echo '<span class="pointer" title="Il n\'y a aucune tâche cron active pour l\'envoi des rappels">Rappels <img src="icons/redcircle.png" /></span>';
                    } else {
                        echo '<span class="pointer" title="Les rappels de planifications sont désactivés">Rappels <img src="icons/redcircle.png" /></span>';
                    }
                    ?>
                </div>
            </div>

            <?php
            /**
             *  1. Récupération de la liste des planifications en liste d'attente ou en cours d'exécution
             */
            $plans = new Planification();
            $planQueueList = $plans->listQueue();
            $planRunningList = $plans->listRunning();
            $planList = array_merge($planRunningList, $planQueueList);

            /**
             *  2. Affichage des planifications si il y en a
             */
            if(!empty($planList)) {

                echo '<p><img src="icons/calendar.png" class="icon" /><b>Planifications actives</b></p>';

                foreach($planList as $plan) {
                    $planGroup     = '';
                    $planId        = $plan['Id'];
                    $planType      = $plan['Type'];
                    if (!empty($plan['Frequency'])) {
                        $planFrequency = $plan['Frequency'];
                    }
                    if (!empty($plan['Date'])) {
                        $planDate = DateTime::createFromFormat('Y-m-d', $plan['Date'])->format('d-m-Y');
                    } else {
                        $planDate = '';
                    }
                    if (!empty($plan['Time'])) {
                        $planTime = $plan['Time'];
                    } else {
                        $planTime = '';
                    }
                    $planAction    = $plan['Action'];
                    $planGroupId   = $plan['Id_group'];
                    $planRepoId    = $plan['Id_repo'];
                    $planGpgCheck  = $plan['Gpgcheck'];
                    $planGpgResign = $plan['Gpgresign'];
                    $planNotificationOnError = $plan['Notification_error'];
                    $planNotificationOnSuccess = $plan['Notification_success'];
                    $planStatus    = $plan['Status'];
                    $planLogfile   = $plan['Logfile'];
                    if (!empty($plan['Reminder']))
                        $planReminder  = $plan['Reminder'];
                    else
                        $planReminder = 'Aucun';

                    /**
                     *  On définit si la planification traite un repo seul ou un groupe en fonction de si les variables sont vides ou non
                     */ ?>
                    <div class="header-container">
                        <div class="header-blue">
                            <table>
                            <tr>
                                <td class="td-fit">
                                <?php
                                if ($planAction == "update") {
                                    echo '<img class="icon" src="icons/update.png" title="Type d\'opération : '.$planAction.'" />';
                                } else {
                                    echo '<img class="icon" src="icons/link.png" title="Type d\'opération : '.$planAction.'" />';
                                } ?>
                                </td>

                                <?php
                                if ($planType == "plan") {
                                    echo '<td class="td-small">Prévue le <b>'.$planDate.'</b> à <b>'.$planTime.'</b></td>';
                                }
                                if ($planType == "regular") {
                                    if ($planFrequency == "every-hour") {
                                        echo '<td class="td-small">Toutes les heures</b></td>';
                                    }
                                    if ($planFrequency == "every-day") {
                                        echo '<td class="td-small">Tous les jours à <b>'.$planTime.'</b></td>';
                                    }
                                } ?>

                                <td>
                                    <?php
                                    if (!empty($planGroupId)) {
                                        $group = new Group(array('groupId' => $planGroupId));
                                        $group->db_getName();
                                        $planGroup = $group->name;
                                        echo "Groupe $planGroup";
                                        unset($group);
                                    }
                                    if (!empty($planRepoId)) {
                                        $repo = new Repo();
                                        $repo->setId($planRepoId);
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
                                    } ?>
                                </td>
                            <?php
                            /**
                             *  Affichage de l'icone 'loupe' pour afficher les détails de la planification
                             */
                            echo '<td class="td-fit">';
                            echo '<img class="planDetailsBtn icon-lowopacity" plan-id="'.$planId.'" title="Afficher les détails" src="icons/search.png" />';
                            if ($planStatus == "queued") echo "<img class=\"planDeleteToggle-${planId} icon-lowopacity\" title=\"Supprimer la planification\" src=\"icons/bin.png\" />";
                            if ($planStatus == "running") echo 'en cours <img src="images/loading.gif" class="icon" title="en cours d\'exécution" />';
                            echo '</td>';
                            if ($planType == "plan")    deleteConfirm("Êtes-vous sûr de vouloir supprimer la planification du <b>$planDate</b> à <b>$planTime</b>", "?action=deletePlan&planId=${planId}", "planDeleteDiv-${planId}", "planDeleteToggle-${planId}");
                            if ($planType == "regular") deleteConfirm("Êtes-vous sûr de vouloir supprimer la planification récurrente", "?action=deletePlan&planId=${planId}", "planDeleteDiv-${planId}", "planDeleteToggle-${planId}");
                            echo '</tr>';
                            echo '</table>';
                        echo '</div>';

                        /**
                         *  Div caché contenant les détails de la planification
                         */
                        echo '<div class="hide planDetailsDiv" plan-id="'.$planId.'">';
                            if ($planAction == "update") {
                                if (!empty($planGroup)) {
                                    if ($OS_FAMILY == "Redhat") echo "<p>Mise à jour des repos ".envtag($DEFAULT_ENV)." du groupe <b>$planGroup</b></p>";
                                    if ($OS_FAMILY == "Debian") echo "<p>Mise à jour des sections de repos ".envtag($DEFAULT_ENV)." du groupe <b>$planGroup</b></p>";
                                } else {
                                    if ($OS_FAMILY == "Redhat") echo "<p>Mise à jour du repo <b>$planName</b> ".envtag($DEFAULT_ENV)."</p>";
                                    if ($OS_FAMILY == "Debian") echo "<p>Mise à jour du repo <b>$planName</b>, distribution <b>$planDist</b>, section <b>$planSection</b> ".envtag($DEFAULT_ENV)."</p>";
                                }
                                echo '<div>';
                                    echo '<span>GPG Check</span>';
                                    if ($planGpgCheck == "yes")
                                        echo '<span><img src="icons/greencircle.png" class="icon-small" /> Activé</span>';
                                    else 
                                        echo '<span><img src="icons/redcircle.png" class="icon-small" /> Désactivé</span>';
                                echo '</div>';

                                echo '<div>';
                                    echo '<span>GPG Resign</span>';
                                    if ($planGpgResign == "yes") 
                                        echo '<span><img src="icons/greencircle.png" class="icon-small" /> Activé</span>';
                                    else 
                                        echo '<span><img src="icons/redcircle.png" class="icon-small" /> Désactivé</span>';
                                echo '</div>';
                            } else {
                                $envs = explode('->', $planAction);
                                $envTarget = $envs[0];
                                $envSource = $envs[1];
                                if (!empty($planGroup)) {
                                    if ($OS_FAMILY == "Redhat") echo "<p>Pointage de l'environnement ".envtag($envSource)." vers ".envtag($envTarget)." pour les repos du groupe <b>$planGroup</b></p>";
                                    if ($OS_FAMILY == "Debian") echo "<p>Pointage de l'environnement ".envtag($envSource)." vers ".envtag($envTarget)." pour les sections de repos du groupe <b>$planGroup</b></p>";
                                } else {
                                    if ($OS_FAMILY == "Redhat") echo "<p>Pointage de l'environnement ".envtag($envSource)." vers ".envtag($envTarget)." pour le repo <b>$planName</b></p>";
                                    if ($OS_FAMILY == "Debian") echo "<p>Pointage de l'environnement ".envtag($envSource)." vers ".envtag($envTarget)." pour le repo <b>$planName</b>, distribution <b>$planDist</b>, section <b>$planSection</b></p>";
                                }
                            }
                            echo '<div>';
                                echo '<span>Rappels</span>';
                                echo '<span>';
                                if ($planReminder == 'Aucun') {
                                    echo 'Aucun';
                                } else {
                                    $planReminder = explode(',', $planReminder);
                                    foreach ($planReminder as $reminder) {
                                        echo "$reminder jours avant<br>";
                                    }
                                }
                                echo '</span>';
                            echo '</div>';

                            if (!empty($planLogfile)) {
                                echo "<div><span>Log</span><span><a href=\"run.php?logfile=${planLogfile}\">Voir</a></span></div>";
                            }
                        echo '</div>';
                    echo '</div>';
                }
                
                echo '<br><hr><br>';
            } ?>

            <form action="planifications.php" class="actionform" method="post" autocomplete="off">
                <input type="hidden" name="action" value="newPlan" />
                <p><b><img src="icons/plus.png" class="icon" />Créer une planification</b></p>
                <table class="table-large">
                    <tr>
                        <td>Type</td>
                        <td class="td-medium">
                            <div class="switch-field">
                                <input type="radio" id="planType_plan" name="addPlanType" value="plan" checked />
                                <label for="planType_plan">Tâche unique</label>
                                <input type="radio" id="planType_regular" name="addPlanType" value="regular" />
                                <label for="planType_regular">Tâche récurrente</label>
                            </div>
                        </td>
                    </tr>
                    <tr class="__regular_plan_input hide">
                        <td class="td-fit">Fréquence</td>
                        <td>
                            <select id="planFrequencySelect" name="addPlanFrequency">
                                <option value="">Sélectionner...</option>
                                <option id="planFrequency-every-hour" value="every-hour">toutes les heures</option>
                                <option id="planFrequency-every-day" value="every-day">tous les jours</option>
                            </select>
                        </td>
                    </tr>
                    <tr class="__plan_input">
                        <td class="td-fit">Date</td>
                        <td><input type="date" name="addPlanDate" /></td>
                    </tr>
                    <tr class="__plan_hour_input">
                        <td class="td-fit">Heure</td>
                        <td><input type="time" name="addPlanTime" /></td>
                    </tr>
                    <tr>
                        <td class="td-fit">Action</td>
                        <td>
                            <select name="addPlanAction" id="planSelect">
                            <?php
                            $lastEnv = '';
                            foreach ($ENVS as $env) {
                                if (!empty($lastEnv)) {
                                    echo "<option value='${lastEnv}->${env}'>Faire pointer un environnement ${lastEnv} -> ${env}</option>";
                                }
                                $lastEnv = $env;
                            }
                            if ($ENVS_TOTAL >= 1) {
                                echo "<option value=\"update\" id=\"updateRepoSelect\">Mise à jour de l'environnement ${DEFAULT_ENV}</option>";
                            } ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="td-fit">Repo</td>
                        <td>
                            <select name="addPlanRepoId">
                                <option value="">Sélectionnez un repo...</option>
                                <?php
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
                                        if ($OS_FAMILY == "Redhat") echo "<option value=\"$repoId\">$repoName</option>";
                                        if ($OS_FAMILY == "Debian") echo "<option value=\"$repoId\">$repoName - $repoDist - $repoSection</option>";
                                    }
                                } ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="td-fit">ou Groupe</td>
                        <td>
                            <select name="addPlanGroupId">
                                <option value="">Sélectionnez un groupe...</option>
                                <?php
                                $group = new Group();
                                $groupsList = $group->listAll();
                                if (!empty($groupsList)) {
                                    foreach($groupsList as $group) {
                                        $groupId = $group['Id'];
                                        $groupName = $group['Name'];
                                        echo "<option value=\"${groupId}\">${groupName}</option>";
                                    }
                                } ?>
                            </select>
                        </td>
                    </tr>
                    <tr class="__plan_gpg_input hide">
                        <td colspan="100%"><hr><p><b>Paramètres GPG</b></p></td>
                    </tr>
                    <tr class="__plan_gpg_input hide">
                        <td class="td-fit">Vérif. des sign. GPG</td>
                        <td>
                            <label class="onoff-switch-label">
                                <input name="addPlanGpgCheck" type="checkbox" class="onoff-switch-input" value="yes" checked />
                                <span class="onoff-switch-slider"></span>
                            </label>
                        </td>
                    </tr>      
                    <tr class="__plan_gpg_input hide">
                        <td class="td-fit">Signer avec GPG</td>
                        <td>
                            <label class="onoff-switch-label">
                                <input name="addPlanGpgResign" type="checkbox" class="onoff-switch-input" value="yes"<?php if ($GPG_SIGN_PACKAGES == "yes") { echo 'checked'; }?> />
                                <span class="onoff-switch-slider"></span>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="100%"><hr><p><b>Notifications mail</b></p></td>
                    </tr>
                    <tr>
                        <td>Destinataire(s)</td>
                        <td><input type="email" name="addPlanMailRecipient" placeholder="Adresses emails séparées par une virgule" value="<?php echo $EMAIL_DEST;?>" multiple /></td>
                    </tr>
                    <tr class="__plan_input">
                        <td class="td-fit">Rappels de planification</td>
                        <td>
                            <select id="planReminderSelect" name="addPlanReminder[]" multiple>
                                <option value="1">1 jour avant</option>
                                <option value="2">2 jours avant</option>
                                <option value="3" selected>3 jours avant</option>
                                <option value="4">4 jours avant</option>
                                <option value="5">5 jours avant</option>
                                <option value="6">6 jours avant</option>
                                <option value="7" selected>7 jours avant</option>
                                <option value="8">8 jours avant</option>
                                <option value="9">9 jours avant</option>
                                <option value="10">10 jours avant</option>
                                <option value="15">15 jours avant</option>
                                <option value="20">20 jours avant</option>
                                <option value="25">25 jours avant</option>
                                <option value="30">30 jours avant</option>
                                <option value="35">35 jours avant</option>
                                <option value="40">40 jours avant</option>
                                <option value="45">45 jours avant</option>
                                <option value="50">50 jours avant</option>
                                <option value="55">55 jours avant</option>
                                <option value="60">60 jours avant</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="td-fit">Planification en erreur</td>
                        <td>
                            <label class="onoff-switch-label">
                                <input name="addPlanNotificationOnError" type="checkbox" class="onoff-switch-input" value="yes" checked />
                                <span class="onoff-switch-slider"></span>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class="td-fit">Planification terminée</td>
                        <td>
                            <label class="onoff-switch-label">
                                <input name="addPlanNotificationOnSuccess" type="checkbox" class="onoff-switch-input" value="yes" checked />
                                <span class="onoff-switch-slider"></span>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="100%"><button type="submit" class="btn-large-blue">Ajouter</button></td>
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
                    $(".__plan_gpg_input").show();
                } else {
                    $(".__plan_gpg_input").hide();
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

                foreach($plansDone as $plan) {
                    $planId        = $plan['Id'];
                    $planDate      = DateTime::createFromFormat('Y-m-d', $plan['Date'])->format('d-m-Y');
                    $planTime      = $plan['Time'];
                    $planAction    = $plan['Action'];
                    $planGroupId   = $plan['Id_group'];
                    $planRepoId    = $plan['Id_repo'];
                    $planGpgCheck  = $plan['Gpgcheck'];
                    $planGpgResign = $plan['Gpgresign'];
                    $planStatus    = $plan['Status'];
                    $planError     = $plan['Error'];
                    $planLogfile   = $plan['Logfile'];
                    if (!empty($plan['Reminder']))
                        $planReminder  = $plan['Reminder'];
                    else
                        $planReminder = 'Aucun';
                    if (empty($planDate))      $planDate = '?';
                    if (empty($planTime))      $planTime = '?';
                    if (empty($planAction))    $planAction = '?';
                    if (empty($planGpgCheck))  $planGpgCheck = '?';
                    if (empty($planGpgResign)) $planGpgResign = '?';
                    if (empty($planReminder))  $planReminder = '?';
                    if (empty($planStatus))    $planStatus = '?';

                    echo '<div class="header-container">';
                        echo '<div class="header-blue">';
                            echo '<table>';
                            echo '<tr>';
                            echo '<td class="td-fit">';
                            if ($planAction == "update")
                                echo "<img class=\"icon\" src=\"icons/update.png\" title=\"Type d'opération : $planAction\" />";
                            else
                                echo "<img class=\"icon\" src=\"icons/link.png\" title=\" Type d'opération : $planAction\" />";
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
                            echo '<img class="planDetailsBtn icon-lowopacity" plan-id="'.$planId.'" title="Afficher les détails" src="icons/search.png" />';
                            echo '</td>';
                            echo '</tr>';
                            echo '</table>';
                        echo '</div>';

                        /**
                         *  Div caché contenant les détails de la planification
                         */
                        echo '<div class="hide planDetailsDiv" plan-id="'.$planId.'">';
                            /**
                             *  Si la planification est en erreur alors on affiche le message d'erreur
                             */
                            if ($planStatus == "error") echo "<p>$planError</p>";

                                if ($planAction == "update") {
                                    /**
                                     *  GPG Check
                                     */
                                    echo '<div>';
                                        echo '<span>GPG Check</span>';
                                        if ($planGpgCheck == "yes")
                                            echo '<span><img src="icons/greencircle.png" class="icon-small" /> Activé</span>';
                                        else
                                            echo '<span><img src="icons/redcircle.png" class="icon-small" /> Désactivé</span>';
                                    echo '</div>';
                                    /**
                                     *  GPG Resign
                                     */
                                    echo '<div>';
                                        echo '<span>GPG Resign</span>';
                                        if ($planGpgResign == "yes")
                                            echo '<span><img src="icons/greencircle.png" class="icon-small" /> Activé</span>';
                                        else
                                            echo '<span><img src="icons/redcircle.png" class="icon-small" /> Désactivé</span>';
                                    echo '</div>';
                                }
                                /**
                                 *  Rappels
                                 */
                                echo '<div>';
                                    echo '<span>Rappels</span>';
                                    echo '<span>';
                                    if ($planReminder == 'Aucun') {
                                        echo 'Aucun';
                                    } else {
                                        $planReminder = explode(',', $planReminder);
                                        foreach ($planReminder as $reminder) {
                                            echo "$reminder jours avant<br>";
                                        }
                                    }
                                    echo '</span>';
                                echo '</div>';
                                /**
                                 *  Log
                                 */
                                echo '<div>';   
                                    if (!empty($planLogfile)) {
                                        echo '<span>Log</span>';
                                        echo "<span><a href='run.php?logfile=$planLogfile'><button class='btn-xsmall-blue'><b>Voir</b></button></a></></span>";
                                    }
                                echo '</div>';
                        echo '</div>';
                    echo '</div>';
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