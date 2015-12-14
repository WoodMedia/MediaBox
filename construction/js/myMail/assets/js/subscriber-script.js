jQuery(document).ready(function($) {

	"use strict"
	
	var _title = $('#title'),
		_userimage = $('#userimage'),
		_publish = $('#publish'),
		timeout,
		wpnonce = $('#mymail_nonce').val();

	//init the whole thing
	function _init(){
		
		_events();
		
	}
	
	function _events(){
		_title
		.bind('blur', function(){
			var _this = $(this),
				email = $.trim(_this.val().toLowerCase()),
				valid = _verify(email);
			
			if(!valid){
				$('.email-error').slideUp(100,function(){ $(this).remove(); });
				
				$('<p class="email-error">&#9650; '+mymailL10n.invalid_email+'</p>').hide().insertAfter(_this).slideDown(100);
				setTimeout(function(){_this.focus(),1});
				_publish.prop('disabled', true);
				
			}else{
				$(this).val(email);
				
				if(_userimage.data('email') != email){
					_userimage.addClass('loading');
					_getGravatar(email,function(data){
						if(data.success)
							_userimage.data('email',email).removeClass('loading').css({'background-image': 'url('+data.url+')'});
					});
				}
				
			}
			if(!email || !valid)_publish.prop('disabled', true);
			_this.trigger('keyup');
			
		})
		.bind('keyup', function(){
			var _this = $(this);
			clearTimeout(timeout);
			timeout = setTimeout(function(){
				var email = $.trim(_this.val().toLowerCase()),
				valid = _verify(email);
				if(!valid) return false;
				
				_ajax('check_email', {email:email,id:$('#post_ID').val()}, function(data){
					_publish.prop('disabled', data.exists);
					$('.email-error').slideUp(100,function(){ $(this).remove(); });
					if(data.exists){
						$('<p class="email-error">&#9650; '+mymailL10n.email_exists+'</p>').hide().insertAfter(_this).slideDown(100);
						setTimeout(function(){_this.focus(),1});
					}
				});
			
			},100);
			
			_publish.prop('disabled', true);
			
		}).trigger('blur');

	}
	
	
	function _verify(email){
		
		return !email || /^([\w-]+(?:\.[\w-]+)*)\@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$|(\[?(\d{1,3}\.){3}\d{1,3}\]?)$/.test(email);
	
	}
	
	function _getGravatar(email, callback){
		_ajax('get_gravatar', {email:email}, callback);
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
	
	_init();
	
});
