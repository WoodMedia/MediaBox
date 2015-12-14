<?php 
?><div id="user_image_wrap">
<div id="user_image" title="<?php _e('Source', 'mymail') ?>: Gravatar.com" data-email="<?php echo $post->post_title?>" style="background-image:url(<?php echo $this->get_gravatar_uri($post->post_title)?>)"></div>
<?php if(mymail_option('track_users') && !empty($this->user_data['_meta']['ip'])) : $meta = $this->user_data['_meta'];
	$city = $country = '';
	if(mymail_option('trackcities')) : 
		$city = mymail_ip2City($meta['ip']);
	endif;
	if(mymail_option('trackcountries')) :
		$country = mymail_ip2Country($meta['ip'], 'name');
	endif;
	
 ?>
<div id="user_location">
	<p>
		<?php 
		if(isset($city->city)) echo '<a target="_blank" href="http://maps.google.com/maps?q='.$city->latitude.','.$city->longitude.'">'.$city->city.'</a>, ';
		
		if($country != 'unknown') echo $country ?><br>
	</p>
</div>
<?php endif; ?>
</div>
<div id="user_fields">
<div>
<label for="mymail_data_firstname"><?php echo mymail_text('firstname', __('First Name', 'mymail')); ?></label> <code title="<?php echo sprintf(__('use %1$s as placeholder tag to replace it with %2$s', 'mymail'), '{fullname}', '&quot;'.trim($this->user_data['firstname'].' '.$this->user_data['lastname']).'&quot;' )?>">{fullname}</code> <code title="<?php echo sprintf(__('use %1$s as placeholder tag to replace it with %2$s'), '{firstname}', '&quot;'.$this->user_data['firstname'].'&quot;' )?>">{firstname}</code><br><input type="text" id="mymail_data_firstname" name="mymail_data[firstname]" tabindex="1" value="<?php echo $this->user_data['firstname']; ?>" class="regular-text input">
</div>
<div>
<label for="mymail_data_lastname"><?php echo mymail_text('lastname', __('Last Name', 'mymail')); ?></label> <code title="<?php echo sprintf(__('use %1$s as placeholder tag to replace it with %2$s', 'mymail'), '{lastname}', '&quot;'.$this->user_data['lastname'].'&quot;' )?>">{lastname}</code><br><input type="text" id="mymail_data_lastname" name="mymail_data[lastname]" tabindex="1" value="<?php echo $this->user_data['lastname']; ?>" class="regular-text input">
</div>
<?php
if($customfield = mymail_option('custom_field')) :
	foreach($customfield as $field => $data){
?>
<div class="custom-field-wrap">
<?php if(!in_array($data['type'], array('checkbox'))) :?>
<label for="mymail_data_<?php echo $field?>"><?php echo $data['name']?></label>
<code title="<?php echo sprintf(__('use %1$s as placeholder tag to replace it with %2$s', 'mymail'), '{'.$field.'}', '&quot;'.(isset($this->user_data[$field]) ? $this->user_data[$field] : '').'&quot;' )?>">{<?php echo $field?>}</code>
<?php endif; ?> 
	<div>
<?php
		switch($data['type']){
			
			case 'dropdown':
			?>
		<select id="mymail_data_<?php echo $field?>" name="mymail_data[<?php echo $field?>]" tabindex="1">
			<?php foreach($data['values'] as $v){?>
				<option value="<?php echo $v ?>" <?php selected((isset($this->user_data[$field])) ? $this->user_data[$field] : $data['default'], $v) ?>><?php echo $v ?></option>
			<?php } ?>
		</select>
			<?php
				break;
			case 'radio':
			?>
			<ul>
			<?php
				$i = 0;
				foreach($data['values'] as $v){ ?>
		<li><label><input type="radio" id="mymail_data_<?php echo $field ?>_<?php echo $i++ ?>" name="mymail_data[<?php echo $field?>]" value="<?php echo $v ?>" tabindex="1" <?php if(isset($this->user_data[$field])) checked($this->user_data[$field], $v) ?>> <?php echo $v ?> </label></li>
			<?php } 
			?>
			</ul>
			<?php
				break;
			case 'checkbox':
 ?>
		<label><input type="checkbox" id="mymail_data_<?php echo $field ?>" name="mymail_data[<?php echo $field?>]" value="1" tabindex="1" <?php if(isset($this->user_data[$field])) checked($this->user_data[$field], true) ?>> <?php echo $data['name'] ?> </label>
			</ul>
			<?php
				break;
			default:
			?>
		<input type="text" id="mymail_data_<?php echo $field ?>" name="mymail_data[<?php echo $field?>]" tabindex="1" value="<?php if(isset($this->user_data[$field])) echo $this->user_data[$field]; ?>" class="regular-text input">
			<?php
		}
		?>

	</div>
</div>
		<?php 
	}	
endif;

if(mymail_option('track_users') && isset($this->user_data['_meta'])) : 
foreach($this->user_data['_meta'] as $key => $meta) { if($key == 'ip') continue; ?>
<input type="hidden" value="<?php echo $meta ?>" name="mymail_data[_meta][<?php echo $key?>]">
<?php 
}
endif; ?>
<p class="howto"><?php echo sprintf(__('add more custom fields at the %s', 'mymail'), '<a href="options-general.php?page=newsletter-settings#subscribers">'.__('Settings Page', 'mymail').'</a>') ?></p>
</div>
