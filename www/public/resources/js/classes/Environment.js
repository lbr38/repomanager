class Environment
{
    /**
     *  Add a new environment
     */
    add()
    {
        ajaxRequest(
            // Controller:
            'environment',
            // Action:
            'add',
            // Data:
            {
                name: $('input[type="text"][name="add-env-name"]').val(),
                color: $('input[type="color"][name="add-env-color"]').val()
            },
            // Print success alert:
            true,
            // Print error alert:
            true
        ).then(function () {
            mylayout.reloadContentById('envs-container');
        });
    }

    /**
     *  Edit environments
     */
    edit()
    {
        var envs = [];

        $('div#current-envs').find('.env-line').each(function () {
            envs.push({
                name: $(this).find('input[type="text"][name="env-name"]').val(),
                color: $(this).find('input[type="color"][name="env-color"]').val(),
                protected: $(this).find('img.env-protection').attr('protected')
            });
        });

        ajaxRequest(
            // Controller:
            'environment',
            // Action:
            'edit',
            // Data:
            {
                envs: envs,
            },
            // Print success alert:
            true,
            // Print error alert:
            true
        ).then(function () {
            mylayout.reloadContentById('envs-container');
        });
    }

    /**
     *  Delete an environment
     */
    delete(id)
    {
        ajaxRequest(
            // Controller:
            'environment',
            // Action:
            'delete',
            // Data:
            {
                id: id,
            },
            // Print success alert:
            true,
            // Print error alert:
            true
        ).then(function () {
            mylayout.reloadContentById('envs-container');
        });
    }
}
