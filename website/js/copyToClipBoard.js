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
    let result;
    let textArea = document.getElementById("downloadLink");
    textArea.focus();
    textArea.select();

    try {
        /*
        If permission to read the clipboard is granted or if the user will
        be prompted to allow it, we proceed.
         */
        result = navigator.clipboard.writeText(textToCopy)
            .then(logInfo => {
                console.info("Text successfully copied: " + textToCopy);

                // If copy to clipboard worked we return true
                return true;
            })
            .catch(err => {
                console.error('Failed to read clipboard contents: ' + err);
            });
    } catch (e) {
        console.error('Error with clipboard-read: ' + e)
        result = fallbackCopyTextToClipboard(textToCopy);
    }

    // If copy to clipboard didn't work we return false
    return result;
}

/**
 * Fallback in case the navigator doesn't support clipboard API
 *
 * @param textToCopy
 */
function fallbackCopyTextToClipboard(textToCopy) {
    let isCopyCommandSuccessful = false;
    let textArea = document.getElementById("downloadLink");
    textArea.focus();
    textArea.select();

    try {
        isCopyCommandSuccessful = document.execCommand('copy');

        if (isCopyCommandSuccessful) {
            let confirmationMsg = isCopyCommandSuccessful ? 'successful' : 'unsuccessful';
            console.info('Fallback: Copying text command was ' + confirmationMsg);
        } else {
            console.error('Fallback failed: Copying text command was ' + confirmationMsg);
        }
    } catch (err) {
        console.error('Fallback: Oops, unable to copy', err);
    }

    return isCopyCommandSuccessful;
    // If copy to clipboard didn't work we return false
}

/**
 * Return the current browser
 *
 * @returns {string}
 */
function browserCheck () {
    return (function (agent) {
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
    let isTextCopied; // boolean
    let elementCopyText = document.getElementById("downloadLink");
    let currentBrowser = browserCheck();
    console.info('Checking current detected browser: ' + currentBrowser);
    console.info('Checking navigator clipboard availability: ' + navigator.clipboard);

    if (
        !navigator.clipboard ||
        currentBrowser === 'firefox' ||
        currentBrowser === 'safari'
    ) {
        console.info('Using fallbackCopyTextToClipboard')
        isTextCopied = fallbackCopyTextToClipboard(elementCopyText.value);
    } else {
        let permissionsCheckResult = await permissionsCheck();

        console.info("Permission check result: " + permissionsCheckResult);
        if (permissionsCheckResult) {
            isTextCopied = copyToClipboard(elementCopyText.value)
        } else {
            console.error('Check clipboard permission failed!')
            console.info('Using fallbackCopyTextToClipboard')
            isTextCopied = fallbackCopyTextToClipboard(elementCopyText.value);
        }
    }

    console.info('Is text been copied: ' + isTextCopied);

    if (isTextCopied) {
        buildCopiedConfirmationButton();
    }
}