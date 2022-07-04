var uploader = new plupload.Uploader({
  runtimes : 'html5',
  browse_button : 'browsefilesBta', // you can pass an id...
  container : document.getElementById('container'), // ... or DOM Element itself
  drop_element : "droparea", // add a drop area using the id in the index
	url : 'app/upload.php',
  multi_selection : false,
  chunk_size : '50mb',
	multipart : false,
	max_retries: 3,

  init: {

		// Display the files in the following div
		FilesAdded: function (up, files) {
        plupload.each(files, function (file) {
          // console.log("1- Fileadded", file);
            document.getElementById('filelist').innerHTML += '<div class="addedFile" id="' + file.id + '">' + file.name + ' (' + plupload.formatSize(file.size) + ') <b></b><div class="progressBar"></div><div class="btaBG"></div></div>';
        });

		},

		PostInit: function () {
			// Open the window to select and upload the files
			document.getElementById('uploadfiles').onclick = function () {
				// console.log("2- Post init before start");
				uploader.start();
				return false;
			};
		},

    QueueChanged: function (up) {
      if (up.files.length = 1) {
        document.getElementById('droparea').id = 'dropareaOff';
        document.getElementById('browsefilesBta').classList.add('hidden');
        document.getElementById('browsefilesBtaOff').classList.remove('hidden');
        document.getElementById('tooManyFiles').classList.remove('hidden');
      }
    },

		// Display progress in console and DOM
		UploadProgress: function (up, file) {
			// console.log("Upload progress", file);
			document.getElementById(file.id).getElementsByTagName('b')[0].innerHTML = '<span>' + file.percent + "%</span>";
			$('#'+file.id).find('.progressBar').css('width', file.percent+'%');
			$('#'+file.id).find('.addedFile').css('color', 'white');
		},

		// Display info when each part/chunk is uploaded
		ChunkUploaded: function (up, file, info) {
			// console.log("3- Chunk uploaded: ", file);
			// console.log("3- chunk info", info)
		},

		// Display in console when file (when not chunked) are uploaded
		FileUploaded: function (up, file, info) {
			console.log("4- File uploaded", file);
			var response = jQuery.parseJSON(info.response);
			// dynamiser href JS id="downloadFile"
			console.log(response.result.fileName);
			document.getElementById('downloadBta').classList.remove('hidden');
		},

		// Handle errors
		Error: function (up, err) {
			document.getElementById('console').appendChild(document.createTextNode("\nError #" + err.code + ": " + err.message));
		}

	}
});

uploader.init();
