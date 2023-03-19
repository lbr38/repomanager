$(document).ready(function () {
    idToSelect2('#planActionSelect', 'Select action...', true);
    idToSelect2('#planReminderSelect', 'Select reminder...', true);
    idToSelect2('#planDayOfWeekSelect', 'Select day(s)...', true);
    idToSelect2('#addPlanMailRecipient', 'Select recipients...', true);
});

/**
 *  Rechargement de la div des planifications
 *  Recharge les menus select2 en même temps
 */
function reloadPlanDiv()
{
    $("#planDiv").load(" #planDiv > *",function () {
        idToSelect2('#planActionSelect', 'Select action...', true);
        idToSelect2('#planReminderSelect', 'Select...', true);
        idToSelect2('#planDayOfWeekSelect', 'Select...', true);
        idToSelect2('#addPlanMailRecipient', 'Select recipients...', true);
    });
}

/**
 *  Events listeners
 */

/**
 *  Event : affichage des détails d'une planification
 */
$(document).on('click','.planDetailsBtn',function () {
    /**
     *  Récupération de l'Id de la planification
     */
    var planId = $(this).attr('plan-id');
    /**
     *  Affichage du div portant cet Id
     */
    $('.detailsDiv[plan-id=' + planId + ']').slideToggle(100);
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
 *  Event : Suppression d'une planification
 */
$(document).on('click','.deletePlanBtn',function () {
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
 *  Disable recurrent plan
 */
$(document).on('click','.disablePlanBtn',function () {
    var planId = $(this).attr('plan-id');

    confirmBox('Disable recurrent plan execution?', function () {
        disablePlan(planId)}, 'Disable');
});

/**
 *  Enable recurrent plan
 */
$(document).on('click','.enablePlanBtn',function () {
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
        url: "ajax/controller.php",
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
            reloadPlanDiv();
        },
        error : function (jqXHR, textStatus, thrownError) {
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
        url: "ajax/controller.php",
        data: {
            controller: "planification",
            action: "deletePlan",
            id: id
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'success');
            reloadPlanDiv();
        },
        error : function (jqXHR, ajaxOptions, thrownError) {
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
        url: "ajax/controller.php",
        data: {
            controller: "planification",
            action: "disablePlan",
            id: id
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'success');
            reloadPlanDiv();
        },
        error : function (jqXHR, ajaxOptions, thrownError) {
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
        url: "ajax/controller.php",
        data: {
            controller: "planification",
            action: "enablePlan",
            id: id
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'success');
            reloadPlanDiv();
        },
        error : function (jqXHR, ajaxOptions, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}