/**
 *  Events listeners
 */

/**
 *  Event: Create new profile
 */
$(document).on('submit','#newProfileForm',function () {
    event.preventDefault();

    var name = $("#newProfileInput").val();

    newProfile(name);

    return false;
});

/**
 *  Event: Delete profile
 */
$(document).on('click','.profile-delete-btn',function (e) {
    // Prevent parent to be triggered
    e.stopPropagation();

    var id = $(this).attr('profile-id');

    confirmBox('Are you sure you want to delete profile <b>' + name + '</b>?', function () {
        deleteProfile(id)});
});

/**
 *  Event: Duplicate profile
 */
$(document).on('click','.profile-duplicate-btn',function (e) {
    // Prevent parent to be triggered
    e.stopPropagation();

    var id = $(this).attr('profile-id');

    duplicate(id);
});

/**
 *  Event: Print profile configuration
 */
$(document).on('click','.profile-config-btn',function () {
    var id = $(this).attr('profile-id');

    slide('.profile-config-div[profile-id=' + id + ']');
});

/**
 *  Event: Save profile configuration
 */
$(document).on('submit','.profile-config-form',function () {
    event.preventDefault();
    /**
     *  Retrieve profile configuration
     */
    var id = $(this).attr('profile-id');
    var name = $(this).find('input[name=profile-name]').val();
    var reposList = $(this).find('select[name=profile-repos]').val();
    var exclude = $(this).find('select[name=profile-exclude]').val();
    var excludeMajor = $(this).find('select[name=profile-exclude-major]').val();
    var serviceRestart = $(this).find('select[name=profile-service-restart]').val();
    var notes = $(this).find('textarea[name=profile-notes]').val();

    configure(id, name, reposList, exclude, excludeMajor, serviceRestart, notes);

    return false;
});

/**
 * Ajax: Create a new profile
 * @param {string} name
 */
function newProfile(name)
{
    $.ajax({
        type: "POST",
        url: "/ajax/controller.php",
        data: {
            controller: "profile",
            action: "new",
            name: name
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'success');
            reloadContainer('profiles/list');
        },
        error: function (jqXHR, textStatus, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

/**
 * Ajax: Delete a profile
 * @param {string} id
 */
function deleteProfile(id)
{
    $.ajax({
        type: "POST",
        url: "/ajax/controller.php",
        data: {
            controller: "profile",
            action: "delete",
            id: id
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'success');
            reloadContainer('profiles/list');
        },
        error: function (jqXHR, ajaxOptions, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

/**
 * Ajax: Duplicate a profile
 * @param {string} id
 */
function duplicate(id)
{
    $.ajax({
        type: "POST",
        url: "/ajax/controller.php",
        data: {
            controller: "profile",
            action: "duplicate",
            id: id
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'success');
            reloadContainer('profiles/list');
        },
        error: function (jqXHR, textStatus, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

/**
 * Ajax: Modify profile configuration
 * @param {string} id
 * @param {string} name
 * @param {string} reposList
 * @param {string} exclude
 * @param {string} excludeMajor
 * @param {string} serviceRestart
 * @param {string} notes
 */
function configure(id, name, reposList, exclude, excludeMajor, serviceRestart, notes)
{
    $.ajax({
        type: "POST",
        url: "/ajax/controller.php",
        data: {
            controller: "profile",
            action: "configure",
            id: id,
            name: name,
            reposList: reposList,
            exclude: exclude,
            excludeMajor: excludeMajor,
            serviceRestart: serviceRestart,
            notes: notes
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'success');
            reloadContainer('profiles/list');
        },
        error: function (jqXHR, textStatus, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}