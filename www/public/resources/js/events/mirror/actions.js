/**
 *  Show / hide the new mirror form fields according to the selected package type
 *  Mirror form uses its own attribute (mirror-field) to not interfere with the repositories form
 */
function mirrorFormPrintFields()
{
    const form = $('.task-form-params[domain="mirror"][action="create"]');
    const packageType = form.find('input:radio[name="mirror-package-type"]:checked').val();

    form.removeClass('form-block-accent-blue form-block-accent-red').addClass(packageType == 'deb' ? 'form-block-accent-red' : 'form-block-accent-blue');
    form.find('[mirror-field]').hide();
    form.find('[mirror-field="' + packageType + '"]').show();
}

/**
 *  Event: show / hide new mirror form fields depending on the selected package type
 */
$(document).on('change', 'input:radio[name="mirror-package-type"]', function () {
    mirrorFormPrintFields();
});

/**
 *  Source selection (populating distributions and release versions) is handled by task.js
 *
 *  Release version is a single value in the mirror form, whereas task.js handlers expect multiple values, and
 *  components are named differently: dist and releasever events are stopped at the body level so that only the
 *  handlers below are executed
 */

/**
 *  Event: on release version selection, populate rpm architectures with the predefined values of the source
 */
$('body').on('change', '.task-form-params[domain="mirror"] select[param-name="releasever"]', function (e) {
    e.stopPropagation();

    const source = $('.task-form-params[domain="mirror"] select[param-name="source"][package-type="rpm"]').val();
    const releasever = $(this).val();

    if (!source || !releasever) {
        return;
    }

    ajaxRequest(
        // Controller:
        'repo/source/releasever',
        // Action:
        'get-predefined-architectures',
        // Data:
        {
            source: source,
            releasever: [releasever]
        },
        // Print success alert:
        false,
        // Print error alert:
        true
    ).then(function () {
        myselect2.update('.task-form-params[domain="mirror"] select[param-name="arch"][package-type="rpm"]', jsonValue.message, 'Select architecture(s)');
    });
});

/**
 *  Event: on distribution selection, populate components with the predefined values of the source
 */
$('body').on('change', '.task-form-params[domain="mirror"] select[param-name="dist"]', function (e) {
    e.stopPropagation();

    const source = $('.task-form-params[domain="mirror"] select[param-name="source"][package-type="deb"]').val();
    const distributions = $(this).val();

    if (!source || !distributions || distributions.length == 0) {
        return;
    }

    ajaxRequest(
        // Controller:
        'repo/source/component',
        // Action:
        'get-predefined-components',
        // Data:
        {
            source: source,
            distribution: distributions
        },
        // Print success alert:
        false,
        // Print error alert:
        true
    ).then(function () {
        myselect2.update('.task-form-params[domain="mirror"] select[param-name="components"]', jsonValue.message, 'Select or type component(s)', true);
    });
});

/**
 *  Event: on components selection, populate deb architectures with the predefined values of the source
 */
$(document).on('change', '.task-form-params[domain="mirror"] select[param-name="components"]', function () {
    const source = $('.task-form-params[domain="mirror"] select[param-name="source"][package-type="deb"]').val();
    const distributions = $('.task-form-params[domain="mirror"] select[param-name="dist"]').val();
    const components = $(this).val();

    if (!source || !distributions || distributions.length == 0 || !components || components.length == 0) {
        return;
    }

    ajaxRequest(
        // Controller:
        'repo/source/component',
        // Action:
        'get-predefined-architectures',
        // Data:
        {
            source: source,
            distribution: distributions,
            component: components
        },
        // Print success alert:
        false,
        // Print error alert:
        true
    ).then(function () {
        myselect2.update('.task-form-params[domain="mirror"] select[param-name="arch"][package-type="deb"]', jsonValue.message, 'Select architecture(s)');
    });
});

/**
 *  Execute mirror tasks directly (no form)
 *  Several tasks are grouped under a parent task, as for repositories
 */
function mirrorExecuteTasks(tasks)
{
    tasks.forEach(function (params) {
        params['domain'] = 'mirror';
        params['schedule'] = { 'scheduled': 'false' };
    });

    ajaxRequest(
        // Controller:
        'task',
        // Action:
        'validate-execute',
        // Data:
        {
            taskParams: JSON.stringify(tasks)
        },
        // Print success alert:
        true,
        // Print error alert:
        true
    ).then(function () {
        mycontainer.reload('repos/list');
        mycontainer.reload('repos/kpi');
    });
}

/**
 *  Unselect all mirrors snapshots and environments
 */
function mirrorClearSelection()
{
    $('.mirror-snap-container').removeClass('snap-selected').find('.mirror-snap-checkbox').prop('checked', false);
    $('.mirror-env-container').removeClass('env-selected');
}

/**
 *  Mirrors are listed with repositories, their selection and the repositories selection are mutually exclusive
 *  clearReposSelection() also clears mirrors snapshots and environments, the current mirrors selection is kept
 */
function mirrorClearReposSelection()
{
    if (typeof clearReposSelection !== 'function') {
        return;
    }

    const selectedSnapshots = $('.mirror-snap-container.snap-selected');
    const selectedEnvs = $('.mirror-env-container.env-selected');

    clearReposSelection();

    selectedSnapshots.addClass('snap-selected').find('.mirror-snap-checkbox').prop('checked', true);
    selectedEnvs.addClass('env-selected');
}

/**
 *  Event: selecting a repository snapshot or environment cancels the mirrors selection
 */
$(document).on('click', 'input[name="checkbox-repo"], .snap-env-container:not(.mirror-env-container)', function () {
    mirrorClearSelection();
});

/**
 *  Event: the 'Mirror' type of the new repository form opens the mirror creation form
 */
$(document).on('change', 'input:radio[name="repo-type"][value="mirror"]', function () {
    mypanel.close('repos/new');
    mypanel.get('mirrors/new');
});

/**
 *  Event: the 'Curated' and 'Local' types of the mirror creation form open the new repository form
 */
$(document).on('change', 'input:radio[name="mirror-repo-type"]', function () {
    const repoType = $(this).val();

    if (repoType == 'mirror') {
        return;
    }

    mypanel.close('mirrors/new');
    mypanel.get('repos/new').then(function () {
        $('.slide-panel-container[slide-panel="repos/new"] input:radio[name="repo-type"][value="' + repoType + '"]').prop('checked', true).trigger('change');
    });
});

/**
 *  Event: click on a mirror snapshot to select / unselect it and print the available actions for the selected snapshots
 *  Same behaviour as repositories snapshots: several snapshots, of one or several mirrors, can be selected
 */
$(document).on('click', '.mirror-snap-container', function (e) {
    // Ignore clicks on links and environments
    if ($(e.target).closest('a, .snap-env-container').length) {
        return;
    }

    // Selecting a snapshot cancels any environment and repository selection, as the selections are mutually exclusive
    mirrorClearReposSelection();
    $('.mirror-env-container').removeClass('env-selected');

    $(this).toggleClass('snap-selected');
    $(this).find('.mirror-snap-checkbox').prop('checked', $(this).hasClass('snap-selected'));

    const selected = $('.mirror-snap-container.snap-selected').map(function () {
        return {
            'mirror-id': $(this).attr('mirror-id'),
            'mirror-snap-id': $(this).attr('mirror-snap-id')
        };
    }).get();

    if (selected.length == 0) {
        myconfirmbox.close();
        return;
    }

    const buttons = [];

    // An environment can only point to one snapshot per mirror
    const mirrorIds = selected.map(snapshot => snapshot['mirror-id']);
    const oneSnapshotPerMirror = new Set(mirrorIds).size == mirrorIds.length;

    if (myrepopermission.allowedAction('env') && oneSnapshotPerMirror) {
        buttons.push({
            'text': 'Point an environment',
            'callback': function () {
                mirrorClearSelection();
                mypanel.get('mirrors/env', {
                    'snapshots': JSON.stringify(selected)
                });
            }
        });
    }

    if (myrepopermission.allowedAction('delete')) {
        buttons.push({
            'text': 'Delete',
            'color': 'red',
            'callback': function () {
                mirrorClearSelection();
                mirrorExecuteTasks(selected.map(snapshot => ({
                    'action': 'delete',
                    'mirror-id': snapshot['mirror-id'],
                    'mirror-snap-id': snapshot['mirror-snap-id']
                })));
            }
        });
    }

    let message = selected.length + ' snapshot' + (selected.length > 1 ? 's' : '') + ' selected';

    if (!oneSnapshotPerMirror) {
        message += '. To point an environment, select only one snapshot per mirror';
    }

    myconfirmbox.print({
        'title': 'Execute an action',
        'message': message,
        'id': 'mirror-actions-confirm-box',
        'buttons': buttons
    });
});

/**
 *  Event: click on a mirror environment to select / unselect it and print the remove action for the selected environments
 */
$(document).on('click', '.mirror-env-container', function (e) {
    e.stopPropagation();

    // Protected environments cannot be removed
    if ($(this).attr('data-protected') == 'true' || !myrepopermission.allowedAction('removeEnv')) {
        return;
    }

    // Selecting an environment cancels any snapshot and repository selection, as the selections are mutually exclusive
    mirrorClearReposSelection();
    $('.mirror-snap-container').removeClass('snap-selected').find('.mirror-snap-checkbox').prop('checked', false);

    $(this).toggleClass('env-selected');

    const selected = $('.mirror-env-container.env-selected').map(function () {
        return {
            'action': 'removeEnv',
            'mirror-id': $(this).attr('mirror-id'),
            'env': $(this).attr('env')
        };
    }).get();

    if (selected.length == 0) {
        myconfirmbox.close();
        return;
    }

    myconfirmbox.print({
        'title': 'Remove environment',
        'message': selected.length + ' environment' + (selected.length > 1 ? 's' : '') + ' selected, their URL will no longer be available to clients',
        'id': 'mirror-env-confirm-box',
        'buttons': [
            {
                'text': 'Remove',
                'color': 'red',
                'callback': function () {
                    mirrorClearSelection();
                    mirrorExecuteTasks(selected);
                }
            }
        ]
    });
});

/**
 *  Event: delete a whole mirror
 */
$(document).on('click', '.mirror-delete-btn', function () {
    const mirrorId = $(this).attr('mirror-id');

    mirrorClearSelection();

    myconfirmbox.print({
        'title': 'Delete mirror ' + $(this).attr('mirror-name'),
        'message': 'All snapshots, environments and scheduled tasks of this mirror will be deleted',
        'id': 'mirror-delete-confirm-box',
        'buttons': [
            {
                'text': 'Delete',
                'color': 'red',
                'callback': function () {
                    mirrorExecuteTasks([{
                        'action': 'delete',
                        'mirror-id': mirrorId
                    }]);
                }
            }
        ]
    });
});

/**
 *  Event: open the sync panel of a mirror
 */
$(document).on('click', '.mirror-sync-btn', function () {
    mypanel.get('mirrors/update', {
        'mirror-id': $(this).attr('mirror-id')
    });
});
