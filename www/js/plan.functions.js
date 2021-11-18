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
$(document).on('change','input:radio[name="addPlanType"]',function(){
    /**
     *  Cas où il s'agit d'une planification
     */
    if ($("#planType_plan").is(":checked")) {       
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
 *  Si on a sélectionné : planification récurente tous les jours, alors on fait apparaitre l'input de l'heure pour pouvoir renseigner l'heure
 */
$(document).on('change','#planFrequencySelect',function(){
    if ($("#planFrequency-every-hour").is(":selected")) {
        $(".__plan_hour_input").hide();
    } else {
        $(".__plan_hour_input").show();
    }
}).trigger('change');