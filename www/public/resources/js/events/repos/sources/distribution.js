/**
 *  Event: Edit source repo distribution
 */
$(document).on('submit','form.source-repo-edit-distribution',function () {
    event.preventDefault();
    
    var id = $(this).attr('source-id');
    var distribution = $(this).attr('distribution');
    var params = {};

    /**
     *  Retrieve the parameters entered by the user and push them into the object
     */
    $('form.source-repo-edit-distribution[source-id="' + id + '"][distribution="' + distribution + '"]').find('.distribution-param').each(function () {
        var name = $(this).attr('param-name');
        var value = $(this).val();

        params[name] = value;
    });

    console.log(params);

    ajaxRequest(
        // Controller:
        'source',
        // Action:
        'distribution/edit',
        // Data:
        {
            id: id,
            distribution: distribution,
            params: params
        },
        // Print success alert:
        true,
        // Print error alert:
        true,
        // Reload containers:
        [],
        // Execute functions on success:
        []
    );

    return false;
});