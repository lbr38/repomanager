/**
 *  The list of containers that must use Morphdom to update their content
 *  @type {Array}
 */
const containersUsingMorphdom = [
    // 'repos/properties',
    'tasks/log',
    'snapshot/list',
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
 *  Per-container Morphdom rules: element is a CSS selector; key identifies an item
 *  by its attributes. skipIf prevents updates; preserveAttributes and preserveClasses
 *  retain only the configured state while allowing content updates.
 *  @type {Object}
 */
const morphdomSkipRules = {
    'tasks/log': [
        { element: '.task-step-content', key: ['task-id', 'step'], preserveAttributes: ['style'] },
        { element: '.task-step', key: ['task-id', 'step'], preserveClasses: ['step-open'] },
    ],
    'snapshot/list': [
        // Filled client-side: the server always renders them in their initial state
        { element: '#loading-tree, #browse-search-input, #browse-search-results, #explorer', skipIf: 'always' },
    ],
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

    // Refresh the package tree only when the user is not exploring it (search, selection, expanded directory)
    'snapshot/list': function () {
        const explorer = $('#explorer');
        const exploring = $('#browse-search-input').val().trim() !== ''
            || explorer.find('input[type="checkbox"]:checked').length > 0
            || explorer.find('.explorer-toggle').filter(function () {
                return $(this).data('loaded') && $(this).next('ul').css('display') !== 'none';
            }).length > 0;

        if (!exploring) {
            mysnapshot.reloadTree();
        }
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
