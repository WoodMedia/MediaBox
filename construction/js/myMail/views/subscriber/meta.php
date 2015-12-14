<?php
	
$meta = isset($this->user_data['_meta']['ip']) ? $this->user_data['_meta'] : false;
	
if(isset($meta['signuptime'])) : ?>

<p>
<label><?php _e('Subscribed', 'mymail'); ?></label><br>
<strong><?php echo date(get_option('date_format').' '.get_option('time_format'), $meta['signuptime']) ?></strong>
<?php 
echo (!empty($meta['signupip']) ? sprintf(__('with IP %s', 'mymail'), '<strong>'.$meta['signupip'].'</strong>') : ', '.__('IP address unknown', 'mymail'))
?></strong>
</p>

<?php if($meta['signuptime'] != $meta['confirmtime']) : ?>
	
<p>
<label><?php _e('Confirmed', 'mymail'); ?></label><br>
<strong><?php echo date(get_option('date_format').' '.get_option('time_format'), $meta['confirmtime']) ?></strong>
<?php 
echo (!empty($meta['signupip']) ? sprintf(__('with IP %s', 'mymail'), '<strong>'.$meta['confirmip'].'</strong>') : ', '.__('IP address unknown', 'mymail'))
?></strong>
</p>

<?php endif; ?>
	
<label><?php _e('Latest known IP address', 'mymail'); ?>:<br>
	<input type="text" name="mymail_data[_meta][ip]" value="<?php echo $meta['ip'] ?>" autocomplete="off" >
</label>


<?php else: ?>

<p> <?php _e('no meta data found for this subscriber', 'mymail'); ?> </p>
<label><?php _e('Latest known IP address', 'mymail'); ?>:<br>
	<input type="text" name="mymail_data[_meta][ip]" value="<?php echo $meta['ip'] ?>" autocomplete="off" >
</label>

<?php endif; ?>
		
