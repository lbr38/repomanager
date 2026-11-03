/**
 *  Show / hide the new mirror form fields according to the selected package type
 *  Mirror form uses its own attribute (mirror-field) to not interfere with the repositories form
 */
function mirrorFormPrintFields()
{
    const form = $('.task-form-params[domain="mirror"][action="create"]');
    const packageType = form.find('input:radio[name="mirror-package-type"]:checked').val();

    const accents = { 'deb': 'form-block-accent-red', 'rpm': 'form-block-accent-blue', 'arch': 'form-block-accent-cyan' };

    form.removeClass('form-block-accent-blue form-block-accent-red form-block-accent-cyan').addClass(accents[packageType]);
    form.find('[mirror-field]').hide();
    form.find('[mirror-field~="' + packageType + '"]').show();
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
 *  Event: on arch source selection, populate repositories and architectures with the values of the source
 */
$(document).on('change', '.task-form-params[domain="mirror"] select[param-name="source"][package-type="arch"]', function () {
    const source = $(this).val();

    if (!source) {
        return;
    }

    const fields = {
        'repos': ['repos', 'Select or type repository(ies)'],
        'archs': ['arch', 'Select or type architecture(s)']
    };

    $.each(fields, function (field, [paramName, placeholder]) {
        ajaxRequest(
            // Controller:
            'repo/source/arch',
            // Action:
            'get-predefined',
            // Data:
            {
                field: field,
                source: source
            },
            // Print success alert:
            false,
            // Print error alert:
            true
        ).then(function () {
            myselect2.update('.task-form-params[domain="mirror"] select[param-name="' + paramName + '"][package-type="arch"]', jsonValue.message, placeholder, true);
        });
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
 *  The confirm box is not closed: callers print the mirrors confirm box right after
 */
function mirrorClearReposSelection()
{
    if (typeof clearReposSelection !== 'function') {
        return;
    }

    const selectedSnapshots = $('.mirror-snap-container.snap-selected');
    const selectedEnvs = $('.mirror-env-container.env-selected');

    clearReposSelection(false);

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
    mypanel.replace('repos/new', 'mirrors/new');
});

/**
 *  Event: Arch Linux repositories can only be mirrors, the 'arch' package type of the new repository form opens the mirror creation form
 */
$(document).on('change', 'input:radio[name="package-type"][value="arch"]', function () {
    mypanel.replace('repos/new', 'mirrors/new').then(function () {
        $('.slide-panel-container[slide-panel="mirrors/new"] input:radio[name="mirror-package-type"][value="arch"]').prop('checked', true).trigger('change');
    });
});

/**
 *  Event: the 'Curated' and 'Local' types of the mirror creation form open the new repository form
 */
$(document).on('change', 'input:radio[name="mirror-repo-type"]', function () {
    const repoType = $(this).val();

    if (repoType == 'mirror') {
        return;
    }

    mypanel.replace('mirrors/new', 'repos/new').then(function () {
        $('.slide-panel-container[slide-panel="repos/new"] input:radio[name="repo-type"][value="' + repoType + '"]').prop('checked', true).trigger('change');
    });
});

/**
 *  Print the confirm box with the available actions for the selected mirrors snapshots
 */
function mirrorPrintSnapshotsActions()
{
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

    // Sync creates a new snapshot of each mirror of the selected snapshots
    if (myrepopermission.allowedAction('update')) {
        buttons.push({
            'text': 'Sync',
            'callback': function () {
                mirrorClearSelection();
                mypanel.get('mirrors/update', {
                    'snapshots': JSON.stringify(selected)
                });
            }
        });
    }

    // Install is always allowed, as for repositories
    buttons.push({
        'text': 'Install',
        'callback': function () {
            mirrorClearSelection();
            mypanel.get('mirrors/install', {
                'snapshots': JSON.stringify(selected)
            });
        }
    });

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
}

/**
 *  Print the confirm box with the remove action for the selected mirrors environments
 */
function mirrorPrintEnvsActions()
{
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
}

/**
 *  Selecting a repository snapshot or environment while mirrors snapshots or environments are selected clears the mirrors
 *  selection: ask the user to confirm first, the click is only replayed if they continue
 *  Capture phase listener, so that the click is stopped before the repositories handlers are executed
 */
let mirrorSelectionGuardBypass = false;

document.addEventListener('click', function (e) {
    if (mirrorSelectionGuardBypass || $(e.target).closest('a').length) {
        return;
    }

    const target = $(e.target).closest('.snap-container:not(.mirror-snap-container), input[name="checkbox-repo"], .snap-env-container:not(.mirror-env-container)');

    if (target.length == 0) {
        return;
    }

    const selectedSnapshots = $('.mirror-snap-container.snap-selected').length;
    const selectedEnvs = $('.mirror-env-container.env-selected').length;

    if (selectedSnapshots + selectedEnvs == 0) {
        return;
    }

    e.preventDefault();
    e.stopPropagation();

    const selection = selectedSnapshots > 0
        ? selectedSnapshots + ' mirror snapshot' + (selectedSnapshots > 1 ? 's are' : ' is')
        : selectedEnvs + ' mirror environment' + (selectedEnvs > 1 ? 's are' : ' is');

    myconfirmbox.print({
        'title': 'Clear selection',
        'message': selection + ' selected. Selecting a repository will clear this selection, do you want to continue?',
        'id': 'mirror-clear-selection-confirm-box',
        'buttons': [
            {
                'text': 'Continue',
                // The replayed click prints the repositories confirm box, closing this one would remove it at the end of the close animation
                'closeBox': false,
                'callback': function () {
                    mirrorClearSelection();
                    mirrorSelectionGuardBypass = true;
                    target[0].click();
                    mirrorSelectionGuardBypass = false;
                }
            },
            {
                'text': 'Cancel',
                'closeBox': false,
                'callback': function () {
                    if (selectedSnapshots > 0) {
                        mirrorPrintSnapshotsActions();
                    } else {
                        mirrorPrintEnvsActions();
                    }
                }
            }
        ]
    });
}, true);

/**
 *  Same as above the other way round: selecting a mirror snapshot or environment while repositories snapshots or environments
 *  are selected clears the repositories selection, ask the user to confirm first
 */
document.addEventListener('click', function (e) {
    if (mirrorSelectionGuardBypass || $(e.target).closest('a').length) {
        return;
    }

    const target = $(e.target).closest('.mirror-snap-container, .mirror-env-container');

    if (target.length == 0) {
        return;
    }

    // Protected environments cannot be selected, the click does nothing
    if (target.hasClass('mirror-env-container') && (target.attr('data-protected') == 'true' || !myrepopermission.allowedAction('removeEnv'))) {
        return;
    }

    const list = $('#repositories-list');
    const selectedSnapshots = list.find('input[name="checkbox-repo"]:checked');
    const selectedEnvs = list.find('.select-env-checkbox:checked');

    if (selectedSnapshots.length + selectedEnvs.length == 0) {
        return;
    }

    e.preventDefault();
    e.stopPropagation();

    const selection = selectedSnapshots.length > 0
        ? selectedSnapshots.length + ' repository snapshot' + (selectedSnapshots.length > 1 ? 's are' : ' is')
        : selectedEnvs.length + ' repository environment' + (selectedEnvs.length > 1 ? 's are' : ' is');

    myconfirmbox.print({
        'title': 'Clear selection',
        'message': selection + ' selected. Selecting a mirror will clear this selection, do you want to continue?',
        'id': 'repo-clear-selection-confirm-box',
        'buttons': [
            {
                'text': 'Continue',
                // The replayed click clears the repositories selection and prints the mirrors confirm box
                'closeBox': false,
                'callback': function () {
                    mirrorSelectionGuardBypass = true;
                    target[0].click();
                    mirrorSelectionGuardBypass = false;
                }
            },
            {
                'text': 'Cancel',
                'closeBox': false,
                'callback': function () {
                    // Print the repositories confirm box again through their own handlers, the selection is unchanged
                    if (selectedSnapshots.length > 0) {
                        const checkbox = selectedSnapshots.first();

                        checkbox.prop('checked', false);
                        mirrorSelectionGuardBypass = true;
                        checkbox[0].click();
                        mirrorSelectionGuardBypass = false;
                    } else {
                        selectedEnvs.first().trigger('change');
                    }
                }
            }
        ]
    });
}, true);

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

    mirrorPrintSnapshotsActions();
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

    mirrorPrintEnvsActions();
});

/**
 *  Event: open the edit panel of a mirror
 */
$(document).on('click', '.mirror-edit-btn', function () {
    mypanel.get('mirrors/edit', {
        'mirror-id': $(this).attr('mirror-id')
    });
});

/**
 *  Event: submit mirror edit form
 */
$(document).on('submit', '#mirror-edit-form', function (e) {
    e.preventDefault();

    const form = $(this);

    ajaxRequest(
        // Controller:
        'repo/edit',
        // Action:
        'retention',
        // Data:
        {
            repoId: 'mirror-' + form.attr('mirror-id'),
            retention: form.find('.mirror-edit-param[param-name="retention"]').val(),
            keepAll: form.find('.mirror-edit-param[param-name="retention-keep-all"]').is(':checked') ? 'true' : 'false'
        },
        // Print success alert:
        true,
        // Print error alert:
        true
    );
});

/**
 *  Event: print mirrors installation commands on environment change
 *  Commands are templates in which __ENV__ is replaced by the selected environment
 */
$(document).on('change', '#mirror-install-select-env', function () {
    const env = $(this).val();

    if (env == '') {
        $('#mirror-install-commands-container').hide();
        return;
    }

    $('pre.mirror-install-commands').each(function () {
        $(this).text($(this).attr('template').replaceAll('__ENV__', env));
    });

    printEnv(env, '.mirror-install-env');

    $('#mirror-install-commands-container').show();
});

/**
 *  Event: select deb mirror configuration format (legacy or deb822)
 */
$(document).on('change', 'input[type="radio"].mirror-config-format', function () {
    const mirrorId = $(this).attr('mirror-id');

    $('pre.mirror-install-commands[mirror-id="' + mirrorId + '"]').addClass('hide');
    $('pre.mirror-install-commands[mirror-id="' + mirrorId + '"][format="' + $(this).val() + '"]').removeClass('hide');
});
