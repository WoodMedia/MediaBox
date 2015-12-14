<?php 
//if uninstall not called from WordPress exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit ();
	
	global $wpdb, $wp_roles;
	
	if (function_exists('is_multisite') && is_multisite()) {
	
		$old_blog = $wpdb->blogid;
		$blogids = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM $wpdb->blogs"));
		
	}else{
	
		$blogids = array(false);
		
	}
	
	foreach ($blogids as $blog_id) {
	
		if($blog_id) switch_to_blog( $blog_id );

		//remove all capabilities
		$mymail_options = get_option( 'mymail_options' );
		$roles = $mymail_options['roles'];
		
		
		foreach($roles as $role => $capabilities){
			foreach($capabilities as $capability){
				$wp_roles->remove_cap( $role, $capability);
			}
		
		}
		
		//remove all options
		$wpdb->query("DELETE FROM `$wpdb->options` WHERE `$wpdb->options`.`option_name` LIKE 'mymail%'");
		$wpdb->query("DELETE FROM `$wpdb->options` WHERE `$wpdb->options`.`option_name` LIKE '_transient_mymail_%'");
		$wpdb->query("DELETE FROM `$wpdb->options` WHERE `$wpdb->options`.`option_name` LIKE '_transient_timeout_mymail_%'");
		$wpdb->query("DELETE FROM `$wpdb->options` WHERE `$wpdb->options`.`option_name` LIKE '_transient__mymail_%'");
		$wpdb->query("DELETE FROM `$wpdb->options` WHERE `$wpdb->options`.`option_name` LIKE '_transient_timeout__mymail_%'");
		//optimize DB
		$wpdb->query("OPTIMIZE TABLE `$wpdb->options`");
		
		//remove folder in the upload directory
		global $wp_filesystem;
		$upload_folder = wp_upload_dir();
	
		$wp_filesystem->delete($upload_folder['basedir'].'/myMail', true);
		
	}
	
	if($blog_id) switch_to_blog($old_blog);
	

?>