class Cookie {
    /**
     * Get cookie by name
     * @param {*} cname
     * @returns
     */
    get(cname)
    {
        let name = cname + "=";
        let decodedCookie = decodeURIComponent(document.cookie);
        let ca = decodedCookie.split(';');

        for (let i = 0; i <ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) == ' ') {
                c = c.substring(1);
            }
            if (c.indexOf(name) == 0) {
                return c.substring(name.length, c.length);
            }
        }

        return "";
    }

    /**
     * Set cookie value
     * @param {*} cname
     * @param {*} cvalue
     * @param {*} exdays
     * @returns
     */
    set(cname, cvalue, exdays)
    {
        const d = new Date();
        d.setTime(d.getTime() + (exdays*24*60*60*1000));
        let expires = "expires="+ d.toUTCString();

        // If https, also set Secure flag
        document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/" + (location.protocol === 'https:' ? ';Secure' : '');
    }

    /**
     * Return true if cookie exists, false otherwise
     * @param {*} cname
     * @returns
     */
    exists(cname)
    {
        if (this.get(cname) == "") {
            return false;
        }

        return true;
    }
}
