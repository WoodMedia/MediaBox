<div class="submitbox" id="submitpost">

<?php do_action('post_submitbox_start'); ?>
<div id="preview-action">
<input type="hidden" name="wp-preview" id="wp-preview" value="" />
</div>
<div class="clear"></div>

<div id="misc-publishing-actions">

	<div id="delete-action">
	<?php
	if ( current_user_can( "delete_post", $post->ID ) ) {
		if ( !EMPTY_TRASH_DAYS )
			$delete_text = __('Delete Permanently');
		else
			$delete_text = __('Move to Trash');
		?>
	<a class="submitdelete deletion" href="<?php echo get_delete_post_link($post->ID); ?>"><?php echo $delete_text; ?></a>
	<?php } ?>
	</div>
	
	<div id="publishing-action">
	<span class="spinner ajax-loading" id="ajax-loading"></span>
	<?php
	if ($post->post_status == 'finished') {
		?>
	<?php if (current_user_can('duplicate_newsletters') && current_user_can('duplicate_others_newsletters', $post->ID)) { ?><a class="button duplicate" href="edit.php?post_type=newsletter&duplicate=<?php echo $post->ID?>&edit=1&_wpnonce=<?php echo wp_create_nonce('mymail_nonce')?>"><?php _e('Duplicate' ,'mymail')?></a> <?php } ?>
		<?php
	}else if ( !in_array( $post->post_status, array('publish', 'future', 'private', 'paused') ) || 0 == $post->ID ) {
		if ( $can_publish ) :
			if ($post->post_status == 'active') : ?>
			<a class="button pause" href="edit.php?post_type=newsletter&pause=<?php echo $post->ID?>&edit=1&_wpnonce=<?php echo wp_create_nonce('mymail_nonce')?>"><?php _e('Pause' ,'mymail')?></a>
	<?php	elseif ($post->post_status == 'queued') : ?>
				<?php if($this->campaign_data['sent']) : ?>
			<input name="send_now" type="submit" class="button" value="<?php esc_attr_e('Continue', 'mymail') ?>" />
				<?php else : ?>
			<input name="send_now" type="submit" class="button" value="<?php esc_attr_e('Send now', 'mymail') ?>" />
				<?php endif; ?>
	<?php	endif;
			if ( !empty($post->post_date_gmt) && time() < strtotime( $post->post_date_gmt . ' +0000' ) ) : ?>
			<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e('Schedule') ?>" />
			<?php submit_button( __( 'Schedule' ), 'primary', 'publish', false, array( 'accesskey' => 'p' ) ); ?>
	<?php	elseif($post->post_status != 'active') : ?>
			<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e('Publish') ?>" />
			<?php submit_button( __( 'Save' ), 'primary', 'publish', false, array( 'accesskey' => 'p' ) ); ?>
	<?php	endif;
		else : ?>
			<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e('Submit for Review') ?>" />
			<?php submit_button( __( 'Submit for Review' ), 'primary', 'publish', false, array( 'accesskey' => 'p' ) ); ?>
	<?php
		endif;
	} else { 
			if ($can_publish && in_array($post->post_status, array('paused', 'queued'))) : ?>
				<?php if($this->campaign_data['sent']) : ?>
			<input name="send_now" type="submit" class="button" value="<?php esc_attr_e('Continue', 'mymail') ?>" />
				<?php else : ?>
			<input name="send_now" type="submit" class="button" value="<?php esc_attr_e('Send now', 'mymail') ?>" />
				<?php endif; ?>
	<?php	endif; ?>
			<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e('Update') ?>" />
			<input name="save" type="submit" class="button-primary" id="publish" tabindex="15" accesskey="p" value="<?php esc_attr_e('Update') ?>" />
	<?php
	} ?>
	</div>
<div class="clear"></div>
</div>

</div>
