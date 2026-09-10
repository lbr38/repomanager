/**
 *  The list of containers that must use Morphdom to update their content
 *  @type {Array}
 */
const containersUsingMorphdom = [
    // 'repos/properties',
];

/**
 *  Containers supporting a partial reload.
 *  When the user is actively interacting with such a container (focused element, checked
 *  checkbox, non-empty text input), the whole container is not replaced. Instead, only the
 *  items that are not busy themselves are refreshed, so the user selections and inputs are kept
 *  while the rest of the container stays up to date.
 *
 *  item: selector of the repeated item inside the container
 *  key:  attribute uniquely identifying an item, used to match current and new items
 *  @type {Object}
 */
const containersPartialReload = {
    'repos/list': { item: '.repo-item-wrapper', key: 'repo-id' },
};

/**
 *  Functions executed after a container has been reloaded
 *  @type {Object}
 */
const postReloadFunctions = {
    // Re-apply the current search filter on the repositories that have just been refreshed
    'repos/list': function () {
        myrepo.search();
    },

    // Restore the expanded look of sub-tasks toggle buttons whose sub-tasks were left unfolded before the reload
    'tasks/tasks': function () {
        $('.toggle-subtasks-btn').each(function () {
            var subTasksSelector = '.task-item-children[task-id="' + $(this).attr('task-id') + '"]';

            if (sessionStorage.getItem(subTasksSelector + '/opened') == 'true') {
                $(this).addClass('subtasks-expanded').attr('title', 'Collapse sub-tasks');
            }
        });
    },
};

/**
 *  Default morphdom skip rules, apply to all containers
 *  @type {Array}
 */
const defaultMorphdomSkipRules = [
    { element: 'INPUT[type="checkbox"]', skipIf: 'checked' },
    { element: 'CANVAS', skipIf: 'always' }
];
