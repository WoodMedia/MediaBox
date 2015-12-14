<?php 
/*
Plugin Name: MyMail - Email Newsletter Plugin for WordPress
Plugin URI: http://revaxarts-themes.com/?t=mymail
Description: advanced Newsletter Plugin for WordPress. Create, Send and Track your Newsletter Campaigns
Version: 1.5.1
Author: revaxarts.com
Author URI: http://revaxarts.com
*/

define('MYMAIL_VERSION', '1.5.1');
define('MYMAIL_DIR', WP_PLUGIN_DIR.'/myMail');
define('MYMAIL_URI', plugins_url().'/myMail');
define('MYMAIL_SLUG', 'myMail/myMail.php');

$upload_folder = wp_upload_dir();
		
define('MYMAIL_UPLOAD_DIR', $upload_folder['basedir'].'/myMail');
define('MYMAIL_UPLOAD_URI',$upload_folder['baseurl'].'/myMail');

require_once MYMAIL_DIR.'/includes/functions.php';
require_once MYMAIL_DIR.'/classes/mymail.class.php';
require_once MYMAIL_DIR.'/classes/settings.class.php';
require_once MYMAIL_DIR.'/classes/subscriber.class.php';
require_once MYMAIL_DIR.'/classes/manage.class.php';
require_once MYMAIL_DIR.'/classes/templates.class.php';
require_once MYMAIL_DIR.'/classes/widget.class.php';
require_once MYMAIL_DIR.'/classes/autoresponder.class.php';

global $mymail_options, $mymail, $mymail_subscriber, $mymail_templates, $mymail_settings, $mymail_manage, $mymail_autoresponder, $mymail_notices, $mymail_mytags;

$mymail_options = get_option( 'mymail_options' );

$mymail = new mymail();

$mymail_subscriber = new mymail_subscriber();

$mymail_templates = new mymail_templates();

$mymail_autoresponder = new mymail_autoresponder();

$mymail_manage = new mymail_manage();

$mymail_settings = new mymail_settings();

add_action( 'widgets_init', create_function( '', 'register_widget( "mymail_signup" );register_widget( "mymail_list_newsletter" );' ) );

if(mymail_option('system_mail')){

	if(function_exists('wp_mail')){
		mymail_notice('function <strong>wp_mail</strong> already exists from a different plugin! Please disable it before using MyMails wp_mail alternative!', 'error', true);
				
	}else{
		
		function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
			$message = str_replace(array('<br>', '<br />'), "\n", $message);
			$message = preg_replace('/(?:(?:\r\n|\r|\n)\s*){2}/s', "\n", $message);
			$message = wpautop($message, true);
			$template = mymail_option('system_mail_template', 'notification.html');
			return mymail_wp_mail( $to, $subject, $message, $headers, $attachments, $template );
		}
		
		
		function mymail_password_reset_link_fix($message, $key){
			$str = network_site_url("wp-login.php?action=rp&key=$key");
			
			return str_replace('<'.$str, $str, $message);
			
		}
		
		add_filter('retrieve_password_message', 'mymail_password_reset_link_fix', 10, 2);

	}
}


//Update Class
require_once 'classes/update.class.php';
new Envato_Plugin_Update(mymail_option('purchasecode'), array(
	'remote_url' => "http://update.revaxarts-themes.com",
	'version' => MYMAIL_VERSION,
	'plugin_slug' => MYMAIL_SLUG,
));



/*
function mytag_function($option, $fallback){
	return 'My Tag: Option: '.$option."; Fallback: ".$fallback."<br>";
}
mymail_add_tag('mytag', 'mytag_function');


function mymail_submit_errors($errors){
	$errors[] = 'new error';
	return  $errors;
}
add_filter( 'mymail_submit_errors', 'mymail_submit_errors' );

function mymail_form_fields($fields, $formid, $form){
	$pos = count($fields) - 1;
	$fields = array_slice($fields, 0, $pos, true) +
	array("fieldID" => "Fieldcontent") +
	array_slice($fields, $pos, count($fields) - 1, true) ;
	return $fields;
}
add_filter( 'mymail_form_fields', 'mymail_form_fields', 10, 3 );

*/

?>