class Table {
    /**
     * Reload table content
     * @param {*} table
     * @param {*} offset
     */
    reload(table, offset = 0)
    {
        return new Promise((resolve, reject) => {
            try {
                mylayout.printLoading();

                ajaxRequest(
                    // Controller:
                    'general',
                    // Action:
                    'getTable',
                    // Data:
                    {
                        table: table,
                        offset: offset,
                        sourceUrl: window.location.href,
                        sourceUri: window.location.pathname,
                        sourceGetParameters: getGetParams()
                    },
                    // Print success alert:
                    false,
                    // Print error alert:
                    true
                ).then(() => {
                    // Replace table with itself, with new content
                    $('.reloadable-table[table="' + table + '"]').replaceWith(jsonValue.message);

                    // Hide loading icon
                    mylayout.hideLoading();

                    // Resolve promise
                    resolve('Table reloaded');
                });
            } catch (error) {
                // Reject promise
                reject('Failed to reload table');
            }
        });
    }
}
