const permissionsCheck = async () => {
    let read = await navigator.permissions.query({name: 'clipboard-read'})
    let write = await navigator.permissions.query({name: 'clipboard-write'})
    return write.state === 'granted' && read.state !== 'denied'
}

function copyToClipboard() {
    try {
        navigator.permissions.query({ name: 'clipboard-read' })
            .then(result => {
                console.info("Clipboard-read permission result successful: " + result);
            if (result.state === 'denied') {
                console.error("Clipboard-read permission result denied: " + result);
                alert("Copied didn't work. Please authorize your navigator to clipboard");
            }

            /*
            If permission to read the clipboard is granted or if the user will
            be prompted to allow it, we proceed.
             */
            if (result.state !== 'denied') {
                var copyText = document.getElementById("downloadLink");
                navigator.clipboard.writeText(copyText.value)
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

const initClipboard = async () => {
    await permissionsCheck()
        .then(allowed => {
        if (allowed){
            copyToClipboard()
        } else {
            console.error('Check clipboard permission failed!')
        }
    })

}