document.getElementById("copyLinkButton").addEventListener("click", copyToClipboard);
function copyToClipboard() {
    navigator.permissions.query({ name: 'clipboard-read' }).then(result => {
        if (result.state === 'denied') {
            alert("Woupsy! It seems that you did not authorize your navigator to clipboard. Please check your settings");
        }

    // If permission to read the clipboard is granted or if the user will
    // be prompted to allow it, we proceed.
        if (result.state === 'granted' || result.state === 'prompt') {
            var copyText = document.getElementById("downloadLink");
            navigator.clipboard.writeText(copyText.value)
                .then(text => {
                    console.log("writeBoard: ", text);
                })
                .catch(err => {
                    console.error('Failed to read clipboard contents: ', err);
                    alert("Seems like there is an unexpected error: " + err);
                });
        }
    })
}