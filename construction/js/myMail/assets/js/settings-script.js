jQuery(document).ready(function($) {

	"use strict"
								
	var nav = $('#mainnav'),
		deliverynav = $('#deliverynav'),
		tabs = $('.tab'),
		deliverytabs = $('#tab-delivery').find('.subtab'),
		wpnonce = $('#mymail_nonce').val(),
		reservedtags = $('#reserved-tags').data('tags');
	
	$('a.external').on('click', function(){
		window.open(this.href);
		return false;
	});
	
	deliverynav.find('a').on('click', function(){
		deliverynav.find('a').removeClass('nav-tab-active');
		deliverytabs.hide();
		var hash = $(this).addClass('nav-tab-active').attr('href');
		$('#deliverymethod').val(hash.substr(1));
		$('#subtab-'+hash.substr(1)).show();
		return false;
	});
	
	nav.find('a').on('click', function(){
		nav.find('a').removeClass('nav-tab-active');
		tabs.hide();
		var hash = $(this).addClass('nav-tab-active').attr('href');
		$('#tab-'+hash.substr(1)).show();
		location.hash = hash;
		$('form').attr('action','options.php'+hash);
		return false;
	});
	
	$('a[href^=#]').not('.nav-tab').on('click', function(){
		nav.find('a[href='+$(this).attr('href')+']').trigger('click');
	});
	
	(location.hash && nav.find('a[href='+location.hash+']').length)
		? nav.find('a[href='+location.hash+']').trigger('click')
		: nav.find('a').eq(0).trigger('click');
	
	$('#mymail_geoip').on('change', function(){
		($(this).is(':checked'))
			? $('#mymail_geoipcity').prop('disabled', false)
			: $('#mymail_geoipcity').prop('disabled', true).prop('checked', false);
	});
	$('#mymail_geoip').on('change', function(){
		($(this).is(':checked'))
			? $('#load_country_db').prop('disabled', false)
			: $('#load_country_db').prop('disabled', true).prop('checked', false);
	});
	$('#mymail_geoipcity').on('change', function(){
		($(this).is(':checked'))
			? $('#load_city_db').prop('disabled', false)
			: $('#load_city_db').prop('disabled', true).prop('checked', false);
	});
	
	$('#load_country_db, #load_city_db').on('click', function(){
		var $this = $(this),
			loader = $('.geo-ajax-loading').css({'visibility':'visible'}),
			type = $this.data('type');
		
		$('button').prop('disabled', true);
		
		_ajax('load_geo_data', {
			type: type
			
		}, function(response){
		
			if(response.success){
				$('button').prop('disabled', false);
				loader.css({'visibility':'hidden'});
				$this.prop('disabled', false).html(response.buttontext);
				var msg = $('<div class="'+((!response.success) ? 'error' : 'updated')+'"><p>'+response.msg+'</p></div>').hide().prependTo($this.parent()).slideDown(200).delay(200).fadeIn().delay(4000).fadeTo(200,0).delay(200).slideUp(200,function(){msg.remove();});
				
				if(response.path){
					$('#'+type+'_db_path').val(response.path);
				}
			}
			
		}, function(jqXHR, textStatus, errorThrown){
			
			$('button').prop('disabled', false);
			loader.css({'visibility':'hidden'});
			$this.prop('disabled', false);
			var msg = $('<div class="error"><p>'+textStatus+' '+jqXHR.status+': '+errorThrown+'</p></div>').hide().prependTo($this.parent()).slideDown(200).delay(200).fadeIn().delay(4000).fadeTo(200,0).delay(200).slideUp(200,function(){msg.remove();});
			
		});
		
		return false;
	});
	
	$('#upload_country_db_btn').on('click', function(){
		$('#upload_country_db').removeClass('hidden');
		return false;
	});
	
	$('#upload_city_db_btn').on('click', function(){
		$('#upload_city_db').removeClass('hidden');
		return false;
	});
	
	$('.users-register').on('change', function(){
		($(this).is(':checked'))
			? $('#'+$(this).data('section')).slideDown(200)
			: $('#'+$(this).data('section')).slideUp(200);
	});
	$('#mymail_dkim').on('change', function(){
		($(this).is(':checked'))
			? $('.dkim-info').slideDown(200)
			: $('.dkim-info').slideUp(200);
	});
	$('#mymail_spf').on('change', function(){
		($(this).is(':checked'))
			? $('.spf-info').slideDown(200)
			: $('.spf-info').slideUp(200);
	});
	
	$('#bounce_active').on('change', function(){
		($(this).is(':checked'))
			? $('#bounce-options').slideDown(200)
			: $('#bounce-options').slideUp(200);
	});
	
	
	$('#mymail_generate_dkim_keys').on('click', function(){
		return ($('#dkim_keys_active').length && confirm(mymailL10n.create_new_keys));
		return false;
	});
	
	$('#double-opt-in').on('change', function(){
		($(this).is(':checked'))
			? $('#double-opt-in-field').slideDown(200)
			: $('#double-opt-in-field').slideUp(200);
	});
	
	$('#vcard').on('change', function(){
		($(this).is(':checked'))
			? $('#v-card-field').slideDown(200)
			: $('#v-card-field').slideUp(200);
	});
	
	
	$('input.smtp.secure').on('change', function(){
		$('#mymail_smtp_port').val($(this).data('port'));
	});
	
	$('#capabilities-table')
		.delegate('label', 'mouseenter',function(){
			$('#current-cap').stop().html($(this).attr('title')).css('opacity',1).show();
			
		})
		.delegate('tbody', 'mouseleave',function(){
			$('#current-cap').fadeOut();
			
		});
	
	$('.mymail_sendtest').on('click', function(){
		var $this = $(this),
			loader = $('.test-ajax-loading').css({'visibility':'visible'}),
			basic = $this.data('role') == 'basic',
			to = (basic) ? $('#mymail_testmail').val() : $('#mymail_authenticationmail').val();
		
		$this.prop('disabled', true);
		
		_ajax('send_test', {
			test: true,
			basic: basic,
			to: to
			
		}, function(response){
		
			loader.css({'visibility':'hidden'});
			$this.prop('disabled', false);
			var msg = $('<div class="'+((!response.success) ? 'error' : 'updated')+'"><p>'+response.msg+'</p></div>').hide().prependTo($this.parent()).slideDown(200).delay(200).fadeIn().delay(4000).fadeTo(200,0).delay(200).slideUp(200,function(){msg.remove();});
			
		}, function(jqXHR, textStatus, errorThrown){
			
			loader.css({'visibility':'hidden'});
			$this.prop('disabled', false);
			var msg = $('<div class="error"><p>'+textStatus+' '+jqXHR.status+': '+errorThrown+'</p></div>').hide().prependTo($this.parent()).slideDown(200).delay(200).fadeIn().delay(4000).fadeTo(200,0).delay(200).slideUp(200,function(){msg.remove();});
			
		});
	});
	
	
	$('#mymail_add_form').on('click', function () {
		var el = $('.formtd'),
			count = el.length,
			clone;
			
		el = el.eq(0);
		clone = el.clone();
		
		clone.find('tr').last().remove();
		clone.find('tr').last().remove();
		clone.find('.mymail_form_id').val(count);
		clone.find('code').html('[newsletter_signup_form id='+count+']')
		clone.find('.mymail_remove_form').removeClass('hidden')
		
		$.each(clone.find('input'), function(){
			if($(this).attr('name'))
			$(this).attr('name', $(this).attr('name').replace('[0]', '['+count+']'));
		})
		
		clone.insertAfter($('.formtd').last());
		
		clone.find( ".form-order" ).sortable({
			containment: "parent"
		});
		clone.find('input').eq(0).val('Form #'+count).focus().select();
		
		return false;
	});
	
	$('#bounce_ssl').on('change', function(){
		
		$('#bounce_port').val($(this).is(':checked') ? '995' : '110');
		
	});
	
	$('.mymail_bouncetest').on('click', function(){
		var $this = $(this),
			loader = $('.bounce-ajax-loading').css({'visibility':'visible'}),
			status = $('.bouncetest_status').show();
		
		$this.prop('disabled', true);
		
		_ajax('bounce_test', {
			
		}, function(response){
		
			bounce_test_check(response.identifier, 1, function(){
				$this.prop('disabled', false);
			});
			
		}, function(jqXHR, textStatus, errorThrown){
			
			loader.css({'visibility':'hidden'});
			$this.prop('disabled', false);
			status.html(textStatus+' '+jqXHR.status+': '+errorThrown);
			
		});
	});
	
	function bounce_test_check(identifier, count, callback){
		var $this = $(this),
			loader = $('.bounce-ajax-loading').css({'visibility':'visible'}),
			status = $('.bouncetest_status');
			
		_ajax('bounce_test_check',{identifier: identifier, passes: count}, function(response){
		
			console.log(response);
			
			status.html(response.msg);
			
			if(response.complete){
				loader.css({'visibility':'hidden'});
				callback && callback();
			}else{
				setTimeout(function(){
					bounce_test_check(identifier, ++count, callback);
				}, 1000);
			}
			
		}, function(jqXHR, textStatus, errorThrown){
		
			loader.css({'visibility':'hidden'});
			$this.prop('disabled', false);
			status.html(textStatus+' '+jqXHR.status+': '+errorThrown);
			
		});

		
	}
	
	$('#tab-forms')
	.delegate('.mymail_remove_form', 'click', function(){
		
		$(this).parent().parent().remove();
		
		return false;
		
	})
	.delegate('.mymail_userschoice', 'change', function(){
		
		var checked = $(this).is(':checked');
		$(this).parent().parent().parent().parent().find('.mymail_userschoice_td').find('span').hide().eq(checked ? 1 : 0).show();
		$(this).parent().parent().find('.mymail_dropdown').prop('disabled', !checked);
		
	})
	.delegate('.embed-form', 'click', function(){
		
		$(this).parent().parent().next().toggle().find('input').eq(0).trigger('change');
		return false;
		
	})
	.delegate('.embed-form-input', 'change', function(){
		var	parent = $(this).parent().parent().parent(),
			inputs = parent.find('.embed-form-input'),
			output = parent.find('.embed-form-output');
			
		output.val(sprintf(output.data('embedcode'), inputs.eq(0).val(), inputs.eq(1).val(), (inputs.eq(2).is(':checked') ? '&s=1' : '') ));
		
	})
	.delegate('.form-order-check', 'change', function(){
		($(this).is(':checked'))
			? $(this).parent().removeClass('inactive')
			: $(this).parent().addClass('inactive').find('.form-order-check-required').prop('checked', false);
	})
	.delegate('.form-order-check-required', 'change', function(){
		if($(this).is(':checked')) $(this).parent().parent().find('.form-order-check').prop('checked', true).trigger('change');
	})
	.delegate('.embed-form-output', 'click', function(){
		$(this).select();
	})
	.delegate('.form-output', 'click', function(){
		$(this).select();
	});

	$( ".form-order" ).sortable({
		containment: "parent"
	});



	$('input.cron_radio').on('change', function(){
		$('.cron_opts').hide();
		$('.'+$(this).val()).show();
	});
	
	$('#mymail_add_tag').on('click', function () {
		var el = $('<div class="tag"><code>{<input type="text" class="tag-key">}</code> &#10152; <input type="text" class="regular-text tag-value"> <a class="tag-remove">&#10005;</a></div>').insertBefore($(this));
		el.find('.tag-key').focus();
	});
	
	$('.tags')
	.delegate('.tag-key', 'change', function(){
		var _this = $(this),
			_base = _this.parent().parent(),
			val = _sanitize(_this.val());
		
		if(!val) _this.parent().parent().remove();
		
		_this.val(val);
		_base.find('.tag-value').attr('name', 'mymail_options[custom_tags]['+val+']'); 
		
	})
	.delegate('.tag-remove', 'click', function(){
		$(this).parent().remove();
		return false;
	});
	
	$('#mymail_add_field').on('click', function(){
		
		var el = $('<div class="customfield"><a class="customfield-move-up" title="'+mymailL10n.move_up+'">&#9650;</a><a class="customfield-move-down" title="'+mymailL10n.move_down+'">&#9660;</a><div><span>'+mymailL10n.fieldname+':</span><label><input type="text" class="regular-text customfield-name"></label></div><div><span>'+mymailL10n.tag+':</span><span><code>{<input type="text" class="customfield-key">}</code></span></div><div><span>'+mymailL10n.type+':</span><select class="customfield-type"><option value="textfield">'+mymailL10n.textfield+'</option><option value="dropdown">'+mymailL10n.dropdown+'</option><option value="radio">'+mymailL10n.radio+'</option><option value="checkbox">'+mymailL10n.checkbox+'</option></select></div><ul class="customfield-additional customfield-dropdown customfield-radio"><li><ul class="customfield-values"><li><span>&nbsp;</span> <span class="customfield-value-box"><input type="text" class="regular-text customfield-value" value=""> <label><input type="radio" value="" title="'+mymailL10n.default_selected+'" class="customfield-default" disabled> '+mymailL10n.default+'</label></span></li></ul><span>&nbsp;</span> <a class="customfield-value-add">'+mymailL10n.add_field+'</a></li></ul><div class="customfield-additional customfield-checkbox"><span>&nbsp;</span><label><input type="checkbox" value="1" title="'+mymailL10n.default+'" class="customfield-default" disabled> '+mymailL10n.default_checked+'</label></div><a class="customfield-remove">remove field</a><br></div>').appendTo($('.customfields').eq(0));
		el.find('.customfield-name').focus();
	});
	
	
	$('.customfields')
	.delegate('.customfield-name', 'change', function(){
		var _this = $(this),
			_tagfield = _this.parent().parent().parent().find('.customfield-key');
		
		if(!_tagfield.val()) _tagfield.val(_this.val()).trigger('change');
	})
	.delegate('.customfield-key', 'change', function(){
		var _this = $(this),
			_base = _this.parent().parent().parent().parent(),
			val = _sanitize(_this.val());
		
		if(!val) _this.parent().parent().remove();
		
		_this.val(val);
		_base.find('.customfield-name').attr('name', 'mymail_options[custom_field]['+val+'][name]'); 
		_base.find('.customfield-type').attr('name', 'mymail_options[custom_field]['+val+'][type]'); 
		_base.find('.customfield-value').attr('name', 'mymail_options[custom_field]['+val+'][values][]'); 
		_base.find('.customfield-default').attr('name', 'mymail_options[custom_field]['+val+'][default]'); 
		
	})
	.delegate('.customfield-remove', 'click', function(){
		$(this).parent().remove();
	})
	.delegate('.customfield-move-up', 'click', function(){
		
		var _this = $(this).parent();
		_this.insertBefore(_this.prev());
		
	})
	.delegate('.customfield-move-down', 'click', function(){
		
		var _this = $(this).parent();
		_this.insertAfter(_this.next());
		
	})
	.delegate('.customfield-type', 'change', function(){
	
		var type = $(this).val();
		$(this).parent().parent().find('.customfield-additional').slideUp(200).find('input').prop('disabled', true);
		
		if(type != 'textfield'){
			$(this).parent().parent().find('.customfield-'+type).stop().slideDown(200).find('input').prop('disabled', false);
		}
	})
	.delegate('.customfield-value', 'change', function(){
		
		$(this).next().find('input').val($(this).val());
	})
	.delegate('a.customfield-value-remove', 'click', function(){
		$(this).parent().parent().remove();
	})
	.delegate('a.customfield-value-add', 'click', function(){
		var field = $(this).parent().find('li').eq(0).clone();
		field.appendTo($(this).parent().find('ul')).find('input').val('').focus().select();
	});
	
	function _sanitize(string){
		var tag = $.trim(string).toLowerCase().replace(/ /g,'-').replace(/[^a-z0-9_-]*/g,'');
		if($.inArray(tag, reservedtags) != -1){
			alert(sprintf(mymailL10n.reserved_tag,'"'+tag+'"' ));
			tag += '-a';
		}
		return tag;
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
	
	function sprintf() {
		var a = Array.prototype.slice.call(arguments), str = a.shift();
		while(a.length)	str = str.replace('%s', a.shift());	
		return str;
	}
	
});
