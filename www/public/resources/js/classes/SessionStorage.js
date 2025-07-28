class SessionStorage
{
    /**
     * Remove key(s) from session storage that start with a specific pattern
     * @param {*} pattern
     */
    removeStartWith(pattern)
    {
        if (typeof(Storage) === "undefined") {
            console.error("Session storage is not supported in this browser.");
            return;
        }

        const checkboxElements = Object.keys(sessionStorage).filter(function (key) {
            return key.startsWith(pattern);
        });

        // Iterate over the filtered keys and remove them from session storage
        checkboxElements.forEach(function (key) {
            sessionStorage.removeItem(key);
        });
    }
}
