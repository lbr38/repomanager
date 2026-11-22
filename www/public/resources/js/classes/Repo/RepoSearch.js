/**
 *  Repositories search input (see Search for the generic part: parsing, suggestions, URL, clear button)
 */
class RepoSearch extends Search
{
    static selectors = {
        input: '#search-repo-input',
        clear: '#search-repo-clear',
        suggestions: '#search-repo-suggestions',
        count: '#search-repo-count',
        item: '.repo-item'
    };

    /**
     * Repository filters: filter name => repository card HTML attribute
     * 'env' is not an attribute, the environments are read from the snapshots of the card (see getValues())
     */
    static filters = {
        'name': 'data-name',
        'dist': 'data-dist',
        'component': 'data-section',
        'releasever': 'data-releasever',
        'type': 'data-type',
        'package-type': 'data-package-type',
        'description': 'data-description',
        'env': 'env',
        'tag': 'data-tags'
    };

    /**
     * Attributes holding a list of values (mirrors can have several distributions and components)
     * Tags and environments must match exactly (env=prod must not match preprod)
     */
    static listAttributes = {
        'data-dist': ' ',
        'data-section': ' ',
        'data-tags': ','
    };
    static exactAttributes = ['data-tags', 'env'];

    static itemName = ['repository', 'repositories'];

    /**
     * Get the values of a repository card attribute, or the environments of its snapshots
     * @param {jQuery} item
     * @param {string} attribute
     * @returns {array}
     */
    static getValues(item, attribute)
    {
        if (attribute === 'env') {
            return item.find('.snap-env-container').map(function () {
                return $(this).attr('env');
            }).get();
        }

        return super.getValues(item, attribute);
    }

    /**
     * Show the repositories matching the search (and their rows and groups), hide the others
     * @param {object} query
     */
    static filter(query)
    {
        const items = $(RepoSearch.selectors.item);
        const active = RepoSearch.isActive(query);
        let count = 0;

        // No search: show all groups and repositories
        if (!active) {
            $('.repos-list-group, .repos-row, .repo-item-wrapper, .repo-item, .group-repo-name').show();
            RepoSearch.printCount(0, items.length, false);
            return;
        }

        // First, hide all groups and repo items (hide the wrapper too, otherwise
        // it stays in the grid flow as an empty cell and shifts the visible cards)
        $('.repos-list-group, .repos-row, .repo-item-wrapper, .repo-item, .group-repo-name').hide();

        items.each(function () {
            if (!RepoSearch.matches($(this), query)) {
                return;
            }

            count++;

            $(this).show();
            $(this).closest('.repo-item-wrapper').show();
            $(this).closest('.repos-row').show();
            $(this).closest('.repos-row').prev('.group-repo-name').show();
            // Reveal ALL ancestor groups (they can be nested), not just the closest one
            $(this).parents('.repos-list-group').show();
        });

        RepoSearch.printCount(count, items.length, true);
    }
}
