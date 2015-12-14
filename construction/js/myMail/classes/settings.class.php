<?php if(!defined('ABSPATH')) die('not allowed');

class mymail_settings {
	
	
	public function __construct() {
		register_activation_hook(MYMAIL_DIR . '/myMail.php', array( &$this, 'activate'));
		register_deactivation_hook(MYMAIL_DIR . '/myMail.php', array( &$this, 'deactivate'));
		
		add_action('admin_menu', array( &$this, 'add_register_menu'));
		add_action('admin_init', array( &$this, 'register_settings'));
		
		add_action('wp_ajax_mymail_load_geo_data', array( &$this, 'ajax_load_geo_data'));
		add_action('wp_ajax_mymail_bounce_test', array( &$this, 'ajax_bounce_test'));
		add_action('wp_ajax_mymail_bounce_test_check', array( &$this, 'ajax_bounce_test_check'));
		
	}
	
	
	/*----------------------------------------------------------------------*/
	/* Settings
	/*----------------------------------------------------------------------*/
	
	private function define_settings($capabilities = true) {
		$current_user = wp_get_current_user();
		
		include MYMAIL_DIR . '/includes/static.php';

		$options = array(
			'from_name' => get_bloginfo('name'),
			'from' => $current_user->user_email,
			'reply_to' => $current_user->user_email,
			'default_template' => 'mymail',
			'send_offset' => 30,
			'embed_images' => true,
			'post_count' => 30,
			'trackcountries' => false,
			
			'bounce' => false,
			'bounce_server' => '',
			'bounce_port' => 110,
			'bounce_user' => '',
			'bounce_pwd' => '',
			'bounce_attempts' => 3,
			'bounce_delete' => true,
			'system_mail' => false,
			
			'homepage' => wp_insert_post($mymail_homepage),
			'share_button' => true,
			'share_services' => array(
				'twitter',
				'facebook',
				'google',
			),
			'frontpage_pagination' => true,
			
			'double_opt_in' => true,
			'subscription_resend_count' => 2,
			'subscription_resend_time' => 48,
			'merge_lists' => true,
			'text' => array(
				'subscription_subject' => __('Please confirm', 'mymail'),
				'subscription_headline' => __('Please confirm your Email Address', 'mymail'),
				'subscription_text' => sprintf(__("You'll need to confirm your email address. Please click the link below to confirm. %s", 'mymail'), "\n{link}"),
				'subscription_link' => __('Click here to confirm', 'mymail'),
				'confirmation' => __('Please confirm your subscription!', 'mymail'),
				'success' => __('Thanks for your interest!', 'mymail'),
				'error' => __('Following fields are missing or incorrect', 'mymail'),
				'newsletter_signup' => __('Sign up to our newsletter', 'mymail'),
				'unsubscribe' => __('You have successfully unsubscribed!', 'mymail'),
				'unsubscribeerror' => __('An error occurred! Please try again later!', 'mymail'),
				'email' => __('Email', 'mymail'),
				'firstname' => __('First Name', 'mymail'),
				'lastname' => __('Last Name', 'mymail'),
				'lists' => __('Lists', 'mymail'),
				'submitbutton' => __('Subscribe', 'mymail'),
				'unsubscribebutton' => __('Yes, unsubscribe me', 'mymail'),
				'unsubscribelink' => __('unsubscribe', 'mymail'),
				'webversion' => __('webversion', 'mymail'),
				'forward' => __('forward to a friend', 'mymail'),
			),
			'custom_field' => array(),
			'register_other_lists' => array('wordpress-users'),
			'register_comment_form_lists' => array('wordpress-users'),
			'register_signup_lists' => array('wordpress-users'),
			'register_comment_form_status' => array('1', '0'),
			'register_other' => true,
			'register_other_confirmation' => true,
			'ajax_form' => true,
			'forms' => array(
				array(
					'name' => __('Default Form', 'mymail'),
					'id' => 0,
					'lists' => array(
						'wordpress-users',
					),
					'order' => array(
						'email', 'firstname', 'lastname',
					),
					'required' => array(
						'email'
					),
					'addlists' => 1,
				),
			),
			
			'form_css' => str_replace(array('MYMAIL_URI'), array(MYMAIL_URI), $mymail_form_css),
			
			'tags' => array(
				'can-spam' => sprintf(__('You have received this email because you have subscribed to %s as {email}. If you no longer wish to receive emails please {unsub}', 'mymail'), '<a href="{homepage}">{company}</a>'),
				'notification' => __("If you received this email by mistake, simply delete it. You won't be subscribed if you don't click the confirmation link", 'mymail'),
				'copyright' => '&copy; {year} {company}, ' . __('All rights reserved', 'mymail'),
				'company' => get_bloginfo('name'),
				'homepage' => get_bloginfo('url')
			),
			'custom_tags' => array(
				'my-tag' => __('Replace Content', 'mymail')
			),
			
			'tweet_cache_time' => 60,
			
			'interval' => 5,
			'send_at_once' => 20,
			'send_limit' => 10000,
			'send_period' => 24,
			'split_campaigns' => true,
			'send_delay' => 0,
			'cron_service' => 'wp_cron',
			'cron_secret' => md5(uniqid()),
			'cron_lasthit' => false,
			
			'deliverymethod' => 'simple',
			'sendmail_path' => '/usr/sbin/sendmail',
			'smtp' => false,
			'smtp_host' => '',
			'smtp_port' => 25,
			'smtp_timeout' => 10,
			'smtp_secure' => '',
			'smtp_auth' => false,
			'smtp_user' => '',
			'smtp_pwd' => '',
			
			'dkim' => false,
			'dkim_selector' => 'mymail',
			'dkim_domain' => $_SERVER['HTTP_HOST'],
			'dkim_identity' => '',
			'dkim_passphrase' => '',
			
			'purchasecode' => '',
			
		);
		
		add_option( 'mymail_purchasecode_disabled', false );
		add_option( 'mymail_options', $options );
		
		global $mymail_options;
		$mymail_options = $options;
		
		if($capabilities) $this->set_capabilities();
		
	}
	
	public function add_register_menu() {
		$page = add_submenu_page('options-general.php', __('Newsletter Settings', 'mymail'), __('Newsletter', 'mymail'), 'manage_options', 'newsletter-settings', array( &$this, 'newsletter_settings'));
		add_action('load-' . $page, array( &$this, 'settings_scripts_styles'));
		
	}
	
	public function settings_scripts_styles() {
		wp_register_script('mymail-settings-script', MYMAIL_URI . '/assets/js/settings-script.js', array('jquery'), MYMAIL_VERSION);
		wp_enqueue_script('mymail-settings-script');
		wp_localize_script('mymail-settings-script', 'mymailL10n', array(
			'add' => __('add', 'mymail'),
			'fieldname' => __('Field Name', 'mymail'),
			'tag' => __('Tag', 'mymail'),
			'type' => __('Type', 'mymail'),
			'textfield' => __('Textfield', 'mymail'),
			'dropdown' => __('Dropdown Menu', 'mymail'),
			'radio' => __('Radio Buttons', 'mymail'),
			'checkbox' => __('Checkbox','mymail'),
			'default' => __('default', 'mymail'),
			'default_checked' => __('checked by default', 'mymail'),
			'default_selected' => __('this field is selected by default', 'mymail'),
			'add_field' => __('add field', 'mymail'),
			'options' => __('Options', 'mymail'),
			'remove_field' => __('remove field', 'mymail'),
			'move_up' => __('move up', 'mymail'),
			'move_down' => __('move down', 'mymail'),
			'reserved_tag' => __('%s is a reserved tag!', 'mymail'),
			'create_new_keys' => __('You are about to create new DKIM keys. The old ones will get deleted. Continue?', 'mymail'),
		));
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui-sortable');
		wp_enqueue_script('jquery-touch-punch');
		wp_register_style('mymail-settings-style', MYMAIL_URI . '/assets/css/settings-style.css', array(), MYMAIL_VERSION);
		wp_enqueue_style('mymail-settings-style');
		
	}
	public function register_settings() {
		
		//General
		register_setting('newsletter_settings', 'mymail_options', array( &$this, 'verify'));
		
		//Purchasecode
		if (!get_option('mymail_purchasecode_disabled')) {
			register_setting('newsletter_settings', 'mymail_purchasecode_disabled');
		}
	}
	
	public function newsletter_settings() {
		include MYMAIL_DIR . '/views/settings.php';
	}
	
	/*----------------------------------------------------------------------*/
	/* Plugin Activation / Deactivation
	/*----------------------------------------------------------------------*/
	
	
	
	public function activate() {
		
		global $wpdb, $mymail;
		
		if (function_exists('is_multisite') && is_multisite()) {
		
			$old_blog = $wpdb->blogid;
			$blogids = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM $wpdb->blogs"));
			
		}else{
		
			$blogids = array(false);
			
		}
			
		foreach ($blogids as $blog_id) {
		
			if($blog_id) switch_to_blog( $blog_id );
			
			if(!get_option('mymail')) $this->define_settings();
		
			update_option('mymail', true);
			
			if($hpid = mymail_option('homepage')){
				$mymail->change_status(get_post($hpid), 'publish', true);
			}
			
		}
	
		if($blog_id) switch_to_blog($old_blog);

		
	}
	
	
	public function deactivate() {
	
		global $wpdb, $mymail;
		
		if (function_exists('is_multisite') && is_multisite()) {
		
			$old_blog = $wpdb->blogid;
			$blogids = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM $wpdb->blogs"));
			
		}else{
		
			$blogids = array(false);
			
		}
		
		foreach ($blogids as $blog_id) {
		
			if($blog_id) switch_to_blog( $blog_id );

			if($hpid = mymail_option('homepage')){
				$mymail->change_status(get_post($hpid), 'draft', true);
			}
			
		}
		
		if($blog_id) switch_to_blog($old_blog);
	}
	
	
	
	
	public function reset_settings() {
		if(wp_verify_nonce( $_REQUEST['_wpnonce'], 'mymail-reset-settings' )){
			$this->define_settings( false );
		}
	}
	
	
	
	
	public function reset_capabilities() {
	
		$this->remove_capabilities();
		$this->set_capabilities();
	
	}
	
	public function update_capabilities() {
		
		include_once(MYMAIL_DIR.'/includes/capability.php');
				
		global $wp_roles;
		foreach($mymail_capabilities as $capability => $data){
		
			//admin has the cap so go on
			if(isset($wp_roles->roles['administrator']['capabilities'][$capability])) continue;
		
			$wp_roles->add_cap( 'administrator', $capability );
			
			foreach($wp_roles->roles as $role => $d){
				if(!isset($d['capabilities'][$capability]) && in_array($role, $data['roles'])) $wp_roles->add_cap( $role, $capability );
			}

		}
		
		return true;
	}
	
	
	public function set_capabilities() {
	
		include (MYMAIL_DIR.'/includes/capability.php');
		
		global $wp_roles;
		if(!$wp_roles){
			add_action('shutdown', array(&$this, 'set_capabilities'));
			return;
		}
		
		$roles = $wp_roles->get_names();
		$newcap = array();
		
		foreach($roles as $role => $title){
			
			$newcap[$role] = array();
		}
		
		
		foreach($mymail_capabilities as $capability => $data){
		
			//give admin all rights
			array_unshift($data['roles'], 'administrator');
			
			foreach($data['roles'] as $role){
				$wp_roles->add_cap( $role, $capability);
				$newcap[$role][] = $capability;
				
			}
			
		}
		
		mymail_update_option('roles', $newcap);
		
	}
	
	
	public function remove_capabilities() {
	
		$roles = mymail_option('roles');
		
		$newcap = array();
		
		if($roles){
		
			global $wp_roles;
			
			
			foreach($roles as $role => $capabilities){
			
				$newcap[$role] = array();
				
				foreach($capabilities as $capability){
					
					$wp_roles->remove_cap( $role, $capability);
					
				}
				
			}
		}
		
		mymail_update_option('roles', $newcap);
		
	}
	
	
	
	public function verify($options) {
	
		global $mymail;
		
		if(isset($_POST['mymail_generate_dkim_keys'])){
			
			try {
				
				$res = openssl_pkey_new(array('private_key_bits' => isset($options['dkim_bitsize']) ? (int) $options['dkim_bitsize'] : 512));
				openssl_pkey_export($res, $dkim_private_key);
				$dkim_public_key = openssl_pkey_get_details($res);
				$dkim_public_key = $dkim_public_key["key"];
				$options['dkim_public_key'] = $dkim_public_key;
				$options['dkim_private_key'] = $dkim_private_key;
				add_settings_error( 'mymail_options', 'mymail_options', __('New DKIM keys have been created!', 'mymail'), 'updated' );
				
			} catch ( Exception $e ) {
			
				add_settings_error( 'mymail_options', 'mymail_options', __('Not able to create new DKIM keys!', 'mymail'));
				
			}
				
		}
		
		if(!empty($_FILES['country_db_file']['name'])){
			
			$file = $_FILES['country_db_file'];
			
			$dest = MYMAIL_UPLOAD_DIR.'/'.$file['name'];
			if(move_uploaded_file($file['tmp_name'], $dest)){
				if(is_file($dest)){
					$options['countries_db'] = $dest;
					add_settings_error( 'mymail_options', 'mymail_options', sprintf(__('File uploaded to %s', 'mymail'), '"'.$dest.'"'), 'updated' );
				}else{
					$options['countries_db'] = '';
				}
			}else{
				add_settings_error( 'mymail_options', 'mymail_options', __('unable to upload file', 'mymail') );
				$options['countries_db'] = '';
			}
			
		}
		if(!empty($_FILES['city_db_file']['name'])){
			$file = $_FILES['city_db_file'];
			
			$dest = MYMAIL_UPLOAD_DIR.'/'.$file['name'];
			if(move_uploaded_file($file['tmp_name'], $dest)){
				if(is_file($dest)){
					$options['cities_db'] = $dest;
					add_settings_error( 'mymail_options', 'mymail_options', sprintf(__('File uploaded to %s', 'mymail'), '"'.$dest.'"'), 'updated' );
				}else{
					$options['cities_db'] = '';
				}
			}else{
				add_settings_error( 'mymail_options', 'mymail_options', __('unable to upload file', 'mymail') );
				$options['cities_db'] = '';
			}
		}
		
		$verify = array('from', 'reply_to', 'homepage', 'trackcountries', 'trackcities', 'vcard_content', 'custom_field', 'forms', 'form_css', 'send_period', 'bounce', 'cron_service', 'cron_secret', 'interval', 'roles', 'tweet_cache_time', 'deliverymethod', 'dkim_domain', 'dkim_selector', 'dkim_identity', 'dkim_passphrase', 'dkim_private_key', 'purchasecode');
		
		if(isset($_POST['mymail_import_settings']) && $_POST['mymail_import_settings']){
			$settings = unserialize(base64_decode($_POST['mymail_import_settings']));
			$options = wp_parse_args( $settings, $options ); 
		}
		
		
		foreach($verify as $id){
			
			if(!isset($options[$id])) continue;
			
			$value = $options[$id];
			$old = mymail_option( $id );
			
			
			switch($id){
				
				case 'from':
				case 'reply_to':
				case 'bounce':
						if($value && !mymail_is_email($value)){
							add_settings_error( 'mymail_options', 'mymail_options', sprintf(__('%s is not a valid email address', 'mymail'), '"'.$value.'"' ) );
							$value = $old;
						}
				break;
				
				case 'trackcountries':
						if(!$options['countries_db'] || !is_file($options['countries_db'])){
							add_settings_error( 'mymail_options', 'mymail_options', __('No country database found! Please load it!', 'mymail'));
							$value = false;
						}
				break;

				case 'trackcities':
						if(!$options['cities_db'] || !is_file($options['cities_db'])){
							add_settings_error( 'mymail_options', 'mymail_options', __('No city database found! Please load it!', 'mymail'));
							$value = false;
						}
				break;
			
				case 'homepage':
					if($old != $value){
						mymail_remove_notice('no-homepage');
					}
				break;
				
			
				case 'interval':
					if($old != $value){
					}
				break;
				
				
				case 'cron_service':
				
						if($old != $value){
							if ($value == 'wp_cron'){
								if(!wp_next_scheduled('mymail_cron_worker')) {
									wp_schedule_event(floor(time()/300)*300, 'mymail_cron_interval', 'mymail_cron_worker');
								}
							}else{
								wp_clear_scheduled_hook('mymail_cron_worker');
							}
						}
						
				break;
				
				
				case 'cron_secret':
				
						if($old != $value){
							if($value == '') $value = md5(uniqid());
						}
						
				break;
				
				
				case 'vcard_content':
						$folder = MYMAIL_UPLOAD_DIR;
						
						if(empty($options['vcard_content'])){
							$options['vcard'] = false;
						}
							
						if(!is_dir($folder)){
							wp_mkdir_p($folder);
						}
						
						$options['vcard_filename'] = sanitize_file_name($options['vcard_filename']);
						
						$filename = $folder.'/'.$options['vcard_filename'];
						if(!empty($options['vcard'])){
							file_put_contents( $filename , $options['vcard_content']);
						}else{
							if(file_exists($filename)) @unlink( $filename );
						}
				break;
				
				case 'custom_field':
						if(serialize($old) != serialize($value)){
						}
				break;
				
				case 'forms':
						if(function_exists('add_settings_error')){
							foreach($value as $form){
								if(!isset($form['lists']) || empty($form['lists']))
									add_settings_error( 'mymail_options', 'mymail_options',  sprintf(__('Form %s has no assigned lists', 'mymail'), '"'.$form['name'].'"' ) );
							}
						}
						if(serialize($old) != serialize($value)){
						
						}
				break;
				
				
				case 'form_css':
						if(isset($_POST['mymail_reset_form_css'])) {
							require_once(MYMAIL_DIR.'/includes/static.php');
							$value = $mymail_form_css;
							add_settings_error( 'mymail_options', 'mymail_options', __('Form CSS reseted!', 'mymail'), 'updated' );
						}
						if($old != $value){
							delete_transient( 'mymail_form_css' );
							$value = str_replace(array('MYMAIL_URI'), array(MYMAIL_URI), $value);
							$options['form_css_hash'] = md5(MYMAIL_VERSION.$value);
							
						}
				break;
				
				
				
				case 'send_period':
						if($old != $value){
							if($timestamp = get_option('_transient_timeout__mymail_send_period_timeout')){
								$new = time()+$value*3600;
								update_option('_transient_timeout__mymail_send_period_timeout', $new);	
							}else{
								update_option('_transient__mymail_send_period_timeout', false);
							}
							mymail_remove_notice('dailylimit');
						}

				break;
				
				case 'roles':
						if(serialize($old) != serialize($value)){
							require_once(MYMAIL_DIR.'/includes/capability.php');

							global $wp_roles;
							
							if(!$wp_roles) break;
							
							$newvalue = array();
							//give admin all rights
							$value['administrator'] = array();
							//foreach role
							foreach($value as $role => $capabilities){
							
								if(!isset($newvalue[$role])) $newvalue[$role] = array();
								
								foreach($mymail_capabilities as $capability => $data){
									if(in_array($capability, $capabilities) || 'administrator' == $role){
										
										$wp_roles->add_cap( $role, $capability);
										$newvalue[$role][] = $capability;
									}else{
										$wp_roles->remove_cap( $role, $capability);
									}
								}
								
								
	
							}
							$value = $newvalue;
						}
						
				break;
				
				
				case 'tweet_cache_time':
					$value = (int) $value;
					if($value < 10){
						$value = 10;
						add_settings_error( 'mymail_options', 'mymail_options', sprintf(__('The caching time for tweets must be at least %d minutes', 'mymail'), '10' ) );
					}
				break;
				
				case 'deliverymethod':
						if($old != $value){
							
							if($value == 'gmail'){
								if($options['send_limit'] != 500){
									$options['send_limit'] = 500;
									$options['send_period'] = 24;
									update_option('_transient__mymail_send_period_timeout', false);
									add_settings_error( 'mymail_options', 'mymail_options', sprintf(__('Send limit has been adjusted to %d for Gmail', 'mymail'), 500) );
								}
							}
						}
					break;
				case 'dkim_domain':
				case 'dkim_selector':
				case 'dkim_identity':
						if($old != $value){
							$value = trim($value);
						}
					break;
				case 'dkim_private_key':
				
						if($old != $value){
							
							global $wp_filesystem;
							if(!mymail_require_filesystem('', '', false)) break;
							
							$folder = MYMAIL_UPLOAD_DIR.'/dkim';
							
							//create folder
							if(!is_dir($folder)){
								wp_mkdir_p($folder);
								$wp_filesystem->put_contents( $folder.'/index.php', '<?php //silence is golden ?>', FS_CHMOD_FILE);
							}
							
							//remove old
							if(isset($options['dkim_private_hash']) && is_file($folder . '/' . $options['dkim_private_hash'].'.pem')){
								$wp_filesystem->delete($folder.'/'.$options['dkim_private_hash'].'.pem');
							}
							
							$hash = md5($value);
							
							if ($wp_filesystem->put_contents( $folder . '/' . $hash .'.pem', $value, FS_CHMOD_FILE) ) {
								$options['dkim_private_hash'] = $hash;
							}
							
						}
				break;
				
				
				case 'purchasecode':
				
						if($old != $value && $value){
							if(preg_match('#^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$#', $value)){
								$envato_plugins = get_option( 'envato_plugins' );
								if(isset($envato_plugins[MYMAIL_SLUG])){
									$envato_plugins[MYMAIL_SLUG]->last_update = 0;
									update_option( 'envato_plugins', $envato_plugins );
								}
							}else{
								add_settings_error( 'mymail_options', 'mymail_options', sprintf(__('The provided purchasecode %s is invalid', 'mymail'), '"'.$value.'"' ) );
								$value = '';
							}
						}
				break;
				
				
			}
			
			$options[$id] = $value;
		
		}
		
		$options = apply_filters('mymail_verify_options', $options);
		
		//clear everything thats cached
		mymail_clear_cache();
				
		return $options;
	}
	
	
	public function ajax_load_geo_data(){
		$return['success'] = false;
		
		$type = esc_attr($_POST['type']);
		
		if($type == 'country'){
			require_once MYMAIL_DIR.'/classes/libs/Ip2Country.php';
			$ip2Country = new Ip2Country();
			
			$result = $ip2Country->renew(true);
			if(is_wp_error($result)){
				$return['msg'] = __('Couldn\'t load Country DB', 'mymail');
			}else{
				$return['success'] = true;
				$return['msg'] = __('Country DB successfully loaded!', 'mymail');
				$return['path'] = $result;
				$return['buttontext'] = __('Update Country Database', 'mymail');
				mymail_update_option('countries_db', $result);
			}
			
		}else if($type == 'city'){
			require_once MYMAIL_DIR.'/classes/libs/Ip2City.php';
			$ip2City = new Ip2City();
			
			$result = $ip2City->renew(true);
			if(is_wp_error($result)){
				$return['msg'] = __('Couldn\'t load City DB', 'mymail');
			}else{
				$return['success'] = true;
				$return['msg'] = __('City DB successfully loaded!', 'mymail');
				$return['path'] = $result;
				$return['buttontext'] = __('Update City Database', 'mymail');
				mymail_update_option('cities_db', $result);
			}
		}else{
			$return['msg'] = 'not allowed';
		}

		echo json_encode($return);
		exit;
	}
	
	public function ajax_bounce_test(){
		$return['success'] = false;
		
		$identifier = 'mymail_bonuce_test_'.md5(uniqid());
		
		$return['identifier'] = $identifier;
		$return['success'] = mymail_send( 'MyMail Bounce Test Mail', $identifier, mymail_option('bounce'), array('preheader' => 'You can delete this message!', 'notification' => 'This message was sent from your WordPress blog to test your bounce server. You can delete this message!') );
		
		echo json_encode($return);
		exit;
	}
	public function ajax_bounce_test_check(){
	
		$return['success'] = false;
		$return['msg'] = '';
		
		$passes = intval($_POST['passes']);
		$identifier = $_POST['identifier'];
		
		if(!mymail_option('bounce_active')){
			$return['complete'] = true;
			echo json_encode($return);
			exit;
		}
		
		$server = mymail_option('bounce_server');
		$user = mymail_option('bounce_user');
		$pwd = mymail_option('bounce_pwd');
		
		if (!$server || !$user || !$pwd){
			$return['complete'] = true;
			echo json_encode($return);
			exit;
		}
		
		if(mymail_option('bounce_ssl')) $server = 'ssl://'.$server;

		require_once ABSPATH . WPINC . '/class-pop3.php';
		$pop3 = new POP3();

		if (!$pop3->connect($server, mymail_option('bounce_port', 110)) || !$pop3->user($user)){
			$return['complete'] = true;
			$return['msg'] = __('Unable to connect to bounce server! Please check your settings.', 'mymail');
			echo json_encode($return);
			exit;
		}

		$return['success'] = true;
		$count = $pop3->pass($pwd);
		$return['msg'] = __('checking for new messages', 'mymail').str_repeat('.', $passes);
		
		if($passes > 20){
			$return['complete'] = true;
			$return['msg'] = __('Unable to get test message! Please check your settings.', 'mymail');
		}
			
		if (false === $count || 0 === $count){
			if(0 === $count) $pop3->quit();
			echo json_encode($return);
			exit;
		}
		
		for ($i = 1; $i <= $count; $i++) {
			$message = $pop3->get($i);
			
			if(!$message) continue;
			
			$message = implode($message);
			
			if(strpos($message, $identifier)){
				$pop3->delete($i);
				$pop3->quit();
				$return['complete'] = true;
				$return['msg'] = __('Your bounce server is good!', 'mymail');
				echo json_encode($return);
				exit;
			} else {
				$pop3->reset();
			}
			
		}
		
		$pop3->quit();
		echo json_encode($return);
		exit;
	}
	
	public function get_vcard() {
		$current_user = wp_get_current_user();
		
		$text = 'BEGIN:VCARD'."\n";
		$text .= 'N:Firstname;Lastname;;;'."\n";
		$text .= 'ADR;INTL;PARCEL;WORK:;;StreetName;City;State;123456;Country'."\n";
		$text .= 'EMAIL;INTERNET:'.$current_user->user_email.''."\n";
		$text .= 'ORG:'.get_bloginfo('name').''."\n";
		$text .= 'URL;WORK:'.home_url().''."\n";
		$text .= 'END:VCARD'."\n";
		return $text;
	}
	
}
?>