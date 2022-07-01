var uploader = new plupload.Uploader({
  runtimes : 'html5',
  browse_button : 'browsefilesBta', // you can pass an id...
  container : document.getElementById('container'), // ... or DOM Element itself
  drop_element : "droparea", // add a drop area using the id in the index
	url : 'website/app/upload.php',
  chunk_size : '50mb',
	multipart : false,
	max_retries: 3,

  init: {

		// Display the files in the following div
		FilesAdded: function (up, files) {
			plupload.each(files, function (file) {
				console.log("1- Fileadded", file);
				document.getElementById('filelist').innerHTML += '<div id="' + file.id + '">' + file.name + ' (' + plupload.formatSize(file.size) + ') <b></b></div>';
			});
		},

		PostInit: function () {
			// Open the window to select and upload the files
			document.getElementById('uploadfiles').onclick = function () {
				console.log("2- Post init before start");
				uploader.start();
				return false;
			};
		},

		// Display progress in console and DOM
		UploadProgress: function (up, file) {
			console.log("Upload progress", file);
			document.getElementById(file.id).getElementsByTagName('b')[0].innerHTML = '<span>' + file.percent + "%</span>";
		},

		// Display info when each part/chunk is uploaded
		ChunkUploaded: function (up, file, info) {
			console.log("3- Chunk uploaded: ", file);
			console.log("3- chunk info", info)
		},

		// Display in console when file (when not chunked) are uploaded
		FileUploaded: function (up, file, info) {
			console.log("4- File uploaded", file);
			console.log(info);
			var response = jQuery.parseJSON(info.response);
			if (response.st == "ok") {
				window.location.href = window.location.href.replace('?saved', '') + '?saved';
			}
		},

		// Handle errors
		Error: function (up, err) {
			document.getElementById('console').appendChild(document.createTextNode("\nError #" + err.code + ": " + err.message));
		}

	}
});

uploader.init();


