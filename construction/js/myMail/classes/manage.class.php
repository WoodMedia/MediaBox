<?php if(!defined('ABSPATH')) die('not allowed');

class mymail_manage {
	
	
	public function __construct() {
		if(is_admin()){
			add_action('admin_menu', array( &$this, 'add_menu'));
			add_action('wp_ajax_mymail_import_subscribers_upload_handler', array( &$this, 'ajax_import_subscribers_upload_handler'));
			add_action('wp_ajax_mymail_get_import_data', array( &$this, 'ajax_get_import_data'));
			add_action('wp_ajax_mymail_do_import', array( &$this, 'ajax_do_import'));
			add_action('wp_ajax_mymail_export_contacts', array( &$this, 'ajax_export_contacts' ) );
			add_action('wp_ajax_mymail_do_export', array( &$this, 'ajax_do_export' ) );
			add_action('wp_ajax_mymail_download_export_file', array( &$this, 'ajax_download_export_file' ) );
			add_action('wp_ajax_mymail_delete_contacts', array( &$this, 'ajax_delete_contacts' ) );
			add_action('wp_ajax_mymail_delete_old_bulk_jobs', array( &$this, 'ajax_delete_old_bulk_jobs' ) );
		}
	}
	
	
	/*----------------------------------------------------------------------*/
	/* Settings
	/*----------------------------------------------------------------------*/
	
	
	public function add_menu() {
	
		$page = add_submenu_page('edit.php?post_type=newsletter', __('Manage Subscribers', 'mymail'), __('Manage Subscribers', 'mymail'), 'mymail_manage_subscribers', 'mymail_subscriber-manage', array( &$this, 'subscriber_manage' ));
		add_action( 'load-'.$page, array( &$this, 'scripts_styles' ) );
		
	}
	
	public function scripts_styles() {
		
		wp_register_script('mymail-manage-script', MYMAIL_URI . '/assets/js/manage-script.js', array('jquery'), MYMAIL_VERSION);
		wp_enqueue_script('mymail-manage-script');
		wp_localize_script('mymail-manage-script', 'mymailL10n', array(
				'select_status' => __( 'Please select the status for the importing contacts!', 'mymail' ),
				'select_emailcolumn' => __( 'Please select at least the column with the email addresses!', 'mymail' ),
				'accept_terms' => __( 'You must have the permission to import these contacts!', 'mymail' ),
				'prepare_data' => __( 'preparing data', 'mymail' ),
				'uploading' => __( 'uploading...%s', 'mymail' ),
				'import_contacts' => __( 'Importing Contacts...%s', 'mymail' ),
				'current_stats' => __( 'Currently %s of %s imported with %s errors. %s memory usage', 'mymail' ),
				'estimate_time' => __( 'Estimate time left: %s minutes', 'mymail' ),
				'continues_in' => __( 'continues in %s seconds', 'mymail' ),
				'error_importing' => __( 'There was a problem during importing contacts. Please check the error logs for more information!', 'mymail' ),
				'prepare_download' => __( 'Preparing Download...%s', 'mymail' ),
				'write_file' => __( 'writing file: %s', 'mymail' ),
				'download_finished' => __( 'Download finished', 'mymail' ),
				'downloading' => __( 'Downloading...', 'mymail' ),
				'no_lists' => __( 'Please select at least one list!', 'mymail' ),
				'confirm_import' => __( 'Do you really like to import these contacts?', 'mymail' ),
				'import_complete' => __( 'Import complete!', 'mymail' ),
				'confirm_delete' => __( 'You are about to delete these subscribers permanently. This step is irreversible!', 'mymail')."\n".sprintf(__('Type "%s" to confirm deletion', 'mymail'), 'DELETE'),
				'onbeforeunloadimport' => __('You are currently importing subscribers! If you leave the page all pending subscribers don\'t get imported!', 'mymail'),
				'onbeforeunloadexport' => __('Your download is preparing! If you leave this page the progress will abort!', 'mymail'),
			) );
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui-sortable');
		wp_enqueue_script('jquery-touch-punch');
		wp_register_style('mymail-manage-style', MYMAIL_URI . '/assets/css/manage-style.css', array(), MYMAIL_VERSION);
		wp_enqueue_style('mymail-manage-style');


	}
	
	public function subscriber_manage() {
	
		remove_action('post-plupload-upload-ui', 'media_upload_flash_bypass');
		wp_enqueue_script('plupload-all');

		include MYMAIL_DIR . '/views/manage.php';
	}
	
	
	public function ajax_delete_old_bulk_jobs() {
		
		global $wpdb;
		//remove all previous data
		$return['success'] = false;

		$this->ajax_nonce(json_encode($return));
		
		$sql = "DELETE FROM {$wpdb->options} WHERE {$wpdb->options}.option_name LIKE 'mymail_bulk_%'";
		
		try {
			
			$wpdb->query($sql);
			
			$return['success'] = true;
			
		} catch ( Exception $e){
				
			$return['success'] = false;
		}
		
		echo json_encode($return);
		exit;
		
		
	}
	
	
	public function ajax_import_subscribers_upload_handler() {
	

		if(isset($_FILES['async-upload'])){
		
			if(!current_user_can('mymail_import_subscribers')){
				die('not allowed');
			}
			
			$file = $_FILES['async-upload'];
			$raw_data = (file_get_contents($file['tmp_name']));
			
			
		}else if(isset($_POST['data'])){
		
			$return['success'] = false;
	
			$this->ajax_nonce(json_encode($return));
			
			if(!current_user_can('mymail_import_subscribers')){
				echo json_encode($return);
				exit;
			}
			
			$raw_data = esc_attr(stripslashes($_POST['data']));
			$return['success'] = true;
			
		}else if(isset($_POST['roles'])){
		
			parse_str($_POST['roles'], $roles);
			$roles = $roles['roles'];
			
			$user_query = new WP_User_Query( array( 'fields' => 'all_with_meta' ) );
			
			$raw_data = ''.mymail_text('email').';'.mymail_text('firstname').';'.mymail_text('lastname').';'.__('display name', 'mymail').';'.__('nice name', 'mymail').';'.__('registered', 'mymail').';'.__('user roles', 'mymail')."\n";
			foreach($user_query->results as $ID => $user){
				
				if(!array_intersect($user->roles, $roles)) continue;
				
				$first_name = get_user_meta( $ID, 'first_name', true );
				$last_name = get_user_meta( $ID, 'last_name', true );
				if (!$first_name) $first_name = $user->data->display_name;

				
				$raw_data .= $user->data->user_email.';';
				$raw_data .= $first_name.';';
				$raw_data .= $last_name.';';
				$raw_data .= $user->data->display_name.';';
				$raw_data .= $user->data->user_nicename.';';
				$raw_data .= $user->data->user_registered.';';
				$raw_data .= implode(',',$user->roles).'';
				$raw_data .= "\n";
				
			}
			
			$return['success'] = true;
			
		}else{
		
			die('not allowed');
			
		}
		
		$raw_data = (trim(str_replace(array("\r", "\r\n", "\n\n"), "\n", $raw_data)));
		$encoding = mb_detect_encoding($raw_data, 'auto');
		if($encoding != 'UTF-8'){
			$raw_data = utf8_encode($raw_data);
			$encoding = mb_detect_encoding($raw_data, 'auto');
		}
		$lines = explode("\n", $raw_data);
		$parts = array_chunk($lines, max(50, round(count($lines)/100)));
		$partcount = count($parts);
		
		$bulkimport = array(
			'ids' => array(),
			'imported' => 0,
			'errors' => 0,
			'encoding' => $encoding,
			'parts' => $partcount,
			'lines' => count($lines),
			'separator' => $this->get_separator( implode($parts[0]) ),
			'errormails' => array(),
		);
		
		for($i = 0; $i < $partcount; $i++){
		
			$part = $parts[$i];
			
			//remove quotes;
			$part = str_replace(array("'".$bulkimport['separator']."'", '"'.$bulkimport['separator'].'"'), $bulkimport['separator'], $part);
			$part = preg_replace('#^("|\')#', '', $part);
			$part = preg_replace('#("|\')$#', '', $part);
			
			$option_name = 'mymail_bulk_'.$i;
			
			$new_value = base64_encode(serialize($part));
			
			add_option( $option_name, $new_value, '', 'no');

			$bulkimport['ids'][] = $i;
		}
		
		update_option('mymail_bulk_import', $bulkimport);
		
		if(isset($return)){
			echo json_encode($return);
			exit;
		}	
		
	}
	
	
	public function ajax_get_import_data() {
	
		$return['success'] = false;

		$this->ajax_nonce(json_encode($return));
		
		if(!current_user_can('mymail_import_subscribers')){
			echo json_encode($return);
			exit;
		}
		
		$return['data'] = get_option( 'mymail_bulk_import' );
		
		
		$first = unserialize(base64_decode(get_option( 'mymail_bulk_0' )));
		$last = unserialize(base64_decode(get_option( 'mymail_bulk_'.($return['data']['parts']-1) )));
		
		$firstline = explode($return['data']['separator'], $first[0]);
		$data = explode($return['data']['separator'], $first[count($first)-1]);
		$cols = count($data);
		
		$contactcount = $return['data']['lines'];
		
		$customfields = mymail_option('custom_field', array());
		
		$fields = array(
			'email' => mymail_text('email'),
			'firstname' => mymail_text('firstname'),
			'lastname' => mymail_text('lastname'),
		);
		$meta = array(
			'_ip' => __('IP Address', 'mymail'),
			'_signupdate' => __('Signup Date', 'mymail'),
		);
		
		$html = '<h2>'.__('Select columns', 'mymail').'</h2><form id="subscriber-table"><table class="wp-list-table widefat fixed">';
		$html .= '<thead><tr><td width="5%">#</td>';
		for($i = 0; $i < $cols; $i++){
			$ismail = mymail_is_email(trim($data[$i]));
			$select = '<select name="order[]">';
			$select .= '<option value="-1">'.__('Ignore column', 'mymail').'</option>';
			$select .= '<option value="-1">----------</option>';
			foreach($fields as $key => $value){
				$is_selected = (($ismail && $key == 'email') || 
				($firstline[$i] == mymail_text('firstname') && $key == 'firstname') || 
				($firstline[$i] == mymail_text('lastname') && $key == 'lastname'));
				$select .= '<option value="'.$key.'" '.($is_selected ? 'selected' : '').'>'.$value.'</option>';
			}
			if(!empty($customfields)) $select .= '<option value="-1">----------</option>';
			foreach($customfields as $key => $d){
				
				$select .= '<option value="'.$key.'">'.$d['name'].'</option>';
			}
			$select .= '<option value="-1">----------</option>';
			foreach($meta as $key => $value){
				$is_selected = (($firstline[$i] == __('registered', 'mymail') && $key == '_signupdate'));
				$select .= '<option value="'.$key.'" '.($is_selected ? 'selected' : '').'>'.$value.'</option>';
			}
			$select .= '</select>';
			$html .= '<td>'.$select.'</td>';
		}
		$html .= '</tr></thead>';
		
		$html .= '<tbody>';
		for($i = 0; $i < min(10, $contactcount); $i++){
			$data = explode($return['data']['separator'], ($first[$i]));
			$html .= '<tr class="'.($i%2 ? '' : 'alternate').'"><td>'.($i+1).'</td>';
			foreach($data as $cell){
				$html .= '<td>'.($cell).'</td>';
			}
			$html .= '<tr>';
		}
		if($contactcount > 10){
			$html .= '<tr class="alternate"><td>&nbsp;</td><td colspan="'.($cols).'"><span class="description">&hellip;'.sprintf(__('%d contacts are hidden', 'mymail'), ($contactcount-11) ).'&hellip;</span></td>';
			
			$data = explode($return['data']['separator'], array_pop($last));
			$html .= '<tr'.($i%2 ? '' : ' class="alternate"').'><td>'.$contactcount.'</td>';
			foreach($data as $cell){
				$html .= '<td>'.($cell).'</td>';
			}
			$html .= '<tr>';
		}
		$html .= '</tbody>';

		$html .= '</table></form>';
		$html .= '<div>';
		$html .= '<p>'.__('add contacts to following lists', 'mymail').'</p>';
		$html .= '<form id="lists"><ul>';
		$lists = get_terms( 'newsletter_lists', array('hide_empty' => false) );
		if($lists && !is_wp_error($lists)){
			foreach($lists as $list){
				$html .= '<li><label><input name="lists[]" value="'.$list->slug.'" type="checkbox"> '.$list->name.'</label></li>';
			}
		}
		$html .= '</ul></form>';
		$html .= '<p><label for="new_list_name">'.__('add new list', 'mymail').': </label><input type="text" id="new_list_name"> <button class="button" id="addlist">'.__('add', 'mymail').'</button></p>
';
		$html .= '<p>'.__('Import as', 'mymail').':<br><label><input type="radio" name="status" value="subscribed"> '.__('Subscribed', 'mymail').'</label> <label><input type="radio" name="status" value="unsubscribed"> '.__('Unsubscribed', 'mymail').'</label></p>';
		$html .= '<p>'.__('Existing subscribers', 'mymail').':<br><label> <input type="radio" name="existing" value="skip" checked> '.__('skip', 'mymail').'</label> <label><input type="radio" name="existing" value="overwrite"> '.__('overwrite', 'mymail').'</label> <label><input type="radio" name="existing" value="merge"> '.__('merge', 'mymail').'</label></p>';
		$html .= '<p><label><input type="checkbox" id="autoresponder" name="autoresponder"> '.__('Do auto responder for all imported emails', 'mymail').' <span class="description">('.__('Respects the signup time if you define it', 'mymail').')</span></label></p>';
		$html .= '<p><label><input type="checkbox" id="terms" name="terms" value="1"> '.__('Yes, I have the permission to import these contacts', 'mymail').'</label></p>';
		
		$html .= '<input type="button" value="'.(sprintf(__('Import %d contacts', 'mymail'), $contactcount)).'" class="do-import button button-large button-primary">';
		$html .= '</div>';
		
		$return['html'] = $html;
		
		echo json_encode($return);
		exit;
		
	}
	
	
	public function ajax_do_import() {
	
		define('MYMAIL_DO_BULKIMPORT', true);
	
		$safe_mode = @ini_get('safe_mode');
		$memory_limit = @ini_get('memory_limit');
		$max_execution_time = @ini_get('max_execution_time');
		
		if(!$safe_mode){
			@set_time_limit(0);
			
			if(intval($max_execution_time) < 300){
				@ini_set( 'max_execution_time', 300 );
			}
			if(intval($memory_limit) < 256){
				@ini_set( 'memory_limit', '256M' );
			}
		}
		
		global $mymail_subscriber, $wpdb;
		
		$return['success'] = false;

		$this->ajax_nonce(json_encode($return));
		
		if(!current_user_can('mymail_import_subscribers')){
			echo json_encode($return);
			exit;
		}
		
		$bulkdata = get_option( 'mymail_bulk_import' );
		
		$bulkdata = wp_parse_args($_POST['options'], get_option( 'mymail_bulk_import' ));
		//$bulkdata = wp_parse_args(get_option( 'mymail_bulk_import' ), $_POST['options']);
		
		$bulkdata['autoresponder'] = !!($bulkdata['autoresponder'] === 'true');
		$bulkdata['existing'] = esc_attr($bulkdata['existing']);
		$bulkdata['status'] = $bulkdata['status'] == 'subscribed' ? $bulkdata['status'] : 'unsubscribed';
		
		$allmails = $wpdb->get_results("SELECT post_title, ID FROM {$wpdb->posts} WHERE {$wpdb->posts}.post_type = 'subscriber' ", OBJECT_K);
		
		parse_str($bulkdata['order']);
		parse_str($bulkdata['lists']);
		
		foreach ( (array) $lists as $term) {
			if ( !strlen(trim($term)) )
				continue;
	
			if ( !$term_info = term_exists($term, 'newsletter_lists') ) {
				if ( is_int($term) )
					continue;
				$term_info = wp_insert_term($term, 'newsletter_lists');
			}
			if ( is_wp_error($term_info) ) {
				echo json_encode($return);
				exit;
			}
		}
		
		$bulkdata['current'] = intval($_POST['id']);
		
		$list = unserialize(base64_decode(get_option( 'mymail_bulk_'.$bulkdata['current'] )));
		
		
		if($list){
			foreach($list as $line){
				if(!trim($line)){
					$bulkdata['lines']--;
					continue;
				}
				
				@set_time_limit(10);
				
				$data = explode($bulkdata['separator'], $line);
				
				$userdata = array();
				$userlists = $lists;
				for($i = 0; $i < count($data); $i++){
					switch ($order[$i]) {
					
						case 'email':
							$email = trim($data[$i]);
							break;
							
						case '_ip':
							if(!isset($userdata['_meta'])) $userdata['_meta'] = array();
							$userdata['_meta']['ip'] = $userdata['_meta']['signupip'] = $userdata['_meta']['confirmip'] = trim($data[$i]);
							break;
							
						case '_signupdate':
							if(!isset($userdata['_meta'])) $userdata['_meta'] = array();
							$time = trim($data[$i]);
							if(!is_numeric($time)) $time = strtotime($time);
							$userdata['_meta']['confirmtime'] = $userdata['_meta']['signuptime'] = $time;
							break;
							
						case '-1':
							break;
							
						default:
							$userdata[$order[$i]] = trim($data[$i]);
					}
				}
				
				$email = trim(strtolower($email));
		
				if(!mymail_is_email( $email )){
					$bulkdata['errormails'][$email] = __('invalid email address', 'mymail');
					$bulkdata['errors']++;
					continue;
				}
				
				if($bulkdata['existing'] == 'skip'){
					if(isset($allmails[$email])){
						$bulkdata['errormails'][$email] = sprintf(__('user already exists (ID: %d)', 'mymail'), $allmails[$email]->ID);
						$bulkdata['errors']++;
						continue;
					}	
				}
				
				$ID = isset($allmails[$email]) ? $allmails[$email]->ID : NULL;
				
				if($ID && $bulkdata['existing'] == 'merge'){

					$oldlists = wp_get_object_terms($ID, 'newsletter_lists', array('fields' => 'slugs', 'orderby' => 'none'));
					$userlists = array_unique(array_merge($userlists, $oldlists));
					
					$olduserdata = get_post_meta( $ID, 'mymail-userdata', true );
					
					$oldusermeta = isset($olduserdata['_meta']) ? $olduserdata['_meta'] : array();
					$usermeta = wp_parse_args($userdata['_meta'], $oldusermeta);
					$userdata = wp_parse_args($userdata, $olduserdata);
					$userdata['_meta'] = $usermeta;
				
				}
				
				$result = $mymail_subscriber->rawinsert($ID, $email, $bulkdata['status'], $userdata, $userlists, $bulkdata['autoresponder']);
				
				if(is_wp_error( $result )){
					$bulkdata['errormails'][$email] = $result->get_error_message();
					$bulkdata['errors']++;
				}else{
					$allmails[$email] = (object) array(
						'post_title' => $email,
						'ID' => $result,
					);
					$bulkdata['imported']++;
				}
				
			}
			delete_option( 'mymail_bulk_'.$bulkdata['current'] );
		}
		
		$mymail_subscriber->trigger_update_post_term_count();
		$bulkdata['memoryusage'] = size_format(memory_get_peak_usage(true),2);
		
		$return['html'] = '';
		
		if($bulkdata['imported'] + $bulkdata['errors'] >= $bulkdata['lines']){
			$return['html'] .= '<p>'.sprintf(__('%1$d of %2$d contacts imported', 'mymail'), $bulkdata['imported'], $bulkdata['lines']) .'<p>';
			if($bulkdata['errors']){
				$i = 0;
				$table = '<p>'.__('The following addresses were not imported', 'mymail').':</p>';
				$table .= '<table class="wp-list-table widefat fixed">';
				$table .= '<thead><tr><td width="5%">#</td><td>'.mymail_text('email').'</td><td>'.__('Reason', 'mymail').'</td></tr></thead><tbody>';
				foreach($bulkdata['errormails'] as $email => $e){
					$table .= '<tr'.($i%2 ? '' : ' class="alternate"').'><td>'.(++$i).'</td><td>'.$email.'</td><td>'.$e.'</td></tr></thead>';
				}
				$table .= '</tbody></table>';
				$return['html'] .= $table;
			}
			
			delete_option( 'mymail_bulk_import' );
			mymail_clear_totals();
			
		}else{
		
			update_option('mymail_bulk_import', $bulkdata);
			
		}
		$return['data'] = $bulkdata;
		$return['success'] = true;
		echo json_encode($return);
		exit;
	}
	
	
	public function ajax_export_contacts() {

		$return['success'] = false;

		$this->ajax_nonce( json_encode( $return ) );
		
		if(!current_user_can('mymail_export_subscribers')){
			$return['msg'] = 'no allowed';
			echo json_encode($return);
			exit;
		}
		
		parse_str($_POST['data'], $d);
		
		global $wpdb;
		
		$count = 0;
		
		if(isset($d['nolists'])){
			
			$count += $wpdb->get_var("SELECT COUNT(DISTINCT a.ID) FROM {$wpdb->posts} a LEFT JOIN {$wpdb->users} u ON (a.post_author = u.ID) WHERE a.post_type IN('subscriber') AND a.post_status IN ('".implode("','", $d['status'])."') AND a.ID NOT IN (SELECT object_id FROM {$wpdb->term_relationships} tr LEFT JOIN {$wpdb->term_taxonomy} tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id) WHERE taxonomy = 'newsletter_lists')");

		}
		
		if(!empty($d['lists'])){
		
			$count += $wpdb->get_var("SELECT COUNT(DISTINCT a.ID) FROM {$wpdb->posts} a LEFT JOIN {$wpdb->term_relationships} b ON ( a.ID = b.object_id ) LEFT JOIN {$wpdb->postmeta} c ON ( a.ID = c.post_id ) LEFT JOIN {$wpdb->term_taxonomy} d ON ( d.term_taxonomy_id = b.term_taxonomy_id ) LEFT JOIN {$wpdb->terms} e ON ( e.term_id = d.term_id ) WHERE a.post_type IN('subscriber') AND a.post_status IN ('".implode("','", $d['status'])."') AND e.term_id IN (".implode(',', $d['lists']).");");
			
		
		}
		
		$return['success'] = !!$return['count'] = $count;
		
		
		
		if($return['success']){
		
				
			$folder = MYMAIL_UPLOAD_DIR;
			
			wp_mkdir_p($folder);
			
			$filename = $folder.'/~mymail_export_'.date('Y-m-d-H-i-s').'.tmp';
			
			set_transient( 'mymail_export_filename', $filename );
			
			try {
				
				$return['success'] =  0 === file_put_contents($filename, '');
				
			} catch ( Exception $e){
			
				$return['success'] = false;
				$return['msg'] = $e->getMessage();
			}
			
			
		}else{
			
			$return['msg'] = __('no subscribers found', 'mymail');
		}
		
		echo json_encode($return);
		exit;

	}
	
	
	public function ajax_do_export() {
		$return['success'] = false;

		$this->ajax_nonce( json_encode( $return ) );
		
		if(!current_user_can('mymail_export_subscribers')){
			$return['msg'] = 'no allowed';
			echo json_encode($return);
			exit;
		}
		
		$filename = get_transient( 'mymail_export_filename' );
		
		if(!file_exists($filename) || !is_writeable($filename)){
			$return['msg'] = 'error';
			echo json_encode($return);
			exit;
		}
		
		parse_str($_POST['data'], $d);
		
		$offset = intval($_POST['offset']);
		$limit = intval($_POST['limit']);
		$raw_data = array();
		
		$encoding = $d['encoding'];
		$outputformat = $d['outputformat'];
		
		$useheader = $offset === 0 && isset($d['header']);
		
		
		if($useheader){
		
				$row = array();
				$customfields = mymail_option('custom_field', array());
				
				foreach($d['column'] as $col){
					switch ($col) {
						case '_number':
							$val = '#';
							break;
						case 'email':
						case 'firstname':
						case 'lastname':
							$val = mymail_text($col, $col);
							break;
						case '_listnames':
							$val = __('Lists', 'mymail');
							break;
						case '_status':
							$val = __('Status', 'mymail');
							break;
						case '_ip':
							$val = __('IP Address', 'mymail');
							break;
						case '_signuptime':
							$val = __('Signup Date', 'mymail');
							break;
						case '_signupip':
							$val = __('Signup IP', 'mymail');
							break;
						case '_confirmtime':
							$val = __('Confirm Date', 'mymail');
							break;
						case '_confirmip':
							$val = __('Confirm IP', 'mymail');
							break;
						default:
							$val = (isset($customfields[$col])) ? $customfields[$col]['name'] : '';
					}
					$val = mb_convert_encoding($val, $encoding, 'UTF-8');
					$row[] = str_replace(';', ',', $val);
				}
				
				$raw_data[] = $row;
				
		}
		
		$offset = $offset*$limit;
		
		global $wpdb;
		
		if(isset($d['nolists'])){
			
			
			$sql = "SELECT ID, post_title a FROM {$wpdb->posts} a LEFT JOIN {$wpdb->users} u ON (a.post_author = u.ID) WHERE a.post_status IN ('".implode("','", $d['status'])."') AND a.post_type IN('subscriber') AND a.ID NOT IN (SELECT object_id FROM {$wpdb->term_relationships} tr LEFT JOIN {$wpdb->term_taxonomy} tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id) WHERE taxonomy = 'newsletter_lists')";
			
			$data = $wpdb->get_results($sql);
			
		}
		
		if(!empty($d['lists'])){
		
			
			$sql = "SELECT ID, post_title, post_status FROM {$wpdb->posts} a LEFT JOIN {$wpdb->term_relationships} b ON ( a.ID = b.object_id ) LEFT JOIN {$wpdb->postmeta} c ON ( a.ID = c.post_id ) LEFT JOIN {$wpdb->term_taxonomy} d ON ( d.term_taxonomy_id = b.term_taxonomy_id ) LEFT JOIN {$wpdb->terms} e ON ( e.term_id = d.term_id ) WHERE a.post_type IN('subscriber') AND a.post_status IN ('".implode("','", $d['status'])."') AND e.term_id IN (".implode(',', $d['lists']).") GROUP BY a.ID LIMIT $offset, $limit;";
			
			$data = $wpdb->get_results($sql);
			
			$counter = 1+$offset;
			
			foreach($data as $user){
				
				$userdata = get_post_meta( $user->ID, 'mymail-userdata', true );
				
				$row = array();
				
				foreach($d['column'] as $col){
					switch ($col) {
						case '_number':
							$val = $counter;
							break;
						case 'email':
							$val = $user->post_title;
							break;
						case '_listnames':
							$terms = wp_get_post_terms($user->ID, 'newsletter_lists', array("fields" => "names"));
							$val = implode(', ', $terms);
							break;
						case '_status':
							$val = __($user->post_status, 'mymail');
							break;
						case '_ip':
							$val = (isset($userdata['_meta']['ip'])) ? $userdata['_meta']['ip'] : '';
							break;
						case '_signuptime':
							$val = (isset($userdata['_meta']['signuptime'])) ? ($d['dateformat'] ? date($d['dateformat'], $userdata['_meta']['signuptime']) : $userdata['_meta']['signuptime']) : '';
							break;
						case '_signupip':
							$val = (isset($userdata['_meta']['signupip'])) ? $userdata['_meta']['signupip'] : '';
							break;
						case '_confirmtime':
							$val = (isset($userdata['_meta']['confirmtime'])) ? ($d['dateformat'] ? date($d['dateformat'], $userdata['_meta']['confirmtime']) : $userdata['_meta']['confirmtime']) : '';
							break;
						case '_confirmip':
							$val = (isset($userdata['_meta']['confirmip'])) ? $userdata['_meta']['confirmip'] : '';
							break;
						default:
							$val = (isset($userdata[$col])) ? apply_filters('mymail_export_custom_field', $userdata[$col], $col) : '';
					}
					$val = mb_convert_encoding($val, $encoding, 'UTF-8');
					$row[] = str_replace(';', ',', $val);
				}
				
				$raw_data[] = $row;
				
				$counter++;
			}
			
			$output = '';
			
			if($outputformat == 'html'){
			
				if($useheader){
					$firstrow = array_shift($raw_data);
					$output .= '<tr><th>'.implode('</th><th>', $firstrow)."</th></tr>\n";
				}
				foreach($raw_data as $row){
					$output .= '<tr><td>'.implode('</td><td>', $row)."</td></tr>\n";
				}
				
			}else{
			
				foreach($raw_data as $row){
					$output .= implode(';', $row)."\n";
				}
				
			}
			
			try {
				
				$bytes = file_put_contents($filename, $output, FILE_APPEND);
				
				$return['total'] = size_format(filesize($filename), 2);
				
				$return['success'] = true;
				
				if($bytes === 0){
				
					$return['finished'] = true;
					
					mymail_require_filesystem();
			
					global $wp_filesystem;
					//finished
					$folder = MYMAIL_UPLOAD_DIR;
					
					$finalname = dirname($filename).'/mymail_export_'.date('Y-m-d-H-i-s').'.'.$outputformat;
					$return['success'] = copy($filename, $finalname);
					$wp_filesystem->delete( $filename );
					$return['filename'] = admin_url('admin-ajax.php?action=mymail_download_export_file&file='.basename($finalname).'&format='.$outputformat.'&_wpnonce='.wp_create_nonce ('mymail_nonce'));
			
				}
				
			} catch ( Exception $e){
			
				$return['success'] = false;
				$return['msg'] = $e->getMessage();
				
			}
			
			
		}
		
		echo json_encode($return);
		exit;
	}
	
	
	public function ajax_download_export_file() {
	
		$this->ajax_nonce( 'not allowed' );
		
		$folder = MYMAIL_UPLOAD_DIR;
		
		$file = $folder.'/'.$_REQUEST['file'];
		
		if(!file_exists($file)) die( 'not found' );
		
		$format = $_REQUEST['format'];
		
		$filename = basename($file);
		
		send_nosniff_header();
		nocache_headers();
		
		switch($format){
			case 'html':
				header('Content-Type: text/html; name="'.$filename.'.html"');
				break;
			case 'csv' :
				header('Content-Type: text/csv; name="'.$filename.'.csv"');
				header('Content-Transfer-Encoding: binary');
				break;
			default;
				die('format not allowed');
		}
		
		header('Content-Disposition: attachment; filename="'.basename($file).'"');
		header('Content-Length: '.filesize($file));
		header('Connection: close');
		
		if($format == 'html') echo '<table>';
		readfile($file);
		if($format == 'html') echo '</table>';
		
		mymail_require_filesystem();
			
		global $wp_filesystem;
		
		$wp_filesystem->delete( $file );
		exit;
		
	}
	
	
	public function ajax_delete_contacts() {

		$return['success'] = false;

		$this->ajax_nonce( json_encode( $return ) );
		
		if(!current_user_can('delete_subscribers') || !current_user_can('delete_others_subscribers')){
			$return['msg'] = 'no allowed';
			echo json_encode($return);
			exit;
		}
		
		parse_str($_POST['data'], $d);
		
		
		global $wpdb;
		
		$count = 0;
		
		if(isset($d['nolists'])){
			
			$count += $wpdb->get_var("SELECT COUNT(DISTINCT a.ID) FROM {$wpdb->posts} a LEFT JOIN {$wpdb->users} u ON (a.post_author = u.ID) WHERE a.post_type IN('subscriber') AND a.post_status IN ('".implode("','", $d['status'])."') AND a.ID NOT IN (SELECT object_id FROM {$wpdb->term_relationships} tr LEFT JOIN {$wpdb->term_taxonomy} tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id) WHERE taxonomy = 'newsletter_lists')");
			
			$sql = "DELETE a, b FROM {$wpdb->posts} a LEFT JOIN {$wpdb->users} u ON (a.post_author = u.ID) LEFT JOIN {$wpdb->postmeta} b ON ( a.ID = b.post_id ) WHERE a.post_status IN ('".implode("','", $d['status'])."') AND a.post_type IN('subscriber') AND a.ID NOT IN (SELECT object_id FROM {$wpdb->term_relationships} tr LEFT JOIN {$wpdb->term_taxonomy} tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id) WHERE taxonomy = 'newsletter_lists')";
			
			$return['success'] = !!$wpdb->query($sql);

		}
		
		if(!empty($d['lists'])){
		
			$count += $wpdb->get_var("SELECT COUNT(DISTINCT a.ID) FROM {$wpdb->posts} a LEFT JOIN {$wpdb->term_relationships} b ON ( a.ID = b.object_id ) LEFT JOIN {$wpdb->postmeta} c ON ( a.ID = c.post_id ) LEFT JOIN {$wpdb->term_taxonomy} d ON ( d.term_taxonomy_id = b.term_taxonomy_id ) LEFT JOIN {$wpdb->terms} e ON ( e.term_id = d.term_id ) WHERE a.post_type IN('subscriber') AND a.post_status IN ('".implode("','", $d['status'])."') AND e.term_id IN (".implode(',', $d['lists']).");");
			
			$rl = isset($d['remove_lists']) ? ',d' : '';
			
			$sql = "DELETE a,b,c".$rl." FROM {$wpdb->posts} a LEFT JOIN {$wpdb->term_relationships} b ON ( a.ID = b.object_id ) LEFT JOIN {$wpdb->postmeta} c ON ( a.ID = c.post_id ) LEFT JOIN {$wpdb->term_taxonomy} d ON ( d.term_taxonomy_id = b.term_taxonomy_id ) LEFT JOIN {$wpdb->terms} e ON ( e.term_id = d.term_id ) WHERE a.post_type IN('subscriber') AND a.post_status IN ('".implode("','", $d['status'])."') AND e.term_id IN (".implode(',', $d['lists']).");";
			
			$return['success'] = !!$wpdb->query($sql);
		
		}
		
		if($return['success']){
		
			global $mymail_subscriber;
			
			$mymail_subscriber->trigger_update_post_term_count();
			mymail_clear_totals();
			$return['msg'] = sprintf(__('%d subscribers removed', 'mymail'), $count);
			
		}else{
			
			$return['msg'] = __('no subscribers removed', 'mymail');
		}
		
		echo json_encode($return);
		exit;

	}
	
	
	private function ajax_nonce($return = NULL, $nonce = 'mymail_nonce') {
		if (!wp_verify_nonce($_REQUEST['_wpnonce'], $nonce)) {
			if (is_string($return)) {
				wp_die($return);
			}else {
				die($return);
			}
		}

	}
	
	
	public function media_upload_form( $errors = null ) {
	
		global $type, $tab, $pagenow, $is_IE, $is_opera;
	
		if ( function_exists('_device_can_upload') && ! _device_can_upload() ) {
			echo '<p>' . __('The web browser on your device cannot be used to upload files. You may be able to use the <a href="http://wordpress.org/extend/mobile/">native app for your device</a> instead.') . '</p>';
			return;
		}
	
		$upload_size_unit = $max_upload_size = wp_max_upload_size();
		$sizes = array( 'KB', 'MB', 'GB' );
	
		for ( $u = -1; $upload_size_unit > 1024 && $u < count( $sizes ) - 1; $u++ ) {
			$upload_size_unit /= 1024;
		}
	
		if ( $u < 0 ) {
			$upload_size_unit = 0;
			$u = 0;
		} else {
			$upload_size_unit = (int) $upload_size_unit;
		}
	?>
	
	<div id="media-upload-notice"><?php
	
		if (isset($errors['upload_notice']) )
			echo $errors['upload_notice'];
	
	?></div>
	<div id="media-upload-error"><?php
	
		if (isset($errors['upload_error']) && is_wp_error($errors['upload_error']))
			echo $errors['upload_error']->get_error_message();
	
	?></div>
	<?php
	if ( is_multisite() && !is_upload_space_available() ) {
		return;
	}
	
	$post_params = array(
			"action" => "mymail_import_subscribers_upload_handler",
			"_wpnonce" => wp_create_nonce('mymail_nonce'),
	);
	$upload_action_url = admin_url('admin-ajax.php');
	
		
	$plupload_init = array(
		'runtimes' => 'html5,silverlight,flash,html4',
		'browse_button' => 'plupload-browse-button',
		'container' => 'plupload-upload-ui',
		'drop_element' => 'drag-drop-area',
		'file_data_name' => 'async-upload',
		'multiple_queues' => true,
		'max_file_size' => $max_upload_size . 'b',
		'url' => $upload_action_url,
		'flash_swf_url' => includes_url('js/plupload/plupload.flash.swf'),
		'silverlight_xap_url' => includes_url('js/plupload/plupload.silverlight.xap'),
		'filters' => array( array('title' => __( 'Comma-separated values (CSV)', 'mymail' ), 'extensions' => 'csv') ),
		'multipart' => true,
		'urlstream_upload' => true,
		'multipart_params' => $post_params,
		'multi_selection' => false
	);
	
	?>
	
	<script type="text/javascript">
	var wpUploaderInit = <?php echo json_encode($plupload_init); ?>;
	</script>
	
	<div id="plupload-upload-ui" class="hide-if-no-js">
	<div id="drag-drop-area">
		<div class="drag-drop-inside">
		<p class="drag-drop-info"><?php _e('Drop your list here', 'mymail'); ?></p>
		<p><?php _ex('or', 'Uploader: Drop files here - or - Select Files'); ?></p>
		<p class="drag-drop-buttons"><input id="plupload-browse-button" type="button" value="<?php esc_attr_e('Select File', 'mymail'); ?>" class="button" /></p>
		</div>
	</div>
	</div>
	
	<div id="html-upload-ui" class="hide-if-js">
		<p id="async-upload-wrap">
			<label class="screen-reader-text" for="async-upload"><?php _e('Upload'); ?></label>
			<input type="file" name="async-upload" id="async-upload" />
			<?php submit_button( __( 'Upload' ), 'button', 'html-upload', false ); ?>
			<a href="#" onclick="try{top.tb_remove();}catch(e){}; return false;"><?php _e('Cancel'); ?></a>
		</p>
		<div class="clear"></div>
	</div>
	
	<p class="max-upload-size"><?php printf( __( 'Maximum upload file size: %d%s.' ), esc_html($upload_size_unit), esc_html($sizes[$u]) ); ?> <?php _e('Split your lists into max 50.000 subscribers each', 'mymail'); ?></p>
	<?php
	if ( ($is_IE || $is_opera) && $max_upload_size > 100 * 1024 * 1024 ) { ?>
		<span class="big-file-warning"><?php _e('Your browser has some limitations uploading large files with the multi-file uploader. Please use the browser uploader for files over 100MB.'); ?></span>
	<?php }
	
	}


	private function get_all_wp_users( ) {
		global $wpdb;
		$wp_users = $wpdb->get_results("SELECT ID, display_name, user_email FROM $wpdb->users ORDER BY ID");
		
		for ($i = 0; $i < count($wp_users); $i++) {
			$wp_users[$i]->first_name = get_user_meta( $wp_users[$i]->ID, 'first_name', true );
			$wp_users[$i]->last_name = get_user_meta( $wp_users[$i]->ID, 'last_name', true );
			if (!$wp_users[$i]->first_name) $wp_users[$i]->first_name = $wp_users[$i]->display_name;
		}

		return $wp_users;
	}
	
	
	
	private function get_separator( $string, $fallback = ';') {
		$seps = array(';',',','|',"\t");
		$max = 0;
		$separator = false;
		foreach($seps as $sep){
			$count = substr_count($string, $sep);
			if($count > $max){
				$separator = $sep;
				$max = $count;
			}
		}
		
		if($separator) return $separator;
		return $fallback;
	}
	
	
}
?>