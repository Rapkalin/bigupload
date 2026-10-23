// Upload rules come from App\Constant\UploadConfig through the data attributes of the drop area
const dropArea = document.getElementById('card-area-drop');
const config = dropArea.dataset;
const titleUpload = document.getElementById('card-area-title-upload');
const uploadButton = document.getElementById('upload-files');
const fileProcessing = document.getElementById('file-processing');
const refreshButton = document.getElementById('refreshButton');
const downloadLink = document.getElementById('downloadLink');

function show(element, display) {
  element.classList.remove('hidden');
  element.style.display = display;
}

function parseResponse(text) {
  try {
    return JSON.parse(text);
  } catch (e) {
    return null;
  }
}

var uploader = new plupload.Uploader({
  runtimes : 'html5',
  browse_button : 'browse-files-btn',
  container : document.getElementById('card-area'),
  drop_element : 'card-area-drop',
  url : config.uploadUrl,
  multi_selection : false,
  chunk_size : Number(config.chunkSize),
  max_file_size: Number(config.maxFileSize),
  filters: {
    mime_types: Object.entries(JSON.parse(config.allowedExtensions)).map(
      ([title, extensions]) => ({ title: title, extensions: extensions.join(',') })
    ),
    prevent_duplicates: true
  },
  multipart : false,
  max_retries: 3,

  init: {
    // Display the selected file (built with textContent: the file name is never interpreted as HTML)
    FilesAdded: function (up, files) {
      plupload.each(files, function (file) {
        const row = document.createElement('div');
        row.className = 'added-file';
        row.id = file.id;
        row.append(file.name + ' (' + plupload.formatSize(file.size) + ') ');

        const percent = document.createElement('b');
        const progress = document.createElement('progress');
        progress.id = 'progressBar-' + file.id;
        progress.value = 0;
        progress.max = 100;
        const background = document.createElement('div');
        background.className = 'btn-bg';

        row.append(percent, progress, background);
        document.getElementById('filelist').append(row);
      });
    },

    PostInit: function () {
      uploadButton.onclick = function () {
        uploader.start();
        return false;
      };
    },

    QueueChanged: function (up) {
      if (up.files.length > 0) {
        dropArea.className = 'hidden';
        show(document.getElementById('card-area-upload'), '');
        show(uploadButton, 'flex');
        uploadButton.style.alignItems = 'center';
      }
    },

    // A new upload must not reuse the token of a previous one
    BeforeUpload: function (up) {
      up.settings.multipart_params = {};
    },

    // The server returns a token on the first chunk: it identifies the upload on the following chunks
    ChunkUploaded: function (up, file, result) {
      const response = parseResponse(result.response);
      if (response && response.extraData && response.extraData.upload_id) {
        up.settings.multipart_params = { upload_id: response.extraData.upload_id };
      }
    },

    UploadProgress: function (up, file) {
      const percent = document.createElement('span');
      percent.textContent = file.percent + '%';
      document.getElementById(file.id).getElementsByTagName('b')[0].replaceChildren(percent);
      document.getElementById('progressBar-' + file.id).value = file.percent;
      uploadButton.style.display = 'none';
      titleUpload.innerText = 'Please wait while we upload your file :)';

      if (file.percent === 100) {
        show(fileProcessing, 'block');
      }
    },

    FileUploaded: function (up, file, result) {
      const response = parseResponse(result.response);
      downloadLink.value = response.extraData.download_url;

      titleUpload.innerText = 'Your file is ready ;)';
      uploadButton.style.display = 'none';
      downloadLink.classList.remove('hidden');
      refreshButton.classList.remove('hidden');
      show(document.getElementById('download-or-copy'), 'flex');
      fileProcessing.style.display = 'none';
    },

    // Client side errors (extension, size) happen in the drop area, server errors during the upload
    Error: function (up, err) {
      const response = err.response ? parseResponse(err.response) : null;
      const message = (response && response.message) || err.message;
      console.error('Upload error', err);

      if (!err.file || !up.getFile(err.file.id)) {
        document.getElementById('card-area-title-drop').innerText = message;
        return;
      }

      titleUpload.innerText = message;
      fileProcessing.style.display = 'none';
      uploadButton.style.display = 'none';
      refreshButton.classList.remove('hidden');
    }
  }
});

uploader.init();
