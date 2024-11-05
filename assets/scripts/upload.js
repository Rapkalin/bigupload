var uploader = new plupload.Uploader({
  runtimes : 'html5',
  browse_button : 'browse-files-btn', // you can pass an id...
  container : document.getElementById('upload-area'), // ... or DOM Element itself
  drop_element : "card-area", // add a drop area using the id in the index
	url : '/uploadFile',
  multi_selection : false,
  chunk_size : '50mb',
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
			document.getElementById('uploadfiles').onclick = function () {
				// console.log("2- Post init before start");
				uploader.start();
				return false;
			};
		},

    QueueChanged: function (up) {
      if (up.files.length > 0) {
        document.getElementById('card-area').id = 'drop-area-off';
        document.getElementById('browse-files-btn').classList.add('hidden');
        document.getElementById('drop-area-or').classList.add('hidden');
        document.getElementById('refreshButton').classList.remove('hidden');
        document.getElementById('upload-btn').classList.remove('hidden');
        document.getElementById('upload-btn').style.display = 'flex';
        document.getElementById('upload-btn').style.alignItems = 'center';
      }
    },

		// Display progress in console and DOM
		UploadProgress: function (up, file) {
			// console.log("Upload progress", file);
			document.getElementById(file.id).getElementsByTagName('b')[0].innerHTML = '<span>' + file.percent + "%</span>";
			document.getElementById("progressBar-"+file.id).value = file.percent;
			// $('#'+file.id).find('.progressBar').value = file.percent;
			$('#'+file.id).find('.added-file').css('color', 'white');
		},

		// Display info when each part/chunk is uploaded
		ChunkUploaded: function (up, file, info) {
			// console.log("3- Chunk uploaded: ", file);
			// console.log("3- chunk info", info)
		},

		// Display in console when file (when not chunked) are uploaded
		FileUploaded: function (up, file, info) {
			// console.log("4- File uploaded", file);
			var response = jQuery.parseJSON(info.response);

			// get the name of the file and recreate the filepath to be downloaded
			fileName = response.result.fileName;
			// var tag = document.getElementById('downloadFile'); // If we need a download button
			longUrl = window.location.origin + "/download?fileName=" + fileName;

			// SHORTEN THE LONGURL FOR THE CLIPBOARD
			fetch("/shortenUrl?longUrl=" + longUrl)
				.then((response) => {
					if(!response.ok){ // Before parsing (i.e. decoding) the JSON data,
						// check for any errors.
						// In case of an error, throw.
						document.getElementById('downloadLink').innerText = longUrl;
						throw new Error("Something went wrong!");
					}
					return response.json(); // Parse the JSON data.
				})
				.then((data) => {
					// This is where you handle what to do with the response.
					document.getElementById('downloadLink').innerText = data;
				})
				.catch((error) => {
					// This is where you handle errors.
					document.getElementById('downloadLink').innerText = longUrl;
					console.error('something went wrong with the tinyURL: ' + error)
				});

			// Hiding upload button and making download button and link visible
			document.getElementById('upload-btn').style.display = 'none';
			document.getElementById('download-or-copy').classList.remove('hidden');
			document.getElementById('download-or-copy').style.display = 'flex';
		},

		// Handle errors
		Error: function (up, err) {
			document.getElementById('console').appendChild(document.createTextNode("\nError #" + err.code + ": " + err.message));
		}

	}
});

uploader.init();
