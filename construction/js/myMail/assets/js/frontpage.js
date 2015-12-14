jQuery(document).ready(function ($) {

	"use strict"
	
	var iframe = $('iframe'),
		sharebtn = $('.share').find('a'),
		sharebox = $('.sharebox'),
		share = sharebox.find('h4'), iOS = window.orientation !== 'undefined';


	function resize() {
		var height = window.innerHeight || $(window).height();
		iframe.attr("height", height + 300);
		if (iOS) {
			height = Math.max(iframe.contents().find("html").height(), iframe.height());
			$('body').height(height);
		}
	}

	$(window).on({
		'load.mymail resize.mymail': resize
	}).trigger('resize.mymail');

	$('#header').on('mousedown', function (event) {
		if (event.target.id == 'header') sharebox.fadeOut(600);
	});

	iframe.load(function () {
		iframe.contents().find("a").bind({
			'click': function () {
				window.open(this.href);
				return false;
			}
		});
		iframe.contents().bind({
			'mousedown': function (event) {
				sharebox.fadeOut(600);
			}
		});
	});

	$('.social-services').delegate('a', 'click', function () {
		window.open(this.href, 'share', 'scrollbars=auto,resizable=1,width=650,height=405,menubar=0,toolbar=0,location=0,directories=0,status=0,top=100,left=100');
		return false;
	});

	sharebtn.on('mouseenter', function () {
		sharebox.fadeIn(200);
	});

	share.on('click', function () {
		share.removeClass('active').next().slideUp();
		$(this).addClass('active').next().stop().slideDown(function () {
			$(this).find('input').eq(0).focus().select();
		});
	});
	
	sharebox.find('li.active').find('div').eq(0).show();

	$('#emailform').on('submit', function () {
		var _this = $(this),
			loader = $('#ajax-loading').css({
				'visibility': 'visible'
			}),
			data = _this.serialize();

		_this.find('input, textarea').prop('disabled', true);

		$.post(ajaxurl, {
			action: 'forward_message',
			data: data
		}, function (response) {
			loader.css({
				'visibility': 'hidden'
			});
			_this.find('.status').html(response.msg);
			if (!response.success) _this.find('input, textarea').prop('disabled', false);

		}, "JSON");
		return false;
	});
	
	$('.appsend').on('click', function(){
		
		var url = 'mailto:'+$('#receiver').val()+'?body='+$('#message').val().replace(/\n/g, '%0D%0A')+'%0D%0A%0D%0A'+$('#url').val();
		window.location = url;
		
		return false;
	});

	if ($.browser.msie) sharebox.find("[placeholder]").bind('focus.placeholder', function () {
		var el = $(this);
		if (el.val() == el.attr("placeholder")) {
			el.val("");
			el.removeClass("placeholder");
		}
	}).bind('blur.placeholder', function () {
		var el = $(this);
		if (el.val() == "" || el.val() == el.attr("placeholder")) {
			el.addClass("placeholder");
			el.val(el.attr("placeholder"));
		} else {

		}
	}).trigger('blur.placeholder');
});