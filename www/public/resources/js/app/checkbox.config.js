/**
 * Checkboxes management
 * Define callbacks for each checkbox-id
 * Each callback must define what to do when a checkbox is selected/deselected
 * The callback function can use the id array which contains the values of the selected checkboxes
 */
const checkboxesCallback = {
    // Checkboxes with checkbox-id 'repo-group'
    'repo-group': function (id, count) {
        myconfirmbox.print(
            {
                'title': 'Repository groups',
                'message': count.length + ' group' + (count.length > 1 ? 's' : '') + ' selected',
                'id': 'repo-group-confirm-box',
                'buttons': [
                    {
                        'text': 'Delete',
                        'color': 'red',
                        'callback': function () {
                            ajaxRequest(
                                // Controller:
                                'group',
                                // Action:
                                'delete',
                                // Data:
                                {
                                    id: id,
                                    type: 'repo'
                                },
                                // Print success alert:
                                true,
                                // Print error alert:
                                true
                            ).then(function () {
                                // Reload repos list
                                mycontainer.reload('repos/list');
                                // Reload group panel
                                mypanel.reload('repos/groups/list');
                                // Reload create repo panel
                                mypanel.reload('repos/new');
                            });
                        }
                }
                ]
            }
        );
    },

    // Checkboxes with checkbox-id 'source-repo'
    'source-repo': function (id, count) {
        myconfirmbox.print(
        {
            'title': 'Source repositories',
            'message': count.length + ' source repositor' + (count.length > 1 ? 'ies' : 'y') + ' selected',
            'id': 'source-repo-confirm-box',
            'buttons': [
            {
                'text': 'Delete',
                'color': 'red',
                'callback': function () {
                    ajaxRequest(
                        // Controller:
                        'repo/source/source',
                        // Action:
                        'delete',
                        // Data:
                        {
                            id: id
                        },
                        // Print success alert:
                        true,
                        // Print error alert:
                        true
                    ).then(function () {
                        mypanel.reload('repos/sources/list');
                        mypanel.reload('repos/new');
                    });
                }
            }]
        });
    },

    // Checkboxes with checkbox-id 'gpg-key'
    'gpg-key': function (id, count) {
        myconfirmbox.print(
        {
            'title': 'GPG keys',
            'message': count.length + ' GPG key' + (count.length > 1 ? 's' : '') + ' selected',
            'id': 'gpgkey-confirm-box',
            'buttons': [
            {
                'text': 'Delete',
                'color': 'red',
                'callback': function () {
                    ajaxRequest(
                        // Controller:
                        'repo/source/source',
                        // Action:
                        'delete-gpgkey',
                        // Data:
                        {
                            id: id
                        },
                        // Print success alert:
                        true,
                        // Print error alert:
                        true                        
                    ).finally(function () {
                        mypanel.reload('repos/sources/list');
                    });
                }
            }]
        });
    },

    // Checkboxes with checkbox-id 'host-group'
    'host-group': function (id, count) {
        myconfirmbox.print(
            {
                'title': 'Host groups',
                'message': count.length + ' group' + (count.length > 1 ? 's' : '') + ' selected',
                'id': 'host-group-confirm-box',
                'buttons': [
                    {
                        'text': 'Delete',
                        'color': 'red',
                        'callback': function () {
                            ajaxRequest(
                                // Controller:
                                'group',
                                // Action:
                                'delete',
                                // Data:
                                {
                                    id: id,
                                    type: 'host'
                                },
                                // Print success alert:
                                true,
                                // Print error alert:
                                true
                            ).then(function () {
                                // Reload hosts list
                                mycontainer.reload('hosts/list');
                                // Reload group panel
                                mypanel.reload('hosts/groups/list')
                            });
                        }
                    }
                ]
            }
        );
    },

    // Checkboxes with checkbox-id 'repo-group'
    'profile': function (id, count) {
        myconfirmbox.print(
            {
                'title': 'Profiles',
                'message': count.length + ' profile' + (count.length > 1 ? 's' : '') + ' selected',
                'id': 'profile-confirm-box',
                'buttons': [
                {
                    'text': 'Delete',
                    'color': 'red',
                    'callback': function () {
                        ajaxRequest(
                            // Controller:
                            'profile',
                            // Action:
                            'delete',
                            // Data:
                            {
                                id: id
                            },
                            // Print success alert:
                            true,
                            // Print error alert:
                            true
                        ).then(function () {
                            mypanel.reload('hosts/profiles');
                        });
                    }
                }]
            }
        );
    },

    // Checkboxes with checkbox-id 'scheduled-task'
    'scheduled-task': function (id, count) {
        myconfirmbox.print(
        {
            'title': 'Scheduled tasks',
            'message': count.length + ' task' + (count.length > 1 ? 's' : '') + ' selected',
            'id': 'scheduled-task-confirm-box',
            'buttons': [
            {
                'text': 'Cancel and delete',
                'color': 'red',
                'callback': function () {
                    ajaxRequest(
                        // Controller:
                        'task',
                        // Action:
                        'deleteTask',
                        // Data:
                        {
                            id: id
                        },
                        // Print success alert:
                        true,
                        // Print error alert:
                        true
                    ).then(function () {
                        mycontainer.reload('tasks/list');
                    });
                }
            }]
        });
    },

    // Checkboxes with checkbox-id 'queued-task'
    'queued-task': function (id, count) {
        myconfirmbox.print(
        {
            'title': 'Queued tasks',
            'message': count.length + ' task' + (count.length > 1 ? 's' : '') + ' selected',
            'id': 'queued-task-confirm-box',
            'buttons': [
            {
                'text': 'Cancel',
                'color': 'red',
                'callback': function () {
                    ajaxRequest(
                        // Controller:
                        'task',
                        // Action:
                        'deleteTask',
                        // Data:
                        {
                            id: id
                        },
                        // Print success alert:
                        true,
                        // Print error alert:
                        true
                    ).then(function () {
                        mycontainer.reload('tasks/list');
                    });
                }
            }]
        });
    },
};