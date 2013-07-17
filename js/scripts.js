$(document).ready(function () {

	var originalTitle = document.title;
	
	$(".no-js").show();

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
			codespace = $codespace.val(),
			$childs = $('form.feedback').not($(this)).children();

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

			$childs.filter('input[name=target_code]').val(code);
			$childs.filter('input[name=target_codespace]').val(codespace);

			// track feedback server form interactions (actions are "submit" or "search") under the "feedback tutorial" category
			ga('send', 'event', 'feedback tutorial', $(this).data('stage'), 'Code: ' + code + ' , Codespace: ' + codespace);
		}
	});

	// event handler for buttons that take users to the next tutorial
	$('.btn-next').on('click', function () {

		$('.subnav a[href="' + $(this).data('next') + '"]').first().trigger('click');
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