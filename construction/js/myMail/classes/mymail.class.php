<?php if (!defined('ABSPATH')) die('not allowed');

class mymail {

	private $defaultTemplate = 'mymail';
	private $template;
	private $templateobj;
	private $post_data;
	private $campaign_data;
	private $mail = array();

	static $form_active;

	public function __construct() {
		register_activation_hook(MYMAIL_DIR . '/myMail.php', array( &$this, 'activate'));
		register_deactivation_hook(MYMAIL_DIR . '/myMail.php', array( &$this, 'deactivate'));

		load_plugin_textdomain('mymail', false, '/myMail/languages');

		add_action('plugins_loaded', array( &$this, 'register_post_status'));
		add_action('init', array( &$this, 'register_post_type'));
		add_action('init', array( &$this, 'setup'));
		add_filter('plugin_action_links', array( &$this, 'add_action_link'), 10, 2 );
		add_filter('plugin_row_meta', array( &$this, 'add_plugin_links'), 10, 2 );
		//init cronjob
		$this->init_cron();
		
	}
	
	
	public function setup() {

		//remove revisions if newsletter is finished
		add_action('finished_newsletter', array( &$this, 'remove_revisions'));


		if (is_admin()) {
		
			if(get_option('mymail_welcome', false)) {
				if(!is_network_admin()){
					delete_option('mymail_welcome');
					add_action('shutdown', array( &$this, 'send_welcome_mail'), 99 );
					wp_redirect('edit.php?post_type=newsletter&page=mymail_welcome');
				}
			}
		
			//try to update
			$this->do_update();

			//add Dashboard widget for qualified users
			if (current_user_can('mymail_dashboard_widget'))
				add_action('wp_dashboard_setup', array( &$this, 'add_dashboard_widgets'));

			//default actions and filters

			add_action('paused_to_trash', array( &$this, 'paused_to_trash' ));
			add_action('active_to_trash', array( &$this, 'active_to_trash' ));
			add_action('queued_to_trash', array( &$this, 'queued_to_trash' ));
			add_action('finished_to_trash', array( &$this, 'finished_to_trash' ));
			add_action('trash_to_paused', array( &$this, 'trash_to_paused' ), 999);
			
			add_action('admin_menu', array( &$this, 'remove_meta_boxs'));
			add_action('save_post', array( &$this, 'save_post'), 10, 2);
			
			//add_action('contextual_help', array( &$this, 'help'));

			add_action('created_newsletter_lists', array( &$this, 'created_newsletter_lists'), 10, 2);
			add_action('delete_newsletter_lists', array( &$this, 'edited_newsletter_lists'), 10, 2);
			add_action('edited_newsletter_lists', array( &$this, 'edited_newsletter_lists'), 10, 2);

			add_action('admin_enqueue_scripts', array( &$this, 'admin_scripts_styles'), 10, 1);

			add_action('admin_notices', array( &$this, 'admin_notices') );
			
			add_filter('post_updated_messages', array( &$this, 'updated_messages'));
			add_filter('wp_insert_post_data', array( &$this, 'wp_insert_post_data'), 99, 2);
			

			//do stuff on certain pages
			global $pagenow;

			if ('edit.php' == $pagenow) {

				add_action('wp_loaded', array( &$this, 'edit_hook'));

			} else if ('edit-tags.php' == $pagenow) {

					add_filter("manage_edit-newsletter_lists_columns", array( &$this, 'list_columns'));

				} else if ('post-new.php' == $pagenow) {


					add_action('wp_loaded', array( &$this, 'post_new_hook'));

				} else if ('post.php' == $pagenow) {

					add_action('pre_get_posts', array( &$this, 'post_hook'));

					if (isset($_REQUEST['post_type']) && $_REQUEST['post_type'] == 'newsletter')

						//remove the KSES filter which strips "unwanted" tags and attributes
						remove_filter('content_save_pre', 'wp_filter_post_kses');

				} else if ('revision.php' == $pagenow) {

					add_filter('_wp_post_revision_field_post_content', array( &$this, 'revision_field_post_content'), 10, 2);

				}

			//ajax is everywhere!
			$this->ajax();

			//frontpage stuff (!is_admin())
		} else {
		
			if( !function_exists( 'shortcode_empty_paragraph_fix' ) )
				add_filter('the_content', array( &$this, 'shortcode_empty_paragraph_fix' ));
			
			add_action("template_redirect", array( &$this, 'front_page'), 1);

			require_once MYMAIL_DIR . '/classes/shortcodes.class.php';
			new mymail_shortcodes();

			add_action('wp_head', array( &$this, 'register_script'));
			
			add_action('wp_enqueue_scripts', array( &$this, 'style'));
			

		}
	}



	public function paused_to_trash($campaign) {
		set_transient( 'mymail_before_trash_status_'.$campaign->ID, 'paused' );
	}


	public function active_to_trash($campaign) {
		set_transient( 'mymail_before_trash_status_'.$campaign->ID, 'active' );
	}


	public function queued_to_trash($campaign) {
		set_transient( 'mymail_before_trash_status_'.$campaign->ID, 'queued' );
	}


	public function finished_to_trash($campaign) {
		set_transient( 'mymail_before_trash_status_'.$campaign->ID, 'finished' );
	}


	public function trash_to_paused($campaign) {

		$oldstatus = get_transient( 'mymail_before_trash_status_'.$campaign->ID, 'paused' );

		if ($campaign->post_status != $oldstatus) $this->change_status($campaign, $oldstatus, true);

	}



	public function created_newsletter_lists($term_id, $tt_id) {

		$term = get_term( $term_id, 'newsletter_lists' );
		$forms = mymail_option('forms');

		foreach ($forms as $id => $form) {
			if (isset($form['addlists']) && $form['addlists']) $forms[$id]['lists'][] = $term->slug;
		}

		mymail_update_option( 'forms', $forms );

		mymail_clear_cache('form');

	}
	public function edited_newsletter_lists($term_id, $tt_id) {
	
		mymail_clear_cache('form');

	}
	
	public function admin_notices() {
	
		global $mymail_notices;
	
		if($mymail_notices = get_option( 'mymail_notices' )){
			
			if(isset($_GET['mymail_remove_notice'])){
				
				unset($mymail_notices[$_GET['mymail_remove_notice']]);
				
				update_option( 'mymail_notices', $mymail_notices );
				
			}
			
			foreach($mymail_notices as $id => $notice){
			
		echo '<div class="'.$notice['type'].'"><p>';
		echo $notice['text'];
		if(!$notice['once']){
			echo ' &mdash; <a href="'.add_query_arg(array('mymail_remove_notice' => $id), $_SERVER['REQUEST_URI']).'">'.__('dismiss message', 'mymail').'</a>';
		}else{
			unset($mymail_notices[$id]);
		}
		echo '</p></div>';
			}
			
			update_option( 'mymail_notices', $mymail_notices );
		}
	}


	/*----------------------------------------------------------------------*/
	/* Frontpage
	/*----------------------------------------------------------------------*/


	public function front_page() {

		global $wp;

		add_filter( 'the_content', array( &$this, 'content_as_iframe' ), 2, 1000);
		add_filter( 'the_excerpt', array( &$this, 'content_as_iframe' ), 2, 1000);
		
		if (isset($_REQUEST['mymail']))
			$this->handleReferrer();
			
		if (isset($wp->query_vars["newsletter"]) || (isset($wp->query_vars["post_type"]) && $wp->query_vars["post_type"] == 'newsletter')) {
			global $post;
			if (isset($wp->query_vars["preview"])) {
				$preview = true;
				$args['post_type'] = 'newsletter';
				$args['p'] = $wp->query_vars["p"];

			} else {
				$preview = false;
				$args['post_type'] = 'newsletter';
				$args['post_status'] = array(
					'finished',
					'active'
				);
			}

			$args['posts_per_page'] = -1;
			$args['paged'] = get_query_var('paged') ? get_query_var('paged') : 1;
			$args['orderby'] = 'menu_order';

			if (have_posts()): while (have_posts()): the_post();

				require_once MYMAIL_DIR . '/classes/placeholder.class.php';

			$post_data = get_post_meta(get_the_ID(), 'mymail-data', true);

			$placeholder = new mymail_placeholder(get_the_content());
			$unsubscribe_homepage = (get_page( mymail_option('homepage') )) ? get_permalink(mymail_option('homepage')) : get_bloginfo('url');
			$unsubscribe_homepage = apply_filters('mymail_unsubscribe_link', $unsubscribe_homepage);

			$placeholder->add(array(
				'preheader' => $post_data['preheader'],
				'subject' => $post_data['subject'],
				'webversion' => '<a href="{webversionlink}">' . mymail_text('webversion') . '</a>',
				'webversionlink' => get_permalink(get_the_ID()),
				'unsub' => '<a href="{unsublink}">' . mymail_text('unsubscribelink') . '</a>',
				'unsublink' => add_query_arg('unsubscribe', '', $unsubscribe_homepage),
				'forward' => '<a href="{forwardlink}">' . mymail_text('forward') . '</a>',
				'forwardlink' => add_query_arg('forward', '', get_permalink(get_the_ID())),
				'email' => antispambot('some@example.com')
			));

			$placeholder->share_service(get_permalink(get_the_ID()), get_the_title());

			$content = $placeholder->get_content();

			if (isset($_GET['frame']) && $_GET['frame'] == '0') {

				if (!$content)
					wp_die(__('There is no content for this newsletter.', 'mymail') . ' <a href="wp-admin/post.php?post=' . get_the_ID() . '&action=edit">' . __('Add content', 'mymail') . '</a>');

				echo str_replace('<a ', '<a target="_top" ', $content);
				exit;

			} else {

				add_filter('get_previous_post_where', array( &$this, 'get_post_where' ));
				add_filter('get_next_post_where', array( &$this, 'get_post_where' ));

				$url = add_query_arg('frame', 0, get_permalink());

				if ($preview)
					$url = add_query_arg('preview', 1, $url);

				include MYMAIL_DIR . '/includes/social_services.php';

				if (!$custom = locate_template('single-newsletter.php')) {

					include MYMAIL_DIR . '/views/single-newsletter.php';

				} else {

					include $custom;

				}

				die();
			}



			endwhile;

			else:
				//NOT FOUND
				wp_redirect( home_url(), 301 ); exit;

			endif;

			// Reset Post Data
			wp_reset_postdata();

		}

	}



	public function content_as_iframe($content) {

		global $post;
		
		if($post->post_type != 'newsletter') return $content;

		return '<iframe class="mymail_frame" src="'.add_query_arg( 'frame', 0, get_permalink($post->ID)).'" style="min-width:610px;" width="'.apply_filters('mymail_iframe_width', '100%' ).'" scrolling="auto" frameborder="0" onload="this.height=this.contentWindow.document.body.scrollHeight+20;"></iframe>';

		exit;

	}


	public function handleReferrer() {


		//user preg_replace to remove unwanted whitespaces
		$target = isset($_REQUEST['t']) ? preg_replace('/\s+/', '', $_REQUEST['t']) : NULL;
		$hash = isset($_REQUEST['k']) ? preg_replace('/\s+/', '', $_REQUEST['k']) : NULL;
		$count = isset($_REQUEST['c']) ? intval($_REQUEST['c']) : NULL;
		$campaign_id = intval($_REQUEST['mymail']);
		
		if(!$campaign_id) return;

		//get user information
		$user = new WP_query( array(
			'post_type' => 'subscriber',
			'name' => $hash,
			'posts_per_page' => 1,
		));


		//user exists
		if ($user->post) {
			wp_cache_delete( $user->post->ID, 'post' . '_meta' );
			$user_campaign_data = get_post_meta($user->post->ID, 'mymail-campaigns', true);
			
			//update latest IP address
			if(mymail_option('track_users')){
				$user_data = get_post_meta($user->post->ID, 'mymail-userdata', true);
				$user_data['_meta']['ip'] = mymail_get_ip();
			}

			//user has recieved this campaign
			if (isset($user_campaign_data[$campaign_id])) {
				//register clicks for campaign
				wp_cache_delete( $campaign_id, 'post' . '_meta' );
				$campaign_data = get_post_meta($campaign_id, 'mymail-campaign', true);
				
				$is_autoresponder = 'autoresponder' == get_post_status( $campaign_id );

				//if users is here it must be openend
				if (!isset($user_campaign_data[$campaign_id]['open'])) {
					$campaign_data['opens']++;
					$user_campaign_data[$campaign_id]['open'] = current_time('timestamp');

					//save user country
					$country = mymail_ip2Country();

					if (!isset($campaign_data['countries'][$country]))
						$campaign_data['countries'][$country] = 0;
					$campaign_data['countries'][$country]++;

					
					if(mymail_option('trackcities')) {
						//save user city
						$city = mymail_ip2City();
						
						if($city == 'unknown') $city = (object) array('country_code' => '', 'city' => '');
						
						if(empty($city->country_code)) $city->country_code = $country;
						if(empty($city->country_code)) $city->country_code = 'unknown';
						
						if(!empty($city->country_code)){
							if (!isset($campaign_data['cities'][$city->country_code]))
								$campaign_data['cities'][$city->country_code] = array();
							
							if(empty($city->city)){
								$city->city = 'unknown';
								$city->latitude = 0;
								$city->longitude = 0;
							}
							
							if (!isset($campaign_data['cities'][$city->country_code][$city->city]))
								$campaign_data['cities'][$city->country_code][$city->city] = array('lat' => $city->latitude, 'lng' => $city->longitude, 'opens' => 0);
									
							$campaign_data['cities'][$city->country_code][$city->city]['opens']++;
						}
					}
					
				}
				
				$redirect_to = false;
				
				//target => link clicked
				if ($target) {
				

					//unsubscribe ?
					if (strpos($target, 'unsubscribe=' . md5($campaign_id . '_unsubscribe'))) {

						$unsubscribe = true;

						//change target for tracking
						$target = add_query_arg('unsubscribe', '', html_entity_decode($target));

					}

					$redirect_to = html_entity_decode($target);
					$target = apply_filters('mymail_click_target', $target, $campaign_id);
					
					//if users first click
					if (!isset($user_campaign_data[$campaign_id]['clicks'])) {
						//increase unique clicks
						$campaign_data['totaluniqueclicks']++;
						$user_campaign_data[$campaign_id]['firstclick'] = current_time('timestamp');
						global $mymail_subscriber;
						$mymail_subscriber->add_new('click');
					}

					//increase target clicks
					if (!isset($campaign_data['clicks'][$target]))
						$campaign_data['clicks'][$target] = array();
					if (!isset($campaign_data['clicks'][$target][$count]))
						$campaign_data['clicks'][$target][$count] = 0;
						
					$campaign_data['clicks'][$target][$count]++;

					//increase total clicks
					$campaign_data['totalclicks']++;

					//register user click

					if (!isset($user_campaign_data[$campaign_id]['totalclicks']))
						$user_campaign_data[$campaign_id]['totalclicks'] = 0;
					$user_campaign_data[$campaign_id]['totalclicks']++;

					//increase target clicks
					if (!isset($user_campaign_data[$campaign_id]['clicks'][$target])) {
						$user_campaign_data[$campaign_id]['clicks'][$target] = 0;
						//increase unique clicks
						if (!isset($user_campaign_data[$campaign_id]['totaluniqueclicks']))
							$user_campaign_data[$campaign_id]['totaluniqueclicks'] = 0;
						$user_campaign_data[$campaign_id]['totaluniqueclicks']++;
					}
					$user_campaign_data[$campaign_id]['clicks'][$target]++;

				}

				//save
				$this->post_meta($user->post->ID, 'mymail-campaigns', $user_campaign_data);
				if(isset($user_data)) $this->post_meta($user->post->ID, 'mymail-userdata', $user_data);

				$this->post_meta($campaign_id, 'mymail-campaign', $campaign_data);

			}

		}

		//no target => tracking image
		if (!$redirect_to) {
			header('Content-type: image/gif');
			# The transparent, beacon image
			echo chr(71).chr(73).chr(70).chr(56).chr(57).chr(97).chr(1).chr(0).chr(1).chr(0).chr(128).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(33).chr(249).chr(4).chr(1).chr(0).chr(0).chr(0).chr(0).chr(44).chr(0).chr(0).chr(0).chr(0).chr(1).chr(0).chr(1).chr(0).chr(0).chr(2).chr(2).chr(68).chr(1).chr(0).chr(59);
			die();

		} else {
			//redirect in any case
			(isset($unsubscribe) && $unsubscribe)
				? wp_redirect((mymail_option('homepage')) ? add_query_arg(array('k' => $campaign_id, 'unsubscribe' => $hash), get_permalink( mymail_option('homepage')) ) : get_bloginfo('url'))
				: wp_redirect($redirect_to, 301);
				
			exit();
		}
	}
	

	/*----------------------------------------------------------------------*/
	/* ACTIONs
	/*----------------------------------------------------------------------*/


	public function edit_hook() {
		if (isset($_REQUEST['post_type']) && 'newsletter' == $_REQUEST['post_type']) {

			//duplicate campaign
			if (isset($_REQUEST['duplicate'])) {
				if (wp_verify_nonce($_REQUEST['_wpnonce'], 'mymail_nonce')) {
					if ($id = $this->duplicate_campaign(intval($_REQUEST['duplicate']))) {
						$status = (isset($_REQUEST['post_status'])) ? '&post_status='.$_REQUEST['post_status'] : '';
						(isset($_REQUEST['edit'])) ? wp_redirect('post.php?post=' . $id . '&action=edit') : wp_redirect('edit.php?post_type=newsletter'.$status);
					}
				}

				//pause campaign
			} else if (isset($_REQUEST['pause'])) {
					if (wp_verify_nonce($_REQUEST['_wpnonce'], 'mymail_nonce')) {
						$id = intval($_REQUEST['pause']);
						if ($this->pause_campaign($id)) {
						$status = (isset($_REQUEST['post_status'])) ? '&post_status='.$_REQUEST['post_status'] : '';
						(isset($_REQUEST['edit'])) ? wp_redirect('post.php?post=' . $id . '&action=edit') : wp_redirect('edit.php?post_type=newsletter'.$status);
						}
					}

					//continue/start campaign
				} else if (isset($_REQUEST['start'])) {
					if (wp_verify_nonce($_REQUEST['_wpnonce'], 'mymail_nonce')) {
					$id = intval($_REQUEST['start']);
						if ($this->start_campaign($id)) {
						$status = (isset($_REQUEST['post_status'])) ? '&post_status='.$_REQUEST['post_status'] : '';
						(isset($_REQUEST['edit'])) ? wp_redirect('post.php?post=' . $id . '&action=edit') : wp_redirect('edit.php?post_type=newsletter'.$status);
						}
					}
				}

			add_filter('post_row_actions', array( &$this, 'quick_edit_btns'), 10, 2);
			
			add_filter('bulk_actions-edit-newsletter', array( &$this, 'bulk_actions'));
			add_filter("manage_edit-newsletter_columns", array( &$this, "columns"));
			add_filter("manage_newsletter_posts_custom_column", array( &$this, "columns_content"));
			add_filter("manage_edit-newsletter_sortable_columns", array( &$this, "columns_sortable"));
			add_filter("parse_query", array( &$this, "columns_sortable_helper"));
			
			add_action('admin_menu', array( &$this, 'add_welcome_page'));
			
		}
		
	}


	public function post_hook() {
		global $post;
		//only on edit old newsletter and save
		if ('newsletter' == $post->post_type) {

			add_filter('enter_title_here', array( &$this, "title"));
			
			add_action('dbx_post_sidebar', array( &$this, 'add_ajax_nonce'));
			
			$this->post_data = get_post_meta($post->ID, 'mymail-data', true);
			$this->campaign_data = get_post_meta($post->ID, 'mymail-campaign', true);

			if ($this->campaign_data)
				add_action('submitpost_box', array( &$this, 'notice'));

			if (isset($_REQUEST['template'])) {
				$file = (isset($_REQUEST['file'])) ? $_REQUEST['file'] : 'index.html';
				$this->set_template($_REQUEST['template'], $file, true);
			} else if (isset($this->post_data['template'])) {
				$file = (isset($this->post_data['file'])) ? $this->post_data['file'] : 'index.html';
				$this->set_template($this->post_data['template'], $file);
			} else {
				$file = (isset($this->post_data['file'])) ? $this->post_data['file'] : 'index.html';
				$this->set_template(mymail_option('default_template', $this->defaultTemplate), $file);
			}
			
		}
	}


	public function post_new_hook() {
		if (isset($_REQUEST['post_type']) && 'newsletter' == $_REQUEST['post_type']) {

			add_filter("enter_title_here", array( &$this, "title"));

			add_action('dbx_post_sidebar', array( &$this, 'add_ajax_nonce'));
			
			if (isset($_REQUEST['template'])) {
				$file = (isset($_REQUEST['file'])) ? $_REQUEST['file'] : 'index.html';
				$this->set_template($_REQUEST['template'], $file, true);
			} else {
				$this->set_template(mymail_option('default_template', $this->defaultTemplate));
			}
		}
	}


	public function notice() {

		if (!$this->campaign_data)
			return false;

		global $post;
		switch ($post->post_status) {
		case 'finished':
			$msg = sprintf(__('This Campaign was sent on %s', 'mymail'), '<strong class="nowrap">'.date(get_option('date_format') . ' ' . get_option('time_format'), $this->campaign_data['timestamp']).'</strong>');
			break;
		case 'queued':
			$msg = __('This Campaign is currently in the queue', 'mymail');
			break;
		case 'active':
			$msg = __('This Campaign is currently progressing', 'mymail');
			break;
		}

		if (!isset($msg))
			return false;

		echo '<div class="mymail_info">' . $msg .'</div>';

	}
	
	
	public function send_campaign_to_subscriber( $campaign, $subscriber, $track = false, $forcesend = false, $force = false ) {
	
		if(is_numeric($campaign)) $campaign = get_post($campaign);
		if(is_numeric($subscriber)) $subscriber = get_post($subscriber);
		
		if(!$campaign || $campaign->post_type != 'newsletter') return new WP_Error('wrong_post_type', __('wrong post type', 'mymail'));
		if(!$subscriber || $subscriber->post_type != 'subscriber') return new WP_Error('wrong_post_type', __('wrong post type', 'mymail'));
		
		if($subscriber->post_status != 'subscribed' && !$forcesend) return new WP_Error('user_unsubscribed', __('User has not subscribed', 'mymail'));;
		
			//get data from the newsletter
		$data = get_post_meta($campaign->ID, 'mymail-data', true);
		$campaigndata = get_post_meta($campaign->ID, 'mymail-campaign', true);
		
			//get campaign data for the user
		$userdata = get_post_meta($subscriber->ID, 'mymail-userdata', true);
		$usercampaigndata = get_post_meta($subscriber->ID, 'mymail-campaigns', true);
		
			//unset meta property if set
		if (isset($userdata['_meta'])) unset($userdata['_meta']);
		
			//get total sends
		$totalsend = isset($campaigndata['sent']) ? $campaigndata['sent'] : 0;
		$errors = isset($campaigndata['errors']) ? $campaigndata['errors'] : 0;
		$totalerrors = isset($campaigndata['totalerrors']) ? $campaigndata['totalerrors'] : count($campaigndata['errors']);
		
		$unsubscribe_homepage = (get_page( mymail_option('homepage') )) ? get_permalink(mymail_option('homepage')) : get_bloginfo('url');
		$unsubscribe_homepage = apply_filters('mymail_unsubscribe_link', $unsubscribe_homepage);
		
		$unsubscribelink = add_query_arg('unsubscribe', md5($campaign->ID . '_unsubscribe'), $unsubscribe_homepage);

		require_once MYMAIL_DIR . '/classes/mail.class.php';
	
		$mail = mymail_mail::get_instance();
	
		$to = $subscriber->post_title;
	
		$mail->to = $to;
		$mail->subject = $data['subject'];
		$mail->from = $data['from'];
		$mail->from_name = $data['from_name'];
		$mail->reply_to = $data['reply_to'];
		$mail->preheader = $data['preheader'];
		$mail->embed_images = $data['embed_images'];
		$mail->add_tracking_image = $track;
		$mail->hash = $subscriber->post_name;
	
		require_once MYMAIL_DIR . '/classes/placeholder.class.php';
		$placeholder = new mymail_placeholder($this->sanitize_content($campaign->post_content));
		
		$placeholder->add(array(
				'preheader' => $data['preheader'],
				'subject' => $data['subject'],
				'webversion' => '<a href="{webversionlink}">' . mymail_text('webversion') . '</a>',
				'webversionlink' => get_permalink($campaign->ID),
				'unsub' => '<a href="{unsublink}">' . mymail_text('unsubscribelink') . '</a>',
				'unsublink' => $unsubscribelink,
				'forward' => '<a href="{forwardlink}">' . mymail_text('forward') . '</a>',
				'forwardlink' => add_query_arg('forward', $subscriber->post_title , get_permalink($campaign->ID)),
				'email' => '<a href="mailto:{emailaddress}">{emailaddress}</a>'
		));
		
		$placeholder->add(array_merge(array(
					'fullname' => trim($userdata['firstname'] . ' ' . $userdata['lastname'])
			), array(
					'emailaddress' => $subscriber->post_title
			), $userdata));
							
		$placeholder->share_service(get_permalink($campaign->ID), $campaign->post_title);
	
		$mail->content = $placeholder->get_content();
	
		if($track){
	
			//get all links from the basecontent
			preg_match_all('#href=(\'|")?(https?[^\'"\#]+)(\'|")?#', $mail->content, $links);
			$links = $links[2];
			
			//baselink with trailing slash required for links to work in Outlook 2007
			$baselink = add_query_arg('mymail', $campaign->ID, home_url('/'));

			//replace links
			$placeholder->replace_links($baselink, $links, $subscriber->post_name);
			$mail->baselink = $baselink;

			$mail->content = $placeholder->get_content();
			
			$mail->add_header('X-MyMail', $subscriber->post_name);
			$mail->add_header('X-MyMail-Campaign', $campaign->ID);
			$mail->add_header('List-Unsubscribe', add_query_arg(array('k' => $campaign->ID, 'unsubscribe' => $subscriber->post_name), $unsubscribelink));
		}
		
		$placeholder->set_content($mail->subject);
		$mail->subject = $placeholder->get_content();
	
		$mail->prepare_content();
	
		if (($success = $mail->send($force)) && $track) {
		
			//mark as send and increase total with 1
			$usercampaigndata[$campaign->ID]['sent'] = true;
			$usercampaigndata[$campaign->ID]['timestamp'] = current_time('timestamp');
			if (!isset($usercampaigndata[$campaign->ID]['bounces'])) $bounces_only = false;
			
			$this->post_meta($subscriber->ID, 'mymail-campaigns', $usercampaigndata);
						
			//load it again cause it may changed during sending
			wp_cache_delete( $campaign->ID, 'post' . '_meta' );
			$campaigndata = get_post_meta( $campaign->ID, 'mymail-campaign', true);
			//count users and save to campaign
			$campaigndata['sent'] = $totalsend+1;
			$campaigndata['errors'] = $errors;
			
			$this->post_meta($campaign->ID, 'mymail-campaign', $campaigndata);
		}
		
		if(!$success){
			if(!mymail_is_email($to)) return new WP_Error('invalid_email', __('Invalid email', 'mymail'));
		}
		
		
		$mail->close();
		
		return $success;
	}
	
	/*----------------------------------------------------------------------*/
	/* AJAX
	/*----------------------------------------------------------------------*/


	private function ajax() {
		add_action('wp_ajax_mymail_get_template', array( &$this, 'ajax_get_template'));
		add_action('wp_ajax_mymail_create_new_template', array( &$this, 'ajax_create_new_template'));
		
		
		add_action('wp_ajax_mymail_set_preview', array( &$this, 'ajax_set_preview'));
		add_action('wp_ajax_mymail_get_preview', array( &$this, 'ajax_get_preview'));
		add_action('wp_ajax_mymail_toogle_codeview', array( &$this, 'ajax_toogle_codeview'));
		add_action('wp_ajax_mymail_send_test', array( &$this, 'ajax_send_test'));
		add_action('wp_ajax_mymail_get_totals', array( &$this, 'ajax_get_totals'));
		add_action('wp_ajax_mymail_save_color_schema', array( &$this, 'ajax_save_color_schema'));
		add_action('wp_ajax_mymail_delete_color_schema', array( &$this, 'ajax_delete_color_schema'));
		add_action('wp_ajax_mymail_delete_color_schema_all', array( &$this, 'ajax_delete_color_schema_all'));
		
		add_action('wp_ajax_mymail_get_recipients', array( &$this, 'ajax_get_recipients'));
		add_action('wp_ajax_mymail_get_recipients_page', array( &$this, 'ajax_get_recipients_page'));
		add_action('wp_ajax_mymail_get_recipient_detail', array( &$this, 'ajax_get_recipient_detail'));
		add_action('wp_ajax_mymail_get_post_term_dropdown', array( &$this, 'ajax_get_post_term_dropdown'));
		add_action('wp_ajax_mymail_check_for_posts', array( &$this, 'ajax_check_for_posts'));
		
		
		
		add_action('wp_ajax_mymail_create_image', array( &$this, 'ajax_create_image'));
		add_action('wp_ajax_mymail_image_placeholder', array( &$this, 'ajax_image_placeholder'));
		
		
		add_action('wp_ajax_mymail_get_post_list', array( &$this, 'ajax_get_post_list'));
		add_action('wp_ajax_mymail_get_post', array( &$this, 'ajax_get_post'));
		add_action('wp_ajax_mymail_forward_message', array( &$this, 'ajax_forward_message'));
		add_action('wp_ajax_mymail_nopriv_forward_message', array( &$this, 'ajax_forward_message'));

		add_action('wp_ajax_mymail_form_submit', array( &$this, 'ajax_form_submit'));
		add_action('wp_ajax_nopriv_mymail_form_submit', array( &$this, 'ajax_form_submit'));
		add_action('wp_ajax_mymail_form_unsubscribe', array( &$this, 'ajax_form_unsubscribe'));
		add_action('wp_ajax_nopriv_mymail_form_unsubscribe', array( &$this, 'ajax_form_unsubscribe'));

		add_action('wp_ajax_mymail_form_css', array( &$this, 'mymail_form_css'));
		add_action('wp_ajax_nopriv_mymail_form_css', array( &$this, 'mymail_form_css'));

	}


	public function ajax_form_submit() {

		$return['success'] = false;
		
		if(!isset($_POST['_extern']))$this->ajax_nonce(json_encode($return));

		require_once MYMAIL_DIR . '/classes/form.class.php';

		global $mymail_form;

		if (!$mymail_form)
			$mymail_form = new mymail_form();

		$return = $mymail_form->handle_submission();

		$return['success'] = !(isset($return['error']));

		echo json_encode($return);
		exit;
	}


	public function ajax_form_unsubscribe() {

		$return['success'] = false;

		$this->ajax_nonce(json_encode($return));

		require_once MYMAIL_DIR . '/classes/form.class.php';

		global $mymail_subscriber;
		
		$hash = $_POST['hash'];
		if(isset($_POST['email'])){
			if(!empty($_POST['email'])){
				$hash = $_POST['email'];
			}
			$return['fields'] = array('email' => mymail_text('email'));
		}
		
		$campaign_id = !empty($_POST['campaign']) ? intval($_POST['campaign']) : NULL;

		$return['success'] = $mymail_subscriber->unsubscribe($hash, $campaign_id);
		
		//redirect if no ajax request
		if (!isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
			$target = add_query_arg(array('mymail_success' => ($return['success']) ? 1 : 2), $_POST['_referer']);
			wp_redirect($target);
		} else {
			
			$return['html'] = $return['success'] ? mymail_text('unsubscribe') : mymail_text('unsubscribeerror');
			echo json_encode($return);
			exit;
		}

	}


	public function ajax_get_template() {
		$this->ajax_nonce('not allowed');
		
		@error_reporting(0);
		
		$id = intval($_GET['id']);
		$template = $_REQUEST['template'];
		$file = isset($_REQUEST['file']) ? $_REQUEST['file'] : 'index.html';
		$editorstyle = ($_REQUEST['editorstyle'] == '1');
		$data = get_post_meta($id, 'mymail-data', true);

		$this->set_template($template, $file, true);
		
		if(!isset($data['file'])) $data['file'] = 'index.html';
		
		//template has been changed
		if (!isset($data['template']) || $template != $data['template'] || $file != $data['file']) {
			$html = $this->get_template_by_slug($template, $file, false, $editorstyle);
		} else {
			$html = $this->get_template_by_id($id, $file, false, $editorstyle);
		}
		
		if (!$editorstyle) {
			$revision = isset($_REQUEST['revision']) ? (int) $_REQUEST['revision'] : false;
			$post = get_post($id);
			$subject = isset($_REQUEST['subject']) ? esc_attr($_REQUEST['subject']) : isset($data['subject']) ? esc_attr($data['subject']) : '';

			$current_user = wp_get_current_user();
			
			if ($revision) {
				$revision = get_post($revision);
				$html = $this->sanitize_content($revision->post_content);
			}

			require_once MYMAIL_DIR . '/classes/placeholder.class.php';
			$placeholder = new mymail_placeholder($html);

			$placeholder->add(array(
				'subject' => $subject,
				'webversion' => '<a href="{webversionlink}">' . mymail_text('webversion') . '</a>',
				'webversionlink' => get_permalink($post->ID),
				'unsub' => '<a href="{unsublink}">' . mymail_text('unsubscribelink') . '</a>',
				'unsublink' => add_query_arg('unsubscribe', '', get_permalink(mymaiL_option('homepage'))),
				'forward' => '<a href="{forwardlink}">' . mymail_text('forward') . '</a>',
				'forwardlink' => add_query_arg('forward', $current_user->user_email, get_permalink($post->ID)),
				'email' => '<a href="mailto:{emailaddress}">{emailaddress}</a>',
				'emailaddress' => $current_user->user_email,
			));

			
			if ( 0 != $current_user->ID ) {
				$firstname = ($current_user->user_firstname) ? $current_user->user_firstname : $current_user->display_name;
			
				$placeholder->add(array(
					'firstname' => $firstname,
					'lastname' => $current_user->user_lastname,
					'fullname' => trim($firstname.' '.$current_user->user_lastname),
				));
			}
			
			$placeholder->share_service(get_permalink($post->ID), $post->post_title);

			$html = $placeholder->get_content();
		}

		echo $html;
		exit;

	}


	public function ajax_create_new_template() {
		$return['success'] = false;

		$this->ajax_nonce(json_encode($return));
		
		$this->ajax_filesystem( );
		
		$content = $this->sanitize_content($_POST['content']);
		
		$name = esc_attr($_POST['name']);
		$template = esc_attr($_POST['template']);
		$modules = 	!!($_POST['modules'] === 'true');
		$overwrite = !!($_POST['overwrite'] === 'true');
		
		require_once MYMAIL_DIR.'/classes/templates.class.php';
		
		$t = new mymail_templates($template);
		$filename = $t->create_new($name, $content, $modules, $overwrite);
		
		if($return['success'] = $filename !== false) $return['url'] = add_query_arg(array( 'template' => $template, 'file' => $filename, 'message' => 3 ), wp_get_referer());
		if(!$return['success']) $return['msg'] = __('Unable to save template!', 'mymail');

		echo json_encode($return);
		die();
		
	}


	public function ajax_toogle_codeview() {
		$return['success'] = false;

		$this->ajax_nonce(json_encode($return));

		$return['content'] = $this->sanitize_content($_POST['content']);

		echo json_encode($return);
		exit;
	}


	public function ajax_set_preview() {
		$return['success'] = false;

		$this->ajax_nonce(json_encode($return));

		$content = isset($_POST['content']) ? ($_POST['content']) : '';
		$subject = isset($_POST['subject']) ? esc_attr($_POST['subject']) : '';
		$issue = isset($_POST['issue']) ? intval($_POST['issue']) : 1;

		$html = $this->sanitize_content($content);
		$current_user = wp_get_current_user();
	

		require_once MYMAIL_DIR . '/classes/placeholder.class.php';
		$placeholder = new mymail_placeholder($html);

		$placeholder->add(array(
			'issue' => $issue,
			'subject' => $subject,
			'webversion' => '<a href="{webversionlink}">' . mymail_text('webversion') . '</a>',
			'webversionlink' => '#',
			'unsub' => '<a href="{unsublink}">' . mymail_text('unsubscribelink') . '</a>',
			'unsublink' => add_query_arg('unsubscribe', '', get_permalink(mymaiL_option('homepage'))),
			'forward' => '<a href="{forwardlink}">' . mymail_text('forward') . '</a>',
			'forwardlink' => '#',
			'email' => '<a href="mailto:{emailaddress}">{emailaddress}</a>',
			'emailaddress' => $current_user->user_email,
		));

		
		if ( 0 != $current_user->ID ) {
			$firstname = ($current_user->user_firstname) ? $current_user->user_firstname : $current_user->display_name;
		
			$placeholder->add(array(
				'firstname' => $firstname,
				'lastname' => $current_user->user_lastname,
				'fullname' => trim($firstname.' '.$current_user->user_lastname),
			));
		}
		
		$placeholder->share_service('{webversionlink}', esc_attr($_POST['subject']));
		$content = $placeholder->get_content();
		
		$content = str_replace('@media only screen and (max-device-width:', '@media only screen and (max-width:', $content);
		
		$hash = md5($content);
		
		//cache preview for 60 seconds
		set_transient( 'mymail_p_'.$hash, $content, 60 );

		$placeholder->set_content($subject);
		$return['subject'] = $placeholder->get_content();
		$return['hash'] = $hash;
		$return['nonce'] = wp_create_nonce( 'mymail_nonce' );
		$return['success'] = true;

		echo json_encode($return);
		exit;
	}


	public function ajax_get_preview() {

		$this->ajax_nonce('not allowed');

		$hash = $_GET['hash'];

		$content = get_transient( 'mymail_p_'.$hash );
		
		if(empty($content)) $content = 'error';
		
		echo $content;
		exit;
	}


	public function ajax_send_test() {
		$return['success'] = false;

		$this->ajax_nonce(json_encode($return));

		require_once MYMAIL_DIR . '/classes/mail.class.php';

		$mail = mymail_mail::get_instance();
		$isnotspam = false;

		if (isset($_POST['test'])) {
		
			$basic = !!($_POST['basic'] === 'true');

			$to = esc_attr($_POST['to']);
			
			if($basic){
				$return['success'] = $mail->sendtest($to);
				
			}else{
			
				$mail->to = 'check@isnotspam.com';
				$mail->subject = 'Authentication Check';
				$mail->reply_to = $to;
				$return['success'] = $isnotspam = $mail->send_notification( '', 'Authentication Check');
				
			}
			 

		} else {

			$to = explode(',',esc_attr($_POST['to']));
			
			$post_id = intval($_POST['ID']);
			$issue = isset($_POST['issue']) ? intval($_POST['issue']) : 1;
			
			$unsubscribe_homepage = (get_page( mymail_option('homepage') )) ? get_permalink(mymail_option('homepage')) : get_bloginfo('url');
			$unsubscribe_homepage = apply_filters('mymail_unsubscribe_link', $unsubscribe_homepage);
			
			$campaign_permalink = get_permalink($post_id);
			
			$mail->to = $to;
			$mail->subject = stripslashes($_POST['subject']);
			$mail->from = esc_attr($_POST['from']);
			$mail->from_name = stripslashes(esc_attr($_POST['from_name']));
			$mail->reply_to = esc_attr($_POST['reply_to']);
			$mail->embed_images = !!($_POST['embed_images'] === 'true');

			require_once MYMAIL_DIR . '/classes/placeholder.class.php';
			
			$content = $this->sanitize_content($_POST['content']);

			$placeholder = new mymail_placeholder($content);

			$placeholder->add(array(
				'issue' => $issue,
				'subject' => $mail->subject,
				'preheader' => esc_attr($_POST['preheader']),
				'email' => '<a href="mailto:{emailaddress}">{emailaddress}</a>',
				'emailaddress' => $to[0],
				'webversion' => '<a href="{webversionlink}">' . mymail_text('webversion') . '</a>',
				'webversionlink' => $campaign_permalink,
				'unsub' => '<a href="{unsublink}">' . mymail_text('unsubscribelink') . '</a>',
				'unsublink' => add_query_arg('unsubscribe','', $unsubscribe_homepage),
				'forward' => '<a href="{forwardlink}">' . mymail_text('forward') . '</a>',
				'forwardlink' => add_query_arg('forward', $to[0], $campaign_permalink),
			));
			
			$current_user = wp_get_current_user();
			
			$firstname = ($current_user->user_firstname) ? $current_user->user_firstname : $current_user->display_name;
			
			$placeholder->add(array(
				'firstname' => $firstname,
				'lastname' => $current_user->user_lastname,
				'fullname' => trim($firstname.' '.$current_user->user_lastname),
			));
			
			$placeholder->share_service($campaign_permalink, $mail->subject);

			$mail->content = $placeholder->get_content();

			$placeholder->set_content($mail->subject);
			$mail->subject = $placeholder->get_content();

			$mail->add_tracking_image = false;
			$mail->prepare_content();

			$return['success'] = $mail->send();
			
			if (!!($_POST['isnotspam'] === 'true')){
				$frommail = $mail->from;
				$mail->from = $to[0];
				$mail->to = 'check@isnotspam.com';
				$return['success'] = $isnotspam = $return['success'] && $mail->send();
			}

			$mail->close();
		}

		$return['msg'] = ($return['success']) ? __('Message sent. Check your inbox!', 'mymail').($isnotspam ? '<br/><a href="http://isnotspam.com/report.php?email='.urlencode($frommail).'" target="_blank">'.__('Check isNotSpam report', 'mymail').'</a>' : '') : ($mail->sentlimitreached ? sprintf(__('Sent limit of %1$s reached! You have to wait %2$s before you can send more mails!', 'mymail'), '<strong>'.$mail->send_limit.'</strong>', '<strong>'.human_time_diff(get_option('_transient_timeout__mymail_send_period_timeout')).'</strong>') : __('Couldn\'t send message. Check your settings!', 'mymail').$mail->get_errors('br'));
		echo json_encode($return);
		exit;

	}


	public function ajax_get_totals() {
		global $wpdb;
		$return['success'] = false;

		$this->ajax_nonce(json_encode($return));

		$lists = $_POST['lists'];
		
		echo $this->get_totals($lists);
		exit;
	}


	public function ajax_save_color_schema() {
		$return['success'] = false;

		$this->ajax_nonce(json_encode($return));

		$colors = get_option('mymail_colors');
		$hash = md5(implode('', $_POST['colors']));

		if (!isset($colors[$_POST['template']])) $colors[$_POST['template']] = array();
		$colors[$_POST['template']][$hash] = $_POST['colors'];

		$return['html'] = '<ul class="colorschema custom" data-hash="' . $hash . '">';
		foreach ($_POST['colors'] as $color) {
			$return['html'] .= '<li class="colorschema-field" data-hex="' . $color . '" style="background-color:' . $color . '"></li>';
		}
		$return['html'] .= '<li class="colorschema-delete-field"><a class="colorschema-delete">&#10005;</a></li></ul>';

		$return['success'] = update_option('mymail_colors', $colors);
		echo json_encode($return);
		die();

	}

	public function ajax_delete_color_schema() {
		$return['success'] = false;

		$this->ajax_nonce(json_encode($return));

		$colors = get_option('mymail_colors');
		
		$template = esc_attr($_POST['template']);

		if (!isset($colors[$template])) $colors[$template] = array();
		
		if(isset($colors[$template][$_POST['hash']])) unset($colors[$template][$_POST['hash']]);

		if(empty($colors[$template])) unset($colors[$template]);
		
		$return['success'] = update_option('mymail_colors', $colors);
		echo json_encode($return);
		exit;

	}
	
	public function ajax_delete_color_schema_all() {
		$return['success'] = false;

		$this->ajax_nonce(json_encode($return));

		$colors = get_option('mymail_colors');
		
		$template = esc_attr($_POST['template']);

		if(isset($colors[$template])) unset($colors[$template]);
		
		$return['success'] = update_option('mymail_colors', $colors);
		echo json_encode($return);
		exit;

	}


	public function ajax_get_recipients() {
	
		$return['success'] = false;

		$this->ajax_nonce(json_encode($return));

		$campaign_ID = (int) $_POST['id'];
		
		$return['html'] = '<table class="wp-list-table widefat"><tbody>';
		
		$return['html'] .= $this->get_recipients_part($campaign_ID);
		
		$return['html'] .= '</tbody>';
		$return['html'] .= '</table>';
		
		echo json_encode($return);
		exit;
		
	}


	public function ajax_get_recipients_page() {
	
		$return['success'] = false;

		$this->ajax_nonce(json_encode($return));

		$campaign_ID = (int) $_POST['id'];
		$page = (int) $_POST['page'];
		
		$return['html'] = $this->get_recipients_part($campaign_ID, $page);
		$return['success'] = true;
		
		echo json_encode($return);
		exit;
		
	}
	
	
	public function ajax_get_recipient_detail(){
		
		$return['success'] = false;

		$this->ajax_nonce(json_encode($return));
		
		global $mymail_subscriber;
		
		$subscriber = get_post(intval($_POST['id']));
		$campaignID = (int) $_POST['campaignid'];
		
		$userdata = get_post_meta($subscriber->ID, 'mymail-userdata', true);
		$campaigndata = get_post_meta($subscriber->ID, 'mymail-campaigns', true);
		
		$campaigndata = isset($campaigndata[$campaignID]) ? $campaigndata[$campaignID] : false;
		$return['success'] = true;
		
		$name = trim($userdata['firstname'].' '.$userdata['lastname']);
		
		$return['html'] = '<div class="user_image" title="'.__('Source', 'mymail') .': Gravatar.com" data-email="'.$subscriber->post_title.'" style="background-image:url('.$mymail_subscriber->get_gravatar_uri($subscriber->post_title).')"></div>';
		
		$return['html'] .= '<div class="receiver-detail-data">';
		$return['html'] .= '<h4>'.($name ? $name : $subscriber->post_title).' <a href="post.php?post='.$subscriber->ID.'&action=edit">'.__('edit', 'mymail').'</a></h4>';
		$return['html'] .= '<ul>';
		
		
		$return['html'] .= '<li><label>'.__('sent', 'mymail').':</label> '.(isset($campaigndata['timestamp']) ? date(get_option('date_format').' '.get_option('time_format'), $campaigndata['timestamp']).', '.sprintf(__('%s ago', 'mymail'), human_time_diff($campaigndata['timestamp']-(get_option('gmt_offset')*3600))) : __('not yet', 'mymail')).'</li>';
		$return['html'] .= '<li><label>'.__('opens', 'mymail').':</label> '.(isset($campaigndata['open']) ? date(get_option('date_format').' '.get_option('time_format'), $campaigndata['open']).', '.sprintf(__('%s ago', 'mymail'), human_time_diff($campaigndata['open']-(get_option('gmt_offset')*3600))) : __('not yet', 'mymail')).'</li>';
		if(isset($campaigndata['open'])) $return['html'] .= '<li><label>'.__('first click', 'mymail').':</label> '.(isset($campaigndata['firstclick']) ? date(get_option('date_format').' '.get_option('time_format'), $campaigndata['firstclick']).', '.sprintf(__('%s ago', 'mymail'), human_time_diff($campaigndata['firstclick']-(get_option('gmt_offset')*3600))) : __('not yet', 'mymail')).'</li>';
		
		if(isset($campaigndata['bounces'])) $return['html'] .= '<li><label class="red">'.sprintf( _n( '%d bounce', '%d bounces', $campaigndata['bounces'], 'mymail'), $campaigndata['bounces']).'</label> </li>';

		if(isset($campaigndata['clicks'])){
			$return['html'] .= '<li><ul>';
			foreach($campaigndata['clicks'] as $link => $count){
				$return['html'] .= '<li class=""><a href="'.$link.'" class="external clicked-link">'.$link.'</a> ('.sprintf( _n( '%d click', '%d clicks', $count, 'mymail'), $count).')</li>';
			}
			$return['html'] .= '</ul></li>';
		}
		
		
		$return['html'] .= '</ul>';
		$return['html'] .= '</div>';
		echo json_encode($return);
		exit;
	}
	
	public function get_recipients_part($campaign_ID, $page = 0 ) {
	
		$return = '';
		
		$limit = 1000;
		$offset = ($page)*$limit;
		
		$lists = wp_get_post_terms($campaign_ID, 'newsletter_lists', array("fields" => "ids"));
		
		$term_taxonomy_ids = wp_list_pluck(get_terms( 'newsletter_lists', array('fields' => 'all', 'hide_empty' => false, 'include' => $lists)), 'term_taxonomy_id');
		
		global $wpdb;
		
		$query = "SELECT {$wpdb->posts}.ID, {$wpdb->posts}.post_name, {$wpdb->posts}.post_title, {$wpdb->posts}.post_status, {$wpdb->posts}.post_status FROM {$wpdb->posts} INNER JOIN {$wpdb->term_relationships} ON ({$wpdb->posts}.ID = {$wpdb->term_relationships}.object_id) WHERE ( {$wpdb->term_relationships}.term_taxonomy_id IN (".implode(',', $term_taxonomy_ids ).") ) AND {$wpdb->posts}.post_type = 'subscriber' AND ({$wpdb->posts}.post_status IN ('subscribed', 'unsubscribed', 'hardbounced', 'error')) GROUP BY {$wpdb->posts}.ID ORDER BY {$wpdb->posts}.post_modified ASC LIMIT $offset, $limit";

		$subscribers = $wpdb->get_results( $query );
		
		$count = 0;
				
		$subscribers_count = count($subscribers);
		
		for($i = 0; $i < $subscribers_count; $i++){
		
			$campaigndata = get_post_meta($subscribers[$i]->ID, 'mymail-campaigns', true);
			if(!isset($campaigndata[$campaign_ID])) continue;
			
			$return .= '<tr '.($i%2 ? ' class="alternate" ' : '').'>';
			$return .= '<td>'.($count+$offset+1).'</td><td width="60%"><a class="show-receiver-detail" data-id="' . $subscribers[$i]->ID . '">' . $subscribers[$i]->post_title . '</a></td>';
			$return .= '<td title="'.__('sent', 'mymail').'">' . (!empty($campaigndata[$campaign_ID]['sent']) ? str_replace(' ', '&nbsp;', date(get_option('date_format').' '.get_option('time_format'), $campaigndata[$campaign_ID]['timestamp'])) : '&ndash;') . '</td>';
			$return .= '<td>';
			$return .= (isset($campaigndata[$campaign_ID]['bounces']) ? '<span class="bounce-indicator" title="' . sprintf(_n('%d bounce', '%d bounces', $campaigndata[$campaign_ID]['bounces']), $campaigndata[$campaign_ID]['bounces']) . '">' . ($subscribers[$i]->post_status == 'hardbounced' ? 'B' :$campaigndata[$campaign_ID]['bounces'] ) . '</span>' : '');
			$return .= ($subscribers[$i]->post_status == 'error') ? '<span class="bounce-indicator" title="' .__('an error occurred while sending to this receiver', 'mymail'). '">E</span>' : '';
			$return .= '</td>';
			$return .= '<td>' . (isset($campaigndata[$campaign_ID]['open']) ? '<span title="'.__('has opened', 'mymail').'">&#10004;</span>' : '<span title="'.__('has not opened yet', 'mymail').'">&#10005;</span>') . '</td>';
			$return .= '<td>' . (isset($campaigndata[$campaign_ID]['totalclicks']) ? sprintf( _n( '%d click', '%d clicks', $campaigndata[$campaign_ID]['totalclicks'], 'mymail'), $campaigndata[$campaign_ID]['totalclicks']) : '&ndash;') . '</td>';
			$return .= '</tr>';
			$return .= '<tr id="receiver-detail-'. $subscribers[$i]->ID .'" class="receiver-detail'.($i%2 ? '  alternate' : '').'">';
			$return .= '<td></td><td colspan="5">';
			$return .= '<div class="receiver-detail-body"></div>';
			$return .= '</td>';
			$return .= '</tr>';
			$count++;

		}
		
		if($count && $limit == $subscribers_count) $return .= '<tr '.($i%2 ? ' class="alternate" ' : '').'><td colspan="6"><a class="load-more-receivers button aligncenter" data-page="'.($page+1).'">'. __('load more recipients from this campaign', 'mymail').'</a>'.'<span class="spinner"></span></td></tr>';
		
		return $return;
		
	}


	public function ajax_create_image() {
		$return['success'] = false;

		$this->ajax_nonce(json_encode($return));

		$id = intval($_POST['id']);
		$src = $_POST['src'];
		$width = intval($_POST['width']);
		$height = intval($_POST['height']);
		
		$return['success'] = !!($return['image'] = $this->create_image($id, $src, $width, $height, false));

		echo json_encode($return);
		exit;
	}

	public function ajax_image_placeholder(){
	
	
		$width = !empty($_REQUEST['w']) ? intval($_REQUEST['w']) : 600;
		$height = !empty($_REQUEST['h']) ? intval($_REQUEST['h']) : round($width/1.6);
		$tag = isset($_REQUEST['tag']) ? ''.$_REQUEST['tag'].'' : '';
		
		$text = '{'.$tag.'}';
		$font_size = max(11,round($width/strlen($text)));
		$font = MYMAIL_DIR.'/assets/font/OpenSans-Regular.ttf';
		
		$im = imagecreatetruecolor($width, $height);
		
		$bg = imagecolorallocate($im, 225, 225, 230);
		$font_color = imagecolorallocate($im, 25, 25, 30);
		imagefilledrectangle($im, 0, 0, $width, $height, $bg);
		
		$bbox = imagettfbbox($font_size, 0, $font, $text);
		
		$center_x = $bbox[0] + (imagesx($im) / 2) - ($bbox[4] / 2);
		$center_y = $bbox[1] + (imagesy($im) / 3) - ($bbox[5] / 3);
		
		
		imagettftext($im, $font_size, 0, $center_x, $center_y, $font_color, $font, $text);
		
		header( 'Expires: Thu, 31 Dec 2050 23:59:59 GMT' );
		header( 'Cache-Control: max-age=3600, must-revalidate' );
		header( 'Pragma: cache' );
		header( 'Content-Type: image/gif' );
		
		imagegif($im);
		
		imagedestroy($im);
		
	}


	public function ajax_get_post_list() {
		$return['success'] = false;

		$this->ajax_nonce(json_encode($return));

		if (in_array($_POST['type'], array( 'post', 'attachment' ))) {
		
			$post_type = $_POST['type'];
			
			$offset = intval($_POST['offset']);
			$post_count = mymail_option('post_count', 30);
			
			$defaults = array(
				'post_type' => $post_type,
				'numberposts' => $post_count,
				'suppress_filters' => true,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
				'post_status' => 'publish',
				'offset' => $offset,
				'orderby' => 'post_date',
				'order' => 'DESC',
			);
			
			if($post_type == 'post'){
				parse_str($_POST['posttypes']);
				
				$args = wp_parse_args(array(
					'post_type' => (!empty($post_types)) ? $post_types : '__',
					'post_status' => 'publish',
				), $defaults);
			}else{
			
				$args = wp_parse_args(array(
					'post_status' => 'inherit',
					'post_mime_type' => ($post_type == 'attachment') ? array('image/jpeg', 'image/gif', 'image/png', 'image/tiff', 'image/bmp') : null,
				), $defaults);
			}
			
			$return['success'] = true;
			$return['itemcount'] = isset($_POST['itemcount']) ? $_POST['itemcount'] : array();
			
			$posts = get_posts($args);
			$post_counts = count(get_posts(wp_parse_args(array(
					'numberposts' => -1,
					'offset' => 0,
			), $args)));
			
			$relativenames = array(
				-1 => __('last %s', 'mymail'),
				-2 => __('second last %s', 'mymail'),
				-3 => __('third last %s', 'mymail'),
				-4 => __('fourth last %s', 'mymail'),
				-5 => __('fifth last %s', 'mymail'),
				-6 => __('sixth last %s', 'mymail'),
				-7 => __('seventh last %s', 'mymail'),
				-8 => __('eighth last %s', 'mymail'),
				-9 => __('ninth last %s', 'mymail'),
				-10 => __('tenth last %s', 'mymail'),
				-11 => __('eleventh last %s', 'mymail'),
				-12 => __('twelfth last %s', 'mymail'),
			);
			
			$posts_lefts = max(0, $post_counts-$offset-$post_count);
			
			if ($post_counts) {
				$html = '';
				if ($_POST['type'] == 'post') {

					$pts = get_post_types( array( ), 'objects' );
					
					foreach ($posts as $post) {
						if(!isset($return['itemcount'][$post->post_type])) $return['itemcount'][$post->post_type] = 0;
						$relative = (--$return['itemcount'][$post->post_type]);
						$hasthumb = !!($thumbid = get_post_thumbnail_id($post->ID));
						$html .= '<li data-id="' . $post->ID . '" data-name="' . $post->post_title . '"';
						if ($hasthumb)
							$html .= ' data-thumbid="' . $thumbid . '" class="has-thumb"';
						$html .= ' data-link="' . get_permalink($post->ID) . '" data-type="' .$post->post_type. '" data-relative="' .$relative. '">';
						($hasthumb)
							? $html .= get_the_post_thumbnail($post->ID, array(48, 48))
							: $html .= '<div class="no-feature"></div>';
						$html .= '<span class="post-type">' . $pts[$post->post_type]->labels->singular_name . '</span>';
						$html .= '<strong>' . $post->post_title . '</strong>';
						$html .= '<span>' . trim(wp_trim_words(strip_shortcodes($post->post_content), 15)) . '</span>';
						$html .= '<span>' . date_i18n(get_option('date_format'), strtotime($post->post_date)) . '</span>';
						$html .= '</li>';
					}
				} else if ($_POST['type'] == 'attachment') {
					foreach ($posts as $post) {
						$image = wp_get_attachment_image_src($post->ID, 'large');
						$html .= '<li data-id="' . $post->ID . '" data-name="' . $post->post_title . '" data-src="' . $image[0] . '" data-asp="' . ($image[2] ? $image[1]/$image[2] : '') . '">';
						$image = wp_get_attachment_image_src($post->ID, 'medium');
						$html .= '<a style="background-image:url(' . $image[0] . ')"><span class="caption" title="' . $post->post_title . '">' . $post->post_title . '</span></a>';
						$html .= '</li>';
					}
				}
				if($posts_lefts) 
				$html .= '<li><a class="load-more-posts" data-offset="'.($offset+$post_count).'" data-type="'.$_POST['type'].'">'.sprintf(__('load more entries (%d left)', 'mymail'), $posts_lefts).'</a></li>';
				
				$return['html'] = $html;
			} else {
				$return['html'] = '<li><span class="norows">'. __('no entries found', 'mymail') .'</span></li>';
			}

		}else if($_POST['type'] == 'link'){
			
			$post_type = $_POST['type'];
			
			$args = array();

			$offset = intval($_POST['offset']);
			$post_count = mymail_option('post_count', 30);
			
			
			$post_counts = $this->link_query( '', true );
			
			$posts_lefts = max(0, $post_counts-$offset-$post_count);
			
			$results = $this->link_query( array(
				'offset' => $offset,
				'posts_per_page' => $post_count,
				'post_status' => array('publish', 'finished', 'queued', 'paused'),
			) );
			
			$return['success'] = true;
			
			if ( isset( $results ) ){
				$html = '';
				foreach($results as $entry){
					$hasthumb = !!($thumbid = get_post_thumbnail_id($entry['ID']));
					$html .= '<li data-id="' . $entry['ID'] . '" data-name="' . $entry['title'] . '"';
					if ($hasthumb)
						$html .= ' data-thumbid="' . $thumbid . '" class="has-thumb"';
					$html .= ' data-link="' . $entry['permalink'] . '">';
					($hasthumb)
						? $html .= get_the_post_thumbnail($entry['ID'], array(48, 48))
						: $html .= '<div class="no-feature"></div>';
					$html .= '<strong>' . $entry['title'] . '</strong>';
					$html .= '<span>' . $entry['permalink'] . '</span>';
					$html .= '<span>' . $entry['info'] . '</span>';
					$html .= '</li>';
				}
				if($posts_lefts) 
				$html .= '<li><a class="load-more-posts" data-offset="'.($offset+$post_count).'" data-type="'.$post_type.'">'.sprintf(__('load more entries (%d left)', 'mymail'), $posts_lefts).'</a></li>';
				
				$return['html'] = $html;

			}else{
				$return['html'] = '<li><span class="norows">'. __('no entries found', 'mymail') .'</span></li>';
			}
							
		}
		
		echo json_encode($return);
		exit;
	}



	public function ajax_get_post() {
		$return['success'] = false;

		$this->ajax_nonce(json_encode($return));

		$post = get_post(intval($_POST['id']));

		if ($post) {
			$return['success'] = true;
			$return['title'] = $post->post_title;
			$return['link'] = get_permalink($post->ID);
			$return['content'] = strip_shortcodes(wpautop($post->post_content, false));
			$return['excerpt'] = strip_shortcodes(wpautop($post->post_excerpt ? $post->post_excerpt : substr($post->post_content, 0, strpos($post->post_content, '<!--more-->')), false));

			if (has_post_thumbnail($post->ID))
				$return['image'] = array(
					'id' => get_post_thumbnail_id($post->ID),
					'name' => $post->post_title
				);

		}

		echo json_encode($return);
		exit;
	}
	
	public function ajax_check_for_posts() {
		$return['success'] = false;

		$this->ajax_nonce(json_encode($return));
		
		$return['success'] = true;
		
		$post_type = $_POST['post_type'];
		$offset = intval($_POST['relative'])+1;
		$term_ids = isset($_POST['extra']) ? (array) $_POST['extra'] : false;
		
		$args = array(
			'numberposts' => 1,
			'post_type' => $post_type,
			'offset' => $offset,
		);
		
		if(is_array($term_ids)){
			
			$tax_query = array();
			
			$taxonomies = get_object_taxonomies( $post_type, 'names' );
			for($i = 0; $i < count($term_ids); $i++){
				if(empty($term_ids[$i])) continue;
				$tax_query[] = array(
					'taxonomy' => $taxonomies[$i],
					'field' => 'id',
					'terms' => explode(',', $term_ids[$i]),
				);
			}
			
			if(!empty($tax_query)){
				$tax_query['relation'] = 'AND';
				$args = wp_parse_args( $args, array('tax_query' => $tax_query));
			}
		}
		
		$post = get_posts( $args );
		$return['title'] = ($return['match'] = !!$post) ? sprintf(__('Current match: %s', 'mymail'), '<a href="post.php?post='.$post[0]->ID.'&action=edit" class="external">'.$post[0]->post_title.'</a>') : __('Currently no match for your selection!', 'mymail').' <a href="post-new.php?post_type='.$post_type.'" class="external">'.__('create a new one', 'mymail').'</a>?';
		
		echo json_encode($return);
		exit;
	}
	
	
	public function ajax_get_post_term_dropdown() {
		$return['success'] = false;

		$this->ajax_nonce(json_encode($return));
		
		$post_type = $_POST['posttype'];
		$labels = isset($_POST['labels']) ? ($_POST['labels'] == 'true') : false;
		$names = isset($_POST['names']) ? $_POST['names'] : false;
		
		
		$return['html'] = '<div class="advanced_embed_options_taxonomies">'.$this->get_post_term_dropdown($post_type, $labels, $names).'</div>';
		$return['success'] = true;

		echo json_encode($return);
		exit;
	}


	public function get_post_term_dropdown($post_type = 'post', $labels = true, $names = false, $values = array()) {
	
		$taxonomies = get_object_taxonomies( $post_type, 'objects' );
		
		$html = '';
		
		$taxwraps = array();
		
		foreach($taxonomies as $id => $taxonomy){
			$tax = '<div>'.($labels ? '<label class="advanced_embed_options_taxonomy_label">'.$taxonomy->labels->name.': </label>' : '').'<span class="advanced_embed_options_taxonomy_wrap">';
			$cats = get_categories( array('hide_empty' => false, 'taxonomy' => $id, 'type' => $post_type, 'orderby' => 'id') );
			
			if(!isset($values[$id])) $values[$id] = array('-1');
			
			$selects = array();
			
			foreach($values[$id] as $term){
				$select = '<select class="advanced_embed_options_taxonomy check-for-posts" '.($names ? 'name="mymail_data[autoresponder][terms]['.$id.'][]"': '').'>';
				$select .= '<option value="-1">'.sprintf(__('any %s', 'mymail'), $taxonomy->labels->singular_name).'</option>';
				foreach($cats as $cat){
					$select .= '<option value="'.$cat->term_id.'" '.selected($cat->term_id, $term, false).'>'.$cat->name.'</option>';
				}
				$select .= '</select>';
				$selects[] = $select;
			}
			
			$tax .= implode(' '.__('or', 'mymail').' ', $selects);
			
			$tax .= '</span></div>';
			
			$taxwraps[] = $tax;
		}
		
		$html = (!empty($taxwraps)) ? implode(($labels ? '<label class="advanced_embed_options_taxonomy_label">&nbsp;</label>' : '').'<span>' .__('and', 'mymail') . '</span>',$taxwraps) : '';
		
		return $html;
	
	}


	public function ajax_forward_message() {
		$return['success'] = false;

		parse_str($_POST['data'], $data);

		if (!wp_verify_nonce($data['_wpnonce'], $data['url'])) {
			die(json_encode($return));
		}

		if (empty($data['message']) || !mymail_is_email($data['receiver']) || !mymail_is_email($data['sender']) || empty($data['sendername'])) {

			$return['msg'] = __('Please fill out all fields correctly!', 'mymail');
			die(json_encode($return));

		}

		require_once MYMAIL_DIR . '/classes/mail.class.php';

		$mail = mymail_mail::get_instance();
		$mail->to = esc_attr($data['receiver']);
		$mail->subject = esc_attr('[' . get_bloginfo('name') . '] ' . sprintf(__('%s is forwarding an email to you!', 'mymail'), $data['sendername']));
		$mail->from = esc_attr($data['sender']);
		$mail->from_name = $data['sendername'] . ' via ' . get_bloginfo('name');

		$return['success'] = $mail->send_notification(nl2br($data['message']) . "<br><br>" . $data['url'], '', array('notification' => sprintf(__('%1$s is forwarding this mail to you via %2$s', 'mymail'), $data['sendername'].' (<a href="mailto:'.esc_attr($data['sender']).'">'.esc_attr($data['sender']).'</a>)', '<a href="'.get_bloginfo('home').'">'.get_bloginfo('name').'</a>' )));

		$return['msg'] = ($return['success']) ? __('Your message was sent succefully!', 'mymail') : __('Sorry, we couldn\'t deliver your message. Please try again later!', 'mymail');

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


	private function ajax_filesystem() {
		if('ftpext' == get_filesystem_method() && !defined('FTP_HOST') && !defined('FTP_USER') && !defined('FTP_PASS')){
			$return['msg'] = __('WordPress is not able to access to your filesystem!', 'mymail');
			$return['msg'] .= "\n".sprintf(__('Please add following lines to the wp-config.php %s', 'mymail'), "\n\ndefine('FTP_HOST', 'your-ftp-host');\ndefine('FTP_USER', 'your-ftp-user');\ndefine('FTP_PASS', 'your-ftp-password');\n");
			$return['success'] = false;
			echo json_encode( $return );
			exit;
		}
		
	}
	
	public function add_ajax_nonce() {
		wp_nonce_field('mymail_nonce', 'mymail_nonce', false);
	}


	public function create_image($attach_id = NULL, $img_url = NULL, $width, $height = NULL, $crop = false) {

		if ($attach_id) {
			$image_src = wp_get_attachment_image_src($attach_id, 'full');
			$actual_file_path = get_attached_file($attach_id);
			
			if (!$width && !$height) {
				$orig_size = getimagesize($actual_file_path);
				$width = $orig_size[0];
				$height = $orig_size[1];
			}

		} else if ($img_url) {
		
				$file_path = parse_url($img_url);
				$actual_file_path = $_SERVER['DOCUMENT_ROOT'] . $file_path['path'];
				
				/* todo: reconize URLs */
				if(!file_exists($actual_file_path)){
					$vt_image = array(
						'url' => $img_url,
						'width' => $width,
						'height' => NULL
					);
	
					return $vt_image;
				}
				
				
				$actual_file_path = ltrim($file_path['path'], '/');
				$actual_file_path = rtrim(ABSPATH, '/') . $file_path['path'];
				if(file_exists($actual_file_path)){
					$orig_size = getimagesize($actual_file_path);
				}else{
					$actual_file_path = ABSPATH.str_replace(site_url('/'), '', $img_url);
					$orig_size = getimagesize($actual_file_path);
				}

				$image_src[0] = $img_url;
				$image_src[1] = $orig_size[0];
				$image_src[2] = $orig_size[1];


		}

		if (!$height) $height = round($width /($image_src[1]/$image_src[2]));

		$file_info = pathinfo($actual_file_path);
		$extension = '.' . $file_info['extension'];

		$no_ext_path = $file_info['dirname'] . '/' . $file_info['filename'];
		
		$cropped_img_path = $no_ext_path . '-' . $width . 'x' . $height . $extension;

		if ($image_src[1] > $width || $image_src[2] > $height) {

			if (file_exists($cropped_img_path)) {
				$cropped_img_url = str_replace(basename($image_src[0]), basename($cropped_img_path), $image_src[0]);
				
				$vt_image = array(
					'url' => $cropped_img_url,
					'width' => $width,
					'height' => $height
				);

				return $vt_image;
			}

			if ($crop == false) {

				$proportional_size = wp_constrain_dimensions($image_src[1], $image_src[2], $width, $height);
				$resized_img_path = $no_ext_path . '-' . $proportional_size[0] . 'x' . $proportional_size[1] . $extension;

				if (file_exists($resized_img_path)) {
					$resized_img_url = str_replace(basename($image_src[0]), basename($resized_img_path), $image_src[0]);

					$vt_image = array(
						'url' => $resized_img_url,
						'width' => $proportional_size[0],
						'height' => $proportional_size[1]
					);

					return $vt_image;
				}
			}

			$new_img_path = image_resize($actual_file_path, $width, $height, $crop);
			$new_img_size = getimagesize($new_img_path);
			$new_img = str_replace(basename($image_src[0]), basename($new_img_path), $image_src[0]);

			$vt_image = array(
				'url' => $new_img,
				'width' => $new_img_size[0],
				'height' => $new_img_size[1]
			);

			return $vt_image;
		}

		$vt_image = array(
			'url' => $image_src[0],
			'width' => $image_src[1],
			'height' => $image_src[2]
		);

		return $vt_image;

	}


	/*----------------------------------------------------------------------*/
	/* Filters
	/*----------------------------------------------------------------------*/

	private function sanitize_content($content, $simple = false) {
		if (empty($content))
			return '';

		$content = stripslashes($content);
		if($simple) return $content;
		$content = preg_replace('#<div ?[^>]+?class=\"modulebuttons(.*)<\/div>#i', '', $content);
		$content = preg_replace('#<script[^>]*?>.*?</script>#si', '', $content);
		$content = str_replace('mymail-highlight', '', $content);
		$content = strip_tags($content, '<address><a><big><blockquote><body><br><b><center><cite><code><dd><dfn><div><dl><dt><em><font><h1><h2><h3><h4><h5><h6><head><hr><html><img><i><kbd><li><meta><ol><pre><p><span><small><strike><strong><style><sub><sup><table><tbody><thead><tfoot><td><th><title><tr><tt><ul><u>');
		$content = str_replace(' !DOCTYPE', '!DOCTYPE', $content);
		$content = str_replace('< html PUBLIC', '<!DOCTYPE html PUBLIC', $content);
		//$content = preg_replace('/(?:(?:\r\n|\r|\n)\s*){2,}/s', "\n", $content);
		$content = preg_replace('/(\r|\n|\r\n){2,}/', "\n", $content);

		
		if (stripos($content, '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd') === true)
			return $content;
		$content = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width" />
	<title>{subject}</title>
</head>
<body>
' . trim(preg_replace(array('#<(meta|link)[^>]*?>#siu', '#<title>[^<]*?</title>#'), '', $content)) . '
</body></html>';
		
		return $content;
	}


	private function plain_text($html) {

		preg_match('#<body[^>]*>.*?<\/body>#is', $html, $matches);
		
		if(!empty($matches)){
			$html = $matches[0];
		}

		$text = preg_replace('# +#',' ',$html);
		$text = str_replace(array("\n","\r","\t"),'',$text);
		//$piclinks = "#< *a[^>]*> *< *img[^>]*> *< *\/ *a *>#isU";
		$piclinks = '/< *a[^>]*href *= *"([^#][^"]*)"[^>]*> *< *img[^>]*> *< *\/ *a *>/Uis';
		$style = "#< *style(?:(?!< */ *style *>).)*< */ *style *>#isU";
		$strikeTags =  '#< *strike(?:(?!< */ *strike *>).)*< */ *strike *>#iU';
		$headlines = '#< *(h1|h2)[^>]*>#Ui';
		$stars = '#< *li[^>]*>#Ui';
		$return1 = '#< */ *(li|td|tr|div|p)[^>]*> *< *(li|td|tr|div|p)[^>]*>#Ui';
		$return2 = '#< */? *(br|p|h1|h2|legend|h3|li|ul|h4|h5|h6|tr|td|div)[^>]*>#Ui';
		$links = '/< *a[^>]*href *= *"([^#][^"]*)"[^>]*>(.*)< *\/ *a *>/Uis';
		$text = preg_replace(array($piclinks,$style,$strikeTags,$headlines,$stars,$return1,$return2,$links),array('${1}'."\n",'','',"\n\n","\n● ","\n","\n",'${2} ( ${1} )'),$text);
		$text = str_replace(array(" ","&nbsp;"),' ',strip_tags($text));
		$text = trim(@html_entity_decode($text, ENT_QUOTES, 'UTF-8' ));
		$text = preg_replace('# +#',' ',$text);
		$text = preg_replace('#\n *\n\s+#',"\n\n",$text);
		
		return $text;

	}


	public function bulk_actions($actions) {
		unset($actions['edit']);
		return $actions;
	}


	public function remove_revisions($post_id) {

		if (!$post_id)
			return false;

		global $wpdb;

		$wpdb->query($wpdb->prepare("DELETE a,b,c FROM $wpdb->posts a LEFT JOIN $wpdb->term_relationships b ON (a.ID = b.object_id) LEFT JOIN $wpdb->postmeta c ON (a.ID = c.post_id) WHERE a.post_type = '%s' AND a.post_parent = %d", 'revision', $post_id));
	}


	public function updated_messages($messages) {
		global $post_id;
		global $post;

		if ($post->post_type != 'newsletter') return $messages;

		$messages[] = 'No subject!';

		$messages['newsletter'] = array(
			0 => '',
			1 => sprintf(__('Campaign updated. %s', 'mymail'), '<a href="' . esc_url(get_permalink($post_id)) . '">' . __('View Newsletter', 'mymail') . '</a>'),
			2 => sprintf(__('Template changed. %1$s', 'mymail'), '<a href="' . remove_query_arg('message', wp_get_referer()) . '">' . __('Go back', 'mymail') . '</a>'),
			3 => __('Template saved', 'mymail'),
			4 => __('Campaign updated.', 'mymail'),
			5 => isset($_GET['revision']) ? sprintf(__('Campaign restored to revision from %s', 'mymail'), wp_post_revision_title((int) $_GET['revision'], false)) : false,
			6 => sprintf(__('Campaign published. %s', 'mymail'), '<a href="' . esc_url(get_permalink($post_id)) . '">' . __('View Newsletter', 'mymail') . '</a>'),
			7 => __('Campaign saved.', 'mymail'),
			8 => sprintf(__('Campaign submitted. %s', 'mymail'), '<a target="_blank" href="' . esc_url(add_query_arg('preview', 'true', get_permalink($post_id))) . '">' . __('Preview Newsletter') . '</a>'),
			9 => __('Campaign scheduled.', 'mymail'),
			10 => __('Campaign draft updated.', 'mymail')
		);

		return $messages;
	}


	public function get_post_where($sql) {
		return str_replace("post_status = 'publish'", "post_status IN ('finished', 'active')", $sql);
	}


	public function shortcode_empty_paragraph_fix($content){
	
		$array = array (
			'<p>[' => '[', 
			']</p>' => ']', 
			']<br />' => ']'
		);
	
		$content = strtr($content, $array);
	
		return $content;
	}
	
	public function title($title) {
		return __('Enter Campaign Title here', 'mymail');
	}


	public function columns($columns) {
		
		global $post;
		$columns = array(
			"cb" => "<input type=\"checkbox\" />",
			"title" => __("Name"),
			"status" => __("Status"),
			"total" => __("Total", 'mymail'),
			"open" => __("Open", 'mymail'),
			"click" => __("Clicks", 'mymail'),
			"unsubs" => __("Unsubscribes", 'mymail'),
			"bounces" => __("Bounces", 'mymail'),
			"date" => __("Date")
		);
		return $columns;
	}

	
	public function columns_sortable($columns) {
		
		$columns['status'] = 'status';
		
		return $columns;
		
	}
	
	public function columns_sortable_helper($query) {
		
		global $pagenow;
		$qv = $query->query_vars;
		
		if($qv['post_type'] == 'newsletter' && isset($qv['orderby'])){
		
		
			
			switch($qv['orderby']){
				
				case 'status':
					add_filter( 'posts_orderby', array( &$this, 'columns_orderby_status'));
					break;
					
			}
		}
		
	}
	
	public function columns_orderby_status($orderby) {
		
		return str_replace('posts.post_date', 'posts.post_status', $orderby);
		
	}

	public function columns_content($column) {
		global $post;
		global $wpdb;

		if($post->post_status == 'autoresponder') return $column;
		
		$campaign = get_post_meta($post->ID, 'mymail-campaign', true);

		switch ($column) {

		case "status":
		
			$data = get_post_meta($post->ID, 'mymail-data', true);
			
			$totalcount = (!in_array($post->post_status, array('finished')))
				? $this->get_totals_by_id($post->ID)+((isset($campaign['unsubscribes'])) ? $campaign['unsubscribes'] : 0)
				: $campaign['total'];
				
				
			$timestamp = isset($data['timestamp']) ? $data['timestamp'] : current_time('timestamp');
			if (!in_array($post->post_status, array('pending', 'auto-draft'))) {
				switch ($post->post_status) {
				case 'paused':
					global $wp_post_statuses;
					echo '<span class="mymail-icon paused"></span> ';
					echo (!$campaign || !$campaign['sent']) ? $wp_post_statuses['paused']->label : __('Paused', 'mymail');
					if ($totalcount) {
						if($campaign['sent'])
							echo "<br><div class='campaign-progress'><span class='bar' style='width:" . round($campaign['sent'] / $totalcount * 100) . "%'></span><span>&nbsp;" . sprintf(__('%1$s of %2$s sent', 'mymail'), number_format_i18n($campaign['sent']), number_format_i18n($totalcount))."</span></div>";
					} else {
						echo '<br><span class="mymail-icon no-receiver"></span> ' . __('no recivers!', 'mymail');
					}
					break;
				case 'active':
					if ($totalcount) {
						echo '<span class="mymail-icon progressing"></span> ' . ($campaign['sent'] == $totalcount ? __('completing job', 'mymail') :  __('progressing', 'mymail') ). '&hellip;';
						echo "<br><div class='campaign-progress'><span class='bar' style='width:" . round($campaign['sent'] / $totalcount * 100) . "%'></span><span>&nbsp;" . sprintf(__('%1$s of %2$s sent', 'mymail'), number_format_i18n($campaign['sent']), number_format_i18n($totalcount))."</span></div>";
					} else {
						echo '<span class="mymail-icon no-receiver"></span> ' . __('no recivers!', 'mymail');
					}
					break;
				case 'queued':
					echo '<span class="mymail-icon queued"></span> ';
					echo sprintf(__('starts in %s', 'mymail'), ($timestamp-current_time('timestamp') > 60) ? human_time_diff(current_time('timestamp'), $timestamp) : __('less than a minute', 'mymail'));
					echo "<br><span class='nonessential' style='padding:0;'>(" . date(get_option('date_format') . ' ' . get_option('time_format'), $timestamp) . ')</span>';
					break;
				case 'finished':
					echo '<span class="mymail-icon finished"></span> ' . __('Finished', 'mymail');
					echo "<br><span class='nonessential' style='padding:0;'>(" . date(get_option('date_format') . ' ' . get_option('time_format'), $campaign['timestamp']) . ')</span>';
					break;
				case 'draft':
					global $wp_post_statuses;
					echo '<span class="mymail-icon draft"></span> ' . $wp_post_statuses['draft']->label;
					break;
				case 'trash':
					global $wp_post_statuses;
					echo $wp_post_statuses['trash']->label;
					break;
				}
			} else {
				$status = get_post_status_object($post->post_status);
				echo $status->label;
			}
			if (current_user_can('publish_newsletters')) {
				echo '<div class="row-actions">';
				$actions = array();
				if ($post->post_status == 'queued') {
							$actions['start'] = '<a class="start" href="?post_type=newsletter&start=' . $post->ID . (isset($_REQUEST['post_status']) ? '&post_status='.$_REQUEST['post_status'] : '' ) . '&_wpnonce=' . wp_create_nonce('mymail_nonce') . '" title="' . __('Start Campaign now', 'mymail') . '">' . __('Start now', 'mymail') . '</a>&nbsp;';
				}
				if (in_array($post->post_status, array('active', 'queued'))) {
					$actions['pause'] = '<a class="pause" href="?post_type=newsletter&pause=' . $post->ID . (isset($_REQUEST['post_status']) ? '&post_status='.$_REQUEST['post_status'] : '' ) . '&_wpnonce=' . wp_create_nonce('mymail_nonce') . '" title="' . __('Pause Campaign', 'mymail') . '">' . __('Pause', 'mymail') . '</a>&nbsp;';
				} else if ($post->post_status == 'paused' && $totalcount) {
						if (!empty($data['timestamp'])) {
							$actions['start'] = '<a class="start" href="?post_type=newsletter&start=' . $post->ID . (isset($_REQUEST['post_status']) ? '&post_status='.$_REQUEST['post_status'] : '' ) . '&_wpnonce=' . wp_create_nonce('mymail_nonce') . '" title="' . __('Resume Campaign', 'mymail') . '">' . __('Resume', 'mymail') . '</a>&nbsp;';
						} else {
							$actions['start'] = '<a class="start" href="?post_type=newsletter&start=' . $post->ID . (isset($_REQUEST['post_status']) ? '&post_status='.$_REQUEST['post_status'] : '' ) . '&_wpnonce=' . wp_create_nonce('mymail_nonce') . '" title="' . __('Start Campaign', 'mymail') . '">' . __('Start', 'mymail') . '</a>&nbsp;';
						}
				}
				echo implode(' | ', $actions);
				echo '</div>';
			}
			break;

		case "total":
			echo number_format_i18n(in_array($post->post_status, array('finished')) ? $campaign['total'] : $this->get_totals_by_id($post->ID)+(!empty($campaign) ? $campaign['unsubscribes']+$campaign['hardbounces'] : 0));
			
			if(!empty($campaign['totalerrors'])) echo '&nbsp;(<a href="edit.php?post_status=error&post_type=subscriber" class="errors" title="'.sprintf(__('%d emails have not been sent', 'mymail'), $campaign['totalerrors']).'">+'.$campaign['totalerrors'].'</a>)';
			break;

		case "open":
			if (in_array($post->post_status, array('finished', 'active', 'paused'))) {
				$opens = $campaign['opens'];
				echo number_format_i18n($opens) . '/<span class="tiny">' . number_format_i18n($campaign['sent']) . '</span>';
				echo "<br><span title='" . __('open rate', 'mymail') . "' class='nonessential' style='padding:0;'>";
				echo ($opens) ? ' (' . (round($opens / $campaign['sent'] * 100, 2)) . '%)' : ' (0%)';
				echo "</span>";
			} else {
				echo '&ndash;';
			}
			break;

		case "click":
			if (in_array($post->post_status, array('finished', 'active', 'paused'))) {
				$clicks = $campaign['totaluniqueclicks'];
				echo number_format_i18n($clicks);
				echo "<br><span title='" . __('click rate', 'mymail') . "' class='nonessential' style='padding:0;'>";
				echo ($clicks) ? ' (' . (round($campaign['totaluniqueclicks'] / $campaign['opens'] * 100, 2)) . '%)' : ' (0%)';
				echo "</span>";
			}else {
				echo '&ndash;';
			}
			break;

		case "unsubs":
			if (in_array($post->post_status, array('finished', 'active', 'paused'))) {
				echo number_format_i18n($campaign['unsubscribes']);
				echo "<br><span title='" . __('unsubscribe rate', 'mymail') . "' class='nonessential' style='padding:0;'>";
				echo ($campaign['unsubscribes']) ? ' (' . (round($campaign['unsubscribes'] / $campaign['opens'] * 100, 2)) . '%)' : ' (0%)';
				echo "</span>";
			}else {
				echo '&ndash;';
			}
			break;

		case "bounces":
			if (in_array($post->post_status, array('finished', 'active', 'paused'))) {
				echo number_format_i18n($campaign['hardbounces']);
				echo "<br><span title='" . __('bounce rate', 'mymail') . "' class='nonessential' style='padding:0;'>";
				echo ($campaign['hardbounces']) ? ' (' . (round($campaign['hardbounces'] / ($campaign['sent']+$campaign['hardbounces']) * 100, 2)) . '%)' : ' (0%)';
				echo "</span>";
			}else {
				echo '&ndash;';
			}
			break;

		}
	}


	public function list_columns($columns) {
		$columns['posts'] = __('Subscribers', 'mymail');
		return $columns;
	}


	public function quick_edit_btns($actions, $post) {
		if ($post->post_type != 'newsletter')
			return $actions;

		unset($actions['inline hide-if-no-js']);
		
		if ($post->post_status != 'trash' && current_user_can('duplicate_newsletters') && current_user_can('duplicate_others_newsletters', $post->ID))
			$actions['duplicate'] = '<a class="duplicate" href="?post_type=newsletter&duplicate=' . $post->ID . (isset($_REQUEST['post_status']) ? '&post_status='.$_REQUEST['post_status'] : '' ) . '&_wpnonce=' . wp_create_nonce('mymail_nonce') . '" title="' . sprintf( __('Duplicate Campaign %s', 'mymail'), '“'.$post->post_title.'”' ) . '">' . __('Duplicate', 'mymail') . '</a>';
			
		return $actions;
	}


	public function add_action_link( $links, $file ) {
		if ( $file == MYMAIL_SLUG ) {
			$settings_link = '<a href="options-general.php?page=newsletter-settings">' . __('Settings', 'mymail') . '</a>';
			array_unshift( $links, $settings_link );
		}
		return $links;
	}
	
	/*
 * Add some links on the plugin page
 */
	public function add_plugin_links($links, $file) {
		if ( $file == MYMAIL_SLUG ) {
			$links[] = '<a href="http://rxa.li/mymailtemplates" target="_blank">'.__('Templates', 'mymail').'</a>';
		}
		return $links;
	}



	public function revision_field_post_content($content, $field) {
		global $post;

		if ($post->post_type != 'newsletter')
			return $content;

		$data = get_post_meta($post->ID, 'mymail-data', true);
		$ids = (isset($_REQUEST['revision'])) ? array(
			(int) $_REQUEST['revision']
		) : array(
			(int) $_REQUEST['left'],
			(int) $_REQUEST['right']
		);

		global $mymail_revisionnow;

?>
		<tr id="revision-field-<?php echo $field; ?>-preview">
			<th scope="row">
			<?php

		if (!$mymail_revisionnow && isset($_REQUEST['left'])) {
			printf(__('Older: %s'), wp_post_revision_title(get_post($_REQUEST['left'])));
		} else if ($mymail_revisionnow && isset($_REQUEST['right'])) {
				printf(__('Newer: %s'), wp_post_revision_title(get_post($_REQUEST['left'])));
			} else {
			_e('Preview');
		}
		$mymail_revisionnow = (!$mymail_revisionnow) ? $ids[0] : $ids[1];

?>
			</th>
			<td><iframe id="mymail_iframe" src="<?php echo admin_url('admin-ajax.php?action=mymail_get_template&id=' . $post->ID . '&revision=' . $mymail_revisionnow . '&template=&_wpnonce=' . wp_create_nonce('mymail_nonce') . '&editorstyle=0&nocache=' . time()); ?>" width="100%" height="640" scrolling="auto" frameborder="0"></iframe></td>
		</tr>
		<?php

		return $this->sanitize_content($content);
	}

	

	/*----------------------------------------------------------------------*/
	/* Dashboard Widget
	/*----------------------------------------------------------------------*/

	public function dashboard_widget() {
		// Display whatever it is you want to show
		include MYMAIL_DIR . '/views/dashboard.php';
	}


	public function add_dashboard_widgets() {
		wp_add_dashboard_widget('dashboard_mymail', __('Newsletter', 'mymail'), array( &$this, 'dashboard_widget'));

		add_action('admin_enqueue_scripts', array( &$this, 'dashboard_widget_styles'), 10, 1);

		//reposition the dashboard widget
		global $wp_meta_boxes;

		$row = 0;
		$col = 'side';

		$dashboard['normal'] = $wp_meta_boxes['dashboard']['normal']['core'];
		$dashboard['side'] = $wp_meta_boxes['dashboard']['side']['core'];

		$widget = array('dashboard_mymail' => array_pop($dashboard['normal']));

		$sorted_dashboard = array_splice($dashboard[$col], 0, $row, true) + $widget + array_splice($dashboard[$col], $row, 999, true);

		$wp_meta_boxes['dashboard']['normal']['core'] = $dashboard['normal'];
		$wp_meta_boxes['dashboard'][$col]['core'] = $sorted_dashboard;
	}


	/*----------------------------------------------------------------------*/
	/* Custom Post Type
	/*----------------------------------------------------------------------*/


	public function register_post_type() {
		register_post_type('newsletter', array(

				'labels' => array(
					'name' => __('Campaigns', 'mymail'),
					'singular_name' => __('Campaign', 'mymail'),
					'add_new' => __('New Campaign', 'mymail'),
					'add_new_item' => __('Create a new Campaign', 'mymail'),
					'edit_item' => __('Edit Campaign', 'mymail'),
					'new_item' => __('New Campaign', 'mymail'),
					'all_items' => __('All Campaigns', 'mymail'),
					'view_item' => __('View Newsletter', 'mymail'),
					'search_items' => __('Search Campaigns', 'mymail'),
					'not_found' => __('No Campaign found', 'mymail'),
					'not_found_in_trash' => __('No Campaign found in Trash', 'mymail'),
					'parent_item_colon' => '',
					'menu_name' => __('Newsletter', 'mymail')
				),

				'public' => true,
				'can_export' => true,
				//no need => retina support
				//'menu_icon' => MYMAIL_URI . '/assets/img/icons/mails.png',
				'show_ui' => true,
				'show_in_nav_menus' => false,
				'show_in_menu' => true,
				'show_in_admin_bar' => true,
				'exclude_from_search' => true,
				'capability_type' => 'newsletter',
				'map_meta_cap' => true,
				//'menu_position' => 30,
				'has_archive' => false,
				'hierarchical' => false,
				'rewrite' => true,
				'rewrite' => array( 'with_front' => false ),
				'supports' => array(
					'title',
					'revisions'
				),
				'register_meta_box_cb' => array( &$this, 'add_meta_boxes')
				// 'taxonomies' => array( 'newsletter_lists' )

			));

	}



	public function register_post_status() {
		register_post_status('paused', array(
				'label' => __('Paused', 'mymail'),
				'public' => true,
				'label_count' => _n_noop(__('Paused', 'mymail') . ' <span class="count">(%s)</span>', __('Paused', 'mymail') . ' <span class="count">(%s)</span>')
			));

		register_post_status('active', array(
				'label' => __('Active', 'mymail'),
				'public' => true,
				'label_count' => _n_noop(__('Active', 'mymail') . ' <span class="count">(%s)</span>', __('Active', 'mymail') . ' <span class="count">(%s)</span>')
			));

		register_post_status('queued', array(
				'label' => __('Queued', 'mymail'),
				'public' => true,
				'label_count' => _n_noop(__('Queued', 'mymail') . ' <span class="count">(%s)</span>', __('Queued', 'mymail') . ' <span class="count">(%s)</span>')
			));

		register_post_status('finished', array(
				'label' => __('Finished', 'mymail'),
				'public' => true,
				'label_count' => _n_noop(__('Finished', 'mymail') . ' <span class="count">(%s)</span>', __('Finished', 'mymail') . ' <span class="count">(%s)</span>')
			));

	}



	/*----------------------------------------------------------------------*/
	/* Meta Boxes
	/*----------------------------------------------------------------------*/


	public function add_meta_boxes() {
		global $post;
		add_meta_box('mymail_details', __('Details', 'mymail'), array( &$this, 'newsletter_details'), 'newsletter', 'normal', 'high');
		add_meta_box('mymail_template', !in_array($post->post_status, array('active', 'finished')) ? __('Template', 'mymail') : __('Clickmap', 'mymail'), array( &$this, 'newsletter_template'), 'newsletter', 'normal', 'high');
		add_meta_box('mymail_submitdiv', __('Save', 'mymail'), array( &$this, 'newsletter_submit'), 'newsletter', 'side', 'high');
		add_meta_box('mymail_delivery', __('Delivery', 'mymail'), array( &$this, 'newsletter_delivery'), 'newsletter', 'side', 'high');
		add_meta_box('mymail_lists', __('Lists', 'mymail'), array( &$this, 'newsletter_lists'), 'newsletter', 'side', 'low');
		add_meta_box('mymail_options', __('Options', 'mymail'), array( &$this, 'newsletter_options'), 'newsletter', 'side', 'low');
		
		/*
		global $wp_meta_boxes;
		foreach($wp_meta_boxes['post'] as $context => $priorities){
			foreach($priorities as $priority => $metaboxes){
				foreach($metaboxes as $id => $data){
					remove_meta_box($id, 'newsletter', 'normal');
				}
			}
		}
		*/
	}


	public function remove_meta_boxs() {
		remove_meta_box('submitdiv', 'newsletter', 'core');
		remove_meta_box('tagsdiv-newsletter_lists', 'newsletter', 'core' );

	}


	public function add_welcome_page() {
	
		$page = add_submenu_page(NULL, 'Welcome', 'Welcome', 'read', 'mymail_welcome', array( &$this, 'welcome_page' ));
		
	}
	
	public function welcome_page() {
	
		include MYMAIL_DIR . '/views/welcome.php';
	}
	
	public function newsletter_details() {
		global $post;
		global $post_id;
		include MYMAIL_DIR . '/views/newsletter/details.php';
	}


	public function newsletter_template() {
		global $post;
		global $post_id;
		include MYMAIL_DIR . '/views/newsletter/template.php';
	}


	public function newsletter_delivery() {
		global $post;
		global $post_id;
		include MYMAIL_DIR . '/views/newsletter/delivery.php';
	}
	
	
	public function newsletter_lists() {
		global $post;
		global $post_id;
		include MYMAIL_DIR . '/views/newsletter/lists.php';
	}


	public function newsletter_options() {
		global $post;
		global $post_id;
		include MYMAIL_DIR . '/views/newsletter/options.php';
	}


	public function newsletter_submit($post) {
		global $action;
		$post_type = $post->post_type;
		$post_type_object = get_post_type_object($post_type);
		$can_publish = current_user_can($post_type_object->cap->publish_posts);
		include MYMAIL_DIR . '/views/newsletter/submit.php';
	}



	/*----------------------------------------------------------------------*/
	/* Help
	/*----------------------------------------------------------------------*/

	public function help($rtfm) {
	
		// TODO make some Help
		
		return $rtfm;

		$current_screen = get_current_screen();
		switch ($current_screen->id) {
		case 'edit-newsletter':
		
			//edit/overview page
			break;
		case 'newsletter_page_templates':
		
			//templates
			break;
		case 'newsletter':

			$current_screen->add_help_tab( array(
					'id' => 'overview1',
					'title' => __( 'More' ),
					'content' =>
					'<h3>Title</h3>'.
					'<p>Content</p>',
			) );

			$current_screen->set_help_sidebar(
				'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
				'<p>Content</p>' .
				'<p>Content</p>'
			);
			break;
		}

		return $rtfm;
	}


	/*----------------------------------------------------------------------*/
	/* Styles & Scripts
	/*----------------------------------------------------------------------*/


	public function admin_scripts_styles($hook) {

		wp_register_style('mymail-admin', MYMAIL_URI . '/assets/css/admin.css', array(), MYMAIL_VERSION);
		wp_enqueue_style('mymail-admin');

		global $post;
		if (!isset($post) || $post->post_type != 'newsletter') return false;

		if ($hook == 'edit.php') {
			wp_register_style('mymail-overview', MYMAIL_URI . '/assets/css/overview-style.css', array(), MYMAIL_VERSION);
			wp_enqueue_style('mymail-overview');

		} else if ($hook == 'post-new.php' || $hook == 'post.php') {
		
			global $wp_locale;

			if (in_array($post->post_status, array('active', 'finished'))) {
				wp_register_script('google-jsapi', 'https://www.google.com/jsapi');
				wp_enqueue_script('google-jsapi');
				wp_register_script('piecharts', MYMAIL_URI . '/assets/js/piecharts.js', array('jquery'), MYMAIL_VERSION);
				wp_enqueue_script('piecharts');
			}

			wp_register_script('mymail-script', MYMAIL_URI . '/assets/js/newsletter-script.js', array('jquery'), MYMAIL_VERSION);

			if (user_can_richedit()) wp_enqueue_script('editor');

			wp_enqueue_style('jquery-style', MYMAIL_URI . '/assets/css/jquery-ui-'.('classic' == get_user_option( 'admin_color' ) ? 'classic' : 'fresh').'.css');
			
			wp_enqueue_style('thickbox');
			wp_enqueue_script('thickbox');
			
			if(function_exists( 'wp_enqueue_media' )){
				wp_enqueue_media();
			}else{
				wp_enqueue_script('media-upload');
			}
			
			wp_enqueue_script('jquery');
			wp_enqueue_script('jquery-ui-datepicker');

			wp_register_style('mymail-minicolors', MYMAIL_URI . '/assets/css/jquery.miniColors.css', array(), MYMAIL_VERSION);
			wp_enqueue_style('mymail-minicolors');
			wp_register_script('mymail-minicolors', MYMAIL_URI . '/assets/js/jquery.miniColors.js');
			wp_enqueue_script('mymail-minicolors');

			wp_enqueue_script('mymail-script');
			wp_localize_script('mymail-script', 'mymailL10n', array(
				'add' => __('add', 'mymail'),
				'or' => __('or', 'mymail'),
				'move_module_up' => __('Move module up', 'mymail'),
				'move_module_down' => __('Move module down', 'mymail'),
				'duplicate_module' => __('Duplicate module', 'mymail'),
				'remove_module' => __('remove module', 'mymail'),
				'add_module' => __('Add Module', 'mymail'),
				'edit' => __('Edit', 'mymail'),
				'auto' => _x('Auto', 'for the autoimporter', 'mymail'),
				'add_button' => __('add button', 'mymail'),
				'add_s' => __('add %s', 'mymail'),
				'remove_s' => __('remove %s', 'mymail'),
				'clickbadge' => _x('%s - %s clicks (%s)', '"link" "count" clicks ("percentage")', 'mymail'),
				'curr_selected' => __('Currently selected', 'mymail'),
				'remove_btn' => __('An empty link will remove this button! Continue?', 'mymail'),
				'preview_for' => __('Preview for %s', 'mymail'),
				'preview' => __('Preview', 'mymail'),
				'read_more' => __('Read more', 'mymail'),
				'invalid_image' => __('%s does not contain a valid image', 'mymail'),

				'next' => __('next', 'mymail'),
				'prev' => __('prev', 'mymail'),
				'start_of_week' => get_option('start_of_week'),
				'day_names' => $wp_locale->weekday,
				'day_names_min' => array_values($wp_locale->weekday_abbrev),
				'month_names' => array_values($wp_locale->month),
				'delete_colorschema' => __('Delete this color schema?', 'mymail'),
				'delete_colorschema_all' => __('Do you really like to delete all custom color schemas for this template?', 'mymail'),

				'undisteps' => mymail_option('undosteps', 10),
			));
			wp_localize_script('mymail-script', 'mymaildata', array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'url' => MYMAIL_URI,
			));

			wp_register_style('mymail-style', MYMAIL_URI . '/assets/css/newsletter-style.css', array(), MYMAIL_VERSION);
			wp_enqueue_style('mymail-style');

		}
	}


	public function dashboard_widget_styles() {
		wp_register_script('piecharts', MYMAIL_URI . '/assets/js/piecharts.js', array('jquery'), MYMAIL_VERSION);
		wp_enqueue_script('piecharts');
		wp_register_script('google-jsapi', 'https://www.google.com/jsapi');
		wp_enqueue_script('google-jsapi');
		wp_register_script('mymail-dashboard-script', MYMAIL_URI . '/assets/js/dashboard-script.js', array('piecharts'), MYMAIL_VERSION);
		wp_enqueue_script('mymail-dashboard-script');
		wp_register_style('mymail-dashboard-style', MYMAIL_URI . '/assets/css/dashboard-style.css', array(), MYMAIL_VERSION);
		wp_enqueue_style('mymail-dashboard-style');
	}


	public function register_script() {							//allow to remove jquery with filter if a theme incorrectly includes jquery
		wp_register_script('mymail-form', MYMAIL_URI . '/assets/js/form.js', apply_filters('mymail_no_jquery', array('jquery')), MYMAIL_VERSION, true);
		wp_register_script('mymail-form-placeholder', MYMAIL_URI . '/assets/js/placeholder-fix.js', apply_filters('mymail_no_jquery', array('jquery')), MYMAIL_VERSION, true);
	}


	static function style() {
		if(mymail_option('form_css')){
			wp_register_style('mymail-form', admin_url('admin-ajax.php?action=mymail_form_css'), NULL, mymail_option('form_css_hash'));
			wp_enqueue_style('mymail-form');
		}
	}


	public function mymail_form_css($return = false) {
		
		if( !$return ){
			header( 'Content-Type: text/css' );
			header( 'Expires: Thu, 31 Dec 2050 23:59:59 GMT' );
			header( 'Pragma: cache' );
		}
		
		if ( false === ( $css = get_transient( 'mymail_form_css' ) ) ) {

			$css = mymail_option('form_css');
			$css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
			$css = trim(str_replace(array("\r\n", "\r", "\n", "\t", '   ', '  '), '', $css));
			$css = str_replace(' {', '{', $css);
			$css = str_replace(' }', '}', $css);
			set_transient( 'mymail_form_css', $css );

		}

		if($return) return $css;
		
		echo $css;
		exit();
	}




	/*----------------------------------------------------------------------*/
	/* Save Methods
	/*----------------------------------------------------------------------*/


	public function wp_insert_post_data($data) {
	
		if ($data['post_type'] != 'newsletter') return $data;
		
		//sanitize the content and remove all content filters
		$data['post_content'] = $this->sanitize_content($data['post_content']);
		
		$data['post_excerpt'] = $this->plain_text($data['post_content']);

		if (!in_array($data['post_status'], array('pending', 'draft', 'auto-draft', 'trash'))) {

			if ($data['post_status'] == 'publish') $data['post_status'] = 'paused';
			$data['post_status'] = (isset($_POST['mymail_data']['active'])) ? 'queued' : $data['post_status'];
			
			$this->add_cron();


		}

		return $data;

	}


	public function save_post($post_id, $post) {

		if (isset($_POST['mymail_data']) && $post->post_type == 'newsletter') {
		
			$save = get_post_meta($post_id, 'mymail-data', true);
			
			if(isset($_POST['mymail_data'])){
				$save['subject'] = $_POST['mymail_data']['subject'];
				$save['preheader'] = $_POST['mymail_data']['preheader'];
				$save['template'] = $_POST['mymail_data']['template'];
				$save['file'] = $_POST['mymail_data']['file'];
					//$save['lists'] = $_POST['mymail_data']['lists'];
				$save['from_name'] = $_POST['mymail_data']['from_name'];
				$save['from'] = $_POST['mymail_data']['from'];
				$save['reply_to'] = $_POST['mymail_data']['reply_to'];

				if (isset($_POST['mymail_data']['newsletter_color']))
					$save['newsletter_color'] = $_POST['mymail_data']['newsletter_color'];

					//$save['version'] = isset($_POST['mymail_data']['version']) ? $_POST['mymail_data']['version'] : NULL;
				$save['background'] = $_POST['mymail_data']['background'];

				$save['active'] = isset($_POST['mymail_data']['active']) || isset($_POST['send_now']);
				$save['embed_images'] = isset($_POST['mymail_data']['embed_images']);
				
			}

			$campaign_data = get_post_meta($post_id, 'mymail-campaign', true);
			
			$campaign_data = wp_parse_args($campaign_data, array(
					'total' => 0,
					'sent' => 0,
					'opens' => 0,
					'totalclicks' => 0,
					'totaluniqueclicks' => 0,
					'clicks' => array(),
					'hardbounces' => 0,
					'unsubscribes' => 0,
					'countries' => array(),
					'cities' => array(),
					'errors' => array(),
					'timestamp' => NULL
				)
			);
			
			$campaign_data['total'] = $this->get_totals_by_id($post_id, true, true);
			
			$this->post_meta($post_id, 'mymail-campaign', $campaign_data, true);
			
			$currenttime = current_time('timestamp');
			
			if ($save['active']) {

				if(isset($_POST['mymail_data'])){
					$save['timestamp'] = max($currenttime, strtotime($_POST['mymail_data']['date'] . ' ' . $_POST['mymail_data']['time']));
				}
				
				if (isset($_POST['send_now'])) {
					$save['timestamp'] = $currenttime;
					$post->post_status = 'queued';
				}

				$save['date'] = date('Y-m-d', $save['timestamp']);
				$save['time'] = date('H:i', $save['timestamp']);


				//set status to 'active' if time is in the past
				if ($post->post_status == 'queued' && $currenttime - $save['timestamp'] == 0 && $campaign_data['total']) {
					$this->change_status($post, 'active');
				}
				
				mymail_remove_notice('camp_error_'.$post_id);
				
			}

			$this->post_meta($post_id, 'mymail-data', $save, true);


			//if post is published, active or queued and campaign start wiwthin the next 60 minutes
			if (in_array($post->post_status, array('active', 'queued')) && $currenttime - $save['timestamp'] > -3600) {
				$this->add_cron();
			}

			//make permalinks work correctly
			flush_rewrite_rules();
			
		}

	}


	/*----------------------------------------------------------------------*/
	/* Cron Stuff
	/*----------------------------------------------------------------------*/

	public function init_cron() {
		add_filter('cron_schedules', array( &$this, 'filter_cron_schedules'));
		add_action('mymail_cron', array( &$this, 'general_cronjob'));
		add_action('mymail_cron_worker', array( &$this, 'cronjob'));
		if (!wp_next_scheduled('mymail_cron')) $this->add_cron(true);
	}


	public function add_cron($generalonly = false) {

		if (!wp_next_scheduled('mymail_cron')) {
			wp_schedule_event(floor(time()/300)*300, 'hourly', 'mymail_cron');
			//stop here cause mymail_cron triggers the worker if required
			return true;
		} elseif ($generalonly) {
			return false;
		}

		//remove the WordPress cron if "normal" cron is used
		if (mymail_option('cron_service') != 'wp_cron') {
			wp_clear_scheduled_hook('mymail_cron_worker');
			return false;
		}

		//add worker only once (5min round)
		if (!wp_next_scheduled('mymail_cron_worker')) {
			wp_schedule_event(floor(time()/300)*300, 'mymail_cron_interval', 'mymail_cron_worker');
			return true;
		}

		return false;

	}


	// add custom time to cron
	public function filter_cron_schedules($cron_schedules) {
	
		$cron_schedules['mymail_cron_interval'] = array(
			'interval' => mymail_option('interval', 5) * 60, // seconds
			'display' => 'myMail Cronjob Interval'
		);
		
		return $cron_schedules;
	}


	//The function which sends the stuff
	public function cronjob($cron_used = false) {
	
		if (defined('DOING_AJAX') || defined('DOING_AUTOSAVE') || defined('WP_INSTALLING')) return false;
		
		//define a constant with the time so we can take a look
		define('MYMAIL_DOING_CRON', time());
		

		if (!$cron_used) {
			if (mymail_option('cron_service') != 'wp_cron') {
				$this->remove_crons();
				return false;
			}else {
				sleep(2);
			}
		}
		
		$safe_mode = @ini_get('safe_mode');
		$memory_limit = @ini_get('memory_limit');
		$max_execution_time = @ini_get('max_execution_time');
		
		//lockfile exists
		if(file_exists(MYMAIL_UPLOAD_DIR.'/CRON_LOCK')){
		
			$age = time()-filemtime(MYMAIL_UPLOAD_DIR.'/CRON_LOCK');
			
			if($age < 10){
				echo "Cron is currently running!";
				return false;
			}else if($age < ini_get('max_execution_time')){
				mymail_notice('<strong>'.sprintf(__('It seems your last cronjob hasn\'t been finished! Increase the %1$s, add %2$s to your wp-config.php or reduce the %3$s in the settings' , 'mymail'), "'max_execution_time'", '<code>define("WP_MEMORY_LIMIT", "256M");</code>', '<a href="options-general.php?page=newsletter-settings&settings-updated=true#delivery">'.__('Number of mails sent', 'mymail').'</a>').'</strong>', 'error', false, 'cron_unfinished');
				die();
			}else{
			}
		}else{
			file_put_contents(MYMAIL_UPLOAD_DIR.'/CRON_LOCK', time());
		}
		
		$this->check_bounces();
		
		
		if(!$safe_mode){
			@set_time_limit(0);
			
			if(intval($max_execution_time) < 300){
				@ini_set( 'max_execution_time', 300 );
				$max_execution_time = @ini_get('max_execution_time');
			}
			if(intval($memory_limit) < 256){
				@ini_set( 'memory_limit', '256M' );
				$memory_limit = @ini_get('memory_limit');
			}
		}
		
		@ignore_user_abort(true);
		register_shutdown_function(array($this, 'finish_cron'));
		
		global $wpdb, $mymail_campaignID, $mymail_campaigndata;
		
		$query = "SELECT * FROM {$wpdb->posts} WHERE {$wpdb->posts}.post_type = 'newsletter' AND {$wpdb->posts}.post_status IN ('active', 'queued') GROUP BY {$wpdb->posts}.ID ORDER BY {$wpdb->posts}.post_modified ASC";
		
		$campaigns = $wpdb->get_results($query);
		$campaign_count = count($campaigns);
		$campaign_active_count = 0;
		for ($i = 0; $i < $campaign_count; $i++) {
			if($campaigns[$i]->post_status == 'active') $campaign_active_count++;
		}		
		$mymail_campaignID = $mymail_campaigndata = array();
		$max_memory_usage = 0;

		//how many newsletter sent at once
		$send_at_once = mymail_option('send_at_once');
		$send_per_camp = $campaign_active_count && mymail_option('split_campaigns') ? ceil($send_at_once/$campaign_active_count) : $send_at_once;
		$max_bounces = mymail_option('bounce_attempts');
		$sent_this_turn = 0;
		$send_delay = intval(mymail_option('send_delay', 0))*1000;
		
		$bounces_only = true;
		$senderrors = array();
		$quit_cronjob = !$campaign_count;
		$unsubscribe_homepage = (get_page( mymail_option('homepage') )) ? get_permalink(mymail_option('homepage')) : get_bloginfo('url');
		$unsubscribe_homepage = apply_filters('mymail_unsubscribe_link', $unsubscribe_homepage);

		if($memory_limit)
			$this->cron_log('memory limit', '<strong>' . intval($memory_limit) . ' MB</strong>');
		
		$this->cron_log('safe_mode', '<strong>' . ($safe_mode ? 'enabled' : 'disabled') . '</strong>');
		$this->cron_log('max_execution_time', '<strong>' . $max_execution_time . ' seconds</strong>');
		$this->cron_log('campaigns found', '<strong>' . $campaign_active_count . '</strong>');
		$this->cron_log('send max at once', '<strong>'.$send_at_once.'</strong>');
		
		$this->cron_log();
		
		for ($i = 0; $i < $campaign_count; $i++) {

			$time_start = microtime(true);

			$break_on_error = false;
			
			//current newsletter
			$campaign = $campaigns[$i];
			$campaign_permalink = get_permalink($campaign->ID);

			//get data from the newsletter
			$data = get_post_meta($campaign->ID, 'mymail-data', true);
			
			//if mail is less then an hour in the future and
			if (current_time('timestamp') - $data['timestamp'] > -3600)
				$quit_cronjob = false;

			///allready sent, not active or in the future go to next
			if (current_time('timestamp') - $data['timestamp'] < 0)
				continue;

			//change post status to active (silence)
			if ($campaign->post_status == 'queued')
				$this->change_status($campaign, 'active', true);

			//to many this turn => break;
			if ($sent_this_turn >= $send_at_once)
				break;

			
			//get more data from the newsletter
			$campaigncount = count($mymail_campaigndata);
			$mymail_campaignID[] = $campaign->ID;
			$mymail_campaigndata[$campaigncount] = get_post_meta($campaign->ID, 'mymail-campaign', true);
			$lists = wp_get_post_terms($campaign->ID, 'newsletter_lists', array("fields" => "ids"));

			//baselink with trailing slash required for links to work in Outlook 2007
			$baselink = add_query_arg('mymail', $campaign->ID, home_url('/'));
			
			$unsubscribelink = add_query_arg('unsubscribe', md5($campaign->ID . '_unsubscribe'), $unsubscribe_homepage);

			$errors = $mymail_campaigndata[$campaigncount]['errors'];
			$totalerrors = isset($mymail_campaigndata[$campaigncount]['totalerrors']) ? $mymail_campaigndata[$campaigncount]['totalerrors'] : count($mymail_campaigndata[$campaigncount]['errors']);

			require_once MYMAIL_DIR . '/classes/mail.class.php';

			$mail = mymail_mail::get_instance();

			//stop if send limit is reached
			if($mail->sentlimitreached)
				break;
				
			$mail->from = $data['from'];
			$mail->from_name = $data['from_name'];

			$mail->bouncemail = mymail_option('bounce');
			$mail->reply_to = $data['reply_to'];

			$mail->embed_images = $data['embed_images'];
			
			require_once MYMAIL_DIR . '/classes/placeholder.class.php';
			$placeholder = new mymail_placeholder($campaign->post_content);

			$placeholder->add(array(
					'preheader' => $data['preheader'],
					'subject' => $data['subject'],
					'webversion' => '<a href="{webversionlink}">' . mymail_text('webversion') . '</a>',
					'webversionlink' => $campaign_permalink,
					'unsub' => '<a href="{unsublink}">' . mymail_text('unsubscribelink') . '</a>',
					'unsublink' => $unsubscribelink,
					'forward' => '<a href="{forwardlink}">' . mymail_text('forward') . '</a>',
					'email' => '<a href="mailto:{emailaddress}">{emailaddress}</a>'
				));

			$placeholder->share_service($campaign_permalink, $campaign->post_title);

			$mail->content = $placeholder->get_content(false);

			$mail->baselink = $baselink;

			$mail->prepare_content();

			//store the base content temporary;
			$basecontent = $mail->content;

			//get all links from the basecontent
			preg_match_all('#href=(\'|")?(https?[^\'"\#]+)(\'|")?#', $basecontent, $links);
			$links = $links[2];
			
			$totalsend = 0;
			
			$term_taxonomy_ids = wp_list_pluck(get_terms( 'newsletter_lists', array('fields' => 'all', 'include' => $lists)), 'term_taxonomy_id');
			
			//no subscribers
			if(empty($term_taxonomy_ids) || empty($lists)) continue;
			
			//get all users from all lists only once ordered by ID
			$query = "SELECT {$wpdb->posts}.ID, {$wpdb->posts}.post_name AS hash, {$wpdb->posts}.post_title AS email, {$wpdb->posts}.post_status as status, {$wpdb->postmeta}.meta_value as meta FROM {$wpdb->posts} INNER JOIN {$wpdb->term_relationships} ON ({$wpdb->posts}.ID = {$wpdb->term_relationships}.object_id) LEFT JOIN {$wpdb->postmeta} ON ({$wpdb->posts}.ID = {$wpdb->postmeta}.post_id AND {$wpdb->postmeta}.meta_key = 'mymail-campaigns') WHERE ( {$wpdb->term_relationships}.term_taxonomy_id IN (".implode(',', $term_taxonomy_ids ).") ) AND {$wpdb->posts}.post_type = 'subscriber' AND ({$wpdb->posts}.post_status IN ('subscribed', 'unsubscribed')) GROUP BY {$wpdb->posts}.ID ORDER BY {$wpdb->posts}.ID ASC";
			
			$result = mysql_query($query, $wpdb->dbh);
			
			$subscribers_count = mysql_num_rows($result);
			
			$this->cron_log('Campaign', '<strong>'.$campaign->post_title.'</strong>');
			$this->cron_log('Subscribers found', '<strong>'.$subscribers_count.'</strong>');
			$this->cron_log('send max for camp', '<strong>' . $send_per_camp . '</strong>');
			
			$this->cron_log();
			
			if($error = mysql_error( $wpdb->dbh )){
				die($error);
			}
			
			$subscribercounter = $totalsend;
			$campaign_send_counter = 0;
			
			//foreach subscribers
			while ($subscriber = @mysql_fetch_object($result)) {
				
				if(!$safe_mode) @set_time_limit(0);
				
				touch(MYMAIL_UPLOAD_DIR.'/CRON_LOCK');
		
				if(connection_aborted())
					break;
					
				$subscribercounter++;
				
				//to many send for this campaign;
				if ($campaign_send_counter >= $send_per_camp)
					break;

				if($break_on_error)
					break;
					
				//to many this turn => break;
				if ($sent_this_turn >= $send_at_once)
					break;
					
				//stop if send limit is reached
				if($mail->sentlimitreached)
					break;
					
				if(!isset($subscriber))
					break;
					
				
				$usercampaigndata = $subscriber->meta ? unserialize($subscriber->meta) : array();
				
				
				//check if campaign was sent
				if ($usercampaigndata[$campaign->ID]['sent'] || $subscribercounter <= 0) {
				
					//campaign was sent
					$totalsend++;
					
					continue;
					
				}else{
					
					//stop if user isn't subscribed
					if($subscriber->status != 'subscribed') continue;
					
					//not sent
					
					//user reaches bouncelimit
					if($usercampaigndata[$campaign->ID]['bounces'] >= $max_bounces) continue;
					
					$time_mail_start = microtime(true);
					
					$userdata = get_post_meta($subscriber->ID, 'mymail-userdata', true);
					if(!is_array($userdata)) $userdata = array();
					
					$mail->to = $subscriber->email;
					
					$mail->hash = $subscriber->hash;
					$mail->subject = $data['subject'];

					//restore from the base
					$placeholder->set_content($basecontent);
					
					//unset meta property if set
					if (isset($userdata['_meta'])) unset($userdata['_meta']);
					
					$placeholder->add(array_merge(array(
						'fullname' => trim($userdata['firstname'] . ' ' . $userdata['lastname']),
						'forwardlink' => add_query_arg('forward', $subscriber->email, $campaign_permalink),
					), array(
						'emailaddress' => $subscriber->email
					), $userdata));

					
					//replace links
					$placeholder->replace_links($baselink, $links, $subscriber->hash);

					$mail->content = $placeholder->get_content();

					//placeholders in subject
					$placeholder->set_content($data['subject']);
					$mail->subject = $placeholder->get_content();


					//set headers for bouncing
					$mail->add_header('X-MyMail', $subscriber->hash);
					$mail->add_header('X-MyMail-Campaign', $campaign->ID);
					$mail->add_header('List-Unsubscribe', add_query_arg(array('k' => $campaign->ID, 'unsubscribe' => $subscriber->hash), $unsubscribelink));
					
					
					try {
					
						$success = $mail->send();
				
					} catch ( Exception $e ) {
					
						$success = false;
						
					}
					
					//send mail
					if ($success) {
					
						//mark as send and increase total with 1
						$usercampaigndata[$campaign->ID]['sent'] = true;
						$usercampaigndata[$campaign->ID]['timestamp'] = current_time('timestamp');
						$this->post_meta($subscriber->ID, 'mymail-campaigns', $usercampaigndata);
						
						if (!isset($usercampaigndata[$campaign->ID]['bounces'])) $bounces_only = false;
						
						//campaign was sent
						$totalsend++;
						$sent_this_turn++;
						$campaign_send_counter++;
						
						$this->cron_log(
							'#'.($subscribercounter).' <strong>'.$subscriber->email.'</strong> sent',
							'try '.($usercampaigndata[$campaign->ID]['bounces']+1).'.',
							(microtime(true) - $time_mail_start)
						);
						
					} else {
					
						$e_array = $mail->get_errors('array');
						$errormsg = trim($e_array[count($e_array)-1]);
						
						if(!$errormsg){
							if(!$campaign->post_content){
								$errormsg = 'no content';
							}else{
								$errormsg = '';
							}
						
						}
						
						$subscriber_errors = apply_filters('mymail_subscriber_errors', array(
							'SMTP Error: The following recipients failed',
							'The following From address failed',
							'Invalid address:',
							'SMTP Error: Data not accepted',
						));
						
						$is_subscriber_error = !mymail_is_email($subscriber->email);
						//check for subscriber error
						foreach($subscriber_errors as $subscriber_error){
							if(stripos($errormsg, $subscriber_error) !== false){
								$is_subscriber_error = true;
								break;
							}
						}
						
						//caused by the subscriber
						if($is_subscriber_error){
							
							$this->cron_log(
								'#'.($subscribercounter).' <strong>'.$subscriber->email.'</strong> <span style="color:#f33">not sent</span>',
								'<br><span style="color:#f33">'.$errormsg.'</span>',
								(microtime(true) - $time_mail_start)
							);
							$usercampaigndata[$campaign->ID]['sent'] = false;
							//change status
							$this->change_status(get_post($subscriber->ID), 'error');
							$errors[$subscriber->email] = $errormsg;
							$totalerrors++;
							
							do_action('mymail_subscriber_error', $campaign, $subscriber, $errormsg);
							
						//campaign failure
						}else{
						
							if(!empty($e_array)){
							
								$senderrors[] = $mail->get_errors('br');
								$break_on_error = true;
								
							}elseif($errormsg){
							
								$senderrors[] = $errormsg;
								$break_on_error = true;
							}
							
							if($break_on_error) $this->cron_log('Campaign paused cause of a sending error: <span style="color:#f33">'.$errormsg.'</span>');
						}
						
						$this->post_meta($subscriber->ID, 'mymail-campaigns', $usercampaigndata);
					}
					
					$max_memory_usage = max($max_memory_usage, memory_get_peak_usage(true));
					
					$took_mail = (microtime(true) - $time_mail_start)*1000;
					
					//pause
					if($send_delay) usleep(max(1,round($send_delay-$took_mail)));
					
				}
				
			}
			
			
			$max_memory_usage = max($max_memory_usage, memory_get_peak_usage(true));
			
			mysql_free_result($result);

			$this->cron_log();
			
			$took = (microtime(true) - $time_start);

			if($max_memory_usage) $this->cron_log('max. memory usage','<strong>'.size_format($max_memory_usage, 2).'</strong>');
				
			$this->cron_log('sent this turn', $sent_this_turn);
			
			if($sent_this_turn) $this->cron_log('time', round($took,2).' sec., ('.round($took/$sent_this_turn, 4).'/mail)');
			
			$this->cron_log();
			//close connection if smtp
			$mail->close();

			//load it again cause it may changed during sending
			wp_cache_delete( $campaign->ID, 'post' . '_meta' );
			$mymail_campaigndata[$campaigncount] = get_post_meta( $campaign->ID, 'mymail-campaign', true);
			
			//count users and save to campaign
			$mymail_campaigndata[$campaigncount]['sent'] = $totalsend;
			$mymail_campaigndata[$campaigncount]['errors'] = $errors;
			$mymail_campaigndata[$campaigncount]['totalerrors'] = $totalerrors;
			
			if($break_on_error){
				$this->change_status($campaign, 'paused');
				mymail_notice(sprintf(__( 'Campaign %1$s has been paused cause of a sending error: %2$s', 'mymail'), '<a href="post.php?post='.$campaign->ID.'&action=edit"><strong>'.$campaign->post_title.'</strong></a>', '<strong>'.implode('',$senderrors)).'</strong>', 'error', false, 'camp_error_'.$campaign->ID);
				
				do_action('mymail_break_on_error', $campaign, $senderrors);
			
			//campaign is finished (or no mail was sent cause no subscriber)
			
			}else{
			
				//recalc totals
				$mymail_campaigndata[$campaigncount]['total'] = $this->get_totals_by_id($campaign->ID)+$mymail_campaigndata[$campaigncount]['unsubscribes']+$mymail_campaigndata[$campaigncount]['hardbounces']-$totalerrors;
				
				//stop with no subscribers
				if($mymail_campaigndata[$campaigncount]['total'] == 0) continue;
				
				//recalculate totals if sent is more or equal the once in the database
/*
				if($mymail_campaigndata[$campaigncount]['sent'] >= $subscribers_count){
					//$mymail_campaigndata[$campaigncount]['total'] = $this->get_totals_by_id($campaign->ID, true, true)+$mymail_campaigndata[$campaigncount]['unsubscribes']-$totalerrors;
				}
*/
				
				//obviously finished
				if ($mymail_campaigndata[$campaigncount]['sent']+$mymail_campaigndata[$campaigncount]['hardbounces'] >= $mymail_campaigndata[$campaigncount]['total']) {
				
					$mymail_campaigndata[$campaigncount]['timestamp'] = current_time('timestamp');
					
					
					//change campaign to finished and replace the dyamic content
					$placeholder->clear_placeholder();
					$placeholder->set_content($campaign->post_content);
					//$placeholder->add($placeholder->get_dynamic());
	
					//remove the KSES filter which strips "unwanted" tags and attributes
					remove_filter('content_save_pre', 'wp_filter_post_kses');
	
					wp_update_post(array(
						'ID' => $campaign->ID,
						'post_content' => $placeholder->get_content(false),
					));
	
					//change status silencly if only bounces where sent
					$this->change_status($campaign, 'finished', $bounces_only);
					
					//do third party plugins stuff
					$this->thirdpartystuff();
					
				}else{
				
					
				}
				
				if($sent_this_turn) do_action('mymail_cron_mails_sent', $campaign);


			}
			

		}

		if ($cron_used && is_user_logged_in())
			$this->show_cron_log();

		if ($quit_cronjob && !$cron_used)
			$this->remove_crons();
		
		do_action('mymail_cron_finished');

		
		return true;

	}


	public function finish_cron() {
		global $mymail_campaigndata, $mymail_campaignID;
		
		for($i = 0; $i < count($mymail_campaigndata); $i++){
			$this->post_meta($mymail_campaignID[$i], 'mymail-campaign', $mymail_campaigndata[$i]);
		}
		
		//set the date in the past if deleteion fails
		touch(MYMAIL_UPLOAD_DIR.'/CRON_LOCK', 1356994800);
		@unlink(MYMAIL_UPLOAD_DIR.'/CRON_LOCK');
		
		mymail_remove_notice('cron_unfinished');
	}


	public function cron_log() {
		global $mymail_cron_log, $mymail_cron_log_max_fields;
		
		if (!$mymail_cron_log) $mymail_cron_log = array();
		
		if ($a = func_get_args()) {
			array_unshift($a, microtime(true));
			$mymail_cron_log[] = $a;
			$mymail_cron_log_max_fields = max($mymail_cron_log_max_fields || 0, count($a));
		}else{
			$mymail_cron_log_max_fields = 0;
			$mymail_cron_log[] = array();
		}
	
	}
	
	public function show_cron_log() {
		global $mymail_cron_log, $mymail_cron_log_max_fields;
		$html = '<table cellpadding="0" cellspacing="0" width="100%">';
		$i = 1;
		foreach($mymail_cron_log as $logs){
			if(empty($logs)){
				$i = 1;
				$html .= '</table><table cellpadding="0" cellspacing="0" width="100%">'; 
				continue;
			}
			$time = array_shift($logs);
			$html .= '<tr style="background-color:'.($i%2 ? '#fafafa' : '#ffffff').'"><td align="right" width="20">'.($i++).'</td>';
			foreach($logs as $j => $log){
				$html .= '<td>'.$log.'</td>';
			}
			$html .= str_repeat('<td>&nbsp;</td>', max(0, ($mymail_cron_log_max_fields+2)-$j-4));
			$html .= '<td width="50">'.date('H:i:s', $time).':'.round(($time-floor($time))*10000).'</td>';
			$html .= '</tr>';
		}
		$html .= '</table>';
		echo $html;
	}


	//Checks for new newsletter in the queue to start new cronjob
	public function general_cronjob() {

		//check for bounced emails
		$this->check_bounces();

		//send confimrations again
		$this->resend_confirmations();
		
		//checks if homepage is set
		$this->check_homepage();

		//get new ip database (if old)
		add_action('shutdown', array( &$this, 'renew_ips'));
		
		$the_query = new WP_Query(array(
				'post_type' => 'newsletter',
				'post_status' => array('active', 'queued'),
				'posts_per_page' => -1,
			));

		$campaigns = $the_query->posts;

		//remove_cron in any case
		$this->remove_crons();

		$timestamp = current_time('timestamp');

		for ($i = 0; $i < $the_query->post_count; $i++) {
			$data = get_post_meta($campaigns[$i]->ID, 'mymail-data', true);

			//more than 60 min in the the future
			if ($data['timestamp'] - $timestamp > 3600)
				continue;

			$this->add_cron();

		}

	}


	public function remove_crons($general = false) {
		wp_clear_scheduled_hook('mymail_cron_worker');
		if ($general)
			wp_clear_scheduled_hook('mymail_cron');
	}


	/*----------------------------------------------------------------------*/
	/* Plugin Activation / Deactivation
	/*----------------------------------------------------------------------*/



	public function activate() {
	
		global $wpdb;
		
		if (function_exists('is_multisite') && is_multisite()) {
		
			$old_blog = $wpdb->blogid;
			$blogids = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM $wpdb->blogs"));
		
		}else{
		
			$blogids = array(false);
			
		}
			
		foreach ($blogids as $blog_id) {
		
			if($blog_id) switch_to_blog( $blog_id );
			
			$this->register_post_status();
			$this->register_post_type();
			
			$isNew = get_option('mymail') == false;
			
			update_option('mymail_activation', 'active');
			
			if ($isNew){
			
				update_option('mymail_welcome', true);
			
			}
			
			if('direct' != get_filesystem_method()){
				mymail_notice('<strong>'.sprintf(__('MyMail is not able access the filesystem! If you have issues saving templates or other files please add this line to you wp-config.php: %s', 'mymail'), "<pre><code>define('FS_METHOD', 'direct');</code>").'</strong></pre>', 'error', false, 'filesystemaccess');
			}
			
			$this->do_update();
			
			$this->add_cron();
			
			flush_rewrite_rules();
			
			
		}
	
		if($blog_id){
			switch_to_blog($old_blog);
			return;
		}
			
		return $isNew;
	
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

			delete_option('mymail_activation');
	
			$this->remove_crons(true);
	
			flush_rewrite_rules();
			
			flush_rewrite_rules();
		}
		
		if($blog_id) switch_to_blog($old_blog);
	}


	private function do_update() {
		$current_version = get_option('mymail_version');

		if ($current_version != MYMAIL_VERSION) {
			if (version_compare($current_version, MYMAIL_VERSION, '<')) {
				include MYMAIL_DIR . '/includes/updates.php';

			}

			update_option('mymail_version', MYMAIL_VERSION);
		}
	}


	public function send_welcome_mail($try = 1, $response = '') {
	
		if($try >= 5) return false;
		
		if(empty($response)){
			$response = wp_remote_get( 'https://dl.dropbox.com/u/9916342/data/mymail_welcome_mail.html' );
			
			if( is_wp_error( $response ) ) {
				return false;
			}
		}

		$content = $response['body'];
		
		$current_user = wp_get_current_user();

		$replace = array(
			'headline' => '',
			'baseurl' => admin_url(),
			'notification' => 'This welcome mail was sent from your website <a href="'.home_url().'">'.get_bloginfo( 'name' ).'</a>. This also makes sure you can send emails with your current settings',
			'name' => $current_user->display_name,
			'preheader' => 'Thank you, '.$current_user->display_name.'! ',
		);
		
		$success = mymail_send('Your MyMail Newsletter Plugin is ready!', $content, $current_user->user_email, $replace);
		
		if(!$success) $this->send_welcome_mail(++$try, $response);
		
	}


	public function renew_ips($force = false) {

		$success = true;
		
		if (mymail_option('trackcountries')){

			//get new ip database
			require_once MYMAIL_DIR.'/classes/libs/Ip2Country.php';
			$ip2Country = new Ip2Country();
			
			$success = $success && $ip2Country->renew($force);
		}
		
		if (mymail_option('trackcities')){

			//get new ip database
			require_once MYMAIL_DIR.'/classes/libs/Ip2City.php';
			$Ip2City = new Ip2City();
			
			$success = $success && $Ip2City->renew($force);
		}
		
		
		return $success;

	}


	public function change_status($post, $new_status, $silent = false) {
		if (!$post)
			return false;

		if ($post->post_status == $new_status)
			return false;

		$old_status = $post->post_status;

		global $wpdb;

		if ($wpdb->update($wpdb->posts, array('post_status' => $new_status), array('ID' => $post->ID))) {
			if (!$silent) wp_transition_post_status($new_status, $old_status, $post);
			return true;
		}

		return false;

	}



	/*----------------------------------------------------------------------*/
	/* Privates
	/*----------------------------------------------------------------------*/


	private function set_template($slug, $file = 'index.html', $verify = false) {
		if ($verify) {
		
			global $mymail_templates;

			if (!is_dir($mymail_templates->path . '/'.  $slug)) {
				$slug = mymail_option('default_template', $this->defaultTemplate);
			}
			if (!file_exists($mymail_templates->path. '/' . $slug . '/' . $file)) {
				$file = 'index.html';
			}
		}
		
		$this->template = $slug;
		$this->templatefile = $file;
		
		require_once MYMAIL_DIR.'/classes/templates.class.php';
		$this->templateobj = new mymail_templates($slug, $file);
		
	}


	private function get_template() {
		return $this->template;
	}
	
	private function get_file() {
		return (!empty($this->templatefile)) ? $this->templatefile : 'index.html';
	}


	private function get_template_by_id($id, $file, $modules = true, $editorstyle = false) {
		$post = get_post($id);
		//must be a newsletter and have a content
		if ($post->post_type == 'newsletter' && !empty($post->post_content)) {
			$html = $post->post_content;

			if ($editorstyle) {
				$html = str_replace('</head>', '<link rel="stylesheet" href="' . MYMAIL_URI . '/assets/css/editor-style.css?ver=' . MYMAIL_VERSION . '" type="text/css" media="all"></head>', $html);
			}
			
			$html = str_replace(' !DOCTYPE', '!DOCTYPE', $html);

		} else if ($post->post_type == 'newsletter') {
				$html = $this->get_template_by_slug($this->get_template(), $file, $modules, $editorstyle);
			} else {
			return '';
		}


		return $html;

	}


	private function get_template_by_slug($slug, $file = 'index.html', $modules = true, $editorstyle = false) {
		require_once MYMAIL_DIR . '/classes/templates.class.php';
		$template = new mymail_templates($slug, $file);
		$html = $template->get($modules, true);
		if ($editorstyle) {
			$html = str_replace('</head>', '<link rel="stylesheet" href="' . MYMAIL_URI . '/assets/css/editor-style.css?ver=' . MYMAIL_VERSION . '" type="text/css" media="all"></head>', $html);
		}
		return $html;

	}



	private function replace_colors($content) {
		//replace the colors
		global $post_id;
		global $post;
		
		$html = $this->templateobj->get(true);
		$colors = array();
		preg_match_all('/#[a-fA-F0-9]{6}/', $html, $hits);
		$original_colors = array_unique($hits[0]);
		$html = $post->post_content;
		
		if(!empty($html) && isset($this->post_data['template']) && $this->post_data['template'] == $this->get_template() && $this->post_data['file'] == $this->get_file()){
			preg_match_all('/#[a-fA-F0-9]{6}/', $html, $hits);
			$current_colors = array_unique($hits[0]);
		}else{
			$current_colors = $original_colors;
		}
		
		if (isset($this->post_data) && isset($this->post_data['newsletter_color'])) {
		
			$search = $replace = array();
			foreach ($this->post_data['newsletter_color'] as $from => $to) {
			
				$to = array_shift($current_colors);
				if ($from == $to)
					continue;
				$search[] = $from;
				$replace[] = $to;
			}
			$content = str_replace($search, $replace, $content);
		}

		return $content;

	}


	//get total
	private function get_totals_by_id($id, $excludebounces = true, $force = false) {
	
		$lists = wp_get_post_terms($id, 'newsletter_lists', array( "fields" => "ids" ));
		
		return $this->get_totals($lists, $excludebounces, $force);
		
	}


	//get total
	private function get_totals($lists, $excludebounces = true, $force = true) {
		
		if (empty($lists))
			return 0;
	
		sort($lists);
		
		$key = 't_'.$excludebounces.'_'.implode('_',$lists);
		
		$count = wp_cache_get( $key, 'mymail' );
		if($count && !$force)
			return $count;
			
		$totals = get_transient( 'mymail_totals' );
		
		if(isset($totals[$key]) && !$force) return $totals[$key];
		
		if(!$totals) $totals = array();
		
		global $wpdb;
			
		$taxonomies = get_terms( 'newsletter_lists', array('fields' => 'all', 'include' => $lists));
			
		$term_taxonomy_ids = wp_list_pluck($taxonomies, 'term_taxonomy_id');
		
		$status = ($excludebounces) ? array('subscribed') : array('subscribed', 'unsubscribed', 'hardbounced', 'error');
		
		if(!empty($term_taxonomy_ids)){
			
			$query = "SELECT COUNT(DISTINCT {$wpdb->posts}.ID) as cnt FROM {$wpdb->posts} INNER JOIN {$wpdb->term_relationships} ON ({$wpdb->posts}.ID = {$wpdb->term_relationships}.object_id) WHERE ( {$wpdb->term_relationships}.term_taxonomy_id IN (".implode(',', $term_taxonomy_ids ).") ) AND {$wpdb->posts}.post_type = 'subscriber' AND ({$wpdb->posts}.post_status IN ('".implode("','", $status )."') )";
			
			$count = $wpdb->get_var( $query );
				
		}else{
				
			$count = 0;
				
		}
		
		$totals[$key] = $count;
			
		set_transient( 'mymail_totals' , $totals );
		
		wp_cache_add( $key, $count, 'mymail' );
		
		return $totals[$key];
		
	}


	private function pause_campaign($id) {
		if (!current_user_can('publish_newsletters')) {
			wp_die( __('You are not allowed to pause campaigns.', 'mymail'));
		}
		$post = get_post($id);
		$post_meta = get_post_meta($id, 'mymail-data', true);
		$post_meta['active'] = false;

		$this->post_meta($id, 'mymail-data', $post_meta, true);
		return $this->change_status($post, 'paused');
	}


	private function start_campaign($id) {
		if (!current_user_can('publish_newsletters')) {
			wp_die( __('You are not allowed to start campaigns.', 'mymail'));
		}
		$post = get_post($id);
		$post_meta = get_post_meta($id, 'mymail-data', true);
		if(!$this->get_totals_by_id($id)) return false;

		$post_meta['active'] = true;

		if (empty($post_meta['timestamp']) || $post->post_status == 'queued') {
			$currenttime = current_time('timestamp');
			$post_meta['timestamp'] = $currenttime;
			$post_meta['date'] = date('Y-m-d', $post_meta['timestamp']);
			$post_meta['time'] = date('H:i', $post_meta['timestamp']);
		}

		$this->post_meta($id, 'mymail-data', $post_meta, true);

		$success = $this->change_status($post, 'active');
		
		mymail_remove_notice('camp_error_'.$id);


		$this->add_cron();
		return $success;

	}


	private function duplicate_campaign($id) {
		$post = get_post($id);
		
		if (!current_user_can('duplicate_newsletters') || !current_user_can('duplicate_others_newsletters', $post->ID)) {
			wp_die( __('You are not allowed to duplicate campaigns.', 'mymail'));
		}
		$lists = wp_get_post_terms($post->ID, 'newsletter_lists', array("fields" => "slugs"));
		$meta = get_post_meta($post->ID, 'mymail-data', true);
		$meta['active'] = $meta['date'] = $meta['time'] = $meta['timestamp'] = NULL;
		
		unset($post->ID);
		unset($post->guid);
		unset($post->post_name);
		unset($post->post_author);
		unset($post->post_date);
		unset($post->post_date_gmt);
		unset($post->post_modified);
		unset($post->post_modified_gmt);
		if (preg_match('# \((\d+)\)$#', $post->post_title, $hits)) {
			$post->post_title = trim(preg_replace('#(.*) \(\d+\)$#', '$1 (' . (++$hits[1]) . ')', $post->post_title));
		} else if ($post->post_title) {
				$post->post_title .= ' (2)';
			}
		if($post->post_status == 'autoresponder'){
			$meta['active_autoresponder'] = NULL;
		}else{
			$post->post_status = 'draft';
		}
		
		//remove the KSES filter which strips "unwanted" tags and attributes
		remove_filter('content_save_pre', 'wp_filter_post_kses');

		$newID = wp_insert_post($post);
		
		if ($newID) {
		
			$this->post_meta($newID, 'mymail-data', $meta);
			wp_set_object_terms($newID, $lists, 'newsletter_lists');
			
			return $newID;
		}
		
		return false;
	}


	private function link_query( $args = array(), $countonly = false ) {
		$pts = get_post_types( array( 'public' => true ), 'objects' );
		$pt_names = array_keys( $pts );

		$defaults = array(
			'post_type' => $pt_names,
			'suppress_filters' => true,
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
			'post_status' => 'publish',
			'order' => 'DESC',
			'orderby' => 'post_date',
			'posts_per_page' => -1,
		);

		$query = wp_parse_args($args, $defaults);
		
		if ( isset( $args['s'] ) )
			$query['s'] = $args['s'];

		// Do main query.
		$get_posts = new WP_Query;
		$posts = $get_posts->query( $query );
		
		if($countonly) return $get_posts->post_count;
		// Check if any posts were found.
		if ( ! $get_posts->post_count )
			return false;

		// Build results.
		$results = array();
		foreach ( $posts as $post ) {
			if ( 'post' == $post->post_type )
				$info = mysql2date( __( 'Y/m/d' ), $post->post_date );
			else
				$info = $pts[ $post->post_type ]->labels->singular_name;

			$results[] = array(
				'ID' => $post->ID,
				'title' => trim( esc_html( strip_tags( get_the_title( $post ) ) ) ),
				'permalink' => get_permalink( $post->ID ),
				'info' => $info,
			);
		}

		return $results;
	}
	
	
	private function post_meta($post_id, $meta_key, $data, $unique = false) {
		$meta_value = get_post_meta($post_id, $meta_key, true);

		/* If a new meta value was added and there was no previous value, add it. */
		if ($data && '' == $meta_value) {
			add_post_meta($post_id, $meta_key, $data, true);
			/* If the new meta value does not match the old value, update it. */
		} elseif ($data && $data != $meta_value) {
			update_post_meta($post_id, $meta_key, $data);
			/* If there is no new meta value but an old value exists, delete it. */
		} elseif ('' == $data && $meta_value) {
			delete_post_meta($post_id, $meta_key, $meta_value);
		}
	}


	private function check_bounces() {

		if(!mymail_option('bounce_active')) return false;
		
		do_action('mymail_check_bounces');
		
		$server = mymail_option('bounce_server');
		$user = mymail_option('bounce_user');
		$pwd = mymail_option('bounce_pwd');
		
		if (!$server || !$user || !$pwd)
			return false;
		
		if ( get_transient( 'mymail_check_bounces_lock' ) ) return false;

		//check bounces only every five minutes
		set_transient( 'mymail_check_bounces_lock', true, 360 );
		
		if(mymail_option('bounce_ssl')) $server = 'ssl://'.$server;

		require_once ABSPATH . WPINC . '/class-pop3.php';
		$pop3 = new POP3();

		if (!$pop3->connect($server, mymail_option('bounce_port', 110)) || !$pop3->user($user))
			return false;

		$count = $pop3->pass($pwd);
		
		if (false === $count)
			return false;

		if (0 === $count) {
			$pop3->quit();
			return false;
		}
		
		$delete_bounces = mymail_option('bounce_delete');
		
		//only max 1000 at once
		$count = min($count, 1000);

		for ($i = 1; $i <= $count; $i++) {
			$message = $pop3->get($i);
			
			if(!$message) continue;
			
			$message = implode($message);
			
			preg_match('#X-MyMail: ([a-f0-9]{32})#i', $message, $hash);
			preg_match('#X-MyMail-Campaign: (\d+)#i', $message, $camp);
			
			if(!empty($hash) && !empty($camp)){
			
				if ($this->reset_mail($hash[1], $camp[1])) {
					$pop3->delete($i);
				} else {
					$pop3->reset();
				}
				
			}else{
				if ($delete_bounces) $pop3->delete($i);
			}
			
		}

		$pop3->quit();

		//do third party stuff
		$this->thirdpartystuff();
		

	}


	private function reset_mail($hash, $camp) {
		$campaign = get_post($camp);
		$user = new WP_query(array(
				'post_type' => 'subscriber',
				'post_status' => array(
					'subscribed',
					'unsubscribed',
					'hardbounced',
					'error'
				),
				'name' => $hash,
				'posts_per_page' => 1
			));

		//no user
		if (!$user) return true;

		$user = $user->post;

		//get campaign data
		wp_cache_delete( $campaign->ID, 'post' . '_meta' );
		$campaign_data = get_post_meta($campaign->ID, 'mymail-campaign', true);
		//get users campaign data
		wp_cache_delete( $user->ID, 'post' . '_meta' );
		$user_campaigndata = get_post_meta($user->ID, 'mymail-campaigns', true);
		
		if(!$campaign_data) return false;

		//no campaigndata for this user
		if (!isset($user_campaigndata[$campaign->ID])) return true;

		//remove data
		$user_campaigndata[$campaign->ID]['sent'] = false;
		$campaign_data['sent']--;
		

		//save the bounce
		if (!isset($user_campaigndata[$campaign->ID]['bounces']))
			$user_campaigndata[$campaign->ID]['bounces'] = 0;

		$user_campaigndata[$campaign->ID]['bounces']++;
		
		$bounce_limit_reached = $user_campaigndata[$campaign->ID]['bounces'] >= mymail_option('bounce_attempts', 3);

		//hardbounce
		if ($bounce_limit_reached) {

			//save the hardbounces to the campaign
			$campaign_data['hardbounces']++;
			
			//set status of user to hardbounced
			$this->change_status($user, 'hardbounced');
			
			mymail_clear_totals();

		}

		//save user stuff
		$this->post_meta($user->ID, 'mymail-campaigns', $user_campaigndata);

		//save campaign stuff
		$this->post_meta($campaign->ID, 'mymail-campaign', $campaign_data);


		if ($campaign->post_status == 'finished') {

			$this->change_status($campaign, 'active', true);

			$this->add_cron();

		}

		return true;
	}

	private function check_homepage(){
	
		$hp = get_permalink( mymail_option('homepage') );
		
		if(!$hp) mymail_notice(sprintf('<strong>'.__('You haven\'t defined a homepage for the newsletter. This is required to make the subscription form work correctly. Please check the %s', 'mymail'), '<a href="options-general.php?page=newsletter-settings#frontend">'.__('frontend settings page', 'mymail').'</a>').'</strong>', 'error', false, 'no-homepage');

	}

	private function resend_confirmations(){
	
	
		if(!mymail_option('subscription_resend')) return false;
		
		$timeoffset = mymail_option('subscription_resend_time', 48)*HOUR_IN_SECONDS;
		
		$confirms = get_option( 'mymail_confirms', array() );
		
		global $mymail_subscriber;
	
		$baselink = get_permalink( mymail_option('homepage') );
	
		if(!$baselink) $baselink = site_url();
		
		foreach($confirms as $id => $data){
		
			if( time()-$data['last'] < $timeoffset ) continue;
			
			$template = isset($data['template']) ? $data['template'] : 'notification.html';
			
			if( $data['try'] >= get_option( 'subscription_resend_count', 3 )) continue;
			
			$email = $data['userdata']['email'];
			
			$mymail_subscriber->send_confirmation( $baselink, $email, $data['userdata'], $data['lists'], array(
				'try' => ++$data['try'],
				'timestamp' => $data['timestamp'],
				'last' => time(),
			), false, $template);
			
			//pause
			if(mymail_option('send_delay')) usleep(mymail_option('send_delay'));
			
		}
		
		
	}

	private function thirdpartystuff() {

		do_action('mymail_thirdpartystuff');
		
		if (function_exists('w3tc_objectcache_flush'))
			add_action('shutdown', 'w3tc_objectcache_flush');

		if (function_exists('wp_cache_clear_cache'))
			add_action('shutdown', 'wp_cache_clear_cache');

	}


}


class mymail_Walker_Category_Checklist extends Walker {
	var $tree_type = 'category';
	var $db_fields = array ('parent' => 'parent', 'id' => 'term_id');

	function start_lvl( &$output, $depth = 0, $args = array() ) {
		$indent = str_repeat("\t", $depth);
		$output .= "$indent<ul class='children'>\n";
	}


	function end_lvl( &$output, $depth = 0, $args = array() ) {
		$indent = str_repeat("\t", $depth);
		$output .= "$indent</ul>\n";
	}


	function start_el( &$output, $category, $depth, $args, $id = 0 ) {
		extract($args);
		if ( empty($taxonomy) )
			$taxonomy = 'category';

		if ( $taxonomy == 'category' )
			$name = 'post_category';
		else
			$name = 'tax_input['.$taxonomy.']';
			
		$class = in_array( $category->term_id, $popular_cats ) ? ' class="popular-category"' : '';
		$output .= "\n<li id='{$taxonomy}-{$category->term_id}'$class>" . '<label class="selectit"><input value="' . $category->slug . '" type="checkbox" name="'.$name.'[]" id="in-'.$taxonomy.'-' . $category->term_id . '" data-id="' . $category->term_id . '" class="list"' . checked( in_array( $category->term_id, $selected_cats ), true, false ) . ' /> ' . esc_html( apply_filters('the_category', $category->name )) . ' (' . $category->count. ')</label>';
	}


	function end_el( &$output, $category, $depth = 0, $args = array() ) {
		$output .= "</li>\n";
	}


}

?>