<?php 
		global $wpdb, $mymail_templates,$mymail_options, $current_user, $wp_roles;
		
		if(isset($_GET['reset'])) $this->reset_settings();
		
		if(!$mymail_options){
		?>
			<div class="wrap">
			
			<h2>Ooops, seems your settings are missing or broken :(</h2>
	
			<p><a href="options-general.php?page=newsletter-settings&reset=1&_wpnonce=<?php echo wp_create_nonce('mymail-reset-settings') ?>" class="button button-primary button-large">Reset all settings now</a></p>
			</div>
		
		<?php
			wp_die();
		}

?>
<form method="post" action="options.php" autocomplete="off" enctype="multipart/form-data">
<div class="wrap">
	<p class="alignright">
		<input type="submit" class="button-primary" value="<?php _e('Save Changes' ,'mymail') ?>" />
	</p>
<div class="icon32" id="icon-options-general"><br></div>
<h2><?php _e('Newsletter Settings', 'mymail')?></h2>
<br>
<?php
		
		$active = count(mymail_get_active_campaigns());
		
		$lists = get_terms( 'newsletter_lists', array('hide_empty' => false) );
		$templatefiles = $mymail_templates->get_files(mymail_option('default_template'));

		if($active) echo '<div class="error"><p>'.sprintf(_n('%d campaign is active. You should pause it before you change the settings!', '%d campaigns are active. You should pause them before you change the settings!', $active, 'mymail'), $active).'</p></div>';
?>
	<?php wp_nonce_field( 'mymail_nonce', 'mymail_nonce', false ); ?>
	<?php settings_fields( 'newsletter_settings' ); ?>
	<?php do_settings_sections( 'newsletter_settings' ); ?>
	
	<?php
	$sections = array(
			'general' => __('General' ,'mymail'),
			'frontend' => __('Frontend' ,'mymail'),
			'subscribers' => __('Subscribers' ,'mymail'),
			'forms' => __('Forms' ,'mymail'),
			'texts' => __('Texts' ,'mymail'),
			'tags' => __('Tags' ,'mymail'),
			'delivery' => __('Delivery' ,'mymail'),
			'cron' => __('Cron' ,'mymail'),
			'capabilities' => __('Capabilities' ,'mymail'),
			'bounce' => __('Bouncing' ,'mymail'),
			'authentication' => __('Authentication' ,'mymail'),
			'purchasecode' => __('Purchasecode' ,'mymail'),
	);
		
	if(!current_user_can('mymail_manage_capabilities')) unset($sections['capabilities']);
	if(get_option('mymail_purchasecode_disabled')) unset($sections['purchasecode']);
	
	$extra_sections = apply_filters('mymail_setting_sections', array());
	
	?>
	<div id="mainnav" class="nav-tab-wrapper hide-if-no-js">
	<?php foreach($sections as $id => $name){ ?>
		<a class="nav-tab" href="#<?php echo $id; ?>"><?php echo $name; ?></a>
	<?php }?>
	<?php foreach($extra_sections as $id => $name){ ?>
		<a class="nav-tab" href="#<?php echo $id; ?>"><?php echo $name; ?></a>
	<?php }?>
		<?php do_action('mymail_settings_tabs') ?>
	</div>
	
	<div id="tab-general" class="tab">
	<table class="form-table">
		
		<tr valign="top">
			<th scope="row"><?php _e('From Name' ,'mymail') ?> *</th>
			<td><input type="text" name="mymail_options[from_name]" value="<?php echo esc_attr(mymail_option('from_name')); ?>" class="regular-text"> <span class="description"><?php _e('The sender name which is displayed in the from field' ,'mymail') ?></span></td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('From Address' ,'mymail') ?> *</th>
			<td><input type="text" name="mymail_options[from]" value="<?php echo esc_attr(mymail_option('from')); ?>" class="regular-text"> <span class="description"><?php _e('The sender email address. Force your recievers to whitelabel this email address.' ,'mymail') ?></span></td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Reply To Address' ,'mymail') ?> *</th>
			<td><input type="text" name="mymail_options[reply_to]" value="<?php echo esc_attr(mymail_option('reply_to')); ?>" class="regular-text"> <span class="description"><?php _e('The address users can reply to' ,'mymail') ?></span></td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Default Template' ,'mymail') ?> *</th>
			<td><p><select name="mymail_options[default_template]" class="postform">
			<?php
				$templates = $mymail_templates->get_templates();
				$selected = mymail_option('default_template');	
				foreach($templates as $slug => $data){
			?>
				<option value="<?php echo $slug ?>"<?php if($slug == $selected) echo " selected";?>><?php echo esc_attr($data['name'])?></option>
			<?php
				}
			?>
			</select> <a href="edit.php?post_type=newsletter&page=mymail_templates"><?php _e('show Templates', 'mymail'); ?></a> | <a href="http://rxa.li/mymailtemplates" class="external"><?php _e('get more' ,'mymail') ?></a>
			</p></td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Send delay' ,'mymail') ?> *</th>
			<td><input type="text" name="mymail_options[send_offset]" value="<?php echo esc_attr(mymail_option('send_offset')); ?>" class="small-text"> <span class="description"><?php _e('The default delay in minutes for sending campaigns.' ,'mymail') ?></span></td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Embed Images' ,'mymail') ?> *</th>
			<td><label><input type="checkbox" name="mymail_options[embed_images]" value="1" <?php if(mymail_option('embed_images')) echo ' checked'; ?>> <?php _e('Embed images in the mail' ,'mymail') ?></label> </td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Post List Count' ,'mymail') ?></th>
			<td><input type="text" name="mymail_options[post_count]" value="<?php echo esc_attr(mymail_option('post_count')); ?>" class="small-text"> <span class="description"><?php _e('Number of posts or images displayed at once in the editbar.' ,'mymail') ?></span></td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Use MyMail for system mails' ,'mymail') ?></th>
			<td><label><input type="checkbox" name="mymail_options[system_mail]" value="1" <?php if(mymail_option('system_mail')) echo ' checked'; ?>> <?php _e('Use the notification template for all outgoing WordPress mails' ,'mymail') ?></label>
			<br>&nbsp;&nbsp;<?php _e('use', 'mymail'); ?><select name="mymail_options[system_mail_template]">
			<?php 
				$selected = mymail_option('system_mail_template', 'notification.html');
				foreach($templatefiles as $slug => $filedata){
					if($slug == 'index.html') continue;
			?>
				<option value="<?php echo $slug ?>"<?php selected($slug == $selected) ?>><?php echo esc_attr($filedata['label'])?> (<?php echo $slug ?>)</option>
			<?php
				}
			?>
			</select>
			</td>
		</tr>
		<?php 
			$geoip = mymail_option('trackcountries');
			$geoipcity = mymail_option('trackcities');
		?>
		<tr valign="top">
			<th scope="row"><?php _e('Track Geolocation' ,'mymail') ?>
			<div class="loading geo-ajax-loading"></div></th>
			<td>
			<label><input type="checkbox" id="mymail_geoip" name="mymail_options[trackcountries]" value="1" <?php checked($geoip); ?>> <?php _e('Track Countries in Campaigns' ,'mymail') ?></label>
			<p><button id="load_country_db" class="button-primary" data-type="country" <?php disabled(!$geoip); ?>><?php (is_file(mymail_option('countries_db'))) ? _e('Update Country Database', 'mymail') : _e('Load Country Database', 'mymail'); ?></button> <?php _e('or', 'mymail'); ?> <a id="upload_country_db_btn" href="#"><?php _e('upload file', 'mymail'); ?></a>
			</p>
			<p id="upload_country_db" class="hidden">
				<input type="file" name="country_db_file"> <input type="submit" class="button" value="<?php _e('Upload' ,'mymail') ?>" />
				<br><span class="description"><?php _e('upload the GeoIPv6.dat you can find in the package here:', 'mymail'); ?> <a href="http://geolite.maxmind.com/download/geoip/database/GeoIPv6.dat.gz">http://geolite.maxmind.com/download/geoip/database/GeoIPv6.dat.gz</a></span>
			</p>

			<input id="country_db_path" type="hidden" name="mymail_options[countries_db]" class="widefat" value="<?php echo mymail_option('countries_db') ?>" placeholder="<?php echo MYMAIL_UPLOAD_DIR.'/GeoIPv6.dat'?>">
			<label><input type="checkbox" id="mymail_geoipcity" name="mymail_options[trackcities]" value="1" <?php checked($geoipcity); ?><?php disabled(!$geoip); ?>> <?php _e('Track Cities in Campaigns' ,'mymail') ?></label>
			<p><button id="load_city_db" class="button-primary" data-type="city" <?php disabled(!$geoipcity); ?>><?php (is_file(mymail_option('cities_db'))) ? _e('Update City Database', 'mymail') : _e('Load City Database', 'mymail'); ?></button> <?php _e('or', 'mymail'); ?> <a id="upload_city_db_btn" href="#"><?php _e('upload file', 'mymail'); ?></a>
			</p>
			<p id="upload_city_db" class="hidden">
				<input type="file" name="city_db_file"> <input type="submit" class="button" value="<?php _e('Upload' ,'mymail') ?>" />
				<br><span class="description"><?php _e('upload the GeoLiteCity.dat you can find in the package here:', 'mymail'); ?> <a href="http://geolite.maxmind.com/download/geoip/database/GeoLiteCity.dat.gz">http://geolite.maxmind.com/download/geoip/database/GeoLiteCity.dat.gz</a></span>
			</p>
			<p class="description"><?php _e('The city DB is about 12 MB. It can take a while to load it', 'mymail'); ?></p>
			<input id="city_db_path" type="hidden" name="mymail_options[cities_db]" class="widefat" value="<?php echo mymail_option('cities_db') ?>" placeholder="<?php echo MYMAIL_UPLOAD_DIR.'/GeoIPCity.dat'?>">
			
			</td>
		</tr>
		<?php if($geoip && is_file(mymail_option('countries_db'))) : ?>
		<tr valign="top">
			<th scope="row"></th>
			<td>
			<p class="description"><?php _e('If you don\'t find your country down below the geo database is missing or corrupt', 'mymail') ?></p>
			<strong><?php _e('Your IP', 'mymail') ?>:</strong> <?php echo mymail_get_ip()?><br>
			<strong><?php _e('Your country', 'mymail') ?>:</strong> <?php echo mymail_ip2Country('', 'name')?><br>&nbsp;&nbsp;<strong><?php _e('Last update', 'mymail') ?>: <?php echo date(get_option('date_format').' '.get_option('time_format'), get_option('mymail_countries')+get_option('gmt_offset')*3600)?> </strong><br>
		<?php if($geoipcity && is_file(mymail_option('cities_db'))) : ?>
			<strong><?php _e('Your city', 'mymail') ?>:</strong> <?php echo mymail_ip2City('', 'city')?><br>&nbsp;&nbsp;<strong><?php _e('Last update', 'mymail') ?>:<?php echo date(get_option('date_format').' '.get_option('time_format'), get_option('mymail_cities')+get_option('gmt_offset')*3600)?></strong>
		<?php endif; ?>
			<p class="description">This product includes GeoLite data created by MaxMind, available from <a href="http://www.maxmind.com">http://www.maxmind.com</a></p>
			</td>
		</tr>
		<?php
			endif;
		?>
	</table>
	<p class="description">* <?php _e('can be changed in each campaign' ,'mymail') ?></p>
		<?php if (get_bloginfo('language') != 'en-US' && 'translator string' !== _x( 'translator string', 'Translators: put your personal info here to display it on the settings page. Leave blank for no info', 'mymail' ) ) : ?>
	<table class="form-table">
		<tr valign="top">
			<th scope="row">&nbsp;</th>
			<td class="alignright"><?php echo sprintf(__('This plugin has been translated by %s', 'mymail'), _x( 'translator string', 'Translators: put your personal info here to display it on the settings page. Leave blank for no info', 'mymail' )); ?>
			</td>
		</tr>
	</table>
		<?php endif; ?>
	</div>
	
	<div id="tab-frontend" class="tab">
	<table class="form-table">
	 
		<tr valign="top">
			<th scope="row"><?php _e('Newsletter Homepage' ,'mymail') ?></th>
			<td><select name="mymail_options[homepage]" class="postform">
				<option value="0"><?php _e('Choose' ,'mymail') ?></option>
			<?php
				$pages = get_pages();
				$selected = mymail_option('homepage');	
				foreach($pages as $page){
			?>
				<option value="<?php echo $page->ID ?>"<?php if($page->ID == $selected) echo " selected";?>><?php echo esc_attr($page->post_title)?></option>
			<?php
				}
			?>
			</select>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Pagination' ,'mymail') ?></th>
			<td><label><input type="checkbox" name="mymail_options[frontpage_pagination]" value="1" <?php if(mymail_option('frontpage_pagination')) echo ' checked'; ?>> <?php _e('Allow users to view the next/last newsletters' ,'mymail') ?></label>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Share Button' ,'mymail') ?></th>
			<td><label><input type="checkbox" name="mymail_options[share_button]" value="1" <?php if(mymail_option('share_button')) echo ' checked'; ?>> <?php _e('Offer share option for your customers' ,'mymail') ?></label>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Services' ,'mymail') ?></th>
			<td><?php
				
				include MYMAIL_DIR . '/includes/social_services.php';
				$services = mymail_option('share_services', array());
				
				foreach($mymail_social_services as $service => $data){
			?>
				<label><input type="checkbox" name="mymail_options[share_services][]" value="<?php echo $service?>" <?php if(in_array($service, $services)) echo ' checked'; ?>> <?php echo $data['name']; ?></label> <br>
			<?php
				}
			?>
			</td>
		</tr>
		
	</table>
	</div>
	
	<div id="tab-subscribers" class="tab">
	<table class="form-table">
		<tr valign="top">
			<th scope="row"><?php _e('Double-Opt-In' ,'mymail') ?></th>
			<td><label><input type="checkbox" name="mymail_options[double_opt_in]" id="double-opt-in" value="1" <?php if(mymail_option('double_opt_in')) echo ' checked'; ?>> <?php _e('new subscribers must confirm their subscription' ,'mymail') ?></label> </td>
		</tr>
	</table>
	<div id="double-opt-in-field" <?php if(!mymail_option('double_opt_in')) echo ' style="display:none"';?>>
	<table class="form-table">
		<tr valign="top">
			<th scope="row"><?php _e('Resend Confirmation' ,'mymail') ?></th>
			<td><label><input type="checkbox" name="mymail_options[subscription_resend]" value="1" <?php if(mymail_option('subscription_resend')) echo ' checked'; ?>></label> <?php echo sprintf(__('resend confirmation %1$s times with a delay of %2$s hours if user hasn\'t confirmed the subscription' ,'mymail'), '<input type="text" name="mymail_options[subscription_resend_count]" value="'.esc_attr(mymail_option('subscription_resend_count')).'" class="small-text">', '<input type="text" name="mymail_options[subscription_resend_time]" value="'.esc_attr(mymail_option('subscription_resend_time')).'" class="small-text">') ?> </td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Subscription' ,'mymail') ?></th>
			<td>
			<div class="mymail_text"><label for="mymail_text_subscription_subject"><?php _e('Subject', 'mymail'); ?>:</label> <input type="text" id="mymail_text_subscription_subject" name="mymail_options[text][subscription_subject]" value="<?php echo esc_attr(mymail_text('subscription_subject')); ?>" class="regular-text"></div>
			<div class="mymail_text"><label for="mymail_text_subscription_headline"><?php _e('Headline', 'mymail'); ?>:</label> <input type="text" id="mymail_text_subscription_headline" name="mymail_options[text][subscription_headline]" value="<?php echo esc_attr(mymail_text('subscription_headline')); ?>" class="regular-text"></div>
			<div class="mymail_text"><label for="mymail_text_subscription_link"><?php _e('Linktext', 'mymail'); ?>:</label> <input type="text" id="mymail_text_subscription_link" name="mymail_options[text][subscription_link]" value="<?php echo esc_attr(mymail_text('subscription_link')); ?>" class="regular-text"></div>
			<div class="mymail_text"><label for="mymail_text_subscription_text"><?php _e('Text', 'mymail'); ?>:</label> <textarea id="mymail_text_subscription_text" name="mymail_options[text][subscription_text]" rows="10" cols="50" class="large-text"><?php echo esc_attr(mymail_text('subscription_text')); ?></textarea> <span class="description"><?php echo sprintf(__('The text new subscribers get when Double-Opt-In is selected. Use %s for the link placeholder. Basic HTML is allowed' ,'mymail'),'<code>{link}</code>'); ?></span></div>
			</td>
		</tr>
	</table>
	<table class="form-table">
		<tr valign="top">
			<th scope="row"><?php _e('Attach vCard' ,'mymail') ?></th>
			<td><label><input type="checkbox" name="mymail_options[vcard]" id="vcard" value="1" <?php if(mymail_option('vcard')) echo ' checked'; ?>> <?php _e('attach vCard to all confirmation mails' ,'mymail') ?></label> </td>
		</tr>
	</table>
			<div id="v-card-field" <?php if(!mymail_option('vcard')) echo ' style="display:none"';?>>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php _e('vCard file name', 'mymail'); ?></th>
					<td><input type="text" name="mymail_options[vcard_filename]" value="<?php echo esc_attr(mymail_option('vcard_filename', 'vCard.vcf')); ?>" class="regular-text"></td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e('vCard content', 'mymail') ?>
					<p class="description"><?php echo sprintf(__('paste in your vCard content. You can use %s to generate your personal vcard' ,'mymail'),'<a href="http://vcardmaker.com/" class="external">vcardmaker.com</a>'); ?></p>
					</th>
					<td><?php $vcard = mymail_option('vcard_content'); if(empty($vcard)) $vcard =  $this->get_vcard(); ?><textarea name="mymail_options[vcard_content]" rows="10" cols="50" class="large-text code"><?php echo esc_attr($vcard); ?></textarea></td>
				</tr>
			</table>
			</div>
	</div>
	<table class="form-table">
		<tr valign="top">
			<th scope="row"><?php _e('Merge Lists' ,'mymail') ?></th>
			<td><label><input type="checkbox" name="mymail_options[merge_lists]" value="1" <?php checked(mymail_option('merge_lists')) ?>> <?php _e('keep selected lists on signup' ,'mymail') ?></label>
			<p class="description"><?php _e('Keep the lists if user has subscribed before. Uncheck to subscribe users only to lists from the form they signed up to' ,'mymail') ?></p>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Save Subscriber IP' ,'mymail') ?></th>
			<td><label><input type="checkbox" name="mymail_options[track_users]" value="1" <?php checked(mymail_option('track_users')) ?>> <?php _e('Save IP address and time of new subscribers' ,'mymail') ?></label>
			<p class="description"><?php _e('In some countries it\'s required to save the IP address and the sign up time for legal reasons. Please add a note in your privacy policy if you save users data' ,'mymail') ?></p>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Custom Fields' ,'mymail') ?>:
				<p class="description"><?php _e('Custom field tags are individual tags for each subscriber. You can ask for them on subscription and/or make it a required field.', 'mymail'); ?></p>
				<p class="description"><?php _e('You have to enable Custom fields for each form:', 'mymail'); ?> <a href="#forms"><?php _e('Forms', 'mymail'); ?></a></p>
			</th>
			<td>
				<div class="customfields">
			<?php 
				if($fields = mymail_option('custom_field')){
					foreach($fields as $id => $data){
					 ?>
					<div class="customfield">
					<a class="customfield-move-up" title="<?php _e('move up', 'mymail'); ?>">&#9650;</a>
					<a class="customfield-move-down" title="<?php _e('move down', 'mymail'); ?>">&#9660;</a>
					<div><span><?php _e('Field Name', 'mymail'); ?>:</span><label><input type="text" name="mymail_options[custom_field][<?php echo $id?>][name]" value="<?php echo esc_attr($data['name'])?>" class="regular-text customfield-name"></label></div>
					<div><span><?php _e('Tag', 'mymail'); ?>:</span><span><code>{<?php echo $id?>}</code></span></div>
					<div><span><?php _e('Type', 'mymail'); ?>:</span><select class="customfield-type" name="mymail_options[custom_field][<?php echo $id?>][type]">
					<?php 
						$types = array(
							'textfield' => __('Textfield', 'mymail'),
							'dropdown' => __('Dropdown Menu', 'mymail'),
							'radio' => __('Radio Buttons', 'mymail'),
							'checkbox' => __('Checkbox', 'mymail')
						);
						foreach($types as $value => $name){
							echo '<option value="'.$value.'" '.selected($data['type'], $value, false).'>'.$name.'</option>';
							
						}
						
					?> 
					</select></div>
						<ul class="customfield-additional customfield-dropdown customfield-radio" <?php if(in_array($data['type'], array('dropdown', 'radio'))) echo ' style="display:block"';?>>
							<li>
								<ul class="customfield-values">
							<?php 
								$values = !empty($data['values']) ? $data['values'] : array('');
								foreach($values as $value){
							?>
								<li><span>&nbsp;</span> <span class="customfield-value-box"><input type="text" name="mymail_options[custom_field][<?php echo $id?>][values][]" class="regular-text customfield-value" value="<?php echo $value; ?>"> <label><input type="radio" name="mymail_options[custom_field][<?php echo $id?>][default]" value="<?php echo $value ?>" title="<?php _e('this field is selected by default', 'mymail'); ?>" <?php if(isset($data['default'])) checked($data['default'], $value)?><?php if(!in_array($data['type'], array('dropdown', 'radio'))) echo ' disabled'?>> <?php _e('default', 'mymail'); ?></label> &nbsp; <a class="customfield-value-remove" title="<?php _e('remove field', 'mymail'); ?>">&#10005;</a></span></li>
							<?php } ?>
								</ul>
							<span>&nbsp;</span> <a class="customfield-value-add"><?php _e('add field', 'mymail'); ?></a>
							</li>
						</ul>
						<div class="customfield-additional customfield-checkbox" <?php if(in_array($data['type'], array('checkbox'))) echo ' style="display:block"';?>>
							<span>&nbsp;</span> <label><input type="checkbox" name="mymail_options[custom_field][<?php echo $id?>][default]" value="1" title="<?php _e('this field is selected by default', 'mymail'); ?>" <?php if(isset($data['default'])) checked($data['default'], true)?> <?php if(!in_array($data['type'], array('checkbox'))) echo ' disabled'?>> <?php _e('checked by default', 'mymail'); ?></label>
						</div>
						<a class="customfield-remove"><?php _e('remove field', 'mymail'); ?></a>
						<br>
					</div>
					 <?php
					}
				}
			?>
				</div>
				<input type="button" value="<?php _e('add' ,'mymail') ?>" class="button" id="mymail_add_field">
			</td>
		</tr>
	</table>
	<h4><?php _e('WordPress Users' ,'mymail') ?></h4>
	<table class="form-table">
		<tr valign="top">
			<th scope="row"><?php _e('Registered Users' ,'mymail') ?></th>
			<td>
			<?php if(get_option('users_can_register')) : ?>
			<label><input type="checkbox" name="mymail_options[register_signup]" value="1" <?php checked(mymail_option('register_signup')) ?> class="users-register" data-section="users-register_signup"> <?php _e('new WordPress users can choose to sign up on the register page' ,'mymail') ?></label>
			<?php else : ?>
			<p class="description"><?php echo sprintf(__('allow %s to your blog to enable this option' ,'mymail'), '<a href="options-general.php">'.__('users to subscribe' ,'mymail').'</a>');?></p>	
			<?php endif; ?>
			</td>
		</tr>
	</table>
	<div id="users-register_signup" <?php if(!get_option('users_can_register') || !mymail_option('register_signup')) echo ' style="display:none"'; ?>>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"></th>
				<td>
				<label><input type="checkbox" name="mymail_options[register_signup_checked]" value="1" <?php checked(mymail_option('register_signup_checked')) ?>> <?php _e('checked by default' ,'mymail') ?></label>
				<p class="description"><?php _e('Subscribe them to these lists:', 'mymail'); ?></p>	
				<?php
				if(!empty($lists)){
					foreach( $lists as $list){
					?>
					<label><input type="checkbox" name="mymail_options[register_signup_lists][]" value="<?php echo $list->slug ?>" <?php if(in_array($list->slug, mymail_option('register_signup_lists', array()))) echo ' checked'; ?>> <?php echo $list->name?></label><br>
					<?php 
					}
				}else{
					?>
					<p class="description"><?php _e('No Lists found!' ,'mymail'); echo ' <a href="edit-tags.php?taxonomy=newsletter_lists&post_type=newsletter">'.__('Create a List now' ,'mymail').'</a>';?></p>	
					<?php 
				}
				?>
				</td>
			</tr>
		</table>
	</div>

	<table class="form-table">
		<tr valign="top">
			<th scope="row"><?php _e('New Comments' ,'mymail') ?></th>
			<td><label><input type="checkbox" name="mymail_options[register_comment_form]" value="1" <?php checked(mymail_option('register_comment_form')) ?> class="users-register" data-section="users-register_comment_form"> <?php _e('allow users to signup on the comment form if they are currently not subscribed to any list', 'mymail'); ?></label>
			</td>
		</tr>
	</table>
	<div id="users-register_comment_form" <?php if(!mymail_option('register_comment_form')) echo ' style="display:none"'; ?>>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"></th>
				<td>
				<label><input type="checkbox" name="mymail_options[register_comment_form_checked]" value="1" <?php checked(mymail_option('register_comment_form_checked')) ?>> <?php _e('checked by default' ,'mymail') ?></label>
				<p><?php _e('sign up only if comment is', 'mymail'); ?><br>&nbsp;&nbsp;
				
				<label><input type="checkbox" name="mymail_options[register_comment_form_status][]" value="1" <?php checked(in_array('1', mymail_option('register_comment_form_status', array())), true) ?>> <?php _e('approved', 'mymail'); ?></label>
				<label><input type="checkbox" name="mymail_options[register_comment_form_status][]" value="0" <?php checked(in_array('0', mymail_option('register_comment_form_status', array())), true) ?>> <?php _e('not approved', 'mymail'); ?></label>
				<label><input type="checkbox" name="mymail_options[register_comment_form_status][]" value="spam" <?php checked(in_array('spam', mymail_option('register_comment_form_status', array())), true) ?>> <?php _e('spam', 'mymail'); ?></label>
				</p>
				<p class="description"><?php _e('Subscribe them to these lists:', 'mymail'); ?></p>
				<?php
				if(!empty($lists)){
					foreach( $lists as $list){
					?>
					<label><input type="checkbox" name="mymail_options[register_comment_form_lists][]" value="<?php echo $list->slug ?>" <?php if(in_array($list->slug, mymail_option('register_comment_form_lists', array()))) echo ' checked'; ?>> <?php echo $list->name?></label><br>
					<?php 
					}
				}else{
					?>
					<p class="description"><?php _e('No Lists found!' ,'mymail'); echo ' <a href="edit-tags.php?taxonomy=newsletter_lists&post_type=newsletter">'.__('Create a List now' ,'mymail').'</a>';?></p>	
					<?php 
				}
				?></td>
				</td>
			</tr>
		</table>
	</div>

	<table class="form-table">
		<tr valign="top">
			<th scope="row"><?php _e('Others' ,'mymail') ?></th>
			<td><label><input type="checkbox" name="mymail_options[register_other]" value="1" <?php checked(mymail_option('register_other')) ?> class="users-register" data-section="users-register_other"> <?php _e('add people who are added via the backend or any third party plugin', 'mymail'); ?></label>
			</td>
		</tr>
	</table>
	<div id="users-register_other" <?php if(!mymail_option('register_other')) echo ' style="display:none"'; ?>>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"></th>
				<td>
				<label><input type="checkbox" name="mymail_options[register_other_confirmation]" value="1" <?php checked(mymail_option('register_other_confirmation')) ?>> <?php _e('send confirmation if double-opt-in is enabled', 'mymail'); ?></label>
				<p class="description"><?php _e('Subscribe them to these lists:', 'mymail'); ?></p>
				<?php
				if(!empty($lists)){
					foreach( $lists as $list){
					?>
					<label><input type="checkbox" name="mymail_options[register_other_lists][]" value="<?php echo $list->slug ?>" <?php if(in_array($list->slug, mymail_option('register_other_lists', array()))) echo ' checked'; ?>> <?php echo $list->name?></label><br>
					<?php 
					}
				}else{
					?>
					<p class="description"><?php _e('No Lists found!' ,'mymail'); echo ' <a href="edit-tags.php?taxonomy=newsletter_lists&post_type=newsletter">'.__('Create a List now' ,'mymail').'</a>';?></p>	
					<?php 
				}
				?></td>
				</td>
			</tr>
		</table>
	</div>

	</div>
	
	<div id="tab-forms" class="tab">
	<table class="form-table">
		<tr valign="top">
			<th scope="row"><?php _e('AJAX Form' ,'mymail') ?> </th>
			<td><label><input type="checkbox" name="mymail_options[ajax_form]" value="1" <?php checked(mymail_option('ajax_form'), true); ?>> <?php _e('use AJAX based forms' ,'mymail') ?></label> </td>
		</tr>
		<?php 
				$forms = mymail_option('forms');
		?>
		<tr valign="top" class="formtd">
		
			<?php $form = array_shift($forms);
			
			
				$fields = array(
					'email' => mymail_text('email'),
					'firstname' => mymail_text('firstname'),
					'lastname' => mymail_text('lastname'),
				);
				if ($customfields = mymail_option('custom_field')) {
					foreach ($customfields as $field => $data) {
						$fields[$field] = $data['name'];
					}
				}
				
			?>
		
			<th scope="row"><input type="text" name="mymail_options[forms][0][name]" value="<?php echo (isset($form['name'])) ? $form['name'] : __('Default Form' ,'mymail') ?>" placeholder="<?php _e('Name your form', 'mymail'); ?>"><br>
			<a href="#" class="mymail_remove_form hidden"><?php _e('remove this form', 'mymail'); ?></a>
			</th>
			<td><div class="form">
				<table class="nested">
				<tr><td width="40%">shortcode</td><td width="60%"><code>[newsletter_signup_form]</code><input type="hidden" class="mymail_form_id" name="mymail_options[forms][0][id]" value="0"></td></tr>
				<tr><td><?php _e('sorting', 'mymail'); ?><p class="howto"><?php _e('Click and drag to reorder fields. Uncheck to disable field for this form. Required fields must be included in the form.', 'mymail'); ?><br><a href="#subscribers"><?php _e('add Custom Fields', 'mymail'); ?></a></p></td><td>
				<ul class="form-order">
				<?php 
				foreach(array_unique($form['order']) as $field){
					if(!isset($fields[$field])) continue;
					?>
				<li>
				<input type="<?php echo $field == 'email' ? 'hidden' : 'checkbox'?>" name="mymail_options[forms][0][order][]" class="form-order-check" value="<?php echo $field ?>" checked> <?php echo $fields[$field] ?>
				<span class="alignright"><input type="<?php echo $field == 'email' ? 'hidden' : 'checkbox'?>" name="mymail_options[forms][0][required][]" class="form-order-check-required" value="<?php echo $field ?>" <?php if(in_array($field, $form['required'])) echo 'checked' ?>> <?php _e('required', 'mymail'); ?></span>
				</li>
					<?php
				}	
				foreach($fields as $field => $name){
					if(in_array($field, $form['order'])) continue;
					?>
				<li class="inactive">
				<input type="checkbox" name="mymail_options[forms][0][order][]" class="form-order-check" value="<?php echo $field ?>"> <?php echo $name ?>
				<span class="alignright"><input type="checkbox" name="mymail_options[forms][0][required][]" class="form-order-check-required" value="<?php echo $field ?>" <?php if(in_array($field, $form['required'])) echo 'checked' ?>> <?php _e('required', 'mymail'); ?></span>
 				</li>
					<?php
				}	
				?>
				</ul>
				</td></tr>
				<tr><td><?php _e('offer lists', 'mymail'); ?></td><td><label><input type="checkbox" name="mymail_options[forms][0][userschoice]" class="mymail_userschoice" value="1" <?php if(isset($form['userschoice'])) if($form['userschoice']) echo ' checked'; ?>> <?php _e('users decide which list they subscribe to', 'mymail'); ?></label><br> &nbsp; <label> <input type="checkbox" name="mymail_options[forms][0][dropdown]" class="mymail_dropdown" value="1" <?php if(isset($form['dropdown'])) if($form['dropdown']) echo ' checked'; ?><?php if(!isset($form['userschoice']) || !$form['userschoice']) echo ' disabled'; ?>> <?php _e('show drop down instead of check boxes', 'mymail'); ?></label>
				</td></tr>
				<tr><td class="mymail_userschoice_td">
				<span<?php if(isset($form['userschoice']) && $form['userschoice']) { echo ' style="display:none"';} ?>><?php _e('subscribe new users to', 'mymail'); ?></span>
				<span<?php if(!isset($form['userschoice']) || !$form['userschoice']) { echo ' style="display:none"';} ?>><?php _e('users can subscribe to', 'mymail'); ?></span>
				</td><td>
				
			<?php
			
			
			if(!empty($lists)){
				foreach( $lists as $list){
				?>
				<label><input type="checkbox" name="mymail_options[forms][0][lists][]" value="<?php echo $list->slug ?>" <?php if(isset($form['lists'])) if(in_array($list->slug, $form['lists'])) echo ' checked'; ?>> <?php echo $list->name?></label><br>
				<?php 
				}
			}else{
				?>
				<p class="description"><?php _e('No Lists found!' ,'mymail'); echo ' <a href="edit-tags.php?taxonomy=newsletter_lists&post_type=newsletter">'.__('Create a List now' ,'mymail').'</a>';?></p>	
				<?php 
			}
			?>
				</td></tr>
				<tr><td><?php _e('new lists', 'mymail'); ?></td><td><label><input type="checkbox" name="mymail_options[forms][0][addlists]" value="1" <?php if(isset($form['addlists']) && $form['addlists']) echo ' checked'; ?>> <?php _e('assign new lists automatically to this form', 'mymail'); ?></label></td></tr>
				<tr><td><?php _e('inline labels', 'mymail'); ?></td><td><label><input type="checkbox" name="mymail_options[forms][0][inline]" value="1" <?php if(isset($form['inline']) && $form['inline']) echo ' checked'; ?>> <?php _e('place labels inside input fields', 'mymail'); ?></label></td></tr>
				<tr><td><?php _e('pre-fill fields', 'mymail'); ?></td><td><label><input type="checkbox" name="mymail_options[forms][0][prefill]" value="1" <?php if(isset($form['prefill']) && $form['prefill']) echo ' checked'; ?>> <?php _e('fill fields with known data if user is logged in', 'mymail'); ?></label></td></tr>
				<tr><td><?php _e('used template file', 'mymail'); ?></td><td>
					<select name="mymail_options[forms][0][template]">
					<?php 
						$selected = isset($form['template']) ? $form['template'] : 'notification.html';
						foreach($templatefiles as $slug => $filedata){
							if($slug == 'index.html') continue;
					?>
						<option value="<?php echo $slug ?>"<?php selected($slug == $selected) ?>><?php echo esc_attr($filedata['label'])?> (<?php echo $slug ?>)</option>
					<?php
						}
					?>
					</select>
				</td></tr>
				<tr><td><?php _e('redirect after submit', 'mymail'); ?></td><td><input type="text" name="mymail_options[forms][0][redirect]" class="widefat" value="<?php if(isset($form['redirect'])) echo $form['redirect']; ?>" placeholder="http://www.example.com" ></td></tr>
				
				<?php $embedcode = '<iframe width="%s" height="%s" allowTransparency="true" frameborder="0" scrolling="no" style="border:none" src="'.MYMAIL_URI.'/form.php?id='.$form['id'].'%s"></iframe>';?>
				
				<tr><td><?php _e('embed form', 'mymail'); ?></td><td>
					<a class="embed-form" href="#"><?php _e('embed this form on another site', 'mymail'); ?></a>
				</td></tr>
				<tr class="embed-form-options"><td colspan="2">
					<p>
					<?php _e('iframe version', 'mymail'); ?><br>
					<label><?php _e('width', 'mymail'); ?>: <input type="text" class="small-text embed-form-input" value="100%"></label>
					<label><?php _e('height', 'mymail'); ?>: <input type="text" class="small-text embed-form-input" value="500"></label>
					<label title="<?php _e('check this option to include the style.css of your theme into the form', 'mymail'); ?>"><input type="checkbox" value="1" class="embed-form-input" checked> <?php _e('include themes style.css', 'mymail'); ?></label>
					<textarea class="widefat code embed-form-output" data-embedcode="<?php echo esc_attr($embedcode) ?>"></textarea></p>
					<p><?php _e('HTML version', 'mymail'); ?> <span class="description"><?php _e('save before you use it!', 'mymail'); ?></span><textarea class="widefat code form-output"><?php echo mymail_form( 0, 100, true, 'extern' )?></textarea></p>
				</td></tr>
				</table>
			</div></td>
		</tr>
		<?php foreach($forms as $form) { ?>
		<tr valign="top" class="formtd">
		
		
			<th scope="row"><input type="text" name="mymail_options[forms][<?php echo $form['id']; ?>][name]" value="<?php echo (isset($form['name'])) ? $form['name'] : __('undefined' ,'mymail') ?>" placeholder="<?php _e('Name your form', 'mymail'); ?>"><br>
			<a href="#" class="mymail_remove_form"><?php _e('remove this form', 'mymail'); ?></a>
			</th>
			<td><div class="form">
				<table class="nested">
				<tr><td width="40%">shortcode</td><td width="60%"><code>[newsletter_signup_form id=<?php echo $form['id']; ?>]</code><input type="hidden" class="mymail_form_id" name="mymail_options[forms][<?php echo $form['id']; ?>][id]" value="<?php echo $form['id']; ?>"></td></tr>
				<tr><td><?php _e('sorting', 'mymail'); ?><p class="howto"><?php _e('Click and drag to reorder fields. Uncheck to disable field for this form. Required fields must be included in the form.', 'mymail'); ?><br><a href="#subscribers"><?php _e('add Custom Fields', 'mymail'); ?></a></p></td><td>
				<ul class="form-order">
				<?php 
				foreach($form['order'] as $field){
					if(!isset($fields[$field])) continue;
					?>
				<li>
				<input type="<?php echo $field == 'email' ? 'hidden' : 'checkbox'?>" name="mymail_options[forms][<?php echo $form['id']; ?>][order][]" class="form-order-check" value="<?php echo $field ?>" checked> <?php echo $fields[$field] ?>
				<span class="alignright"><input type="<?php echo $field == 'email' ? 'hidden' : 'checkbox'?>" name="mymail_options[forms][<?php echo $form['id']; ?>][required][]" class="form-order-check-required" value="<?php echo $field ?>" <?php if(in_array($field, $form['required'])) echo 'checked' ?>> <?php _e('required', 'mymail'); ?></span>
				</li>
					<?php
				}	
				foreach($fields as $field => $name){
					if(in_array($field, $form['order'])) continue;
					?>
				<li class="inactive">
				<input type="checkbox" name="mymail_options[forms][<?php echo $form['id']; ?>][order][]" class="form-order-check" value="<?php echo $field ?>"> <?php echo $name ?>
				<span class="alignright"><input type="checkbox" name="mymail_options[forms][<?php echo $form['id']; ?>][required][]" class="form-order-check-required" value="<?php echo $field ?>" <?php if(in_array($field, $form['required'])) echo 'checked' ?>> <?php _e('required', 'mymail'); ?></span>
 				</li>
					<?php
				}	
				?>
				</ul>
				</td></tr>
				<tr><td><?php _e('offer lists', 'mymail'); ?></td><td><label><input type="checkbox" name="mymail_options[forms][<?php echo $form['id']; ?>][userschoice]" class="mymail_userschoice" value="1" <?php if(isset($form['userschoice'])) if($form['userschoice']) echo ' checked'; ?>> <?php _e('users decide which list they subscribe to', 'mymail'); ?></label><br> &nbsp; <label> <input type="checkbox" name="mymail_options[forms][<?php echo $form['id']; ?>][dropdown]" class="mymail_dropdown" value="1" <?php if(isset($form['dropdown'])) if($form['dropdown']) echo ' checked'; ?><?php if(!isset($form['userschoice']) || !$form['userschoice']) echo ' disabled'; ?>> <?php _e('show drop down instead of check boxes', 'mymail'); ?></label>
				</td></tr>
				<tr><td class="mymail_userschoice_td">
				<span<?php if(isset($form['userschoice']) && $form['userschoice']) { echo ' style="display:none"';} ?>><?php _e('subscribe new users to', 'mymail'); ?></span>
				<span<?php if(!isset($form['userschoice']) || !$form['userschoice']) { echo ' style="display:none"';} ?>><?php _e('users can subscribe to', 'mymail'); ?></span>
				</td><td>
				
			<?php
			
			if(!empty($lists)){
				foreach( $lists as $list){
				?>
				<label><input type="checkbox" name="mymail_options[forms][<?php echo $form['id']; ?>][lists][]" value="<?php echo $list->slug ?>" <?php if(isset($form['lists'])) if(in_array($list->slug, $form['lists'])) echo ' checked'; ?>> <?php echo $list->name?></label><br>
				<?php 
				}
			}else{
				?>
				<p class="description"><?php _e('No Lists found!' ,'mymail'); echo ' <a href="edit-tags.php?taxonomy=newsletter_lists&post_type=newsletter">'.__('Create a List now' ,'mymail').'</a>';?></p>	
				<?php 
			}
			?>
				</td></tr>
				<tr><td><?php _e('new lists', 'mymail'); ?></td><td><label><input type="checkbox" name="mymail_options[forms][<?php echo $form['id']; ?>][addlists]" value="1" <?php if(isset($form['addlists']) && $form['addlists']) echo ' checked'; ?>> <?php _e('assign new lists automatically to this form', 'mymail'); ?></label></td></tr>
				<tr><td><?php _e('inline labels', 'mymail'); ?></td><td><label><input type="checkbox" name="mymail_options[forms][<?php echo $form['id']; ?>][inline]" value="1" <?php if(isset($form['inline']) && $form['inline']) echo ' checked'; ?>> <?php _e('place labels inside input fields', 'mymail'); ?></label></td></tr>
				<tr><td><?php _e('pre-fill fields', 'mymail'); ?></td><td><label><input type="checkbox" name="mymail_options[forms][<?php echo $form['id']; ?>][prefill]" value="1" <?php if(isset($form['prefill']) && $form['prefill']) echo ' checked'; ?>> <?php _e('fill fields with known data if user is logged in', 'mymail'); ?></label></td></tr>
				<tr><td><?php _e('used template file', 'mymail'); ?></td><td>
					<select name="mymail_options[forms][<?php echo $form['id']; ?>][template]">
					<?php 
						$selected = isset($form['template']) ? $form['template'] : 'notification.html';
						foreach($templatefiles as $slug => $filedata){
							if($slug == 'index.html') continue;
					?>
						<option value="<?php echo $slug ?>"<?php selected($slug == $selected) ?>><?php echo esc_attr($filedata['label'])?> (<?php echo $slug ?>)</option>
					<?php
						}
					?>
					</select>
				</td></tr>
				<tr><td><?php _e('redirect after submit', 'mymail'); ?></td><td><input type="text" name="mymail_options[forms][<?php echo $form['id']; ?>][redirect]" class="widefat" value="<?php if(isset($form['redirect'])) echo $form['redirect']; ?>" placeholder="http://www.example.com" ></td></tr>
				<?php $embedcode = '<iframe width="%s" height="%s" allowTransparency="true" frameborder="0" scrolling="no" style="border:none" src="'.MYMAIL_URI.'/form.php?id='.$form['id'].'%s"></iframe>';?>
				
				<tr><td><?php _e('embed form', 'mymail'); ?></td><td>
					<a class="embed-form" href="#"><?php _e('embed this form on another site', 'mymail'); ?></a>
				</td></tr>
				<tr class="embed-form-options"><td colspan="2">
					<p>
					<?php _e('iframe version', 'mymail'); ?><br>
					<label><?php _e('width', 'mymail'); ?>: <input type="text" class="small-text embed-form-input" value="100%"></label>
					<label><?php _e('height', 'mymail'); ?>: <input type="text" class="small-text embed-form-input" value="500"></label>
					<label title="<?php _e('check this option to include the style.css of your theme into the form', 'mymail'); ?>"><input type="checkbox" value="1" class="embed-form-input" checked> <?php _e('include themes style.css', 'mymail'); ?></label>
					<textarea class="widefat code embed-form-output" data-embedcode="<?php echo esc_attr($embedcode) ?>"></textarea></p>
					<p><?php _e('HTML version', 'mymail'); ?> <span class="description"><?php _e('save before you use it!', 'mymail'); ?></span><textarea class="widefat code form-output"><?php echo mymail_form( $form['id'], 100, true, 'extern' )?></textarea></p>
				</td></tr>
				</table>
			</div></td>
		</tr>
		
		<?php } ?>
		<tr valign="top">
			<th scope="row">&nbsp;</th>
			<td><input type="button" value="<?php _e('add form' ,'mymail') ?>" class="button" id="mymail_add_form"></td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Form CSS' ,'mymail') ?><p class="description"><?php _e('Define some CSS rules for the form' ,'mymail'); ?></p></th>
			<td><input type="hidden" name="mymail_options[form_css_hash]" value="<?php echo esc_attr(mymail_option('form_css_hash')); ?>"><textarea name="mymail_options[form_css]" rows="10" cols="50" class="large-text code"><?php echo esc_attr(mymail_option('form_css')); ?></textarea></td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Reset form CSS' ,'mymail') ?> </th>
			<td><label><input type="checkbox" name="mymail_reset_form_css" value="1"> <?php _e('check this box to reset the style to default value', 'mymail'); ?></label> </td>
		</tr>
	</table>
	</div>
	
	<div id="tab-texts" class="tab">
	<table class="form-table">
		<tr valign="top">
			<th scope="row"><?php _e('Subscription Form' ,'mymail') ?><p class="description"><?php _e('Define messages for the subscription form' ,'mymail'); ?>.<br><?php if(mymail_option('homepage')) echo sprintf(__('Some text can get defined on the %s as well' ,'mymail'), '<a href="post.php?post='.mymail_option('homepage').'&action=edit">Newsletter Homepage</a>'); ?></p></th>
			<td>
			<div class="mymail_text"><label><?php _e('Confirmation', 'mymail'); ?>:</label> <input type="text" name="mymail_options[text][confirmation]" value="<?php echo esc_attr(mymail_text('confirmation')); ?>" class="regular-text"></div>
			<div class="mymail_text"><label><?php _e('Successful', 'mymail'); ?>:</label> <input type="text" name="mymail_options[text][success]" value="<?php echo esc_attr(mymail_text('success')); ?>" class="regular-text"></div>
			<div class="mymail_text"><label><?php _e('Error Message', 'mymail'); ?>:</label> <input type="text" name="mymail_options[text][error]" value="<?php echo esc_attr(mymail_text('error')); ?>" class="regular-text"></div>
			<div class="mymail_text"><label><?php _e('Unsubscribe', 'mymail'); ?>:</label> <input type="text" name="mymail_options[text][unsubscribe]" value="<?php echo esc_attr(mymail_text('unsubscribe')); ?>" class="regular-text"></div>
			<div class="mymail_text"><label><?php _e('Unsubscribe Error', 'mymail'); ?>:</label> <input type="text" name="mymail_options[text][unsubscribeerror]" value="<?php echo esc_attr(mymail_text('unsubscribeerror')); ?>" class="regular-text"></div>
			<div class="mymail_text"><label><?php _e('Newsletter Sign up', 'mymail'); ?>:</label> <input type="text" name="mymail_options[text][newsletter_signup]" value="<?php echo esc_attr(mymail_text('newsletter_signup')); ?>" class="regular-text"></div>
			</td>
		</tr>
	</table>
	<table class="form-table">
		<tr valign="top">
			<th scope="row"><?php _e('Field Labels' ,'mymail') ?><p class="description"><?php _e('Define texts for the labels of forms. Custom field labels can be defined on the Subscribers tab' ,'mymail'); ?></p></th>
			<td>
			<div class="mymail_text"><label><?php _e('Email', 'mymail'); ?>:</label> <input type="text" name="mymail_options[text][email]" value="<?php echo esc_attr(mymail_text('email')); ?>" class="regular-text"></div>
			<div class="mymail_text"><label><?php _e('First Name', 'mymail'); ?>:</label> <input type="text" name="mymail_options[text][firstname]" value="<?php echo esc_attr(mymail_text('firstname')); ?>" class="regular-text"></div>
			<div class="mymail_text"><label><?php _e('Last Name', 'mymail'); ?>:</label> <input type="text" name="mymail_options[text][lastname]" value="<?php echo esc_attr(mymail_text('lastname')); ?>" class="regular-text"></div>
			<div class="mymail_text"><label><?php _e('Lists', 'mymail'); ?>:</label> <input type="text" name="mymail_options[text][lists]" value="<?php echo esc_attr(mymail_text('lists')); ?>" class="regular-text"></div>
			<div class="mymail_text"><label><?php _e('Submit Button', 'mymail'); ?>:</label> <input type="text" name="mymail_options[text][submitbutton]" value="<?php echo esc_attr(mymail_text('submitbutton')); ?>" class="regular-text"></div>
			<div class="mymail_text"><label><?php _e('Unsubscribe Button', 'mymail'); ?>:</label> <input type="text" name="mymail_options[text][unsubscribebutton]" value="<?php echo esc_attr(mymail_text('unsubscribebutton')); ?>" class="regular-text"></div>
			</td>
		</tr>
	</table>
	<table class="form-table">
		<tr valign="top">
			<th scope="row"><?php _e('Mail' ,'mymail') ?><p class="description"><?php _e('Define texts for the mails' ,'mymail'); ?></p></th>
			<td>
			<div class="mymail_text"><label><?php _e('Unsubscribe Link', 'mymail'); ?>:</label> <input type="text" name="mymail_options[text][unsubscribelink]" value="<?php echo esc_attr(mymail_text('unsubscribelink')); ?>" class="regular-text"></div>
			<div class="mymail_text"><label><?php _e('Webversion Link', 'mymail'); ?>:</label> <input type="text" name="mymail_options[text][webversion]" value="<?php echo esc_attr(mymail_text('webversion')); ?>" class="regular-text"></div>
			<div class="mymail_text"><label><?php _e('Forward Link', 'mymail'); ?>:</label> <input type="text" name="mymail_options[text][forward]" value="<?php echo esc_attr(mymail_text('forward')); ?>" class="regular-text"></div>
			</td>
		</tr>
	</table>
	</div>
	
	<div id="tab-tags" class="tab">
	<table class="form-table">
		<tr valign="top">
			<td class="tags" colspan="2">
			<p class="description"><?php echo sprintf(__('Tags are placeholder for your newsletter. You can set them anywhere in your newsletter template with the format %s. Custom field tags are induvidual for each subscriber.', 'mymail'), '<code>{tagname}</code>'); ?></p>
			<p class="description"><?php echo sprintf(__('You can set alternative content with %1$s which will be uses if %2$s is not defined. All unused tags will get removed in the final message', 'mymail'), '<code>{tagname|alternative content}</code>', '[tagname]'); ?></p>
			<?php $reserved = array('unsub', 'unsublink', 'webversion', 'webversionlink', 'forward', 'forwardlink', 'subject', 'preheader', 'headline', 'content', 'link', 'email', 'emailaddress', 'firstname', 'lastname', 'fullname', 'year', 'month', 'day', 'share', 'tweet')?>
			<p id="reserved-tags" data-tags='["<?php echo implode('","', $reserved)?>"]'><?php _e('reserved tags', 'mymail'); ?>: <code>{<?php echo implode('}</code>, <code>{', $reserved)?>}</code></p>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Permanent Tags' ,'mymail') ?>:</th>
			<td class="tags">
			<p class="description"><?php _e('These are permanent tags which cannot get deleted. The CAN-SPAM tag is required in many countries.', 'mymail'); ?> <a href="http://en.wikipedia.org/wiki/CAN-SPAM_Act_of_2003" class="external"><?php _e('Read more', 'mymail'); ?></a></p>
			<?php 
				if($tags = mymail_option('tags')){
					foreach($tags as $tag => $content){
					 ?>
				<div class="tag"><span><code>{<?php echo $tag?>}</code></span> &#10152; <input type="text" name="mymail_options[tags][<?php echo $tag?>]" value="<?php echo esc_attr($content)?>" class="regular-text tag-value"></div>
					 <?php					
					}
				}
			?>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Custom Tags' ,'mymail') ?>:</th>
			<td class="tags">
			<p class="description"><?php _e('Add your custom tags here. They work like permanent tags', 'mymail'); ?></p>
			<?php 
				if($tags = mymail_option('custom_tags')){
					foreach($tags as $tag => $content){
					 ?>
				<div class="tag"><span><code>{<?php echo $tag?>}</code></span> &#10152; <input type="text" name="mymail_options[custom_tags][<?php echo $tag?>]" value="<?php echo esc_attr($content)?>" class="regular-text tag-value"> <a class="tag-remove">&#10005;</a></div>
					 <?php					
					}
				}
			?>
			<input type="button" value="<?php _e('add' ,'mymail') ?>" class="button" id="mymail_add_tag">
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Special Tags' ,'mymail') ?>:</th>
			<td class="customfields">
			<p class="description"><?php _e('Special tags display dynamic content and are equally for all subscribers', 'mymail'); ?></p>
			<div class="customfield"><span><code>{tweet:username}</code></span> &#10152; <?php echo sprintf(__('displays the last tweet from Twitter user [username] (cache it for %s minutes)', 'mymail'), '<input type="text" name="mymail_options[tweet_cache_time]" value="'.esc_attr(mymail_option('tweet_cache_time')).'" class="small-text">'); ?></div>
			<div class="customfield"><span><code>{share:twitter}</code></span> &#10152; <?php echo sprintf(__('displays %1$s to share the newsletter via %2$s', 'mymail'), '<img src="' . MYMAIL_URI. '/assets/img/share/share_twitter.png">', 'Twitter'); ?></div>
			<div class="customfield"><span><code>{share:facebook}</code></span> &#10152; <?php echo sprintf(__('displays %1$s to share the newsletter via %2$s', 'mymail'), '<img src="' . MYMAIL_URI. '/assets/img/share/share_facebook.png">', 'Facebook'); ?></div>
			<div class="customfield"><span><code>{share:google}</code></span> &#10152; <?php echo sprintf(__('displays %1$s to share the newsletter via %2$s', 'mymail'), '<img src="' . MYMAIL_URI. '/assets/img/share/share_google.png">', 'Google+'); ?></div>
			<div class="customfield"><span><code>{share:linkedin}</code></span> &#10152; <?php echo sprintf(__('displays %1$s to share the newsletter via %2$s', 'mymail'), '<img src="' . MYMAIL_URI. '/assets/img/share/share_linkedin.png">', 'LinkedIn'); ?></div>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Dynamic Tags' ,'mymail') ?></th>
			<td><p class="description"><?php _e('Dynamic tags let you display your posts or pages in a reverse chronicle order. Some examples:', 'mymail'); ?></p>
			<div class="customfield"><span><code>{post_title:-1}</code></span> &#10152; <?php _e('displays the latest post title', 'mymail'); ?></div>
			<div class="customfield"><span><code>{page_title:-4}</code></span> &#10152; <?php _e('displays the fourth latest page title', 'mymail'); ?></div>
			<div class="customfield"><span><code>{post_image:-1}</code></span> &#10152; <?php _e('displays the feature image of the latest posts', 'mymail'); ?></div>
			<div class="customfield"><span><code>{post_image:-4|23}</code></span> &#10152; <?php _e('displays the feature image of the fourth latest posts. Uses the image with ID 23 if the post doesn\'t have a feature image', 'mymail'); ?></div>
			<div class="customfield"><span><code>{post_content:-1}</code></span> &#10152; <?php _e('displays the latest posts content', 'mymail'); ?></div>
			<div class="customfield"><span><code>{post_excerpt:-1}</code></span> &#10152; <?php _e('displays the latest posts excerpt or content if no excerpt is defined', 'mymail'); ?></div>
			<div class="customfield"><span><code>{post_date:-1}</code></span> &#10152; <?php _e('displays the latest posts date', 'mymail'); ?></div>
			<p class="description"><?php _e('you can also use absolute values', 'mymail'); ?></p>
			<div class="customfield"><span><code>{post_title:23}</code></span> &#10152; <?php _e('displays the post title of post ID 23', 'mymail'); ?></div>
			<div class="customfield"><span><code>{post_link:15}</code></span> &#10152; <?php _e('displays the permalink of post ID 15', 'mymail'); ?></div>
			<p class="description"><?php _e('Instead of "post_" and "page_" you can use custom post types too', 'mymail'); ?></p>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Image Fallback' ,'mymail') ?></th>
			<td><p class="description"><?php _e('Fallback image for dynamic image tags', 'mymail'); ?></p>
			<select name="mymail_options[fallback_image]" class="postform">
				<option value="0"><?php _e('Choose' ,'mymail') ?></option>
			<?php
				$images = get_posts(array(
					'posts_per_page' => -1,
					'post_type' => 'attachment',
					'post_mime_type' => array('image/jpeg', 'image/gif', 'image/png', 'image/tiff', 'image/bmp'),
				));
				
				$selected = mymail_option('fallback_image');
				foreach($images as $page){
			?>
				<option value="<?php echo $page->ID ?>"<?php selected($page->ID, $selected);?>><?php echo '['.$page->ID.'] '.esc_attr($page->post_title)?></option>
			<?php
				}
			?>
			</select>
			</td>
		</tr>
	</table>
	</div>
	
	
	
	<div id="tab-delivery" class="tab">
	<table class="form-table">
		<tr valign="top">
			<th scope="row"><?php _e('Number of mails sent' ,'mymail') ?></th>
			<td><?php echo sprintf(__('Send max %1$s emails at once and max %2$s within %3$s hours' ,'mymail'),'<input type="text" name="mymail_options[send_at_once]" value="'.mymail_option('send_at_once').'" class="small-text">', '<input type="text" name="mymail_options[send_limit]" value="'.mymail_option('send_limit').'" class="small-text">', '<input type="text" name="mymail_options[send_period]" value="'.mymail_option('send_period').'" class="small-text">')?>
			<p class="description"><?php _e('Depending on your hosting provider you can increase these values' ,'mymail') ?></p>
			<p class="description"><?php echo sprintf(__('You can still send %1$s mails within the next %2$s', 'mymail'), '<strong>'.max(0, mymail_option('send_limit')-((get_transient('_mymail_send_period_timeout') ? get_transient('_mymail_send_period') : 0 ))).'</strong>' , '<strong>'.human_time_diff((get_transient('_mymail_send_period_timeout') ? get_option('_transient_timeout__mymail_send_period_timeout', (time()+mymail_option('send_period')*3600)) : time()+mymail_option('send_period')*3600)).'</strong>'); ?></p>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Split campaigns' ,'mymail') ?></th>
			<td><label><input type="checkbox" name="mymail_options[split_campaigns]" value="1" <?php if(mymail_option('split_campaigns')) echo ' checked'; ?>> <?php _e('send campaigns simultaneously instead of one after the other' ,'mymail') ?></label> </td>
		</tr>
		<tr valign="top">
			<tr valign="top">
				<th scope="row"><?php _e('Time between mails' ,'mymail') ?></th>
				<td><input type="text" name="mymail_options[send_delay]" value="<?php echo mymail_option('send_delay'); ?>" class="small-text"> <?php _e('milliseconds', 'mymail'); ?> <p class="description"><?php _e('define a delay between mails in milliseconds if you have problems with sending two many mails at once', 'mymail'); ?></p>
</td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Send test email with current settings' ,'mymail') ?></th>
			<td>
			<input type="text" value="<?php echo $current_user->user_email ?>" autocomplete="off" class="form-input-tip" id="mymail_testmail">
			<input type="button" value="<?php _e('Send Test' ,'mymail') ?>" class="button mymail_sendtest" data-role="basic">
			<div class="loading test-ajax-loading"></div>
			<p class="description"><?php _e('You have to save your settings before you can test them!', 'mymail'); ?></p>
			</td>
		</tr>
	</table>
	
	<?php
		
		$deliverymethods = apply_filters('mymail_delivery_methods', array(
			'simple' => __('Simple' ,'mymail'),
			'smtp' => 'SMTP',
			'gmail' => 'Gmail',
		));
		
		$method = mymail_option('deliverymethod', 'simple');
		
	?>
	
	<h4><?php _e('Delivery Method', 'mymail'); ?>: <?php echo $deliverymethods[$method]?></h4>
	
	<div id="deliverynav" class="nav-tab-wrapper hide-if-no-js">
		<?php foreach($deliverymethods as $id => $name){?>
		<a class="nav-tab <?php if($method == $id) echo 'nav-tab-active' ?>" href="#<?php echo $id?>"><?php echo $name ?></a>
		<?php }?>
	</div>
	
	<input type="hidden" name="mymail_options[deliverymethod]" id="deliverymethod" value="<?php echo esc_attr($method); ?>" class="regular-text">
	
	<div class="subtab" id="subtab-simple" <?php if($method == 'simple') echo 'style="display:block"'; ?>>
		<p class="description">
		<?php _e('use this option if you don\'t have access to a SMTP server or any other provided options', 'mymail'); ?>
		</p>
		<?php $basicmethod = mymail_option('simplemethod', 'sendmail'); ?>
		<table class="form-table">
			<tr valign="top">
				<td><label><input type="radio" name="mymail_options[simplemethod]" value="sendmail" <?php checked($basicmethod, 'sendmail') ?> id="sendmail"> Sendmail</label>
				<div class="sendmailpath">
					<label>Sendmail Path: <input type="text" value="<?php echo mymail_option('sendmail_path'); ?>" class="form-input-tip" name="mymail_options[sendmail_path]"></label>
				</div>
				</td>
			</tr>
			<tr valign="top">
				<td><label><input type="radio" name="mymail_options[simplemethod]" value="mail" <?php checked($basicmethod, 'mail') ?>> PHPs mail() function</label></td>
			</tr>
			<tr valign="top">
				<td><label><input type="radio" name="mymail_options[simplemethod]" value="qmail" <?php checked($basicmethod, 'qmail') ?>> QMail</label></td>
			</tr>
		</table>
	</div>
	<div class="subtab" id="subtab-smtp" <?php if($method == 'smtp') echo 'style="display:block"'; ?>>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">SMTP Host : Port</th>
				<td><input type="text" name="mymail_options[smtp_host]" value="<?php echo esc_attr(mymail_option('smtp_host')); ?>" class="regular-text ">:<input type="text" name="mymail_options[smtp_port]" id="mymail_smtp_port" value="<?php echo mymail_option('smtp_port'); ?>" class="small-text smtp"></td>
			</tr>
			<tr valign="top">
				<th scope="row">Timeout</th>
				<td><input type="text" name="mymail_options[smtp_timeout]" value="<?php echo mymail_option('smtp_timeout'); ?>" class="small-text"> <?php _e('seconds', 'mymail'); ?></td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Secure connection' ,'mymail') ?></th>
				<?php 
					$secure = mymail_option('smtp_secure');
				?>
				<td>
				<label><input type="radio" name="mymail_options[smtp_secure]" value="" <?php if(!$secure) echo ' checked'; ?> class="smtp secure" data-port="25"> <?php _e('none' ,'mymail') ?></label>
				<label><input type="radio" name="mymail_options[smtp_secure]" value="ssl" <?php if($secure == 'ssl') echo ' checked'; ?> class="smtp secure" data-port="465"> SSL</label>
				<label><input type="radio" name="mymail_options[smtp_secure]" value="tls" <?php if($secure == 'tls') echo ' checked'; ?> class="smtp secure" data-port="465"> TLS</label>
				 </td>
			</tr>
			<tr valign="top">
				<th scope="row">SMTPAuth</th>
				<td><label><input type="checkbox" name="mymail_options[smtp_auth]" value="1" <?php if(mymail_option('smtp_auth')) echo ' checked'; ?>> <?php _e('If checked username and password are required' ,'mymail') ?></label> </td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Username' ,'mymail') ?></th>
				<td><input type="text" name="mymail_options[smtp_user]" value="<?php echo esc_attr(mymail_option('smtp_user')); ?>" class="regular-text"></td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Password' ,'mymail') ?></th>
				<td><input type="password" name="mymail_options[smtp_pwd]" value="<?php echo esc_attr(mymail_option('smtp_pwd')); ?>" class="regular-text"></td>
			</tr>
		</table>
	</div>
	<div class="subtab" id="subtab-gmail" <?php if($method == 'gmail') echo 'style="display:block"'; ?>>
		<p class="description">
		<?php _e('Gmail has a limit of 500 mails within 24 hours! Also sending a mail can take up to one second which is quite long. This options is only recommended for few subscribers. DKIM works only if set the from address to your Gmail address.', 'mymail'); ?>
		</p>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php _e('Username' ,'mymail') ?></th>
				<td><input type="text" name="mymail_options[gmail_user]" value="<?php echo esc_attr(mymail_option('gmail_user')); ?>" class="regular-text" placeholder="@gmail.com"></td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Password' ,'mymail') ?></th>
				<td><input type="password" name="mymail_options[gmail_pwd]" value="<?php echo esc_attr(mymail_option('gmail_pwd')); ?>" class="regular-text"></td>
			</tr>
		</table>
	</div>
	<?php foreach($deliverymethods as $id => $name){ 
		if(in_array($id, array('simple', 'smtp', 'gmail'))) continue;
		
	?>
	<div class="subtab" id="subtab-<?php echo $id ?>" <?php if($method == $id) echo 'style="display:block"'; ?>>
		<?php do_action('mymail_deliverymethod_tab') ?>
		<?php do_action('mymail_deliverymethod_tab_'.$id) ?>
	</div>
	<?php } ?>
	</div>
	
	
	
	<div id="tab-cron" class="tab">
	<table class="form-table">
		<tr valign="top" class="wp_cron">
			<th scope="row"><?php _e('Interval for sending emails' ,'mymail') ?></th>
			<td><?php echo sprintf(__('Send emails at most every %1$s minutes' ,'mymail'),'<input type="text" name="mymail_options[interval]" value="'.mymail_option('interval').'" class="small-text">')?> <p class="description"><?php _e('Optional if a real cron service is used', 'mymail'); ?></p></td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Cron Service' ,'mymail') ?></th>
			<td>
				<?php  $cron = mymail_option('cron_service'); ?>
				<label><input type="radio" class="cron_radio" name="mymail_options[cron_service]" value="wp_cron" <?php if($cron == 'wp_cron') echo ' checked'; ?> > <?php _e('Use the wp_cron function to send newsletters' ,'mymail') ?></label><br>
				<label><input type="radio" class="cron_radio" name="mymail_options[cron_service]" value="cron" <?php if($cron == 'cron') echo ' checked'; ?> > <?php _e('Use a real cron to send newsletters' ,'mymail') ?></label> <span class="description"><?php _e('reccomended for many subscribers' ,'mymail') ?></span>
			</td>
		</tr>
		<tr valign="top" class="cron_opts cron" <?php if($cron != 'cron') echo ' style="display:none"';?>>
			<th scope="row"><?php _e('Cron Settings' ,'mymail') ?></th>
			<td>
				<p>
				<input type="text" name="mymail_options[cron_secret]" value="<?php echo esc_attr(mymail_option('cron_secret')); ?>" class="regular-text"> <span class="description"><?php _e('a secret hash which is required to execute the cron' ,'mymail') ?></span>
				</p>
				<p><?php _e('You can leave a browser window open with following URL' ,'mymail') ?><br>
				<a href="<?php echo MYMAIL_URI.'/cron.php?'.mymail_option('cron_secret')?>" class="external"><code><?php echo MYMAIL_URI.'/cron.php?'.mymail_option('cron_secret')?></code></a><br>
				<?php _e('call it directly' ,'mymail') ?><br>
				<code>curl --silent <?php echo MYMAIL_URI.'/cron.php?'.mymail_option('cron_secret')?></code><br>
				<?php _e('or set up a cron' ,'mymail') ?><br>
				<code>*/<?php echo mymail_option('interval')?> * * * * GET <?php echo MYMAIL_URI.'/cron.php?'.mymail_option('cron_secret')?> > /dev/null</code></p>
				<p class="highlight"><?php _e('Last hit' ,'mymail') ?>: <span><?php $lasthit = get_option('mymail_cron_lasthit'); echo ($lasthit) ? ' '.sprintf(__('from %s', 'mymail'), $lasthit['ip']).', '.date(get_option('date_format').' '.get_option('time_format'), $lasthit['timestamp']).', '.sprintf(__('%s ago', 'mymail'), human_time_diff($lasthit['timestamp']-(get_option('gmt_offset')*3600))).' '.(($interv = round(($lasthit['timestamp']-$lasthit['oldtimestamp'])/60)) ? '('.__('Interval','mymail').': '.$interv.' '._x('min', 'short for minute', 'mymail').')' : '') : __('never' ,'mymail'); ?></span></p>
				<p class="description"><?php _e('You can setup an interval as low as one minute, but should consider a reasonable value of 5-15 minutes as well.', 'mymail'); ?></p>
				<p class="description"><?php _e('If you need help setting up a cron job please refer to the documentation that your provider offers.', 'mymail'); ?></p>
				<p class="description"><?php echo sprintf(__('Anyway, chances are high that either %1$s, %2$s or %3$s  documentation will help you.', 'mymail'),'<a href="http://docs.cpanel.net/twiki/bin/view/AllDocumentation/CpanelDocs/CronJobs#Adding a cron job" class="external">the CPanel</a>', '<a href="http://download1.parallels.com/Plesk/PP10/10.3.1/Doc/en-US/online/plesk-administrator-guide/plesk-control-panel-user-guide/index.htm?fileName=65208.htm" class="external">Plesk</a>', '<a href="http://www.thegeekstuff.com/2011/07/php-cron-job/" class="external">the crontab</a>'); ?></p>
			</td>
		</tr>
	</table>
	</div>

	<?php if(current_user_can('mymail_manage_capabilities')) : ?>

	<div id="tab-capabilities" class="tab">
	<table class="form-table"><?php 
			$roles = $wp_roles->get_names();
			unset($roles['administrator']);

	?>
	 
		<tr valign="top">
			<th scope="row"><?php _e('Set Capability' ,'mymail') ?> <p class="description"><?php _e('Define capabilities for each user role. To add new roles you can use a third party plugin. Administrator has always all privileges', 'mymail'); ?></p>
				<div id="current-cap"></div>

			</th>
			<td>
				<table id="capabilities-table">
					<thead>
						<tr>
						<th>&nbsp;</th><?php 
			foreach($roles as $role => $name){
				echo '<th><input type="hidden" name="mymail_options[roles]['.$role.'][]" value="">'.$name.'</th>';
				
			}?>			</tr>
					</thead>
					<tbody>
			<?php 
				include_once(MYMAIL_DIR.'/includes/capability.php');
				
				foreach($mymail_capabilities as $capability => $data){
				
			?>
					<tr><th><?php echo $data['title'] ?></th><?php 
					foreach($roles as $role => $name){
						$r = get_role($role);
						echo '<td><label title="'.sprintf(__('%1$s can %2$s', 'mymail'), $name, $data['title']).'"><input name="mymail_options[roles]['.$role.'][]" type="checkbox" value="'.$capability.'" '.checked(!empty($r->capabilities[$capability]), 1, false).' '.($role == 'administrator' ? 'readonly' : '').'></label></td>';
						
					}?>
					</tr>
			<?php } ?>
					</tbody>
				</table>
			</td>
		</tr>
	</table>
	</div>
	
	<?php endif;?>
	
	<div id="tab-bounce" class="tab">
	<table class="form-table">
	 
		<tr valign="top">
			<th scope="row"><?php _e('Bounce Address' ,'mymail') ?></th>
			<td><input type="text" name="mymail_options[bounce]" value="<?php echo esc_attr(mymail_option('bounce')); ?>" class="regular-text"> <span class="description"><?php _e('Undeliverable emails will return to this address', 'mymail'); ?></span></td>
		</tr>
		<tr valign="top">
			<th scope="row">&nbsp;</th>
			<td><label><input type="checkbox" name="mymail_options[bounce_active]" id="bounce_active" value="1" <?php if(mymail_option('bounce_active')) echo ' checked'; ?>> <?php _e('enable automatic bounce handling' ,'mymail') ?></label>
			</td>
		</tr>
		
	</table>
		<div id="bounce-options" <?php if(!mymail_option('bounce_active')) echo 'style="display:none"';?>>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">&nbsp;</th>
				<td><p class="description"><?php _e('If you would like to enable bouncing you have to setup a separate POP3 mail account', 'mymail'); ?></p></td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Server Address : Port' ,'mymail') ?></th>
				<td><input type="text" name="mymail_options[bounce_server]" value="<?php echo esc_attr(mymail_option('bounce_server')); ?>" class="regular-text">:<input type="text" name="mymail_options[bounce_port]" id="bounce_port" value="<?php echo mymail_option('bounce_port'); ?>" class="small-text"></td>
			</tr>
			<tr valign="top">
				<th scope="row">SSL</th>
				<td><label><input type="checkbox" name="mymail_options[bounce_ssl]" id="bounce_ssl" value="1" <?php if(mymail_option('bounce_ssl')) echo ' checked'; ?>> <?php _e('Use SSL. Default port is 995' ,'mymail') ?></label>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Username' ,'mymail') ?></th>
				<td><input type="text" name="mymail_options[bounce_user]" value="<?php echo esc_attr(mymail_option('bounce_user')); ?>" class="regular-text"></td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Password' ,'mymail') ?></th>
				<td><input type="password" name="mymail_options[bounce_pwd]" value="<?php echo esc_attr(mymail_option('bounce_pwd')); ?>" class="regular-text"></td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Delete messages', 'mymail'); ?></th>
				<td><label><input type="checkbox" name="mymail_options[bounce_delete]" value="1" <?php if(mymail_option('bounce_delete')) echo ' checked'; ?>> <?php _e('Delete messages without tracking code to keep postbox clear (recommended)' ,'mymail') ?></label>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Attempts' ,'mymail') ?></th>
				<td><?php
					$dropdown = '<select name="mymail_options[bounce_attempts]" class="postform">';
					$value = mymail_option('bounce_attempts');	
					for($i = 1; $i <= 10; $i++){
						$selected = ($value == $i) ? ' selected' : '';
					$dropdown .= '<option value="'.$i.'" '.$selected.'>'.$i.'</option>';
					}
					$dropdown .= '</select>';
				
				echo sprintf( __('%s attempts to deliver message until hardbounce' ,'mymail'), $dropdown);
				
				?></td>
			</tr>
		</table>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"></th>
				<td>
				<input type="button" value="<?php _e('Test bounce settings' ,'mymail') ?>" class="button button-primary mymail_bouncetest">
				<div class="loading bounce-ajax-loading"></div>
				<span class="bouncetest_status"><?php _e('sending message...', 'mymail'); ?></span>
				<p class="description"><?php _e('You have to save your settings before you can test them!', 'mymail'); ?></p>
				</td>
			</tr>
		</table>
		</div>
	</div>



	<div id="tab-authentication" class="tab">
		<?php 
			$spf = mymail_option('spf');
			$dkim = mymail_option('dkim');
		?>
	<table class="form-table">
		<tr valign="top">
			<th scope="row">&nbsp;</th>
			<td>
	<p class="description"><?php _e('You need to change the namespace records of your domain if you would like to use one of these methods. Ask your provider how to add "TXT namespace records". Changes take some time to get published on all DNS worldwide.', 'mymail'); ?></p>
			</td>
		</tr>
		<?php if($spf || $dkim): ?>
		<tr>
			<th scope="row"><?php _e('Check authentication settings' ,'mymail') ?></th>
			<td>
			<p class="description"><?php _e('Check your settings with isNotSpam.com. Results get sent to this address:', 'mymail'); ?></p>
			<input type="text" value="<?php echo $current_user->user_email ?>" autocomplete="off" class="form-input-tip" id="mymail_authenticationmail">
			<input type="button" value="<?php _e('Send Test' ,'mymail') ?>" class="button mymail_sendtest" data-role="authentication">
			<div class="loading test-ajax-loading"></div>
			<p class="description"><?php _e('You have to save your settings before you can test them!', 'mymail'); ?></p>
			</td>
		</tr>
		<?php endif; ?>
	</table>
	<table class="form-table">
	
		<tr valign="top">
			<th scope="row"><h4>SPF</h4></th>
			<td><label><input type="checkbox" name="mymail_options[spf]" id="mymail_spf" value="1" <?php if($spf) echo ' checked'; ?>> <?php _e('Use Sender Policy Framework' ,'mymail') ?>. <a href="http://en.wikipedia.org/wiki/Sender_Policy_Framework" class="external"><?php _e('read more') ?></a></label> </td>
		</tr>
	</table>
	<div class="spf-info" <?php if(!$spf) echo ' style="display:none"';?>>
	<table class="form-table no-margin">
		<?php if(mymail_option('spf_domain') && $spf) :?>
		<tr valign="top">
			<th scope="row">&nbsp;</th>
			<td>
				<?php 
				$host = mymail_option('spf_domain');
				if ( false === ( $records = get_transient( 'mymail_spf_records' ) ) ) {
				
					require_once MYMAIL_DIR.'/classes/libs/dns.class.php';
					
					$dns_query = new DNSQuery('8.8.8.8');
					$result = $dns_query->Query($host, 'TXT');
					
					if($result->count){
						$records = json_decode(json_encode($result->results), true);
						
						set_transient( 'mymail_spf_records', $records, 900 );
					}
					
				}
				
				$found = false;
				if($records){
					foreach($records as $r){
						if($r['typeid'] === 'TXT' && preg_match('#v=spf1 #', $r['data'])){
							$found = $r;
							break;
						}
					}
				}
				
				if($found) :?>
				
				<div class="verified"><p><?php echo sprintf(__('Domain %s', 'mymail'), '<strong>'.mymail_option('spf_domain').'</strong>').': '.__('TXT record found', 'mymail'); ?>: <code><?php echo $r['data']?></code></p>
				<?php else : ?>
				<div class="not-verified"><p><?php echo sprintf(__('Domain %s', 'mymail'), '<strong>'.mymail_option('spf_domain').'</strong>').': '.__('no TXT record found', 'mymail'); ?></p>
				<p><?php echo sprintf(__('No or wrong record found for %s. Please adjust the namespace records and add these lines:', 'mymail'), '<strong>'.$host.'</strong>'); ?>
				</p>
				<?php
					require_once MYMAIL_DIR.'/classes/libs/dns.class.php';
					
					$dns_query = new DNSQuery('8.8.8.8');
					$result = $dns_query->Query($host, 'MX');
					$result = wp_list_pluck($result->results, 'data');
				?>
				<dl>
					<dt><strong><?php echo mymail_option('spf_domain'); ?></strong> IN TXT</dt>
						<dd><textarea class="widefat" rows="1" id="spf-record">v=spf1 mx a include:<?php echo implode(' include:', $result)?> ~all</textarea></dd>
				</dl>
				<?php
				endif;
			?>
				</div>
				<p class="description"><?php echo sprintf(__('SPF doesn’t require any configuration on this settings page. This should give you some help to set it up correctly. If this SPF configuration doesn\'t work or your mails returned as spam you should ask your provider for help or change your delivery method or try %s', 'mymail'), '<a href="http://www.microsoft.com/mscorp/safety/content/technologies/senderid/wizard/default.aspx" class="external">'.__('this wizard', 'mymail').'</a>'); ?></p>
			</td>
		</tr>
		<?php endif; ?>
		<tr valign="top">
			<th scope="row">Domain</th>
			<td><input type="text" name="mymail_options[spf_domain]" id="spf-domain" value="<?php echo esc_attr(mymail_option('spf_domain')); ?>" class="regular-text dkim">
			<span class="description"><?php _e('The domain you would like to add a SPF record', 'mymail'); ?></span>
			</td>
		</tr>
	</table>
	</div>
	<table class="form-table">
		<tr valign="top">
			<th scope="row"><h4>DKIM</h4></th>
			<td><label><input type="checkbox" name="mymail_options[dkim]" id="mymail_dkim" value="1" <?php if($dkim) echo ' checked'; ?>> <?php _e('Use DomainKeys Identified Mail' ,'mymail') ?>. <a href="http://en.wikipedia.org/wiki/DomainKeys_Identified_Mail" class="external"><?php _e('read more') ?></a></label> </td>
		</tr>
	</table>
	<div class="dkim-info" <?php if(!$dkim) echo ' style="display:none"';?>>
	<table class="form-table no-margin">
	<?php if($dkim && mymail_option('dkim_private_key') && mymail_option('dkim_public_key')) : ?>
		<tr valign="top">
			<th scope="row">&nbsp;</th>
			<td>
				<?php 
			
				$pubkey = trim(str_replace(array('-----BEGIN PUBLIC KEY-----', '-----END PUBLIC KEY-----', "\n", "\r"), '', mymail_option('dkim_public_key')));
				$record = 'k=rsa;t=y;'."\n".'p='.$pubkey;
				
				$host = mymail_option('dkim_selector').'._domainkey.'.mymail_option('dkim_domain');
				
				if ( false === ( $records = get_transient( 'mymail_dkim_records' ) ) ) {
				
					require_once MYMAIL_DIR.'/classes/libs/dns.class.php';
					
					$dns_query = new DNSQuery('8.8.8.8');
					$result = $dns_query->Query($host, 'TXT');
					
					if($result->count){
						$records = json_decode(json_encode($result->results), true);;
						
						set_transient( 'mymail_dkim_records', $records, 900 );
					}
					
				}
				
				$found = false;
				if($records){
					foreach($records as $r){
						if($r['typeid'] === 'TXT' && preg_replace('#[^a-zA-Z0-9]#', '', $r['data']) == preg_replace('#[^a-zA-Z0-9]#', '', $record)){
							$found = $r;
							break;
						}
					}
				}
				if($found) : ?>
				
				<div class="verified"><p><?php echo sprintf(__('Domain %s', 'mymail'), '<strong>'.mymail_option('dkim_domain').'</strong>').', Selector: <strong>'.mymail_option('dkim_selector').'</strong>: '.__('verified', 'mymail'); ?></p>
				<?php else : ?>
				<div class="not-verified"><p><?php echo sprintf(__('Domain %s', 'mymail'), '<strong>'.mymail_option('dkim_domain').'</strong>').': '.__('not verified', 'mymail'); ?></p>
				<p><?php echo sprintf(__('No or wrong record found for %s. Please adjust the namespace records and add these lines:', 'mymail'), '<strong>'.$host.'</strong>'); ?>
				</p>
				<dl>
					<dt><strong><?php echo '_domainkey.'.mymail_option('dkim_domain'); ?></strong> IN TXT</dt>
						<dd><textarea class="widefat" rows="1" disabled>t=y;0=~</textarea></dd>
					<dt><strong><?php echo mymail_option('dkim_selector').'._domainkey.'.mymail_option('dkim_domain') ?></strong> IN TXT</dt>
						<dd><textarea class="widefat" rows="4" disabled><?php echo $record ?></textarea></dd>
				</dl>
				<?php endif; ?>
				</div>
			</td>
		</tr>
	<?php endif; ?>
		<tr valign="top">
			<th scope="row">DKIM Domain</th>
			<td><input type="text" name="mymail_options[dkim_domain]" value="<?php echo esc_attr(mymail_option('dkim_domain')); ?>" class="regular-text dkim">
			<span class="description"><?php _e('The domain you have set the TXT namespace records', 'mymail'); ?></span>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">DKIM Selector</th>
			<td><input type="text" name="mymail_options[dkim_selector]" value="<?php echo esc_attr(mymail_option('dkim_selector')); ?>" class="regular-text dkim">
			<span class="description"><?php _e('The selector is used to identify the keys used to attach a token to the email', 'mymail'); ?></span>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">DKIM Identity</th>
			<td><input type="text" name="mymail_options[dkim_identity]" value="<?php echo esc_attr(mymail_option('dkim_identity')); ?>" class="regular-text dkim">
			<span class="description"><?php _e('You can leave this field blank unless you know what you do', 'mymail'); ?></span>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">DKIM Pass Phrase</th>
			<td><input type="text" name="mymail_options[dkim_passphrase]" value="<?php echo esc_attr(mymail_option('dkim_passphrase')); ?>" class="regular-text dkim">
			<span class="description"><?php _e('You can leave this field blank unless you know what you do', 'mymail'); ?></span>
			</td>
		</tr>
	</table>
	<table class="form-table">
		<tr valign="top">
			<th scope="row"><h4><?php _e('Keys', 'mymail'); ?></h4></th>
			<td>
			<p class="description">
			<?php _e('If you have defined the domain and a selector you have to generate a public and a private key. Once created you have to add some TXT namespace records at your mail provider', 'mymail'); ?>.
			<?php _e("DKIM often doesn't work out of the box. You may have to contact your email provider to get more information", 'mymail'); ?>.
			<?php _e("Changing namespace entries can take up to 48 hours to take affect around the world.", 'mymail'); ?>.
			<?php _e("It's recommend to change the keys occasionally", 'mymail'); ?>.
			<?php _e("If you change one of the settings above new keys are required", 'mymail'); ?>.
			<?php _e("Some providers don't allow TXT records with a specific size. Choose less bits in this case", 'mymail'); ?>.
			</p>
			</td>
		</tr>
	</table>
	<?php if($dkim && mymail_option('dkim_private_key') && mymail_option('dkim_public_key')) : ?>
	<table class="form-table" id="dkim_keys_active">
		<tr valign="top">
			<th scope="row">DKIM Public Key</th>
			<td><textarea name="mymail_options[dkim_public_key]" rows="10" cols="40" class="large-text code"><?php echo esc_attr(mymail_option('dkim_public_key')); ?></textarea>
		</tr>
		<tr valign="top">
			<th scope="row">DKIM Private Key
				<p class="description">
			<?php _e('Private keys should be kept private. Don\'t share them or post it somewhere', 'mymail'); ?>
			</p>
			</th>
			<td><textarea name="mymail_options[dkim_private_key]" rows="10" cols="40" class="large-text code"><?php echo esc_attr(mymail_option('dkim_private_key')); ?></textarea>
			<input type="hidden" name="mymail_options[dkim_private_hash]" value="<?php echo esc_attr(mymail_option('dkim_private_hash')); ?>" class="regular-text dkim"></td>
		</tr>
	</table>
	<?php endif; ?>
	<table class="form-table">
		<tr valign="top">
			<th scope="row"><?php _e('new Keys', 'mymail'); ?></th>
			<td>
			<p class="dkim-create-keys">
				<?php $bitsize = mymail_option('dkim_bitsize', 512); ?>	
				<?php _e('Bit Size', 'mymail'); ?>:
				<label> <input type="radio" name="mymail_options[dkim_bitsize]" value="512" <?php checked($bitsize, 512)?>> 512</label>&nbsp;
				<label> <input type="radio" name="mymail_options[dkim_bitsize]" value="768" <?php checked($bitsize, 768)?>> 768</label>&nbsp;
				<label> <input type="radio" name="mymail_options[dkim_bitsize]" value="1024" <?php checked($bitsize, 1024)?>> 1024</label>&nbsp;
				<label> <input type="radio" name="mymail_options[dkim_bitsize]" value="2048" <?php checked($bitsize, 2048)?>> 2048</label>&nbsp;
				<input type="submit" class="button-primary" value="<?php _e('generate Keys' ,'mymail') ?>" name="mymail_generate_dkim_keys" id="mymail_generate_dkim_keys" />
			</p>
			</td>
		</tr>
	</table>
	</div>
	</div>
	
	<?php if(!get_option('mymail_purchasecode_disabled')) : ?>
	
	<div id="tab-purchasecode" class="tab">
	<table class="form-table">
		<tr valign="top">
			<th scope="row"><?php _e('Purchasecode', 'mymail'); ?></th>
			<td><input type="text" name="mymail_options[purchasecode]" value="<?php echo esc_attr(mymail_option('purchasecode')); ?>" class="regular-text" placeholder="XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX">
			<p class="description"><?php _e('Enter your purchasecode to enable automatic updates', 'mymail'); ?></p>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Disable this tab' ,'mymail') ?></th>
			<td><label><input type="checkbox" name="mymail_purchasecode_disabled" value="1" <?php if(get_option('mymail_purchasecode_disabled')) echo ' checked'; ?>> <?php _e('Hide this tab', 'mymail') ?> <p class="description"><?php echo sprintf(__('If you disable this tab you cannot access it anymore from the settings page. If you would like to bring it back delete the %1$s option from the %2$s table', 'mymail'), '"mymail_purchasecode_disabled"', '"'.$wpdb->options.'"'); ?></p></label>
			</td>
		</tr>
	</table>
	</div>
	
	<?php else :?>
	
	<input type="hidden" name="mymail_options[purchasecode]" value="<?php echo esc_attr(mymail_option('purchasecode')); ?>">
	
	<?php endif;?>

	<?php foreach($extra_sections as $id => $name){ ?>
	<div id="tab-<?php echo $id; ?>" class="tab">
		<?php do_action('mymail_section_tab') ?>
		<?php do_action('mymail_section_tab_'.$id) ?>
	</div>
	<?php }?>
	
	<?php do_action('mymail_settings') ?>
	
	<input type="password" class="hidden" name="mymail_foo" value="bar"><input class="hidden" type="password" name="mymail_bar" value="foo">

	<p>
		<input type="submit" class="button-primary" value="<?php _e('Save Changes' ,'mymail') ?>" />
	</p>

</div>
</form>
