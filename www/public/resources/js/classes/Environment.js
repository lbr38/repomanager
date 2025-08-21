class Environment
{
    /**
     * Upodate environment description
     * @param {*} id
     * @param {*} description
     */
    updateDescription(id, description)
    {
        ajaxRequest(
            // Controller:
            'repo/environment',
            // Action:
            'update-description',
            // Data:
            {
                envId: id,
                description: description
            },
            // Print success alert:
            true,
            // Print error alert:
            true
        );
    }
}
