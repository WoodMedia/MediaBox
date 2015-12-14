<?php
/*
This runs if an update was done.
*/
global $mymail_options, $mymail, $mymail_subscriber, $mymail_templates, $mymail_settings, $mymail_manage, $mymail_autoresponder, $mymail_notices;
$old = $current_version;
$new = MYMAIL_VERSION;

$options = get_option('mymail_options');
$texts = $options['text'];
		
switch ($old) {
	case '1.0':
	case '1.0.1':
	
		mymail_notice('[1.1.0] Capabilities are now available. Please check the <a href="options-general.php?page=newsletter-settings#capabilities">settings page</a>');
		mymail_notice('[1.1.0] Custom Fields now support dropbox and radio button. Please check the <a href="options-general.php?page=newsletter-settings#subscribers">settings page</a>');
		
		$texts['firstname'] = __('First Name', 'mymail');
		$texts['lastname'] = __('Last Name', 'mymail');
		
	case '1.1.0':
	
		$texts['email'] = __('Email', 'mymail');
		$texts['submitbutton'] = __('Subscribe', 'mymail');
		$texts['unsubscribebutton'] = __('Yes, unsubscribe me', 'mymail');
		$texts['unsubscribelink'] = __('unsubscribe', 'mymail');
		$texts['webversion'] = __('webversion', 'mymail');
		
	case '1.1.1.1':

		$texts['lists'] = __('Lists', 'mymail');

		mymail_notice('[1.2.0] Auto responders are now available! Please set the <a href="options-general.php?page=newsletter-settings#capabilities">capabilities</a> to get access');
	
	case '1.2.0':
	
		$options['send_limit'] = 10000;
		$options['send_period'] = 24;
		$options['ajax_form'] = true;
		
		$texts['unsubscribeerror'] = __('An error occurred! Please try again later!', 'mymail');

		mymail_notice('[1.2.1] New capabilities available! Please update them in the <a href="options-general.php?page=newsletter-settings#capabilities">settings</a>');
	
	case '1.2.1':
	case '1.2.1.1':
	case '1.2.1.2':
	case '1.2.1.3':
	case '1.2.1.4':
		mymail_notice('[1.2.2] New capability: "manage capabilities". Please check the <a href="options-general.php?page=newsletter-settings#capabilities">settings page</a>');
	case '1.2.2':
	case '1.2.2.1':
		$options['post_count'] = 30;
		mymail_notice('[1.3.0] Track your visitors cities! Activate the option on the <a href="options-general.php?page=newsletter-settings#general">settings page</a>');
	
		$texts['forward'] = __('forward to a friend', 'mymail');

	
	case '1.3.0':
	
		$options['frontpage_pagination'] = true;
		$options['basicmethod'] = 'sendmail';
		$options['deliverymethod'] = (mymail_option('smtp')) ? 'smtp' : 'simple';
		$options['bounce_active'] = (mymail_option('bounce_server') && mymail_option('bounce_user') && mymail_option('bounce_pwd'));
		
		$options['spf_domain'] = $options['dkim_domain'];
		$options['send_offset'] = $options['send_delay'];
		$options['send_delay'] = 0;
		$options['smtp_timeout'] = 10;
		
		
		mymail_notice('[1.3.1] DKIM is now better supported but you have to check  <a href="options-general.php?page=newsletter-settings#general">settings page</a>');
		
	case '1.3.1':
	case '1.3.1.1':
	case '1.3.1.2':
	case '1.3.1.3':
	case '1.3.2':
	case '1.3.2.1':
	case '1.3.2.2':
	case '1.3.2.3':
	case '1.3.2.4':
	
		delete_option('mymail_bulk_imports');
		$forms = $options['forms'];
		$options['forms'] = array();
		foreach($forms as $form){
			$form['prefill'] = true;
			$options['forms'][] = $form;
		}
	
		mymail_notice('[1.3.3] New capability: "manage subscribers". Please check the <a href="options-general.php?page=newsletter-settings#capabilities">capabilities settings page</a>');
	case '1.3.3':
	case '1.3.3.1':
	case '1.3.3.2':
		
		$options['subscription_resend_count'] = 2;
		$options['subscription_resend_time'] = 48;
		
		
	case '1.3.4':
		$options['sendmail_path'] = '/usr/sbin/sendmail';
	case '1.3.4.1':
	case '1.3.4.2':
	case '1.3.4.3':
	
		$forms = $options['forms'];
		$customfields = mymail_option('custom_field', array());

		$options['forms'] = array();
		foreach($forms as $form){
			$order = array('email');
			if(isset($options['firstname'])) $order[] = 'firstname';
			if(isset($options['lastname'])) $order[] = 'lastname';
			$required = array('email');
			if(isset($options['require_firstname'])) $required[] = 'firstname';
			if(isset($options['require_lastname'])) $required[] = 'lastname';
			
			foreach($customfields as $field => $data){
				if(isset($data['ask'])) $order[] = $field;
				if(isset($data['required'])) $required[] = $field;
			}
			$form['order'] = $order;
			$form['required'] = $required;
			$options['forms'][] = $form;
		}
	
	case '1.3.4.4':
	case '1.3.4.5':
	case '1.3.5':
	case '1.3.6':
	case '1.3.6.1':
	
		add_action('shutdown', array($mymail_templates, 'renew_default_template'));
	
	case '1.4.0':
	case '1.4.0.1':
	
		$lists = isset($options['newusers']) ? $options['newusers'] : array();
		$options['register_other_lists'] = $options['register_comment_form_lists'] = $options['register_signup_lists'] = $lists;
		$options['register_comment_form_status'] = array('1', '0');
		if(!empty($lists)) $options['register_other'] = true;
		
		$texts['newsletter_signup'] = __('Sign up to our newsletter', 'mymail');

		mymail_notice('[1.4.1] New option for WordPress Users! Please <a href="options-general.php?page=newsletter-settings#subscribers">update your settings</a>!');
		mymail_notice('[1.4.1] New text for newsletter sign up Please <a href="options-general.php?page=newsletter-settings#texts">update your settings</a>!');
	
	default:

}


//do stuff every update


$options['text'] = $texts;

//update options
update_option('mymail_options', $options);

//update caps
$mymail_settings->update_capabilities();

//clear cache
mymail_clear_cache('', true);
flush_rewrite_rules();


?>