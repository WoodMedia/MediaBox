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
	if ( !in_array( $post->post_status, array('publish', 'future', 'private', 'subscribed', 'unsubscribed', 'hardbounced') ) || 0 == $post->ID ) {
		if ( $can_publish ) :
			if ( !empty($post->post_date_gmt) && time() < strtotime( $post->post_date_gmt . ' +0000' ) ) : ?>
			<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e('Schedule') ?>" />
			<?php submit_button( __( 'Schedule' ), 'primary', 'publish', false, array( 'tabindex' => '5', 'accesskey' => 'p' ) ); ?>
	<?php	else : ?>
			<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e('Publish') ?>" />
			<?php submit_button( __( 'Save' ), 'primary', 'publish', false, array( 'tabindex' => '5', 'accesskey' => 'p' ) ); ?>
	<?php	endif;
		else : ?>
			<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e('Submit for Review') ?>" />
			<?php submit_button( __( 'Submit for Review' ), 'primary', 'publish', false, array( 'tabindex' => '5', 'accesskey' => 'p' ) ); ?>
	<?php
		endif;
	} else { ?>
			<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e('Update') ?>" />
		<?php if ( 'subscribed' == $post->post_status ) {?>
			<input name="unsubscribe" type="submit" class="button" id="unsubscribe" tabindex="15" accesskey="u" value="<?php esc_attr_e('Unsubscribe', 'mymail') ?>" />
		<?php } else if ( 'unsubscribed' == $post->post_status ) {?>
			<input name="subscribe" type="submit" class="button" id="subscribe" tabindex="15" accesskey="u" value="<?php esc_attr_e('Subscribe', 'mymail') ?>" />
		<?php } else if ( 'hardbounced' == $post->post_status ) {?>
			<input name="subscribe" type="submit" class="button" id="hardbounce" tabindex="15" accesskey="u" value="<?php esc_attr_e('Subscribe', 'mymail') ?>" />
		<?php } ?>
			<input name="save" type="submit" class="button-primary" id="publish" tabindex="16" accesskey="p" value="<?php esc_attr_e('Update') ?>" />
	<?php
	} ?>
	</div>
<div class="clear"></div>
</div>

</div>
