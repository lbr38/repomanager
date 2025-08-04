/**
 *  Add new environment
 */
function addEnv()
{
    var name = $('input[type="text"][name="add-env-name"]').val();
    var color = $('input[type="color"][name="add-env-color"]').val();

    ajaxRequest(
        // Controller:
        'environment',
        // Action:
        'add-env',
        // Data:
        {
            name: name,
            color: color
        },
        // Print success alert:
        true,
        // Print error alert:
        true
    ).then(function () {
        mylayout.reloadContentById('envs-div');
    });
}

/**
 *  Edit environments color and name
 */
function editEnv()
{
    var envs = [];

    $('#current-envs-div').find('.env-line').each(function () {
        var name = $(this).find('input[type="text"][name="env-name"]').val();
        var color = $(this).find('input[type="color"][name="env-color"]').val();

        envs.push({
            name: name,
            color: color
        });
    });

    ajaxRequest(
        // Controller:
        'environment',
        // Action:
        'edit-env',
        // Data:
        {
            envs: envs,
        },
        // Print success alert:
        true,
        // Print error alert:
        true
    ).then(function () {
        mylayout.reloadContentById('envs-div');
    });
}
