groupSelect2();

/**
 *  Functions
 */

/**
 *  Configure select2 for repo groups
 */
function groupSelect2()
{
    $(".slide-panel-container[slide-panel='repo-groups']").find('.reposSelectList').select2({
        closeOnSelect: false,
        placeholder: 'Add repo...'
    });
}


/**
 *  Reload repo groups panel content
 */
function reloadGroupsDiv()
{
    $(".slide-panel-reloadable-div[slide-panel='repo-groups']").load(" .slide-panel-reloadable-div[slide-panel='repo-groups'] > *",function () {
        groupSelect2();
    });
}


/**
 *  Events listeners
 */

/**
 *  Event : Création d'un nouveau groupe
 */
$(document).on('submit','#newGroupForm',function () {
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
$(document).on('submit','.groupForm',function () {
    event.preventDefault();
    /**
     *  Récupération du nom actuel (dans <form>) et du nouveau nom (dans <input> contenant l'attribut groupname="name")
     */
    var name = $(this).attr('groupname');
    var newname = $('input[groupname=' + name + '].groupFormInput').val();
    renameGroup(name, newname);

    return false;
});

/**
 *  Event : Suppression d'un groupe
 */
$(document).on('click','.deleteGroupButton',function () {
    var name = $(this).attr('name');
    confirmBox('Are you sure you want to delete group ' + name + '?', function () {
        deleteGroup(name)});
});

/**
 *  Event : ajouter / supprimer des repos d'un groupe
 */
$(document).on('submit','.groupReposForm',function () {
    event.preventDefault();
    /**
     *  Récupération du nom du groupe (dans <form>) puis de la liste des repos (dans le <select>)
     */
    var name = $(this).attr('groupname');
    var reposId = $('select[groupname=' + name + '].reposSelectList').val();

    editGroupRepos(name, reposId);

    return false;
});

/**
 * Event : Afficher la configuration d'un groupe
 * @param {*} name
 */
$(document).on('click','.groupConfigurationButton',function () {
    var name = $(this).attr('name');
    $('#groupConfigurationDiv-' + name).slideToggle(150);
});

/**
 * Ajax: Créer un nouveau groupe
 * @param {string} name
 */
function newGroup(name)
{
    $.ajax({
        type: "POST",
        url: "ajax/controller.php",
        data: {
            controller: "group",
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
            reloadContentByClass('reposList');
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
function deleteGroup(name)
{
    $.ajax({
        type: "POST",
        url: "ajax/controller.php",
        data: {
            controller: "group",
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
            reloadContentByClass('reposList');
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
function renameGroup(name, newname)
{
    $.ajax({
        type: "POST",
        url: "ajax/controller.php",
        data: {
            controller: "group",
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
            reloadContentByClass('reposList');
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
function editGroupRepos(name, reposId)
{
    $.ajax({
        type: "POST",
        url: "ajax/controller.php",
        data: {
            controller: "group",
            action: "editGroupRepos",
            name: name,
            reposId : reposId
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            /**
             *  Affichage d'une alerte success et rechargement des groupes et de la liste des repos
             */
            printAlert(jsonValue.message, 'success');
            reloadContentByClass('reposList');
        },
        error : function (jqXHR, textStatus, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}