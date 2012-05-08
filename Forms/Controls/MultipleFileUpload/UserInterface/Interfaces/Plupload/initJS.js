var fallbackController = this;
(function($){
	// API: http://www.plupload.com/plupload/docs/api/index.html

	// Convert divs to queue widgets when the DOM is ready
	$(function(){
		// TODO: auto fallback
		var uploader = $("#"+{!$id|escapeJs}).pluploadQueue({
			// General settings
			runtimes : 'gears,html5,browserplus,flash,silverlight,html4',
			{* runtimes : 'gears,html5,browserplus,silverlight,html4', *}
			{* runtimes : 'flash',*}
			url : {!$backLink|escapeJs},
			max_file_size : {!$sizeLimit},
			chunk_size : '5mb',

			headers: {
				"X-Uploader": "plupload",
				"token"     : {!$token|escapeJs}
			},

			// Flash settings
			flash_swf_url : {!$baseUrl|escapeJs}+'/swf/MultipleFileUpload/plupload/plupload.flash.swf',

			// Silverlight settings
			silverlight_xap_url : {!$baseUrl|escapeJs}+'/xap/MultipleFileUpload/plupload/plupload.silverlight.xap'
		});
		uploader = $(uploader).pluploadQueue();
		uploader.bind("Error",function(){
			fallbackController.fallback();
		})
	});

	return true; // OK

})(jQuery);