/**
 * Sets a cookie.
 * @param {string} name - The name of the cookie.
 * @param {string} value - The value of the cookie.
 * @param {number} [days] - The number of days until the cookie expires.
 */
//setCookie('user', 'John Doe', 7);
function setCookie(name, value, days, time = false) {
    /*let expires = "";
    if (days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toUTCString();
        if (time) {
            value = value + "|expires=" + date.toISOString();
        }
    }
    document.cookie = name + "=" + (value || "") + expires + "; path=/";*/
    let expires = "";
    const date = new Date();
    if (typeof days === 'string' && days.includes('T')) {
        date.setTime(Date.parse(days) + (value * 24 * 60 * 60 * 1000)); // get days date (usually in the past) and add value (days) to that
    } else if (typeof days === 'number') {
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
    }
    expires = "; expires=" + date.toUTCString();
    //console.log(name, value, days, time, expires);
    if (time) {
        value = value + "|expires=" + date.toISOString();
    }
    document.cookie = name + "=" + (value || "") + expires + "; path=/";
}

/**
 * Gets a cookie by name.
 * @param {string} name - The name of the cookie to get.
 * @returns {string|null} The cookie value or null if not found.
 */
//let userValue = getCookie('user'); // Output: John Doe
function getCookie(name) {
    let nameEQ = name + "=";
    let ca = document.cookie.split(';');
    for(let i=0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) === ' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
}

/**
 * Updates an existing cookie. If the cookie does not exist, it creates a new one.
 * @param {string} name - The name of the cookie.
 * @param {string} value - The new value for the cookie.
 * @param {number} [days] - The number of days until the cookie expires.
 */
//updateCookie('user', 'Jane Doe', 7);
function updateCookie(name, value, days) {
    setCookie(name, value, days);
}

/**
 * Deletes a cookie by name.
 * @param {string} name - The name of the cookie to delete.
 */
//deleteCookie('user');
function deleteCookie(name) {
    document.cookie = name+'=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
}

/* Check if cookie is set */
function isCookieSet(name) {
    let nameEQ = name + "=";
    let ca = document.cookie.split(';');
    for(let i = 0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) === ' ') c = c.substring(1);
        if (c.indexOf(nameEQ) === 0) return true;
    }
    return false;
}

function getCookieExpiry(name) {
    let cookie = getCookie(name);
    if (cookie) {
        let parts = cookie.split("|expires=");
        if (parts.length === 2) {
            return new Date(parts[1]);
        }
    }
    return cookie;
}