$(document).ready(function () {

	var originalTitle = document.title;
	
	$(".no-js").show().removeClass('no-js');

	// hide all sections
	$("section.tab-pane").removeClass('active');

	$("a.fancy").fancybox();

	videojs.options.flash.swf = 'js/video-js/video-js.swf';

	// display video.js player in a lightbox, auto-plays on show and pauses when hidden
	$('a.video-preview').fancybox({
		type: 'inline',
		autoResize: true,
		hideOnContentClick: false,
		afterShow: function() {
			var self = this.element;
			videojs($(self).data('video') + '-player').ready(function() {

				var myPlayer = this;

				if (myPlayer.paused()) {
					myPlayer.play();
				}

			});
		},
		beforeClose: function() {
			var self = this.element;
			videojs($(self).data('video') + '-player').ready(function() {

				var myPlayer = this;

				if (!myPlayer.paused()) {
					myPlayer.pause();
				}
			});
		}
	});

	$('.subnav a, a.smooth').smoothScroll({
		offset: -$('.subnav').offset().top
	});

	$('#tabs1-pane2 div').hide();

	// POST to transform script and display results
	$('#transform-form').on('submit', function(e) {

		var metadata_url = $('#metadata_url').val(),
			metadata = $('#metadata').val();

		if (metadata_url !== '' && metadata !== '') {

			alert("Error: You have provided both a metadata document and a metadata URL location, please only use one!");
			return false;
		}
		else if (metadata_url === '' && metadata === '') {

			alert("Error: Please provide a metadata document either by uploading a local document or entering a URL.");
			return false;
		}
		else {

			$('#tabs1-pane2 .alert-success').show();
			$('#results-tab a').click();

			// track metadata transformation form interactions under the "producer tutorial" category
			ga('send', 'event', 'producer tutorial', 'transform metadata', (metadata !== '' ? metadata : metadata_url));
		}

		return true;
	});

	var publish_uploader = new plupload.Uploader({
			runtimes : 'html5,flash,html4',
			browse_button: 'browse',
			container: 'publish-upload-container',
			url: 'publish.php',
			file_data_name: 'publish-metadata',
			flash_swf_url : '/js/plupload/plupload.flash.swf'
		}),
		$publish_form = $('#publish-form'),
		$publish_inputs = $publish_form.find('input, select, button, textarea'),
		$publish_loading = $publish_form.find('.loading'),
		$publish_error_container = $publish_form.find('.alert-error');

	publish_uploader.init();

	publish_uploader.bind('FilesAdded', function(up, files) {

		$('#publish-upload-container .filename').text(files[0].name);
	});

	publish_uploader.bind('QueueChanged', function(up) {

		if (publish_uploader.files.length === 0) {

			$('#publish-upload-container .filename').text('No file selected.');
		}
	});

	var handlePublishResponse = function(response) {

		// parse the response
		response = $.parseJSON(response);

		if (response.status === 'error') {

			// show error message
			$publish_error_container.html(response.message);
			$publish_error_container.show();

			// track the error
			ga('send', 'event', 'error', 'producer tutorial', 'Publish form: ' + response.message);
		}
		else if (response.status === 'success') {

			// hide error messages
			$publish_error_container.hide();
			$('#tabs2-pane2').find('.alert-error').hide();

			// update the tutorial copy with the correct information
			var publish_url = response.data.geonetwork.url + 'xml_geoviqua?id=' + response.data.geonetwork.metadata_id + '&styleSheet=xml_iso19139.geoviqua.xsl',
				$publish_steps = $('.publish-steps');
			$publish_steps.find('#publish-ID').text(response.data.geonetwork.metadata_id);
			$publish_steps.find('#publish-URL').attr("href", publish_url);
			$publish_steps.find('#publish-username').text(response.data.geonetwork.username);
			$publish_steps.find('#publish-password').text(response.data.geonetwork.password);

			// update the GEO label form in part 3 with the ID
			$('#geonetwork_id').val(response.data.geonetwork.metadata_id);

			// display the tutorial copy
			$publish_steps.show();
			$('#publish-results-tab a').click();

			// track this interaction & where the metadata has been published to
			ga('send', 'event', 'producer tutorial', 'publish metadata', 'Metadata published to ' + publish_url);
		}

		// show form buttons again
		$publish_loading.hide();
		$publish_form.find('.form-actions button').show();

		// re-enable inputs
		$publish_inputs.prop("disabled", false);
	}

	publish_uploader.bind('Error', function(up, response) {

		handlePublishResponse(response.response);
		$('#publish-form [type="reset"]').trigger('click');
	});

	publish_uploader.bind('FileUploaded', function(up, file, response) {

		handlePublishResponse(response.response);
	});

	publish_uploader.bind('UploadComplete', function(up, files) {

		publish_uploader.refresh();
	});

	$('#publish-form .submit').on('click', function (e) {

		if (publish_uploader.files.length > 0) {

			// disable inputs for duration of request
			$publish_inputs.prop("disabled", true);

			// show the loading animation
			$publish_form.find('.form-actions button').hide();
			$publish_loading.show();

			// start upload
			publish_uploader.start();
		}
		else {

			// show error message
			$publish_error_container.html("Please select a file for upload.");
			$publish_error_container.show();
		}

		// prevent default posting of form
		e.preventDefault();
	});

	$('#publish-form [type="reset"]').on('click', function (e) {

		publish_uploader.splice();
	});

	$('#geolabel-form .submit').on('click', function (e) {

		var $form = $(this).parents('form'),
		$inputs = $form.find('input, select, button, textarea'),
		$error_container = $form.find('.alert-error'),
		$loading = $form.find('.loading'),
		url = $form.attr('action'),
		data = $form.serialize();

		// disable inputs for duration of request
		$inputs.prop("disabled", true);

		// show the loading animation
		$form.find('.form-actions button').hide();
		$loading.show();

		// POST the form
		$.ajax({
			url: url,
			method: 'POST',
			data: data
		})
		.done(function (response) {

			// hide error messages
			$error_container.hide();
			$('#tabs3-pane2').find('.alert-error').hide();

			// embed the GEO label
			$('#geolabel-result').html(response.data.label_svg);

			// display the success message & GEO label
			$('#tabs3-pane2').find('.alert-success').show();
			$('#geolabel-results-tab a').click();

			// track this interaction
			ga('send', 'event', 'GEO label tutorial', 'generate label', 'GEO label generated for ID ' + response.data.ID);
		})
		.fail(function (response) {

			response = $.parseJSON(response.responseText);

			// show error message
			$error_container.html(response.message);
			$error_container.show();

			// track the error
			ga('send', 'event', 'error', 'GEO label tutorial', 'Generate label form: ' + response.message);
		})
		.always(function() {

			// hide the loading animation
			$loading.hide();
			$form.find('.form-actions button').show();
		});

		// prevent default posting of form
		e.preventDefault();

		// re-enable inputs
		$inputs.prop("disabled", false);
	});

	// toggle active/inactive across multiple tab elements
	$('a[data-toggle="pill"]').on('shown', function (e) {

		var targetHref = $(e.target).attr('href'),
			$active = $('a[data-toggle="pill"]').filter(function() {
				return $(this).attr('href') === targetHref;	
			}),
			$notActive = $('a[data-toggle="pill"]').not($active);
		
		$notActive.parent().removeClass('active');
		$active.parent().addClass('active');

		// workaround: trigger a 2nd click event to correctly scroll to the top of the page
		// pass a 2nd parameter that indicates that this is a jquery click (for use of the analytics handler)
		$(e.target).trigger('click', [true]);
	});

	// update each feedback form with the code and codespace used on submit
	$('form.feedback').on('submit', function (e) {

		var $code = $(this).children('input[name=target_code]'),
			code = $code.val(),
			$codespace = $(this).children('input[name=target_codespace]'),
			codespace = $codespace.val();

		if (code === '') {

			alert('Error: Please enter a code');
			$code.focus();
			return false;
		}
		else if (codespace === '') {

			alert('Error: Please enter a codespace');
			$codespace.focus();
			return false;
		}
		else {

			$('input[name=target_code]').not($code).val(code);
			$('input[name=target_codespace]').not($codespace).val(codespace);

			// track feedback server form interactions (actions are "submit" or "search") under the "feedback tutorial" category
			ga('send', 'event', 'feedback tutorial', $(this).data('stage'), 'Code: ' + code + ' , Codespace: ' + codespace);
		}
	});

	// event handler for buttons that take users to the next tutorial
	$('.btn-next').on('click', function () {

		$('.subnav a[href="' + $(this).data('next') + '"]').first().trigger('click');
	});

	// add a hover effect to the corresponding hero picture on the homepage
	$('#homeNav li').on('mouseenter mouseleave', function (e) {

		var href = $(this).find('a').attr('href'),
			href = href.substring(1),
			target = $('.hero-preview div[data-sibling="' + href + '"]');

		if (e.type === 'mouseenter') {

			target.addClass('focused');
		}
		else {

			target.removeClass('focused');
		}
	});

	// use the validation library to validate the contact form
	$('#contact').validate({
		submitHandler: function(form) {

			// track this event
			ga('send', 'event', 'contact form', 'valid submission', 'Name: ' + $(form).find('#contactname').val() + ', Email: ' + $(form).find('#email').val() + ', Subject: ' + $(form).find('#subject').val() + ', Message: ' + $(form).find('#message').val());

			// submit
			form.submit();
		}
	});


	/**
	* Analytics
	*/

	// track page views for different sections of the tutorial
	$('a[data-toggle="pill"]').on('click', function (e, isJquery) {

		// check that the event has been triggered by a human, not jquery
		if (!isJquery) {

			window.location.href = this.href;
			document.title = originalTitle + ': ' + $(this).text();
			ga('send', 'pageview', window.location.pathname + window.location.search + window.location.hash);
		}
	});

	// track video interactions without affecting bounce rate, weighted by importance
	$('a.video-preview').each(function () {

		videojs($(this).data('video') + '-player').ready(function () {

			this.on('play', function () {
				ga('send', 'event', 'videos', 'play', this.O, 5, {'nonInteraction': 1});
			});

			this.on('pause', function () {
				ga('send', 'event', 'videos', 'pause', this.O, 1, {'nonInteraction': 1});
			});

			this.on('ended', function () {
				ga('send', 'event', 'videos', 'ended', this.O, 10, {'nonInteraction': 1});
			});

			this.on('error', function () {
				ga('send', 'event', 'videos', 'error', this.O, 1, {'nonInteraction': 1});
			});
		});
	});

});

(function ($) {

	$(function(){

		// fix sub nav on scroll
		var $win = $(window),
				$body = $('body'),
				$nav = $('.subnav'),
				navHeight = $('.navbar').first().height(),
				subnavHeight = $('.subnav').first().height(),
				subnavTop = $('.subnav').length && $('.subnav').offset().top - navHeight,
				marginTop = parseInt($body.css('margin-top'), 10);
				isFixed = 0;

		processScroll();

		$win.on('scroll', processScroll);

		function processScroll() {
			var i, scrollTop = $win.scrollTop();

			if (scrollTop >= subnavTop && !isFixed) {
				isFixed = 1;
				$nav.addClass('subnav-fixed');
				$body.css('margin-top', marginTop + subnavHeight + 'px');
			} else if (scrollTop <= subnavTop && isFixed) {
				isFixed = 0;
				$nav.removeClass('subnav-fixed');
				$body.css('margin-top', marginTop + 'px');
			}
		}

	});

})(window.jQuery);

// helper method to track outbound links
function trackOutbound(obj, category, value) {

	value = typeof value !== 'undefined' ? value : 1;

	try {

		ga('send', 'event', category, 'click', obj.href, value);
	}
	catch (err) {}

	setTimeout(function() {

		if (obj.target === '_blank') {

			window.open(obj.href);
		}
		else {

			document.location.href = obj.href;
		}
	}, 100);
}