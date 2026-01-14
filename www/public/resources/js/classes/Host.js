class Host
{
    /**
     * Get the action box for a list of hosts
     * @param {*} hosts
     */
    getActionBox()
    {
        var hosts = [];

        // The buttons that will be displayed in the confirm box
        var buttons = [];

        /**
         *  The list of allowed actions the user can execute on the selected hosts
         *  By default: all, unless the user has specific permissions
         *  Those permissions are later verified by the server so even if the user tries to execute an action he is not allowed to, it will not work
         */
        var allowedActions = ['request-general-infos', 'request-packages-infos', 'update-packages', 'reset', 'delete'];

        // Get all checked checkbox
        $('input[type="checkbox"][name="checkbox-host[]"]').each(function () {
            // If checkbox is checked then add host id to hosts
            if (this.checked) {
                hosts.push($(this).val());
            }
        });

        // If no hosts are selected, close the confirmation box and exit
        if (hosts.length == 0) {
            myconfirmbox.close();
            return;
        }

        // Get permissions from cookie
        if (mycookie.exists('user_permissions')) {
            var userPermissions = JSON.parse(mycookie.get('user_permissions'));

            // Reset allowed actions array
            var allowedActions = [];

            // Loop through all permissions and check if the user has the permission to execute the action
            if (userPermissions.hosts && userPermissions.hosts['allowed-actions'] && userPermissions.hosts['allowed-actions']) {
                var allowedActions = userPermissions.hosts['allowed-actions'];
            }
        }

        // Define confirm box buttons depending on the allowed actions
        if (allowedActions.includes('request-general-infos')) {
            buttons.push(
                {
                    'text': 'Request general informations',
                    'color': 'blue-alt',
                    'callback': function () {
                        executeAction('request-general-infos', hosts);
                    }
                }
            );
        }

        if (allowedActions.includes('request-packages-infos')) {
            buttons.push(
                {
                    'text': 'Request packages informations',
                    'color': 'blue-alt',
                    'callback': function () {
                        executeAction('request-packages-infos', hosts);
                    }
                }
            );
        }

        if (allowedActions.includes('update-packages')) {
            buttons.push(
                {
                    'text': 'Update packages',
                    'color': 'blue-alt',
                    'callback': function () {
                        mypanel.get('hosts/requests/update-packages', {
                            hostsId: hosts
                        });
                    }
                }
            );
        }

        // Export to CSV is always allowed by default
        buttons.push(
            {
                'text': 'Export to CSV',
                'color': 'blue-alt',
                'callback': function () {
                    myhost.export(hosts);
                }
            }
        );

        if (allowedActions.includes('reset')) {
            buttons.push(
                {
                    'text': 'Reset',
                    'color': 'red',
                    'callback': function () {
                        executeAction('reset', hosts);
                    }
                }
            );
        }

        if (allowedActions.includes('delete')) {
            buttons.push(
                {
                    'text': 'Delete',
                    'color': 'red',
                    'callback': function () {
                        executeAction('delete', hosts);
                    }
                }
            );
        }

        myconfirmbox.print(
            {
                'title': 'Execute an action',
                'message': hosts.length + ' host' + (hosts.length > 1 ? 's' : '') + ' selected',
                'id': 'hosts-actions-confirm-box',
                'buttons': buttons
            }
        );
    }

    /**
     * Export a list of hosts to a CSV format
     * @param {*} hosts
     */
    export(hosts)
    {
        myalert.print('Exporting hosts...');

        ajaxRequest(
            // Controller:
            'host/export',
            // Action:
            'export',
            // Data:
            {
                hosts: hosts,
            },
            // Print success alert:
            false,
            // Print error alert:
            true
        ).then(function () {
            // Convert the JSON response to CSV format, replacing null values with empty strings and escaping quotes
            const lines = JSON.parse(jsonValue.message).map(row => row.map(field => `"${(field ?? '').toString().replace(/"/g,'""')}"`).join(','));

            // Join the lines with newline characters
            const csv = lines.join('\n');
            const blob = new Blob(["\uFEFF" + csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);

            // Create a link to download the CSV file
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', 'hosts.csv');
            document.body.appendChild(link);

            // Trigger the download
            link.click();

            // Clean up
            document.body.removeChild(link);
    });
}
}
