function copyToClipboard() {
    /* Get the text field */
/*    var copyText = document.getElementById("downloadLink");
    console.log("copyText", copyText);

    /!* Select the text field *!/
    copyText.select();
    copyText.setSelectionRange(0, 99999); /!* For mobile devices *!/

    /!* Copy the text inside the text field *!/
    navigator.clipboard.writeText(copyText.value);

    /!* Alert the copied text *!/
    alert("Copied the text: " + copyText.value);*/

    navigator.permissions.query({ name: 'clipboard-read' }).then(result => {
    var copyText = document.getElementById("downloadLink");
    console.log("copyText", copyText);
    console.log("Result", result);
        if (result.state === 'denied') {
            alert("Woupsy it seems that you don't have the permission to do that!);
        }

    // If permission to read the clipboard is granted or if the user will
    // be prompted to allow it, we proceed.
        if (result.state === 'granted' || result.state === 'prompt') {
            navigator.clipboard.readText()
                .then(copyText => {
                    navigator.clipboard.writeText(copyText.value);
                    alert("Copied the text: " + copyText.value);
                })
                .catch(err => {
                    console.error('Failed to read clipboard contents: ', err);
                    alert("Error my man:" + err);
                });
        }
    })
}