/**
 *  Event: Show/hide source repo distribution params
 */
$(document).on('click','.source-repo-distribution-edit-param-btn',function () {
    var id = $(this).attr('source-id');
    var distributionId = $(this).attr('distribution-id');

    getPanel('repos/sources/edit-distribution', {
        id: id,
        distributionId: distributionId
    });
});

/**
 *  Event: add source repository distribution
 */
$(document).on('click','button.source-repo-add-distribution-btn',function () {
    var id = $(this).attr('source-id');
    var name = $('input.source-repo-add-distribution-input[source-id="' + id + '"]').val();

    ajaxRequest(
        // Controller:
        'source',
        // Action:
        'distribution/add',
        // Data:
        {
            id: id,
            name: name
        },
        // Print success alert:
        true,
        // Print error alert:
        true,
        // Reload containers:
        [],
        // Execute functions on success:
        [
            "reloadPanel('repos/sources/list')"
        ]
    );
});

/**
 *  Event: edit source repository distribution
 */
$(document).on('submit','form.source-repo-edit-distribution',function () {
    event.preventDefault();

    var id = $(this).attr('source-id');
    var distributionId = $(this).attr('distribution-id');
    var params = {};

    /**
     *  Retrieve the parameters entered by the user and push them into the object
     */
    $('form.source-repo-edit-distribution[source-id="' + id + '"][distribution-id="' + distributionId + '"]').find('.distribution-param').each(function () {
        var name = $(this).attr('param-name');
        var value = $(this).val();

        params[name] = value;
    });

    ajaxRequest(
        // Controller:
        'source',
        // Action:
        'distribution/edit',
        // Data:
        {
            id: id,
            distributionId: distributionId,
            params: params
        },
        // Print success alert:
        true,
        // Print error alert:
        true,
        // Reload containers:
        [],
        // Execute functions on success:
        [
            "reloadPanel('repos/sources/list')",
            "reloadPanel('repos/sources/edit-releasever', {id: " + id + ", distributionId: " + distributionId + "})"
        ]
    );

    return false;
});

/**
 *  Event: delete source repository distribution
 */
$(document).on('click','.source-repo-remove-distribution-btn',function (e) {
    // Prevent parent to be triggered
    e.stopPropagation();

    var id = $(this).attr('source-id');
    var distributionId = $(this).attr('distribution-id');

    ajaxRequest(
        // Controller:
        'source',
        // Action:
        'distribution/remove',
        // Data:
        {
            id: id,
            distributionId: distributionId,
        },
        // Print success alert:
        true,
        // Print error alert:
        true,
        // Reload containers:
        [],
        // Execute functions on success:
        [
            "reloadPanel('repos/sources/list')"
        ]
    );
});

/**
 *  Event: remove gpg key from distribution
 */
$(document).on('click','.source-repo-edit-distribution-remove-gpgkey-btn',function (e) {
    // Prevent parent to be triggered
    e.stopPropagation();

    var id = $(this).attr('source-id');
    var distributionId = $(this).attr('distribution-id');
    var gpgkey = $(this).attr('gpgkey');

    ajaxRequest(
        // Controller:
        'source',
        // Action:
        'distribution/remove-gpgkey',
        // Data:
        {
            id: id,
            distributionId: distributionId,
            gpgkey: gpgkey,
        },
        // Print success alert:
        true,
        // Print error alert:
        true,
        // Reload containers:
        [],
        // Execute functions on success:
        [
            "reloadPanel('repos/sources/list')",
            "reloadPanel('repos/sources/edit-distribution', {id: " + id + ", distributionId: " + distributionId + "})"
        ]
    );
});