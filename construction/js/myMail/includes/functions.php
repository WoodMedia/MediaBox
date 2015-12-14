<?php

function mymail_option($option, $fallback = NULL) {
	global $mymail_options;
	return (isset($mymail_options[$option])) ? $mymail_options[$option] : $fallback;
}


function mymail_options() {
	global $mymail_options;
	return $mymail_options;
}


function mymail_text($option, $fallback = '') {
	$text = mymail_option('text');
	return (isset($text[$option])) ? $text[$option] : $fallback;
}


function mymail_update_option( $option, $value ) {
	global $mymail_options;
	$mymail_options[$option] = $value;
	update_option('mymail_options', $mymail_options);
}



function mymail_send( $headline, $content, $to = '', $replace = array(), $attachments = array(), $template = 'notification.html' ) {

	if(empty($to)){
		$current_user = wp_get_current_user();
		$to = $current_user->user_email;	
	}
	
	$defaults = array('notification' => '');
	
	$replace = apply_filters( 'mymail_send_replace', wp_parse_args( $replace, $defaults ));
	
	require_once MYMAIL_DIR . '/classes/mail.class.php';

	$mail = mymail_mail::get_instance();

	$mail->to = $to;
	$mail->subject = $headline;
	$mail->attachments = $attachments;
	
	return $mail->send_notification( $content, $headline, $replace, false, $template );
}


function mymail_wp_mail($to, $subject, $message, $headers = '', $attachments = array(), $template = 'notification.html' ){
	return mymail_send( $subject, $message, $to, array(), $attachments, $template );
}

function mymail_send_campaign_to_subscriber( $campaign, $subscriber, $track = false, $forcesend = false, $force = false ) {

	global $mymail;
	
	return $mymail->send_campaign_to_subscriber( $campaign, $subscriber, $track, $forcesend, $force );

}


function mymail_form( $id = 0, $tabindex = 100, $echo = true, $classes = '' ) {
	require_once MYMAIL_DIR.'/classes/form.class.php';
	
	$mymail_form = new mymail_form();
	$form = $mymail_form->form($id, $tabindex, $classes);
	
	if ($echo) {
		echo $form;
	} else {
		return $form;
	}
}


function mymail_get_active_campaigns( $args = '' ) {
	$defaults = array(
		'post_status' => 'active',
	);
	$r = wp_parse_args( $args, $defaults );

	return mymail_get_campaigns ($r);
}


function mymail_get_paused_campaigns( $args = '' ) {
	$defaults = array(
		'post_status' => 'paused',
	);
	$r = wp_parse_args( $args, $defaults );

	return mymail_get_campaigns ($r);
}


function mymail_get_queued_campaigns( $args = '' ) {
	$defaults = array(
		'post_status' => 'queued',
	);
	$r = wp_parse_args( $args, $defaults );

	return mymail_get_campaigns ($r);
}


function mymail_get_draft_campaigns( $args = '' ) {
	$defaults = array(
		'post_status' => 'draft',
	);
	$r = wp_parse_args( $args, $defaults );

	return mymail_get_campaigns ($r);
}


function mymail_get_finished_campaigns( $args = '' ) {
	$defaults = array(
		'post_status' => 'finished',
	);
	$r = wp_parse_args( $args, $defaults );

	return mymail_get_campaigns ($r);
}


function mymail_get_pending_campaigns( $args = '' ) {
	$defaults = array(
		'post_status' => 'pending',
	);
	$r = wp_parse_args( $args, $defaults );

	return mymail_get_campaigns ($r);
}


function mymail_get_campaigns( $args = '' ) {
	$defaults = array(
		'post_type' => 'newsletter',
		'post_status' => 'any',
		'orderby' => 'modified',
		'order' => 'DESC',
		'posts_per_page' => -1,
	);
	$r = wp_parse_args( $args, $defaults );

	$query = new WP_Query( $r );
	return $query->posts;
}


function mymail_list_newsletter( $args = '' ) {
	$defaults = array(
		'title_li' => __('Newsletters', 'mymail'),
		'post_type' => 'newsletter',
		'post_status' => array('finished', 'active'),
		'echo' => 1,
	);
	$r = wp_parse_args( $args, $defaults );

	extract( $r, EXTR_SKIP );

	$output = '';

	// sanitize, mostly to keep spaces out
	$r['exclude'] = preg_replace('/[^0-9,]/', '', $r['exclude']);

	// Allow plugins to filter an array of excluded pages (but don't put a nullstring into the array)
	$exclude_array = ( $r['exclude'] ) ? explode(',', $r['exclude']) : array();
	$r['exclude'] = implode( ',', apply_filters('mymail_list_newsletter_excludes', $exclude_array) );

	$newsletters = get_posts($r);

	if ( !empty($newsletters) ) {
		if ( $r['title_li'] )
			$output .= '<li class="pagenav">' . $r['title_li'] . '<ul>';

		foreach ($newsletters as $newsletter) {
			$output .= '<li class="newsletter_item newsletter-item-'.$newsletter->ID.'"><a href="'.get_permalink($newsletter->ID).'">'.$newsletter->post_title.'</a></li>';
		}

		if ( $r['title_li'] )
			$output .= '</ul></li>';
	}

	$output = apply_filters('mymail_list_newsletter', $output, $r);

	if ( $r['echo'] )
		echo $output;
	else
		return $output;
}


function mymail_ip2Country( $ip = '', $get = 'code' ) {

	if (!mymail_option('trackcountries')) return 'unknown';

	if ( empty($ip) ) {
		$ip = mymail_get_ip( );
	}

	require_once  MYMAIL_DIR.'/classes/libs/Ip2Country.php';
	$i = new Ip2Country();
	$code = $i->get($ip, $get);
	return ($code) ? $code : 'unknown';
}

function mymail_ip2City( $ip = '', $get = NULL ) {

	if (!mymail_option('trackcities')) return 'unknown';

	if ( empty($ip) ) {
		$ip = mymail_get_ip( );
	}

	require_once  MYMAIL_DIR.'/classes/libs/Ip2City.php';
	$i = new Ip2City();
	$code = $i->get($ip, $get);
	return ($code) ? $code : 'unknown';
}


function mymail_get_ip( ) {
	$ip = '';
	if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
		$ip=$_SERVER['HTTP_CLIENT_IP'];
	} else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
		$ip=$_SERVER['REMOTE_ADDR'];
	}
	
	return $ip;
}


function mymail_subscribe( $email, $userdata = array(), $lists = array(), $double_opt_in = NULL, $overwrite = true, $mergelists = NULL, $template = 'notification.html') {

	global $mymail_subscriber;
	
	return $mymail_subscriber->subscribe( $email, $userdata, $lists, $double_opt_in, $overwrite, $mergelists, $template );
	
}

//users email, hash or ID
function mymail_unsubscribe( $email_hash_id, $campaign_id = NULL, $logit = true ) {
	global $mymail_subscriber;
	
	return $mymail_subscriber->unsubscribe($email_hash_id, $campaign_id, $logit);
}



function mymail_get_subscribed_subscribers( $args = '' ) {
	$defaults = array(
		'post_status' => 'subscribed',
	);
	$r = wp_parse_args( $args, $defaults );

	return mymail_get_subscribers ($r);
}


function mymail_get_unsubscribed_subscribers( $args = '' ) {
	$defaults = array(
		'post_status' => 'unsubscribed',
	);
	$r = wp_parse_args( $args, $defaults );

	return mymail_get_subscribers ($r);
}


function mymail_get_hardbounced_subscribers( $args = '' ) {
	$defaults = array(
		'post_status' => 'hardbounced',
	);
	$r = wp_parse_args( $args, $defaults );

	return mymail_get_subscribers ($r);
}


function mymail_get_subscribers( $args = array() ) {
	$defaults = array(
		'post_type' => 'subscriber',
		'post_status' => 'subscribed',
		'orderby' => 'modified',
		'order' => 'ASC',
		'posts_per_page' => -1,
	);
	$args = wp_parse_args( $args, $defaults );

	$query = new WP_Query( $args );

	return $query->posts;
}


function mymail_get_subscribers_emails( $status = 'subscribed' ) {

	global $wpdb;
	
	$sql = "SELECT post_title as email FROM $wpdb->posts WHERE post_type = 'subscriber'";
	
	if($status != 'any'){
		if(!is_array($status)) $status = array($status);
		$sql .= " AND post_status = ('".implode(' OR ', $status)."')";
	}
	$result = $wpdb->get_col($sql);
	
	return $result;
	
}


function mymail_get_new_subscribers( ) {
	if ($t = mymail_option('subscribers_count')) {
		return $t['new'];
	};
	return 0;
}


function mymail_get_new_unsubscribers( ) {

	if ($t = mymail_option('subscribers_count')) {
		return $t['unsub'];
	};
	return 0;
}

function mymail_clear_totals( $lists = '' , $optimize = false) {

	$totals = array();
	
	if(!empty($lists)){
		
		if($totals = get_transient( 'mymail_totals' )){
		
			$ids = array();
			foreach($lists as $list){
				$term = get_term_by('slug', $list, 'newsletter_lists');
				
				$id[] = $term->term_id;
				foreach($totals as $key => $value){
					if(strpos($key, '_'.$term->term_id)) unset($totals[$key]);
				}
				
			}
			
		}
		
	}
	set_transient( 'mymail_totals' , $totals );
	return true;
	
}

function mymail_clear_cache( $part = '' , $optimize = false) {

	global $wpdb;
	$wpdb->query("DELETE FROM `$wpdb->options` WHERE `$wpdb->options`.`option_name` LIKE '_transient_timeout_mymail_".$part."%'");
	$wpdb->query("DELETE FROM `$wpdb->options` WHERE `$wpdb->options`.`option_name` LIKE '_transient_mymail_".$part."%'");
	//optimize DB
	if($optimize) $wpdb->query("OPTIMIZE TABLE `$wpdb->options`");
	return true;
	
}

function mymail_notice($text, $type = '', $once = false, $key = NULL){
	
	if(!$type) $type = 'updated';
	
	global $mymail_notices;
	
	$mymail_notices = get_option( 'mymail_notices' , array());
	
	$key = (!$key) ? uniqid('mymail_') : 'mymail_'.$key;

	$mymail_notices[$key] = array(
		'text' => $text,
		'type' => $type,
		'once' => $once,
	);

	update_option( 'mymail_notices', $mymail_notices );
	
	return $key;
	
}

function mymail_remove_notice($key){
	
	global $mymail_notices;
	
	$mymail_notices = get_option( 'mymail_notices' , array());
	
	if(isset($mymail_notices['mymail_'.$key])) {
		unset($mymail_notices['mymail_'.$key]);
		return update_option( 'mymail_notices', $mymail_notices );
	}
	
	return false;
	
}

function mymail_is_email($email){

	if (!ereg("^[^@]{1,64}@[^@]{1,255}$", $email)) return false;
	
	$email_array = explode("@", $email);
	$local_array = explode(".", $email_array[0]);
	
	for ($i = 0; $i < sizeof($local_array); $i++) {
		if (!ereg("^(([A-Za-z0-9!#$%&'*+/=?^_`{|}~-][A-Za-z0-9!#$%&↪'*+/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$",	$local_array[$i])) return false;
	}

	if (!ereg("^\[?[0-9\.]+\]?$", $email_array[1])) {
		$domain_array = explode(".", $email_array[1]);
		
		if (sizeof($domain_array) < 2) 	return false;
		
		for ($i = 0; $i < sizeof($domain_array); $i++) {
			if(!ereg("^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|↪([A-Za-z0-9]+))$",$domain_array[$i])) return false;
		}
	}
	
	return true;


	/* does fail in some cases
	$qtext = '[^\\x0d\\x22\\x5c\\x80-\\xff]';
	$dtext = '[^\\x0d\\x5b-\\x5d\\x80-\\xff]';
	$atom = '[^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c'.
		'\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+';
	$quoted_pair = '\\x5c[\\x00-\\x7f]';
	$domain_literal = "\\x5b($dtext|$quoted_pair)*\\x5d";
	$quoted_string = "\\x22($qtext|$quoted_pair)*\\x22";
	$domain_ref = $atom;
	$sub_domain = "($domain_ref|$domain_literal)";
	$word = "($atom|$quoted_string)";
	$domain = "$sub_domain(\\x2e$sub_domain)*";
	$local_part = "$word(\\x2e$word)*";
	$addr_spec = "$local_part\\x40$domain";

	return preg_match("!^$addr_spec$!", $email) ? 1 : 0;
	
	*/
	
}

function mymail_add_tag($tag, $callbackfunction){

	if(is_array($callbackfunction)){
		if(!method_exists($callbackfunction[0], $callbackfunction[1])){
		
			return false;
			
		}
	}else{
		if(!function_exists($callbackfunction)){
		
			return false;
			
		}
	}
	
	global $mymail_mytags;
	
	if(!isset($mymail_mytags)) $mymail_mytags = array();
		
	$mymail_mytags[$tag] = $callbackfunction;
		
	return true;
	
}


function mymail_require_filesystem($redirect = '', $method = '', $showform = true) {
	
	global $wp_filesystem;
	
	if (!function_exists( 'request_filesystem_credentials' )){
		
		require_once(ABSPATH . 'wp-admin/includes/file.php');
		
	}
	ob_start();
	
	if ( false === ($credentials = request_filesystem_credentials($redirect, $method)) ) {
		$data = ob_get_contents();
		ob_end_clean();
		if ( ! empty($data) ){
			include_once( ABSPATH . 'wp-admin/admin-header.php');
			echo $data;
			include( ABSPATH . 'wp-admin/admin-footer.php');
			exit;
		}
		return false;
	}
	
	if(!$showform){
		return false;
	}

	
	if ( ! WP_Filesystem($credentials) ) {
		request_filesystem_credentials($redirect, $method, true); // Failed to connect, Error and request again
		$data = ob_get_contents();
		ob_end_clean();
		if ( ! empty($data) ) {
			include_once( ABSPATH . 'wp-admin/admin-header.php');
			echo $data;
			include( ABSPATH . 'wp-admin/admin-footer.php');
			exit;
		}
		return false;
	}
	
	return true;

}


?>