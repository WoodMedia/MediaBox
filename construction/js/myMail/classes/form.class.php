<?php if (!defined('ABSPATH')) die('not allowed');

class mymail_form {

	private $values = array();
	private $errors = array();
	private $lists = array();
	private $message = '';
	static $add_script = false;

	public function __construct( ) {
		add_action('wp_footer', array( &$this, 'print_script'));
	}



	public function form($formid = 0, $tabindex = 100, $classes = '') {

		self::$add_script = true;
		
		global $mymail_form_tabstopp;
		$tabindex = $mymail_form_tabstopp ? $mymail_form_tabstopp : $tabindex;
		
		$cache = true;
		$msg_id = 0;
		$current_user = wp_get_current_user();
		$forms = mymail_option('forms');
		$backend = is_admin();

		$formid = (isset($forms[$formid])) ? $formid : 0;
		$form = $forms[$formid];
		
		if($current_user->ID != 0 && isset($form['prefill']) && !$backend){
			$this->values['email'] = $current_user->user_email;
			$this->values['firstname'] = get_user_meta( $current_user->ID, 'first_name', true );
			$this->values['lastname'] = get_user_meta( $current_user->ID, 'last_name', true );
			if (!$this->values['firstname']) $this->values['firstname'] = $current_user->display_name;
			$cache = false;
		}
		
		if(isset($_REQUEST['mymail_error']) && ($_REQUEST['id'] == $formid || isset($_REQUEST['extern'])) && !$backend){
		
			$transient = 'mymail_error_'.esc_attr($_REQUEST['mymail_error']);
			$data = get_transient($transient);
			if($data){
				$this->values = $data['values'];
				$this->errors = $data['errors'];
				$this->lists = $data['lists'];
				
				$cache = false;
				delete_transient($transient);
			}
		}
		
		if(isset($_REQUEST['mymail_success']) && ($_REQUEST['id'] == $formid || isset($_REQUEST['extern'])) && !$backend){
		
			$msg_id = intval($_REQUEST['mymail_success']);
			
			if($msg_id == 1){
				$this->message = '<p>'.mymail_text('success').'</p>';
			}else if($msg_id == 2){
				$this->message = '<p>'.mymail_text('confirmation').'</p>';
			}
			
			$cache = false;
		}
		
		
		$nonce = wp_create_nonce('mymail_nonce');
		$transient = 'mymail_form'.$formid;

		$html = '';

		$customfields = mymail_option('custom_field', array());
		$inline = isset($form['inline']);

		$html .= '<form action="'.admin_url('admin-ajax.php').'" method="post" class="mymail-form mymail-form-submit mymail-form-'.$formid.' '.esc_attr($classes).'">';

		$html .= '<div class="mymail-form-info '.(!empty($this->errors) ? 'error' :'success').'"'.(!empty($this->errors) || !empty($this->message) ? ' style="display:block"' : '').'>';
		$html .= $this->get_error_html();
		$html .= $this->message;
		$html .= '</div>';
		if(!$backend){
			$html .= '<input name="_wpnonce" type="hidden" value="'.$nonce.'">';
			$html .= '<input name="_referer" type="hidden" value="'.remove_query_arg(array('mymail_error', 'mymail_success'), $_SERVER['REQUEST_URI']).'">';
		}else{
			$html .= '<input name="_extern" type="hidden" value="1">';
		}
		$html .= '<input name="action" type="hidden" value="mymail_form_submit">';
		$html .= '<input name="formid" type="hidden" value="'.$formid.'">';
		
		if ( false === ( $fields = get_transient( $transient ) ) || !$cache ) {

			$fields = array();
			
			foreach($form['order'] as $field){
				
				$required = in_array($field, $form['required']);
				
				switch($field){
				
					
					case 'email':
					
						$fields['email'] = '<div class="mymail-wrapper mymail-email-wrapper'.(isset($this->errors['email']) ? ' error' : '').'">';
						if(!$inline) $fields['email'] .= '<label for="mymail-email-'.$formid.'">'.mymail_text('email').' <span class="required">*</span></label>';
						$fields['email'] .= '<input id="mymail-email-'.$formid.'" name="userdata[email]" type="text" value="'.(isset($this->values['email']) ? $this->values['email'] : '').'"'.($inline ? ' placeholder="'.mymail_text('email').' *"' : '').' class="input mymail-email required" tabindex="'.($tabindex++).'">';
						$fields['email'] .= '</div>';
						
					break;
					
					
					case 'firstname':
					
						$fields['firstname'] = '<div class="mymail-wrapper mymail-firstname-wrapper'.(isset($this->errors['firstname']) ? ' error' : '').'">';
						if(!$inline) $fields['firstname'] .= '<label for="mymail-firstname-'.$formid.'">'.mymail_text('firstname').($required ? ' <span class="required">*</span>' : '').'</label>';
						$fields['firstname'] .= '<input id="mymail-firstname-'.$formid.'" name="userdata[firstname]" type="text" value="'.(isset($this->values['firstname']) ? $this->values['firstname'] : '').'"'.($inline ? ' placeholder="'.mymail_text('firstname').($required ? ' *' : '').'"' : '').' class="input mymail-firstname'.($required ? ' required' : '').'" tabindex="'.($tabindex++).'">';
						$fields['firstname'] .= '</div>';
				
					break;
					
					case 'lastname':
					
						$fields['lastname'] = '<div class="mymail-wrapper mymail-lastname-wrapper'.(isset($this->errors['lastname']) ? ' error' : '').'">';
						if(!$inline) $fields['lastname'] .= '<label for="mymail-lastname-'.$formid.'">'.mymail_text('lastname').($required ? ' <span class="required">*</span>' : '').'</label>';
						$fields['lastname'] .= '<input id="mymail-lastname-'.$formid.'" name="userdata[lastname]" type="text" value="'.(isset($this->values['lastname']) ? $this->values['lastname'] : '').'"'.($inline ? ' placeholder="'.mymail_text('lastname').($required ? ' *' : '').'"' : '').' class="input mymail-lastname'.($required ? ' required' : '').'" tabindex="'.($tabindex++).'">';
						$fields['lastname'] .= '</div>';
				
					break;
					
					//custom fields
					default:
					
					if(!isset($customfields[$field])) break;
					$data = $customfields[$field];
					
					$fields[$field] = '<div class="mymail-wrapper mymail-'.$field.'-wrapper'.(isset($this->errors[$field]) ? ' error' : '').'">';
					
					if(!in_array($data['type'], array('checkbox')) && !$inline){
						$fields[$field] .= '<label for="mymail-'.$field.'-'.$formid.'">'.$data['name'];
						if ($required) $fields[$field] .= ' <span class="required">*</span>';
						$fields[$field] .= '</label>';
					}
					switch($data['type']){
					
						case 'dropdown':
						
							$fields[$field] .= '<select id="mymail-'.$field.'-'.$formid.'" name="userdata['.$field.']" class="input mymail-'.$field.''.($required ? ' required' : '').'" tabindex="'.($tabindex++).'">';
						foreach($data['values'] as $v){
							if(!isset($data['default'])) $data['default'] = false;
							$fields[$field] .= '<option value="'.$v.'" '.(isset($data['default']) ? selected($data['default'], (isset($this->values[$field]) ? $this->values[$field] : $v), false) : '').'>'.$v.'</option>';
						}
							$fields[$field] .= '</select>';
							break;
							
						case 'radio':
						
						$fields[$field] .= '<ul>';
						$i = 0;
						foreach($data['values'] as $v){
							$fields[$field] .= '<li><label><input id="mymail-'.$field.'-'.$formid.'-'.($i++).'" name="userdata['.$field.']" type="radio" value="'.$v.'" class="radio mymail-'.$field.''.($required ? ' required' : '').'" tabindex="'.($tabindex++).'" '.(isset($data['default']) ? checked($data['default'], (isset($this->values[$field]) ? $this->values[$field] : $v), false) : '').'> '.$v.'</label></li>';
						}
						$fields[$field] .= '</ul>';
							break;
							
						case 'checkbox':
						
							$fields[$field] .= '<label for="mymail-'.$field.'-'.$formid.'">';
							$fields[$field] .= '<input id="mymail-'.$field.'-'.$formid.'" name="userdata['.$field.']" type="checkbox" value="1" '.((isset($this->values[$field]) || isset($data['default']) ) ? ' checked' : '').' class="mymail-'.$field.''.($required ? ' required' : '').'" tabindex="'.($tabindex++).'"> ';
							$fields[$field] .= ' '.$data['name'];
							if ($required) $fields[$field] .= ' <span class="required">*</span>';
							$fields[$field] .= '</label>';
							
							break;
							
						default:
							$fields[$field] .= '<input id="mymail-'.$field.'-'.$formid.'" name="userdata['.$field.']" type="text" value="'.(isset($this->values[$field]) ? $this->values[$field] : '').'"'.($inline ? ' placeholder="'.$data['name'].($required ? ' *' : '').'"' : '').' class="input mymail-'.$field.''.($required ? ' required' : '').'" tabindex="'.($tabindex++).'">';
					}
					
					$fields[$field] .= '</div>';
					
				}
				
			}
			

			if (isset($form['userschoice']) && $form['userschoice']) {
				$fields['lists'] = '<div class="mymail-wrapper mymail-lists-wrapper"><label>'.mymail_text('lists', __('Lists', 'mymail')).'</label>';
				$lists = get_terms( 'newsletter_lists', array('hide_empty' => false) );
				
				if (isset($form['dropdown']) && $form['dropdown']) {
					$fields['lists'] .= '<br><select name="lists[]">';
					foreach ($lists as $list) {
						if (in_array($list->slug, $form['lists'])) $fields['lists'] .= '<option value="'.$list->slug.'"> '.$list->name.'</option>';
					}
					$fields['lists'] .= '</select>';
				}else{
					$fields['lists'] .= '<ul>';
					foreach ($lists as $list) {
						if (in_array($list->slug, $form['lists'])){
							$fields['lists'] .= '<li><label title="'.$list->description.'"><input class="mymail-list-'.$list->slug.'" type="checkbox" name="lists[]" value="'.$list->slug.'" '.(!empty($this->errors) && in_array($list->slug, $this->lists) ? 'checked' : empty($this->errors) ? 'checked' : '').'> '.$list->name;
							if(!empty($list->description)) $fields['lists'] .= ' <span class="mymail-list-description mymail-list-description-'.$list->slug.'">'.$list->description.'</span>';
							$fields['lists'] .= '</label></li>';
						}
					}
					$fields['lists'] .= '</ul>';
				}
				
				$fields['lists'] .= '</div>';
			}
			$fields['_submit'] = '<div class="mymail-wrapper mymail-submit-wrapper form-submit"><input name="submit" type="submit" value="'.mymail_text('submitbutton', __('Subscribe', 'mymail')).'" class="submit-button button" tabindex="'.($tabindex++).'"><span class="mymail-loader"></span></div>';

			if($cache) set_transient( $transient, $fields );
			
		}
		
		$fields = apply_filters('mymail_form_fields', $fields, $formid, $form);

		$html .= "\n".implode("\n", $fields)."\n";
		
		$html .= '</form>';

		//global
		$mymail_form_tabstopp = $tabindex;

		return apply_filters('mymail_form', $html, $formid, $form);
	}


	public function unsubscribe_form($hash = '', $campaignid = '', $tabindex = 100, $classes = '') {
	
		self::$add_script = true;
		
		global $mymail_form_tabstopp;
		$tabindex = $mymail_form_tabstopp ? $mymail_form_tabstopp : $tabindex;
		
		$msg_id = 0;
		
		if(isset($_REQUEST['mymail_success'])){
			$msg_id = intval($_REQUEST['mymail_success']);
			
			if($msg_id == 1){
				$this->message = '<p>'.mymail_text('unsubscribe').'</p>';
			}else if($msg_id == 2){
				$this->message = '<p>'.mymail_text('unsubscribeerror').'</p>';
			}
		}

		$html = '';

		$html .= '<form action="'.admin_url('admin-ajax.php').'" method="post" class="mymail-form mymail-form-unsubscribe '.$classes.'" id="mymail-form-unsubscribe">';
		$html .= '<div class="mymail-form-info '.($msg_id == 2 ? 'error' :'success').'"'.(!empty($this->errors) || !empty($this->message) ? ' style="display:block"' : '').'>';
		$html .= $this->get_error_html();
		$html .= $this->message;
		$html .= '</div>';
		$html .= '<input name="_wpnonce" type="hidden" value="'.wp_create_nonce('mymail_nonce').'">';
		$html .= '<input name="_referer" type="hidden" value="'.$_SERVER['REQUEST_URI'].'">';
		$html .= '<input name="hash" type="hidden" value="'.$hash.'">';
		$html .= '<input name="campaign" type="hidden" value="'.$campaignid.'">';
		$html .= '<input name="action" type="hidden" value="mymail_form_unsubscribe">';
		if(empty($hash)){
			
			$html .= '<div class="mymail-wrapper mymail-email-wrapper"><label for="mymail-email">'.mymail_text('email', __('Email', 'mymail')).' <span class="required">*</span></label>';
			$html .= '<input id="mymail-email" class="input mymail-email required" name="email" type="text" value="" tabindex="'.($tabindex++).'"></div>';
			
		}
		$html .= '<div class="mymail-wrapper mymail-submit-wrapper form-submit"><input name="submit" type="submit" value="'.mymail_text('unsubscribebutton', __('Unsubscribe', 'mymail')).'" class="submit-button button" tabindex="'.($tabindex++).'"><span class="mymail-loader"></span></div>';
		$html .= '</form>';

		//global
		$mymail_form_tabstopp = $tabindex;
		
		return apply_filters('mymail_unsubscribe_form', $html, $campaignid);
	}


	public function handle_submission( ) {
	
		$double_opt_in = mymail_option('double_opt_in');
		$baselink = get_permalink( mymail_option('homepage') );
		if(!$baselink) $baselink = site_url();
		
		$referer = isset($_POST['_referer']) ? $_POST['_referer'] : $baselink;
		
		$forms = mymail_option('forms');

		$form_id = (isset($forms[$_POST['formid']])) ? intval($_POST['formid']) : 0;
		$form = $forms[$form_id];
		
		$userdata = array();
		
		$customfields = mymail_option('custom_field');

		foreach ($form['order'] as $field){
		
			$userdata[$field] = isset($_POST['userdata'][$field]) ? esc_attr($_POST['userdata'][$field]) : '';
			
			if (($field == 'email' && !mymail_is_email(trim($userdata[$field]))) || (!$userdata[$field] && in_array($field, $form['required']))) {
				$this->errors[$field] = mymail_text($field, isset($customfields[$field]['name']) ? $customfields[$field]['name'] : $field);
			}
			
		}
		
		
		$userdata['email'] = trim($userdata['email']);
		
		$this->values = $userdata;

		if(mymail_option('track_users'))
			$userdata['_meta'] = array(
				'ip' => mymail_get_ip(),
				'signupip' => mymail_get_ip(),
				'signuptime' => current_time('timestamp'),
			);
		
		$this->lists = isset($form['userschoice']) ? (isset($_POST['lists']) ? (array) $_POST['lists'] : array()) : $form['lists'];
		
		$this->errors = apply_filters('mymail_submit_errors', $this->errors);
		$this->lists = apply_filters('mymail_submit_lists', $this->lists);
		$userdata = apply_filters('mymail_submit_userdata', $userdata);
		
		if(empty($this->lists)) $this->errors['lists'] = __('Select at least one list', 'mymail');
		
		if ($this->valid()) {
			$email = $userdata['email'];

			if ($double_opt_in) {
				//send confirmation email
				global $mymail_subscriber;

				if ($e = $mymail_subscriber->send_confirmation($baselink, $email, $userdata, $this->lists, false, (isset($form['template']) ? $form['template'] : 'notification.html'))) {
					$target = add_query_arg(array(
							'confirm' => ''
						), $baselink);
				}else {
					//error
				}
			}else {
				global $mymail_subscriber;
				

				unset($userdata['email']);

				//subscribe user
				if ($mymail_subscriber->insert($email, 'subscribed', $userdata, $this->lists )) {
					$target = add_query_arg(array(
							'subscribe' => ''
						), $baselink);
				}else {
					//error
				}
			}


			//redirect if no ajax request oder extern
			if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_POST['_extern'])) {
			
				
				$target = (!empty($form['redirect'])) ? $form['redirect'] : add_query_arg(array('mymail_success' => $double_opt_in+1, 'id' => $form_id, 'extern' => isset($_POST['_extern'])), $referer);
				wp_redirect(apply_filters('mymail_subscribe_target', $target, $form_id));
			} else {
			
				$return = array('html' => '<p>'.(($double_opt_in) ? mymail_text('confirmation') : mymail_text('success')).'</p>');
				
				if(!empty($form['redirect'])) $return = wp_parse_args(array('redirect' => $form['redirect']), $return);
				
				return $return;
			}

			return $target;

		} else {
		
			//redirect if no ajax request oder extern
			if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_POST['_extern'])) {
			
				$save = array(
					'values' => $this->values,
					'errors' => $this->errors,
					'lists' => $this->lists,
					
				);
				$hash = md5(serialize($save));
				set_transient( 'mymail_error_'.$hash, $save );
				$target = add_query_arg(array('mymail_error' => $hash, 'id' => $form_id, 'extern' => isset($_POST['_extern'])), $referer);
				wp_redirect($target);
			}

			return array('error' => true, 'fields' => $this->errors, 'html' => $this->get_error_html());
		}

	}


	private function get_error_html() {

		$html = '';
		if (!empty($this->errors)) {
			$html .= '<p>'.mymaiL_text('error').'</p><ul>';
			foreach ($this->errors as $field => $name) {
				$html .= '<li>'.$name.'</li>';
			}
			$html .= '</ul>';
		}

		return $html;
	}


	private function valid() {
		return empty($this->errors);
	}


	static function print_script() {
		if ( !self::$add_script )
			return;
		
		global $is_IE;
		if ( $is_IE ){
			wp_print_scripts('jquery');
			echo '<!--[if lte IE 9]>';
			wp_print_scripts('mymail-form-placeholder');
			echo '<![endif]-->';
		}

		if(mymail_option('ajax_form')) wp_print_scripts('mymail-form');

		
	}


}


?>