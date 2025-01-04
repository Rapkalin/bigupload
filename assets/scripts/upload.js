var uploader = new plupload.Uploader({
  runtimes : 'html5',
  browse_button : 'browse-files-btn', // you can pass an id...
  container : document.getElementById('card-area'), // ... or DOM Element itself
  drop_element : "card-area-drop", // add a drop area using the id in the index
  url : '/handleFile',
  multi_selection : false,
  chunk_size : '250mb',
  max_file_size: '15gb',
  filters: {
    mime_types: [
      { title: "Image files", extensions: "jpg,jpeg,png,gif" },
      { title: "Video files", extensions: "mp4,mov"},
      { title: "Pdf files", extensions: "pdf"},
      { title: "Audio files", extensions: "mp3"},
      { title: "Subtitles", extensions: "vtt,srt"},
      { title: "Zip files", extensions: "zip" },
    ],
    prevent_duplicates: true
  },
  multipart : false,
  max_retries: 3,

  init: {
    // Display the files in the following div
    FilesAdded: function (up, files) {
      plupload.each(files, function (file) {
          // console.log("1- Fileadded", file);
          document.getElementById('filelist').innerHTML += '<div class="added-file" id="' + file.id + '">' + file.name + ' (' + plupload.formatSize(file.size) + ') <b></b><progress id="progressBar-'+ file.id +'" value="0" max="100"></progress><div class="btn-bg"></div></div>';
      });
    },

    PostInit: function () {
      // Open the window to select and upload the files
      document.getElementById('upload-files').onclick = function () {
        // console.log("2- Post init before start");
        uploader.start();
        return false;
      };
    },

    QueueChanged: function (up) {
      if (up.files.length > 0) {
        document.getElementById('card-area-drop').className = 'hidden';
        document.getElementById('browse-files-btn').style.display = 'none';
        document.getElementById('card-area-subtitle').style.display = 'none';
        document.getElementById('drop-area-or').style.display = 'none';
        document.getElementById('card-area-upload').classList.remove('hidden');
        document.getElementById('upload-files').style.display = 'flex';
        document.getElementById('upload-files').style.alignItems = 'center';
      }
    },

    // Display progress in console and DOM
    UploadProgress: function (up, file) {
      // console.log("Upload progress", file);
      document.getElementById(file.id).getElementsByTagName('b')[0].innerHTML = '<span>' + file.percent + "%</span>";
      document.getElementById("progressBar-"+file.id).value = file.percent;
      document.getElementById('upload-files').style.display = 'none';
      document.getElementById('card-area-title-upload').innerText = 'Please wait while we upload your file :)';

      if (file.percent === 100) {
        // add text: wait while we create the zip file
        document.getElementById('file-processing').classList.remove('hidden');
        document.getElementById('file-processing').style.display = 'block';
      }
      // $('#'+file.id).find('.progressBar').value = file.percent;
      $('#'+file.id).find('.added-file').css('color', 'white');
    },

    FileUploaded: function (uploader, file, result)
    {
      let response = jQuery.parseJSON(result.response);
      document.getElementById('downloadLink').value = response.extraData.download_url;

      // Hiding upload button and making download button and link visible
      document.getElementById('card-area-title-upload').innerText = 'Your file is ready ;)';
      document.getElementById('upload-files').style.display = 'none';
      document.getElementById('downloadLink').classList.remove('hidden');
      document.getElementById('refreshButton').classList.remove('hidden');
      document.getElementById('download-or-copy').classList.remove('hidden');
      document.getElementById('download-or-copy').style.display = 'flex';
      document.getElementById('file-processing').style.display = 'none';
    },

    // See other available event like ChunkUploaded / FileUploaded in plupload.dev.js file
    // Handle errors
    Error: function (up, err) {
      console.info("\nError #" + err);
      console.alert(["\nError #", err.response.details.message]);
      // document.getElementById('console').appendChild(document.createTextNode("\nError #" + err.code + ": " + err.message));
    }

  }
});

uploader.init();
