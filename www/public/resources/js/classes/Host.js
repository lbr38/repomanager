class Host
{
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
