jQuery(document).ready(function(jQuery) {
	
	//fix for the missing placeholder feature in IE < 10
	jQuery('body')
	.delegate('form.mymail-form input[placeholder]', 'focus.mymail', function(){
		var el = jQuery(this);
		if (el.val() == el.attr("placeholder"))
			el.val("");
	})
	.delegate('form.mymail-form input[placeholder]', 'blur.mymail', function(){
		var el = jQuery(this);
		if (el.val() == "")
			el.val(el.attr("placeholder"));
			
	})
	.delegate('form.mymail-form', 'submit.mymail', function(){
		var form = jQuery(this),
			inputs = form.find('input[placeholder]');
		
			
		jQuery.each(inputs, function(){
			var el = jQuery(this);
			if (el.val() == el.attr("placeholder"))
				el.val("");
		});
		
	})
	
	jQuery('form.mymail-form').find('input[placeholder]').trigger('blur.mymail');
	
});
	