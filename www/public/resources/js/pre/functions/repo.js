/**
 *  Get repos size
 */
function getReposSize()
{
    /**
     *  Loop through all repos and get their size
     */
    $('#repos-list-container').find('.item-size').each(function () {
        /**
         *  Get repo Id, snap Id and repo relative path
         */
        var repoId = $(this).attr('repo-id');
        var snapId = $(this).attr('snap-id');
        var path = $(this).attr('repo-relative-path');

        /**
         *  Get repo size
         */
        ajaxRequest(
            // Controller:
            'repo/get',
            // Action:
            'size',
            // Data:
            {
                path: path
            },
            // Print success alert:
            false,
            // Print error alert:
            false
        ).then(function () {
            $("#repos-list-container").find('.item-size[repo-id="' + repoId + '"][snap-id="' + snapId + '"]').html(jsonValue.message);
        }).catch(function () {
            $("#repos-list-container").find('.item-size[repo-id="' + repoId + '"][snap-id="' + snapId + '"]').replaceWith('<img src="/assets/icons/warning.svg" class="icon" title="' + jsonValue.message + '"/>');
        });
    });
}

/**
 *  Get repos tasks status
 */
function getTaskStatus()
{
    /**
     *  Loop through all repos and get their size
     */
    $('#repos-list-container').find('.item-size').each(function () {
        /**
         *  Get repo Id, snap Id
         */
        var repoId = $(this).attr('repo-id');
        var snapId = $(this).attr('snap-id');


        /**
         *  Get repo tasks status
         */
        ajaxRequest(
            // Controller:
            'repo/get',
            // Action:
            'taskStatus',
            // Data:
            {
                repoId: repoId,
                snapId: snapId
            },
            // Print success alert:
            false,
            // Print error alert:
            false
        );
    });
}
