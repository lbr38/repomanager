// cpuWorker.js
async function fetchCpuUsage() {
    try {
        // Create a FormData object to send the action and controller via POST
        const formData = new FormData();
        formData.append('action', 'get-cpu-usage');
        formData.append('controller', 'system');

        // Send a POST request to the server to get CPU usage
        const response = await fetch('/ajax/controller.php', {
            body: formData,
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest', // Ensure the request is recognized as AJAX
            }
        });

        const data = await response.json();

        if (!response.ok) {
            return null;
        }

        return data.message;
    } catch (error) {
        return null;
    }
}

async function loop() {
    const cpuUsage = await fetchCpuUsage();
    postMessage({ cpuUsage });

    // Wait 30 seconds before the next fetch
    setTimeout(loop, 30000);
}

// Start the loop
loop();
