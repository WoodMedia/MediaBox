jQuery(document).ready(function($) {

	"use strict"
	
	var iframe = $('#mymail_iframe'),
		wpnonce = $('#_wpnonce').val(),
		base = iframe.data('base'),
		templateeditor = $('#templateeditor'),
		templatecontent = $('textarea.editor'),
		animateDOM = $.browser.webkit ? $('body') : $('html');
		
	$('.remove-file').on('click', function(){
		
		var $this = $(this);
		
		_ajax('remove_template', {
			file: $this.data('file'),
		}, function(response){
			if(response.success){
				$this.parent().fadeOut();
			}
			
		});
		return false;
	});
		
	$('#mymail_templates')
	.delegate('a.edit', 'click', function(){
		var $this = $(this),
			$container = $this.closest('.available-theme'),
			$themes = $('.available-theme'),
			loader = $('.template-ajax-loading').css({ 'display':'inline' }),
			href = $this.attr('href'),
			slug = $this.data('slug');
		
		if($this.hasClass('disabled')) return false;
			
		if(!$this.is('.nav-tab')){
		
			$themes.removeClass('edit');
		
			$container.addClass('edit');
			
			var id = $container.data('id');
			var count = Math.floor( $('#availablethemes').outerWidth()/$container.width() );
			var pos = Math.floor(id/count-1)+count;
		
			templateeditor.find('textarea').val('');
			templateeditor.slideDown();
			
			templateeditor.insertAfter($container);
			_scroll($container.offset().top);
			
		}
		
		_ajax('get_template_html', {
			slug: slug,
			href: href
		}, function(response){
			loader.hide();
			
			$('#file').val(response.file);
			$('#slug').val(response.slug);
			templatecontent.val(response.html);
			var html = '';
			
			$.each(response.files, function(name,data){
				html += ' <a class="nav-tab '+(name == response.file ? 'nav-tab-active' : 'edit')+'" href="mymail/'+name+'" data-slug="'+slug+'">'+name+'</a>';
			});
			templateeditor.find('.nav-tab-wrapper').html(html);
		});
		
		return false;
	})
	.delegate('a.nav-tab', 'click', function(){
		return false;
	})
	.delegate('a.cancel', 'click', function(){
		templateeditor.slideUp();
		$('.available-theme').removeClass('edit');
	})
	.delegate('button.save', 'click', function(){
		var $this = $(this),
			loader = $('.template-ajax-loading').css({ 'display':'inline' }),
			content = templatecontent.val(),
			message = $('span.message');
			
			
		$this.prop('disabled', true);
		
		_ajax('set_template_html', {
			content: content,	
			slug: $('#slug').val(),
			file: $('#file').val()
		}, function(response){
			loader.hide();
			$this.prop('disabled', false);
		
			if(response.success){
				message.fadeIn(10).html(response.msg).delay(2000).fadeOut();
			}else{
				alert(response.msg);
			}
			
		}, function(jqXHR, textStatus, errorThrown){
			loader.hide();
			$this.prop('disabled', false);
			
			alert(textStatus+' '+jqXHR.status+': '+errorThrown);
		});
		
		return false;
	});
		
	function _scroll(pos, callback) {
		animateDOM.animate({
			'scrollTop': pos  
		}, callback && function(){
			callback();
		});
	}
	
	function _ajax(action, data, callback, errorCallback){

		if($.isFunction(data)){
			if($.isFunction(callback)){
				errorCallback = callback;
			}
			callback = data;
			data = {};
		}
		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: $.extend({action: 'mymail_'+action, _wpnonce:wpnonce}, data),
			success: function(data, textStatus, jqXHR){
					callback && callback.call(this, data, textStatus, jqXHR);
				},
			error: function(jqXHR, textStatus, errorThrown){
				if(textStatus == 'error' && !errorThrown) return;
					errorCallback && errorCallback.call(this, jqXHR, textStatus, errorThrown);
				},
			dataType: "JSON"
		});
	}
	
});
