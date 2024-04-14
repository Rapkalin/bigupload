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
        navigator.permissions.query({ name: 'clipboard-read' })
            .then(result => {
                console.info("Clipboard-read permission result successful: ", result);
            if (result.state === 'denied') {
                console.error("Clipboard-read permission result denied: ", result);
                alert("Copied didn't work. Please authorize your navigator to clipboard");
            }

            /*
            If permission to read the clipboard is granted or if the user will
            be prompted to allow it, we proceed.
             */
            if (result.state !== 'denied') {
                var copyText = document.getElementById("downloadLink");
                navigator.clipboard.writeText(textToCopy)
                    .then(text => {
                        console.info("Text successfully copied: " + text);
                    })
                    .catch(err => {
                        console.error('Failed to read clipboard contents: ' + err);
                    });
            }
        })
    } catch (e) {
        console.error('Error with clipboard-read: ' + e)
    }

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
        var msg = successful ? 'successful' : 'unsuccessful';
        console.log('Fallback: Copying text command was ' + msg);
    } catch (err) {
        console.error('Fallback: Oops, unable to copy', err);
    }

    document.body.removeChild(textArea);
}

/**
 * Return the current browser
 *
 * @returns {string}
 */
function browserCheck () {
    switch (true) {
        case agent.indexOf("edge") > -1: return "MS Edge (EdgeHtml)";
        case agent.indexOf("edg") > -1: return "MS Edge Chromium";
        case agent.indexOf("opr") > -1 && !!window.opr: return "opera";
        case agent.indexOf("chrome") > -1 && !!window.chrome: return "chrome";
        case agent.indexOf("trident") > -1: return "Internet Explorer";
        case agent.indexOf("firefox") > -1: return "firefox";
        case agent.indexOf("safari") > -1: return "safari";
        default: return "other";
    }
}

/**
 * Init the function to copy to clipboard
 *
 * @returns {Promise<void>}
 */
const initClipboard = async () => {
    var elementCopyText = document.getElementById("downloadLink");
    let currentBrowser = browserCheck();
    console.info('Current detected browser is: ' + currentBrowser);
    
    if (
        !navigator.clipboard ||
        currentBrowser === 'firefox' ||
        currentBrowser === 'safari'
    ) {
        fallbackCopyTextToClipboard(elementCopyText.value);
        return;
    }

    await permissionsCheck()
        .then(permissionsCheckResult => {
            console.info("Permission check result: " + permissionsCheckResult);
        if (permissionsCheckResult){
            copyToClipboard(elementCopyText.value)
        } else {
            console.error('Check clipboard permission failed!')
        }
    })
}