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
                    'get-table',
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

                    // Resolve promise
                    resolve('Table reloaded');
                }).catch((e) => {
                    // Log error to console
                    console.error('Failed to reload table ' + table + ': ' + e);

                    // Print error to the user
                    myalert.print(e, 'error');

                    // Reject promise
                    reject('Failed to reload table ' + table + ': ' + e);
                }).finally(() => {
                    // Hide loading icon
                    mylayout.hideLoading();
                });
            } catch (error) {
                // Catch synchronous errors (before the ajax request is sent)

                // Reject promise
                reject('Failed to reload table, synchronous error: ' + error.message);
            }
        });
    }
}
