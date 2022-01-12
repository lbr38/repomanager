$(document).ready(function(){
    /**
     *  Script Select2 pour transformer un select multiple en liste déroulante
     */
    $('#planReminderSelect, #planDayOfWeekSelect').select2({
        closeOnSelect: false,
        placeholder: 'Sélectionner...'
    });
});

/**
 *  Rechargement de la div des planifications
 *  Recharge les menus select2 en même temps
 */
 function reloadPlanDiv(){
    $("#planDiv").load(" #planDiv > *",function(){
        $('#planReminderSelect, #planDayOfWeekSelect').select2({
            closeOnSelect: false,
            placeholder: 'Sélectionner...'
        });
    });
}

/**
 *  Events listeners
 */

/**
 *  Event : affichage des détails d'une planification
 */
$(document).on('click','.planDetailsBtn',function(){
    /**
     *  Récupération de l'Id de la planification
     */
    var planId = $(this).attr('plan-id');
    /**
     *  Affichage du div portant cet Id
     */
    $('.planDetailsDiv[plan-id='+planId+']').toggle(100);
});

/**
 *  Premier chargement de la page planification : Affiche les inputs supplémentaires en fonction du type de planification sélectionné par défaut
 */
$(document).ready(function(){
    $(".__plan_input").show();
    $(".__regular_plan_input").hide();
});

/**
 *  Event : Puis à chaque changement d'état, affiche ou masque les inputs supplémentaires en fonction de ce qui est coché
 */
$(document).on('change','input:radio[name="planType"]',function(){
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
        $(".__regular_plan_input").show();
        $(".__plan_input").hide();
    }
});

/**
 *  Event : Si on a sélectionné : planification récurrente tous les jours, alors on fait apparaitre l'input de l'heure pour pouvoir renseigner l'heure
 */
$(document).on('change','#planFrequencySelect',function(){
    /**
     *  Si la fréquence sélectionnée est "toutes les heures"
     */
    if ($("#planFrequency-every-hour").is(":selected")) {
        $(".__regular_plan_day_input").hide();
        $(".__plan_hour_input").hide();
    }
    /**
     *  Si la fréquence sélectionnée est "tous les jours"
     */
    if ($("#planFrequency-every-day").is(":selected")) {
        $(".__regular_plan_day_input").hide();
        $(".__plan_hour_input").show();
    }
    /**
     *  Si la fréquence sélectionnée est "toutes les semaines"
     */
    if ($("#planFrequency-every-week").is(":selected")) {
        $(".__regular_plan_day_input").show();
        $(".__plan_hour_input").show();
        $(".__plan_input_reminder").show();
    }
}).trigger('change');


/**
 *   Event : Afficher des boutons radio supplémentaires si l'option du select sélectionnée est '#updateRepoSelect' afin de choisir si on souhaite activer gpg check et resigner les paquets
 */
$(document).on('change','#planActionSelect',function(){
    if ($("#updateRepoSelect").is(":selected")) {
        $(".__plan_gpg_input").show();
    } else {
        $(".__plan_gpg_input").hide();
    }
}).trigger('change');

/**
 *  Event : Création d'une planification
 */
$(document).on('submit','#newPlanForm',function(){
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
    var repo = $("#addPlanRepoId").val();
    var group = $("#addPlanGroupId").val();
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

    newPlan(type, day, date, time, frequency, action, repo, group, gpgCheck, gpgResign, mailRecipient, reminder, notificationOnError, notificationOnSuccess);

    return false;
});


/**
 *  Event : Suppression d'une planification
 */
 $(document).on('click','.deletePlanButton',function(){
    event.preventDefault();

    var planId = $(this).attr('plan-id');
    var planType = $(this).attr('plan-type');

    if (planType == 'plan') {
        deleteConfirm('Êtes vous sûr de vouloir supprimer cette planification ?', function(){deletePlan(planId)});
    }
    if (planType == 'regular') {
        deleteConfirm('Êtes vous sûr de vouloir supprimer cette tâche récurrente ?', function(){deletePlan(planId)});
    }

    return false;
});


/**
 * Ajax: Créer une nouvelle planification
 * @param {string} type 
 */
 function newPlan(type, day, date, time, frequency, planAction, repo, group, gpgCheck, gpgResign, mailRecipient, reminder, notificationOnError, notificationOnSuccess) {
    $.ajax({
        type: "POST",
        url: "controllers/ajax.php",
        data: {
            action: "newPlan",
            type: type,
            day: day,
            date: date,
            time: time,
            frequency: frequency,
            planAction: planAction,
            repo: repo,
            group: group,
            gpgCheck: gpgCheck,
            gpgResign: gpgResign,
            mailRecipient: mailRecipient,
            reminder: reminder,
            notificationOnError: notificationOnError,
            notificationOnSuccess: notificationOnSuccess
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            /**
             *  Affichage d'une alerte success et rechargement de la liste des planifications
             */
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
 function deletePlan(id) {
    $.ajax({
        type: "POST",
        url: "controllers/ajax.php",
        data: {
            action: "deletePlan",
            id: id
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            /**
             *  Affichage d'une alerte success et rechargement de la liste des planifications
             */
            printAlert(jsonValue.message, 'success');
            reloadPlanDiv();
        },
        error : function (jqXHR, ajaxOptions, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });   
}