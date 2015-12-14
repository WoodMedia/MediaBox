jQuery(document).ready(function ($) {

	"use strict"
	
	var importstatus = $('#import-status'),
		exportstatus = $('.export-status'),
		progress = $('#progress'),
		progressbar = progress.find('.bar'),
		wpnonce = $('#mymail_nonce').val(),
		importdata = null,
		importerrors = 0,
		importstarttime,
	
	uploader_init = function() {
		var uploader = new plupload.Uploader(wpUploaderInit);


		uploader.bind('Init', function(up) {
			var uploaddiv = $('#plupload-upload-ui');

			if ( up.features.dragdrop && ! $(document.body).hasClass('mobile') ) {
				uploaddiv.addClass('drag-drop');
				$('#drag-drop-area').bind('dragover.wp-uploader', function(){ // dragenter doesn't fire right :(
					uploaddiv.addClass('drag-over');
				}).bind('dragleave.wp-uploader, drop.wp-uploader', function(){
					uploaddiv.removeClass('drag-over');
				});
			} else {
				uploaddiv.removeClass('drag-drop');
				$('#drag-drop-area').unbind('.wp-uploader');
			}

			if ( up.runtime == 'html4' )
				$('.upload-flash-bypass').hide();
				
		});

		uploader.init();

		uploader.bind('FilesAdded', function(up, files) {
			$('#media-upload-error').html('');
			$('#wordpress-users').fadeOut();
			
			_ajax('delete_old_bulk_jobs', function(response){
				up.refresh();
				up.start();
			});	

		});

		uploader.bind('BeforeUpload', function(up, file) {
			progress.show().removeClass('finished error');
			importstatus.html('uploading');
		});

		uploader.bind('UploadFile', function(up, file) {
		});

		uploader.bind('UploadProgress', function(up, file) {
			importstatus.html(sprintf(mymailL10n.uploading, file.percent+'%'));
			progressbar.stop().animate({'width': file.percent+'%'}, 100);
		});

		uploader.bind('Error', function(up, err) {
			importstatus.html(err.message);
			progress.addClass('error');
			up.refresh();
		});

		uploader.bind('FileUploaded', function(up, file, response) {
		});

		uploader.bind('UploadComplete', function(up, files) {
			importstatus.html(mymailL10n.prepare_data);
			progress.addClass('finished');
			get_import_data();
		});
	}

	if ( typeof(wpUploaderInit) == 'object' )
		uploader_init();
		
		
	$('.wrap')
	.delegate('.do-import', 'click', function(){
	
		var lists = $('#lists').serialize(),
			order = $('#subscriber-table').serialize();
		
		if (!/%5D=email/.test(order)) {
			alert(mymailL10n.select_emailcolumn);
			return false;
		}
		if (!lists) {
			alert(mymailL10n.no_lists);
			return false;
		}
		if (!$('input[name="status"]:checked').length) {
			alert(mymailL10n.select_status);
			return false;
		}
		if (!$('#terms').is(':checked')) {
			alert(mymailL10n.accept_terms);
			return false;
		}
		
		if(!confirm(mymailL10n.confirm_import)) return false;
		
		
		var _this = $(this).prop('disabled', true),
			status = $('input[name="status"]:checked').val(),
			existing = $('input[name="existing"]:checked').val(),
			autoresponder = $('#autoresponder').is(':checked'),
			loader = $('#import-ajax-loading').css({ 'display': 'inline-block' });
			
		
		
		progress.show();
		progressbar.stop().width(0);
		$('.step2').html('<br><br>').show();
		$('.step1').slideUp();
		
		importstarttime = new Date();
		
		do_import(0, {
			order: order,
			lists: lists,
			status: status,
			existing: existing,
			autoresponder: autoresponder,
			lines: importdata.lines,
			imported : 0,
			errors: 0
		});
		
		importstatus.html(sprintf(mymailL10n.import_contacts, ''));
		
		window.onbeforeunload = function(){
			return mymailL10n.onbeforeunloadimport;
		};
		
		
	})
	.delegate('#addlist', 'click', function () {
		var val = $('#new_list_name').val();
		if (!val) return false;

		$('<li><label><input name="lists[]" value="' + val + '" type="checkbox" checked> ' + val + ' </label></li>').appendTo('#lists > ul');
		$('#new_list_name').val('');

	});
	
	$('#paste-import')
	.on('focus', function(){
		$(this).val('').addClass('focus');
	})
	.on('blur', function(){
		$(this).removeClass('focus');
		var value = $.trim($(this).val());
		
		if(value){
			_ajax('delete_old_bulk_jobs', function(response){
				_ajax('import_subscribers_upload_handler', {
					data: value
				}, function(response){
					
					if(response.success){
						get_import_data();
					}
				}, function(){
				
					importstatus.html('Error');
				});	
			});	
		}
	});
	$('#import_wordpress')
	.on('submit', function(){
	
		var roles = $(this).serialize();
		_ajax('delete_old_bulk_jobs', function(response){
			_ajax('import_subscribers_upload_handler', {
				roles: roles
			}, function(response){
				
				if(response.success){
					$('#wordpress-users').fadeOut();
					get_import_data();
				}
			}, function(){
			
				importstatus.html('Error');
			});	
		});
		
		return false;
	});
	
	$( ".export-order" ).sortable({
		containment: "parent"
	});

	$('#export-subscribers').on('submit', function(){
	
		var data = $(this).serialize();
		
		progress.show().removeClass('finished error');
		
		$('.step1').slideUp();
		$('.step2').html(sprintf(mymailL10n.write_file, '0.00 Kb')).slideDown();
		_ajax('export_contacts', {
				data: data,
			},function(response){
			
				if(response.success){
					
					window.onbeforeunload = function(){
						return mymailL10n.onbeforeunloadexport;
					};
					
					var limit = $('.performance').val();
	
					do_export(0, limit, response.count, data);
				
				}else{
					
				}
				
			},function(jqXHR, textStatus, errorThrown){
			
				
			});
		
		
		return false;
	});

	$('#delete-subscribers').on('submit', function(){
	
		if('delete' == prompt(mymailL10n.confirm_delete, '').toLowerCase()){
		
			var data = $(this).serialize();
			
			progress.show().removeClass('finished error');
			
			$('.step1').slideUp();
			progressbar.stop().animate({'width': '99%'}, 25000);
			
			_ajax('delete_contacts', {
					data: data,
				},function(response){
					
					if(response.success){
						progressbar.stop().animate({'width': '100%'}, 200, function(){
							$('.delete-status').html(response.msg);
							progress.addClass('finished');
						});
					}else{
						progressbar.stop();
						$('.delete-status').html(response.msg);
						progress.addClass('error');
					}
				
				},function(jqXHR, textStatus, errorThrown){
					
					progressbar.stop();
					$('.delete-status').html('['+jqXHR.status+'] '+errorThrown);
					progress.addClass('error');
					
				});
		
		}
		
		return false;
	});
	

	
	function do_export(offset, limit, count, data) {
	
		var t = new Date().getTime(),
			percentage = (Math.min(1, (limit*offset)/count)*100);
		

		_ajax('do_export',{
			limit: limit,
			offset: offset,
			data: data
		}, function(response){
		
			var finished = percentage >= 100 && response.finished;
		
			if(response.success){
			
				if(!finished) do_export(offset+1, limit, count, data);
				
				progressbar.stop().animate({'width': (percentage)+'%'}, {
					duration: finished ? 100 : (new Date().getTime()-t)*0.9,
					easing: 'swing',
					queue:false,
					step: function(percentage){
						exportstatus.html(sprintf(mymailL10n.prepare_download, Math.round(percentage)+'%'));
					},
					complete: function(){
						exportstatus.html(sprintf(mymailL10n.prepare_download, Math.round(percentage)+'%'));
						if(finished){
							window.onbeforeunload = null;
							progress.addClass('finished');
							$('.step2').html(mymailL10n.download_finished);
							
							exportstatus.html(mymailL10n.downloading);
							if(response.filename) setTimeout( function() { document.location = response.filename }, 1000);
							
						}else{
							$('.step2').html(sprintf(mymailL10n.write_file, response.total));
						}
					}
				});
			}else{
			}
		}, function(jqXHR, textStatus, errorThrown){
		
			
		});
		
	}
	function do_import(id, options) {
	
		if(id > importdata.parts) return;
		
		var t = new Date().getTime(),
			percentage = 0;
		
		if(!id) id = 0;
		
		_ajax('do_import',{
			id:id,
			options: options
		}, function(response){
		
			percentage = (Math.min(1, (id+1)/importdata.parts)*100);
			
			$('.step2').html(get_stats(options, percentage, response.data.memoryusage));
			importerrors = 0;		
			var finished = percentage >= 100;
		
			if(response.success){
			
				if(!finished) do_import(id+1, response.data);
				progressbar.stop().animate({'width': (percentage)+'%'}, {
					duration: finished ? 100 : (new Date().getTime()-t)*0.9,
					easing: 'swing',
					queue:false,
					step: function(percentage){
						importstatus.html(sprintf(mymailL10n.import_contacts, Math.round(percentage)+'%'));
					},
					complete: function(){
						importstatus.html(sprintf(mymailL10n.import_contacts, Math.round(percentage)+'%'));
						if(finished){
							window.onbeforeunload = null;
							progress.addClass('finished');
							$('.step2').html(response.html).slideDown();
							
						}
					}
				});
			}else{
				upload_error_handler(percentage, id, options);
			}
		}, function(jqXHR, textStatus, errorThrown){
		
			upload_error_handler(percentage, id, options);
			
		});

	}
	
	function get_import_data(){
	
		progress.removeClass('finished error');
		
		_ajax('get_import_data', function(response){
			progress.hide().removeClass('finished');

			$('.step1').slideUp();
			$('.step2').html(response.html);
			importstatus.html('');
			
			importdata = response.data;
		});
		
	}
	
	function upload_error_handler(percentage, id, options){
			
		importerrors++;
		
		if(importerrors >= 5){
			
			alert(mymailL10n.error_importing);
			window.onbeforeunload = null;
			return;
		}
		
		var i = importerrors*5,
			str = '',
			errorint = setInterval(function(){
	
			if(i <= 0) {
				clearInterval(errorint);
				progress.removeClass('paused');
				do_import(id, options);
				str = Math.round(percentage)+'%';
			}else{
				progress.addClass('paused');
				str = '<span class="error">'+sprintf(mymailL10n.continues_in, (i--))+'</span>';

			}
			importstatus.html(sprintf(mymailL10n.import_contacts, str));
			
			
		}, 1000);
	}


	function get_stats(options, percentage, memoryusage) {

		var timepast = new Date().getTime()-importstarttime.getTime(),
			timeleft = Math.ceil(((100 - percentage) * (timepast/percentage))/60000);
			
		return sprintf(mymailL10n.current_stats, options.imported, options.lines || 0, options.errors, memoryusage)+'<br>'+
				sprintf(mymailL10n.estimate_time, timeleft);

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
		var a = Array.prototype.slice.call(arguments),
			str = a.shift();
		while (a.length) str = str.replace('%s', a.shift());
		return str;
	}

});



