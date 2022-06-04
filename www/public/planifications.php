<!DOCTYPE html>
<html>
<?php
require_once('../controllers/Autoloader.php');
\Controllers\Autoloader::load();
include_once('../includes/head.inc.php');
require_once('../functions/repo.functions.php');
?>

<body>
<?php include_once('../includes/header.inc.php'); ?>

<article>
    <section class="mainSectionRight">
        <?php if (Models\Common::isadmin()) : ?>
            <!-- GERER L'AFFICHAGE -->
            <?php include_once('../includes/display.inc.php'); ?>

            <!-- EXECUTER DES OPERATIONS -->
            <?php include_once('../includes/operation.inc.php'); ?> 

            <!-- GERER LES GROUPES -->
            <?php include_once('../includes/manage-groups.inc.php'); ?>

            <!-- GERER LES SOURCES -->
            <?php include_once('../includes/manage-sources.inc.php'); ?>
        <?php endif ?>

        <section id="planDiv" class="right">
            <div class="div-flex">
                <h3>PLANIFICATIONS</h3>
                <div id="planCronStatus">
                    <?php
                    // on commence par vérifier si une tache cron est déjà présente ou non :
                    if (CRON_PLAN_REMINDERS_ENABLED == "yes") {
                        $cronStatus = \Models\Common::checkCronReminder();
                        if ($cronStatus == 'On') {
                            echo '<span class="pointer" title="La tâche cron pour l\'envoi des rappels est active">Rappels <img src="ressources/icons/greencircle.png" /></span>';
                        }
                        if ($cronStatus == 'Off') {
                            echo '<span class="pointer" title="Il n\'y a aucune tâche cron active pour l\'envoi des rappels">Rappels <img src="ressources/icons/redcircle.png" /></span>';
                        }
                    } else {
                        echo '<span class="pointer" title="Les rappels de planifications sont désactivés">Rappels <img src="ressources/icons/redcircle.png" /></span>';
                    }
                    ?>
                </div>
            </div>

            <?php

            /**
             *  1. Récupération de la liste des planifications en liste d'attente ou en cours d'exécution
             */

            $plans = new \Controllers\Planification();
            $planQueueList = $plans->listQueue();
            $planRunningList = $plans->listRunning();
            $planList = array_merge($planRunningList, $planQueueList);

            /**
             *  2. Affichage des planifications si il y en a
             */
            if (!empty($planList)) {
                echo '<div class="div-generic-gray">';
                    echo '<h5><img src="ressources/icons/calendar.png" class="icon" />Planifications actives</h5>';

                foreach ($planList as $plan) {
                    $planId = $plan['Id'];
                    $planType = $plan['Type'];
                    $planGroup = '';
                    if (!empty($plan['Day'])) {
                        $planDay = $plan['Day'];
                    }
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
                    $planAction = $plan['Action'];
                    $planGroupId = $plan['Id_group'];
                    $planSnapId = $plan['Id_snap'];
                    $planGpgCheck = $plan['Gpgcheck'];
                    $planGpgResign = $plan['Gpgresign'];
                    $planMailRecipient = $plan['Mail_recipient'];
                    $planNotificationOnError = $plan['Notification_error'];
                    $planNotificationOnSuccess = $plan['Notification_success'];
                    $planStatus = $plan['Status'];
                    $planLogfile = $plan['Logfile'];
                    if (!empty($plan['Reminder'])) {
                        $planReminder = $plan['Reminder'];
                    } else {
                        $planReminder = 'Aucun';
                    }

                    /**
                     *  On définit si la planification traite un repo seul ou un groupe en fonction de si les variables sont vides ou non
                     */ ?>
                        <div class="header-container">
                            <div class="header-blue">
                                <table>
                                <tr>
                                    <td class="td-10">
                                    <?php
                                    if ($planAction == "update") {
                                        echo '<img class="icon" src="ressources/icons/update.png" title="Type d\'opération : ' . $planAction . '" />';
                                    } else {
                                        echo '<img class="icon" src="ressources/icons/link.png" title="Type d\'opération : ' . $planAction . '" />';
                                    } ?>
                                    </td>
                                    <td class="td-small">
                                        <?php
                                        /**
                                         *  Affichage du type de planification
                                         */
                                        if ($planType == "plan") {
                                            echo 'Prévue le <b>' . $planDate . '</b> à <b>' . $planTime . '</b>';
                                        }
                                        if ($planType == "regular") {
                                            if ($planFrequency == "every-hour") {
                                                echo 'Toutes les heures</b>';
                                            }
                                            if ($planFrequency == "every-day") {
                                                echo 'Tous les jours à <b>' . $planTime . '</b>';
                                            }
                                            if ($planFrequency == "every-week") {
                                                echo 'Toutes les semaines';
                                            }
                                        } ?>
                                    </td>
                                    <td>
                                        <?php
                                        /**
                                         *  Si la planification traite un groupe, on récupère son nom à partir de son Id
                                         */
                                        if (!empty($planGroupId)) {
                                            $group = new \Controllers\Group('repo');

                                            /**
                                             *  On vérifie que le groupe spécifié existe toujours (il a peut être été supprimé entre temps)
                                             */
                                            if ($group->existsId($planGroupId) === false) {
                                                $planGroup = "inconnu (supprimé)";
                                            } else {
                                                $planGroup = '<span class="label-white">' . $group->getNameById($planGroupId) . '</span>';
                                            }

                                            echo 'Groupe ' . $planGroup;
                                        }
                                        /**
                                         *  Si la planification traite un repo, on récupère ses informations à partir de son Id
                                         */
                                        if (!empty($planSnapId)) {
                                            $repo = new \Controllers\Repo();

                                            /**
                                             *  On vérifie que le repo spécifié existe toujours (il a peut être été supprimé entre temps)
                                             */
                                            if ($repo->existsSnapId($planSnapId) === false) {
                                                $repo = "Repo inconnu (supprimé)";
                                            } else {
                                                /**
                                                 *  Récupération de toutes les infos concernant le repo
                                                 */
                                                $repo->getAllById('', $planSnapId);
                                                $planName = $repo->getName();
                                                $planDist = $repo->getDist();
                                                $planSection = $repo->getSection();
                                                $planDate = $repo->getDateFormatted();

                                                /**
                                                 *  Formatage
                                                 */
                                                if (!empty($repo->getDist()) and !empty($repo->getSection())) {
                                                    $planDist = $repo->getDist();
                                                    $planSection = $repo->getSection();
                                                    $repo = '<span class="label-white">' . $planName . ' ❯ ' . $planDist . ' ❯ ' . $planSection . '</span>';
                                                } else {
                                                    $repo = '<span class="label-white">' . $planName . '</span>';
                                                }

                                                $planDate = '<span class="label-white">' . $planDate . '</span>';
                                            }

                                            echo $repo;
                                        } ?>
                                    </td>
                                    <?php
                                    /**
                                     *  Affichage de l'icone 'loupe' pour afficher les détails de la planification
                                     */ ?>
                                    <td class="td-fit">
                                        <span>
                                            <img class="planDetailsBtn icon-lowopacity" plan-id="<?=$planId?>" title="Afficher les détails" src="ressources/icons/search.png" />
                                        </span>
                                        <span>
                                        <?php
                                        if ($planStatus == "queued") {
                                            echo '<img class="deletePlanButton icon-lowopacity" plan-id="' . $planId . '" plan-type="' . $planType . '" title="Supprimer la planification" src="ressources/icons/bin.png" />';
                                        }
                                        if ($planStatus == "running") {
                                            echo 'en cours<img src="ressources/images/loading.gif" class="icon" title="en cours d\'exécution" />';
                                        }
                                        ?>
                                        </span>
                                    </td>
                                </tr>
                                </table>
                            </div>
                        
                            <div class="hide detailsDiv" plan-id="<?=$planId?>">
                                <?php
                                /**
                                 *  Div caché contenant les détails de la planification
                                 */
                                /**
                                 *  Affichage de l'action
                                 *
                                 *  Si l'action est 'update'
                                 */
                                if ($planAction == "update") {
                                    if (!empty($planGroup)) {
                                        echo '<p>Mise à jour des repos du groupe ' . $planGroup . '</p>';
                                    } else {
                                        echo '<p>Mise à jour du repo ' . $repo . '</p>';
                                    }

                                /**
                                 *  Si l'action est un changement d'env
                                 */
                                } /*else {
                                $envs = explode('->', $planAction);
                                $envTarget = $envs[0];
                                $envSource = $envs[1];
                                if (!empty($planGroup)) {
                                    if (OS_FAMILY == "Redhat") echo "<p>Nouvel environnement ".Models\Common::envtag($envSource)."⟶".Models\Common::envtag($envTarget).'⟶<span class="label-black">xx-xx-xxxx</span> pour les repos du groupe <span class="label-white">'.$planGroup.'</span></p>';
                                    if (OS_FAMILY == "Debian") echo "<p>Nouvel environnement ".Models\Common::envtag($envSource)."⟶".Models\Common::envtag($envTarget).'⟶<span class="label-black">xx-xx-xxxx</span> pour les sections de repos du groupe <span class="label-white">'.$planGroup.'</span></p>';
                                } else {
                                    if (OS_FAMILY == "Redhat") echo "<p>Nouvel environnement ".Models\Common::envtag($envSource)."⟶".Models\Common::envtag($envTarget)."⟶$planDate pour le repo $repo</p>";
                                    if (OS_FAMILY == "Debian") echo "<p>Nouvel environnement ".Models\Common::envtag($envSource)."⟶".Models\Common::envtag($envTarget)."⟶$planDate pour le repo $repo</p>";
                                }
                                }*/

                                echo '<br>';

                                /**
                                 *  Affichage des jours où la planification récurrente est active
                                 */
                                if ($planType == "regular") {
                                    if (!empty($planDay)) {
                                        echo '<div>';
                                        echo '<span>Jour(s)</span>';
                                        echo '<span>';
                                        $planDay = explode(',', $planDay);
                                        foreach ($planDay as $day) {
                                            if ($day == "monday") {
                                                echo 'lundi';
                                            }
                                            if ($day == "tuesday") {
                                                echo 'mardi';
                                            }
                                            if ($day == "wednesday") {
                                                echo 'mercredi';
                                            }
                                            if ($day == "thursday") {
                                                echo 'jeudi';
                                            }
                                            if ($day == "friday") {
                                                echo 'vendredi';
                                            }
                                            if ($day == "saturday") {
                                                echo 'samedi';
                                            }
                                            if ($day == "sunday") {
                                                echo 'dimanche';
                                            }
                                            echo '<br>';
                                        }
                                        echo '</span>';
                                        echo '</div>';
                                    }
                                }

                                /**
                                 *  Affichage de l'heure
                                 */
                                if (!empty($planTime)) {
                                    echo '<div>';
                                        echo '<span>Heure</span>';
                                        echo '<span>' . $planTime . '</span>';
                                    echo '</div>';
                                }

                                if ($planAction == "update") {
                                    /**
                                     *  GPG Check
                                     */
                                    echo '<div>';
                                        echo '<span>Vérif. des signatures GPG</span>';
                                    if ($planGpgCheck == "yes") {
                                        echo '<span><img src="ressources/icons/greencircle.png" class="icon-small" /> Activé</span>';
                                    } else {
                                        echo '<span><img src="ressources/icons/redcircle.png" class="icon-small" /> Désactivé</span>';
                                    }
                                    echo '</div>';
                                    /**
                                     *  GPG Resign
                                     */
                                    echo '<div>';
                                        echo '<span>Signature des paquets avec GPG</span>';
                                    if ($planGpgResign == "yes") {
                                        echo '<span><img src="ressources/icons/greencircle.png" class="icon-small" /> Activé</span>';
                                    } else {
                                        echo '<span><img src="ressources/icons/redcircle.png" class="icon-small" /> Désactivé</span>';
                                    }
                                    echo '</div>';
                                }

                                echo '<hr>';

                                /**
                                 *  Rappels mail
                                 */
                                echo '<div>';
                                echo '<span>Rappels</span>';
                                echo '<span>';
                                if ($planReminder == 'Aucun') {
                                    echo 'Aucun';
                                } else {
                                    $planReminder = explode(',', $planReminder);
                                    foreach ($planReminder as $reminder) {
                                        if (!empty($reminder)) {
                                            echo $reminder . ' jours avant<br>';
                                        }
                                    }
                                }
                                    echo '</span>';
                                    echo '</div>';

                                /**
                                 *  Notification en cas d'erreur
                                 */
                                    echo '<div>';
                                    echo '<span>Notification en cas d\'erreur</span>';
                                if ($planNotificationOnError == "yes") {
                                    echo '<span><img src="ressources/icons/greencircle.png" class="icon-small" /> Activé</span>';
                                } else {
                                    echo '<span><img src="ressources/icons/redcircle.png" class="icon-small" /> Désactivé</span>';
                                }
                                    echo '</div>';

                                /**
                                 *  Notification en cas de succès
                                 */
                                    echo '<div>';
                                    echo '<span>Notification en cas de succès</span>';
                                if ($planNotificationOnSuccess == "yes") {
                                    echo '<span><img src="ressources/icons/greencircle.png" class="icon-small" /> Activé</span>';
                                } else {
                                    echo '<span><img src="ressources/icons/redcircle.png" class="icon-small" /> Désactivé</span>';
                                }
                                    echo '</div>';

                                /**
                                 *  Destinataire mail
                                 */
                                if (!empty($planMailRecipient)) {
                                    echo '<div>';
                                    echo '<span>Contact</span>';
                                    echo '<span>';
                                        $planMailRecipient = explode(',', $planMailRecipient);
                                    foreach ($planMailRecipient as $recipient) {
                                        if (!empty($recipient)) {
                                            echo $recipient . '<br>';
                                        }
                                    }
                                    echo '</span>';
                                    echo '</div>';
                                }

                                /**
                                 *  Log
                                 */
                                if (!empty($planLogfile)) {
                                    echo '<div><span>Log</span><span><a href="run.php?logfile=' . $planLogfile . '"><button class="btn-xsmall-blue"><b>Voir</b></button></a></span></div>';
                                } ?>
                            </div>
                        </div>
                <?php               }
                echo '</div>';
            } ?>

            <?php if (Models\Common::isadmin()) { ?>
                <form id="newPlanForm" class="div-generic-gray" autocomplete="off">
                    <h5><img src="ressources/icons/plus.png" class="icon" />Créer une planification</h5>
                    <table class="table-large">
                        <tr>
                            <td>Type</td>
                            <td class="td-medium">
                                <div class="switch-field">
                                    <input type="radio" id="addPlanType-plan" name="planType" value="plan" checked />
                                    <label for="addPlanType-plan">Tâche unique</label>
                                    <input type="radio" id="addPlanType-regular" name="planType" value="regular" />
                                    <label for="addPlanType-regular">Tâche récurrente</label>
                                </div>
                            </td>
                        </tr>

                        <tr class="__regular_plan_input hide">
                            <td class="td-10">Fréquence</td>
                            <td>
                                <select id="planFrequencySelect">
                                    <option value="">Sélectionner...</option>
                                    <option id="planFrequency-every-hour" value="every-hour">toutes les heures</option>
                                    <option id="planFrequency-every-day" value="every-day">tous les jours</option>
                                    <option id="planFrequency-every-week" value="every-week">toutes les semaines</option>
                                </select>
                            </td>
                        </tr>

                        <tr class="__regular_plan_input __regular_plan_day_input hide">
                            <td class="td-10">Jour(s)</td>
                            <td>
                                <select id="planDayOfWeekSelect" multiple>
                                    <option value="monday">Lundi</option>
                                    <option value="tuesday">Mardi</option>
                                    <option value="wednesday">Mercredi</option>
                                    <option value="thursday">Jeudi</option>
                                    <option value="friday">Vendredi</option>
                                    <option value="saturday">Samedi</option>
                                    <option value="sunday">Dimanche</option>
                                </select>
                            </td>
                        </tr>

                        <tr class="__plan_input">
                            <td class="td-10">Date</td>
                            <td><input id="addPlanDate" type="date" /></td>
                        </tr>

                        <tr class="__plan_hour_input">
                            <td class="td-10">Heure</td>
                            <td><input id="addPlanTime" type="time" /></td>
                        </tr>

                        <tr>
                            <td class="td-10">Action</td>
                            <td>
                                <select id="planActionSelect">
                                    <option></option>
                                    <?php
                                    // foreach (ENVS as $env) {
                                    //     echo '<option value="' . $env . '">Faire pointer un environnement ' . \Models\Common::envtag($env) . '</option>';
                                    // }
                                    ?>
                                    <option value="update" id="updateRepoSelect">Mise à jour</option>
                                </select>
                            </td>
                        </tr>

                        <tr>
                            <td class="td-10">Repo</td>
                            <td>
                                <select id="addPlanSnapId">
                                    <option value="">Sélectionnez un repo...</option>
                                    <?php
                                    /**
                                     *  Récupération de la liste des repos qui possèdent un environnement DEFAULT_ENV
                                     */
                                    $repo = new \Controllers\Repo();
                                    $reposList = $repo->listForPlan();

                                    if (!empty($reposList)) {
                                        foreach ($reposList as $myrepo) {
                                            $snapId = $myrepo['snapId'];
                                            $repoName = $myrepo['Name'];
                                            $repoDist = $myrepo['Dist'];
                                            $repoSection = $myrepo['Section'];
                                            $repoDate = $myrepo['Date'];
                                            $repoDateFormatted = DateTime::createFromFormat('Y-m-d', $repoDate)->format('d-m-Y');
                                            $repoPackageType = $myrepo['Package_type'];
                                            $repoType = $myrepo['Type'];

                                            /**
                                             *  Si le repo est local alors on ne l'affiche pas dans la liste
                                             */
                                            if ($repoType == 'local') {
                                                continue;
                                            }

                                            /**
                                             *  On génère une <option> pour chaque repo
                                             */
                                            if ($repoPackageType == "rpm") {
                                                echo '<option value="' . $snapId . '">' . $repoName . ' ⟶ ' . $repoDateFormatted . '</option>';
                                            }
                                            if ($repoPackageType == "deb") {
                                                echo '<option value="' . $snapId . '"><span class="label-white">' . $repoName . ' ❯ ' . $repoDist . ' ❯ ' . $repoSection . '</span> ⟶ ' . $repoDateFormatted . '</option>';
                                            }
                                        }
                                    } ?>
                                </select>
                            </td>
                        </tr>

                        <tr>
                            <td class="td-10">ou Groupe</td>
                            <td>
                                <select id="addPlanGroupId">
                                    <option value="">Sélectionnez un groupe...</option>
                                    <?php
                                    $group = new \Controllers\Group('repo');
                                    $groupsList = $group->listAll();

                                    if (!empty($groupsList)) {
                                        foreach ($groupsList as $group) {
                                            $groupId = $group['Id'];
                                            $groupName = $group['Name'];
                                            echo '<option value="' . $groupId . '">' . $groupName . '</option>';
                                        }
                                    } ?>
                                </select>
                            </td>
                        </tr>

                        <tr>
                            <td>Faire pointer un environnement</td>
                            <td>
                                <select id="addPlanTargetEnv">
                                    <option value=""></option>
                                    <?php
                                    foreach (ENVS as $env) {
                                        if ($env == DEFAULT_ENV) {
                                            echo '<option value="' . $env . '" selected>' . $env . '</option>';
                                        } else {
                                            echo '<option value="' . $env . '">' . $env . '</option>';
                                        }
                                    } ?>
                                </select>
                            </td>
                        </tr>

                        <tr id="update-preview" class="hide">
                            <td colspan="100%">
                                <br><hr><br>
                                <p>La mise à jour créera un nouveau snapshot pour chaque repo sélectionné :<br></p>
                                <span id="update-preview-date" class="label-black"></span><span id="update-preview-target-env">⟵</span>
                                <br><br>
                            </td>
                        </tr>

                        <tr class="__plan_gpg_input hide">
                            <td colspan="100%"><hr><p><b>Paramètres GPG</b></p></td>
                        </tr>

                        <tr class="__plan_gpg_input hide">
                            <td class="td-10">Vérif. des sign. GPG</td>
                            <td>
                                <label class="onoff-switch-label">
                                    <input id="addPlanGpgCheck" type="checkbox" name="addPlanGpgCheck" class="onoff-switch-input" value="yes" checked />
                                    <span class="onoff-switch-slider"></span>
                                </label>
                            </td>
                        </tr>     

                        <tr class="__plan_gpg_input hide">
                            <td class="td-10">Signer avec GPG</td>
                            <td>
                                <label class="onoff-switch-label">
                                    <input id="addPlanGpgResign" type="checkbox" name="addPlanGpgResign" class="onoff-switch-input" value="yes" <?php echo (GPG_SIGN_PACKAGES == "yes") ? 'checked' : ''; ?>>
                                    <span class="onoff-switch-slider"></span>
                                </label>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="100%">
                                <hr><p><b>Notifications mail</b></p>
                            </td>
                        </tr>

                        <tr>
                            <td>Destinataire(s)</td>
                            <td><input type="email" id="addPlanMailRecipient" placeholder="Adresses emails séparées par une virgule" value="<?= EMAIL_DEST ?>" multiple /></td>
                        </tr>

                        <tr class="__plan_input __plan_input_reminder">
                            <td class="td-10">Envoyer un rappel</td>
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
                            <td class="td-10">Planification en erreur</td>
                            <td>
                                <label class="onoff-switch-label">
                                    <input id="addPlanNotificationOnError" name="addPlanNotificationOnError" type="checkbox" class="onoff-switch-input" value="yes" checked />
                                    <span class="onoff-switch-slider"></span>
                                </label>
                            </td>
                        </tr>

                        <tr>
                            <td class="td-10">Planification terminée</td>
                            <td>
                                <label class="onoff-switch-label">
                                    <input id="addPlanNotificationOnSuccess" type="checkbox" class="onoff-switch-input" value="yes" checked />
                                    <span class="onoff-switch-slider"></span>
                                </label>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="100%"><button type="submit" class="btn-large-blue">Ajouter</button></td>
                        </tr>
                    </table>
                </form>
            <?php } ?>

            <?php
            /**
             *  Affichage des planifications terminées si il y en a
             */
            $plansDone = $plans->listDone();

            if (!empty($plansDone)) {
                echo '<div class="div-generic-gray">';
                    echo '<h5><img src="ressources/icons/history.png" class="icon" />Historique des planifications</h5>';

                foreach ($plansDone as $plan) {
                    $planId                    = $plan['Id'];
                    $planDay                   = $plan['Day'];
                    $planDate                  = DateTime::createFromFormat('Y-m-d', $plan['Date'])->format('d-m-Y');
                    $planTime                  = $plan['Time'];
                    $planAction                = $plan['Action'];
                    $planGroupId               = $plan['Id_group'];
                    $planSnapId                = $plan['Id_snap'];
                    $planGpgCheck              = $plan['Gpgcheck'];
                    $planGpgResign             = $plan['Gpgresign'];
                    $planStatus                = $plan['Status'];
                    $planError                 = $plan['Error'];
                    $planMailRecipient         = $plan['Mail_recipient'];
                    $planNotificationOnError   = $plan['Notification_error'];
                    $planNotificationOnSuccess = $plan['Notification_success'];
                    $planLogfile               = $plan['Logfile'];

                    if (!empty($plan['Reminder'])) {
                        $planReminder  = $plan['Reminder'];
                    } else {
                        $planReminder = 'Aucun';
                    }
                    if (empty($planDate)) {
                        $planDate = '?';
                    }
                    if (empty($planTime)) {
                        $planTime = '?';
                    }
                    if (empty($planAction)) {
                        $planAction = '?';
                    }
                    if (empty($planGpgCheck)) {
                        $planGpgCheck = '?';
                    }
                    if (empty($planGpgResign)) {
                        $planGpgResign = '?';
                    }
                    if (empty($planReminder)) {
                        $planReminder = '?';
                    }
                    if (empty($planStatus)) {
                        $planStatus = '?';
                    } ?>

                        <div class="header-container">
                            <div class="header-blue">
                                <table>
                                    <tr>
                                        <td class="td-10">
                                        <?php
                                        if ($planAction == "update") {
                                            echo "<img class=\"icon\" src=\"ressources/icons/update.png\" title=\"Type d'opération : $planAction\" />";
                                        } else {
                                            echo "<img class=\"icon\" src=\"ressources/icons/link.png\" title=\" Type d'opération : $planAction\" />";
                                        } ?>
                                        </td>
                                        <td class="td-small">Le <b><?=$planDate?></b> à <b><?=$planTime?></b></td>

                                        <td>
                                            <?php
                                            /**
                                             *  Affichage du repo ou du groupe
                                             */
                                            if (!empty($planGroupId)) {
                                                $group = new \Controllers\Group('repo');
                                                $planGroup = $group->getNameById($planGroupId);

                                                echo 'Groupe <span class="label-white">' . $planGroup . '</span>';
                                                unset($group);
                                            }
                                            if (!empty($planSnapId)) {
                                                $repo = new \Controllers\Repo();

                                                /**
                                                 *  Récupération de toutes les infos concernant le repo
                                                 */
                                                $repo->getAllById('', $planSnapId);
                                                $planName = $repo->getName();
                                                $planDist = $repo->getDist();
                                                $planSection = $repo->getSection();

                                                /**
                                                 *  Formatage
                                                 */
                                                if (!empty($planDist) and !empty($planSection)) {
                                                    $repo = '<span class="label-white">' . $planName . ' ❯ ' . $planDist . ' ❯ ' . $planSection . '</span>';
                                                } else {
                                                    $repo = '<span class="label-white">' . $planName . '</span>';
                                                }

                                                echo $repo;
                                            } ?>
                                        </td>
                                        <td class="td-fit">
                                            <span>
                                                <img class="planDetailsBtn icon-lowopacity" plan-id="<?=$planId?>" title="Afficher les détails" src="ressources/icons/search.png" />
                                            </span>
                                            <span>
                                                <?php
                                                /**
                                                 *  Affichage d'une pastille verte ou rouge en fonction du status de la planification
                                                 */
                                                if ($planStatus == "done") {
                                                    echo '<img class="icon-small" src="ressources/icons/greencircle.png" title="Planification terminée" />';
                                                } elseif ($planStatus == "error") {
                                                    echo '<img class="icon-small" src="ressources/icons/redcircle.png" title="Planification en erreur" />';
                                                } elseif ($planStatus == "stopped") {
                                                    echo '<img class="icon-small" src="ressources/icons/redcircle.png" title="Planification stoppée par l\'utilisateur" />';
                                                } ?>
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <?php
                            /**
                             *  Div caché contenant les détails de la planification
                             */
                            echo '<div class="hide detailsDiv" plan-id="' . $planId . '">';
                            /**
                             *  Si la planification est en erreur alors on affiche le message d'erreur
                             */
                            if ($planStatus == "error") {
                                echo "<p>$planError</p>";
                            }

                            if ($planAction == "update") {
                                /**
                                 *  GPG Check
                                 */
                                echo '<div>';
                                    echo '<span>Vérif. des signatures GPG</span>';
                                if ($planGpgCheck == "yes") {
                                    echo '<span><img src="ressources/icons/greencircle.png" class="icon-small" /> Activé</span>';
                                } else {
                                    echo '<span><img src="ressources/icons/redcircle.png" class="icon-small" /> Désactivé</span>';
                                }
                                    echo '</div>';

                                    /**
                                     *  GPG Resign
                                     */
                                    echo '<div>';
                                    echo '<span>Signature des paquets avec GPG</span>';
                                if ($planGpgResign == "yes") {
                                    echo '<span><img src="ressources/icons/greencircle.png" class="icon-small" /> Activé</span>';
                                } else {
                                        echo '<span><img src="ressources/icons/redcircle.png" class="icon-small" /> Désactivé</span>';
                                }
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
                                 *  Notification en cas d'erreur
                                 */
                                echo '<div>';
                                echo '<span>Notification en cas d\'erreur</span>';
                            if ($planNotificationOnError == "yes") {
                                echo '<span><img src="ressources/icons/greencircle.png" class="icon-small" /> Activé</span>';
                            } else {
                                echo '<span><img src="ressources/icons/redcircle.png" class="icon-small" /> Désactivé</span>';
                            }
                                echo '</div>';

                                /**
                                 *  Notification en cas de succès
                                 */
                                echo '<div>';
                                echo '<span>Notification en cas de succès</span>';
                            if ($planNotificationOnSuccess == "yes") {
                                echo '<span><img src="ressources/icons/greencircle.png" class="icon-small" /> Activé</span>';
                            } else {
                                echo '<span><img src="ressources/icons/redcircle.png" class="icon-small" /> Désactivé</span>';
                            }
                                echo '</div>';

                                /**
                                 *  Destinataire mail
                                 */
                            if (!empty($planMailRecipient)) {
                                echo '<div>';
                                    echo '<span>Contact</span>';
                                    echo '<span>';
                                        $planMailRecipient = explode(',', $planMailRecipient);
                                foreach ($planMailRecipient as $recipient) {
                                    if (!empty($recipient)) {
                                        echo $recipient . '<br>';
                                    }
                                }
                                    echo '</span>';
                                echo '</div>';
                            }

                                /**
                                 *  Log
                                 */
                                echo '<div>';
                            if (!empty($planLogfile)) {
                                echo '<span>Log</span>';
                                echo "<span><a href='run.php?logfile=$planLogfile'><button class='btn-xsmall-blue'><b>Voir</b></button></a></></span>";
                            } ?>
                                </div>
                            </div>
                        </div>
                <?php               }
                echo '</div>';
            } ?>
        </section>
    </section>

    <section class="mainSectionLeft">
        <section class="left reposList">
            <?php include_once('../includes/repos-list-container.inc.php'); ?>
        </section>
    </section>
</article>

<?php include_once('../includes/footer.inc.php'); ?>
</body>
<script>
$(document).ready(function(){

    var selectDateName = '#addPlanDate';
    var dateSpan = '#update-preview-date';
    var selectEnvName = '#addPlanTargetEnv';
    var envSpan = '#update-preview-target-env';

    function printDate() {
        /**
         *  Récupération de la date sélectionnée dans la liste
         */
        var selectValue = $(selectDateName).val();

        /**
         *  Si aucune date n'a été selectionnée par l'utilisateur alors on n'affiche rien 
         */
        if (selectValue == "") {
            $("#update-preview").hide();
        
        /**
         *  Sinon on affiche l'environnement qui pointe vers le nouveau snapshot qui sera créé
         */
        } else {
            $("#update-preview").show()
            $(dateSpan).html(selectValue);
        }
    }

    function printEnv() {
        /**
         *  Nom du dernier environnement de la chaine
         */
        var lastEnv = '<?=LAST_ENV?>';

        /**
         *  Récupération de l'environnement sélectionné dans la liste
         */
        var selectValue = $(selectEnvName).val();
        
        /**
         *  Si l'environnement correspond au dernier environnement de la chaine alors il sera affiché en rouge
         */
        if (selectValue == lastEnv) {
            var envSpanClass = 'last-env';

        } else {            
            var envSpanClass = 'env';
        }

        /**
         *  Si aucun environnement n'a été selectionné par l'utilisateur alors on n'affiche rien 
         */
        if (selectValue == "") {
            $(envSpan).html('');
        
        /**
         *  Sinon on affiche l'environnement qui pointe vers le nouveau snapshot qui sera créé
         */
        } else {
            $(envSpan).html('⟵<span class="'+envSpanClass+'">'+selectValue+'</span>');
        }
    }

    printDate();
    printEnv();

    $(document).on('change',selectDateName+','+selectEnvName,function(){
        printDate();
        printEnv();
  
    }).trigger('change');

});
</script>
</html>