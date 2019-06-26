/*!
 * jquery.powerUpload.js
 * Drop upload with paste image function by windy2000
*/
(function($){
    jQuery.event.addProp("dataTransfer");
	var opts = {},
		default_opts = {
			url: '',
			refresh: 1000,
			paramname: 'userfile',
			maxfiles: 25,
			maxfilesize: 1, // MBs
			data: {},
			drop: dummy,
			paste: dummy,
			dragEnter: dummy,
			dragOver: dummy,
			dragLeave: dummy,
			docEnter: dummy,
			docOver: dummy,
			docLeave: dummy,
			beforeEach: dummy,
			allDone: dummy,
			error: function(err, file, i){alert(err);},
			uploadStarted: dummy,
			uploadFinished: dummy,
			progressUpdated: dummy,
			speedUpdated: dummy
		},
		errors = ["BrowserNotSupported", "TooManyFiles", "FileTooLarge"],
		doc_leave_timer,
		stop_loop = false,
		files_count = 0,
		files;

	$.fn.powerUpload = function(options) {
		opts = $.extend( {}, default_opts, options );
		this.bind('drop', drop).bind('dragenter', dragEnter).bind('dragover', dragOver).bind('dragleave', dragLeave).bind('paste', paste);
		$(document).bind('drop', docDrop).bind('dragenter', docEnter).bind('dragover', docOver).bind('dragleave', docLeave);
	};
	
	function paste(e) {
		if((/webkit/i).test(navigator.userAgent)) {
			e = e.originalEvent;
			files = e.clipboardData.files;
			items = e.clipboardData.items;
			if(items.length > 0) {
				if((items[0]['kind'] == 'file') && (items[0]['type'].search('^image/') != -1)) {
					var reader = new FileReader();
					files_count = 1;
					filesDone = 0;
					filesRejected = 0;
					reader.onloadend = send;
					reader.readAsBinaryString(items[0].getAsFile());
					e.preventDefault();
				}
			} else if(files.length>0) {
				files_count = files.length;
				upload();
				e.preventDefault();
			}
			opts.paste(e);
		}
		return true;
	}
		 
	function drop(e) {
		opts.drop(e);
		files = e.dataTransfer.files;
		if (files === null || files === undefined) {
			opts.error(errors[0]);
			return false;
		}
		files_count = files.length;
		upload();
		e.preventDefault();
		return false;
	}
	
	function getBuilder(filename, filetype, filedata, boundary) {
		var dashdash = '--', crlf = '\r\n', builder = '';

		$.each(opts.data, function(i, val) {
			if (typeof val === 'function') val = val();
			builder += dashdash;
			builder += boundary;
			builder += crlf;
			builder += 'Content-Disposition: form-data; name="'+i+'"';
			builder += crlf;
			builder += crlf;
			builder += val;
			builder += crlf;
		});
		
		builder += dashdash;
		builder += boundary;
		builder += crlf;
		builder += 'Content-Disposition: form-data; name="'+opts.paramname+'"';
		builder += '; filename="' + filename + '"';
		builder += crlf;
		
		builder += 'Content-Type: ' + (filetype.length<5?"application/octet-stream":filetype);
		builder += crlf;
		builder += crlf; 
		
		builder += filedata;
		builder += crlf;
				
		builder += dashdash;
		builder += boundary;
		builder += dashdash;
		builder += crlf;
		return builder;
	}

	function progress(e) {
		if (e.lengthComputable) {
			var percentage = Math.round((e.loaded * 100) / e.total);
			if (this.currentProgress != percentage) {
				this.currentProgress = percentage;
				opts.progressUpdated(this.index, this.file, this.currentProgress);
				var elapsed = new Date().getTime();
				var diffTime = elapsed - this.currentStart;
				if (diffTime >= opts.refresh) {
					var diffData = e.loaded - this.startData;
					var speed = diffData / diffTime; // KB per second
					opts.speedUpdated(this.index, this.file, speed);
					this.startData = e.loaded;
					this.currentStart = elapsed;
				}
			}
		}
	}
	
	function upload() {
		stop_loop = false;
		if (!files) {
			opts.error(errors[0]);
			return false;
		}
		filesDone = 0;
		filesRejected = 0;
		
		if (files_count > opts.maxfiles) {
				opts.error(errors[1]);
				return false;
		}

		for (var i=0; i<files_count; i++) {
			if (stop_loop) return false;
			try {
				if (beforeEach(files[i]) != false) {
					if (i === files_count) return;
					var reader = new FileReader(), max_file_size = 1048576 * opts.maxfilesize;
					reader.index = i;
					if (files[i].size > max_file_size) {
						opts.error(errors[2], files[i], i);
						filesRejected++;
						continue;
					}
					reader.onloadend = send;
					reader.readAsBinaryString(files[i]);
				} else {
					filesRejected++;
				}
			} catch(err) {
				opts.error(errors[0]);
				return false;
			}
		}
	}

	function send(e) {
		var xhr = new XMLHttpRequest(),
			upload = xhr.upload,
			file = null,
			index = 0,
			start_time = new Date().getTime(),
			boundary = '------multipartformboundary' + (new Date).getTime(),
			builder;
			
		if(e.target.index == undefined) {
			file = new File([], "img_" + start_time + ".png");
			file.name = "img_" + start_time + ".png";
			file.size = e.total;
			file.type = "image/png";
		} else {
			file = files[e.target.index];
			index = e.target.index;
		}
		builder = getBuilder(encodeURIComponent(file.name), file.type, e.target.result, boundary);
		
		upload.index = index;
		upload.file = file;
		upload.downloadStartTime = start_time;
		upload.currentStart = start_time;
		upload.currentProgress = 0;
		upload.startData = 0;
		upload.addEventListener("progress", progress, false);
		
		xhr.open("POST", opts.url, true);
		xhr.setRequestHeader('content-type', 'multipart/form-data; boundary=' + boundary);
		xhr.overrideMimeType(file.type);
		xhr.sendAsBinary(builder);
		opts.uploadStarted(index, file, files_count);
		
		xhr.onload = function() {
			if (xhr.responseText) {
				var now = new Date().getTime(),
				timeDiff = now - start_time,
				result = opts.uploadFinished(index, file, jQuery.parseJSON(xhr.responseText), timeDiff);
				filesDone++;
				if (filesDone == files_count - filesRejected) {
					allDone();
				}
				if (result === false) stop_loop = true;
			}
		};
	}

	function beforeEach(file) {
		return opts.beforeEach(file);
	}
	
	function allDone() {
		return opts.allDone();
	}
	
	function dragEnter(e) {
		clearTimeout(doc_leave_timer);
		e.preventDefault();
		opts.dragEnter(e);
	}
	
	function dragOver(e) {
		clearTimeout(doc_leave_timer);
		e.preventDefault();
		opts.docOver(e);
		opts.dragOver(e);
	}
	 
	function dragLeave(e) {
		clearTimeout(doc_leave_timer);
		opts.dragLeave(e);
		e.stopPropagation();
	}
	 
	function docDrop(e) {
		e.preventDefault();
		opts.docLeave(e);
		return false;
	}
	 
	function docEnter(e) {
		clearTimeout(doc_leave_timer);
		e.preventDefault();
		opts.docEnter(e);
		return false;
	}
	 
	function docOver(e) {
		clearTimeout(doc_leave_timer);
		e.preventDefault();
		opts.docOver(e);
		return false;
	}
	 
	function docLeave(e) {
		doc_leave_timer = setTimeout(function(){
			opts.docLeave(e);
		}, 200);
	}
	 
	function dummy(){
		return true;
	}
	
	try {
		if (XMLHttpRequest.prototype.sendAsBinary) return;
		XMLHttpRequest.prototype.sendAsBinary = function(datastr) {
			function byteValue(x) {
				return x.charCodeAt(0) & 0xff;
			}
			var ords = Array.prototype.map.call(datastr, byteValue);
			var ui8a = new Uint8Array(ords);
			this.send(ui8a.buffer);
		}
	} catch(e) {}
})(jQuery);

$(function(){
    $("<div>").attr("id", "info_upload").html('<div class="info"></div>').css("display", "none").appendTo("body");
});