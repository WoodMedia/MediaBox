jQuery(document).ready(function ($) {

	"use strict"

	var _iframe = $('#mymail_iframe'),
		_ibody = _iframe.contents().find('body'),
		_idoc, _container = $('#mymail_template .inside'),
		_disabled = !! $('#mymail_disabled').val(),
		_title = $('#title'),
		_subject = $('#mymail_subject'),
		_content = $('#content'),
		_obar = $('#optionbar'),
		_undo = [],
		style = '',
		_currentundo = 0,
		clickbadgeZ = 1,
		wpnonce = $('#mymail_nonce').val(),
		editor = $('#wp-mymail-editor-wrap'),
		iframeloaded = false,
		timeout, modules, optionbar, charts, editbar, animateDOM = $.browser.webkit ? $('body') : $('html'),
		getSelect, selectRange, isIE7 = $.browser.msie && $.browser.version <= 8,
		is_touch_device = 'ontouchstart' in document.documentElement,
		isTinyMCE = typeof tinymce == 'object';


	//init the whole thing

	function _init() {

		_disable(true);
		_time();

		//set the document of the iframe cross browser like
		_idoc = (_iframe[0].contentWindow || _iframe[0].contentDocument);
		if (_idoc.document) _idoc = _idoc.document;

		_events();
		
		var iframeloadinterval = setTimeout(function () {
			if (!iframeloaded) _iframe.trigger('load');
		}, 3000);
		
		_iframe.load(function () {
		
			if (iframeloaded) return false;
			if (!_disabled) {
				optionbar = new _optionbar();
				editbar = new _editbar();
				modules = new _modules();
			} else {}
			
			//open all links in new window
			_iframe.contents().find("html")
			.delegate('a', 'click', function () {
				//window.open($(this).attr('href'));
				return false;
			}).find("img").each(function () {
				this.onload = function(){
					_refresh();
				};
			});

			_enable();
			iframeloaded = true;
			_refresh();
			_save();
			
			clearInterval(iframeloadinterval);
			
			//prevent first time autosave
			if (typeof autosaveLast != 'undefined') window.autosaveLast = _title.val() + _content.val();

		});

		$(window).on('resize.mymail', function () {
			_refresh();
		});


		$('#publish').on('click', function () {
			_save();
		});
		
		//switch to autoresponder if referer is right
		if(/post_status=autoresponder/.test($('#referredby').val())) $('#mymail_delivery').find('a[href="#autoresponder"]').click();

		if ($.browser.msie) $('body').addClass('ie');
		if (is_touch_device) $('body').addClass('touch');
		
		
	}


	function _events() {

		$('body').delegate('a.external', 'click', function(){
			window.open(this.href);
			return false;
		});
		
		if (!_disabled) {
			_title.change(function () {
				if (!_subject.val()) _subject.val($(this).val());
			});
			
			$('#editbar, #mymail_delivery')
				.delegate('.advanced_embed_options_taxonomy', 'change', function(){
					var $this = $(this),
						val = $this.val();
						$this.parent().find('.button').remove();
						console.log(val);
					if(val != -1){
						if($this.parent().find('select').length < $this.find('option').length-1)
							$(' <a class="button button-small add_embed_options_taxonomy">' +mymailL10n.add+ '</a>').insertAfter($this);
					}else{
						$this.parent().html('').append($this);
					}	
				
					return false;
				})
				.delegate('.add_embed_options_taxonomy', 'click', function(){
					var $this = $(this),
						el = $this.prev().clone();
						
					el.insertBefore($this).val('-1');
					$('<span> '+mymailL10n.or+ ' </span>').insertBefore(el);
					$this.remove();
					
					return false;
				});
				
			$('#autoresponder-post_type').on('change', function(){
				var cats = $('#autoresponder-taxonomies');
				cats.find('select').prop('disabled', true);
				_ajax('get_post_term_dropdown', {
					labels:false,
					names:true,
					posttype: $(this).val()
				}, function (response) {
					if (response.success) {
						cats.html(response.html);
					}
				}, function(jqXHR, textStatus, errorThrown){
				
					loader(false);
					alert(textStatus+' '+jqXHR.status+': '+errorThrown);
		
				});
			});

			$('.default-value').on('click', function () {
				var _this = $(this);
				$('#' + _this.data('for')).val(_this.data('value'));
			});
			
			$('.category-tabs').delegate('a', 'click', function(){
				var _this = $(this),
					href = _this.attr('href');
				
				$('#mymail_delivery').find('.tabs-panel').hide();
				$('#mymail_delivery').find('.tabs').removeClass('tabs');
				_this.parent().addClass('tabs');
				$(href).show();
				$('#mymail_is_autoresponder').val((href == '#autoresponder') ? 1 :'');
				return false;
			});

			$('.mymail_sendtest').on('click', function () {
				var $this = $(this),
					loader = $('#delivery-ajax-loading').css({'display': 'inline'});
					$this.prop('disabled', true);
				_save();
				_ajax('send_test', {
					ID: $('#post_ID').val(),
					subject: _subject.val(),
					from: $('#mymail_from').val(),
					from_name: $('#mymail_from-name').val(),
					to: $('#mymail_testmail').val(),
					reply_to: $('#mymail_reply_to').val(),
					isnotspam: $('#isnotspam').is(':checked'),
					template: $('#mymail_template').val(),
					preheader: $('#mymail_preheader').val(),
					embed_images: $('#mymail_data_embed_images').is(':checked'),
					issue: $('#mymail_autoresponder_issue').val(),
					content: _content.val()
					
				}, function (response) {
				
					loader.hide();
					$this.prop('disabled', false);
					var msg = $('<div class="' + ((!response.success) ? 'error' : 'updated') + '"><p>' + response.msg + '</p></div>').hide().prependTo($this.parent()).slideDown(200).delay(200).fadeIn().delay(3000).fadeTo(200, 0).delay(200).slideUp(200, function () {
						msg.remove();
					});
				}, function(jqXHR, textStatus, errorThrown){
			
					loader.hide();
					$this.prop('disabled', false);
					var msg = $('<div class="error"><p>'+textStatus+' '+jqXHR.status+': '+errorThrown+'</p></div>').hide().prependTo($this.parent()).slideDown(200).delay(200).fadeIn().delay(3000).fadeTo(200, 0).delay(200).slideUp(200, function () {
						msg.remove();
					});
					
				})
			});

			$('#mymail_data_active').on('change', function () {
				var checked = $(this).is(':checked');
				(checked) ? $('.active_wrap').addClass('disabled') : $('.active_wrap').removeClass('disabled');
				$('.deliverydate, .deliverytime').prop('disabled', !checked);

			});
			
			$('#mymail_data_autoresponder_active').on('change', function () {
				var checked = $(this).is(':checked');
				(checked) ? $('.autoresponder_active_wrap').addClass('disabled') : $('.autoresponder_active_wrap').removeClass('disabled');

			});

			$('input.color').miniColors({
				change: function () {
					var _this = this;
					clearTimeout(timeout);
					timeout = setTimeout(function () {
						_this.trigger('change');
					}, 300);
				}
			}).on('change', function () {
				var _this = $(this);
				var from = _this.data('value');
				_changeColor(from, _this.val(), _this, true);
			});
			
			if(typeof jQuery.datepicker == 'object'){
				$('input.datepicker').datepicker({
					dateFormat: 'yy-mm-dd',
					minDate: new Date(),
					firstDay: mymailL10n.start_of_week,
					dayNames: mymailL10n.day_names,
					dayNamesMin: mymailL10n.day_names_min,
					monthNames: mymailL10n.month_names,
					prevText: mymailL10n.prev,
					nextText: mymailL10n.next,
					showAnim: 'fadeIn',
					onClose: function () {
						var date = $(this).datepicker('getDate');
						$('.deliverydate').html($(this).val());
					}
				});
				
				$('.deliverydate').on('click', function () {
					$('input.datepicker').datepicker('show');
				});
				
			}else{
			
				$('input.datepicker').prop('readonly', false);
				
			}
			
			$('input.datepicker').on('focus', function () {
				$(this).removeClass('inactive').trigger('click');
			}).on('blur', function () {
				$('.deliverydate').html($(this).val());
				$(this).addClass('inactive');
			}).on('change', function () {});

			$('input.deliverytime').on('blur', function () {
				$(document).unbind('.mymail_deliverytime')
			}).on('focus, click', function (event) {
				var $this = $(this),
					input = $(this)[0],
					l = $this.offset().left;
				if (event.clientX - l > 23) {
					var c = 1,
						startPos = 3,
						endPos = 5;
				} else {
					var c = 0,
						startPos = 0,
						endPos = 2;
				}

				$(document).unbind('.mymail_deliverytime').on('keypress.mymail_deliverytime', function (event) {
					if (event.keyCode == 9) {
						return (c = !c) ? !selectRange(input, 3, 5) : (event.shiftKey) ? !selectRange(input, 0, 2) : true;
					}

				}).on('keyup.mymail_deliverytime', function (event) {
					if ($this.val().length == 1) {
						$this.val($this.val() + ':00');
						selectRange(input, 1, 1);
					}
					if (document.activeElement.selectionStart == 2) {
						if ($this.val().substr(0, 2) > 23) {
							$this.trigger('change');
							return false;
						}
						selectRange(input, 3, 5);
					}
				});
				selectRange(input, startPos, endPos);

			}).on('change', function () {
				var $this = $(this),
					val = $this.val();
				$this.addClass('inactive');
				if (!/^\d+:\d+$/.test(val)) {

					if (val.length == 1) {
						val = "0" + val + ":00";
					} else if (val.length == 2) {
						val = val + ":00";
					} else if (val.length == 3) {
						val = val.substr(0, 2) + ":" + val.substr(2, 3) + "0";
					} else if (val.length == 4) {
						val = val.substr(0, 2) + ":" + val.substr(2, 4);
					}
				}
				time = val.split(':');

				if (!/\d\d:\d\d$/.test(val) && val != "" || time[0] > 23 || time[1] > 59) {
					$this.val('00:00').focus();
					selectRange($this[0], 0, 2);
				} else {
					$this.val(val);
				}
			})
			
			$('#mymail_autoresponder_action').on('change', function(){
				$('#autoresponder_wrap').removeAttr('class').addClass('autoresponder-'+$(this).val());
			});
			
			$('#mymail_autoresponder_advanced_check').on('change', function(){
				$('#mymail_autoresponder_advanced').slideToggle();
			});
			
			$('#mymail_autoresponder_advanced')
			.delegate('.add-condition', 'click', function () {
				var cond = $('.mymail_autoresponder_condition'),
					id = cond.length,
					clone = cond.last().clone();
				
				clone.hide().removeAttr('id').insertAfter(cond.last()).slideDown();
					$.each(clone.find('input, select'), function(){
						var name = $(this).val('').attr('name');
						$(this).attr('name', name.replace(/\[\d+\]/, '['+id+']'));
					});
			})
			.delegate('.remove-condition', 'click', function () {
				$(this).parent().parent().slideUp(function(){$(this).remove()});
			});
			

			$('#taxonomy-newsletter_lists').delegate('input.list', 'change', function () {
				var lists = [],
					inputs = $('#taxonomy-newsletter_lists').find('input.list');
					
				$.each(inputs, function () {
					var id = $(this).data('id');
					if($(this).is(':checked')) lists.push(id);
				});
				
				if(!lists.length) {
					$('#mymail_total').html('0');
					return false;
				};
				
				var loader = $('#mymail_total').addClass('loading');
				
				inputs.prop('disabled', true);

				_ajax('get_totals', {
					lists: lists
				}, function (response) {
					inputs.prop('disabled', false);
					loader.removeClass('loading').html(response);
					
				}, function(jqXHR, textStatus, errorThrown){
					inputs.prop('disabled', false);
					loader.removeClass('loading').html('');
					alert(textStatus+' '+jqXHR.status+': '+errorThrown);
				});
				
			}).delegate('#all_lists', 'change', function () {
				$('#taxonomy-newsletter_lists').find('input.list').prop('checked', $(this).is(':checked')).eq(0).trigger('change');
				
				
			}).find('input.list').eq(0).trigger('change');

			$('#mymail_options').delegate('a.default-value', 'click', function () {
				var trigger = $(this).prev(),
					el = trigger.prev(),
					color = el.data('default');
				trigger.css({
					'background-color': color
				});
				el.val(color).trigger('change');
				return false;
			}).delegate('ul.colorschema', 'click', function () {
				var colorfields = $('#mymail_options').find('input.color'),
					li = $(this).find('li.colorschema-field');
					
				_disable();
				
				$.each(li, function (i) {
					var color = li.eq(i).data('hex');
					colorfields.eq(i).val(color).each(function(){
					
						_changeColor($(this).data('value'), color, $(this), true);
						
						$(this).data('value', color);
						
					}).next().css({
						'background-color': color
					});
					
					
				});
				
				/*$.each(li, function (i) {
					var color = li.eq(i).data('hex');
					if(color) _changeColor('%%%REPLACE'+i+'%%%', color, colorfields.eq(i), true);
				});*/
					
				_enable();

			}).delegate('a.savecolorschema', 'click', function () {
				var colors = $.map($('#mymail_options').find('.color'), function (e) {
					return $(e).val();
				});
				
				var loader = $('#colorschema-ajax-loading').css({ 'display': 'inline' });

				_ajax('save_color_schema', {
					template: $('#mymail_template').val(),
					colors: colors
				}, function (response) {
					loader.hide();
					if (response.success) {
						$('.colorschema').last().after($(response.html).hide().fadeIn());
					}
				}, function(jqXHR, textStatus, errorThrown){
					loader.hide();
					alert(textStatus+' '+jqXHR.status+': '+errorThrown);
				})

			}).delegate('.colorschema-delete', 'click', function(){
				
				if(confirm(mymailL10n.delete_colorschema)){
					
					var schema = $(this).parent().parent();
					var loader = $('#colorschema-ajax-loading').css({ 'display': 'inline' });
					_ajax('delete_color_schema', {
						template: $('#mymail_template').val(),
						hash: schema.data('hash')
					}, function (response) {
						loader.hide();
						if(response.success){
							schema.fadeOut(100,function(){schema.remove()});
						}
					}, function(jqXHR, textStatus, errorThrown){
						loader.hide();
						alert(textStatus+' '+jqXHR.status+': '+errorThrown);
					});
					
				}
				
				return false;
				
			}).delegate('.colorschema-delete-all', 'click', function(){
				
				if(confirm(mymailL10n.delete_colorschema_all)){
					
					var schema = $('.colorschema.custom');
					var loader = $('#colorschema-ajax-loading').css({ 'display': 'inline' });
					_ajax('delete_color_schema_all', {
						template: $('#mymail_template').val(),
					}, function (response) {
						loader.hide();
						if(response.success){
							schema.fadeOut(100,function(){schema.remove()});
						}
					}, function(jqXHR, textStatus, errorThrown){
						loader.hide();
						alert(textStatus+' '+jqXHR.status+': '+errorThrown);
					});
					
				}
				
				return false;
				
			}).delegate('#mymail_version', 'change', function () {
				var val = $(this).val();
				_changeElements(val);

			}).delegate('ul.backgrounds ul a', 'click', function () {
				$('ul.backgrounds').find('a').removeClass('active');
				var base = $(this).parent().parent().data('base'),
					val = $(this).addClass('active').data('file');
					
				if(!val) base = '';
				$('#mymail_background').val(base + val);
				_changeBG(base + val);
				
			}).delegate('ul.backgrounds a', 'mouseenter', function () {
				var base = $(this).parent().parent().data('base'),
					file = $(this).data('file');

				if (file) $('ul.backgrounds a').eq(0).css({
					'background-image': 'url(' + base + $(this).data('file') + ')'
				});
			}).delegate('ul.backgrounds a', 'mouseleave', function () {
				$('ul.backgrounds a').eq(0).css({
					'background-image': 'url(' + $('#mymail_background').val() + ')'
				});
			});

			_container.delegate('a.editbutton', 'mouseenter', function () {
				var _this = $(this);
				_this.data('element').addClass('mymail-highlight');
			}).delegate('a.editbutton', 'mouseleave', function () {
				var _this = $(this);
				_this.data('element').removeClass('mymail-highlight');
			}).delegate('a.editbutton', 'click', function () {
				editbar.open($(this).data());
				return false;
			}).delegate('a.addbutton', 'click', function () {
				var data = $(this).data();

				editbar.open({
					type: 'btn',
					offset: data.offset,
					element: $('<img data-editable="Button" alt="">').appendTo(data.element),
					name: data.name
				});
				return false;
			}).delegate('a.addrepeater', 'click', function () {
				var data = $(this).data();
				
				data.element.clone().insertAfter(data.element);
				_refresh();
	
				return false;
			}).delegate('a.removerepeater', 'click', function () {
				var data = $(this).data();
				
				data.element.remove();
				_refresh();

				return false;
			});
			
			$('.device-list').delegate('a', 'click', function(){
				var size = $(this).data('size'),
					zoom = size == '320x480' ? 0.5 : size == '480x320' ? 0.75 : 1;
					
				$('.mymail_campaign_preview').removeAttr('class').addClass('mymail_campaign_preview device-'+size);
				
				$('#mymail_campaign_preview_iframe').data('zoom', zoom).trigger('load');
				
			});
			
			$('#mymail_campaign_preview_iframe').on('load', function(){
				var body = $('#mymail_campaign_preview_iframe').contents().find('body'),
					style = body.find('style').text(),
					hasqueries = /@media/.test(style);
					
				if(!hasqueries){
					var zoom = $('#mymail_campaign_preview_iframe').data('zoom');
					body.css({
						'zoom': zoom,
						'-moz-transform': 'scale('+zoom+')',
						'-moz-transform-origin': '0 0',
						'-o-transform': 'scale('+zoom+')',
						'-o-transform-origin': '0 0',
						'transform': 'scale('+zoom+')',
						'transform-origin': '0 0',
					});
				}
				
			});
		} else {
			_title.prop('disabled', true);
			$('#change-permalinks').remove();
			if (typeof autosavePeriodical != 'undefined') autosavePeriodical.repeat = false;
			
			$('#show_recipients').on('click', function () {
				var list = $('#recipients-list'),
					loader = $('#recipients-ajax-loading');
				
				if(!list.is(':hidden')){
					list.slideUp(100);
					return false;
				}	
				loader.css({ 'display': 'inline' });
				
				_ajax('get_recipients', {
					id: $('#post_ID').val()
				}, function (response) {
					loader.hide();
					list.html(response.html).slideDown(100);
				}, function(jqXHR, textStatus, errorThrown){
					loader.hide();
					alert(textStatus+' '+jqXHR.status+': '+errorThrown);
				})
				return false;
			});
			$('#show_errors').on('click', function () {
				
				$('#error-list').slideToggle(100);
				return false;
			});
			$('#show_clicks').on('click', function () {
				
				$('#click-list').slideToggle(100);
				return false;
			});
			
			$('#mymail_details')
			.delegate('.load-more-receivers', 'click', function(){
				var $this = $(this),
					page = $this.data('page'),
					loader = $this.next().css({ 'display': 'inline' });
				
				_ajax('get_recipients_page', {
					page: page,
					id: $('#post_ID').val()
				}, function (response) {
					loader.hide();
					if(response.success){
						$this.parent().parent().replaceWith(response.html);
					}
				}, function(jqXHR, textStatus, errorThrown){
					detailbox.removeClass('loading');
					alert(textStatus+' '+jqXHR.status+': '+errorThrown);
				});
				
				return false;
			})
			.delegate('.show-receiver-detail', 'click', function(){
				var $this = $(this),
					id = $this.data('id'),
					detailbox = $('#receiver-detail-'+id).show();
					
				$this.parent().addClass('loading').parent().addClass('expanded');
				
				_ajax('get_recipient_detail', {
					id: id,
					campaignid: $('#post_ID').val()
				}, function (response) {
					$this.parent().removeClass('loading');
					if(response.success){
						detailbox.find('div.receiver-detail-body').html(response.html).slideDown(100);
					}
				}, function(jqXHR, textStatus, errorThrown){
					detailbox.removeClass('loading');
					alert(textStatus+' '+jqXHR.status+': '+errorThrown);
				});
				
				return false;
			});
			_container.delegate('a.clickbadge', 'mouseenter', function () {
				$(this).css({
					'zIndex': ++clickbadgeZ,
					'opacity': 1
				});
			}).delegate('a.clickbadge', 'mouseleave', function () {
				$(this).css({
					'opacity': 0.8
				});
			});


			$('.piechart').piechart();

		}



	}

	var _optionbar = function () {


			function init() {
				_obar
				.delegate('a.template', 'click', showFiles)
				.delegate('button.save_template', 'click', save)
				.delegate('a.save_template', 'mouseenter', focusName)
				.delegate('a.clear', 'click', clear)
				.delegate('a.preview', 'click', preview)
				.delegate('a.undo', 'click', undo)
				.delegate('a.redo', 'click', redo)
				.delegate('a.code', 'click', codeView);
			}

			function save() {
				var name = $('#new_template_name').val();
				if (!name) return false;
				_save();
				var loader = $('#new_template-ajax-loading').css({ 'display': 'inline' }),
					modules = $('#new_template_modules').is(':checked'),
					overwrite = $('#new_template_overwrite').is(':checked'),
					content = _getContent();
					
				_ajax('create_new_template', {
					name: name,
					modules: modules,
					overwrite: overwrite,
					template: $('#mymail_template').val(),
					content: content
				}, function (response) {
					loader.hide();
					if(response.success){
						window.location = response.url;
					}else{
						alert(response.msg);
					}
				}, function(jqXHR, textStatus, errorThrown){
					loader.hide();
					alert(textStatus+' '+jqXHR.status+': '+errorThrown);
				});
				return false;
			}

			function undo() {

				if (_currentundo) {
					_container.addClass('noeditbuttons');
					_currentundo--;
					_setContent(_undo[_currentundo], 100, false);
					_content.val(_undo[_currentundo]);
					_obar.find('a.redo').removeClass('disabled');
					if (!_currentundo) $(this).addClass('disabled');
				}

				return false;
			}

			function redo() {
				var length = _undo.length;

				if (_currentundo < length - 1) {
					_container.addClass('noeditbuttons');
					_currentundo++;
					_setContent(_undo[_currentundo], 100, false);
					_content.val(_undo[_currentundo]);
					_obar.find('a.undo').removeClass('disabled');
					if (_currentundo >= length - 1) $(this).addClass('disabled');
				}

				return false;
			}

			function clear() {
				if (confirm('remove all modules?')) {
					_container.addClass('noeditbuttons');
					var modules = _iframe.contents().find('.module');
					var modulecontainer = _iframe.contents().find('.modulecontainer');
					modulecontainer.slideUp(function () {
						modules.remove();
						modulecontainer.show();
						_refresh();
						_save();
					});
				}
				return false;
			}

			function preview() {

				var _this = $(this),
					content = _getContent(),
					subject = _subject.val(),
					title = _title.val();
				
				if(_obar.find('a.preview').is('.loading')) return false;

				_obar.find('a.preview').addClass('loading');
				_ajax('set_preview', {
					content: content,
					issue: $('#mymail_autoresponder_issue').val(),
					subject: subject
				}, function (response) {
					_obar.find('a.preview').removeClass('loading');
					
					$('#mymail_campaign_preview_iframe').attr('src', ajaxurl + '?action=mymail_get_preview&hash=' + response.hash + '&_wpnonce=' + response.nonce);
					tb_show((title ? sprintf(mymailL10n.preview_for, '"' + title + '"') : mymailL10n.preview), '#TB_inline?hash=' + response.hash + '&_wpnonce=' + response.nonce + '&width='+(Math.min(900, $(window).width()-250))+'&height='+($(window).height()-100)+'&inlineId=mymail_campaign_preview', null);

				}, function(jqXHR, textStatus, errorThrown){
					_obar.find('a.preview').removeClass('loading');
					alert(textStatus+' '+jqXHR.status+': '+errorThrown);
				});

			}

			function hide() {
				_obar.remove();
			}
			
			function focusName() {
				$('#new_template_name')
				.on('focus', function(){
					$(document).unbind('keypress.mymail').bind('keypress.mymail', function (event) {
						if(event.keyCode == 13) {
							save();
							return false;
						}
					});
				}).select().focus()
				.on('blur', function(){
					$(document).unbind('keypress.mymail');
				});
			}

			function showFiles(name) {
				var $this = $(this);
					
				$this.parent().find('ul').eq(0).slideToggle(100);
				return false;
			}

			function codeView() {
				var isRaw = !_iframe.is(':visible'),
					content = isRaw ? _content.val() : _getContent();

				if (!isRaw) {
					_obar.find('a.code').addClass('loading');
					_disable();
					_ajax('toogle_codeview', {
						content: content,
						_wpnonce: wpnonce
					}, function (response) {
						_obar.find('a.code').addClass('active').removeClass('loading');
						_iframe.hide();
						_content.val(response.content).show();
						_obar.find('a').not('a.redo, a.undo, a.code').addClass('disabled');
						_container.addClass('noeditbuttons');
					}, function(jqXHR, textStatus, errorThrown){
						_obar.find('a.code').addClass('active').removeClass('loading');
						_enable();
						alert(textStatus+' '+jqXHR.status+': '+errorThrown);
					});
				} else {
					_iframe.show();
					_setContent(_content.val());
					_content.hide();
					_obar.find('a.code').removeClass('active');
					_obar.find('a').not('a.redo, a.undo, a.code').removeClass('disabled');
					_container.removeClass('noeditbuttons');
					_editButtons();
					_enable();
				}
				return false;
			}

			init();

			return {
				hide: function () {
					hide();
				}
			}
		}



	var _editbar = function () {

			var bar = $('#editbar'),
				base, contentheights = {
					'img': 60,
					'single': 80,
					'multi': 100,
					'btn': -60,
					'auto': 10
				},
				imagepreview = bar.find('.imagepreview'),
				imagewidth = bar.find('.imagewidth'),
				imageheight = bar.find('.imageheight'),
				imagelink = bar.find('.imagelink'),
				imageurl = bar.find('.imageurl'),
				imagealt = bar.find('.imagealt'),
				buttonlink = bar.find('.buttonlink'),
				buttonalt = bar.find('.buttonalt'),
				buttonnav = bar.find('.button-nav'),
				buttontabs = bar.find('ul.buttons'),
				current, currentimage, currenttext, currenttag, assetstype, assetslist, itemcount, checkForPostsInterval,
				mediauploader = typeof wp !== 'undefined' && typeof wp.media !== 'undefined';

			function init() {
				bar
				.delegate('input.live', 'keyup change', change)
				.delegate('#mymail-editor', 'keyup change', change)
				.delegate('button.save', 'click', save)
				.delegate('.cancel', 'click', cancel)
				.delegate('a.remove', 'click', remove)
				.delegate('a.reload', 'click', loadPosts)
				.delegate('a.add_image', 'click', openMedia)
				.delegate('a.add_image_url', 'click', openURL)
				.delegate('.imagelist li', 'click', choosePic)
				.delegate('.imagelist li', 'dblclick', save)
				.delegate('#post_type_select input', 'change', loadPosts)
				.delegate('.postlist li', 'click', choosePost)
				.delegate('.load-more-posts', 'click', loadMorePosts)
				.delegate('a.btnsrc', 'click', changeBtn)
				.delegate('.imagepreview', 'click', toogleImgZoom)
				.delegate('.type.auto a.nav-tab', 'click', openTab)
				.delegate('select.check-for-posts', 'change', checkForPosts);
				
				
				buttonnav.delegate('a', 'click', function(){
					$(this).parent().find('a').removeClass('nav-tab-active');
					$(this).parent().parent().find('ul.buttons').hide();
					var hash = $(this).addClass('nav-tab-active').attr('href');
					$('#tab-'+hash.substr(1)).show();
					return false;
				});
				
				imageurl.on('paste change', function(e){
					var $this = $(this);
					setTimeout(function(){
						var url = dynamicImage($this.val()),
							img = new Image();
						if(url){
							loader();
							img.onload = function(){
								imagepreview.attr('src', url);
								loader(false);
							};
							img.onerror = function(){
								if(e.type != 'paste') alert(sprintf(mymailL10n.invalid_image, '"'+url+'"'));
							};
							img.src = url;
						}
					}, 1);
				});
				
				$('#advanced_embed_options_post_type').on('change', function(){
					
					var cats = $('#advanced_embed_options_cats');
					cats.find('select').prop('disabled', true);
					loader();
					_ajax('get_post_term_dropdown', {
						posttype: $(this).val()
					}, function (response) {
						loader(false);
						if (response.success) {
							cats.html(response.html);
							if(currenttag && currenttag.terms){
								var taxonomies = cats.find('.advanced_embed_options_taxonomy_wrap');
								$.each(currenttag.terms, function(i, term){
									if(!term) return;
									var term_ids = term.split(',');
									$.each(term_ids, function(j, id){
										var select = taxonomies.eq(i).find('select').eq(j);
										if(!select.length){
											var last = taxonomies.eq(i).find('select').last(),
												select = last.clone();
											select.insertAfter(last);
											$('<span> '+mymailL10n.or+ ' </span>').insertBefore(select);
										}
										select.val(id);
									});
								});
							}
				

							
						}
						checkForPosts();
					}, function(jqXHR, textStatus, errorThrown){
					
						loader(false);
						alert(textStatus+' '+jqXHR.status+': '+errorThrown);
			
					});
					
				});
				
				
				if(bar.draggable){
					bar.draggable({
						'distance': 20,
						'axis': 'y'
					});
				}
				
				if(mediauploader){
				
					var ed_id = wp.media.editor.id();
					var ed_media = wp.media.editor.get( ed_id );
						ed_media = 'undefined' != typeof( ed_media ) ? ed_media : wp.media.editor.add( ed_id );
						
					ed_media.on('close', loadPosts);
					
				}
			}


			function openTab(id, trigger) {
				var $this;
				if(typeof id == 'string'){
					$this = base.find('a[href="'+id+'"]');
				}else{
					$this = $(this);
					id = $this.attr('href');
				}
				
				$this.parent().find('a.nav-tab').removeClass('nav-tab-active');
				$this.addClass('nav-tab-active');
				base.find('.tab').hide();
				base.find(id).show();
				if(id == '#advanced_embed_options' && trigger !== false) $('#advanced_embed_options_post_type').trigger('change');
				return false;
			}


			function checkForPosts() {
				clearInterval(checkForPostsInterval);
				loader();
				$('#advanced_embed_options').find('h4').eq(0).html('&hellip;');
				checkForPostsInterval = setTimeout(function(){
				
				var post_type = bar.find('#advanced_embed_options_post_type').val(),
					relative = bar.find('#advanced_embed_options_relative').val(),
					taxonomies = bar.find('.advanced_embed_options_taxonomy_wrap'),
					extra = [];
					
					$.each(taxonomies, function(i){
						var selects = $(this).find('select'),
							values = [];
						$.each(selects, function(){
							var val = parseInt($(this).val(), 10);
							if(val != -1 && $.inArray(val, values) == -1) values.push(val);
						});
						values = values.join(',');
						if(values) extra[i] = values;
					});
					
				_ajax('check_for_posts', {
					post_type:post_type,
					relative:relative,
					extra:extra
				
				}, function (response) {
					loader(false);
					if (response.success) {
						$('#advanced_embed_options').find('h4').eq(0).html(response.title);
					}
				}, function(jqXHR, textStatus, errorThrown){
				
					loader(false);
					alert(textStatus+' '+jqXHR.status+': '+errorThrown);
		
				});
				
				}, 500);
				
				return false;
			}
			
			function dynamicImage(val, w, h) {
				if(/^\{([a-z0-9-_]+)_image:-?[0-9,;]+(\|\d+)?\}$/.test(val)){
					val = mymaildata.ajaxurl+'?action=mymail_image_placeholder&tag='+val.replace('{','').replace('}','')+'&w='+(w || imagewidth.val())+'&h='+(h || imageheight.val());
				}
				return val
			}
			
			function isDynamicImage(val) {
				if(-1 !== val.indexOf('?action=mymail_image_placeholder&tag=')){
					var m = val.match(/([a-z0-9-_]+)_image:-?[0-9,;]+(\|\d+)?/);
					return '{'+m[0]+'}';
				}
				return false;
			}
			
			function change() {
				current.element.html($(this).val());
			}

			function loader(bool) {
				if(bool === false){
					$('#editbar-ajax-loading').hide();
					bar.find('.buttons').find('button').prop('disabled', false);
				}else{
					$('#editbar-ajax-loading').css({ 'display': 'inline' });
					bar.find('.buttons').find('button').prop('disabled', true);
				}
			}

			function save() {

				if (current.type == 'img') {
				
					if(imageurl.val()){
						
						currentimage = {
							id: null,
							name: '',
							src: dynamicImage(imageurl.val())
						};
						
						
					}
					
					if(currentimage){
						loader();
						
						_ajax('create_image', {
							id: currentimage.id,
							src: currentimage.src,
							width: imagewidth.val(),
							height: imageheight.val()
						}, function (response) {
						
							loader(false);
	
							if (response.success) {
								imagepreview.attr('src', response.image.url);
								currentimage = response.image;
								currentimage['name'] = imagealt.val();
								current.element.removeAttr('src').attr({
									'src': currentimage.url,
									'width': currentimage.width,
									'height': currentimage.height,
									'alt': currentimage.name
								});
	
							}
							imagealt.val('');
							close();
						}, function(jqXHR, textStatus, errorThrown){
						
							loader(false);
							alert(textStatus+' '+jqXHR.status+': '+errorThrown);
			
						});
						
					}else{
						current.element.attr({
							'alt': imagealt.val()
						});
					}

					if (current.element.parent().is('a')) current.element.unwrap();
					var link = imagelink.val();
					if (link) current.element.wrap('<a href="' + link + '"></a>');

				} else if (current.type == 'btn') {

					var link = buttonlink.val();
					if(!link && !confirm(mymailL10n.remove_btn)) return false;
					var btnsrc = base.find('a.active').find('img').attr('src');
					var img = new Image();
					img.onload = function(){
					
						current.element.attr({
							'src': btnsrc,
							'width': img.width || current.element.width(),
							'height': img.height || current.element.height(),
							'alt': buttonalt.val(),
							'title': buttonalt.val()
						});
						
						if(current.element.parent().is('a')) current.element.unwrap('a');
						
						(link) ? current.element.wrap('<a href="' + link + '"></a>') : current.element.remove();
						close();
					}
					img.src = btnsrc;
					return false;

				} else if (current.type == 'auto') {

					var insertmethod = $('#embedoption-bar').find('.nav-tab-active').data('type');
					
					if('dynamic' == insertmethod){
					
						var post_type = bar.find('#advanced_embed_options_post_type').val(),
							relative = bar.find('#advanced_embed_options_relative').val(),
							contenttype = bar.find('#advanced_embed_options_content').val(),
							taxonomies = bar.find('.advanced_embed_options_taxonomy_wrap'),
							tag = '{'+post_type+':'+relative,
							extra = [];
							
						$.each(taxonomies, function(i){
							var selects = $(this).find('select'),
								values = [];
							$.each(selects, function(){
								var val = parseInt($(this).val(), 10);
								if(val != -1 && $.inArray(val, values) == -1) values.push(val);
							});
							values = values.join(',');
							if(values) extra[i] = values;
						});
						
						
						extra = extra.join(';');
						if(extra) extra = ';'+extra;
						
						currenttext = {
							title: '{'+post_type+'_title:'+relative+extra+'}',
							link: '{'+post_type+'_link:'+relative+extra+'}',
							content: '{'+post_type+'_content:'+relative+extra+'}',
							excerpt: '{'+post_type+'_excerpt:'+relative+extra+'}',
							image: '{'+post_type+'_image:'+relative+extra+'}'
						};
						
						current.element.attr('data-tag', '{'+post_type+':'+relative+extra+'}').data('tag', '{'+post_type+':'+relative+extra+'}');
						
						
					}else{
						
						var contenttype = $('.embed_options_content:checked').val();
						current.element.removeAttr('data-tag').removeData('tag');
						
					}
					
					if (currenttext) {

						current.elements.headlines.html('');
						if (currenttext.title) current.elements.headlines.eq(0).html(currenttext.title);
						
						
						if (currenttext.link){
							
							if(current.elements.buttons.length){
								current.elements.buttons.last().parent().attr('href', currenttext.link);
								current.elements.buttons.not(':last').remove();
							}else{
							
								currenttext.content += '<br/><a href="'+currenttext.link+'" title="'+mymailL10n.read_more+'">'+mymailL10n.read_more+'</a>';
								
							}
						}

						if (currenttext.content && current.elements.bodies.length) {
							var contentcount = current.elements.bodies.length,
								content = ('excerpt' == contenttype && currenttext.excerpt) ? currenttext.excerpt : currenttext.content,
								contentlength = content.length,
								partlength = (insertmethod == 'static') ? Math.ceil(contentlength / contentcount) : contentlength;
								
							for (var i = 0; i < contentcount; i++) {
								current.elements.bodies.eq(i).html(content.substring(i * partlength, i * partlength + partlength));
							}


						}
							

						if (currenttext.image && current.elements.images.length) {
							loader();
							
							var imgelement = current.elements.images.eq(0);
							
							imgelement.onload = function(){
								_resize();
							};
							
							if (insertmethod == 'static'){
								_ajax('create_image', {
									id: currenttext.image.id,
									width: current.elements.images.eq(0).width(),
								}, function (response) {
								
									if (response.success) {
										loader(false);
										
										imgelement.attr({
											'src': response.image.url,
											'width': response.image.width,
											'height' : response.image.height,
											'alt' : currenttext.title
										});
										
										if (imgelement.parent().is('a')) {
											imgelement.unwrap();
										}
	
										if (currenttext.link) {
											imgelement.wrap('<a>');
											imgelement.parent().attr('href', currenttext.link);
										} 
									}
									close();
								}, function(jqXHR, textStatus, errorThrown){
								
									loader(false);
									alert(textStatus+' '+jqXHR.status+': '+errorThrown);
				
								});
								
								return false;
								
							}else{
							
								var width = imgelement.width();
								
								imgelement.removeAttr('height').attr({
									'src': dynamicImage(currenttext.image, width),
									'width': width,
									'alt' : currenttext.title
								});
							}
						}
					}

				} else if (current.type == 'multi') {

					if(isTinyMCE) $('#mymail-editor-tmce').trigger('click');

				}
				
				close();
				return false;
			}

			function remove() {
				if (current.element.parent().is('a')) current.element.unwrap();
				current.element.remove();
				close();
				return false;
			}

			function cancel() {
				switch (current.type) {
				case 'img':
					break;
				case 'btn':
					if (!current.element.attr('src')) current.element.remove();
					break;
				default:
					current.element.html(current.content);
				}
				close();
				return false;
			}

			function changeBtn() {
				var _this = $(this),
				link = _this.data('link');
				base.find('.btnsrc').removeClass('active');
				_this.addClass('active');
				
				buttonalt.val(_this.attr('title'));
				
				if(link){
					var pos;
					buttonlink.val(link);
					if((pos = (link+'').indexOf('USERNAME', 0)) != -1){
						buttonlink.focus();
						selectRange(buttonlink[0], pos, pos+8);
					};
					
				}
				return false;
			}
			
			function toogleImgZoom() {
				$(this).toggleClass('zoom');
			}
			
			function choosePic(event, el) {
				var _this = el || $(this),
					id = _this.data('id'),
					name = _this.data('name'),
					src = _this.data('src');
					
				currentimage = {
					id: id,
					name: name,
					src: src
				};
				
				loader();
				
				assetslist.find('li.selected').removeClass('selected');
				_this.addClass('selected');
				
				imagealt.val(name);
				imageurl.val('');
				imagepreview.attr('src', src).on('load', function () {
				
					imagepreview.off('load');
					
					current.width = imagepreview.width();
					current.height = imagepreview.height();
					current.asp = current.width / current.height;
					
					loader(false);
/*
					current.element.attr({
						'src': src,
						'width': imagewidth.val(),
						'height': Math.round(imagewidth.val()/current.asp)
					});
*/

				});

				return currentimage;
			}

			function choosePost() {
				var _this = $(this),
					id = _this.data('id'),
					name = _this.data('name'),
					link = _this.data('link'),
					thumbid = _this.data('thumbid');
					
				if (current.type == 'btn') {
				
					buttonlink.val(link);
					buttonalt.val(name);
					assetslist.find('li.selected').removeClass('selected');
					_this.addClass('selected')

				} else {
					loader();
					_ajax('get_post', {
						id: id
					}, function (response) {
						loader(false);
						assetslist.find('li.selected').removeClass('selected');
						_this.addClass('selected')
						if (response.success) {
							currenttext = {
									title: response.title,
									link: response.link,
									content: response.content,
									excerpt: response.excerpt,
									image: response.image ? {
										id: response.image.id,
										src: response.image.src,
										name: response.image.name
									} : false
							};
							
							base.find('#editbarinfo').html(mymailL10n.curr_selected + ': <span>' + currenttext.title + '</span>');
							
						}
					}, function(jqXHR, textStatus, errorThrown){
					
						loader(false);
						assetslist.find('li.selected').removeClass('selected');
						alert(textStatus+' '+jqXHR.status+': '+errorThrown);
			
					});

				}
				return false;
			}

			function open(data) {
			

				current = data;
				var el = data.element,
					top = (type != 'img') ? data.offset.top : 0,
					name = data.name,
					type = data.type,
					content = $.trim(el.html()),
					offset;

				base = bar.find('div.type.' + type);

				current.width = el.width();
				current.height = el.height();
				current.asp = current.width / current.height;

				current.content = content;
				
				currenttag = current.element.data('tag');
				
				if(type == 'btn'){
					var btnsrc = el.attr('src');
					
					buttonlink.val(el.parent().is('a') ? el.parent().attr('href') : '');
						
					if(buttonnav.length){
					
						var button = bar.find("img[src='"+btnsrc+"']");
							
						if(button.length){
							bar.find('ul.buttons').hide();
							var b = button.parent().parent().parent();
							bar.find('a[href="#'+b.attr('id').substr(4)+'"]').trigger('click');
						}else{
							$.each(bar.find('.button-nav'), function(){
								$(this).find('.nav-tab').eq(0).trigger('click');
							});
						}
						
					}
				}else if(type == 'auto'){
				
				
					if(currenttag){
					
						var parts = currenttag.substr(1, currenttag.length - 2).split(':'),
							extra = parts[1].split(';'),
							relative = parseInt(extra.shift(), 10),
							terms = extra.length ? extra : null;
							
						currenttag = {
							'post_type' : parts[0],
							'relative' : relative,
							'terms' : terms
						};
						
						openTab('#advanced_embed_options', false);
						
						$('#advanced_embed_options_post_type').val(currenttag.post_type).trigger('change');
						$('#advanced_embed_options_relative').val(currenttag.relative).trigger('change');
						
					}else{
					
						openTab('#simple_embed_options', false);
						
					}

				}
				
				offset = ($(document).height() - top < 600 ? -bar.height() + 100 : (top > contentheights[type] + 50) ? contentheights[type] : current.height + 50);

				_container.addClass('noeditbuttons');

				_scroll(_container.offset().top + top + offset - 200, function () {

					bar.find('h2').eq(0).html(name);
					bar.find('div.type').hide();

					bar.find('div.' + type).show();
					bar.css({
						top: Math.min(top + offset,_container.height()-bar.height() )
					})

					loader();
					
					if (type == 'single') {
					
						base.find('input').val(content.replace(/&amp;/g, '&'));

					} else if (type == 'img') {

						var maxwidth = parseInt(el[0].style.maxWidth, 10) || el.parent().width() || null;
						var maxheight = parseInt(el[0].style.maxHeight, 10) || null;
						var url = isDynamicImage(el.attr('src')) || '';


						if (el.parent().is('a')) imagelink.val(el.parent().attr('href'));

						imagealt.val(el.attr('alt'));
						
						imageurl.val(url);
						
						$('.imageurl-popup').toggle(!!url);
						imagepreview.removeAttr('src').attr('src', el.attr('src'));
						assetstype = 'attachment';
						assetslist = base.find('.imagelist');
						loadPosts();
						currentimage = null;


					} else if (type == 'btn') {

						assetstype = 'link';
						assetslist = base.find('.postlist');
						loadPosts();
						
						$.each(base.find('.buttons img'), function () {
							var _this = $(this);
							_this.css('background-color',el.css('background-color'));
							(_this.attr('src') == btnsrc) ? _this.parent().addClass('active') : _this.parent().removeClass('active');
						});


					} else if (type == 'auto') {

						assetstype = 'post';
						assetslist = base.find('.postlist');
						loadPosts();
						current.elements = {
							headlines: current.element.find('div[data-editable*="Headline"]'),
							bodies: current.element.find('div[data-editable*="Body"]'),
							buttons: current.element.find('img[data-editable*="Button"]'),
							images: current.element.find('img[data-editable!="Button"]')
						}
						
						

						
					}

					bar.fadeIn(100, function () {

						if (type == 'single') {

							bar.find('input').focus().select();

						} else if (type == 'img') {

							imagewidth.val(maxwidth);
							imageheight.val(maxheight);

						} else if (type == 'btn') {

							imagewidth.val(maxwidth);
							imageheight.val(maxheight);

						} else if (type == 'multi') {
							
							if(isTinyMCE) tinymce.execCommand('mceRemoveControl', false, 'mymail-editor');
							editor.prependTo(bar.find('div.' + type));
							$('#mymail-editor').val(content);
							
							if(isTinyMCE) tinymce.execCommand('mceAddControl', false, 'mymail-editor');
							if(isTinyMCE && tinymce.activeEditor){
								tinymce.activeEditor.onKeyUp.add(function () {
									mceUpdater(this);
								});
								tinymce.activeEditor.onExecCommand.add(function () {
									mceUpdater(this);
								});
								tinymce.execCommand('mceFocus', false, 'mymail-editor');
							}
							

						}
						

						$(document).on('keypress.mymail', function (event) {
							switch (event.keyCode) {
							case 27:
								cancel();
								return false;
								break;
							case 13:
								save();
								return false;
								break;
							}
						});
					});

					loader(false);

				});


			}

			function loadPosts() {
				assetslist.empty();
				loader();
				
				var posttypes = $('#post_type_select').find('input:checked').serialize();
				
				_ajax('get_post_list', {
					type: assetstype,
					posttypes: posttypes,
					offset: 0
				}, function (response) {
					loader(false);
					if (response.success) {
						itemcount = response.itemcount;
						displayPosts(response.html, true);
					}
				}, function(jqXHR, textStatus, errorThrown){
				
					loader(false);
					alert(textStatus+' '+jqXHR.status+': '+errorThrown);
					
				});
			}
			
			function loadMorePosts() {
				var $this = $(this),
				offset = $this.data('offset'),
				type = $this.data('type');
				loader();
				
				var posttypes = $('#post_type_select').find('input:checked').serialize();
				
				_ajax('get_post_list', {
					type: type,
					posttypes: posttypes,
					offset: offset,
					itemcount: itemcount
				}, function (response) {
					loader(false);
					if (response.success) {
						itemcount = response.itemcount;
						$this.parent().remove();
						displayPosts(response.html, false);
					}
				}, function(jqXHR, textStatus, errorThrown){
				
					loader(false);
					alert(textStatus+' '+jqXHR.status+': '+errorThrown);
					
				});
				return false;
			}
			
			function displayPosts(html, replace) {
				if(replace) assetslist.empty();
				if(!assetslist.html()) assetslist.html('<ul></ul>');
				
				assetslist.find('ul').append(html);
			}

			function openURL() {
				$('.imageurl-popup').toggle();
				imageurl.focus().select();
				return false;
			}

			function openMedia() {
			
				if(mediauploader){
				
					var send_attachment = wp.media.editor.send.attachment;
					
					wp.media.editor.send.attachment = function(props, attachment) {
					
						var el = $('img');
	
						el.data({
							id: attachment.id,
							name: attachment.name,
							src: attachment.url
						});
				
						wp.media.editor.send.attachment = send_attachment;
						choosePic(null, el);
						
					}
					
					wp.media.editor.open();
					
						
				
				}else{
				
					var old_tb_remove = window.tb_remove;
					var send_to_editor = window.send_to_editor;
					
						
					window.tb_remove = function () {
						old_tb_remove();
						loadPosts();
						window.tb_remove = old_tb_remove;
						return false;
					}
	
					window.send_to_editor = function (html) {
						var el = $('img', html);
	
						el.data({
							id: html.match(/wp-image-(\d+)/)[1],
							name: el.attr('title'),
							src: el.attr('src')
						});
	
						window.send_to_editor = send_to_editor;
	
						choosePic(null, el);
						tb_remove();
						return false;
					}
	
					tb_show($(this).html(), 'media-upload.php?type=image&amp;post_id=&amp;TB_iframe=true');
				}
				
				return false;
			}




			function mceUpdater(editor) {
				clearTimeout(timeout);
				timeout = setTimeout(function () {
					var val = $.trim(editor.save());
					val = val.replace(/^<p>|<\/p>$/g, '').replace(/<\/p>\s+<p>/g, '<br><br>');
					current.element.html(val);
				}, 100);
			}

			function close() {
				bar.fadeOut(100);
				$(document).unbind('keyup.mymail, keypress.mymail');

				if (current.type == 'multi') {
					if(isTinyMCE){
						$('#mymail-editor-tmce').trigger('click');
						if (tinymce.activeEditor) tinymce.execCommand('mceRemoveControl', false, 'mymail-editor');
					}
					editor.appendTo('#mymail_editor_holder');
				}
				//current = currentimage = currenttext = null;
				_refresh();
				_save();
			}

			init();

			return {
				open: function (data) {
					open(data);
				},
				close: function () {
					close();
				}
			}
		};



	var _modules = function () {

			var container = _iframe.contents().find('.modulecontainer'),
				elements, modules = {},
				count = 0,
				modulesraw = $('#modules'),
				preview = $('#mymail_type_preview'),
				previewimg = $('<img>').appendTo(preview),
				selector;

			function up() {
				var module = $(this).parent().parent();
				module.insertBefore(module.prev('.module'));
				_refresh();
				_save();
				return false;
			}

			function down() {
				var module = $(this).parent().parent();
				module.insertAfter(module.next('.module'));
				_refresh();
				_save();
				return false;
			}

			function add() {
				var module = $(this).parent().parent(),
					offset = Math.max(50, $(this).offset().top+55-selector.height()/2);
					
				selector.data('current', module).css({
					'top': offset
				}).show();
				return false;
			}

			function duplicate() {
				var module = $(this).parent().parent(),
					clone = module.clone().hide();
					
				_container.addClass('noeditbuttons');
				
				clone.insertAfter(module);
				
				_resize(clone.height());
				
				clone.slideDown(function(){
					_refresh();
					_save();
				});
				_scroll(clone.offset().top + 200);
				return false;
			}
			
			function auto() {
				var module = $(this).parent().parent();
				var data = {
					element: module,
					name: module.data('module'),
					type: 'auto',
					offset: module.offset()
				}
				editbar.open(data);
				return false;
			}

			function addmodule() {
				var module = selector.data('current');
				insert($(this).attr('title'), ((module.is('.module')) ? module : false));
				hideselector();
				return false;
			}

			function hideselector() {
				selector.hide();
				return false;
			}

			function remove() {
				var module = $(this).parent().parent();
				module.fadeTo(100, 0, function () {
					_container.addClass('noeditbuttons');
					module.slideUp(function () {
						module.remove();
						_refresh();
						_save();
					});
				});
				return false;
			}

			function insert(name, before) {

				if (!modules[name]) return false;
				var clone = modules[name].clone();
				_container.addClass('noeditbuttons');
				
				(before) ? clone.hide().insertBefore(before) : clone.hide().appendTo(container);

				_resize(clone.height());

				clone.slideDown(function () {
					clone.addClass('active');
					_refresh();
					_save();
				});
				
				_scroll(clone.offset().top + 200);

			}

			function showPreview() {
				//soon
				return false;
				var $this = $(this),
					type = $this.data('type');
				if (!type) return false;
				var pos = $(this).position();
				previewimg.attr('src', '');
				preview.css({
					top: pos.top + 50,
					right: 145
				}).stop().show();
			}

			function hidePreview() {
				preview.stop().fadeOut(100);
			}

			function init() {
				_container.delegate('a.addmodule', 'click', addmodule).delegate('a.addmodule', 'mouseenter', showPreview).delegate('a.addmodule', 'mouseleave', hidePreview).delegate('#moduleselector', 'mouseleave', hideselector);
				refresh();
			}

			function refresh() {

				elements = $(modulesraw.val()).add(_iframe.contents().find('[data-module]'));

				container = _iframe.contents().find('.modulecontainer');
				container
				.delegate('a.up', 'click', up)
				.delegate('a.down', 'click', down)
				.delegate('a.add', 'click', add)
				.delegate('a.auto', 'click', auto)
				.delegate('a.duplicate', 'click', duplicate)
				.delegate('a.remove', 'click', remove);

				var html = '<ul id="moduleselector">';

				//reset
				modules = {}, count = 0;
				//add module buttons and add them to the list
				$.each(elements, function () {
					var $this = $(this);
					if ($this.is('.module')) {
						var name = $this.data('module'),
							p, auto = ($this.data('auto') ? '<a class="btn auto" title="auto module">' + mymailL10n.auto + '</a>' : '');
						$('<div class="modulebuttons">' + auto + '<a class="btn add" title="add module">' + mymailL10n.add + '</a><a class="btn duplicate" title="' + mymailL10n.duplicate_module + '">&#10010;</a><a class="btn up" title="' + mymailL10n.move_module_up + '">&#9650;</a><a class="btn down" title="' + mymailL10n.move_module_down + '">&#9660;</a><a class="remove btn" title="' + mymailL10n.remove_module + '">&#10005;</a></div>').prependTo($this);
						if (!modules[name]) {
							modules[name] = $this;
							count++;
							p = $this.data('type') ? ' data-type="' + $this.data('type') + '"' : '';
							html += '<li><a class="btn addmodule" title="' + name + '"' + p + '>' + name + '</a></li>';
						}
					}
				});
				
				html += '</ul>';
				selector = $(html);
				selector.prependTo(_container);

				if (!container.find('.modulebuttons.main').length && count) container.prepend('<div class="modulebuttons main"><a class="btn add" title="' + mymailL10n.add_module + '" style="display:block">' + mymailL10n.add_module + '</a></div>')

				_refresh();

			}

			init();
			return {
				refresh: function () {
					refresh();
				}
			}
		}




	function _scroll(pos, callback) {
		animateDOM.animate({
			'scrollTop': pos
		}, callback &&
		function () {
			callback();
		});
	}

	function _refresh() {
		if (!_disabled) {
			_editButtons();
		} else {
			_clickBadges();
		}
		_resize();
	}

	function _resize(extra) {
		if (!iframeloaded) return false;
		var height = _iframe[0].contentWindow.document.body.offsetHeight || _iframe.contents().find("html")[0].innerHeight || _iframe.contents().find("html").height();
		_iframe.attr("height", Math.max(500, height + 10 + (extra || 0)));
	}

	//write the html into the content;

	function _save() {
		if (!_disabled && iframeloaded) {

			var m = _iframe.contents().find(".modulebuttons");

			var content = _getFrameContent();
			
			var length = _undo.length,
				lastundo = _undo[length];
			
			if (lastundo != content) {

				_content.val(style + content.replace(/<\/div>/g, '</div>\n'));

				_undo = _undo.splice(0, _currentundo + 1);

				_undo.push(content);
				if (length >= mymailL10n.undosteps) _undo.shift();
				_currentundo = _undo.length - 1;

				if (_currentundo) _obar.find('a.undo').removeClass('disabled');
				_obar.find('a.redo').addClass('disabled');

			}

		}
	}

	function _editButtons() {
		_container.find('.content.btn').remove();
		var elements = _iframe.contents().find('[data-editable]');
		
		$.each(elements, function () {
			var $this = $(this),
				offset = $this.offset(),
				top = offset.top + 40,
				left = offset.left,
				name = $this.data('editable'),
				type;

			if ($this.is('img')) {
				(name.match(/button/i)) ? type = 'btn' : type = 'img';
				top += 18;
			} else if ($this.data('multi')) {
				type = 'multi';
			} else {
				type = 'single';
			}

			$('<a class="editbutton content btn" title="' + $this.data('editable') + '">' + mymailL10n.edit + '</a>').css({
				top: top,
				left: left
			}).data('type', type).data('name', name).data('offset', offset).data('element', $this).appendTo(_container);
			
			$this.unbind('dblclick').bind('dblclick', function(){
				editbar.open({
					'offset': offset,
					'type' : type,
					'name' : name,
					'element' : $this
				});
			});
		});

		$.each(_iframe.contents().find('div.btn'), function () {
			var $this = $(this),
				name = $this.attr('title'),
				offset = $this.offset(),
				top = offset.top + 40,
				left = offset.left;

			$('<a class="addbutton content btn" title="' + mymailL10n.add_button + '">+</a>').css({
				top: top,
				left: left
			}).data('offset', offset).data('name', name).data('element', $this).appendTo(_container);

		});
		
		$.each(_iframe.contents().find('[data-repeatable]'), function () {
			var $this = $(this),
				name = $this.data('repeatable'),
				offset = $this.offset(),
				top = offset.top + 48,
				left = offset.left;

			$('<a class="addrepeater content btn" title="' + sprintf(mymailL10n.add_s, name) + '">+</a>').css({
				top: top,
				left: left-20
			}).data('offset', offset).data('name', name).data('element', $this).appendTo(_container);
			$('<a class="removerepeater content btn" title="' + sprintf(mymailL10n.remove_s, name) + '">-</a>').css({
				top: top+18,
				left: left-20
			}).data('offset', offset).data('name', name).data('element', $this).appendTo(_container);

		});

		_container.removeClass('noeditbuttons');

	}

	function _clickBadges() {
		_container.find('.clickbadge').remove();
		var stats = $('#mymail_click_stats').data('stats'),
			total = parseInt(stats.total, 10);

		if (!total) return;
		
		$.each(stats.clicks, function (link, ids) {

			var links = _iframe.contents().find('a[href="' + link + '"]');

			if (links.length) {
				links.css({
					'display': 'inline-block'
				});
				$.each(ids, function (i, count) {
				
					var el = links.eq(i),
						offset = el.offset(),
						top = offset.top,
						left = offset.left + 10,
						percentage = (count / total) * 100, badge = $('<a class="clickbadge" title="' + sprintf(mymailL10n.clickbadge, link, count, percentage.toFixed(2) + '%') + '"><span style="width:' + Math.max(0, percentage - 2) + '%">' + (percentage < 1 ? '&lsaquo;1' : Math.round(percentage)) + '%</span></a>').css({
						top: top,
						left: left,
						opacity: 0.8
					}).appendTo(_container);
				});

			}

		});
	}


	function _changeColor(from, to, element, save) {
		if(!from) from = to;
		if(!to) return false;
		if(from == to) return false;
		var raw = _getContent(),
			reg = new RegExp(from, 'gi'),
			m = $('#modules').val();

		if(element) element.data('value', to);
		
		$('#modules').val(m.replace(reg, to));
		
		
		if(save){
			_setContent(raw.replace(reg, to), 300);
		}else{
			_content.val(raw.replace(reg, to));
		}
		
	}

	function _changeBG(file) {
		var raw = _getContent(),
			html = raw.replace(/body{background-image:url\(.*}/i, '');

		if (file) {
			var s = (file) ? "\tbody{background-image:url('" + file + "');background-repeat:repeat-y no-repeat;background-position:top center;}" : '',
			html = html.replace(/<style.*?>/i, '<style type="text/css">' + s)
			//.replace(/<td /i, '<td background="'+base+file+'"');
			.replace(/<td/i, '<td background="' + file + '"');
			//.replace(/background="([^"]*)"/i,'background="'+base+file+'"');
			$('ul.backgrounds > li > a').css({
				'background-image': "url('" + file + "')"
			});
		} else {

			var parts = html.match(/<td(.*)background="[^"]*"(.*)/i);

			if (parts) html = html.replace(parts[0], '<td ' + parts[1] + ' ' + parts[2]);
			//.replace(/<td(.*)background="([^"]*)"/i,'<td ');
			$('ul.backgrounds > li > a').css({
				'background-image': "none"
			});
			//.replace(/background="([^"]*)"/i,'background=""');
		}

		_setContent(html);
		return;
	}

	function _changeElements(version) {
		var raw = _getContent(),
			reg = /\/img\/version(\d+)\//g,
			to = '/img/' + version + '/';

		html = raw.replace(reg, to);

		var m = $('#modules').val();
		$('#modules').val(m.replace(reg, to));

		_setContent(html);

		return;
	}

	function _disable(buttononly) {
		$('#publishing-action').find('input').prop('disabled', true);
		if (buttononly !== true) $('input').prop('disabled', true);
	}

	function _enable() {
		$('#publishing-action').find('input').prop('disabled', false);
		$('input').prop('disabled', false);
	}

	function _getFrameContent() {
		if(typeof _iframe[0].contentWindow.document.body == 'null') return '';
		var content = _iframe[0].contentWindow.document.body.innerHTML;
		if (isIE7) {
			var s = content.replace("\n", '').match(/<style.*?>([^<]+)<\/style>/i);
			content = innerXHTML(_iframe[0].contentWindow.document.body);
			content = content
			//.replace(/<\/table>/g, '</table>\n')
			//.replace(/<!--/g, '\n<!--')
			.replace(/><\/img>/g, '>').replace(/ jquery(\d+)="(\d+)"/g, '').replace('<style type="text/css"></style>', '<style type="text/css">\n\t' + (s[1].replace(/(\r\n|\t)/g, '').replace(/}/g, '}\n\t').toLowerCase()) + '</style>');
		}
		return $.trim(content);
	}

	function _getContent() {
		var c = _content.val() || _getFrameContent();
		return c;
	}

	function _setContent(content, delay, saveit) {

		if (isIE7) {
			var s = content.replace("\n", '').match(/<style.*?>([^<]+)<\/style>/i),
				newel = document.createElement('style');

			newel.type = 'text/css';
			newel.styleSheet.cssText = s[1];
			_idoc.getElementsByTagName('head')[0].appendChild(newel);

			style = s[0];
		} else {
			($.browser.webkit || $.browser.mozilla) ? _iframe[0].contentWindow.document.body.innerHTML = content : _idoc.body.innerHTML = content;
		}

		if (delay !== false) {
			clearTimeout(timeout);
			timeout = setTimeout(function () {
				modules.refresh();
			}, delay || 100);
		}
		if (typeof saveit == 'undefined' || saveit === true) _save();
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
					errorCallback && errorCallback.call(this, jqXHR, textStatus, errorThrown);
				},
			dataType: "JSON"
		});
	}

	function _sanitize(string) {
		return $.trim(string).toLowerCase().replace(/ /g, '_').replace(/[^a-z0-9_-]*/g, '');
	}

	function _time() {

		var t, x, h, m, usertime = new Date(),
			elements = $('.time'),
			deliverytime = $('.deliverytime'),
			activecheck = $('#mymail_data_active'),
			servertime = parseInt(elements.data('timestamp'), 10) * 1000,
			seconds = false,
			offset = servertime - usertime.getTime() + (usertime.getTimezoneOffset() * 60000);

		var delay = (seconds) ? 1000 : 20000;

		function set() {
			t = new Date();

			usertime = t.getTime();
			t.setTime(usertime + offset);
			h = t.getHours();
			m = t.getMinutes();

			if (!_disabled && x && m != x[1] && !activecheck.is(':checked')) deliverytime.val(zero(h) + ':' + zero(m));
			x = [];
			x.push(t.getHours());
			x.push(t.getMinutes());
			if (seconds) x.push(t.getSeconds());
			for (var i = 0; i < 2; i++) {
				x[i] = zero(x[i]);
			};
			elements.html(x.join('<span style="text-decoration:blink">:</span>'));
			setTimeout(function () {
				set();
			}, delay);
		}

		function zero(value) {
			return (value < 10) ? '0' + value : value;
		}

		set();
	}


	function sprintf() {
		var a = Array.prototype.slice.call(arguments),
			str = a.shift();
		while (a.length) str = str.replace('%s', a.shift());
		return str;
	}

	if (document.selection && document.selection.createRange) {
	
		selectRange = function (input, startPos, endPos) {
			input.focus();
			input.select();
			var range = document.selection.createRange();
			range.collapse(true);
			range.moveEnd("character", endPos);
			range.moveStart("character", startPos);
			range.select();
			return true;
		}
		
	} else {
	
		selectRange = function (input, startPos, endPos) {
			input.selectionStart = startPos;
			input.selectionEnd = endPos;
			return true;
		}
	}

	if (window.getSelection) { // all browsers, except IE before version 9

		getSelect = function (input) {
			var selText = "";
			if (document.activeElement && (document.activeElement.tagName.toLowerCase() == "textarea" || document.activeElement.tagName.toLowerCase() == "input")) {
				var text = document.activeElement.value;
				selText = text.substring(document.activeElement.selectionStart, document.activeElement.selectionEnd);
			} else {
				var selRange = window.getSelection();
				selText = selRange.toString();
			}

			return selText;
		}
		
	} else {
	
		getSelect = function (input) {
			var selText = "";
			if (document.selection.createRange) { // Internet Explorer
				var range = document.selection.createRange();
				selText = range.text;
			}

			return selText;
		}
	}

	_init();

/*
	Written by Steve Tucker, 2006, http://www.stevetucker.co.uk
	Full documentation can be found at http://www.stevetucker.co.uk/page-innerxhtml.php
	Released under the Creative Commons Attribution-Share Alike 3.0 License, http://creativecommons.org/licenses/by-sa/3.0/
	
	Change Log
	----------
	15/10/2006	v0.3	innerXHTML official release.
	21/03/2007	v0.4	1. Third argument $appendage added (Steve Tucker & Stef Dawson, www.stefdawson.com)
				2. $source argument accepts string ID (Stef Dawson)
				3. IE6 'on' functions work (Stef Dawson & Steve Tucker)
	*/
	function innerXHTML($source, $string, $appendage) {
		// (v0.4) Written 2006 by Steve Tucker, http://www.stevetucker.co.uk
		if (typeof ($source) == 'string') $source = document.getElementById($source);
		if (!($source.nodeType == 1)) return false;
		var $children = $source.childNodes;
		var $xhtml = '';
		if (!$string) {
			for (var $i = 0; $i < $children.length; $i++) {
				if ($children[$i].nodeType == 3) {
					var $text_content = $children[$i].nodeValue;
					$text_content = $text_content.replace(/</g, '&lt;');
					$text_content = $text_content.replace(/>/g, '&gt;');
					$xhtml += $text_content;
				} else if ($children[$i].nodeType == 8) {
					$xhtml += '<!--' + $children[$i].nodeValue + '-->';
				} else {
					var nodeName = $children[$i].nodeName.toLowerCase();
					$xhtml += '<' + nodeName;
					var $attributes = $children[$i].attributes;
					for (var $j = 0; $j < $attributes.length; $j++) {
						var $attName = $attributes[$j].nodeName.toLowerCase();
						var $attValue = $attributes[$j].nodeValue;
						if ($attName == 'style' && $children[$i].style.cssText) {
							$xhtml += ' style="' + $children[$i].style.cssText.toLowerCase() + '"';
						} else if ($attValue && $attName != 'contenteditable') {
							$xhtml += ' ' + $attName + '="' + $attValue + '"';
						}
					}
					$xhtml += '>' + innerXHTML($children[$i]);
					$xhtml += '</' + nodeName + '>';
					//add linebreak after certain tags
					if (/div|table|tr/.test(nodeName)) $xhtml += '\n';
				}
			}
		} else {
			if (!$appendage) {
				while ($children.length > 0) {
					$source.removeChild($children[0]);
				}
				$appendage = false;
			}
			$xhtml = $string;
			while ($string) {
				var $returned = translateXHTML($string);
				var $elements = $returned[0];
				$string = $returned[1];
				if ($elements) {
					if (typeof ($appendage) == 'string') $appendage = document.getElementById($appendage);
					if (!($appendage.nodeType == 1)) $source.appendChild($elements);
					else $source.insertBefore($elements, $appendage);
				}
			}
		}
		return $xhtml;
	}

	function translateXHTML($string) {
		var $match = /^<\/[a-z0-9]{1,}>/i.test($string);
		if ($match) {
			var $return = Array;
			$return[0] = false;
			$return[1] = $string.replace(/^<\/[a-z0-9]{1,}>/i, '');
			return $return;
		}
		$match = /^<[a-z]{1,}/i.test($string);
		if ($match) {
			$string = $string.replace(/^</, '');
			var $element = $string.match(/[a-z0-9]{1,}/i);
			if ($element) {
				var $new_element = document.createElement($element[0]);
				$string = $string.replace(/[a-z0-9]{1,}/i, '');
				var $attribute = true;
				while ($attribute) {
					$string = $string.replace(/^\s{1,}/, '');
					$attribute = $string.match(/^[a-z1-9_-]{1,}="[^"]{0,}"/i);
					if ($attribute) {
						$attribute = $attribute[0];
						$string = $string.replace(/^[a-z1-9_-]{1,}="[^"]{0,}"/i, '');
						var $attName = $attribute.match(/^[a-z1-9_-]{1,}/i);
						$attribute = $attribute.replace(/^[a-z1-9_-]{1,}="/i, '');
						$attribute = $attribute.replace(/;{0,1}"$/, '');
						if ($attribute) {
							var $attValue = $attribute;
							if ($attName == 'value') $new_element.value = $attValue;
							else if ($attName == 'class') $new_element.className = $attValue;
							else if ($attName == 'style') {
								var $style = $attValue.split(';');
								for (var $i = 0; $i < $style.length; $i++) {
									var $this_style = $style[$i].split(':');
									$this_style[0] = $this_style[0].toLowerCase().replace(/(^\s{0,})|(\s{0,1}$)/, '');
									$this_style[1] = $this_style[1].toLowerCase().replace(/(^\s{0,})|(\s{0,1}$)/, '');
									if (/-{1,}/g.test($this_style[0])) {
										var $this_style_words = $this_style[0].split(/-/g);
										$this_style[0] = '';
										for (var $j = 0; $j < $this_style_words.length; $j++) {
											if ($j == 0) {
												$this_style[0] = $this_style_words[0];
												continue;
											}
											var $first_letter = $this_style_words[$j].toUpperCase().match(/^[a-z]{1,1}/i);
											$this_style[0] += $first_letter + $this_style_words[$j].replace(/^[a-z]{1,1}/, '');
										}
									}
									$new_element.style[$this_style[0]] = $this_style[1];
								}
							} else if (/^on/.test($attName)) $new_element[$attName] = function () {
								eval($attValue)
							};
							else $new_element.setAttribute($attName, $attValue);
						} else $attribute = true;
					}
				}
				$match = /^>/.test($string);
				if ($match) {
					$string = $string.replace(/^>/, '');
					var $child = true;
					while ($child) {
						var $returned = translateXHTML($string, false);
						$child = $returned[0];
						if ($child) $new_element.appendChild($child);
						$string = $returned[1];
					}
				}
				$string = $string.replace(/^\/>/, '');
			}
		}
		$match = /^[^<>]{1,}/i.test($string);
		if ($match && !$new_element) {
			var $text_content = $string.match(/^[^<>]{1,}/i)[0];
			$text_content = $text_content.replace(/&lt;/g, '<');
			$text_content = $text_content.replace(/&gt;/g, '>');
			var $new_element = document.createTextNode($text_content);
			$string = $string.replace(/^[^<>]{1,}/i, '');
		}
		$match = /^<!--[^<>]{1,}-->/i.test($string);
		if ($match && !$new_element) {
			if (document.createComment) {
				$string = $string.replace(/^<!--/i, '');
				var $text_content = $string.match(/^[^<>]{0,}-->{1,}/i);
				$text_content = $text_content[0].replace(/-->{1,1}$/, '');
				var $new_element = document.createComment($text_content);
				$string = $string.replace(/^[^<>]{1,}-->/i, '');
			} else $string = $string.replace(/^<!--[^<>]{1,}-->/i, '');
		}
		var $return = Array;
		$return[0] = $new_element;
		$return[1] = $string;
		return $return;
	}
});