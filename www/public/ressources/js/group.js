$(document).ready(function(){
	// Script Select2 pour transformer un select multiple en liste déroulante
	$('.reposSelectList').select2({
		closeOnSelect: false,
		placeholder: 'Ajouter un repo...'
	});
});

/**
 *  Fonctions utiles
 */

/**
 *  Rechargement de la div des groupes
 *  Recharge les menus select2 en même temps
 */
function reloadGroupsDiv(){
    $("#groupsDiv").load(" #groupsDiv > *",function(){
        $('.reposSelectList').select2({
            closeOnSelect: false,
            placeholder: 'Ajouter un repo...'
        });
    });
}

/**
 *  Rechargement de la div des groupes, puis affichage de la configuration d'un groupe en particulier
 *  Recharge les menus select2 en même temps
 */
function reloadGroupsDivSlideGroup(groupName){
    $("#groupsDiv").load(" #groupsDiv > *",function(){
        $('.reposSelectList').select2({
            closeOnSelect: false,
            placeholder: 'Ajouter un repo...'
        });

        $("#groupConfigurationDiv-"+groupName).show();
    });
}


/**
 *  Events listeners
 */

/**
 *  Event : Création d'un nouveau groupe
 */
$(document).on('submit','#newGroupForm',function(){
    event.preventDefault();
    /**
     *  Récupération du nom de groupe à créer dans l'input prévu à cet effet
     */
    var name = $("#newGroupInput").val();
    newGroup(name);

    return false;
});

/**
 *  Event : Renommage d'un groupe
 */
$(document).on('submit','.groupForm',function(){
    event.preventDefault();
    /**
     *  Récupération du nom actuel (dans <form>) et du nouveau nom (dans <input> contenant l'attribut groupname="name")
     */
    var name = $(this).attr('groupname');
    var newname = $('input[groupname='+name+'].groupFormInput').val();
    renameGroup(name, newname);

    return false;
});

/**
 *  Event : Suppression d'un groupe
 */
$(document).on('click','.deleteGroupButton',function(){
    var name = $(this).attr('name');
    deleteConfirm('Êtes vous sûr de vouloir supprimer le groupe '+name+' ?', function(){deleteGroup(name)});
});

/**
 *  Event : ajouter / supprimer des repos d'un groupe
 */
$(document).on('submit','.groupReposForm',function(){
    event.preventDefault();
    /**
     *  Récupération du nom du groupe (dans <form>) puis de la liste des repos (dans le <select>)
     */
    var name = $(this).attr('groupname');
    var reposList = $('select[groupname='+name+'].reposSelectList').val();
    
    editGroupRepos(name, reposList);

    return false;
});

/**
 *  Event : Affichage / masquage du div permettant de gérer les groupes
 */
$(document).on('click','#GroupsListToggleButton, #GroupsListCloseButton',function(){
    $("#groupsDiv").slideToggle();
});

/**
 * Event : Afficher la configuration d'un groupe
 * @param {*} name 
 */
$(document).on('click','.groupConfigurationButton',function(){
    var name = $(this).attr('name');
    $('#groupConfigurationDiv-'+name).slideToggle(150);
});

/**
 * Ajax: Créer un nouveau groupe
 * @param {string} name 
 */
function newGroup(name) {
    $.ajax({
        type: "POST",
        url: "controllers/ajax.php",
        data: {
            action: "newGroup",
            name: name,
            type: "repo"
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            /**
             *  Affichage d'une alerte success et rechargement des groupes et de la liste des repos
             */
            printAlert(jsonValue.message, 'success');
            reloadGroupsDiv();
            reloadNewRepoDiv();
            reloadContentByClass('mainSectionLeft');
        },
        error : function (jqXHR, textStatus, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

/**
 * Ajax : Supprimer un groupe
 * @param {string} name 
 */
function deleteGroup(name) {
    $.ajax({
        type: "POST",
        url: "controllers/ajax.php",
        data: {
            action: "deleteGroup",
            name: name,
            type: "repo"
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            /**
             *  Affichage d'une alerte success et rechargement des groupes et de la liste des repos
             */
            printAlert(jsonValue.message, 'success');
            reloadGroupsDiv();
            reloadNewRepoDiv();
            reloadContentByClass('mainSectionLeft');
        },
        error : function (jqXHR, ajaxOptions, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });   
}

/**
 * Ajax: Renommer un groupe
 * @param {string} name 
 */
function renameGroup(name, newname) {
    $.ajax({
        type: "POST",
        url: "controllers/ajax.php",
        data: {
            action: "renameGroup",
            name: name,
            newname : newname,
            type: "repo"
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            /**
             *  Affichage d'une alerte success et rechargement des groupes et de la liste des repos
             */
            printAlert(jsonValue.message, 'success');
            reloadGroupsDiv();
            reloadNewRepoDiv();
            reloadContentByClass('mainSectionLeft');
        },
        error : function (jqXHR, textStatus, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });   
}

/**
 * Ajax: Ajouter ou supprimer des repos d'un groupe
 * @param {string} name 
 */
function editGroupRepos(name, reposList) {
    $.ajax({
        type: "POST",
        url: "controllers/ajax.php",
        data: {
            action: "editGroupRepos",
            name: name,
            reposList : reposList
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            /**
             *  Affichage d'une alerte success et rechargement des groupes et de la liste des repos
             */
            printAlert(jsonValue.message, 'success');
            reloadGroupsDivSlideGroup(name);
            reloadContentByClass('mainSectionLeft');
        },
        error : function (jqXHR, textStatus, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });   
}