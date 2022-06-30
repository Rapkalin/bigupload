var uploader = new plupload.Uploader({
  runtimes : 'html5',
  browse_button : 'pickfiles', // you can pass an id...
  container: document.getElementById('container'), // ... or DOM Element itself
  url : 'website/app/upload.php',
  chunk_size : '50mb',
	multipart : false,
	max_retries: 3,

  init: {

		PostInit: function () {
			// Open the window to select and upload the files
			document.getElementById('uploadfiles').onclick = function () {
				console.log("2- Post init before start");
				uploader.start();
				return false;
			};
		},

		// Display the files in the following div
		FilesAdded: function (up, files) {
			plupload.each(files, function (file) {
				console.log("1- fileadded test", file);
				document.getElementById('filelist').innerHTML += '<div id="' + file.id + '">' + file.name + ' (' + plupload.formatSize(file.size) + ') <b></b></div>';
			});
		},

		// Display progress in console and DOM
		UploadProgress: function (up, file) {
			console.log("upload progress", file);
			document.getElementById(file.id).getElementsByTagName('b')[0].innerHTML = '<span>' + file.percent + "%</span>";
		},

		// Handle errors
		Error: function (up, err) {
			document.getElementById('console').appendChild(document.createTextNode("\nError #" + err.code + ": " + err.message));
		},

		// Display in console when file (when not chunked) are uploaded
		FileUploaded: function (up, file, info) {
			console.log("4- file uploaded", file);
			console.log(info);
			var response = jQuery.parseJSON(info.response);
			if (response.st == "ok") {
				window.location.href = window.location.href.replace('?saved', '') + '?saved';
			}
		},

		// Display info when each part/chunk is uploaded
		ChunkUploaded: function (up, file, info) {
			console.log("3-  Chunk uploaded: ", file);
			console.log("3- chunk info", info)
		}

	}
});

uploader.init();


