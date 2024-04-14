/**
 * Check if navigator permission's for clipboard is validated
 *
 * @returns {Promise<boolean>}
 */
const permissionsCheck = async () => {
    try {
        let read = await navigator.permissions.query({name: 'clipboard-read'})
        let write = await navigator.permissions.query({name: 'clipboard-write'})
        return write.state === 'granted' && read.state !== 'denied'
    } catch (e) {
        console.error('Permission Check error: ' + e);
    }
}

/**
 * Function to clipboard copy if navigator supports clipboard API
 *
 * @param textToCopy
 */
function copyToClipboard(textToCopy) {
    try {
        /*
        If permission to read the clipboard is granted or if the user will
        be prompted to allow it, we proceed.
         */
        navigator.clipboard.writeText(textToCopy)
            .then(textToCopy => {
                console.info("Text successfully copied: " + textToCopy);

                // If copy to clipboard worked we return true
                return true;
            })
            .catch(err => {
                console.error('Failed to read clipboard contents: ' + err);
            });
    } catch (e) {
        console.error('Error with clipboard-read: ' + e)
    }

    // If copy to clipboard didn't work we return false
    return false;
}

/**
 * Fallback in case the navigator doesn't support clipboard API
 *
 * @param textToCopy
 */
function fallbackCopyTextToClipboard(textToCopy) {
    var textArea = document.createElement("textarea");
    textArea.value = textToCopy;

    // Avoid scrolling to bottom
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.position = "fixed";

    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
        var successful = document.execCommand('copy');

        if (successful) {
            var msg = successful ? 'successful' : 'unsuccessful';
            console.info('Fallback: Copying text command was ' + msg);
        } else {
            console.error('Fallback: Copying text command was ' + msg);
        }
    } catch (err) {
        console.error('Fallback: Oops, unable to copy', err);
    }

    document.body.removeChild(textArea);
    return successful;
    // If copy to clipboard didn't work we return false
}

/**
 * Return the current browser
 *
 * @returns {string}
 */
function browserCheck () {
    var browser = (function (agent) {
        switch (true) {
            case agent.indexOf("edge") > -1:
                return "MS Edge (EdgeHtml)";
            case agent.indexOf("edg") > -1:
                return "MS Edge Chromium";
            case agent.indexOf("opr") > -1 && !!window.opr:
                return "opera";
            case agent.indexOf("chrome") > -1 && !!window.chrome:
                return "chrome";
            case agent.indexOf("trident") > -1:
                return "Internet Explorer";
            case agent.indexOf("firefox") > -1:
                return "firefox";
            case agent.indexOf("safari") > -1:
                return "safari";
            default:
                return "other";
        }
    })(window.navigator.userAgent.toLowerCase());

    return browser;
}

/**
 * reBuild the copy link button to a confirm copied link button
 */
function buildCopiedConfirmationButton () {
    let elementCopyText = document.getElementById("copyLinkButton");
    console.info('Updating copy button element');

    elementCopyText.classList.remove("downloadClipBoard");
    elementCopyText.classList.add("confirmClipBoard");
    elementCopyText.removeAttribute("onclick");
    elementCopyText.innerHTML = 'Link copied';

    console.info('Copy button element updated');
}

/**
 * Init the function to copy to clipboard
 *
 * @returns {Promise<void>}
 */
const initClipboard = async () => {
    var isTextCopied = false;
    var elementCopyText = document.getElementById("downloadLink");
    let currentBrowser = browserCheck();
    console.info('Current detected browser is: ' + currentBrowser);

    if (
        !navigator.clipboard ||
        currentBrowser === 'firefox' ||
        currentBrowser === 'safari'
    ) {
        isTextCopied = fallbackCopyTextToClipboard(elementCopyText.value);
    } else {
        await permissionsCheck()
            .then(permissionsCheckResult => {
                console.info("Permission check result: " + permissionsCheckResult);
            if (permissionsCheckResult){
                isTextCopied = copyToClipboard(elementCopyText.value)
            } else {
                console.error('Check clipboard permission failed!')
                return false;
            }
        })
    }

    console.info('Is text been copied: ' + isTextCopied);

    if (isTextCopied) {
        buildCopiedConfirmationButton();
    }
}