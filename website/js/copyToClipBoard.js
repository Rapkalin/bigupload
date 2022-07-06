function copyToClipboard() {
    navigator.permissions.query({ name: 'clipboard-read' }).then(result => {
    console.log("Result", result);
        if (result.state === 'denied') {
            alert("Woupsy it seems that you don't have the permission to do that!");
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
                    alert("Error my man:" + err);
                });
        }
    })
}