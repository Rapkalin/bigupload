// Copies the download link; the result is shown and announced by screen readers (role="status")
const copyButton = document.getElementById('animatedButton');
const copyStatus = document.getElementById('copy-status');
const linkInput = document.getElementById('downloadLink');

copyButton.addEventListener('click', async () => {
  try {
    await navigator.clipboard.writeText(linkInput.value);
    copyStatus.textContent = 'Link copied to your clipboard!';
  } catch (e) {
    // Clipboard refused by the browser: the link is selected so that it can be copied by hand
    linkInput.select();
    copyStatus.textContent = 'Press Ctrl+C (Cmd+C on Mac) to copy the selected link.';
  }
});
