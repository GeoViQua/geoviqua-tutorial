$(document).ready(function () {
	
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

		$('#tabs1-pane2 .alert-success').show();
		$('#results-tab a').click();

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
		$(e.target).trigger('click');
	});

	// update each feedback form with the code and codespace used on submit
	$('form.feedback').on('submit', function (e) {

		var code = $(this).children('input[name=target_code]').val(), 
		codespace = $(this).children('input[name=target_codespace]').val(), 
		$childs = $('form.feedback').not($(this)).children();
		$childs.filter('input[name=target_code]').val(code);
		$childs.filter('input[name=target_codespace]').val(codespace);
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