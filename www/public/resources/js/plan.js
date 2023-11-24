/**
 *  Functions
 */
function printDate()
{
    /**
     *  Récupération de la date sélectionnée dans la liste
     */
    var selectValue = $('#addPlanDate').val();

    /**
     *  Si aucune date n'a été selectionnée par l'utilisateur alors on n'affiche rien
     */
    if (selectValue == "") {
        $("#update-preview").hide();

    /**
     *  Sinon on affiche l'environnement qui pointe vers le nouveau snapshot qui sera créé
     */
    } else {
        $("#update-preview").css('display', 'table-row');
        $('#update-preview-date').html(selectValue);
    }
}

function printEnv()
{
    var envSpan = '#update-preview-target-env';

    /**
     *  Nom du dernier environnement de la chaine
     */
    var lastEnv = '<?=LAST_ENV?>';

    /**
     *  Récupération de l'environnement sélectionné dans la liste
     */
    var selectValue = $('#addPlanTargetEnv').val();

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


/**
 *  Events listeners
 */

/**
 *  Event: On date or env change, update the preview
 */
$(document).on('change', '#addPlanDate, #addPlanTargetEnv', function () {
    printDate();
    printEnv();

}).trigger('change');

/**
 *  Event : affichage des détails d'une planification
 */
$(document).on('click','.plan-details-btn',function () {
    /**
     *  Récupération de l'Id de la planification
     */
    var planId = $(this).attr('plan-id');
    /**
     *  Affichage du div portant cet Id
     */
    $('.plan-info-div[plan-id=' + planId + ']').slideToggle(100);
});

/**
 *  Premier chargement de la page planification : Affiche les inputs supplémentaires en fonction du type de planification sélectionné par défaut
 */
$(document).ready(function () {
    $(".__plan_input").show();
    $(".__regular_plan_input").hide();
});

/**
 *  Event : Puis à chaque changement d'état, affiche ou masque les inputs supplémentaires en fonction de ce qui est coché
 */
$(document).on('change','input:radio[name="planType"]',function () {
    /**
     *  Cas où il s'agit d'une planification
     */
    if ($("#addPlanType-plan").is(":checked")) {
        $(".__plan_hour_input").show();
        $(".__regular_plan_input").hide();
        $(".__plan_input").show();
    /**
     *  Cas où il s'agit d'une tâche récurrente
     */
    } else {
        $(".__plan_hour_input").hide();
        $(".__regular_plan_input").css('display', 'table-row');
        $(".__plan_input").hide();
    }
});

/**
 *  Event : Si on a sélectionné : planification récurrente tous les jours, alors on fait apparaitre l'input de l'heure pour pouvoir renseigner l'heure
 */
$(document).on('change','#planFrequencySelect',function () {
    var frequency = $("#planFrequencySelect").val();

    /**
     *  Si la fréquence sélectionnée est "toutes les heures"
     */
    if (frequency == 'every-hour') {
        $(".__regular_plan_day_input").hide();
        $(".__plan_hour_input").hide();
    }
    /**
     *  Si la fréquence sélectionnée est "tous les jours"
     */
    if (frequency == 'every-day') {
        $(".__regular_plan_day_input").hide();
        $(".__plan_hour_input").show();
    }
    /**
     *  Si la fréquence sélectionnée est "toutes les semaines"
     */
    if (frequency == 'every-week') {
        $(".__regular_plan_day_input").show();
        $(".__plan_hour_input").show();
        $(".__plan_input_reminder").show();
    }
}).trigger('change');

/**
 *   Event : Afficher des boutons radio supplémentaires si l'option du select sélectionnée est '#updateRepoSelect' afin de choisir si on souhaite activer gpg check et resigner les paquets
 */
$(document).on('change','#planActionSelect',function () {
    if ($("#updateRepoSelect").is(":selected")) {
        $(".__plan_gpg_input").css('display', 'table-row');
        $(".__plan_difference_input").css('display', 'table-row');
    } else {
        $(".__plan_gpg_input").hide();
        $(".__plan_difference_input").hide();
    }
}).trigger('change');

/**
 *  Event : Création d'une planification
 */
$(document).on('submit','#newPlanForm',function () {
    event.preventDefault();
    /**
     *  Récupération des paramètres de la planification
     */
    if ($("#addPlanType-plan").is(':checked')) {
        var type = 'plan';
    } else {
        var type = 'regular';
    }
    var day = $("#planDayOfWeekSelect").val();
    var date = $("#addPlanDate").val();
    var time = $("#addPlanTime").val();
    var frequency = $("#planFrequencySelect").val();
    var action = $("#planActionSelect").val();
    var snapId = $("#addPlanSnapId").val();
    var groupId = $("#addPlanGroupId").val();
    var targetEnv = $("#addPlanTargetEnv").val();
    var mailRecipient = $("#addPlanMailRecipient").val();
    var reminder = $("#planReminderSelect").val();
    if ($("#addPlanGpgCheck").is(':checked')) {
        var gpgCheck = 'yes';
    } else {
        var gpgCheck = 'no';
    }
    if ($("#addPlanGpgResign").is(':checked')) {
        var gpgResign = 'yes';
    } else {
        var gpgResign = 'no';
    }
    if ($("#onlySyncDifference").is(':checked')) {
        var onlySyncDifference = 'yes';
    } else {
        var onlySyncDifference = 'no';
    }
    if ($("#addPlanNotificationOnError").is(':checked')) {
        var notificationOnError = 'yes';
    } else {
        var notificationOnError = 'no';
    }
    if ($("#addPlanNotificationOnSuccess").is(':checked')) {
        var notificationOnSuccess = 'yes';
    } else {
        var notificationOnSuccess = 'no';
    }

    newPlan(type, day, date, time, frequency, action, snapId, groupId, targetEnv, gpgCheck, gpgResign, onlySyncDifference, mailRecipient, reminder, notificationOnError, notificationOnSuccess);

    return false;
});

/**
 *  Event: Delete planification
 */
$(document).on('click','.deletePlanBtn',function (e) {
    // Prevent parent to be clicked
    e.stopPropagation();

    var planId = $(this).attr('plan-id');
    var planType = $(this).attr('plan-type');

    if (planType == 'plan') {
        confirmBox('Are you sure you want to delete this planification?', function () {
            deletePlan(planId)});
    }
    if (planType == 'regular') {
        confirmBox('Are you sure you want to delete this regular plan task?', function () {
            deletePlan(planId)});
    }
});

/**
 *  Event: Disable recurrent planification
 */
$(document).on('click','.disablePlanBtn',function (e) {
    // Prevent parent to be clicked
    e.stopPropagation();

    var planId = $(this).attr('plan-id');

    confirmBox('Disable recurrent plan execution?', function () {
        disablePlan(planId)}, 'Disable');
});

/**
 *  Event: Enable recurrent planification
 */
$(document).on('click','.enablePlanBtn',function (e) {
    // Prevent parent to be clicked
    e.stopPropagation();

    var planId = $(this).attr('plan-id');

    confirmBox('Enable recurrent plan execution?', function () {
        enablePlan(planId)}, 'Enable');
});


/**
 * Ajax: Créer une nouvelle planification
 * @param {string} type
 */
function newPlan(type, day, date, time, frequency, planAction, snapId, groupId, targetEnv, gpgCheck, gpgResign, onlySyncDifference, mailRecipient, reminder, notificationOnError, notificationOnSuccess)
{
    $.ajax({
        type: "POST",
        url: "/ajax/controller.php",
        data: {
            controller: "planification",
            action: "newPlan",
            type: type,
            day: day,
            date: date,
            time: time,
            frequency: frequency,
            planAction: planAction,
            snapId: snapId,
            groupId: groupId,
            targetEnv: targetEnv,
            gpgCheck: gpgCheck,
            gpgResign: gpgResign,
            onlySyncDifference: onlySyncDifference,
            mailRecipient: mailRecipient,
            reminder: reminder,
            notificationOnError: notificationOnError,
            notificationOnSuccess: notificationOnSuccess
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'success');
            reloadContainer('planifications/queued-running');
        },
        error: function (jqXHR, textStatus, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

/**
 * Ajax : Supprimer une planification
 * @param {string} id
 */
function deletePlan(id)
{
    $.ajax({
        type: "POST",
        url: "/ajax/controller.php",
        data: {
            controller: "planification",
            action: "deletePlan",
            id: id
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'success');
            reloadContainer('planifications/queued-running');
        },
        error: function (jqXHR, ajaxOptions, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

/**
 *  Ajax: Disable recurrent plan
 *  @param {string} id
 */
function disablePlan(id)
{
    $.ajax({
        type: "POST",
        url: "/ajax/controller.php",
        data: {
            controller: "planification",
            action: "disablePlan",
            id: id
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'success');
            reloadContainer('planifications/queued-running');
        },
        error: function (jqXHR, ajaxOptions, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

/**
 *  Ajax: Enable recurrent plan
 *  @param {string} id
 */
function enablePlan(id)
{
    $.ajax({
        type: "POST",
        url: "/ajax/controller.php",
        data: {
            controller: "planification",
            action: "enablePlan",
            id: id
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'success');
            reloadContainer('planifications/queued-running');
        },
        error: function (jqXHR, ajaxOptions, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}