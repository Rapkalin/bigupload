document.getElementById("copyLinkButton").addEventListener("click", copyToClipboard);
function copyToClipboard() {
    window.Clipboard = (function(window, document, navigator) {
        let textArea,
            copy;

        function isOS() {
            return navigator.userAgent.match(/ipad|iphone/i);
        }

        function createTextArea(text) {
            textArea = document.createElement('textArea');
            textArea.value = text;
            document.body.appendChild(textArea);
        }

        function selectText() {
            let range,
                selection;

            if (isOS()) {
                range = document.createRange();
                range.selectNodeContents(textArea);
                selection = window.getSelection();
                selection.removeAllRanges();
                selection.addRange(range);
                textArea.setSelectionRange(0, 999999);
            } else {
                textArea.select();
            }
        }

        function copyToClipboardText() {
            document.execCommand('copy');
            document.body.removeChild(textArea);
        }

        let text = document.getElementById("downloadLink");
        console.log('text:::: ', text.value);
        createTextArea(text.value);
        selectText();
        copyToClipboardText();

        return {
            copy: text.value
        };
    })(window, document, navigator);
}