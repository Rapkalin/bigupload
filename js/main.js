// Custom example logic

var uploader = new plupload.Uploader({
  runtimes : 'html5',
  browse_button : 'pickfiles', // you can pass an id...
  container: document.getElementById('container'), // ... or DOM Element itself
  url : 'upload.php',
  chunk_size : '10mb',
	send_chunk_number : true,
 	urlstream_upload : true,
	multipart : false,
	max_retries: 3,
/*	filters : {
		mime_types: [
			{
				title : "Video files",
				extensions : "mp4"
			}
		]
	},*/
//	flash_swf_url : '../js/Moxie.swf',
//	silverlight_xap_url : '../js/Moxie.xap',
  multipart_params : {directory : 'test'},


  init: {

		PostInit: function () {

//      document.getElementById('filelist').innerHTML = '';
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

		// Display in console and file are uploaded
		FileUploaded: function (up, file, info) {
			console.log("4- file uploaded", file);
			console.log(info);
			var response = jQuery.parseJSON(info.response);
			if (response.st == "ok") {
				window.location.href = window.location.href.replace('?saved', '') + '?saved';
			}
		},

		// Display info on each part/chunk uploaded
		ChunkUploaded: function (up, file, info) {
			console.log("3-  Chunk uploaded: ", file);
			console.log("3- chunk info", info)
		}

	}

});


uploader.init();

/*uploader.bind('BeforeChunkUpload', function(up, file) {
  console.log("Before Chunk Upload");
});*/


